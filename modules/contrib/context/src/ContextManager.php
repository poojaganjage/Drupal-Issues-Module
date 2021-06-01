<?php

namespace Drupal\context;

use Drupal\context\Entity\Context;
use Drupal\context\Plugin\ContextReaction\Blocks;
use Drupal\Core\Entity\EntityFormBuilderInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Plugin\ContextAwarePluginInterface;
use Drupal\Core\Condition\ConditionPluginCollection;
use Drupal\Component\Plugin\Exception\ContextException;
use Drupal\Core\Condition\ConditionAccessResolverTrait;
use Drupal\Core\Plugin\Context\ContextHandlerInterface;
use Drupal\Core\Plugin\Context\ContextRepositoryInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Theme\ThemeManagerInterface;

/**
 * This is the manager service for the context module and should not be
 * confused with the built in contexts in Drupal.
 */
class ContextManager {

  use ConditionAccessResolverTrait;
  use StringTranslationTrait;

  /**
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * @var \Drupal\Core\Plugin\Context\ContextRepositoryInterface
   */
  protected $contextRepository;

  /**
   * @var \Drupal\Core\Plugin\Context\ContextHandlerInterface
   */
  protected $contextHandler;

  /**
   * If the context conditions has been evaluated then this is set to TRUE
   * otherwise FALSE.
   *
   * @var bool
   */
  protected $contextConditionsEvaluated = FALSE;

  /**
   * An array of contexts that have been evaluated and are active.
   *
   * @var array
   */
  protected $activeContexts = [];
  /**
   * @var \Drupal\Core\Entity\EntityFormBuilderInterface
   */
  private $entityFormBuilder;

  /**
   * @var \Drupal\Core\Theme\ThemeManagerInterface;
   */
  protected $themeManager;

  /**
   * Construct.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The Drupal entity manager service.
   * @param ContextRepositoryInterface $contextRepository
   *   The drupal context repository service.
   * @param ContextHandlerInterface $contextHandler
   *   The Drupal context handler service.
   * @param ThemeManagerInterface $themeManager
   *   The Drupal theme manager service.
   * @param EntityFormBuilderInterface $entityFormBuilder
   *   The Drupal EntityFormBuilder service.
   */
  public function __construct(
    EntityTypeManagerInterface $entityTypeManager,
    ContextRepositoryInterface $contextRepository,
    ContextHandlerInterface $contextHandler,
    EntityFormBuilderInterface $entityFormBuilder,
    ThemeManagerInterface $themeManager
  )
  {
    $this->entityTypeManager = $entityTypeManager;
    $this->contextRepository = $contextRepository;
    $this->contextHandler = $contextHandler;
    $this->entityFormBuilder = $entityFormBuilder;
    $this->themeManager = $themeManager;
  }

  /**
   * Get all contexts.
   *
   * @return Context[]
   */
  public function getContexts() {

    $contexts = $this->entityTypeManager->getStorage('context')->loadByProperties();

    // Sort the contexts by their weight.
    uasort($contexts, [$this, 'sortContextsByWeight']);

    return $contexts;
  }

  /**
   * Get all contexts sorted by their group and sorted by their weight inside
   * of each group.
   *
   * @return array
   */
  public function getContextsByGroup() {
    $contexts = $this->getContexts();

    $groups = [];

    // Add each context to their respective groups.
    foreach ($contexts as $context_id => $context) {
      $group = $context->getGroup();

      if ($group === Context::CONTEXT_GROUP_NONE) {
        $group = 'not_grouped';
      }

      $groups[$group][$context_id] = $context;
    }

    return $groups;
  }

  /**
   * Check to validate that the context name does not already exist.
   *
   * @param string $name
   *   The machine name of the context to validate.
   *
   * @return bool
   */
  public function contextExists($name) {
    $entity = $this->entityTypeManager->getStorage('context')->loadByProperties(['name' => $name]);

    return (bool) $entity;
  }

  /**
   * Check to see if context conditions has been evaluated.
   *
   * @return bool
   */
  public function conditionsHasBeenEvaluated() {
    return $this->contextConditionsEvaluated;
  }

  /**
   * Get the evaluated and active contexts.
   *
   * @return \Drupal\context\ContextInterface[]
   */
  public function getActiveContexts() {
    if ($this->conditionsHasBeenEvaluated()) {
      return $this->activeContexts;
    }

    $this->evaluateContexts();

    return $this->activeContexts;
  }

  /**
   * Evaluate all context conditions.
   */
  public function evaluateContexts() {

    /** @var \Drupal\context\ContextInterface $context */
    foreach ($this->getContexts() as $context) {
      if ($this->evaluateContextConditions($context) && !$context->disabled()) {
        $this->activeContexts[] = $context;
      }
    }

    $this->contextConditionsEvaluated = TRUE;
  }

  /**
   * Get all active reactions or reactions of a certain type.
   *
   * @param string $reactionType
   *   Either the reaction class name or the id of the reaction type to get.
   *
   * @return ContextReactionInterface[]
   */
  public function getActiveReactions($reactionType = NULL) {
    $reactions = [];

    foreach ($this->getActiveContexts() as $context) {

      // If no reaction type has been specified then add all reactions and
      // continue to the next context.
      if (is_null($reactionType)) {
        foreach ($context->getReactions() as $reaction) {
          // Only return block reaction if there is a block applied to the current theme.
          if ($reaction instanceof Blocks) {
            $blocks = $reaction->getBlocks();
            $current_theme = $this->getCurrentTheme();
            foreach ($blocks as $block) {
              if ($block->getConfiguration()['theme'] == $current_theme) {
                $reactions[] = $reaction;
                break;
              }
            }
          }
          else {
            $reactions[] = $reaction;
          }
        }
        continue;
      }

      $contextReactions = $context->getReactions();

      // Filter the reactions based on the reaction type.
      foreach ($contextReactions as $reaction) {

        if (class_exists($reactionType) && $reaction instanceof $reactionType) {
          $reactions[] = $reaction;
          continue;
        }

        if ($reaction->getPluginId() === $reactionType) {
          $reactions[] = $reaction;
          continue;
        }
      }
    }

    return $reactions;
  }

  /**
   * Evaluate a contexts conditions.
   *
   * @param ContextInterface $context
   *   The context to evaluate conditions for.
   *
   * @return bool
   */
  public function evaluateContextConditions(ContextInterface $context) {
    $conditions = $context->getConditions();

    // Apply context to any context aware conditions.
    // Abort if the application of contexts has been unsuccessful
    // similarly to BlockAccessControlHandler::checkAccess().
    if (!$this->applyContexts($conditions)) {
      return FALSE;
    }

    // Set the logic to use when validating the conditions.
    $logic = $context->requiresAllConditions()
      ? 'and'
      : 'or';

    // Of there are no conditions then the context will be
    // applied as a site wide context.
    if (!count($conditions)) {
      $logic = 'and';
    }

    return $this->resolveConditions($conditions, $logic);
  }

  /**
   * Apply context to all the context aware conditions in the collection.
   *
   * @param ConditionPluginCollection $conditions
   *   A collection of conditions to apply context to.
   *
   * @return bool
   */
  protected function applyContexts(ConditionPluginCollection &$conditions) {

    foreach ($conditions as $condition) {
      if ($condition instanceof ContextAwarePluginInterface) {
        try {
          $contexts = $this->contextRepository->getRuntimeContexts(array_values($condition->getContextMapping()));
          $this->contextHandler->applyContextMapping($condition, $contexts);
        }
        catch (ContextException $e) {
          return FALSE;
        }
      }
    }

    return TRUE;
  }

  /**
   * Get a rendered form for the context.
   * @param \Drupal\context\ContextInterface $context
   * @param string $formType
   * @param array $form_state_additions
   * @return array
   */
  public function getForm(ContextInterface $context, $formType = 'edit', array $form_state_additions = []) {
    return $this->entityFormBuilder->getForm($context, $formType, $form_state_additions);
  }

  /**
   * Sorts an array of context entities by their weight.
   *
   * Callback for uasort().
   *
   * @param ContextInterface $a
   *   First item for comparison.
   *
   * @param ContextInterface $b
   *   Second item for comparison.
   *
   * @return int
   *   The comparison result for uasort().
   */
  public function sortContextsByWeight(ContextInterface $a, ContextInterface $b) {
    if ($a->getWeight() == $b->getWeight()) {
      return 0;
    }

    return ($a->getWeight() < $b->getWeight()) ? -1 : 1;
  }

  /**
   * Get current active theme.
   *
   * @return string
   *   Current active theme name.
   */
  private function getCurrentTheme() {
    return $this->themeManager->getActiveTheme()->getName();
  }

}

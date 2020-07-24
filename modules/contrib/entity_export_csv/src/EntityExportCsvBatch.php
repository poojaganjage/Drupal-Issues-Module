<?php

namespace Drupal\entity_export_csv;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\StreamWrapper\PrivateStream;
use Drupal\Core\StreamWrapper\TemporaryStream;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\Url;
use Drupal\entity_export_csv\Plugin\FieldTypeExportInterface;
use Drupal\facets\Exception\Exception;
use Drupal\search_api\Plugin\search_api\datasource\ContentEntity;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Define entity export csv batch.
 */
class EntityExportCsvBatch {

  /**
   * Export entity data.
   *
   * @param $entity_type_id
   *   The entity type on which to export.
   * @param $bundle
   *   The entity bundle type.
   * @param array $fields
   *   An array of fields to export keyed by field_name.
   *   The structure of this array is :
   *     - field_name
   *       - enable : 0|1
   *       - exporter : the field type exporter id to use.
   *       - form
   *         - options
   *           - plugin configuration key : value
   *           - plugin configuration key : value
   *           - etc.
   * @param string $langcode
   *   The langcode to export.
   * @param array $context
   *   An array of the batch context.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public static function export($entity_type_id, $bundle, array $fields, $langcode, array &$context) {
    $limit = 10;
    $messenger = \Drupal::messenger();
    /** @var \Drupal\entity_export_csv\EntityExportCsvManagerInterface $entity_export_csv_manager */
    $entity_export_csv_manager = \Drupal::service('entity_export_csv.manager');
    /** @var \Drupal\entity_export_csv\Plugin\FieldTypeExportManagerInterface $field_type_export_manager */
    $field_type_export_manager = \Drupal::service('plugin.manager.field_type_export');
    $definitions = $entity_export_csv_manager->getBundleFields($entity_type_id, $bundle, TRUE);

    if (!isset($context['results']['file_path'])) {
      $entity_types = $entity_export_csv_manager->getContentEntityTypesEnabled(TRUE);
      $bundles = $entity_export_csv_manager->getBundlesEnabledPerEntityType($entity_type_id, TRUE);
      if (!isset($entity_types[$entity_type_id]) || !isset($bundles[$bundle])) {
        $messenger->addWarning(t('Exporting this entity type or bundle is not allowed anymore.'));
        $context['finished'] = 1;
        return;
      }
      else {
        $context['results']['entity_type_label'] = $entity_types[$entity_type_id];
        $context['results']['bundle_label'] = $bundles[$bundle];
      }

      try {
        $file_path = static::prepareExportFile($entity_type_id, $bundle, $context);
      }
      catch (\Exception $e) {
        $file_path = NULL;
      }
      if (!$file_path) {
        $messenger->addError(t('Unable to create the export file. Please check the system file permissions.'));
        $context['finished'] = 1;
        return;
      }

      $headers = [];
      foreach ($fields as $field_name => $values) {
        $field_definition = isset($definitions[$field_name]) ? $definitions[$field_name] : NULL;
        if (!$field_definition) {
          continue;
        }
        $enable = $values['enable'];
        $exporter = $values['exporter'];
        $configuration = $values['form']['options'];
        if (empty($enable)) {
          continue;
        }
        try {
          /** @var \Drupal\entity_export_csv\Plugin\FieldTypeExportInterface $field_type_exporter */
          $field_type_exporter = $field_type_export_manager->createInstance($exporter, $configuration);
          $field_headers = $field_type_exporter->getHeaders($field_definition);
          $headers = array_merge($headers, $field_headers);
        }
        catch (\Exception $e) {
          // The exporter is not available anymore, probably deleted.
          $messenger->addError('The export @exporter is not available anymore. Please contact an administrator', ['@exporter' => $exporter]);
        }

      }
      $context['results']['headers'] = $headers;
      $handle = fopen($file_path, 'w');
      fputcsv($handle, $headers);
      fclose($handle);
    }

    /** @var \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager */
    $entity_type_manager = \Drupal::service('entity_type.manager');
    /** @var \Drupal\Core\Entity\EntityTypeInterface $entity_type_definition */
    $entity_type_definition = $entity_type_manager->getDefinition($entity_type_id);
    $storage = $entity_type_manager->getStorage($entity_type_id);
    $query = $storage->getQuery();
    $bundle_key = $entity_type_definition->getKey('bundle');
    if (!empty($bundle_key)) {
      $query->condition($bundle_key, $bundle);
    }
    if (empty($context['sandbox'])) {
      $count_query = clone $query;
      $total = $count_query->count()->execute();
      $context['sandbox'] = [];
      $context['sandbox']['batch'] = 0;
      $context['sandbox']['iterations'] = abs(ceil($total / $limit));
      $context['sandbox']['count'] = 0;
      $context['sandbox']['total'] = $total;
    }

    $count = &$context['sandbox']['count'];
    $total = $context['sandbox']['total'];
    $batch = &$context['sandbox']['batch'];
    $iterations = $context['sandbox']['iterations'];

    $offset = $batch * $limit;
    $entities = $query->range($offset, $limit)->execute();
    $handle = fopen($context['results']['file_path'], 'a');
    foreach ($entities as $entity_id) {
      $entity = $storage->load($entity_id);
      if (!$entity instanceof ContentEntityInterface) {
        continue;
      }
      if ($langcode && $entity->isTranslatable() && $entity->hasTranslation($langcode)) {
        $entity = $entity->getTranslation($langcode);
      }
      $row = [];
      foreach ($fields as $field_name => $values) {
        $field_definition = isset($definitions[$field_name]) ? $definitions[$field_name] : NULL;
        if (!$field_definition) {
          continue;
        }
        $enable = $values['enable'];
        $exporter = $values['exporter'];
        $configuration = $values['form']['options'];
        if (empty($enable)) {
          continue;
        }
        try {
          /** @var \Drupal\entity_export_csv\Plugin\FieldTypeExportInterface $field_type_exporter */
          $field_type_exporter = $field_type_export_manager->createInstance($exporter, $configuration);
          $field_values = $field_type_exporter->export($entity, $field_definition);
          $row = array_merge($row, $field_values);
        }
        catch (\Exception $e) {
          // The exporter is not available anymore, probably deleted. We already
          // have displayed an error message when building the file headers.
        }
      }
      fputcsv($handle, $row);
      $count++;
    }
    fclose($handle);

    $context['message'] = new TranslatableMarkup(
      'Exporting entities @entity_type_label @bundle_label (@count/@total)', [
        '@entity_type_label' => $context['results']['entity_type_label'],
        '@bundle_label' => $context['results']['bundle_label'],
        '@count' => $count,
        '@total' => $total,
      ]
    );
    $batch++;

    if ($batch != $iterations) {
      $context['finished'] = $batch / $iterations;
    }

  }

  /**
   * The finished callback for the entity content export.
   *
   * @param $success
   *   A boolean if the batch process was successful.
   * @param $results
   *   An array of results for the given batch process.
   * @param $operations
   *   An array of batch operations that were performed.
   *
   */
  public static function finished($success, $results, $operations) {
    if ($success && isset($results['file_uri']) && file_exists($results['file_uri'])) {
      $file_uri = $results['file_uri'];
      $query_options = [
        'query' => [
          'file' => $file_uri,
        ],
      ];
      $download_url = Url::fromRoute('entity_export_csv.download', [], $query_options)->toString();
      \Drupal::messenger()->addStatus(t("Export successful. The download should automatically start shortly. If it doesn't, click <a data-auto-download href='@download_url'>Download</a>.", [
          '@download_url' => $download_url,
        ]
      ));
    }

    else {
      $message = t('An error has occurred. Please contact an administrator.');
      \Drupal::messenger()->addError($message);
    }
  }

  /**
   * Prepare the export file.
   *
   * @param $entity_type_id
   *   The entity type on which to export.
   * @param $bundle
   *   The entity bundle type.
   * @param array $context
   *   An array of the batch context.
   *
   * @return false|string
   *   The file path or false
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public static function prepareExportFile($entity_type_id, $bundle, &$context) {
    /** @var \Drupal\Core\File\FileSystemInterface $file_system */
    $file_system = \Drupal::service('file_system');
    $private_system_file = PrivateStream::basePath();
    if (!$private_system_file) {
      $directory = EntityExportCsvManagerInterface::TEMPORARY_DIRECTORY;
    }
    else {
      $directory = EntityExportCsvManagerInterface::PRIVATE_DIRECTORY;
    }
    $file_system->prepareDirectory($directory, FileSystemInterface::CREATE_DIRECTORY);
    $time = time();
    $filename = $entity_type_id . '_' . $bundle . '_' . $time . '.csv';
    $destination = $directory . '/' . $filename;
    $file = file_save_data('', $destination, FileSystemInterface::EXISTS_REPLACE);
    $file->setTemporary();
    $file->save();
    $file_path = $file_system->realpath($destination);
    $file_uri = $file->getFileUri();
    $context['results']['filename'] = $filename;
    $context['results']['file_path'] = $file_path;
    $context['results']['file_uri'] = $file_uri;

    return $file_path;
  }

  /**
   * {@inheritdoc}
   */
  public static function getBundleFieldDefinitions($entity_type_id, $bundle) {
    $options = [];
    /** @var \Drupal\Core\Entity\EntityFieldManagerInterface $entity_field_manager */
    $entity_field_manager = \Drupal::service('entity_field.manager');
    $fields = $entity_field_manager->getFieldDefinitions($entity_type_id, $bundle);
    foreach ($fields as $field_name => $field_definition) {
      $options[$field_name] = $field_definition;
    }
    return $options;
  }
}

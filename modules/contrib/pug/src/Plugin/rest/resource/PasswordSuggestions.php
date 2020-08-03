<?php

namespace Drupal\pug\Plugin\rest\resource;

use Drupal\rest\Plugin\ResourceBase;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Drupal\pug\PasswordSuggestionTrait;

/**
 * Provides a password suggestions resource.
 *
 * @RestResource(
 *   id = "password_suggestions",
 *   label = @Translation("Password Suggestions"),
 *   uri_paths = {
 *     "canonical" = "/api/v1/password_suggestions"
 *   }
 * )
 */
class PasswordSuggestions extends ResourceBase {
  use PasswordSuggestionTrait;

  /**
   * Fetch password policy help text.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   Rest resource query parameters.
   *
   * @return \Drupal\rest\ResourceResponse
   *   View result.
   */
  public function get(Request $request) {

    return new JsonResponse($this->suggestionsItems(), 200, [], FALSE);
  }

}

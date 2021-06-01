<?php

namespace Drupal\captcha_module\CaptchaService;
 
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Helper service for CAPTCHA module.
 */

class CaptchaService {
 
  use StringTranslationTrait;

  /**
   * Return an array with the available CAPTCHA types.
   */
    
  protected $challenges = [];

  // protected $result;
 
  // public function captcha () {
   if($add_special_options) {
    // $challenges['default'] = t('Default challenge type');
    $challenges['default'] = $this->t('Default challenge type');
   } 
    // return $this->challenges;
  // }
  
  // protected $result;

  // // // We do our own version of Drupal's module_invoke_all() here because
  // // class CaptchaService {
  //    // public function captcha() {
  //     // $challenges = [];
  //    $result = call_user_func_array($module . '_captcha', ['list']);
  //    if (is_array($result)) {
  //      foreach ($result as $type) {
  //       // $challenges["$module/$type"] = t('@type (from module @module)',
  //       $challenges["$module/$type"] = $this->t('@type (from module @module)', 
  //       [
  //          '@type' => $type,
  //          '@module' => $module,
  //       ]);
  //     // ]);
  //    }
  //  }
  //  //   return $challenges;
   // }
}
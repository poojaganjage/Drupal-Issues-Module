<?php

namespace Drupal\charts\Settings;

use Drupal\Component\Utility\Color;

/**
 * The chart default settings instance.
 */
class ChartsDefaultSettings {

  /**
   * The default colors.
   *
   * @var \Drupal\charts\Settings\ChartsDefaultColors
   */
  protected $colors;

  /**
   * The default settings.
   *
   * @var array
   */
  public $defaults = [
    'type' => 'line',
    'library' => NULL,
    'grouping' => FALSE,
    'label_field' => NULL,
    'data_fields' => NULL,
    'field_colors' => NULL,
    'title' => '',
    'title_position' => 'out',
    'data_labels' => FALSE,
    'data_markers' => TRUE,
    'legend' => TRUE,
    'legend_position' => 'right',
    'background' => '',
    'three_dimensional' => FALSE,
    'polar' => FALSE,
    'tooltips' => TRUE,
    'tooltips_use_html' => FALSE,
    'width' => NULL,
    'width_units' => '%',
    'height' => NULL,
    'height_units' => 'px',
    'xaxis_title' => '',
    'xaxis_labels_rotation' => 0,
    'yaxis_title' => '',
    'yaxis_min' => '',
    'yaxis_max' => '',
    'yaxis_prefix' => '',
    'yaxis_suffix' => '',
    'yaxis_decimal_count' => '',
    'yaxis_labels_rotation' => 0,
    'green_to' => 100,
    'green_from' => 85,
    'yellow_to' => 85,
    'yellow_from' => 50,
    'red_to' => 50,
    'red_from' => 0,
    'max' => 100,
    'min' => 0,
  ];

  /**
   * ChartsDefaultSettings constructor.
   */
  public function __construct() {
    $this->colors = new ChartsDefaultColors();
  }

  /**
   * Gets defaults settings.
   *
   * @param bool $new_format
   *   Whether to return the new format or not.
   *
   * @return array
   *   The defaults settings.
   */
  public function getDefaults($new_format = FALSE) {
    $defaults = $this->defaults;
    $defaults['colors'] = $this->colors->getDefaultColors();

    // Transforming the legacy settings array to the newer one by making sure
    // that we don't do this process twice.
    if ($new_format && empty($defaults['display'])) {
      $keys_mapping = self::getLegacySettingsMappingKeys();
      $keys_mapping['colors'] = 'display_colors';
      $defaults = self::transformLegacySettingsToNew($defaults, $keys_mapping);
    }

    return $defaults;
  }

  /**
   * Sets the defaults settings.
   *
   * @param array $defaults
   *   The settings.
   */
  public function setDefaults(array $defaults) {
    $this->defaults = $defaults;
  }

  /**
   * Transforms legacy settings to newer ones.
   *
   * @param array $old_settings
   *   The old settings.
   * @param array $old_config_keys
   *   The old settings keys.
   *
   * @return array
   *   The new format settings.
   */
  public static function transformLegacySettingsToNew(array &$old_settings, array $old_config_keys = []) {
    $new_settings = [];
    $new_settings['fields']['stacking'] = !empty($old_settings['grouping']);
    $old_config_keys = $old_config_keys ?: self::getLegacySettingsMappingKeys();
    foreach ($old_settings as $setting_id => $setting_value) {
      $setting_key_map = isset($old_config_keys[$setting_id]) ? $old_config_keys[$setting_id] : '';
      if ($setting_key_map) {
        $value = self::transformBoolStringValueToBool($setting_value);
        // When a block setting belongs to the chart blocks we save it in a
        // new setting.
        if (substr($setting_key_map, 0, 7) === 'display') {
          // Stripping the 'display_' in front of the mapping key.
          $setting_key_map = substr($setting_key_map, 8, strlen($setting_key_map));
          if (substr($setting_key_map, 0, 10) === 'dimensions') {
            // Stripping dimensions_.
            $setting_key_map = substr($setting_key_map, 11, strlen($setting_key_map));
            $new_settings['display']['dimensions'][$setting_key_map] = $value;
          }
          elseif (substr($setting_key_map, 0, 5) === 'gauge') {
            // Stripping gauge_.
            $setting_key_map = substr($setting_key_map, 6, strlen($setting_key_map));
            $new_settings['display']['gauge'][$setting_key_map] = $value;
          }
          else {
            $new_settings['display'][$setting_key_map] = $value;
          }
        }
        elseif (substr($setting_key_map, 0, 5) === 'xaxis') {
          // Stripping xaxis_.
          $setting_key_map = substr($setting_key_map, 6, strlen($setting_key_map));
          $new_settings['xaxis'][$setting_key_map] = $value;
        }
        elseif (substr($setting_key_map, 0, 5) === 'yaxis') {
          // Stripping yaxis_.
          $setting_key_map = substr($setting_key_map, 6, strlen($setting_key_map));
          if (substr($setting_key_map, 0, 9) === 'secondary') {
            // Stripping gauge_.
            $setting_key_map = substr($setting_key_map, 10, strlen($setting_key_map));
            $new_settings['yaxis']['secondary'][$setting_key_map] = $value;
          }
          else {
            $new_settings['yaxis'][$setting_key_map] = $value;
          }
        }
        elseif (substr($setting_key_map, 0, 6) === 'fields') {
          // Stripping fields_.
          $setting_key_map = substr($setting_key_map, 7, strlen($setting_key_map));
          if ($setting_key_map === 'data_providers' && is_array($value)) {
            $data_providers = !empty($new_settings['fields']['data_providers']) ? $new_settings['fields']['data_providers'] : [];
            if ($setting_id === 'data_fields' || $setting_id == 'field_colors') {
              $new_settings['fields']['data_providers'] = self::getFieldsDataProviders($data_providers, $value);
            }
          }
          else {
            $new_settings['fields'][$setting_key_map] = $value;
          }
        }
        elseif ($setting_key_map === 'grouping' && $new_settings['fields']['stacking']) {
          $new_settings[$setting_key_map] = [];
        }
        else {
          // We make sure that we handle the color unneeded array.
          $new_settings[$setting_key_map] = $setting_key_map !== 'color' ? $value : $value[0];
        }
        // Then we remove it from the main old settings tree.
        unset($old_settings[$setting_id]);
      }
    }
    return $new_settings;
  }

  /**
   * Gets legacy settings mapping keys.
   *
   * @return array
   *   Legacy settings keys to newer ones mapping.
   */
  public static function getLegacySettingsMappingKeys() {
    return [
      'library' => 'library',
      'chart_library' => 'library',
      'type' => 'type',
      'chart_type' => 'type',
      'grouping' => 'grouping',
      'title' => 'display_title',
      'title_position' => 'display_title_position',
      'data_labels' => 'display_data_labels',
      'data_markers' => 'display_data_markers',
      'legend' => 'display_legend',
      'legend_position' => 'display_legend_position',
      'background' => 'display_background',
      'three_dimensional' => 'display_three_dimensional',
      'polar' => 'display_polar',
      'series' => 'series',
      'data' => 'data',
      'color' => 'color',
      'data_series' => 'data_series',
      'series_label' => 'series_label',
      'categories' => 'categories',
      'field_colors' => 'fields_data_providers',
      'tooltips' => 'display_tooltips',
      'tooltips_use_html' => 'display_tooltips_use_html',
      'width' => 'display_dimensions_width',
      'height' => 'display_dimensions_height',
      'width_units' => 'display_dimensions_width_units',
      'height_units' => 'display_dimensions_height_units',
      'xaxis_title' => 'xaxis_title',
      'xaxis_labels_rotation' => 'xaxis_labels_rotation',
      'yaxis_title' => 'yaxis_title',
      'yaxis_min' => 'yaxis_min',
      'yaxis_max' => 'yaxis_max',
      'yaxis_prefix' => 'yaxis_prefix',
      'yaxis_suffix' => 'yaxis_suffix',
      'yaxis_decimal_count' => 'yaxis_decimal_count',
      'yaxis_labels_rotation' => 'yaxis_labels_rotation',
      'inherit_yaxis' => 'yaxis_inherit',
      'secondary_yaxis_title' => 'yaxis_secondary_title',
      'secondary_yaxis_min' => 'yaxis_secondary_min',
      'secondary_yaxis_max' => 'yaxis_secondary_min',
      'secondary_yaxis_prefix' => 'yaxis_secondary_prefix',
      'secondary_yaxis_suffix' => 'yaxis_secondary_suffix',
      'secondary_yaxis_decimal_count' => 'yaxis_secondary_decimal_count',
      'secondary_yaxis_labels_rotation' => 'yaxis_secondary_labels_rotation',
      'green_from' => 'display_gauge_green_from',
      'green_to' => 'display_gauge_green_to',
      'red_from' => 'display_gauge_red_from',
      'red_to' => 'display_gauge_red_to',
      'yellow_from' => 'display_gauge_yellow_from',
      'yellow_to' => 'display_gauge_yellow_to',
      'max' => 'display_gauge_max',
      'min' => 'display_gauge_min',
      'allow_advanced_rendering' => 'fields_allow_advanced_rendering',
      'label_field' => 'fields_label',
      'data_fields' => 'fields_data_providers',
    ];
  }

  /**
   * Transform boolean strings value to real boolean.
   *
   * @param mixed $value
   *   The value to be transformed.
   *
   * @return bool|mixed
   *   The boolean value or the original passed value.
   */
  public static function transformBoolStringValueToBool($value) {
    if ($value === 'FALSE') {
      $value = FALSE;
    }
    elseif ($value === 'TRUE') {
      $value = TRUE;
    }

    return $value;
  }

  /**
   * Field data provider.
   *
   * @param array $data_providers
   *   Data providers.
   * @param array $legacy_value
   *   Legacy value.
   *
   * @return mixed
   *   Data providers returned
   */
  public static function getFieldsDataProviders(array $data_providers, array $legacy_value) {
    $default_weight = 0;
    foreach ($legacy_value as $field_id => $value) {
      if (Color::validateHex($value)) {
        $data_providers[$field_id]['color'] = $value;
      }
      else {
        $data_providers[$field_id]['enabled'] = !empty($value);
      }
      $data_providers[$field_id]['weight'] = $default_weight;
      $default_weight++;
    }
    return $data_providers;
  }

}

<?php
defined('ABSPATH') || exit;

require_once(WPSP_DIR_PATH . 'modules/elementor/defaults.php');
global $wpsp_e_all_controls;
class WPSP_Layout_Manager
{
    function init( $element ) {
        if ( ! $element->get_settings('enable_slick') ) {
            return;
        }
        $settings = $element->get_settings();
        require_once(WPSP_DIR_PATH . 'modules/elementor/classes/elementor-settings-manager.php');
        $cleanedSettings = (new WPSP_Settings_Manager($settings))->clean_elementor_container_settings();
        require_once(WPSP_DIR_PATH . 'modules/elementor/defaults.php');
        global $wpsp_e_all_controls;
        global $wpsp_e_general_controls;
        $flattenedKeysArray = $this->collectKeys($wpsp_e_all_controls, $wpsp_e_general_controls);

        // echo '<pre>';
        // print_r($cleanedSettings);
        // die;

        $advance_options_enabled = ! empty(get_option('wpsp_elementor_advance_options', '1'));

        if ( ! $advance_options_enabled ) {
            $cleanedSettings = $this->removeMatchingKeys($cleanedSettings, $flattenedKeysArray);
        }

        if ( isset($settings['custom_slick']) && ! empty($settings['custom_slick']) && ! empty($settings['custom_slick_code']) ) {
            $jsonString = $settings['custom_slick_code'] ?? '';
            $cleanedSettings = json_decode($jsonString, true);
            $finalSettings = $cleanedSettings;
        } else {
            $finalSettings = $cleanedSettings;
        }

        $element->add_render_attribute(
            '_wrapper',
            [
                'class'                    => 'wpsp-enabled',
                'data-wpsp-id'             => $element->get_id(),
                'data-wpsp-slick-settings' => wp_json_encode($finalSettings),
            ]
        );
    }

    function collectKeys( $defaultArray, $excludeKeys = [] ) {
        $result = [];
        foreach ( $defaultArray as $key => $value ) {
            if ( is_array($value) && array_keys($value) !== range(0, count($value) - 1) ) {
                if ( ! in_array($key, $excludeKeys) ) {
                    $result[] = $key;
                }
                $result = array_merge($result, $this->collectKeys($value, $excludeKeys));
            } else {
                if ( ! in_array($key, $excludeKeys) ) {
                    $result[] = $key;
                }
            }
        }
        return $result;
    }

    function removeMatchingKeys( $mainArray, $keysToRemove ) {
        foreach ( $mainArray as $key => &$value ) {
            if ( in_array($key, $keysToRemove) ) {
                unset($mainArray[ $key ]);
            } elseif ( is_array($value) ) {
                $value = $this->removeMatchingKeys($value, $keysToRemove);
            }
        }
        return $mainArray;
    }
}

<?php
require_once(WPSP_DIR_PATH . 'modules/elementor/defaults.php');
class WPSP_Settings_Manager
{
    public $rawSettings;

    public function __construct( $settings ) {
        $this->rawSettings = $settings;
    }

    function clean_elementor_container_settings() {
        global $wpsp_e_general_controls, $wpsp_slick_defaults;
        $filteredSettings = array_filter($this->rawSettings, fn ($key) => $this->filterByPrefix($key), ARRAY_FILTER_USE_KEY);
        $cleanedSettings = [];

        foreach ( $filteredSettings as $key => $value ) {
            $newKey = str_replace([ 'responsive_', 'slick_' ], '', $key);
            $keyType = $this->control_type($newKey);
            if ( ! empty($keyType) && $keyType === 'switcher' ) {
                $cleanedSettings[ $newKey ] = ($value === 'yes') ? 'true' : 'false';
                continue;
            }
            $cleanedSettings[ $newKey ] = $value;
        }

        $responsiveBreakpoints = (
            isset($cleanedSettings['control'])
            && ! empty($cleanedSettings['control'])
        ) ? $cleanedSettings['control'] : [];

        $cleanedSettings['control'] = array();

        foreach ( $responsiveBreakpoints as &$breakpoint ) {
            unset($breakpoint['_id']);
            $isResponsiveSetting = false;
            foreach ( $breakpoint as $key => $value ) {
                $newKey = str_replace('responsive_', '', $key);
                unset($breakpoint[ $key ]);
                $keyType = $this->control_type($newKey);
                if ( ! empty($keyType) && $keyType === 'switcher' ) {
                    $breakpoint[ $newKey ] = ($value === 'yes') ? 'true' : 'false';
                } else {
                    $breakpoint[ $newKey ] = $value;
                }
                if ( (
                        (isset($breakpoint['advance_mode']) && $breakpoint['advance_mode'] === '')
                        || (isset($breakpoint['responsive_advance_mode']) && $breakpoint['responsive_advance_mode'] === '')
                        || ( ! isset($breakpoint['advance_mode']) && ! isset($breakpoint['responsive_advance_mode']))
                    )
                    && ! in_array($newKey, $wpsp_e_general_controls)
                ) {
                    unset($breakpoint[ $newKey ]);
                }
            }
        }

        foreach ( $responsiveBreakpoints as $key => &$breakpoint ) {
            if (
                ! isset($breakpoint['breakpoint']) || ! (int)$breakpoint['breakpoint'] || empty($breakpoint['breakpoint'])
            ) {
                unset($responsiveBreakpoints[ $key ]);
                continue;
            }

            $breakpt = $breakpoint['breakpoint'];
            unset($breakpoint['breakpoint']);
            $newBreakpt = array(
                'breakpoint' => $breakpt,
                'settings'   => $breakpoint,
            );

            $breakpoint = $newBreakpt;
        }

        $cleanedSettings['responsive'] = array_values($responsiveBreakpoints);
        unset($cleanedSettings['control']);

        foreach ( $cleanedSettings as $ckey => $cval ) {
            if (
                $ckey !== "responsive"
                && (
                    (isset($cleanedSettings['advance_mode']) && $cleanedSettings['advance_mode'] === '')
                    || ! isset($cleanedSettings['advance_mode'])
                )
                && ! in_array($ckey, $wpsp_e_general_controls)
            ) {
                unset($cleanedSettings[ $ckey ]);
            }
        }

        $cleanedSettings = $this->cleanSettings($cleanedSettings, $wpsp_slick_defaults);
        return $cleanedSettings;
    }
    function cleanSettings( $settings, $options ) {


        foreach ( $settings as $key => &$value ) {

            if ( ! array_key_exists($key, $options) ) {
                unset($settings[ $key ]);
                continue;
            }

            if ( empty($value) ) {
                unset($settings[ $key ]);
                continue;
            }

            $keyType = $this->control_type($key);

            if ( $value === $options[ $key ] || ($keyType === 'switcher' && ($value === 'true') === $options[ $key ]) ) {
                unset($settings[ $key ]);
                continue;
            }

            if ( $key === 'responsive' ) {
                if ( ! is_array($value) ) {
                    unset($settings[ $key ]);
                    continue;
                }
                foreach ( $settings[ $key ] as &$brkpt ) {

                    foreach ( $brkpt['settings'] as $bkey => $bval ) {

                        if ( ! array_key_exists($bkey, $options) ) {
                            unset($brkpt['settings'][ $bkey ]);
                            continue;
                        }

                        if ( empty($bval) ) {
                            unset($brkpt['settings'][ $bkey ]);
                            continue;
                        }

                        if ( isset($settings[ $bkey ]) ) {
                            continue;
                        }

                        $keyType = $this->control_type($bkey);
                        $isEqualDefault = ($bval === $options[ $bkey ]
                            || ($keyType === 'switcher' && ($bval === 'true') === $options[ $bkey ]));

                        if (
                            $isEqualDefault
                        ) {
                            unset($brkpt['settings'][ $bkey ]);
                        }
                    }
                }

                if ( empty($value) ) {
                    unset($settings[ $key ]);
                }
                continue;
            }

            if ( is_array($value) ) {
                $value = $this->cleanSettings($value, $options);

                if ( empty($value) ) {
                    unset($settings[ $key ]);
                }
            }
        }

        return $settings;
    }


    function filterByPrefix( $key ) {
        return strpos($key, 'responsive_') === 0 || strpos($key, 'slick_') === 0;
    }

    function control_type( $controlKey ) {
        global $wpsp_e_all_controls;

        foreach ( $wpsp_e_all_controls as $cKey => $cValue ) {
            if (
                isset($cValue[ $controlKey ])
            ) {
                return $cKey;
                break;
            }
        }
    }
}

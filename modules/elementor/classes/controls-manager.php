<?php
class WPSP_Controls_Manager
{
    function init( $element ) {
        require_once(WPSP_DIR_PATH . 'modules/elementor/defaults.php');
        global $wpsp_e_all_controls;

        $element->start_controls_section(
            'custom_section',
            [
                'tab'   => \Elementor\Controls_Manager::TAB_LAYOUT,
                'label' => esc_html__('SlickPress Options', 'slickpress'),
            ]
        );

        $element->add_control(
            'enable_slick',
            [
                'label'        => esc_html__('Enable Slick Slider', 'slickpress'),
                'type'         => \Elementor\Controls_Manager::SWITCHER,
                'label_on'     => __('No', 'slickpress'),
                'label_off'    => __('Yes', 'slickpress'),
                'return_value' => 'yes',
                'default'      => '',
            ]
        );

        $element->add_control(
            'custom_slick',
            [
                'label'        => esc_html__('Enable Custom Slick', 'slickpress'),
                'type'         => \Elementor\Controls_Manager::SWITCHER,
                'label_on'     => __('No', 'slickpress'),
                'label_off'    => __('Yes', 'slickpress'),
                'return_value' => 'yes',
                'default'      => '',
                'condition'    => [
                    'enable_slick' => 'yes',
                ],
            ]
        );

        $element->add_control(
            'custom_slick_code',
            [
                'label'       => esc_html__('Code', 'slickpress'),
                'description' => esc_html__('add custom slick code', 'slickpress'),
                'type'        => \Elementor\Controls_Manager::TEXTAREA,
                'condition'   => [
                    'custom_slick' => 'yes',
                ],
                'default'     => '{"dots": true,"infinite": false}',
            ]
        );

        $element->add_control(
            'slick_dots',
            [
                'label'        => esc_html__('dots', 'slickpress'),
                'type'         => \Elementor\Controls_Manager::SWITCHER,
                'label_on'     => __('No', 'slickpress'),
                'label_off'    => __('Yes', 'slickpress'),
                'label_off'    => __('Yes', 'slickpress'),
                'return_value' => 'yes',
                'condition'    => [
                    'enable_slick' => 'yes',
                    'custom_slick' => '',
                ],
                'default'      => '',
            ]
        );

        $element->add_control(
            'slick_infinite',
            [
                'label'        => esc_html__('Infinite', 'slickpress'),
                'type'         => \Elementor\Controls_Manager::SWITCHER,
                'label_on'     => __('No', 'slickpress'),
                'label_off'    => __('Yes', 'slickpress'),
                'return_value' => 'yes',
                'default'      => 'yes',
                'condition'    => [
                    'enable_slick' => 'yes',
                    'custom_slick' => '',
                ],
            ]
        );

        $element->add_control(
            'slick_centerMode',
            [
                'label'        => esc_html__('Center Mode', 'slickpress'),
                'type'         => \Elementor\Controls_Manager::SWITCHER,
                'label_on'     => __('No', 'slickpress'),
                'label_off'    => __('Yes', 'slickpress'),
                'return_value' => 'yes',
                'default'      => '',
                'condition'    => [
                    'enable_slick' => 'yes',
                    'custom_slick' => '',
                ],
            ]
        );

        $element->add_control(
            'slick_autoplay',
            [
                'label'        => esc_html__('autoplay', 'slickpress'),
                'type'         => \Elementor\Controls_Manager::SWITCHER,
                'label_on'     => __('No', 'slickpress'),
                'label_off'    => __('Yes', 'slickpress'),
                'return_value' => 'yes',
                'default'      => '',
                'condition'    => [
                    'enable_slick' => 'yes',
                    'custom_slick' => '',
                ],
            ]
        );

        $element->add_control(
            'slick_arrows',
            [
                'label'        => esc_html__('arrows', 'slickpress'),
                'type'         => \Elementor\Controls_Manager::SWITCHER,
                'label_on'     => __('No', 'slickpress'),
                'label_off'    => __('Yes', 'slickpress'),
                'return_value' => 'yes',
                'default'      => 'yes',
                'condition'    => [
                    'enable_slick' => 'yes',
                    'custom_slick' => '',
                ],
            ]
        );



        $element->add_control(
            'slick_speed',
            [
                'type'        => \Elementor\Controls_Manager::NUMBER,
                'label'       => esc_html__('Slides Speed', 'slickpress'),
                'description' => esc_html__('Slides speed control.', 'slickpress'),
                'condition'   => [
                    'enable_slick' => 'yes',
                    'custom_slick' => '',
                ],
                'step'        => 100,
                'min'         => 100,
                'default'     => 500,
            ]
        );
        $advance_options_enabled = ! empty(get_option('wpsp_elementor_advance_options', '1'));
        if (
            $advance_options_enabled
        ) {
            $element->add_control(
                'slick_advance_mode',
                [
                    'label'        => esc_html__('Advance Options', 'slickpress'),
                    'type'         => \Elementor\Controls_Manager::SWITCHER,
                    'label_on'     => __('No', 'slickpress'),
                    'label_off'    => __('Yes', 'slickpress'),
                    'return_value' => 'yes',
                    'default'      => '',
                    'condition'    => [
                        'enable_slick' => 'yes',
                        'custom_slick' => '',
                    ],
                ]
            );

            foreach ( $wpsp_e_all_controls as $type => $control ) {
                $this->add_controls_from_array($type, $control, $element, 'element');
            }
        }

        $repeater = new \Elementor\Repeater();


        $repeater->add_control(
            'breakpoint',
            [
                'label'   => esc_html__('Break Point Width', 'slickpress'),
                'type'    => \Elementor\Controls_Manager::NUMBER,
                'default' => '',
            ]
        );

        $repeater->add_control(
            'responsive_infinite',
            [
                'label'        => esc_html__('Infinite', 'slickpress'),
                'type'         => \Elementor\Controls_Manager::SWITCHER,
                'label_on'     => __('No', 'slickpress'),
                'label_off'    => __('Yes', 'slickpress'),
                'return_value' => 'yes',
                'default'      => 'yes',
            ]
        );

        $repeater->add_control(
            'responsive_centerMode',
            [
                'label'        => esc_html__('Center Mode', 'slickpress'),
                'type'         => \Elementor\Controls_Manager::SWITCHER,
                'label_on'     => __('No', 'slickpress'),
                'label_off'    => __('Yes', 'slickpress'),
                'return_value' => 'yes',
                'default'      => '',
            ]
        );

        $repeater->add_control(
            'responsive_autoplay',
            [
                'label'        => esc_html__('autoplay', 'slickpress'),
                'type'         => \Elementor\Controls_Manager::SWITCHER,
                'label_on'     => __('No', 'slickpress'),
                'label_off'    => __('Yes', 'slickpress'),
                'return_value' => 'yes',
                'default'      => '',
            ]
        );

        $repeater->add_control(
            'responsive_arrows',
            [
                'label'        => esc_html__('arrow', 'slickpress'),
                'type'         => \Elementor\Controls_Manager::SWITCHER,
                'label_on'     => __('No', 'slickpress'),
                'label_off'    => __('Yes', 'slickpress'),
                'return_value' => 'yes',
                'default'      => 'yes',
            ]
        );

        $repeater->add_control(
            'responsive_dots',
            [
                'label'        => esc_html__('dots', 'slickpress'),
                'type'         => \Elementor\Controls_Manager::SWITCHER,
                'label_on'     => __('No', 'slickpress'),
                'label_off'    => __('Yes', 'slickpress'),
                'return_value' => 'yes',
                'default'      => '',
            ]
        );

        $repeater->add_control(
            'responsive_speed',
            [
                'label'   => esc_html__('Speed', 'slickpress'),
                'type'    => \Elementor\Controls_Manager::NUMBER,
                'step'    => 100,
                'min'     => 100,
                'default' => 300,
            ]
        );

        if (
            $advance_options_enabled
        ) {
            $repeater->add_control(
                'responsive_advance_mode',
                [
                    'label'        => esc_html__('Advance Options', 'slickpress'),
                    'type'         => \Elementor\Controls_Manager::SWITCHER,
                    'label_on'     => __('No', 'slickpress'),
                    'label_off'    => __('Yes', 'slickpress'),
                    'return_value' => 'yes',
                    'default'      => '',
                ]
            );

            foreach ( $wpsp_e_all_controls as $type => $control ) {
                $this->add_controls_from_array($type, $control, $repeater, 'repeater');
            }
        }


        $element->add_control(
            'responsive_control',
            [
                'label'       => esc_html__('Responsive Breakpoints', 'slickpress'),
                'type'        => \Elementor\Controls_Manager::REPEATER,
                'fields'      => $repeater->get_controls(),
                'default'     => [],
                'condition'   => [
                    'enable_slick' => 'yes',
                    'custom_slick' => '',
                ],
                'title_field' => 'Breakpoint {{{ breakpoint }}}',
            ]
        );
        $element->end_controls_section();
    }

    function add_controls_from_array( $field_type, $controlsData, $element_type, $add_type ) {
        foreach ( $controlsData as $controlName => $controlValue ) {

            $prefix = $add_type === 'repeater' ? 'responsive_' : 'slick_';
            $controlSettingsKey = $prefix . $controlName;

            $control_config = array(
                'label'     => sprintf('%s', esc_html($controlName)),
                'condition' => array(
                    ($add_type === 'repeater' ? 'responsive_' : 'slick_') . 'advance_mode' => 'yes',

                ),
                'default'   => $controlValue,
            );

            if ( $add_type !== 'repeater' ) {
                $control_config['condition']['enable_slick'] = 'yes';
                $control_config['condition']['custom_slick'] = '';
            }

            switch ( $field_type ) {
                case 'switcher':
                    $control_config = array_merge($control_config, array(
                        'type'         => \Elementor\Controls_Manager::SWITCHER,
                        'label_on'     => __('Yes', 'slickpress'),
                        'label_off'    => __('No', 'slickpress'),
                        'return_value' => 'yes',
                        'default'      => $controlValue ? 'yes' : '',
                    ));
                    break;

                case 'text':
                    $control_config = array_merge($control_config, array(
                        'type'        => \Elementor\Controls_Manager::TEXT,
                        'description' => esc_html__('Maximum 40 characters allowed', 'slickpress'),
                        'label_block' => true,
                        'default'     => $controlValue,
                    ));
                    break;

                case 'select':
                    $control_config = array_merge($control_config, array(
                        'type'        => \Elementor\Controls_Manager::SELECT,
                        'options'     => $controlValue,
                        'description' => esc_html__('Templates are defined under Global Component settings', 'slickpress'),
                        'default'     => $controlValue,
                    ));
                    break;

                case 'integer':
                    $control_config = array_merge($control_config, array(
                        'type'    => \Elementor\Controls_Manager::NUMBER,
                        'default' => $controlValue,
                    ));
                    break;

                default:
                    // skip unsupported types
                    break;
            }

            $existing_controls = array_keys($element_type->get_controls());
            if ( ! in_array($controlSettingsKey, $existing_controls) ) {

                $element_type->add_control($controlSettingsKey, $control_config);
            }
        }
    }
}

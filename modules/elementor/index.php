<?php
class WPSP_Elementor_Manager
{
    private $about = array(
        'enable_default' => true,
        'icon'           => WPSP_URL . 'modules/elementor/assets/img/icon.png',
        'name'           => 'Elementor Slick Builder',
        'slug'           => 'wpsp_elementor',
        'description'    => 'This module will allow you to utilize the power of elementor in combination with slick slider',
    );

    private $settings_manager;
    private $controls_manager;
    private $layout_manager;

    public function __construct() {
       
    }
    public function get_module_info() {
        return $this->about;
    }
    
    public function load_module() {   
        add_action('init', array( $this, 'init' ));
        add_action('admin_enqueue_scripts', array( $this, 'add_elementor_css_js' ));
        $this->init_elmentorsettings();

    }

    public function init_elmentorsettings() {
        require_once(WPSP_DIR_PATH . 'modules/elementor/templates/setting.php');
        $this->settings_manager = new WPSP_Elementor_Settings_Manager();
        add_filter('wpsp/settings/panels/general', array( $this->settings_manager, 'add_general_settings' ));
        add_action('wpsp/settings/save', array( $this->settings_manager, 'handle_panel_settings' ));
    }

    public function init() {
        if ( ! $this->is_elementor_active() || ! class_exists('\Elementor\Plugin') ) {
            return;
        }

        $elementor_instance = Elementor\Plugin::$instance->experiments;
        $is_container_active = $elementor_instance->is_feature_active('container');

        if ( ! $is_container_active ) {
            add_action('admin_notices', array( $this, 'admin_notice_elementor_conatiner_enable' ));
            return;
        }

        $this->add_elementor_hooks();
        $this->add_settings();
        $this->add_other_hooks();
    }

    public function add_elementor_css_js() {
        wp_enqueue_style('elementor-admin-css', WPSP_URL . 'modules/elementor/assets/css/style.css', array(), '1.0.1');
        wp_enqueue_script('elementor-admin-js', WPSP_URL . 'modules/elementor/assets/js/admin/admin.js', array( 'slick-js' ), '1.1.0', true);
    }

    public function add_other_hooks() {
        add_filter('script_loader_tag', array( $this, 'add_type_attribute' ), 10, 3);
    }

    public function add_settings() {
        // Settings related code can be added here
    }

    public function add_elementor_hooks() {
        add_action('elementor/element/container/section_layout/after_section_end', array( $this, 'add_elementor_controls' ), 10, 2);
        add_action('elementor/frontend/before_render', array( $this, 'render_elementor_container_frontend' ));
        add_action('elementor/preview/enqueue_scripts', array( $this, 'enqueue_elementor_preview_css_js' ));
        add_action('elementor/elements/elements_registered', array( $this, 'update_elementor_container_preview_template' ));
    }

    private function is_elementor_active() {
        $active = true;
        if ( ! function_exists('is_plugin_active') ) {
            require_once(ABSPATH . '/wp-admin/includes/plugin.php');
        }
        if ( ! is_plugin_active('elementor/elementor.php') ) {
            $active = false;
            add_action('admin_notices', array( $this, 'install_or_activate_main_plugin' ));
        }

        return $active;
    }

    public function install_or_activate_main_plugin() {
        $plugin_slug = 'elementor/elementor.php';
        $install_plugin_slug = 'elementor';

        if ( current_user_can('install_plugins') ) {
            $install_url = wp_nonce_url(admin_url('update.php?action=install-plugin&plugin=' . $install_plugin_slug), 'install-plugin_' . $install_plugin_slug);
            $activate_url = wp_nonce_url(admin_url('plugins.php?action=activate&plugin=' . $plugin_slug), 'activate-plugin_' . $plugin_slug);
            $notice_msg = 'The Elementor plugin is required for SlickPress to work properly. ';
            $action_url = $this->is_plugin_installed($plugin_slug) ? $activate_url : $install_url;
            $action_text = $this->is_plugin_installed($plugin_slug) ? 'activate' : 'install';
            echo '<div class="notice notice-info"><p>' . esc_html($notice_msg) . '<a href="' . esc_url($action_url) . '">Click here to ' . esc_html($action_text) . ' it</a>.</p></div>';
        }
    }

    private function is_plugin_installed( $plugin_slug ) {
        $plugins = get_plugins();
        return isset($plugins[ $plugin_slug ]);
    }

    public function add_type_attribute( $tag, $handle, $src ) {
        if ( in_array($handle, array( 'wpsp-admin-js', 'wpsp-front-js' ), true) ) {
            if ( strpos($tag, 'type="text/javascript"') !== false ) {
                $tag = str_replace('type="text/javascript"', 'type="module"', $tag);
            } else {
                $tag = str_replace('src', 'type="module" src', $tag);
            }
        }
        return $tag;
    }

    public function enqueue_elementor_preview_css_js() {
        wp_enqueue_style('slick-css', '', array(), '1.0.0');
        wp_enqueue_style('slick-theme-css', '', array(), '1.0.0');
        wp_enqueue_script('slick-js', '', array(), '1.0.0', true);
        wp_enqueue_script('wpsp-admin-js', WPSP_URL . 'modules/elementor/assets/js/admin/index.js', array( 'slick-js' ), '1.1.0', true);
        wp_enqueue_style('wpsp-admin-css', WPSP_URL . 'modules/elementor/assets/css/admin/preview-style.css', array(), '1.0.0');
    }

    public function add_elementor_controls( $element, $args ) {
        require_once(WPSP_DIR_PATH . 'modules/elementor/classes/controls-manager.php');
        $this->controls_manager = new WPSP_Controls_Manager();
        $this->controls_manager->init($element);
    }

    public function render_elementor_container_frontend( $element ) {
        require_once(WPSP_DIR_PATH . 'modules/elementor/classes/layout-manager.php');
        $this->layout_manager = new WPSP_Layout_Manager();
        $this->layout_manager->init($element);
    }

    public function update_elementor_container_preview_template( $el_manager ) {
        require_once(WPSP_DIR_PATH . 'modules/elementor/classes/elementor-container.php');
        $el_manager->register_element_type(new WPSP_Elementor_Container());
    }

    public function admin_notice_elementor_conatiner_enable() {
        $elementor_settings = admin_url('admin.php?page=elementor-settings#tab-experiments');
        echo '<div class="notice notice-warning notice-red-border is-dismissible"><p>Please enable Flexbox Container & Grid Container. <a href="' . esc_url($elementor_settings) . '">Click here</a></p></div>';
    }
}

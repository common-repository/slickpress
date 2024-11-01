<?php

/**
 * Plugin Name: SlickPress
 * Description: Slickpress allows you to create slick sliders and convert any elementor container into slider. 
 * Version:     1.1.1
 * Author:      Quuantum
 * Author URI:  https://www.quuantum.com
 * Text Domain: slickpress
 * Plugin URI:        https://www.quuantum.com/slickpress
 * License:           GPLv3
 * License URI:       http://www.gnu.org/licenses/gpl.html
 */

if ( ! defined('ABSPATH')) exit;

define('WPSP_VERSION', '1.1.1');
define('WPSP_DIR_PATH', plugin_dir_path(__FILE__));
define('WPSP_URL', plugin_dir_url(__FILE__));
define('WPSP_BASENAME', plugin_basename(__FILE__));
define('WPSP', 'wpsp');
define('WPSP_QS_URL', 'https://store.quuantum.com');
define('WPSP_QS_ID', 187);

class WPSP
{
    private $modules;
    private $wpsp_manager;

    public function __construct() {
        register_activation_hook(__FILE__, array( $this, 'activation' ));
        register_deactivation_hook(__FILE__, array( $this, 'deactivation' ));

        $this->detect_modules();
        $this->init();
        $this->load_modules();
    }

    public function activation() {
        do_action('wpsp/activated');
    }

    public function deactivation() {
        do_action('wpsp/deactivated');
    }

    public function init() {
        add_action('init', array( $this, 'register_styles_scripts' ));
        add_action('init', array( $this, 'init_manager' ));
        add_action('wp_enqueue_scripts', array( $this, 'enqueue_css_js' ));
    }

    public function detect_modules() {
        $modules_path = WPSP_DIR_PATH . 'modules/';
        $this->modules = apply_filters('wpsp/detect_modules/before', array());
        $excluded_folders = [ 'manager' ];

        $all_files = glob($modules_path . '*/index.php');

        $filtered_files = array_filter($all_files, function ( $file ) use ( $excluded_folders ) {
            foreach ( $excluded_folders as $folder ) {
                if ( strpos($file, $folder . '/index.php') !== false ) {
                    return false;
                }
            }
            return true;
        });

        foreach ( $filtered_files as $module_index_file ) {
            require_once $module_index_file;
            $folder_name = basename(dirname($module_index_file));
            $class_name = 'WPSP_' . ucfirst($folder_name) . '_Manager';

            if ( class_exists($class_name) ) {
                $instance = new $class_name();
                $module_info = $instance->get_module_info();

                $this->modules[] = array_merge([
                    'type'     => 'module',
                    'instance' => $instance,
                    'class'    => $class_name,
                ], $module_info);
            }
        }

        $this->modules = apply_filters('wpsp/detect_modules/after', $this->modules);
    }

    public function load_modules() {
        do_action('wpsp/load_modules/before', $this->modules);

        foreach ( $this->modules as $module ) {
            if ( $module['type'] === 'module' && ! empty(get_option('enable_' . $module['slug'], ($module['enable_default'] ? '1' : ''))) ) {
                do_action('wpsp/load_module/before', $this->modules, $module);
                $module['instance']->load_module();
                do_action('wpsp/load_module/after', $this->modules, $module);
            }
        }

        do_action('wpsp/load_modules/after', $this->modules);
    }

    public function register_styles_scripts() {
        wp_register_style('slick-css', WPSP_URL . 'libs/slick/slick.css', array(), '1.8.1');
        wp_register_style('slick-theme-css', WPSP_URL . 'libs/slick/slick-theme.css', array( 'slick-css' ), '1.8.1');
        wp_register_script('slick-js', WPSP_URL . 'libs/slick/slick.min.js', array( 'jquery' ), '1.8.1', true);
        wp_register_script('wpsp-front-js', WPSP_URL . 'modules/elementor/assets/js/front/index.js', array( 'slick-js' ), '1.1.0', true);
    }

    public function enqueue_css_js() {
        wp_enqueue_style('slick-css');
        wp_enqueue_style('slick-theme-css');
        wp_enqueue_script('slick-js');
        wp_enqueue_script('wpsp-front-js');
    }

    public function init_manager() {
        require_once(WPSP_DIR_PATH . 'modules/manager/index.php');
        $this->wpsp_manager = new WPSP_Manager($this->modules);
    }
}

$wpsp = new WPSP();

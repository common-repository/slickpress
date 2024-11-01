<?php
class WPSP_Ext_Manager
{

    private $plugin_url;
    private $plugin_path;

    public function __construct( $plugin_url, $plugin_path ) {
        $this->plugin_url = $plugin_url;
        $this->plugin_path = $plugin_path;
    }

    public static function wpsp_check_license() {
        $license = trim(get_option('wpsp_license_key', ''));
        $response = wp_remote_post(
            WPSP_QS_URL,
            array(
                'timeout'   => 30,
                'sslverify' => false,
                'body'      => array(
                    'edd_action'  => 'check_license',
                    'license'     => $license,
                    'item_id'     => WPSP_QS_ID,
                    'url'         => home_url(),
                    'environment' => function_exists('wp_get_environment_type') ? wp_get_environment_type() : 'production',
                ),
            )
        );

        if ( is_wp_error($response) ) {
            return false;
        }

        $license_data = json_decode(wp_remote_retrieve_body($response));

        if ( 'valid' === $license_data->license ) {
            return 'valid';
        } else {
            return 'invalid';
        }
    }

    public static function get_extensions_from_qstore() {
        $result = get_transient('wpsp_qstore_ext_list');
        if ( false === $result ) {
            $response = wp_remote_get(WPSP_QS_URL . '/wp-json/qstore/v1/extension/' . WPSP_QS_ID, array( 'timeout' => 30 ));

            if ( is_wp_error($response) ) {
                return $response;
            }

            $body = wp_remote_retrieve_body($response);
            $result = json_decode($body, true);
            set_transient('wpsp_qstore_ext_list', $result, 1800);
        }

        return $result;
    }

    public function download_plugin() {
        if ( ! function_exists('WP_Filesystem') ) {
            require_once ABSPATH . 'wp-admin/includes/file.php';
        }

        global $wp_filesystem;

        $creds = request_filesystem_credentials('', '', false, false, null);

        if ( ! WP_Filesystem($creds) ) {
            return new WP_Error('filesystem_error', 'Could not initialize filesystem.');
        }

        if ( filter_var($this->plugin_url, FILTER_VALIDATE_URL) ) {
            $temp_file = download_url($this->plugin_url);

            if ( is_wp_error($temp_file) ) {
                $temp_file = wp_tempnam();

                if ( ! $temp_file ) {
                    return new WP_Error('temp_file_error', 'Could not create a temporary file.');
                }

                $response = wp_safe_remote_get($this->plugin_url, [
                    'timeout'  => 300,
                    'stream'   => true,
                    'filename' => $temp_file,
                ]);

                if ( is_wp_error($response) ) {
                    unlink($temp_file);
                    return $response;
                }
            }
        } else {
            if ( ! file_exists($this->plugin_url) ) {
                return new WP_Error('file_not_found', 'The local file does not exist.');
            }

            $temp_file = $this->plugin_url;
        }

        try {
            $result = unzip_file($temp_file, WP_PLUGIN_DIR);

            if ( is_wp_error($result) ) {
                throw new Exception($result->get_error_message());
            }
        } catch ( Exception $e ) {
            return new WP_Error('plugin_install_error', $e->getMessage());
        } finally {
            if ( filter_var($this->plugin_url, FILTER_VALIDATE_URL) ) {
                unlink($temp_file);
            }
        }

        return $result;
    }

    public function activate_plugin() {
        $result = activate_plugin($this->plugin_path);
        return ($result);
    }

    public function deactivate_plugin() {
        deactivate_plugins($this->plugin_path);
        return ! is_plugin_active($this->plugin_path);
    }
}

<?php
class WPSP_Manager
{

  private $modules;

  public function __construct( $modules = [] ) {
    $this->modules = $modules;
    add_action('admin_menu', [ $this, 'add_menu_option' ]);
    add_action('admin_enqueue_scripts', [ $this, 'enqueue_admin_css_js' ]);
    require_once(WPSP_DIR_PATH . 'modules/manager/classes/extensions-manager.php');

    $action = 'wpsp_manage_extensions';
    add_action('wp_ajax_' . $action, [ $this, $action ]);
    add_action('wp_ajax_nopriv_' . $action, [ $this, $action ]);

    add_action('admin_notices', [ $this, 'show_admin_notices' ]);
  }

  public function enqueue_admin_css_js() {
    wp_enqueue_style('wpsp-admin-css', WPSP_URL . 'modules/manager/css/style.css', [], '1.0.0');
    wp_enqueue_script('wpsp-admins-js', WPSP_URL . 'modules/manager/js/wpsp-admin.js', [ 'jquery' ], '1.0.1', true);
    wp_localize_script('wpsp-admins-js', 'wpsp_settings', [
		'ajax_url' => admin_url('admin-ajax.php'),
		'nonce'    => wp_create_nonce('ajax_nonce'),
    ]);
  }

  public function add_menu_option() {
    add_menu_page(
      'SlickPress',
      'SlickPress',
      'manage_options',
      'wpsp-settings',
      [ $this, 'add_main_menu_page' ],
      WPSP_URL . 'modules/manager/images/icon.png',
      25
    );
    add_submenu_page('wpsp-settings', 'Settings', 'Settings', 'manage_options', 'wpsp-settings', [ $this, 'add_main_menu_page' ]);
  }

  public function add_main_menu_page() {
    wp_enqueue_style('sweetalert-css', 'https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css', [], '2.1.1');
    wp_enqueue_script('sweetalert-js', 'https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.js', [ 'jquery' ], '2.1.1', true);

    require_once(WPSP_DIR_PATH . 'modules/manager/templates/setting.php');
    $manager_settings = new WPSP_Settings_Manager($this->modules);

    $manager_settings->handle_panel_settings();
?>
    <div class="tab nav-tab-wrapper">
      <?php echo $manager_settings->tabs(); ?>
    </div>

    <div class="slick-option-settings">
      <form action="" method="post">
        <?php wp_nonce_field('wpsp_settings_nonce_action', 'wpsp_settings_nonce_field'); ?>
        <?php echo $manager_settings->panels(); ?>
        <p class="submit">
          <button type="submit" name="wpsp_submit_settings" class="button-primary"><?php esc_html_e('Save changes', 'slickpress'); ?></button>
        </p>
      </form>
    </div>
    <?php
  }

  public function log_status( $id, $msg, $status = 'Processing' ) {
    $existing_data = get_transient($id);

    if ( ! $existing_data ) {
      $existing_data = [
		  'status' => $status,
		  'log'    => '',
      ];
    }

    $existing_data['log'] = $msg . "\n";
    $existing_data['status'] = $status;

    return set_transient($id, $existing_data);
  }

  public function wpsp_manage_extensions() {
    check_ajax_referer('ajax_nonce', 'nonce');

    $action = isset($_POST['action_type']) ? sanitize_text_field(wp_unslash($_POST['action_type'])) : '';
    $transient_id = isset($_POST['transient_id']) ? sanitize_text_field(wp_unslash($_POST['transient_id'])) : '';

    if ( ! current_user_can('manage_options') ) {
      $this->make_error($transient_id, 'Not allowed');
    }

    $id = isset($_POST['id']) ? intval(wp_unslash($_POST['id'])) : '';
    $plugin_type = isset($_POST['plugin_type']) ? sanitize_text_field(wp_unslash($_POST['plugin_type'])) : '';
    $item_type = ($plugin_type === 'recommended') ? 'plugin' : 'extension';
    $plugin_list_key = ($plugin_type === 'recommended') ? 'recommended' : 'extensions';

    if ( empty($id) || empty($action) ) {
      $this->make_error($transient_id, 'Invalid id or action.');
    }

    if ( ! in_array($plugin_type, [ 'extension', 'recommended' ]) ) {
      $this->make_error($transient_id, 'Invalid type.');
    }

    if ( $plugin_type === 'recommended' && ! in_array($action, [ 'activate', 'deactivate' ]) ) {
      $this->make_error($transient_id, 'Invalid action. ' . $action);
    }

    if ( in_array($action, [ 'add_to_queue', 'download', 'download_activate' ]) ) {
      $license_key = trim(get_option('wpsp_license_key', ''));
      if ( empty($license_key) ) {
        $this->make_error($transient_id, 'License key not found.');
      }
    }

    if ( in_array($action, [ 'activate', 'deactivate' ]) ) {
      $extensions = WPSP_Ext_Manager::get_extensions_from_qstore();

      if ( is_wp_error($extensions) ) {
        $this->make_error('', "Failed to fetch {$item_type}s: " . $extensions->get_error_code());
      }

      if ( empty($extensions) || ! is_array($extensions) ) {
        $this->make_error('', "No {$item_type}s found in the list");
      }

      [$extension] = array_filter($extensions[ $plugin_list_key ], function ( $ext ) use ( $id ) {
        return (int)$ext['id'] === $id;
      });

      if ( empty($extension) || ! isset($extension['path']) || empty($extension['path']) ) {
        $this->make_error('', "No related {$item_type} found.");
      }

      $plugin_path = $extension['path'];
      require_once(WPSP_DIR_PATH . 'modules/manager/classes/extensions-manager.php');
      $extensions_manager = new WPSP_Ext_Manager('', $plugin_path);

      $method = $action . '_plugin';
      $result = $extensions_manager->$method();

      if ( is_wp_error($result) ) {
        $this->make_error('', "Failed to $action the {$item_type}. " . $result->get_error_message());
      }

      wp_send_json_success("{$item_type} {$action}d successfully");
      wp_die();
    }

    if ( $action === 'add_to_queue' ) {
      $transient_id = 'wpsp_ext_log_' . wp_rand(100000, 999999);
      $this->log_status($transient_id, 'Initialising the process', 'Starting');

      if ( ! isset($_POST['nonce']) || empty($_POST['nonce']) ) {
        $this->make_error($transient_id, 'Invalid request.');
      }

      $cookies = [];
      foreach ( $_COOKIE as $name => $value ) {
        $cookies[] = "$name=" . rawurlencode(is_array($value) ? wp_json_encode($value) : $value);
      }

      $background_process = wp_remote_post(admin_url('admin-ajax.php'), [
		  'body'      => [
			  'action'       => 'wpsp_manage_extensions',
			  'transient_id' => $transient_id,
			  'action_type'  => 'download_activate',
			  'id'           => $id,
			  'nonce'        => sanitize_text_field(wp_unslash($_POST['nonce'] ?? '')),
			  'plugin_type'  => $plugin_type,
		  ],
		  'headers'   => [
			  'cookie' => implode('; ', $cookies),
		  ],
		  'timeout'   => 0.01,
		  'blocking'  => false,
		  'sslverify' => false,
      ]);

      if ( is_wp_error($background_process) ) {
        $this->make_error($transient_id, 'Failed to initiate background processing.');
      }

      $this->log_status($transient_id, 'Adding plugin to queue.');
      wp_send_json_success([ 'transient_id' => $transient_id ]);
      wp_die();
    }

    if ( $action === 'download_activate' ) {
      $this->log_status($transient_id, 'Download and activation plugin started...');
      if ( empty($transient_id) ) {
        $this->make_error($transient_id, 'Invalid log id.');
      }

      $this->log_status($transient_id, 'Fetching details from server');

      $edd_api_url = add_query_arg([
		  'edd_action' => 'get_version',
		  'item_id'    => $id,
		  'license'    => $license_key,
		  'url'        => home_url(),
      ], 'https://store.quuantum.com');

      $response = wp_remote_get($edd_api_url, [ 'timeout' => 30 ]);

      $this->log_status($transient_id, 'Got the response from the server');

      if ( is_wp_error($response) ) {
        $this->make_error($transient_id, "Failed to fetch the download link. " . $response->get_error_code() . $response->get_error_message());
      }

      $this->log_status($transient_id, 'Processing the data');

      $response_code = wp_remote_retrieve_response_code($response);
      if ( $response_code !== 200 ) {
        $this->make_error($transient_id, 'API returned error code ' . $response_code);
      }

      $download_data = json_decode(wp_remote_retrieve_body($response), true);
      if ( empty($download_data['download_link']) ) {
        $this->make_error($transient_id, 'Invalid download URL.');
      }

      $plugin_url = $download_data['download_link'];
      $extensions = WPSP_Ext_Manager::get_extensions_from_qstore();

      if ( is_wp_error($extensions) ) {
        $this->make_error($transient_id, 'Failed to fetch extensions: ' . $extensions->get_error_message());
      }

      if ( empty($extensions) || ! is_array($extensions) ) {
        $this->make_error($transient_id, 'No extensions found in the list');
      }

      [$extension] = array_filter($extensions[ $plugin_list_key ], function ( $ext ) use ( $id ) {
        return (int)$ext['id'] === $id;
      });

      if ( empty($extension) || ! isset($extension['path']) || empty($extension['path']) ) {
        $this->make_error($transient_id, 'No related Addon found');
      }

      $plugin_path = $extension['path'];

      $this->log_status($transient_id, "Got path and url: {$plugin_path} {$plugin_url}");

      require_once(WPSP_DIR_PATH . 'modules/manager/classes/extensions-manager.php');
      $extensions_manager = new WPSP_Ext_Manager($plugin_url, $plugin_path);

      $this->log_status($transient_id, 'Downloading the plugin');

      $downloaded = $extensions_manager->download_plugin();
      if ( is_wp_error($downloaded) ) {
        $this->make_error($transient_id, "Failed to download the plugin. {$downloaded->get_error_code()}" . $downloaded->get_error_message());
      }

      $this->log_status($transient_id, 'Activating the plugin');

      $activated = $extensions_manager->activate_plugin();
      if ( is_wp_error($activated) ) {
        $this->make_error($transient_id, 'Failed to activate the plugin.' . $activated->get_error_message());
      }

      $this->log_status($transient_id, 'Plugin installation completed', 'completed');

      wp_send_json_success([ 'transient_id' => $transient_id ]);
      wp_die();
    }

    if ( $action === 'get_log' ) {
      $transient_id = isset($_POST['transient_id']) ? sanitize_text_field(wp_unslash($_POST['transient_id'])) : '';

      if ( empty($transient_id) ) {
        $this->make_error($transient_id, 'Transient ID is required.');
      }

      $status_data = get_transient($transient_id);

      if ( $status_data === false ) {
        $this->make_error($transient_id, 'Transient not found or expired.');
      }

      wp_send_json_success($status_data);
      wp_die();
    }

    $this->make_error($transient_id, 'Invalid action type.');
  }

  private function make_error( $id, $message ) {
    if ( ! empty($id) ) {
      $this->log_status($id, $message, 'error');
    }
    wp_send_json_error([ 'message' => $message ]);
    wp_die();
  }

  public function show_admin_notices() {
    if ( isset($_GET['page']) && $_GET['page'] === 'wpsp-settings' && ! empty($_GET['message']) ) {
      $message = urldecode(sanitize_text_field(wp_unslash($_GET['message'])));
      $error = isset($_GET['error']) && ! empty($_GET['error']);
    ?>
      <script type="text/javascript">
        jQuery(document).ready(function($) {
          Swal.fire({
            icon: '<?php echo $error ? 'error' : 'success'; ?>',
            title: '<?php echo $error ? 'Error' : 'Success'; ?>',
            text: '<?php echo esc_js($message); ?>',
            confirmButtonText: 'OK'
          });
        });
      </script>
<?php
    }
  }
}
?>
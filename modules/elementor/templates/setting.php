<?php
class WPSP_Elementor_Settings_Manager
{
    private $tab_status;

    public function __construct() {
        $this->tab_status = (isset($_GET['tab']) && $_GET['tab'] === 'elementor') ? 'active' : '';
    }

    public function add_general_settings( $html ) {
        ob_start();
        $current_status = get_option('wpsp_elementor_advance_options', '1');
        $checked = ! empty($current_status) ? 'checked' : '';
        $switchValue = ! empty($current_status) ? '1' : '';
?>
        <h3><?php esc_html_e('Elementor Settings', 'slickpress'); ?></h3>
        <div class="form-wrapper">
            <div class="form-row">
                <label class="titledesc" for="wpsp-elementor-advance-options">Advance Options</label>
                <div class="forminp forminp-checkbox">
                    <fieldset>
                        <label class="switch">
                            <input <?php echo esc_attr($checked); ?> type="checkbox" name="wpsp-elementor-advance-options">
                            <span class="slider round"></span>
                        </label>
                    </fieldset>
                </div>
            </div>
        </div>
<?php
        $panel = ob_get_clean();
        return $html . $panel;
    }

    public function handle_panel_settings() {
        if ( ! isset($_POST['wpsp_settings_nonce_field']) || ! check_admin_referer('wpsp_settings_nonce_action', 'wpsp_settings_nonce_field') ) {
            wp_die(esc_html__('Nonce verification failed!', 'slickpress'));
        }

        $enable_advance_options = (isset($_POST['wpsp-elementor-advance-options']) && ! empty($_POST['wpsp-elementor-advance-options'])) ? '1' : '';
        update_option('wpsp_elementor_advance_options', $enable_advance_options);
    }
}

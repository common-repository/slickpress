<?php
class WPSP_Settings_Manager
{
    private $license_tab_status;
    private $modules_tab_status;
    private $general_tab_status;
    private $modules;

    public function __construct( $modules = [] ) {
        $this->modules = $modules;
        $tab = sanitize_text_field(wp_unslash($_GET['tab'] ?? 'wpsp'));
        $this->license_tab_status = $tab === 'licenses' ? 'active' : '';
        $this->modules_tab_status = $tab === 'modules' ? 'active' : '';
        $this->general_tab_status = $tab === 'wpsp' ? 'active' : '';
    }

    public function tabs() {
        ob_start();
?>
        <a href="<?php echo esc_url(add_query_arg('tab', 'wpsp')); ?>" class="tablinks nav-tab <?php echo esc_attr($this->general_tab_status); ?>" data-tab="wpsp">General</a>
        <a href="<?php echo esc_url(add_query_arg('tab', 'modules')); ?>" class="tablinks nav-tab <?php echo esc_attr($this->modules_tab_status); ?>" data-tab="modules">Modules/Extensions</a>
        <?php
        $tab_content = ob_get_clean();
        echo wp_kses(apply_filters('wpsp/settings/tabs', $tab_content), [
            'a' => [
                'href'     => [],
                'class'    => [],
                'data-tab' => [],
            ],
        ]); ?>
        <a href="<?php echo esc_url(add_query_arg('tab', 'licenses')); ?>" class="tablinks nav-tab <?php echo esc_attr($this->license_tab_status); ?>" data-tab="licenses">Licenses</a>
    <?php
        return ob_get_clean();
    }

    public function panels() {
        ob_start();
    ?>
        <div id="wpsp" class="wpsp-form-group tabcontent <?php echo esc_attr($this->general_tab_status); ?>">
            <div class="wrap">
                <h2><?php esc_html_e('General Settings', 'slickpress'); ?></h2>
                <p>
                    <?php esc_html_e('Here you can manage general settings related to Slickpress and it\'s addons', 'slickpress'); ?>
                </p>
                <?php echo wp_kses(
                    apply_filters('wpsp/settings/panels/general', ''),
                    [
                        'div'    => [
                            'id'    => [],
                            'class' => [],
                        ],
                        'h2'     => [],
                        'h3'     => [],
                        'img'    => [
                            'src'    => [],
                            'alt'    => [],
                            'class'  => [],
                            'width'  => [],
                            'height' => [],
                        ],
                        'a'      => [
                            'href'   => [],
                            'target' => [],
                            'class'  => [],
                        ],
                        'button' => [
                            'type'             => [],
                            'name'             => [],
                            'class'            => [],
                            'data-install-url' => [],
                            'data-is-on-org'   => [],
                            'data-plugin-type' => [],
                            'data-type'        => [],
                            'data-id'          => [],
                        ],
                        'input'  => [
                            'type'    => [],
                            'name'    => [],
                            'id'      => [],
                            'value'   => [],
                            'checked' => [],
                            'class'   => [],
                        ],
                        'p'      => [
                            'class' => [],
                        ],
                        'label'  => [
                            'for'   => [],
                            'class' => [],
                        ],
                        'span'   => [
                            'class' => [],
                        ],
                    ]
                );
                ?>
            </div>
        </div>

        <div id="modules" class="wpsp-form-group tabcontent <?php echo esc_attr($this->modules_tab_status); ?>">
            <div class="wpsp-modules-cards">
                <div class="wpsp-sections wpsp-modules">
                    <h2><?php esc_html_e('Modules', 'slickpress'); ?></h2>
                    <div class="wpsp-cards">
                        <?php
                        foreach ( $this->modules as $module ) {
                            $module_slug = $module['slug'];
                            $module_name = $module['name'];
                            $module_description = $module['description'];
                            $module_icon = $module['icon'];
                            $checked_module_option = ! empty(get_option('enable_' . $module_slug, '1')) ? 'checked' : '';
                        ?>
                            <div class="wpsp-card wpsp-module-card">
                                <div class="wpsp-card-body">
                                    <div class="wpsp-card-icon">
                                        <img src="<?php echo esc_url($module_icon); ?>" alt="<?php echo esc_attr($module_name); ?>">
                                    </div>
                                    <div class="wpsp-card-content">
                                        <h3 class="wpsp-card-title">
                                            <?php echo esc_html($module_name); ?>
                                        </h3>
                                        <div class="wpsp-card-description">
                                            <p><?php echo esc_html($module_description); ?></p>
                                        </div>
                                    </div>
                                </div>
                                <div class="wpsp-card-actions">
                                    <div class="wpsp-card-action">
                                        <?php esc_html_e('Enable/Disable', 'slickpress'); ?>
                                        <label for="<?php echo esc_attr("{$module_slug}_enabled"); ?>" class="switch">
                                            <input
                                                <?php echo esc_attr($checked_module_option); ?>
                                                name="<?php echo esc_attr("{$module_slug}_enabled"); ?>"
                                                id="<?php echo esc_attr("{$module_slug}_enabled"); ?>"
                                                type="checkbox">
                                            <span class="slider round"></span>
                                        </label>

                                        <input
                                            type="hidden"
                                            name="<?php echo esc_attr("{$module_slug}_enabled_prev"); ?>"
                                            value="<?php echo esc_attr(empty($checked_module_option) ? '' : '1'); ?>">
                                    </div>
                                </div>
                            </div>
                        <?php } ?>
                    </div>
                </div>
            </div>

            <div class="wpsp-extensions-cards">
                <?php
                $result = WPSP_Ext_Manager::get_extensions_from_qstore();
                if ( is_wp_error($result) ) {
                    echo esc_html("Failed to fetch the extensions:\n" . $result->get_error_code() . "\n" . $result->get_error_message());
                    wp_die();
                }
                $extensions = $result['extensions'] ?? [];
                $recommended = $result['recommended'] ?? [];

                if ( ! empty($extensions) || ! empty($recommended) ) {
                    $is_wpsp_active = ! empty(trim(get_option('wpsp_license_key', '')));

                    function get_extension_button_type( $extension_path, $is_wpsp_active, $is_recommended = false ) {
                        if ( ! $is_recommended && ! $is_wpsp_active ) {
                            return 'upgrade';
                        }

                        $all_plugins = get_plugins();
                        if ( ! array_key_exists($extension_path, $all_plugins) ) {
                            return 'download';
                        }

                        if ( ! is_plugin_active($extension_path) ) {
                            return 'activate';
                        }

                        if ( is_plugin_active($extension_path) ) {
                            return 'deactivate';
                        }
                    }

                    function render_card( $item, $button_type = null, $is_recommended = true ) {
                        $item_id = esc_attr($item['id']);
                        $item_name = esc_html($item['name']);
                        $item_description = esc_html($item['description']);
                        $item_icon = esc_url($item['icon']);
                        $item_landing_page = esc_url($item['landing_page_url']);
                        $item_is_on_org = esc_attr($item['is_on_org']);

                        $item_org_page = $item_install_url = '';
                        if ( ! empty($item_is_on_org) ) {
                            $item_org_page = esc_url($item['wp_org_url']);
                            $install_plugin_slug = esc_attr(end(explode('/', trim($item_org_page, " \n\r\t\v\0/"))));
                            $item_install_url = esc_url(wp_nonce_url(admin_url('update.php?action=install-plugin&plugin=' . $install_plugin_slug), 'install-plugin_' . $install_plugin_slug));
                        }
                        $card_class = esc_attr($button_type ? "wpsp-$button_type-card" : "wpsp-recommended-card");
                ?>
                        <div class="wpsp-card <?php echo esc_attr($card_class); ?>">
                            <div class="wpsp-card-body">
                                <div class="wpsp-card-icon">
                                    <img src="<?php echo esc_url($item_icon); ?>" alt="<?php echo esc_attr($item_name); ?>">
                                </div>
                                <div class="wpsp-card-content">
                                    <h3 class="wpsp-card-title"><?php echo esc_html($item_name); ?></h3>
                                    <div class="wpsp-card-description">
                                        <p><?php echo esc_html($item_description); ?></p>
                                    </div>
                                </div>
                            </div>
                            <div class="wpsp-card-actions">
                                <div class="wpsp-card-action">
                                    <a target="_blank" href="<?php echo esc_url($item_landing_page); ?>"><?php esc_html_e('View Details', 'slickpress'); ?></a>
                                </div>
                                <?php if ( $button_type ) : ?>
                                    <div class="wpsp-card-action">
                                        <button type="button"
                                            data-install-url="<?php echo esc_url($item_install_url); ?>"
                                            data-is-on-org="<?php echo esc_attr($item_is_on_org); ?>"
                                            data-plugin-type="<?php echo esc_attr($is_recommended ? 'recommended' : 'extension'); ?>"
                                            data-type="<?php echo esc_attr($button_type); ?>"
                                            data-id="<?php echo esc_attr($item_id); ?>"
                                            class="wpsp-ext-card-btn wpsp-<?php echo esc_attr($button_type); ?>-btn"><?php echo esc_html(ucfirst($button_type)); ?></button>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>

                    <?php
                    }

                    function render_section( $items, $is_wpsp_active, $is_recommended = false ) {
                        foreach ( $items as $item ) {
                            $button_type = null;

                            if ( ! empty($item['path']) ) {
                                $button_type = get_extension_button_type($item['path'], $is_wpsp_active, $is_recommended);
                            }

                            render_card($item, $button_type, $is_recommended);
                        }
                    }

                    if ( ! empty($extensions) ) { ?>
                        <div class="wpsp-sections wpsp-extensions">
                            <?php
                            echo '<h2>' . esc_html__('SlickPress Addons', 'slickpress') . '</h2>';
                            ?>
                            <div class="wpsp-cards">
                                <?php
                                render_section($extensions, $is_wpsp_active); ?>
                            </div>
                        </div>
                    <?php
                    }
                    ?>

                    <?php
                    if ( ! empty($recommended) ) { ?>
                        <div class="wpsp-sections wpsp-recommended">
                            <?php echo '<h2>' . esc_html__('Recommended Plugins', 'slickpress') . '</h2>'; ?>
                            <div class="wpsp-cards">
                                <?php
                                render_section($recommended, $is_wpsp_active, true); ?>
                            </div>
                        </div>
                <?php
                    }
                }
                ?>

            </div>
        </div>

        <?php
        $license = trim(get_option('wpsp_license_key', ''));
        $status = get_option('wpsp_license_status');

        $is_license_active = ($status !== false && $status === 'valid');
        $btn_action = $is_license_active ? 'deactivate' : 'activate';
        ?>
        <div id="licenses" class="wpsp-form-group tabcontent <?php echo esc_attr($this->license_tab_status); ?>">
            <div class="wrap">
                <h2><?php esc_html_e('Activate License for SlickPress', 'slickpress'); ?></h2>
                <p>
                    <?php esc_html_e('Enter your license key to activate your plugin. If you don\'t have one, you can get it here:', 'slickpress'); ?>
                    <a target="_blank" href="https://www.quuantum.com/slickpress/">Get a license</a>
                </p>

                <p>
                    <?php esc_html_e('To manage your existing licenses and Quuantum Store account, ', 'slickpress'); ?>
                    <a target="_blank" href="https://store.quuantum.com/account/">Click here</a>
                </p>

                <div class="form-wrapper">
                    <div class="form-row">
                        <label for="wpsp_license_key">License Key</label>
                        <div class="form-input">
                            <input type="text" id="wpsp_license_key" name="wpsp_license_key" value="<?php echo esc_attr($license); ?>" class="regular-text" <?php echo $is_license_active ? 'disabled' : '' ?>>
                            <p class="description">Enter your license key.</p>
                        </div>

                        <label for="wpsp_license_status">License Status</label>
                        <div class="form-input">
                            <?php if ( $status !== false && $status === 'valid' ) { ?>
                                <div class="ls-active">Active</div>
                            <?php } else { ?>
                                <div class="ls-inactive">Inactive</div>
                            <?php } ?>
                            <input type="submit" class="button-secondary" name="wpsp_<?php echo esc_attr($btn_action); ?>_license" value="<?php echo esc_attr(ucfirst($btn_action)); ?> License" />
                        </div>
                    </div>
                    <?php echo wp_kses(
                        apply_filters('wpsp/settings/panels/licenses', ''),
                        [
                            'div'    => [
                                'id'    => [],
                                'class' => [],
                            ],
                            'h2'     => [],
                            'h3'     => [],
                            'img'    => [
                                'src'    => [],
                                'alt'    => [],
                                'class'  => [],
                                'width'  => [],
                                'height' => [],
                            ],
                            'a'      => [
                                'href'   => [],
                                'target' => [],
                                'class'  => [],
                            ],
                            'button' => [
                                'type'             => [],
                                'name'             => [],
                                'class'            => [],
                                'data-install-url' => [],
                                'data-is-on-org'   => [],
                                'data-plugin-type' => [],
                                'data-type'        => [],
                                'data-id'          => [],
                            ],
                            'input'  => [
                                'type'    => [],
                                'name'    => [],
                                'id'      => [],
                                'value'   => [],
                                'checked' => [],
                                'class'   => [],
                            ],
                            'p'      => [
                                'class' => [],
                            ],
                            'label'  => [
                                'for'   => [],
                                'class' => [],
                            ],
                            'span'   => [
                                'class' => [],
                            ],
                        ]
                    );
                    ?>
                </div>
            </div>
        </div>
<?php
        $panel_content = ob_get_clean();

        return wp_kses(
            apply_filters('wpsp/settings/panels', $panel_content),
            [
                'div'    => [
                    'id'    => [],
                    'class' => [],
                ],
                'h2'     => [],
                'h3'     => [],
                'img'    => [
                    'src'    => [],
                    'alt'    => [],
                    'class'  => [],
                    'width'  => [],
                    'height' => [],
                ],
                'a'      => [
                    'href'   => [],
                    'target' => [],
                    'class'  => [],
                ],
                'button' => [
                    'type'             => [],
                    'name'             => [],
                    'class'            => [],
                    'data-install-url' => [],
                    'data-is-on-org'   => [],
                    'data-plugin-type' => [],
                    'data-type'        => [],
                    'data-id'          => [],
                ],
                'input'  => [
                    'type'    => [],
                    'name'    => [],
                    'id'      => [],
                    'value'   => [],
                    'checked' => [],
                    'class'   => [],
                ],
                'p'      => [
                    'class' => [],
                ],
                'label'  => [
                    'for'   => [],
                    'class' => [],
                ],
                'span'   => [
                    'class' => [],
                ],
            ]
        );
    }

    public function handle_panel_settings() {
        if ( isset($_POST['wpsp_submit_settings']) || isset($_POST['wpsp_activate_license']) || isset($_POST['wpsp_deactivate_license']) ) {
            if ( ! isset($_POST['wpsp_settings_nonce_field']) || ! check_admin_referer('wpsp_settings_nonce_action', 'wpsp_settings_nonce_field') ) {
                $this->show_admin_error(esc_html__('Nonce verification failed!', 'slickpress'));
            }

            if ( isset($_POST['wpsp_activate_license']) ) {
                if ( ! isset($_POST['wpsp_license_key']) || empty($_POST['wpsp_license_key']) ) {
                    $this->show_admin_error(esc_html__('License key is required!', 'slickpress'));
                }

                $license = ! empty($_POST['wpsp_license_key']) ? sanitize_text_field(wp_unslash($_POST['wpsp_license_key'])) : '';

                if ( ! $license ) {
                    $this->show_admin_error(esc_html__('License key is required!', 'slickpress'));
                }

                $response = wp_remote_post(
                    WPSP_QS_URL,
                    array(
                        'timeout'   => 30,
                        'sslverify' => false,
                        'body'      => array(
                            'edd_action'  => 'activate_license',
                            'license'     => $license,
                            'item_id'     => WPSP_QS_ID,
                            'url'         => home_url(),
                            'environment' => function_exists('wp_get_environment_type') ? wp_get_environment_type() : 'production',
                        ),
                    )
                );

                if ( is_wp_error($response) || 200 !== wp_remote_retrieve_response_code($response) ) {
                    $this->show_admin_error(
                        is_wp_error($response) ? $response->get_error_message() : __('An error occurred, please try again.', 'slickpress')
                    );
                }

                $license_data = json_decode(wp_remote_retrieve_body($response));

                if ( false === $license_data->success ) {
                    switch ( $license_data->error ) {
                        case 'expired':
                            $message = sprintf(
                                __('Your license key expired on %s.', 'slickpress'),
                                wp_date(
                                    get_option('date_format'),
                                    strtotime($license_data->expires, time())
                                )
                            );
                            break;

                        case 'disabled':
                        case 'revoked':
                            $message = __('Your license key has been disabled.', 'slickpress');
                            break;

                        case 'missing':
                            $message = __('Invalid license.', 'slickpress');
                            break;

                        case 'invalid':
                        case 'site_inactive':
                            $message = __('Your license is not active for this URL.', 'slickpress');
                            break;

                        case 'item_name_mismatch':
                            $message = sprintf(__('This appears to be an invalid license key for %s.', 'slickpress'), 'Slickpress');
                            break;

                        case 'no_activations_left':
                            $message = __('Your license key has reached its activation limit.', 'slickpress');
                            break;

                        default:
                            $message = __('An error occurred, please try again.', 'slickpress');
                            break;
                    }

                    $this->show_admin_error($message);
                }

                if ( 'valid' !== $license_data->license ) {
                    $this->show_admin_error('License is invalid');
                }

                update_option('wpsp_license_key', $license);
                update_option('wpsp_license_status', 'valid');
                $this->show_admin_error('License successfully activated', false);
            }

            if ( isset($_POST['wpsp_deactivate_license']) ) {
                $license = trim(get_option('wpsp_license_key', ''));
                $response = wp_remote_post(
                    WPSP_QS_URL,
                    array(
                        'timeout'   => 30,
                        'sslverify' => false,
                        'body'      => array(
                            'edd_action'  => 'deactivate_license',
                            'license'     => $license,
                            'item_id'     => WPSP_QS_ID,
                            'url'         => home_url(),
                            'environment' => function_exists('wp_get_environment_type') ? wp_get_environment_type() : 'production',
                        ),
                    )
                );

                if ( is_wp_error($response) || 200 !== wp_remote_retrieve_response_code($response) ) {

                    $this->show_admin_error(
                        is_wp_error($response) ? $response->get_error_message() : __('An error occurred, please try again.', 'slickpress')
                    );
                }

                $license_data = json_decode(wp_remote_retrieve_body($response));

                if ( 'deactivated' !== $license_data->license ) {
                    $this->show_admin_error(sprintf('License is %s', $license_data->license));
                }

                delete_option('wpsp_license_status');
                delete_option('wpsp_license_key');
                $this->show_admin_error('License successfully deactivated', false);
            }

            if ( isset($_POST['wpsp_submit_settings']) ) {
                foreach ( $this->modules as $module ) {
                    $module_slug = $module['slug'];
                    $is_module_active_previous = isset($_POST[ "{$module_slug}_enabled_prev" ]) && ! empty($_POST[ "{$module_slug}_enabled_prev" ]);
                    $is_module_active = isset($_POST[ "{$module_slug}_enabled" ]) && ! empty($_POST[ "{$module_slug}_enabled" ]);
                    // print_r($_POST);
                    if ( $is_module_active_previous && $is_module_active && $is_module_active_previous !== $is_module_active ) {
                        update_option('enable_' . $module_slug, ( ! $is_module_active ? '' : '1'));
                        // die;
                        $action = $is_module_active ? 'Activating' : 'Deactivating';
                        echo '<div style="padding: 10px" class="notice notice-warning inline">' . esc_html($action) . ' module...</div> <meta http-equiv="refresh" content="1">';
                        exit;
                    }
                }
            }
        }

        $do_settings_submit = apply_filters(
            'wpsp/settings/do_submit',
            isset($_POST['wpsp_submit_settings'])
        );

        if ( $do_settings_submit ) {
            if ( ! isset($_POST['wpsp_settings_nonce_field']) || ! check_admin_referer('wpsp_settings_nonce_action', 'wpsp_settings_nonce_field') ) {
                wp_die(esc_html__('Nonce verification failed!', 'slickpress'));
            }
            if ( has_action('wpsp/settings/save') ) {
                do_action('wpsp/settings/save');
            }
        }
    }

    private function show_admin_error( $message, $error = true ) {
        $args = array(
            'page'    => 'wpsp-settings',
            'message' => rawurlencode($message),
        );

        if ( isset($_GET['tab']) ) {
            $args['tab'] = sanitize_text_field(wp_unslash($_GET['tab']));
        }

        if ( $error ) {
            $args['error'] = 1;
        }

        $redirect = add_query_arg(
            $args,
            admin_url('admin.php')
        );

        // pending improvement
        // wp_safe_redirect($redirect);
        echo '<meta http-equiv="refresh" content="0; URL=' . esc_html($redirect) . '" />';
        exit();
    }
}

<?php
class WPSP_Elementor_Container extends  \Elementor\Includes\Elements\Container
{
    protected function content_template() {
        parent::content_template(); ?>
        <# console.log(settings); if(settings.enable_slick){ let wpspSettings=JSON.stringify(Object.fromEntries( Object.entries(settings).filter(([key, value])=> key.startsWith('responsive_') || key.startsWith('slick_'))
            ));
            view.addRenderAttribute( 'wpsp-settings', 'data-wpsp-full-settings', wpspSettings );
            #>
            <div data-wpsp-pending="true" class="wpsp-elementor-preview" style="position: absolute;top: 50%;left: 50%;transform: translate(-50%, -50%);z-index: 99999999;max-width: 90px;text-align: center;background-color: white;padding: 15px;border-radius: 10px;" {{{ view.getRenderAttributeString( 'wpsp-settings' ) }}}>
                <img style="display:none;" src onerror="window.wpsp_refresh_init()" />
                <img src="<?php echo esc_url(WPSP_URL . 'modules/elementor/assets/img/preview-loader.gif'); ?>" alt="loading">
            </div>
            <# } #>
        <?php
    }
}

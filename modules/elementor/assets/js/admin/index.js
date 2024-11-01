import { WPSP_Settings_Manager } from './settings-manager.js';
import { parseBooleanValues } from '../common.js';

jQuery(document).ready(function () {
    function wpsp_refresh() {
        jQuery('[data-wpsp-pending="true"]').each(function () {
            let $element = jQuery(this);
            let settings = $element.attr('data-wpsp-full-settings');
            settings = JSON.parse(settings);

            let manager = new WPSP_Settings_Manager(settings);
            let cleanedSettings = manager.clean_elementor_container_settings();

            let $widgetslick = $element.parent('[data-element_type="container"]');
            $element.remove();

            let dataSlick = parseBooleanValues(cleanedSettings);
            dataSlick.slide = 'div:not(.elementor-shape, .ui-resizable-handle, .elementor-element-overlay)';

            let parentSection = $widgetslick;

            if ($widgetslick.children(':not(.elementor-shape, ui-resizable-handle, .elementor-element-overlay)').length === 1 && $widgetslick.children('.e-con-inner').length) {
                parentSection = $widgetslick.children('.e-con-inner');
            }

            if (parentSection?.hasClass('slick-initialized') && parentSection?.children('.slick-list').length) {
                try { parentSection?.slick('slickSetOption', dataSlick, true); } catch (e) { }
            } else {
                if (parentSection?.hasClass('slick-initialized')) {

                    try { parentSection?.slick('unslick'); } catch (e) { }

                    var seen = {};
                    parentSection?.children('[data-id]:not([role="tabpanel"], [tabindex])').each(function () {
                        let id = jQuery(this).data('id');
                        if (seen[id])
                            jQuery(this).remove();
                        else
                            seen[id] = true;
                    });

                    parentSection?.children('[role="tabpanel"], [tabindex]').remove();
                }
                setTimeout(() => {
                    try { parentSection?.slick(dataSlick); } catch (e) { }
                }, 500);
            }

            console.log('slick final preview rendered', dataSlick, $widgetslick);
        });
    }

    window.wpsp_refresh_init = () => {
        wpsp_refresh();
    };


})
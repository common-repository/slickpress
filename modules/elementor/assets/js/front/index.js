import { parseBooleanValues } from '../common.js';

window.wpspSliders = window.wpspSliders || {};

jQuery(document).ready(function () {
	jQuery('.wpsp-enabled').each(function () {
		const $widgetSlick = jQuery(this);
		let parentSection = $widgetSlick;

		if ($widgetSlick.children().length === 1 && $widgetSlick.children('.e-con-inner').length) {
			parentSection = $widgetSlick.children('.e-con-inner');
		}

		if (!parentSection.hasClass('slick-initialized')) {
			let dataSlick = $widgetSlick.data('wpsp-slick-settings');
			dataSlick = parseBooleanValues(dataSlick);

			const id = $widgetSlick.data('wpsp-id');
			const slickInstance = parentSection.slick(dataSlick);

			window.wpspSliders[id] = slickInstance;
			parentSection.trigger('wpspInit', [id, parentSection, slickInstance]);
		}
	});
});

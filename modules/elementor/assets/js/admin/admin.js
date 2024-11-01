
jQuery(document).ready(function () {
    jQuery('.slider.round').click(function () {
        var checkbox = jQuery(this).prev('input[name="wpsp-elementor-advance-options"]');
        console.log(checkbox.val);
        if (checkbox.val() === 'no') {
            checkbox.val('yes');
        } else {
            checkbox.val('no');
        }
        //checkbox.prop('checked', !checkbox.prop('checked'));
    });
})
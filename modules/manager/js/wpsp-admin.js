(function ($) {
  $('.tablinks').on('click', function (event) {
    event.preventDefault();
    const tabname = $(this).data('tab');
    const newUrl = $(this).attr('href');
    history.pushState(null, '', newUrl);

    // $(".tabcontent").hide();
    $(".tablinks, .wpsp-form-group").removeClass("active");
    // $("#" + tabname).show();
    $(this).addClass("active");
    $("#" + tabname).addClass("active");
  });

  jQuery(document).ready(function ($) {
    $('.wpsp-module-card .wpsp-card-actions input[type="checkbox"]').change(function () {
      var $this = $(this);
      // setTimeout(() => {
      $this.closest('form').find('[name="wpsp_submit_settings"]').trigger('click');
      // }, 1000);
    });

    $(".slick-option-settings").on("click", ".wpsp-ext-card-btn.wpsp-upgrade-btn", function (e) {
      e.preventDefault();
      $('[data-tab="licenses"]').trigger('click');
    });

    $(".slick-option-settings").on("click", ".wpsp-ext-card-btn.wpsp-activate-btn, .wpsp-ext-card-btn.wpsp-deactivate-btn", function (e) {
      e.preventDefault();
      let $this = $(this);
      var pluginId = $this.attr('data-id');
      var action = $this.hasClass('wpsp-activate-btn') ? 'activate' : 'deactivate';
      let pluginType = $this.attr('data-plugin-type');
      let itemType = (pluginType === 'recommended') ? 'plugin' : 'extension';

      if (!pluginId) {
        Swal.fire({
          title: 'Error',
          text: 'Missing plugin ID.',
          icon: 'error'
        });
        return;
      }

      var actionText = action === 'activate' ? 'Activating' : 'Deactivating';

      Swal.fire({
        title: `${actionText}...`,
        text: 'Please wait while the action is being completed.',
        icon: 'info',
        allowOutsideClick: false,
        showConfirmButton: false,
        didOpen: () => {
          Swal.showLoading();
          let swal = (Swal.isVisible()) ? Swal.update : Swal.fire;

          $.ajax({
            url: wpsp_settings.ajax_url,
            type: 'POST',
            data: {
              action: 'wpsp_manage_extensions',
              action_type: action,
              id: pluginId,
              plugin_type: pluginType,
              nonce: wpsp_settings.nonce
            },
            success: function (response) {
              Swal.hideLoading();
              if (response.success) {
                swal({
                  title: 'Success',
                  text: `${itemType} ${action === 'activate' ? 'activated' : 'deactivated'} successfully.`,
                  icon: 'success',
                  allowOutsideClick: true,
                  showConfirmButton: true,
                  didOpen: () => {
                    Swal.hideLoading();
                  }
                }).then(() => {
                  location.reload();
                });
              } else {
                swal({
                  title: 'Error',
                  text: response.data.message || `Failed to ${action} the ${itemType}.`,
                  icon: 'error',
                  allowOutsideClick: true,
                  showConfirmButton: true,
                  didOpen: () => {
                    Swal.hideLoading();
                  }
                });
              }
            },
            error: function () {
              Swal.hideLoading();
              swal({
                title: 'Error',
                text: `An unexpected error occurred while ${action === 'activate' ? 'activating' : 'deactivating'} the ${itemType}.`,
                icon: 'error',
                allowOutsideClick: true,
                showConfirmButton: true,
                didOpen: () => {
                  Swal.hideLoading();
                }
              });
            }
          });
        }
      }).then(() => {
        location.reload();
      });
    });

    $(".slick-option-settings").on("click", ".wpsp-extensions .wpsp-ext-card-btn.wpsp-download-btn", function (e) {

      e.preventDefault();
      let $this = $(this);
      var pluginId = $this.attr('data-id');

      if (!pluginId) {
        Swal.fire({
          title: 'Error',
          text: 'Missing plugin ID.',
          icon: 'error'
        });
        return;
      }

      Swal.fire({
        title: 'Processing...',
        text: 'Please wait while the action is being completed.',
        icon: 'info',
        allowOutsideClick: false,
        showConfirmButton: false,
        didOpen: () => {
          Swal.showLoading();

          $.ajax({
            url: wpsp_settings.ajax_url,
            type: 'POST',
            data: {
              action: 'wpsp_manage_extensions',
              action_type: 'add_to_queue',
              id: pluginId,
              plugin_type: 'extension',
              nonce: wpsp_settings.nonce
            },
            success: function (response) {
              if (response.success) {
                var transientId = response.data.transient_id;
                checkExtStatus(transientId, pluginId);
              } else {
                Swal.fire({
                  title: 'Error',
                  text: response.data.message,
                  icon: 'error'
                });
              }
            },
            error: function () {
              Swal.fire({
                title: 'Error',
                text: 'An unexpected error occurred while initiating the action.',
                icon: 'error'
              });
            }
          });
        }
      });
    });

    $(".slick-option-settings").on("click", ".wpsp-recommended .wpsp-ext-card-btn.wpsp-download-btn", function (e) {

      e.preventDefault();
      let $this = $(this);
      var pluginInstallUrl = $this.attr('data-install-url');

      if (!pluginInstallUrl) {
        Swal.fire({
          title: 'Error',
          text: 'Missing plugin url.',
          icon: 'error'
        });
        return;
      }

      location.href = pluginInstallUrl;
    });

    let checkExtStatus = (transientId, pluginId) => {
      var checkStatusInterval = setInterval(function () {
        $.ajax({
          url: wpsp_settings.ajax_url,
          type: 'POST',
          data: {
            action: 'wpsp_manage_extensions',
            action_type: 'get_log',
            transient_id: transientId,
            id: pluginId,
            plugin_type: 'extension',
            nonce: wpsp_settings.nonce
          },
          success: function (response) {
            try {
              if (!response) {
                throw new Error("Invalid response");
              }
              if (!response?.success) {
                throw new Error(response.data.message);
              }

              var status = response.data.status;
              var log = response.data.log;
              var alertContent = log;

              if (status === 'error') {
                throw new Error(alertContent);
              }

              let swal_settings = {
                title: status,
                text: alertContent,
                icon: 'info',
                showConfirmButton: false,
                didOpen: () => {
                  Swal.showLoading();
                }
              };
              let swal = (Swal.isVisible()) ? Swal.update : Swal.fire;
              swal(swal_settings);
              Swal.isVisible() ? Swal.showLoading() : '';

              if (status !== 'Processing') {
                clearInterval(checkStatusInterval);
                Swal.fire({
                  title: (status === 'completed') ? "Done!" : status,
                  text: alertContent,
                  icon: (status === 'completed') ? "success" : 'info'
                }).then((result) => {
                  window.location.reload();
                });
              }
            } catch (error) {
              clearInterval(checkStatusInterval);
              Swal.fire({
                title: 'Error',
                text: error.message,
                icon: 'error'
              }).then((result) => {
                window.location.reload();
              });
            }
          },
          error: function () {
            clearInterval(checkStatusInterval);
            Swal.fire({
              title: 'Error',
              text: 'An unexpected error occurred while checking status.',
              icon: 'error'
            }).then((result) => {
              window.location.reload();
            });
          }
        });
      }, 2000);
    }

  });

})(jQuery);
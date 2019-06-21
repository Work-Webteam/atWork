(function ($) {
  'use strict';
  Drupal.behaviors.photosAccess = {
    attach: function (context) {
      var checkval = $('#photos_access_privacy input[type=radio]:checked').val();
      switch (checkval) {
        case '2':
          $('#photos_access_viewuser, .photos_access_remove').show(500);
          break;
        case '3':
          $('#photos_access_password').show(500);
          $('#photos_access_password label').text(Drupal.t('Reset Password'));
          break;
      }
      $('#photos_access_privacy input[type=radio]').change(function () {
        var checkval = $(this).attr('checked', true).val();
        switch (checkval) {
          case '2':
            $('#photos_access_viewuser, .photos_access_remove').show(500);
            $('#photos_access_password').hide(500);
            break;
          case '3':
            $('#photos_access_password').show(500);
            $('#photos_access_viewuser, .photos_access_remove').hide(500);
            break;
          default:
            $('#photos_access_password').hide(500);
            $('#photos_access_viewuser, .photos_access_remove').hide(500);
            break;
        }
      });
    }
  };
})(jQuery);

(function ($, Drupal) {
  Drupal.behaviors.user_alert_get_message = {
    attach: function(context, settings) {
      $.ajax({
        type: 'GET',
        url: '/js/user-alert/get-message',
        success: function(response) {
          $('.block-user-alert').html(response['alerts']);
        }
      });
    	
      $('body').delegate('div.user-alert-close a', 'click', function() {
        id = $(this).attr('rel');
        $.ajax({
          type: 'GET',
          data: 'message=' + id,
          url: '/js/user-alert/close-message',
          success: function(response) {
            $('#user-alert-' + id).fadeOut('slow');
          }
        });
      });
  	}
  };
}(jQuery, Drupal));
(function ($, Drupal) {
  Drupal.behaviors.content_segmentation_get_message = {
    attach: function(context, settings) {
      $.ajax({
        type: 'GET',
        url: '/js/content_segmentation/get-message',
        success: function(response) {
          $('.block-user-alert').html(response['alerts']);
        }
      });
    	
      $('body').delegate('div.user-alert-close a', 'click', function() {
        id = $(this).attr('rel');
        $.ajax({
          type: 'GET',
          data: 'message=' + id,
          url: '/js/content_segmentation/close-message',
          success: function(response) {
            $('#user-alert-' + id).fadeOut('slow');
          }
        });
      });
  	}
  };
}(jQuery, Drupal));
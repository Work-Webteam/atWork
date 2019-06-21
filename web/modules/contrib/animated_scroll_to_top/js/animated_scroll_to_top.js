(function ($, Drupal, drupalSettings) {

  Drupal.behaviors.animated_scroll_to_top = {

    attach: function (context, settings) {
      
      $(document).ready(function(){
        $('body').append('<a href="#" class="scrollup">Scroll</a>');
          var position = drupalSettings.animated_scroll_to_top_position;
          var button_bg_color = drupalSettings.animated_scroll_to_top_button_bg_color;
          var hover_button_bg_color = drupalSettings.animated_scroll_to_top_button_hover_bg_color;
          if (position == 1) {
            $('.scrollup').css({"left":"100px","background-color":button_bg_color});
          } else {
            $('.scrollup').css({"right":"100px","background-color":button_bg_color});
          }
          
          $(".scrollup").hover(function(){
            $(this).css("background-color", hover_button_bg_color);
          }, function(){
            $(this).css("background-color", button_bg_color);
          });
          
          $(window).scroll(function () {
            if ($(this).scrollTop() > 100) {
              $('.scrollup').fadeIn();
            } else {
              $('.scrollup').fadeOut();
            }
          });
          
          $(".scrollup").click(function(){
            $("html, body").animate({
              scrollTop: 0
            }, 600);
            return false;
          });
      });
    }
  };
})(jQuery, Drupal, drupalSettings);

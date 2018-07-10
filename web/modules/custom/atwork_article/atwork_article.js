(function ($) {
  
  $( document ).ready(function() {
    var $button = $('.node-article-edit-form .link-edit-summary');
    $button.html('Edit teaser');
  
    var toggleClick = true;
    $button.on('click', function (e) {
      if (toggleClick) {
      	setTimeout(function() { 
          $button.html('Hide teaser');
      	},1);

      } else {
      	setTimeout(function() { 
          $button.html('Edit teaser');
      	},1);
      }
      toggleClick = !toggleClick;
    });
  });
    
})(jQuery);
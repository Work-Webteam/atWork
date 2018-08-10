(function ($) {
  "use strict";
  /**
   * Enable the colorbox inline functionality.
   */
  Drupal.behaviors.colorboxInline = {
    attach: function (context, drupalSettings) {
      $('[data-colorbox-inline]', context).once().click(function () {
        var $link = $(this);
        var settings = $.extend({}, drupalSettings.colorbox, {
          href: false,
          inline: true
        }, {
          className: $link.data('class'),
          href: $link.data('colorbox-inline'),
          width: $link.data('width'),
          height: $link.data('height')
        });
        $link.colorbox(settings);
      });
    }
  };
})(jQuery);

/**
 * @file
 * Responsive advanced sidebar tray.
 *
 * This also supports collapsible navigable is the 'is-collapsible' class is
 * added to the main element, and a target element is included.
 */
(function ($, Drupal) {

  'use strict';

  /**
   * Initialise the advanced sidebar tray JS.
   */
  Drupal.behaviors.root = {
    attach: function (context) {
      var $body = $(context).find('body.seeds-root');
      // Add a click handler to the button(s) that toggle the advanced sidebar
      // tray.
      var $toggleBtn = $body.find('[data-toggle-advanced-sidebar]');
      if ($body.length && $toggleBtn.length) {
        $toggleBtn.unbind('click').on('click', function (e) {
          e.preventDefault();
          $body.toggleClass('advanced-sidebar-tray-toggled');

          // Trigger resize event.
          $(window).trigger('resize.tabs');
        });
      }
      //Ckeditor
      if (typeof CKEDITOR !== "undefined") {
        CKEDITOR.config.autoGrow_maxHeight = 0.8 * (window.innerHeight);
      }
    }
  };

})(jQuery, Drupal);

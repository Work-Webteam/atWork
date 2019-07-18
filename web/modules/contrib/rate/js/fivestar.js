/**
 * @file
 * Provides Fivestar voting effect.
 */

(function($, Drupal) {

  'use strict';

  Drupal.behaviors.fivestar = {
    attach: function(context, settings) {
      var $stars = $('.rate-widget-fivestar li').once();

      // Add hover effect to fivestar voting.
      $stars.each(function (){
        var $this = $(this);
        var $prev = $this.prev();

        $this.hover(function () {
          $this.addClass('hovering');
          $prev.trigger('mouseenter');
        }, function () {
          $this.removeClass('hovering');
          $prev.trigger('mouseleave');
        });
      });
    }
  };
})(jQuery, Drupal);

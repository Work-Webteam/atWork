/**
 *
 */
(function ($, Drupal) {
  Drupal.behaviors.atworkGroups = {
    attach: function (context, settings) {
      if (!$('#edit-field-themes--wrapper').hasClass('radio-update')) {
        $('#edit-field-themes--wrapper').addClass('radio-update');
        $('.js-form-item-field-themes.form-item-field-themes .option').after(
          '<div class="group-swatch"><div class="primary-col">Primary</div>' +
          '<div class="secondary-col">Secondary</div><div class="link-col">' +
          'Links</div></div>'
      );
      }
    }
  }
})(jQuery, Drupal);

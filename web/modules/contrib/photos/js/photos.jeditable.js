(function ($, Drupal, drupalSettings) {
  'use strict';

  Drupal.behaviors.photosJeditable = {
    attach(context) {
      const atext = [Drupal.t('Save'), Drupal.t('Cancel'), Drupal.t('Being updated...'), Drupal.t('Click to edit')];

      $('.jQueryeditable_edit_title, .jQueryeditable_edit_des', context).on({
        mouseenter: function photosJeditableMouseEnter() {
          $(this).addClass('photos_ajax_hover');
        },
        mouseleave: function photosJeditableMouseLeave() {
          $(this).removeClass('photos_ajax_hover');
        }
      });

      // Edit image title.
      function photosCancelJeditable(element) {
        // Cancel.
        $(element).removeClass('photos_ajax_hover');
        return false;
      }
      $('.jQueryeditable_edit_title', context).editable(`${drupalSettings.path.baseUrl}photos/image/update`, {
        loadurl: `${drupalSettings.path.baseUrl}photos/image/update/load`,
        type: 'textarea',
        submit: atext[0],
        cancel: atext[1],
        indicator: atext[2],
        tooltip: atext[3],
        loadtype: 'POST',
        loadtext: Drupal.t('Loading...'),
        submitdata: {},
        callback() {
          // Success.
          // @todo add option for title selector i.e. #page-title $('#page-title').text(value);
          // @note test on album page (make sure album title does not change).
          $(this).removeClass('photos_ajax_hover');
        }
      }, photosCancelJeditable(this));
      // Edit image description.
      $('.jQueryeditable_edit_des', context).editable(`${drupalSettings.path.baseUrl}photos/image/update`, {
        loadurl: `${drupalSettings.path.baseUrl}photos/image/update/load`,
        height: 140,
        type: 'textarea',
        submit: atext[0],
        cancel: atext[1],
        indicator: atext[2],
        tooltip: atext[3],
        loadtype: 'POST',
        loadtext: Drupal.t('Loading...'),
        submitdata: {},
        callback() {
          // Success.
          $(this).removeClass('photos_ajax_hover');
        }
      }, photosCancelJeditable(this));
    }
  };
}(jQuery, Drupal, drupalSettings));

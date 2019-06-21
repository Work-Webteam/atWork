(function ($, Drupal, drupalSettings) {
  'use strict';

  Drupal.behaviors.photos = {
    attach(context) {
      if ($('#photos-sortable').length) {
        $('#photos-sortable', context).sortable({
          stop() {
            const sortedIDs = $('#photos-sortable').sortable('toArray');
            const sortUrl = `${drupalSettings.path.baseUrl}photos/ajax/rearrange`;
            const postData = {
              order: sortedIDs,
              pid: drupalSettings.photos.pid,
              uid: drupalSettings.photos.uid,
              type: drupalSettings.photos.sort
            };
            function photosSortUpdateComplate() {
              $('#photos-sort-updates').show();
              $('#photos-sort-updates').delay(500).fadeOut(500);
            }
            $('#photos-sort-updates').load(sortUrl, postData, photosSortUpdateComplate());
          }
        });
        $('#photos-sortable', context).disableSelection();
      }
    }
  };
}(jQuery, Drupal, drupalSettings));

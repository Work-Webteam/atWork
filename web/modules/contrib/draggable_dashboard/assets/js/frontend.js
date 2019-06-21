/**
 * @file
 * Contains javascript functionality for the draggable dashboard module.
 */

(function ($, window, Drupal) {

  Drupal.behaviors.draggableDashboardActions = {
    attach: function attach(context, settings) {
      $('.draggable-dashboard', context).once('draggable-processed').each(function() {
        var blocksMin = [];
        // If we already saved an order in the local storage,
        // get the value and collapse those blocks
        if (localStorage.getItem('dashboard-blocks-min')) {
          blocksMin = JSON.parse(localStorage.getItem('dashboard-blocks-min'));
        }
        if(blocksMin.length > 0) {
          $.each(blocksMin, function(i, block) {
            $(block).find('.draggable-dashboard-block__content').hide();
            $(block).find('.draggable-dashboard__icon--collapse')
            .toggleClass('draggable-dashboard__icon--collapse draggable-dashboard__icon--expand').attr('title', 'Expand');
          });
        }

        // Expand / collapse the content of a block
        $('.draggable-dashboard__icon--toggle', context).click(function() {
          var blockId = '#' + $(this).closest('.draggable-dashboard-block').attr('id');
          $(blockId).find('.draggable-dashboard-block__content').slideToggle();
          $(this).toggleClass('draggable-dashboard__icon--collapse draggable-dashboard__icon--expand');
          if($(this).hasClass('draggable-dashboard__icon--expand')) {
            // If we have just minimized (collapsed) the content:
            $(this).attr('title', 'Expand');
            // Add block to the minimized blocks list and save to Local Storage
            if(blocksMin.indexOf(blockId) === -1) {
              blocksMin.push(blockId);
              localStorage.setItem('dashboard-blocks-min', JSON.stringify(blocksMin));
            }
          } else {
            // If we have just expanded the content:
            $(this).attr('title', 'Collapse');
            // Remove block from the minimized blocks list and save to Local Storage
            if(blocksMin.indexOf(blockId) !== -1) {
              blocksMin.splice(blocksMin.indexOf(blockId), 1);
              localStorage.setItem('dashboard-blocks-min', JSON.stringify(blocksMin));
            }
          }
        });

        // Maximize / minimize block
        $('.draggable-dashboard__icon--resize', context).click(function() {
          $(this).closest('.draggable-dashboard-block').toggleClass('draggable-dashboard-block--maximized');
          $(this).toggleClass('draggable-dashboard__icon--maximize draggable-dashboard__icon--minimize');
          if($(this).hasClass('draggable-dashboard__icon--minimize')) {
            $(this).attr('title', 'Minimize');
          } else {
            $(this).attr('title', 'Maximize');
          }
        });
      });
    }
  };

})(jQuery, window, Drupal);

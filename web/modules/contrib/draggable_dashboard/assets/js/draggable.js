/**
 * @file
 * Contains javascript functionality for the draggable blocks module.
 */

 (function ($, window, Drupal) {
  Drupal.behaviors.draggable_dashboard = {
    attach: function (context, settings) {
      $('.draggable-dashboard').once('dashboard-processed').each(function() {
        var $dashboards = $('.draggable-dashboard', context);
        var blocks = {};
        // If we already saved an order in the local storage, get the value
        if (localStorage.getItem('dashboard-blocks-order')) {
          blocks = JSON.parse(localStorage.getItem('dashboard-blocks-order'));
        }

        $dashboards.each(function(){
          var $dashboard = $(this);
          var $columns = $(this).find('div.draggable-dashboard-column');
          // Make columns sortable
          $columns.sortable({
            items: '.draggable-dashboard-block',
            handle: '.draggable-dashboard-block__header',
            connectWith: $columns,
            placeholder: 'draggable-dashboard-placeholder',
            forcePlaceholderSize: true,
            over: function () {

            },
            stop: function () {
              dashboardChanged($dashboard);
            }
          });
        });

        dashboardChanged = function ($dashboard) {
          blocks = {};
          var regionIndex;
          $dashboard.find('.draggable-dashboard-column').each(function () {
            // Determine region id.
            regionIndex = $(this).data('id');
            var columnId = '#' + $(this).attr('id');
            // Build blocks object
            if (blocks[columnId] == undefined) {
                blocks[columnId] = [];
            }
            $(this).find('.draggable-dashboard-block').each(function(i, e){
              var blockId = '#' + $(this).attr('id');
              blocks[columnId].push(blockId);
            });
          });

          localStorage.setItem('dashboard-blocks-order', JSON.stringify(blocks));
        };

        // Rearrange blocks
        if (blocks !== undefined) {
          $.each(blocks, function(col, blocksInCol) {
            // Put every block in the correct place in its region.
            $.each(blocksInCol, function(j, block) {
              $(col).append($(block));
            });
          });
        }

      });
    }
  };

})(jQuery, window, Drupal);

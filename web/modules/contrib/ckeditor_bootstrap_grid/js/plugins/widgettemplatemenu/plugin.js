(function () {
  'use strict';

  CKEDITOR.plugins.add('widgettemplatemenu', {
    requires: 'menu',

    init: function init(editor) {
      var buttonData = {};
      if (editor.plugins.widgetbootstrap !== 'undefined') {
        buttonData.widgetbootstrapLeftCol = 'Insert left column template';
        buttonData.widgetbootstrapRightCol = 'Insert right column template';
        buttonData.widgetbootstrapTwoCol = 'Insert two column template';
        buttonData.widgetbootstrapThreeCol = 'Insert three column template';
      }

      var items = {};
      if (buttonData !== 'undefined') {
        for (var key in buttonData) {
          if (buttonData.hasOwnProperty(key)) {
            items[key] = {
              label: buttonData[key],
              command: key,
              group: 'widgettemplatemenu',
              icon: key
            };
          }
        }
      }

      editor.addMenuGroup('widgettemplatemenu');
      editor.addMenuItems(items);

      editor.ui.add('WidgetTemplateMenu', CKEDITOR.UI_MENUBUTTON, {
        label: 'Insert Template',
        icon: this.path + 'icons/widgettemplatemenu.png',
        onMenu: function onMenu() {
          return {
            widgetcommonQuotebox: editor.commands.widgetcommonQuotebox === 'undefined' ? false : editor.commands.widgetbootstrapLeftCol.state,
            widgetbootstrapLeftCol: editor.commands.widgetbootstrapLeftCol === 'undefined' ? false : editor.commands.widgetbootstrapLeftCol.state,
            widgetbootstrapRightCol: editor.commands.widgetbootstrapRightCol === 'undefined' ? false : editor.commands.widgetbootstrapRightCol.state,
            widgetbootstrapTwoCol: editor.commands.widgetbootstrapTwoCol === 'undefined' ? false : editor.commands.widgetbootstrapTwoCol.state,
            widgetbootstrapThreeCol: editor.commands.widgetbootstrapThreeCol === 'undefined' ? false : editor.commands.widgetbootstrapThreeCol.state
          };
        }
      });
    }
  });
})();

/**
 * @file
 * Functionality to enable Bootstrap Widget Menu functionality in CKEditor.
 */

(function () {
  'use strict';

  // Register plugin.
  CKEDITOR.plugins.add('widgettemplatemenu', {
    requires: 'menu',

    init(editor) {
      // Set the default button info based on installed plugins
      const buttonData = {};
      if (editor.plugins.widgetbootstrap !== 'undefined') {
        buttonData.widgetbootstrapLeftCol = 'Insert left column template';
        buttonData.widgetbootstrapRightCol = 'Insert right column template';
        buttonData.widgetbootstrapTwoCol = 'Insert two column template';
        buttonData.widgetbootstrapThreeCol = 'Insert three column template';
      }

      // Build the list of menu items
      const items = {};
      if (buttonData !== 'undefined') {
        for (const key in buttonData) {
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
      // Items must belong to a group.
      editor.addMenuGroup('widgettemplatemenu');
      editor.addMenuItems(items);

      editor.ui.add('WidgetTemplateMenu', CKEDITOR.UI_MENUBUTTON, {
        label: 'Insert Template',
        icon: `${this.path}icons/widgettemplatemenu.png`,
        onMenu() {
          // You can control the state of your commands live, every time
          // the menu is opened.
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
}());

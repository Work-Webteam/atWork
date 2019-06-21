/**
 * @file
 * Functionality to enable Bootstrap Grid functionality in CKEditor.
 */

(function () {
  'use strict';

  // Register plugin.
  CKEDITOR.plugins.add('widgetbootstrap', {

    requires: 'widget',
    icons: 'widgetbootstrapLeftCol,widgetbootstrapRightCol,widgetbootstrapTwoCol,widgetbootstrapThreeCol',

    init(editor) {
    // Configurable settings
    // var allowedWidget = editor.config.widgetbootstrap_allowedWidget != undefined ?
    // editor.config.widgetbootstrap_allowedFull :
    //    'p h2 h3 h4 h5 h6 span br ul ol li strong em img[!src,alt,width,height]';

      const showButtons = editor.config.widgetbootstrapShowButtons !== 'undefined' ? editor.config.widgetbootstrapShowButtons : true;

      // Define the widgets
      editor.widgets.add('widgetbootstrapLeftCol', {

        button: showButtons ? 'Add left column box' : 'undefined',

        template:
                '<div class="row two-col-left">' +
                    '<div class="col-sm-3 col-md-3 col-sidebar"><p><img src="https://placehold.it/300x250&text=Image" /></p></div>' +
                    '<div class="col-sm-9 col-md-9 col-main"><p>Content</p></div>' +
                '</div>',

        editables: {
          col1: {
            selector: '.col-sidebar'
          },
          col2: {
            selector: '.col-main'
          }
        },

        upcast(element) {
          return element.name === 'div' && element.hasClass('two-col-right-left');
        }

      });

      editor.widgets.add('widgetbootstrapRightCol', {

        button: showButtons ? 'Add right column box' : 'undefined',

        template:
                '<div class="row two-col-right">' +
                     '<div class="col-sm-9 col-md-9 col-main"><p>Content</p></div>' +
                     '<div class="col-sm-3 col-md-3 col-sidebar"><p><img src="https://placehold.it/300x250&text=Image" /></p></div>' +
                '</div>',

        editables: {
          col1: {
            selector: '.col-sidebar'
          },
          col2: {
            selector: '.col-main'
          }
        },

        upcast(element) {
          return element.name === 'div' && element.hasClass('two-col-right');
        }

      });

      editor.widgets.add('widgetbootstrapTwoCol', {

        button: showButtons ? 'Add two column box' : 'undefined',

        template:
                '<div class="row two-col">' +
                     '<div class="col-sm-6 col-md-6 col-1"><p><img src="https://placehold.it/500x280&text=Image" /></p><p>Content</p></div>' +
                     '<div class="col-sm-6 col-md-6 col-2"><p><img src="https://placehold.it/500x280&text=Image" /></p><p>Content</p></div>' +
                '</div>',

        editables: {
          col1: {
            selector: '.col-1'
          },
          col2: {
            selector: '.col-2'
          }
        },

        upcast(element) {
          return element.name === 'div' && element.hasClass('two-col');
        }

      });

      editor.widgets.add('widgetbootstrapThreeCol', {

        button: showButtons ? 'Add three column box' : 'undefined',

        template:
                '<div class="row three-col">' +
                     '<div class="col-sm-4 col-md-4 col-1"><p><img src="https://placehold.it/400x225&text=Image" /></p><p>Text below</p></div>' +
                     '<div class="col-sm-4 col-md-4 col-2"><p><img src="https://placehold.it/400x225&text=Image" /></p><p>Text below</p></div>' +
                     '<div class="col-sm-4 col-md-4 col-3"><p><img src="https://placehold.it/400x225&text=Image" /></p><p>Text below</p></div>' +
                 '</div>',

        editables: {
          col1: {
            selector: '.col-1'
          },
          col2: {
            selector: '.col-2'
          },
          col3: {
            selector: '.col-3'
          }
        },

        upcast(element) {
          return element.name === 'div' && element.hasClass('three-col');
        }

      });
      // Append the widget's styles when in the CKEditor edit page,
      // added for better user experience.
      // Assign or append the widget's styles depending on the existing setup.
      if (typeof editor.config.contentsCss === 'object') {
        editor.config.contentsCss.push(CKEDITOR.getUrl(`${this.path}contents.css`));
      }
      else {
        editor.config.contentsCss = [editor.config.contentsCss, CKEDITOR.getUrl(`${this.path}contents.css`)];
      }
    }


  });
}());

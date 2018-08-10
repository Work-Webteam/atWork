(function($, Drupal) {
  'use strict';

  /**
   * @constructor
   */
  Drupal.insert.editors.CKEditor = Drupal.insert.editors.CKEditor || (function() {

    /**
     * @constructor
     */
    function CKEditor() {
      var self = this;

      if (this.check()) {
        CKEDITOR.on('instanceReady', function(e) {
          $(self).trigger({
            type: 'instanceReady',
            editor: e.editor
          });
        });
      }
    }

    $.extend(CKEditor.prototype, {
      constructor: CKEditor,

      /**
       * @inheritDoc
       */
      editorConstructor: CKEDITOR.editor,

      /**
       * @inheritDoc
       */
      check: function() {
        return typeof CKEDITOR !== 'undefined';
      },

      /**
       * @inheritDoc
       */
      getId: function(editor) {
        return editor.id;
      },

      /**
       * @inheritDoc
       */
      isReady: function(editor) {
        return editor.status === 'ready';
      },

      /**
       * @inheritDoc
       */
      getInstances: function() {
        return CKEDITOR.instances;
      },

      /**
       * @inheritDoc
       */
      getCurrentInstance: function() {
        return CKEDITOR.currentInstance;
      },

      /**
       * @inheritDoc
       */
      getElement: function(editor) {
        return editor.element ? editor.element.$ : undefined;
      },

      /**
       * @inheritDoc
       */
      getDom: function(editor) {
        return $(editor.document.$).find('body');
      },

      /**
       * @inheritDoc
       */
      getData: function(editor) {
        return editor.getData();
      },

      /**
       * @inheritDoc
       */
      getUUID: function(editor, element) {
        var widget = editor.widgets.getByElement(new CKEDITOR.dom.element(element));

        if (widget && widget.data['data-entity-uuid']) {
          return widget.data['data-entity-uuid'];
        }

        return null;
      },

      /**
       * @inheritDoc
       */
      setAltAttribute: function(editor, element, text) {
        var widget = editor.widgets.getByElement(new CKEDITOR.dom.element(element));

        if (widget) {
          widget.setData('alt', text);
        }
      },

      /**
       * @inheritDoc
       */
      setTitleAttribute: function(editor, element, text) {
        var widget = editor.widgets.getByElement(new CKEDITOR.dom.element(element));

        if (widget) {
          widget.setData('title', text);
        }
      }

    });

    return CKEditor;

  })();

  Drupal.insert.editors.interfaces.CKEditor = new Drupal.insert.editors.CKEditor();

})(jQuery, Drupal);

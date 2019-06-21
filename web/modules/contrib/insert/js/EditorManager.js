(function($, Drupal) {
  'use strict';

  /**
   * Manages interaction with editor instances.
   * @constructor
   *
   * @param {Drupal.insert.EditorInterface} [editorInterface]
   */
  Drupal.insert.EditorManager = Drupal.insert.EditorManager || (function() {

    /**
     * @type {Drupal.insert.EditorInterface}
     */
    var eInterface;

    /**
     * Storage for editor contents for checking whether contents actually
     * changed when the "change" event is triggered.
     * @type {Object}
     */
    var editorContents;

    /**
     * @constructor
     *
     * @param {Drupal.insert.EditorInterface} [editorInterface]
     */
    function EditorManager(editorInterface) {
      if (editorInterface && typeof editorInterface !== 'object') {
        throw new Error('editorInterface needs to be an instance of Drupal.insert.EditorInterface.');
      }

      eInterface = editorInterface;
      editorContents = {};
      this._editors = {};
      this._classesToRetain = {};
    }

    $.extend(EditorManager.prototype, {

      /**
       * @type {Object}
       */
      _editors: undefined,

      /**
       * @type {Object}
       */
      _classesToRetain: undefined,

      /**
       * @param {eInterface.editorConstructor} editor
       * @return {boolean}
       *   FALSE if editor is already registered, TRUE if editor was added.
       */
      addEditor: function(editor) {
        var editorId = eInterface.getId(editor);

        if (this._editors[editorId]) {
          return false;
        }

        var self = this;

        this._evaluateEditorContent(editor);
        editorContents[editorId] = eInterface.getData(editor);

        editor.on('change', function(e) {
          var editorContent = eInterface.getData(e.editor);

          if (editorContent === editorContents[eInterface.getId(e.editor)]) {
            return;
          }
          editorContents[eInterface.getId(e.editor)] = editorContent;

          self._evaluateEditorContent(e.editor);
        });

        this._editors[editorId] = editor;

        return true;
      },

      /**
       * Parses the editor's content and stores CSS classes to retain at their
       * corresponding nodes.
       *
       * @param {eInterface.editorConstructor} editor
       */
      _evaluateEditorContent: function(editor) {
        var self = this;

        $(eInterface.getDom(editor)).find('[data-insert-type]').each(function() {
          var $element = $(this);
          var classes = $element.data('insert-class');

          if (typeof classes !== 'undefined' && classes !== '') {
            // Element has already been evaluated: Make sure classes to retain
            // are in place.
            $.each(classes.split(' '), function() {
              if (!$element.hasClass(this)) {
                $element.addClass(this);
              }
            });
            return true;
          }

          // Initialize element.
          var retain = [];
          classes = $(this).attr('class');
          if (typeof classes !== 'undefined') {
            $.each($(this).attr('class').split(' '), function() {
              if (self._isClassToRetain(this, $element.data('insert-type'))) {
                retain.push(this);
              }
            });
          }

          $(this).attr('data-insert-class', retain.length ? retain.join(' ') : '');
        });
      },

      /**
       * Determines whether a CSS class is supposed to be retained.
       *
       * @param {string} className
       * @param {string} fieldType
       * @return {boolean}
       */
      _isClassToRetain: function(className, fieldType) {
        return this._classesToRetain[fieldType]
          && $.inArray(className, this._classesToRetain[fieldType]) !== -1;
      },

      /**
       * @param {Object} classesToRetain
       */
      updateClassesToRetain: function(classesToRetain) {
        this._classesToRetain = classesToRetain;
      }

    });

    return EditorManager;

  })();

})(jQuery, Drupal);

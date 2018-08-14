(function($, Drupal) {
  'use strict';

  /**
   * Keeps track of focusing elements that the Insert module interacts with.
   * @constructor
   *
   * @param {Drupal.insert.EditorInterface} [editorInterface]
   * @param {HTMLElement} [defaultTarget]
   */
  Drupal.insert.FocusManager = Drupal.insert.FocusManager || (function() {

    /**
     * @type {Drupal.insert.EditorInterface|undefined}
     */
    var eInterface;

    /**
     * @constructor
     *
     * @param {Drupal.insert.EditorInterface} [editorInterface]
     * @param {HTMLElement} [defaultTarget]
     */
    function FocusManager(editorInterface, defaultTarget) {
      if (editorInterface && typeof editorInterface !== 'object') {
        throw new Error('editorInterface needs to be an instance of Drupal.insert.EditorInterface.');
      }

      eInterface = editorInterface;
      this._$defaultTarget = $(defaultTarget);
      this._editors = {};
      this._$textareas = $();
    }

    $.extend(FocusManager.prototype, {

      /**
       * Target for inserting when no textarea was focused yet.
       * @type {jQuery}
       */
      _$defaultTarget: undefined,

      /**
       * @type {Object}
       */
      _editors: undefined,

      /**
       * @type {jQuery}
       */
      _$textareas: undefined,

      /**
       * @param {HTMLElement} [element]
       */
      setDefaultTarget: function(element) {
        this._$defaultTarget = $(element);
      },

      /**
       * @param {eInterface.editorConstructor} editor
       */
      addEditor: function(editor) {
        if (eInterface && !this._editors[eInterface.getId(editor)]) {
          this._attachEvents(editor);
          this._editors[eInterface.getId(editor)] = editor;
        }
      },

      /**
       * @param {jQuery} $textareas
       */
      addTextareas: function($textareas) {
        var $unregistered = $textareas.not(this._$textareas);

        if ($unregistered.length) {
          this._attachEvents($unregistered);
          this._$textareas = this._$textareas.add($unregistered);
        }
      },

      /**
       * @return {*[]}
       */
      getEditors: function() {
        var editors = [];
        $.each(this._editors, function() {
          editors.push(this);
        });
        return editors;
      },

      /**
       * @return {jQuery}
       */
      getTextareas: function() {
        return this._$textareas;
      },

      /**
       * @param {eInterface.editorConstructor|jQuery} editorOrTextareas
       */
      _attachEvents: function(editorOrTextareas) {

        // Beware: CKEditor neither supports chaining nor event namespaces!
        editorOrTextareas.on('focus', function(event) {
          $(':data(insertIsFocused)').removeData('insertIsFocused');
          $(':data(insertLastFocused)').removeData('insertLastFocused');

            var subject = (event.editor && event.editor instanceof eInterface.editorConstructor)
              ? eInterface.getElement(event.editor)
              : this;
            $(subject).data('insertIsFocused', true).data('insertLastFocused', true);
        });

        editorOrTextareas.on('blur', function(event) {
          var subject = (event.editor && event.editor instanceof eInterface.editorConstructor)
            ? eInterface.getElement(event.editor)
            : this;
          // Delay removing focus marker, so, when instantly clicking on the
          // Insert button, the focused subject will receive the input.
          setTimeout(function() {
            $(subject).removeData('insertIsFocused');
          }, 1000);
        });
      },

      /**
       * @return {HTMLElement|null}
       */
      _getLastFocused: function() {
        var $lastFocusedTextarea = $(':data(insertLastFocused)');
        if (!$lastFocusedTextarea.length) {
          $lastFocusedTextarea = this._$defaultTarget;
        }
        return $lastFocusedTextarea.length ? $lastFocusedTextarea.get(0) : null;
      },

      /**
       * @return {jQuery}
       */
      _getCurrentlyFocusedTextarea: function() {
        return this._$textareas.find(':data(insertIsFocused)');
      },

      /**
       * @return {eInterface.editorConstructor|HTMLElement}
       */
      getActive: function() {
        var subject = this._getCurrentlyFocusedTextarea();

        if (subject.length !== 0) {
          return subject.get(0);
        }

        var lastFocused = this._getLastFocused();
        subject = undefined;

        if (eInterface) {
          $.each(this._editors, function(id, editor) {
            // Editor element is undefined after switching to restricted HTML
            // mode, as that mode detaches the WYSIWYG editor.
            var editorElement = eInterface.getElement(editor);
            if (editorElement && lastFocused && editorElement === lastFocused) {
              subject = this;
              return false;
            }
          });
        }

        if (subject) {
          return subject;
        }
        if (lastFocused) {
          return lastFocused;
        }

        var currentEditorInstance = eInterface
          ? eInterface.getCurrentInstance()
          : undefined;

        if (currentEditorInstance) {
          return currentEditorInstance;
        }

        return null;
      }

    });

    return FocusManager;

  })();

})(jQuery, Drupal);

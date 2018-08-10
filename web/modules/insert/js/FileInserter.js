(function($, Drupal) {
  'use strict';

  /**
   * Handles inserting content into text areas and editors.
   * @constructor
   *
   * @param {HTMLElement} insertContainer
   * @param {Drupal.insert.FocusManager} focusManager
   * @param {Drupal.insert.EditorInterface} [editorInterface]
   * @param {Object} [widgetSettings]
   */
  Drupal.insert.FileInserter = Drupal.insert.FileInserter || (function() {

    /**
     * @constructor
     *
     * @param {HTMLElement} insertContainer
     * @param {Drupal.insert.FocusManager} focusManager
     * @param {Drupal.insert.EditorInterface|undefined} [editorInterface]
     * @param {Object} [widgetSettings]
     */
    function FileInserter(insertContainer, focusManager, editorInterface, widgetSettings) {
      var self = this;

      if (typeof insertContainer === 'undefined') {
        throw new Error('insertContainer needs to be specified.');
      }
      if (typeof focusManager === 'undefined') {
        throw new Error('focusManager needs to be specified.')
      }
      if (editorInterface && typeof editorInterface !== 'object') {
        throw new Error('editorInterface needs to be an instance of Drupal.insert.EditorInterface.');
      }

      this._focusManager = focusManager;
      this._editorInterface = editorInterface;

      this._$container = $(insertContainer);
      this._$insertStyle = this._$container.find('.insert-style');
      this._$button = this._$container.find('.insert-button');

      this._$button.on('click.insert', function() {
        self._insertIntoActive(self._buildContent());
      });

      this._type = this._$container.data('insert-type');
      this._uuid = this._$container.data('uuid');
      this._settings = widgetSettings || {};
    }

    $.extend(FileInserter.prototype, {

      /**
       * @type {Drupal.insert.FocusManager}
       */
      _focusManager: undefined,

      /**
       * @type {Drupal.insert.EditorInterface|undefined}
       */
      _editorInterface: undefined,

      /**
       * The widget type or type of the field, the Inserter interacts with, i.e.
       * "file" or "image".
       * @type {string|undefined}
       */
      _type: undefined,

      /**
       * @type {jQuery}
       */
      _$container: undefined,

      /**
       * The Insert style select box or the hidden style input, if just one
       * style is enabled.
       * @type {jQuery}
       */
      _$insertStyle: undefined,

      /**
       * @type {jQuery}
       */
      _$button: undefined,

      /**
       * @type {string}
       */
      _uuid: undefined,

      /**
       * @type {Object}
       */
      _settings: null,

      /**
       * @return {string}
       */
      getType: function() {
        return this._type;
      },

      /**
       * Inserts content into the current (or last active) editor/textarea on
       * the page.
       *
       * @param {string} content
       * @return {HTMLElement|undefined}
       *
       * @triggers insertIntoActive
       */
      _insertIntoActive: function(content) {
        var active = this._focusManager.getActive();
        var activeElement;

        // Allow other modules to perform template replacements.
        var options = this._aggregateVariables();
        options['template'] = content;
        $(this).trigger('insertIntoActive', [options]);
        content = options['template'];

        if (active && active.insertHtml && this._editorInterface) {
          active.insertHtml(content);
          activeElement = this._editorInterface.getElement(active);
        }
        else if (active) {
          this._insertAtCursor(active, content);
          activeElement = active;
        }

        return activeElement;
      },

      /**
       * Insert content into a textarea at the current cursor position.
       *
       * @param {HTMLElement} textarea
       *   The DOM object of the textarea that will receive the text.
       * @param {string} content
       *   The string to be inserted.
       */
      _insertAtCursor: function(textarea, content) {
        // Record the current scroll position.
        var scroll = textarea.scrollTop;

        // IE support.
        if (document.selection) {
          textarea.focus();
          var sel = document.selection.createRange();
          sel.text = content;
        }

        // Mozilla/Firefox/Netscape 7+ support.
        else if (textarea.selectionStart || textarea.selectionStart == '0') {
          var startPos = textarea.selectionStart;
          var endPos = textarea.selectionEnd;
          textarea.value = textarea.value.substring(0, startPos)
            + content
            + textarea.value.substring(endPos, textarea.value.length);
          textarea.selectionStart = textarea.selectionEnd = startPos + content.length;
        }

        // Fallback, just add to the end of the content.
        else {
          textarea.value += content;
        }

        // Ensure the textarea does not scroll unexpectedly.
        textarea.scrollTop = scroll;
      },

      /**
       * @return {string}
       */
      _buildContent: function() {
        var template = this._getTemplate();
        template = this._replacePlaceholders(template);
        return this._cleanupPlaceholders(template);
      },

      /**
       * @return {string}
       */
      _getTemplate: function() {
        var style = this._$insertStyle.val();
        return $('input.insert-template[name$="[' + style + ']"]', this._$container).val();
      },

      /**
       * Aggregates the template for the selected style and returns is with the
       * placeholders replaced.
       *
       * @return {string}
       */
      _replacePlaceholders: function(template) {
        var variables = this._aggregateVariables();
        var fieldRegExp;

        $.each(variables['fields'], function(fieldName, fieldValue) {
          if (fieldValue) {
            fieldRegExp = new RegExp('__' + fieldName + '(_or_filename)?__', 'g');
            template = template.replace(fieldRegExp, fieldValue);
          }
          else {
            fieldRegExp = new RegExp('__' + fieldName + '_or_filename__', 'g');
            template = template.replace(fieldRegExp, variables.filename);
          }
        });

        // File name placeholder.
        fieldRegExp = new RegExp('__filename__', 'g');
        template = template.replace(fieldRegExp, variables.filename);

        return template;
      },

      /**
       * @param {string} template
       * @return {string}
       */
      _cleanupPlaceholders: function(template) {
        // Cleanup unused replacements.
        template = template.replace(/"__([a-z0-9_]+)__"/g, '""');

        // Cleanup empty attributes (other than alt).
        template = template.replace(/([a-z]+)[ ]*=[ ]*""[ ]?/g, function(match, tagName) {
          return (tagName === 'alt') ? match : '';
        });

        template = template.replace(/[ ]?>/g, '>');

        return template;
      },

      /**
       * @return {Object}
       */
      _aggregateVariables: function() {
        var variables = {
          widgetType: this._type,
          filename: this._$container.find('input.insert-filename').val(),
          style: this._$insertStyle.val(),
          fields: {}
        };
        var $fieldDataWrapper = this._$container.parent();

        // Replace field name placeholders with field values.
        $.each(this._settings.fields, function(fieldName, fieldValueElementSelector) {
          var fieldValue = $(fieldValueElementSelector, $fieldDataWrapper).val();

          if (fieldValue) {
            fieldValue = fieldValue
              .replace(/&/g, '&amp;')
              .replace(/"/g, '&quot;')
              .replace(/'/g, '&#39;')
              .replace(/</g, '&lt;')
              .replace(/>/g, '&gt;');
          }
          variables['fields'][fieldName] = fieldValue;
        });

        return variables;
      }

    });

    return FileInserter;

  })();

})(jQuery, Drupal);

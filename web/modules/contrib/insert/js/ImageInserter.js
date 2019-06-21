(function($, Drupal) {
  'use strict';

  var PARENT = Drupal.insert.FileInserter;

  /**
   * The names of the pseudo-styles that images may be inserted in without
   * having to specify the alternative text (if set to required).
   * @type {string[]}
   */
  var noAltTextInsertStyles = ['link', 'icon_link'];

  /**
   * Handles inserting images into text areas and editors.
   * @constructor
   *
   * @param {HTMLElement} insertContainer
   * @param {Drupal.insert.FocusManager} focusManager
   * @param {Drupal.insert.EditorInterface} [editorInterface]
   * @param {Object} [widgetSettings]
   */
  Drupal.insert.ImageInserter = Drupal.insert.ImageInserter || (function() {

    /**
     * @constructor
     *
     * @param {HTMLElement} insertContainer
     * @param {Drupal.insert.FocusManager} focusManager
     * @param {Drupal.insert.EditorInterface} [editorInterface]
     * @param {Object} [widgetSettings]
     */
    function ImageInserter(insertContainer, focusManager, editorInterface, widgetSettings) {
      PARENT.prototype.constructor.apply(this, arguments);

      var $rotator = this._$container.find('.insert-rotate');

      if ($rotator.length) {
        this._rotator = new Drupal.insert.Rotator(
          $rotator.get(0),
          this._$container.find('.insert-templates').get(0),
          focusManager,
          editorInterface
        );
      }

      this._initAltField();
      this._initTitleField();

      this._disable(!this._checkAltField());
    }

    $.extend(ImageInserter.prototype, PARENT.prototype, {
      constructor: ImageInserter,

      /**
       * The button overlay allows hover events over the button in disabled
       * state. The overlay is used only when the button is disabled and is used
       * to highlight invalid components when hovering the button.
       * @type {jQuery|undefined}
       */
      _$buttonOverlay: undefined,

      /**
       * @type {Drupal.insert.Rotator|undefined}
       */
      _rotator: undefined,

      /**
       * The alternative text field corresponding to the image, if any.
       * @type {jQuery}
       */
      _$altField: undefined,

      /**
       * The title text field corresponding to the image, if any.
       * @type {jQuery}
       */
      _$titleField: undefined,

      /**
       * @inheritDoc
       *
       * @param {string} content
       * @return {HTMLElement|undefined}
       *
       * @triggers insertIntoActive
       */
      _insertIntoActive: function(content) {
        var activeElement = PARENT.prototype._insertIntoActive.call(this, content);

        if (activeElement) {
          this._contentWarning(activeElement, content);
        }

        return activeElement;
      },

      /**
       * Warns users when attempting to insert an image into an unsupported
       * field.
       *
       * This function is only a 90% use-case, as it does not support when the
       * filter tips are hidden, themed, or when only one format is available.
       * However, it should fail silently in these situations.
       *
       * @param {HTMLElement} editorElement
       * @param {string} content
       */
      _contentWarning: function(editorElement, content) {
        if (!content.match(/<img /)) {
          return;
        }

        var $wrapper = $(editorElement).parents('div.text-format-wrapper:first');
        if (!$wrapper.length) {
          return;
        }

        $wrapper.find('.filter-guidelines-item:visible li').each(function(index, element) {
          var expression = new RegExp(Drupal.t('Allowed HTML tags'));
          if (expression.exec(element.textContent) && !element.textContent.match(/<img( |>)/)) {
            alert(Drupal.t("The selected text format will not allow it to display images. The text format will need to be changed for this image to display properly when saved."));
          }
        });
      },

      /**
       * @inheritDoc
       *
       * @param {string} template
       * @return {string}
       */
      _replacePlaceholders: function(template) {
        template = PARENT.prototype._replacePlaceholders.call(this, template);
        return this._replaceImageDimensionPlaceholders(template);
      },

      /**
       * Checks for a maximum dimension and scales down the width if necessary.
       *
       * @param {string} template
       * @return {string}
       *   Updated template.
       */
      _replaceImageDimensionPlaceholders: function(template) {
        var widthMatches = template.match(/width[ ]*=[ ]*"(\d*)"/i);
        var heightMatches = template.match(/height[ ]*=[ ]*"(\d*)"/i);
        if (this._settings.maxWidth && widthMatches && parseInt(widthMatches[1]) > this._settings.maxWidth) {
          var insertRatio = this._settings.maxWidth / widthMatches[1];
          var width = this._settings.maxWidth;
          template = template.replace(/width[ ]*=[ ]*"?(\d*)"?/i, 'width="' + width + '"');

          if (heightMatches) {
            var height = Math.round(heightMatches[1] * insertRatio);
            template = template.replace(/height[ ]*=[ ]*"?(\d*)"?/i, 'height="' + height + '"');
          }
        }
        return template;
      },

      /**
       * Initializes the alternative text input element, if any.
       */
      _initAltField: function() {
        this._$altField = this._$container
          .parent()
          .find('input[name$="[alt]"]');

        if (!this._$altField.length) {
          return;
        }

        var self = this;

        this._$altField.on('input.insert', function() {
          self._disable(!self._checkAltField());
          self._updateAttributes('alt');
        });

        this._$insertStyle.on('change.insert', function() {
          self._disable(!self._checkAltField());
        });
      },

      /**
       * Initializes the title text input element, if any.
       */
      _initTitleField: function() {
        this._$titleField = this._$container
          .parent()
          .find('input[name$="[title]"]');

        if (!this._$titleField.length) {
          return;
        }

        var self = this;

        this._$titleField.on('input.insert', function() {
          self._updateAttributes('title');
        });
      },

      /**
       * Checks whether the alternative text configuration, its input and the
       * selected style allows the image to get inserted. For example, if the
       * alternative text is required, it may not be empty to allow inserting an
       * image, as long as the image shall not be inserted in the form of a
       * plain text link.
       *
       * @return {boolean}
       *   TRUE if alternative text configuration/input is valid, FALSE if not.
       */
      _checkAltField: function() {
        return !this._$altField.length
          || !this._$altField.prop('required')
          || this._$altField.prop('required') && this._$altField.val().trim() !== ''
          || $.inArray(this._$insertStyle.val(), noAltTextInsertStyles) !== -1
      },

      /**
       * Updates attributes of all inserted image instances.
       *
       * @param {string} attributeName
       */
      _updateAttributes: function(attributeName) {
        var self = this;
        var regExp = new RegExp(this._uuid + '$');
        var value = this['_$' + attributeName + 'Field'].val();

        this._focusManager.getTextareas().each(function() {
          self._updateTextareaAttributes(attributeName, $(this), regExp, value);
        });

        if (this._editorInterface) {
          $.each(this._focusManager.getEditors(), function() {
            self._updateEditorAttributes(attributeName, this, regExp, value);
          });
        }
      },

      /**
       * @param {string} attributeName
       * @param {jQuery} $textarea
       * @param {RegExp} regExp
       * @param {string} value
       */
      _updateTextareaAttributes: function(attributeName, $textarea, regExp, value) {
        var $dom = $('<div>').html($textarea.val());
        var found = false;

        $dom.find('img').each(function() {
          var $img = $(this);
          var uuid = $img.data('entity-uuid');

          if (uuid && regExp.test(uuid)) {
            $img.attr(attributeName, value);
            found = true;
          }
        });

        if (found) {
          $textarea.val($dom.html());
        }
      },

      /**
       * @param {string} attributeName
       * @param {*} editor
       * @param {RegExp} regExp
       * @param {string} value
       */
      _updateEditorAttributes: function(attributeName, editor, regExp, value) {
        var editorInterface = this._editorInterface;
        var $dom = editorInterface.getDom(editor);

        $dom.find('img').each(function() {
          var uuid = editorInterface.getUUID(editor, this);

          if (uuid && regExp.test(uuid)) {
            if (attributeName === 'alt') {
              editorInterface.setAltAttribute(editor, this, value);
            }
            else if (attributeName === 'title') {
              editorInterface.setTitleAttribute(editor, this, value);
            }
          }
        });
      },

      /**
       * @param {boolean} disable
       */
      _disable: function(disable) {
        if (!this._$buttonOverlay) {
          var self = this;

          this._$buttonOverlay = this._$container.find('.insert-button-overlay')
            .on('mouseover.insert', function() {
              self._$altField.addClass('insert-required');
            })
            .on('mouseout.insert', function() {
              self._$altField.removeClass('insert-required');
            });
        }

        this._$button.prop('disabled', disable);
        this._$buttonOverlay[disable ? 'show' : 'hide']();
      }

    });

    return ImageInserter;

  })();

})(jQuery, Drupal);

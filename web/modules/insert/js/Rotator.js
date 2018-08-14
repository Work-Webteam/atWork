(function($, Drupal) {
  'use strict';

  /**
   * Image Rotator
   * Responsible for having images rotated and updates image derivatives already
   * placed in editor.
   * @constructor
   *
   * @param {HTMLElement} node
   * @param {HTMLElement} templates
   * @param {Drupal.insert.FocusManager} focusManager
   * @param {Drupal.insert.EditorInterface} [editorInterface]
   */
  Drupal.insert.Rotator = Drupal.insert.Rotator || (function() {

    /**
     * @type {Drupal.insert.FocusManager}
     */
    var fManager;

    /**
     * @type {Drupal.insert.EditorInterface}
     */
    var eInterface;

    /**
     * @constructor
     *
     * @param {HTMLElement} node
     * @param {HTMLElement} templates
     * @param {Drupal.insert.FocusManager} focusManager
     * @param {Drupal.insert.EditorInterface} [editorInterface]
     */
    function Rotator(node, templates, focusManager, editorInterface) {
      var self = this;

      if (typeof node === 'undefined') {
        throw new Error('Rotator root node needs to be specified.')
      }
      if (typeof node === 'undefined') {
        throw new Error('Templates node needs to be specified.')
      }
      if (typeof focusManager !== 'object') {
        throw new Error('focusManager needs to be an instance of Drupal.insert.FocusManager.')
      }
      if (editorInterface && typeof editorInterface !== 'object') {
        throw new Error('editorInterface needs to be an instance of Drupal.insert.EditorInterface.');
      }

      this._$node = $(node);
      this._$templates = $(templates);
      fManager = focusManager;
      eInterface = editorInterface;

      $('.insert-rotate-controls a', node).on('click.insert-rotator', function(event) {
        event.preventDefault();

        $.ajax($(this).attr('href'), {
          dataType: 'json'
        })
          .done(function(response) {
            $('input[name="changed"]').val(response.revision);
            self._updateImageRotation(response.data);
          });
      });
    }

    $.extend(Rotator.prototype, {

      /**
       * @type {jQuery}
       */
      _$node: undefined,

      /**
       * @type {jQuery}
       */
      _$templates: undefined,

      /**
       * Updates the preview image, the insert templates as well as any images
       * derivatives already placed.
       *
       * @param {Object} json
       */
      _updateImageRotation: function(json) {
        $.each(json, function(style_name, url) {
          json[style_name] += url.indexOf('?') === -1 ? '?' : '&';
          json[style_name] += 'insert-refresh=' + Date.now();
        });

        this._updatePreviewImage(json);
        this._updateTemplates(json);
        this._updateInsertedImages(json);
      },

      /**
       * @param {Object} json
       */
      _updatePreviewImage: function(json) {
        var $previewImg = this._$node.parents('.image-widget').find('.image-preview img');

        $.each($previewImg.attr('class').split(/\s+/), function() {
          var styleClass = this.match('^image-style-(.+)');

          if (styleClass !== null && typeof json[styleClass[1]] !== 'undefined') {
            $previewImg
              .attr('src', json[styleClass[1]])
              .removeAttr('width')
              .removeAttr('height');

            return false;
          }
        });
      },

      /**
       * @param {Object} json
       */
      _updateTemplates: function(json) {
        var self = this;

        $.each(json, function(styleName, url) {
          self._$templates
            .children('.insert-template[name*="[' + styleName + ']"]')
            .each(function() {
              var $template = $(this);
              var template =  $template.val();
              var widthMatches = template.match(/width[ ]*=[ ]*"(\d*)"/i);
              var heightMatches = template.match(/height[ ]*=[ ]*"(\d*)"/i);

              if (heightMatches.length === 2) {
                template = template.replace(/(width[ ]*=[ ]*")(\d*)"/i, 'width="' + heightMatches[1] + '"');
              }
              if (widthMatches.length === 2) {
                template = template.replace(/(height[ ]*=[ ]*")(\d*)"/i, 'height="' + widthMatches[1] + '"');
              }

              $template.val(
                template.replace(/src="[^"]+"/, 'src="' + url + '"')
              );
            });
        });
      },

      /**
       * @param {Object} json
       */
      _updateInsertedImages: function(json) {
        var self = this;

        $.each(json, function(styleName, url) {
          var updatedImageCleanUrl = url.split('?')[0];
          fManager.getTextareas().each(function() {
            var $textarea = $(this);
            var $newDom = self._updateDom($('<div>').html($(this).val()), url, updatedImageCleanUrl);

            if ($newDom !== null) {
              $textarea.val($newDom.html());
            }
          });


          if (eInterface) {
            $.each(fManager.getEditors(), function() {
              self._updateDom(eInterface.getDom(this), url, updatedImageCleanUrl);
            });
          }
        });
      },

      /**
       * @param {jQuery} $dom
       * @param {string} url
       * @param {string} updatedImageCleanUrl
       * @return {jQuery|null}
       *   null if no update was applied.
       */
      _updateDom: function($dom, url, updatedImageCleanUrl) {
        var found = false;

        $dom.find('img').each(function() {
          var $img = $(this);
          var imgCleanUrl = $img.attr('src').split('?')[0];

          if (imgCleanUrl === updatedImageCleanUrl) {
            var width = $img.attr('width');
            var height = $img.attr('height');

            if (width) {
              $img.attr('height', width);
            }
            else {
              $img.removeAttr('height');
            }
            if (height) {
              $img.attr('width', height);
            }
            else {
              $img.removeAttr('width');
            }

            // Editor is supposed to automatically take care of the cache
            // breaker getting removed.
            $img.attr('src', url);

            found = true;
          }
        });

        return found ? $dom : null;
      }

    });

    return Rotator;

  })();

})(jQuery, Drupal);

(function($, Drupal, drupalSettings) {
  'use strict';

  /**
   * @type {Drupal.insert.FocusManager}
   */
  var focusManager;

  /**
   * @type {Drupal.insert.EditorManager|undefined}
   */
  var editorManager;

  /**
   * Behavior to add "Insert" buttons.
   */
  Drupal.behaviors.insert = {};
  Drupal.behaviors.insert.attach = function(context) {

    var editorInterface = undefined;

    $.each(Drupal.insert.editors.interfaces, function() {
      if (this.check()) {
        editorInterface = this;
        return false;
      }
    });

    focusManager = focusManager || new Drupal.insert.FocusManager(
      editorInterface
    );

    focusManager.addTextareas($('textarea:not([name$="[data][title]"])'));

    if (editorInterface) {
      editorManager = editorManager || new Drupal.insert.EditorManager(
        editorInterface
      );

      // Aggregate classes each time the behaviour is triggered as another Insert
      // type ("image", "file"), that has not been loaded yet, might have been
      // loaded now.
      editorManager.updateClassesToRetain(aggregateClassesToRetain());
    }

    // insert.js is loaded on page load.
    if (editorInterface) {
      $(editorInterface).on('instanceReady', function(e) {
        if (editorManager) {
          editorManager.addEditor(e.editor);
        }
        focusManager.addEditor(e.editor);
      });
    }

    // insert.js is loaded asynchronously.
    $.each(editorInterface.getInstances(), function(id, editor) {
      if (editorInterface.isReady(editor)) {
        if (editorManager) {
          editorManager.addEditor(editor);
        }
        focusManager.addEditor(editor);
      }
    });

    $('.insert', context).each(function() {
      var $this = $(this);

      if (!$this.data('insert')) {
        $this.data(
          'insert',
          new Drupal.insert[$this.data('insert-type') === 'image'
              ? 'ImageInserter'
              : 'FileInserter'
            ](
              this,
              focusManager,
              editorInterface,
              drupalSettings.insert.widgets[$this.data('insert-type')]
            )
        );

        focusManager.setDefaultTarget(determineDefaultTarget($this).get(0));
      }
    });

  };

  /**
   * CKEditor removes all other classes when setting a style defined in
   * CKEditor. Since it is impossible to inject solid code into CKEditor, CSS
   * classes that should be retained are gathered for checking against those
   * actually applied to individual images.
   *
   * @return {Object}
   */
  function aggregateClassesToRetain() {
    var classesToRetain = {};

    $.each(drupalSettings.insert.classes, function(type, typeClasses) {
      classesToRetain[type] = [];

      var classesToRetainString = typeClasses.insertClass
        + ' ' + typeClasses.styleClass;

      $.each(classesToRetainString.split(' '), function() {
        classesToRetain[type].push(this.trim());
      });
    });

    return classesToRetain;
  }

  /**
   * Determines the default target objects shall be inserted in. The default
   * target is used when no text area was focused yet.
   *
   * @param {jQuery} $insert
   * @return {jQuery}
   */
  function determineDefaultTarget($insert) {
    var $commentBody = $insert
      .parents('.comment-form')
      .find('#edit-comment-body-wrapper')
      .find('textarea.text-full');

    if ($commentBody.length) {
      return $commentBody;
    }

    return $('#edit-body-wrapper').find('textarea.text-full');
  }

})(jQuery, Drupal, drupalSettings);

(function(QUnit, $, Drupal, CKEDITOR) {
  QUnit.module('FocusManager', {
    afterEach: function() {
      $.each(CKEDITOR.instances, function(id, editor) {
        var element = editor.element;
        editor.destroy();
        element.$.remove();
      });

      CKEDITOR.currentInstance = undefined;

      $('.insert-test').remove();
    }
  });

  var editorInterface = Drupal.insert.editors.interfaces.CKEditor;

  QUnit.test('Instantiation', function(assert) {
    var focusManager = new Drupal.insert.FocusManager();
    assert.ok(focusManager instanceof Drupal.insert.FocusManager, 'Instantiated FocusManager without editor interface.');

    focusManager = new Drupal.insert.FocusManager(editorInterface);
    assert.ok(focusManager instanceof Drupal.insert.FocusManager, 'Instantiated FocusManager with editor interface.');
  });

  QUnit.test('addEditor / getEditors', function(assert) {
    var focusManager = new Drupal.insert.FocusManager(editorInterface);
    var $editor = $('<div>').appendTo('body');
    var editor = CKEDITOR.replace($editor.get(0));

    focusManager.addEditor(editor);

    assert.strictEqual(focusManager.getEditors().length, 1, 'getEditors() returns one item.');
    assert.ok(focusManager.getEditors()[0] instanceof CKEDITOR.editor, 'getEditors() returns editor.');

    editor.focusManager.focus(true);

    assert.ok($(editorInterface.getElement(editor)).data('insertIsFocused'), 'insertIsFocused is true after focusing.');
    assert.ok($(editorInterface.getElement(editor)).data('insertLastFocused'), 'insertLastFocused is true after focusing.');

    // Causes error in Chrome, see https://dev.ckeditor.com/ticket/16825
    editor.focusManager.blur(true);

    assert.ok($(editorInterface.getElement(editor)).data('insertIsFocused'), 'Still, insertIsFocused is true immediately after blurring.');
    assert.ok($(editorInterface.getElement(editor)).data('insertLastFocused'), 'Still, insertLastFocused is true after blurring.');

    var done = assert.async();

    setTimeout(function() {
      assert.ok(typeof $(editorInterface.getElement(editor)).data('insertIsFocused') === 'undefined', 'insertIsFocused is undefined after blurring.');
      done();
    }, 1500);
  });

  QUnit.test('addTextareas / getTextareas', function(assert) {
    var focusManager = new Drupal.insert.FocusManager();
    var $textarea = $('<textarea>').addClass('insert-test').appendTo('body');

    focusManager.addTextareas($textarea);

    assert.strictEqual(focusManager.getTextareas().length, 1, 'getTextareas() returns one item.');
    assert.strictEqual(focusManager.getTextareas().get(0), $textarea.get(0), 'getTextareas() returns text area.');

    $textarea.focus();

    assert.ok($textarea.data('insertIsFocused'), 'insertIsFocused is true after focusing.');
    assert.ok($textarea.data('insertLastFocused'), 'insertLastFocused is true after focusing.');

    $textarea.blur();

    assert.ok($textarea.data('insertIsFocused'), 'Still, insertIsFocused is true immediately after blurring.');
    assert.ok($textarea.data('insertLastFocused'), 'Still, insertLastFocused is true after blurring.');

    var done = assert.async();

    setTimeout(function() {
      assert.ok(typeof $textarea.data('insertIsFocused') === 'undefined', 'insertIsFocused is undefined after blurring.');
      done();
    }, 1500);
  });

  QUnit.test('getActive', function(assert) {
    var focusManager = new Drupal.insert.FocusManager(editorInterface);
    var $textarea = $('<textarea>').addClass('insert-test').appendTo('body');
    var $editor = $('<div>').appendTo('body');
    var editor = CKEDITOR.replace($editor.get(0));

    focusManager.addTextareas($textarea);
    focusManager.addEditor(editor);

    assert.strictEqual(focusManager.getActive(), null, 'Returning null when no input was focused yet.');

    $textarea.focus();

    assert.strictEqual(focusManager.getActive(), $textarea.get(0), 'Returning textarea after focusing.');

    editor.focusManager.focus();

    assert.strictEqual(focusManager.getActive().id, editor.id, 'Returning editor after focusing.');

    $textarea.focus();

    assert.strictEqual(focusManager.getActive(), $textarea.get(0), 'Returning textarea after re-focusing.');

    $textarea.blur();

    assert.strictEqual(focusManager.getActive(), $textarea.get(0), 'Returning textarea after bluring.');

    var done = assert.async();

    setTimeout(function() {
      assert.strictEqual(focusManager.getActive(), $textarea.get(0), 'Returning textarea after some sleep.');
      done();
    }, 1500);
  });

  QUnit.test('setDefaultTarget', function(assert) {
    var focusManager = new Drupal.insert.FocusManager(editorInterface);
    var $textarea1 = $('<textarea>').addClass('insert-test insert-test-1').appendTo('body');
    var $textarea2 = $('<textarea>').addClass('insert-test insert-test-2').appendTo('body');

    focusManager.addTextareas($textarea1.add($textarea2));

    focusManager.setDefaultTarget($textarea1.get(0));

    assert.strictEqual(focusManager.getActive(), $textarea1.get(0), 'Returning first textarea after setting default target to first textarea.');

    $textarea1.focus();

    assert.strictEqual(focusManager.getActive(), $textarea1.get(0), 'Still returning first textarea after focusing.');

    $textarea2.focus();

    assert.strictEqual(focusManager.getActive(), $textarea2.get(0), 'Returning second textarea after focusing second textarea while first textarea is the default target.');

    $textarea1.focus();
    $textarea1.blur();
    focusManager.setDefaultTarget($textarea2.get(0));

    assert.strictEqual(focusManager.getActive(), $textarea1.get(0), 'Returning first textarea after setting second default target to second textarea, because first textarea was focused last.');
  });

})(QUnit, jQuery, Drupal, CKEDITOR);
(function(QUnit, $, CKEDITOR, Drupal) {

  QUnit.module('EditorManager', {
    afterEach: function() {
      $.each(CKEDITOR.instances, function(id, editor) {
        var element = editor.element;
        editor.destroy();
        element.$.remove();
      });

      CKEDITOR.currentInstance = undefined;

      $('textarea.insert-test').remove();
    }
  });

  QUnit.test('Instantiation', function(assert) {
    var editorInterface = new Drupal.insert.editors.CKEditor();
    var editorManager = new Drupal.insert.EditorManager(editorInterface);

    assert.ok(editorManager instanceof Drupal.insert.EditorManager, 'Instantiated EditorManager.')
  });

  QUnit.test('basic functionality', function(assert) {
    var $textarea = $('<div>').addClass('insert-test').appendTo('body');
    var editorInterface = new Drupal.insert.editors.CKEditor();
    var editorManager = new Drupal.insert.EditorManager(editorInterface);
    editorManager.updateClassesToRetain({
      'test': ['test1']
    });
    var editor = CKEDITOR.replace($textarea.get(0), {
      extraAllowedContent: 'span[data-insert-type](*)'
    });

    var done = assert.async(2);

    CKEDITOR.on('instanceReady', function(e) {
      editorManager.addEditor(e.editor);

      editor.on('change', function() {
        var $span = $(editor.document.$).find('body').find('span');

        assert.strictEqual($span.attr('data-insert-type'), 'test', 'data-insert-type attribute set: ' + editor.getData());
        assert.strictEqual($span.attr('data-insert-class'), 'test1', 'data-insert-class attribute set: ' + editor.getData());

        done();
      });

      editor.insertHtml('<span class="test1 test2" data-insert-type="test">test</span>');

      e.removeListener();

      done();
    });

  });

})(QUnit, jQuery, CKEDITOR, Drupal);
(function(QUnit, $, Drupal, CKEDITOR) {

  QUnit.module('FileInserter', {
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
  var focusManager = new Drupal.insert.FocusManager(editorInterface);

  var $dom = $('<div class="insert-test">\
    <input class="insert-style" type="hidden" value="test">\
    <input class="insert-filename" type="hidden" value="test-filename">\
    <div class="insert-templates">\
    <input class="insert-template" type="hidden" name="insert-template[test]" value="<span attr=&quot;__unused__&quot;>__filename__</span>">\
    </div>\
    <button class="insert-button"></button>\
    </div>\
    ').appendTo('body');

  QUnit.test('textarea interaction', function(assert) {
    var $textarea = $('<textarea>').addClass('insert-test').appendTo('body');
    focusManager.addTextareas($textarea);

    var fileInserter = new Drupal.insert.FileInserter($dom.get(0), focusManager);

    $textarea.focus();

    $dom.find('button').click();

    assert.strictEqual($textarea.val(), '<span>test-filename</span>', 'Verified textarea content: ' + $textarea.val())
  });


})(QUnit, jQuery, Drupal, CKEDITOR);

<?php

namespace Drupal\Tests\insert\FunctionalJavascript;

/**
 * @group insert
 */
class InsertFileWidgetTest extends InsertFileTestBase {

  public function testDefaultWidgetElement() {
    $field_name = strtolower($this->randomMachineName());
    $this->createFileField($field_name, 'article');
    $files = $this->drupalGetTestFiles('text');

    $this->drupalGet('node/add/article');
    $page = $this->getSession()->getPage();

    $page->attachFileToField(
      'files[' . $field_name . '_0]',
      \Drupal::service('file_system')->realpath($files[0]->uri)
    );

    $this->assertSession()->waitForField($field_name . '[0][fids]');

    $this->assertEquals(1, count($page->find('css', '.insert')), 'Insert container node exists');
    $this->assertEquals(1, count($page->find('css', '.insert > .insert-templates')), 'Insert templates exist');
    $this->assertEquals(1, count($page->find('css', '[name="' . $field_name . '[0][insert_template][link]"]')), 'Insert link template exists');
    $this->assertEquals(1, count($page->find('css', '.insert > input.insert-filename')), 'Insert filename input node exists');
    $this->assertEquals(1, count($page->find('css', '.insert > input.insert-style')), 'Insert style input node exists');
    $this->assertEquals('link', $page->find('css', '.insert > .insert-style')->getValue(), 'Insert style value is "link"');
    $this->assertEquals(1, count($page->find('css', '.insert input.insert-button')), 'Insert button exists');
  }

  public function testInsertStyleSelectDefault() {
    $field_name = strtolower($this->randomMachineName());
    $this->createFileField($field_name, 'article', array(), array(), array(
      'insert_styles' => array('link' => 'link', 'icon_link' => 'icon_link'),
    ));

    $files = $this->drupalGetTestFiles('text');

    $this->drupalGet('node/add/article');
    $page = $this->getSession()->getPage();

    $page->attachFileToField(
      'files[' . $field_name . '_0]',
      \Drupal::service('file_system')->realpath($files[0]->uri)
    );

    $this->assertSession()->waitForField($field_name . '[0][fids]');

    $this->assertEquals(1, count($page->find('css', '[name="' . $field_name . '[0][insert_template][link]"]')), 'Insert link template exists');
    $this->assertEquals(1, count($page->find('css', '[name="' . $field_name . '[0][insert_template][icon_link]"]')), 'Insert icon link template exists');
    $this->assertEquals(1, count($page->find('css', '.insert select.insert-style')), 'Insert style select box exists');
    $this->assertEquals(1, count($page->find('css', '.insert select.insert-style > option[value="link"]')), 'Insert style option "link" exists');
    $this->assertEquals(1, count($page->find('css', '.insert select.insert-style > option[value="icon_link"]')), 'Insert style option "icon link" exists');

    $page->findButton('Insert')->click();

    $body_value = $page->find('css', '#edit-body-0-value')->getValue();
    $this->assertEquals(1, preg_match('!<a href="[^"]+/text-0.txt" data-insert-type="file">text-0.txt</a>!', $body_value), 'Verified inserted HTML: "' . $body_value . '"');
  }

  public function testMultipleInsertOperations() {
    $field_name = strtolower($this->randomMachineName());
    $this->createFileField($field_name, 'article');

    $files = $this->drupalGetTestFiles('text');

    $this->drupalGet('node/add/article');
    $page = $this->getSession()->getPage();

    $page->attachFileToField(
      'files[' . $field_name . '_0]',
      \Drupal::service('file_system')->realpath($files[0]->uri)
    );

    $this->assertSession()->waitForField($field_name . '[0][fids]');

    $body = $page->find('css', '#edit-body-0-value');

    $page->findButton('Insert')->click();
    $this->assertEquals(1, preg_match_all('!<a [^>]+>[^<]+</a>!', $body->getValue()), 'Verified inserted HTML after inserting once: "' . $body->getValue() . '"');

    $page->findButton('Insert')->click();
    $this->assertEquals(2, preg_match_all('!<a [^>]+>[^<]+</a>!', $body->getValue()), 'Verified inserted HTML after inserting twice: "' . $body->getValue() . '"');

    $body->setValue($body->getValue() . 'insert after');
    // Simulate updated caret position:
    $this->getSession()->executeScript("var textarea = jQuery('#edit-body-0-value').get(0); textarea.selectionStart = textarea.selectionEnd = textarea.selectionStart + 'insert after'.length;");

    $page->findButton('Insert')->click();
    $this->assertEquals(1, preg_match('!^<a [^>]+>[^<]+</a><a [^>]+>[^<]+</a>insert after<a [^>]+>[^<]+</a>$!', $body->getValue()), 'Verified HTML after inserting three times: "' . $body->getValue() . '"');

    $body->setValue($body->getValue() . 'insert before');
    $page->findButton('Insert')->click();

    $this->assertEquals(1, preg_match('!^<a [^>]+>[^<]+</a><a [^>]+>[^<]+</a>insert after<a [^>]+>[^<]+</a><a [^>]+>[^<]+</a>insert before$!', $body->getValue()), 'Verified HTML after inserting four times: "' . $body->getValue() . '"');
  }

  public function testInsertStyleSelectOption() {
    $field_name = strtolower($this->randomMachineName());
    $this->createFileField($field_name, 'article', array(), array(), array(
      'insert_styles' => array('link' => 'link', 'icon_link' => 'icon_link'),
    ));

    $files = $this->drupalGetTestFiles('text');

    $this->drupalGet('node/add/article');
    $page = $this->getSession()->getPage();

    $page->attachFileToField(
      'files[' . $field_name . '_0]',
      \Drupal::service('file_system')->realpath($files[0]->uri)
    );

    $this->assertSession()->waitForField($field_name . '[0][fids]');

    $page->find('css', '.insert select.insert-style')->selectOption('icon_link');

    $page->findButton('Insert')->click();

    $body_value = $page->find('css', '#edit-body-0-value')->getValue();
    $this->assertEquals(1, preg_match('!<span class="file [^"]+" contenteditable="false" data-insert-type="file"><a href="[^"]+/text-0.txt" type="text/plain; length=1024">text-0.txt</a>!', $body_value), 'Verified inserted HTML: "' . $body_value . '"');
  }

  public function testFocus() {
    $longText_field_name = strtolower($this->randomMachineName());
    $this->createTextField($longText_field_name, 'article');

    $field_name = strtolower($this->randomMachineName());
    $this->createFileField($field_name, 'article');

    $files = $this->drupalGetTestFiles('text');

    $this->drupalGet('node/add/article');
    $page = $this->getSession()->getPage();

    $page->attachFileToField(
      'files[' . $field_name . '_0]',
      \Drupal::service('file_system')->realpath($files[0]->uri)
    );

    $this->assertSession()->waitForField($field_name . '[0][fids]');

    $page->find('css', '#edit-' . $longText_field_name . '-0-value')->focus();
    $page->findButton('Insert')->click();

    $body_value = $page->find('css', '#edit-body-0-value')->getValue();
    $long_text_value = $page->find('css', '#edit-' . $longText_field_name . '-0-value')->getValue();

    $this->assertEquals('', $body_value, 'Body is empty');
    $this->assertEquals(1, preg_match('!^<a [^>]+>text-0.txt</a>$!', $long_text_value), 'Inserted HTML into focused text area');

    $page->find('css', '#edit-body-0-value')->focus();
    $page->findButton('Insert')->click();

    $body_value = $page->find('css', '#edit-body-0-value')->getValue();
    $long_text_value = $page->find('css', '#edit-' . $longText_field_name . '-0-value')->getValue();

    $this->assertEquals(1, preg_match('!^<a [^>]+>text-0.txt</a>$!', $body_value), 'Inserted HTML into body after refocusing: ' . $body_value);
    $this->assertEquals(1, preg_match('!^<a [^>]+>text-0.txt</a>$!', $long_text_value), 'Still, second text area has HTML inserted once: ' . $body_value);
  }

  public function testAbsoluteUrlSetting() {
    $field_names = array(
      strtolower($this->randomMachineName()),
      strtolower($this->randomMachineName()),
    );
    $this->createFileField($field_names[0], 'article');
    $this->createFileField($field_names[1], 'article', array(), array(), array(
      'insert_absolute' => '1',
    ));

    $files = $this->drupalGetTestFiles('text');

    $this->drupalGet('node/add/article');
    $page = $this->getSession()->getPage();

    $page->attachFileToField(
      'files[' . $field_names[0] . '_0]',
      \Drupal::service('file_system')->realpath($files[0]->uri)
    );

    $page->attachFileToField(
      'files[' . $field_names[1] . '_0]',
      \Drupal::service('file_system')->realpath($files[0]->uri)
    );

    $this->assertSession()->waitForField($field_names[0] . '[0][fids]');
    $this->assertSession()->waitForField($field_names[1] . '[0][fids]');

    $page->find('css', '#edit-' . $field_names[0] . '-wrapper')->findButton('Insert')->click();
    $body_value = $page->find('css', '#edit-body-0-value')->getValue();
    $this->assertEquals(1, preg_match('!^<a href="/!', $body_value), 'Inserted relative URL: ' . $body_value);

    $page->find('css', '#edit-body-0-value')->setValue('');

    $page->find('css', '#edit-' . $field_names[1] . '-wrapper')->findButton('Insert')->click();
    $body_value = $page->find('css', '#edit-body-0-value')->getValue();
    $this->assertEquals(1, preg_match('!^<a href="http://!', $body_value), 'Inserted absolute URL: ' . $body_value);
  }

  public function testDescriptionField() {
    $field_name = strtolower($this->randomMachineName());
    $this->createFileField($field_name, 'article', array(), array(
      'description_field' => '1',
    ));

    $files = $this->drupalGetTestFiles('text');

    $this->drupalGet('node/add/article');
    $page = $this->getSession()->getPage();

    $page->attachFileToField(
      'files[' . $field_name . '_0]',
      \Drupal::service('file_system')->realpath($files[0]->uri)
    );

    $this->assertSession()->waitForField($field_name . '[0][fids]');

    $page->findField($field_name . '[0][description]')->setValue('test-description');

    $page->findButton('Insert')->click();

    $body_value = $page->find('css', '#edit-body-0-value')->getValue();
    $this->assertEquals(1, preg_match('!<a[^>]+ title="test-description"[^>]+>test-description</a>!', $body_value), 'Verified using description: "' . $body_value . '"');
  }

  public function testAdditionalCssClassesSetting() {
    $this->drupalGet('admin/config/content/insert');
    $page = $this->getSession()->getPage();
    $page->findField('edit-file')->setValue('test-class-1 test-class-2');

    $page->findButton('edit-submit')->click();
    $this->assertSession()->waitForElement('css', 'role[contentinfo]');

    $field_name = strtolower($this->randomMachineName());
    $this->createFileField($field_name, 'article');

    $files = $this->drupalGetTestFiles('text');

    $this->drupalGet('node/add/article');
    $page = $this->getSession()->getPage();

    $page->attachFileToField(
      'files[' . $field_name . '_0]',
      \Drupal::service('file_system')->realpath($files[0]->uri)
    );

    $this->assertSession()->waitForField($field_name . '[0][fids]');

    $page->findButton('Insert')->click();

    $body_value = $page->find('css', '#edit-body-0-value')->getValue();
    $this->assertEquals(1, preg_match('!class="test-class-1 test-class-2"!', $body_value), 'Verified configured classes: "' . $body_value . '"');
  }

}

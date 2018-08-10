<?php

namespace Drupal\Tests\insert\FunctionalJavascript;

/**
 * @group insert
 */
class InsertImageWidgetTest extends InsertImageTestBase {

  public function testDefaultWidgetElement() {
    $field_name = strtolower($this->randomMachineName());
    $this->createImageField($field_name, 'article');
    $images = $this->drupalGetTestFiles('image');

    $this->drupalGet('node/add/article');
    $page = $this->getSession()->getPage();

    $page->attachFileToField(
      'files[' . $field_name . '_0]',
      \Drupal::service('file_system')->realpath($images[0]->uri)
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

  public function testOriginalImageRotation() {
    $field_name = strtolower($this->randomMachineName());
    $this->createImageField($field_name, 'article', array(), array(
      'alt_field' => '0',
    ), array(
      'insert_default' => 'image',
      'insert_rotate' => '1',
    ));
    $images = $this->drupalGetTestFiles('image');

    $this->drupalGet('node/add/article');
    $page = $this->getSession()->getPage();

    $page->attachFileToField(
      'files[' . $field_name . '_0]',
      \Drupal::service('file_system')->realpath($images[0]->uri)
    );

    $this->assertSession()->waitForField($field_name . '[0][fids]');

    $page->findButton('Insert')->click();

    $body = $page->find('css', '#edit-body-0-value');

    $this->assertTrue(
      strpos($body->getValue(), 'width="40"') !== FALSE &&
      strpos($body->getValue(), 'height="20"') !== FALSE,
      'Verified default dimension attributes: ' . $body->getValue()
    );

    $page->findLink('↺')->click();

    $body->waitFor(20, function($element) {
      /** @var \Behat\Mink\Element\NodeElement $element */
      return strpos($element->getValue(), 'width="20"') !== FALSE;
    });

    $this->assertTrue(
      strpos($body->getValue(), 'width="20"') !== FALSE &&
      strpos($body->getValue(), 'height="40"') !== FALSE,
      'Switched dimension attribute values: ' . $body->getValue()
    );

    $page->findLink('↺')->click();

    $body->waitFor(20, function($element) {
      /** @var \Behat\Mink\Element\NodeElement $element */
      return strpos($element->getValue(), 'width="40"') !== FALSE;
    });

    $this->assertTrue(
      strpos($body->getValue(), 'width="40"') !== FALSE &&
      strpos($body->getValue(), 'height="20"') !== FALSE,
      'Switched dimension attribute values again after rotating a second time: ' . $body->getValue()
    );
  }

  public function testStyledImageRotation() {
    $field_name = strtolower($this->randomMachineName());
    $this->createImageField($field_name, 'article', array(), array(
      'alt_field' => '0',
    ), array(
      'insert_default' => 'thumbnail',
      'insert_rotate' => '1',
    ));
    $images = $this->drupalGetTestFiles('image');

    $this->drupalGet('node/add/article');
    $page = $this->getSession()->getPage();

    $page->attachFileToField(
      'files[' . $field_name . '_0]',
      \Drupal::service('file_system')->realpath($images[0]->uri)
    );

    $this->assertSession()->waitForField($field_name . '[0][fids]');

    $page->findButton('Insert')->click();

    $body = $page->find('css', '#edit-body-0-value');

    $this->assertTrue(
      strpos($body->getValue(), 'width="40"') !== FALSE &&
      strpos($body->getValue(), 'height="20"') !== FALSE,
      'Verified default dimension attributes: ' . $body->getValue()
    );

    $page->findLink('↺')->click();

    $body->waitFor(20, function($element) {
      /** @var \Behat\Mink\Element\NodeElement $element */
      return strpos($element->getValue(), 'width="20"') !== FALSE;
    });

    $this->assertTrue(
      strpos($body->getValue(), 'width="20"') !== FALSE &&
      strpos($body->getValue(), 'height="40"') !== FALSE,
      'Switched dimension attribute values: ' . $body->getValue()
    );

    $page->findLink('↺')->click();

    $body->waitFor(20, function($element) {
      /** @var \Behat\Mink\Element\NodeElement $element */
      return strpos($element->getValue(), 'width="40"') !== FALSE;
    });

    $this->assertTrue(
      strpos($body->getValue(), 'width="40"') !== FALSE &&
      strpos($body->getValue(), 'height="20"') !== FALSE,
      'Switched dimension attribute values again after rotating a second time: ' . $body->getValue()
    );
  }

  public function testAbsoluteUrlSetting() {
    $field_names = array(
      strtolower($this->randomMachineName()),
      strtolower($this->randomMachineName()),
    );
    $this->createImageField($field_names[0], 'article', array(), array(
      'alt_field' => '0',
    ), array(
      'insert_default' => 'thumbnail',
    ));
    $this->createImageField($field_names[1], 'article', array(), array(
      'alt_field' => '0',
    ), array(
      'insert_default' => 'thumbnail',
      'insert_absolute' => '1',
    ));

    $files = $this->drupalGetTestFiles('image');

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
    $this->assertEquals(1, preg_match('!^<img src="/!', $body_value), 'Inserted relative URL: ' . $body_value);

    $page->find('css', '#edit-body-0-value')->setValue('');

    $page->find('css', '#edit-' . $field_names[1] . '-wrapper')->findButton('Insert')->click();
    $body_value = $page->find('css', '#edit-body-0-value')->getValue();
    $this->assertEquals(1, preg_match('!^<img src="http://!', $body_value), 'Inserted absolute URL: ' . $body_value);
  }

  public function testRotationWithAbsoluteUrl() {
    $field_names = array(
      strtolower($this->randomMachineName()),
      strtolower($this->randomMachineName()),
    );
    $this->createImageField($field_names[0], 'article', array(), array(
      'alt_field' => '0',
    ), array(
      'insert_default' => 'image',
      'insert_absolute' => '1',
      'insert_rotate' => '1',
    ));
    $this->createImageField($field_names[1], 'article', array(), array(
      'alt_field' => '0',
    ), array(
      'insert_default' => 'thumbnail',
      'insert_absolute' => '1',
      'insert_rotate' => '1',
    ));

    $files = $this->drupalGetTestFiles('image');

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

    $body = $page->find('css', '#edit-body-0-value');

    $wrappers = array(
      $page->find('css', '#edit-' . $field_names[0] . '-wrapper'),
      $page->find('css', '#edit-' . $field_names[1] . '-wrapper'),
    );

    $wrappers[0]->findButton('Insert')->click();

    $this->assertTrue(
      strpos($body->getValue(), '<img src="http') !== FALSE,
      'Verified absolute path: ' . $body->getValue()
    );

    $this->assertTrue(
      strpos($body->getValue(), 'width="40"') !== FALSE &&
      strpos($body->getValue(), 'height="20"') !== FALSE,
      'Verified default dimension attributes: ' . $body->getValue()
    );

    $wrappers[0]->findLink('↺')->click();

    $body->waitFor(20, function($element) {
      /** @var \Behat\Mink\Element\NodeElement $element */
      return strpos($element->getValue(), 'width="20"') !== FALSE;
    });

    $this->assertTrue(
      strpos($body->getValue(), '<img src="http') !== FALSE,
      'Verified absolute path after rotating: ' . $body->getValue()
    );

    $this->assertTrue(
      strpos($body->getValue(), 'width="20"') !== FALSE &&
      strpos($body->getValue(), 'height="40"') !== FALSE,
      'Switched dimension attributes: ' . $body->getValue()
    );

    $body->setValue('');

    $wrappers[1]->findButton('Insert')->click();

    $this->assertTrue(
      strpos($body->getValue(), '<img src="http') !== FALSE,
      'Styled image - verified absolute path on: ' . $body->getValue()
    );

    $this->assertTrue(
      strpos($body->getValue(), 'width="40"') !== FALSE &&
      strpos($body->getValue(), 'height="20"') !== FALSE,
      'Styled image - verified default dimension attributes: ' . $body->getValue()
    );

    $wrappers[1]->findLink('↺')->click();

    $body->waitFor(20, function($element) {
      /** @var \Behat\Mink\Element\NodeElement $element */
      return strpos($element->getValue(), 'width="20"') !== FALSE;
    });

    $this->assertTrue(
      strpos($body->getValue(), '<img src="http') !== FALSE,
      'Styled image - verified absolute path after rotating: ' . $body->getValue()
    );

    $this->assertTrue(
      strpos($body->getValue(), 'width="20"') !== FALSE &&
      strpos($body->getValue(), 'height="40"') !== FALSE,
      'Styled image - switched dimension attributes: ' . $body->getValue()
    );
  }

  public function testImageUrlOutput() {
    $field_name = strtolower($this->randomMachineName());
    $this->createImageField($field_name, 'article', array(), array(
      'alt_field' => '0',
    ), array(
      'insert_default' => 'image',
      'insert_styles' => array('image' => 'image', 'thumbnail' => 'thumbnail'),
    ));
    $images = $this->drupalGetTestFiles('image');

    $this->drupalGet('admin/config/content/formats/manage/plain_text');
    $page = $this->getSession()->getPage();

    $page->findField('filters[filter_html_escape][status]')->uncheck();
    $page->findField('filters[editor_file_reference][status]')->check();
    $page->findButton('Save configuration')->click();

    $this->drupalGet('node/add/article');
    $page = $this->getSession()->getPage();

    $page->attachFileToField(
      'files[' . $field_name . '_0]',
      \Drupal::service('file_system')->realpath($images[0]->uri)
    );

    $this->assertSession()->waitForField($field_name . '[0][fids]');

    $page->findButton('Insert')->click();
    $page->find('css', '.insert select.insert-style')->selectOption('thumbnail');
    $page->findButton('Insert')->click();

    $page->findField('title[0][value]')->setValue('title');
    $page->findButton('Save and publish')->click();

    $page = $this->getSession()->getPage();

    $count = preg_match_all(
      '!(src="[^"]+")!',
      $page->find('css', '.field--name-body')->getHtml(),
      $matches
    );

    $this->assertEquals(2, $count, 'Verified two image being inserted in body.');

    $this->assertFalse(strpos($matches[0][0], 'thumbnail'), 'First image refers to original URL.');
    $this->assertTrue(strpos($matches[0][1], 'thumbnail') !== FALSE, 'Second image refers to style URL.');
  }

  public function testUpdatingAltAttribute() {
    $field_name = strtolower($this->randomMachineName());
    $this->createImageField($field_name, 'article', array(), array(), array(
      'insert_default' => 'image',
      'insert_styles' => array('thumbnail' => 'thumbnail'),
    ));
    $images = $this->drupalGetTestFiles('image');

    $this->drupalGet('node/add/article');
    $page = $this->getSession()->getPage();

    $page->attachFileToField(
      'files[' . $field_name . '_0]',
      \Drupal::service('file_system')->realpath($images[0]->uri)
    );

    $this->assertSession()->waitForField($field_name . '[0][fids]');

    $body = $page->findField('body[0][value]');
    $altField = $page->findField($field_name . '[0][alt]');

    $altField->setValue('initial');
    $page->findButton('Insert')->click();
    $this->assertTrue(strpos($body->getValue(), 'alt="initial"') !== FALSE, 'Verified initial string set on alt attribute: ' . $body->getValue());
    $altField->setValue('altered');
    $this->assertTrue(strpos($body->getValue(), 'alt="altered"') !== FALSE, 'Verified altered string set on alt attribute: ' . $body->getValue());
  }

  public function testUpdatingTitleAttribute() {
    $field_name = strtolower($this->randomMachineName());
    $this->createImageField($field_name, 'article', array(), array(
      'title_field' => '1',
    ), array(
      'insert_default' => 'image',
      'insert_styles' => array('thumbnail' => 'thumbnail'),
    ));
    $images = $this->drupalGetTestFiles('image');

    $this->drupalGet('node/add/article');
    $page = $this->getSession()->getPage();

    $page->attachFileToField(
      'files[' . $field_name . '_0]',
      \Drupal::service('file_system')->realpath($images[0]->uri)
    );

    $this->assertSession()->waitForField($field_name . '[0][fids]');

    $body = $page->findField('body[0][value]');
    $altField = $page->findField($field_name . '[0][alt]');
    $titleField = $page->findField($field_name . '[0][title]');

    $altField->setValue('alt');
    $titleField->setValue('initial');
    $page->findButton('Insert')->click();
    $this->assertTrue(strpos($body->getValue(), 'title="initial"') !== FALSE, 'Verified initial string set on title attribute: ' . $body->getValue());
    $titleField->setValue('altered');
    $this->assertTrue(strpos($body->getValue(), 'title="altered"') !== FALSE, 'Verified altered string set on title attribute: ' . $body->getValue());
  }

}

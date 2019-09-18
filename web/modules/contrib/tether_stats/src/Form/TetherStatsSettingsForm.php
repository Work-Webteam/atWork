<?php

namespace Drupal\tether_stats\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Database\Database;
use Drupal\Core\Database\SchemaObjectExistsException;

/**
 * Configure Tether Stats settings.
 */
class TetherStatsSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'tether_stats_admin_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {

    return [
      'tether_stats.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $config = $this->config('tether_stats.settings');

    $form['active'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Activate Stat Data Collection'),
      '#description' => $this->t('Toggles the collection of data into the stat tables. When this is false, no activity will be recorded.'),
      '#default_value' => $config->get('active'),
    ];

    $form['allow_query_string_elements'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Allow Query String To Define New Elements'),
      '#description' => $this->t('By default, when a query string is passed to a page it is ignored. Setting this variable will modify this behavior and collects stats independently if a query string present. Each unique query string would define a new element as if it were a different page. This adds granularity but makes data mining more complex.'),
      '#default_value' => $config->get('allow_query_string_elements'),
    ];

    $form['filter'] = [
      '#type' => 'details',
      '#title' => $this->t('Page Tracking Filter'),
      '#description' => $this->t('Create path filter rules to exclude or include pages you wish to track stats on.'),
      '#open' => FALSE,
    ];

    $form['filter']['exclusion_mode'] = [
      '#type' => 'radios',
      '#title' => $this->t('Use Inclusion or Exclusion Mode'),
      '#description' => $this->t('Choose to use the set of filter rules to either include or exclude matching pages for hit tracking. Inclusion mode will enforce that only urls matching one or more of the filter will be tracked. Contrarily, exclusion mode will enforce that all page urls will be tracked except those matched by the rules.'),
      '#options' => [
        'exclude' => $this->t('Exclude'),
        'include' => $this->t('Include'),
      ],
      '#default_value' => $config->get('filter.mode'),
    ];

    $filter_rules_description = $this->t('Enter a list of url paths matching rules, one per line, that will match pages you wish to include/exclude from stat tracking and element creation.') .
      '<ul><li>' . $this->t('Wildcards "%" and "#" can be used to match any one url part respectively. "%" will match any string whereas "#" will only match numeric values.') .
      '</li><li><strong>' . $this->t('Examples') .
      '</strong>:<br /> <ul><li><em>admin/config</em> - ' .
      $this->t('Will match %example_base and any page under %example_base such as %example_url',
        ['%example_base' => 'admin/config', '%example_url' => 'admin/config/system/cron']) .
      '</li><li><em>admin/%/test</em> - ' .
      $this->t('Will match %example_a and %example_b', ['%example_a' => 'admin/config/test', '%example_b' => 'admin/123/test']) .
      '</li><li><em>admin/#/test</em> - ' .
      $this->t('Will match %example_a but not %example_b', ['%example_a' => 'admin/123/test', '%example_b' => 'admin/config/test']) .
      '</li></ul></li></ul>';

    $form['filter']['rules_url'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Pages to Exclude/Include'),
      '#description' => $filter_rules_description,
      '#rows' => 5,
      '#default_value' => implode("\n", $config->get('filter.rules.url')),
    ];

    $form['filter']['rules_route'] = [
      '#type' => 'textarea',
      '#title' => t('Routes to Exclude/Include'),
      '#description' => $this->t('Advanced users may alternatively enter a list of system route names to include or exclude. The wildcard "*" character can be used in place of route name parts. If the route ends with a "*", then all routes that extend from that will be filtered. For example, tether_stats.derivatives.* will match all derivative forms.'),
      '#rows' => 5,
      '#default_value' => implode("\n", $config->get('filter.rules.route')),
    ];

    $form['advanced'] = [
      '#type' => 'details',
      '#title' => 'Advanced Configuration',
    ];

    $database_keys = array_keys(Database::getAllConnectionInfo());
    $database_options = array_combine($database_keys, $database_keys);

    unset($database_options['default']);

    $form['advanced']['use_alternate_database'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Use an alternative database for Tether Stats data?'),
      '#description' => $this->t('The database must first be registered within the Drupal settings file.'),
      '#default_value' => $config->get('database') != 'default',
      '#disabled' => empty($database_options),
    ];

    $database_ids = array_keys(Database::getAllConnectionInfo());

    $form['advanced']['database'] = [
      '#type' => 'select',
      '#title' => $this->t('Database'),
      '#description' => $this->t('This is the database to be used for stat data collection. If selected, the Tether Stats table schema will be automatically installed on the selected, alternative database when submitting this form.'),
      '#options' => $database_options,
      '#states' => [
        'visible' => [
          ':input[name="use_alternate_database"]' => ['checked' => TRUE],
        ],
      ],
      '#default_value' => $config->get('database'),
    ];

    $form['advanced']['element_ttl'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Element TTL'),
      '#description' => $this->t('The amount of time, in seconds, that an element in the database is allowed to persist before updating. This is intended to correct any elements not matching up properly with URLs because of untracked edits or other conditions.'),
      '#default_value' => $config->get('advanced.element_ttl'),
      '#element_validate' => [[get_class($this), 'validatePositiveInteger']],
      '#required' => TRUE,
    ];

    $form['advanced']['first_activation_time'] = [
      '#type' => 'textfield',
      '#title' => $this->t('First Activation Time'),
      '#description' => $this->t('The unix time stamp of when stats collection was first activation. This will be set automatically but may be manually adjusted here. This is used to determine the starting boundary for chart data iteration.'),
      '#default_value' => $config->get('advanced.first_activation_time'),
      '#element_validate' => [[get_class($this), 'validateNonNegativeInteger']],
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);

    if ($form_state->getValue('use_alternate_database')) {

      $database_id = $form_state->getValue('database', 'default');

      // Test to ensure the database identifier is valid.
      $connection = Database::getConnection('default', $database_id);

      if (!isset($connection)) {

        $form_state->setErrorByName('database', $this->t('Database identifier not found.'));
      }
      else {

        $tables_exist = TRUE;
        $schema = drupal_get_module_schema('tether_stats');

        foreach ($schema as $name => $table) {

          if (!$connection->schema()->tableExists($name)) {
            $tables_exist = FALSE;
            break;
          }
        }

        if (!$tables_exist) {

          _drupal_schema_initialize($schema, 'tether_stats', FALSE);

          try {
            foreach ($schema as $name => $table) {

              $connection->schema()->createTable($name, $table);
            }

            $this->messenger()->addWarning($this->t('Tether Stats tables were not found in the selected database and have been created.'));

          } catch (SchemaObjectExistsException $exception) {

            $form_state->setErrorByName('database', $this->t('Some, but not all, Tether Stats tables already exist in the alternate database. You may need to remove existing tables or create the missing tables manually.'));
          }

        }
      }
    }

    // Expand and trim the filter rules.
    $rules = explode("\n", $form_state->getValue('rules_url'));
    $trimmed_rules = [];

    if (!empty($rules)) {

      foreach ($rules as $rule) {

        // Trim any whitespace as well as '/'.
        $trim = trim($rule, "/ \t\n\r\0\x0B");

        if (!empty($trim)) {

          $trimmed_rules[] = $trim;
        }
      }
    }
    $form_state->setValue('rules_url', $trimmed_rules);

    $rules = explode("\n", $form_state->getValue('rules_route'));
    $trimmed_rules = [];

    if (!empty($rules)) {

      foreach ($rules as $rule) {

        $trim = trim($rule);

        if (!empty($trim)) {

          $trimmed_rules[] = $trim;
        }
      }
    }
    $form_state->setValue('rules_route', $trimmed_rules);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->config('tether_stats.settings');

    // Set the first activition time if not already set. This will
    // provide a lower boundary when iterating chart data.
    if ($form_state->getValue('active') && $config->get('advanced.first_activation_time') == 0 && $form_state->getValue('first_activation_time') == 0) {

      $form_state->setValue('first_activation_time', REQUEST_TIME);
    }

    if ($form_state->getValue('use_alternate_database')) {

      $config->set('database', $form_state->getValue('database'));
    }
    else {

      $config->set('database', 'default');
    }

    $config
      ->set('active', $form_state->getValue('active'))
      ->set('filter.mode', $form_state->getValue('exclusion_mode'))
      ->set('filter.rules.url', $form_state->getValue('rules_url'))
      ->set('filter.rules.route', $form_state->getValue('rules_route'))
      ->set('allow_query_string_elements', $form_state->getValue('allow_query_string_elements'))
      ->set('advanced.element_ttl', $form_state->getValue('element_ttl'))
      ->set('advanced.first_activation_time', $form_state->getValue('first_activation_time'))
      ->save();

    parent::submitForm($form, $form_state);
  }

  /**
   * Element callback for validating a positive integer.
   *
   * Called using #element_validate.
   *
   * @param array $element
   *   An associative array containing the properties and children of the
   *   generic form element.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The $form_state array for the form this element belongs to.
   *
   * @see form_process_pattern()
   */
  public static function validatePositiveInteger(array $element, FormStateInterface $form_state) {

    $value = $element['#value'];

    if ($value != '' && (!is_numeric($element['#value']) || intval($value) != $value || $value <= 0)) {
      $form_state->setError($element, t('%name must be a positive integer.', ['%name' => $element['#title']]));
    }
  }

  /**
   * Element callback for validating a non-negative integer.
   *
   * Called using #element_validate.
   *
   * @param array $element
   *   An associative array containing the properties and children of the
   *   generic form element.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The $form_state array for the form this element belongs to.
   *
   * @see form_process_pattern()
   */
  public static function validateNonNegativeInteger(array $element, FormStateInterface $form_state) {

    $value = $element['#value'];

    if ($value != '' && (!is_numeric($element['#value']) || intval($value) != $value || $value < 0)) {
      $form_state->setError($element, t('%name must be a non-negative integer.', ['%name' => $element['#title']]));
    }
  }

}

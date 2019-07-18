<?php

namespace Drupal\term_csv_export_import\Controller;

use Drupal\Core\Database\Database;
use Drupal\taxonomy\Entity\Term;
use Drupal\Core\Cache\Cache;

/**
 * Class ImportController.
 */
class ImportController {
  protected  $data = [];
  protected  $vocabulary;

  /**
   * {@inheritdoc}
   */
  public function __construct($data, $vocabulary) {
    $this->vocabulary = $vocabulary;
    $temp = fopen('php://memory', 'rw');
    fwrite($temp, $data);
    rewind($temp);
    $csvArray = [];
    while (!feof($temp)) {
      if ($csvRow = fgetcsv($temp)) {
        $csvArray[] = $csvRow;
      }
    }
    fclose($temp);
    $keys_noid = ['name', 'status', 'description__value', 'description__format', 'weight', 'parent_name'];
    $keys_id = [
      'tid',
      'uuid',
      'name',
      'status',
      'description__value',
      'description__format',
      'weight',
      'parent_name',
      'parent_tid',
    ];
    $keys = [];
    if (!array_diff($keys_noid, $csvArray[0])) {
      drupal_set_message(t('The header keys were not included in the import.'), 'warning');
      $keys = $csvArray[0];
      unset($csvArray[0]);
    }
    foreach ($csvArray as $csvLine) {
      $num_of_lines = count($csvLine);
      if (empty($keys)) {
        if (in_array($num_of_lines, [9, 10])) {
          $keys = $keys_id;
        }
        elseif (in_array($num_of_lines, [6, 7])) {
          $keys = $keys_noid;
        }
        else {
          drupal_set_message(t('Line with "@part" could not be parsed. Incorrect number of values: @count.',
            [
              '@part' => implode(',', $csvLine),
              '@count' => count($csvLine),
            ]), 'error');
          continue;
        }
        if (in_array($num_of_lines, [7, 10])) {
          $keys[] = 'fields';
        }
      }
      $this->data[] = array_combine($keys, $csvLine);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function execute($preserve_vocabularies) {
    // We need to invalidate caches to pull direct from db.
    Cache::invalidateTags(array(
      'taxonomy_term_values',
    ));
    $processed = 0;
    // TODO Inject.
    $langcode = \Drupal::languageManager()->getCurrentLanguage()->getId();
    foreach ($this->data as $row) {
      //remove whitespace
      foreach ($row as $key => $value) {
        $row[$key] = trim($value);
      }
      // Check for existence of terms.
      if (isset($row['tid'])) {
        $term_existing = Term::load($row['tid']);
      }
      else {
        $term_existing = taxonomy_term_load_multiple_by_name($row['name'], $this->vocabulary);
        if (count($term_existing) > 1) {
          drupal_set_message(t('The term @name has multiple matches. Ignoring.', ['@name' => $row['name']]));
          continue;
        }
        else {
          $term_existing = $term_existing[0];
        }
      }
      if ($term_existing) {
        $new_term = $term_existing;
      }
      // Or create the term.
      elseif (isset($row['tid'])) {
        // Double check for Term ID cause this could go bad.
        $db = Database::getConnection();
        $query = $db->select('taxonomy_term_data')
          ->fields('taxonomy_term_data', ['tid'])
          ->condition('taxonomy_term_data.tid', $row['tid'], '=');
        $tids = $query->execute()->fetchAll(\PDO::FETCH_OBJ);
        $query1 = $db->select('taxonomy_term_field_data')
          ->fields('taxonomy_term_field_data', ['tid'])
          ->condition('taxonomy_term_field_data.tid', $row['tid'], '=');
        $tids1 = $query1->execute()->fetchAll(\PDO::FETCH_OBJ);
        if (!empty($tids) || !empty($tids1)) {
          drupal_set_message(t('The Term ID already exists.'), 'error');
          continue;
        }
        $db->insert('taxonomy_term_data')
          ->fields([
            'tid' => $row['tid'],
            'vid' => $this->vocabulary,
            'uuid' => $row['uuid'],
            'langcode' => $langcode,
          ])
          ->execute();
        $db->insert('taxonomy_term_field_data')
          ->fields([
            'tid' => $row['tid'],
            'vid' => $this->vocabulary,
            'status' => $row['status'],
            'name' => $row['name'],
            'langcode' => $langcode,
            'default_langcode' => 1,
            'weight' => $row['weight'],
          ])
          ->execute();
        $new_term = Term::load($row['tid']);
      }
      else {
        $new_term = Term::create(['name' => $row['name'], 'vid' => $this->vocabulary, 'status' => $row['status'], 'langcode' => $langcode]);
      }
      // Change the vocabulary if requested.
      if ($new_term->getVocabularyId() != $this->vocabulary && !$preserve_vocabularies) {
        // TODO: Make this work. 
        // $new_term->vid->setValue($this->vocabulary);
        // Currently get an EntityStorageException when field does not exist in new vocab.
        // TODO: Save the term so fields are set properly when above todo done.
        // $new_term->save();
        // So, we update the db instead.
        $tid = $new_term->id();
        $db = Database::getConnection();
        $db->update('taxonomy_term_data')
          ->fields(['vid' => $this->vocabulary])
          ->condition('tid', $tid, '=')
          ->execute();
        $db->update('taxonomy_term_field_data')
          ->fields(['vid' => $this->vocabulary])
          ->condition('tid', $tid, '=')
          ->execute();
        \Drupal::entityTypeManager()->getStorage('taxonomy_term')->resetCache([$tid]);
        $new_term = Term::load($tid);
      }
      // Set temp parents.
      $parent_terms = NULL;
      if (!empty($row['parent_tid'])) {
        if (strpos($row['parent_tid'],';') !== FALSE) {
          $parent_tids =  array_filter(explode(';',$row['parent_tid']), 'strlen');
          foreach ($parent_tids as $parent_tid) {
            $parent_terms[] = Term::load($parent_tid);
          }
        }
        else {
          $parent_terms[] = Term::load($row['parent_tid']);
        }
      }
      $new_term->setDescription($row['description__value'])
        ->setName($row['name'])
        ->set('status', $row['status'])
        ->set('langcode', $langcode)
        ->setFormat($row['description__format'])
        ->setWeight($row['weight']);
      // Check for parents.
      if ($parent_terms == NULL && !empty($row['parent_name'])) {
        $parent_names = explode(';',$row['parent_name']);
        foreach ($parent_names as $parent_name) {
          $parent_term = taxonomy_term_load_multiple_by_name($parent_name, $this->vocabulary);
          if (count($parent_term) > 1) {
            unset($parent_term);
            drupal_set_message(t('More than 1 terms are named @name. Cannot distinguish by name. Try using id export/import.', ['@name' => $row['parent_name']]), 'error');
          }
          else {
            $parent_terms[] = array_values($parent_term)[0];
          }
        }
      }
      if ($parent_terms) {
        $parent_terms = array_filter($parent_terms);
        $parent_tids = [];
        foreach ($parent_terms as $parent_term) {
          $parent_tids[] = $parent_term->id();
        }
        $new_term->parent = $parent_tids;
      }
      else {
        $new_term->parent = 0;
      }

      // Import all other non-default taxonomy fields if the row is there.
      if (isset($row['fields']) && !empty($row['fields'])) {
        parse_str($row['fields'], $field_array);
        if (!is_array($field_array)) {
          drupal_set_message(t('The field data <em>@data</em> is not formatted correctly. Please use the export function.', ['@data' => $row['fields']]), 'error');
          continue;
        }
        else {
          foreach ($field_array as $field_name => $field_values) {
            if ($new_term->hasField($field_name)) {
              $new_term->set($field_name, $field_values);
            }
            else {
              drupal_set_message(t('The field data <em>@data</em> could not be imported. Please add the appropriate fields to the vocabulary you are importing into.', ['@data' => $row['fields']]), 'warning');
            }
          }
        }
      }

      $new_term->save();
      $processed++;
    }
    drupal_set_message(t('Imported @count terms.', ['@count' => $processed]));
  }

}

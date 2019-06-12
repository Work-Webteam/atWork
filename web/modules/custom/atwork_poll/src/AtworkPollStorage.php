<?php

namespace Drupal\atwork_poll;

use Exception;

/**
 * Controller class for polls.
 *
 * This extends the default content entity storage class,
 * adding required special handling for poll entities.
 */
class AtworkPollStorage {

  /**
   * {@inheritdoc}
   */
  public function getPollsToPublish() {
    $connection = \Drupal::database();
    $result = NULL;
    try {
      $query = $connection->query("SELECT poll_field_data.id FROM {poll_field_data} JOIN {poll__field_poll_publishing_date} ON poll_field_data.id = poll__field_poll_publishing_date.entity_id WHERE UNIX_TIMESTAMP(poll__field_poll_publishing_date.field_poll_publishing_date_value) <  UNIX_TIMESTAMP() AND (((poll_field_data.created + poll_field_data.runtime) < UNIX_TIMESTAMP() AND runtime <> 0 ) OR runtime = 0) AND status = 0");
      $result = \Drupal::entityTypeManager()->getStorage('poll')->loadMultiple($query->fetchCol());
    }
    catch (Exception $e) {
      \Drupal::logger('type')->error($e->getMessage());
    }
    return $result;
  }

}

<?php
/**
 * @file
 * Contains \Drupal\hello_world\Controller\HelloController.
 */
namespace Drupal\atwork_idir_update\Controller;
use Drupal\atwork_idir_update\AtworkIdirUpdateLogSplit;
class AtworkIdirUpdateController {
  public function content() {
    // Secondary way to run the idir script for testing - or if cron hook does not fire or errors for some reason.
    $run_cron = $this->AtworkIdirInit();
    return array(
      '#type' => 'markup',
      '#markup' => t('<p>Running Cron ' . $run_cron . '.</p>'),
    );
  }

  public function AtworkIdirInit()
  {
    $file_handle = new AtworkIdirUpdateLogSplit;
    $filename = 'idir_' . $file_handle->getTimestamp() . '.tsv';

    try
    {
      $full_list = fopen($file_handle->getDrupalPath() . '/idir/' . $filename, 'rb');
      // Check if the file was opened properly.
      if( !$full_list )
      {
        // TODO: Eventually this should be updated to reflect this exact Exception (FileNotFoundException extends Exeption)
        throw new \exception("Failed to open file at atwork_idir_update/idir/" . $filename . '. Script was terminated in Controller.');
      } 
    }
    catch ( Exception $e ) 
    {
      // Generic exception handling if something else gets thrown.
      \Drupal::logger('AtworkIdirUpdate')->error($e->getMessage());
      // And log it as well
      AtworkIdirLog::errorCollect($e);
      AtworkIdirLog::notify();
      die();
    }
    // If we get here - we have a file to split - so lets move on.
    try
    {
      $check = $file_handle->splitFile();
      if( !$check )
      {
        throw new \exception("");
      }
    }
    catch ( Exception $e )
    {

    }
  }
}
<?php

namespace Drupal\atwork_idir_update;

use Drupal\Core\FileTransfer\FTPExtension;

/**
 * Class AtworkIdirUpdateFTP.
 *
 * Takes care of FTP functionality for the atwork_idir module.
 *
 * @package Drupal\atwork_idir_update
 */
class AtworkIdirUpdateFTP extends FTPExtension {

  /**
   * Connect to our ftp target.
   *
   * {@inheritdoc}
   *   Extends FTPExtension::connect
   *   we need ftp_ssl_connect and not only ftp_connect.
   *
   * @Throws \exeption
   *  This stops execution of the connection and logs error.
   */
  public function connect() {
    $this->connection = ftp_ssl_connect($this->hostname, $this->port);
    if (!$this->connection) {
      throw new \exception("Cannot connect to FTP Server, check settings");
    }
    if (!ftp_login($this->connection, $this->username, $this->password)) {
      throw new \exception("Cannot log in to FTP server. Check username and password");
    }
    // If we have no exceptions, then return true.
    return TRUE;
  }

  /**
   * Function to transfer .csv to jail.
   *
   * @param string $timestamp
   *   A unique way to mark the file.
   * @param string $filename
   *   The name of the file we are looking for.
   *
   * @return bool
   *   Return boolean on success of transfer.
   */
  final public function ftpFile($timestamp, $filename) {
    ftp_pasv($this->connection, TRUE);
    $transfer_result = ftp_get($this->connection, $this->jail . 'idir/' . $timestamp . '/idir_' . $timestamp . ".tsv", $filename, FTP_BINARY);
    return $transfer_result;
  }

  /**
   * Creates a directory for the file by date.
   *
   * @param string $timestamp
   *   Timestamp to mark out directory/filenames.
   *
   * @return bool
   *   Returns success/fail state of created directory.
   *
   * @throws \exception
   *   Catches and logs any errors.
   */
  final public function createIdirDir($timestamp) {
    $new_dir = 'public://idir/' . $timestamp;
    try {
      $dir = file_prepare_directory($new_dir, FILE_CREATE_DIRECTORY);
      if (!$dir) {
        throw new \exception("Could not create a directory for the file.");
      }
    }
    catch (Exception $e) {
      // Generic exception handling if something else gets thrown.
      \Drupal::logger('AtworkIdirUpdate')->error($e->getMessage());
      // And log it as well.
      AtworkIdirLog::errorCollect($e);
    }
    return $dir;
  }

}

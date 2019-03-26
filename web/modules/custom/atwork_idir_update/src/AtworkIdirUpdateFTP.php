<?php
namespace Drupal\atwork_idir_update;
use Drupal\Core\FileTransfer\FTPExtension;

/**
 * Extends FTP/Filetransfer classes, to give us FTP functionality
 */
class AtworkIdirUpdateFTP extends FTPExtension  
{

   /**
   * {@inheritdoc}
   * Overwrites FTPExtension::connect because we need ftp_ssl_connect and not only ftp_connect
   */
  public function connect() {
    $this->connection = ftp_ssl_connect($this->hostname, $this->port);
    if (!$this->connection) {
      throw new \exception("Cannot connect to FTP Server, check settings");
      return false;
    }
    if (!ftp_login($this->connection, $this->username, $this->password)) {
      throw new \exception("Cannot log in to FTP server. Check username and password");
      return false;
    }
    // if we have not exceptions, then return true.
    return true;
  }


  /**
   * @date $timestamp - timestamp to mark out directory/filenames
   * @resource $transfer_result - variable used to collect .tsv file if available.
   *
   * @return Resource - Returns the idir file if we are able to collect it form the directory
   */
  public final function ftpFile($timestamp, $filename)
  {
    ftp_pasv($this->connection, true);
    $transfer_result = ftp_get( $this->connection, $this->jail . 'idir/' . $timestamp . '/idir_' . $timestamp . ".tsv", $filename, FTP_BINARY);
    return $transfer_result;
  }

  /**
   * @date $timestamp - timestamp to mark out directory/filenames
   *
   * @return bool - the result of preparing a directory- if we cannot this returns false and throws and error.
   * @throws \exception
   */
  public final function create_idir_dir($timestamp)
  {
    $new_dir = 'public://idir/' . $timestamp;
    try
    {
      $dir = file_prepare_directory( $new_dir, FILE_CREATE_DIRECTORY );
      if( !$dir )
      {
        throw new \exception( "Could not create a directory for the file." );
      }
    }
    catch( Exception $e )
    {
      // Generic exception handling if something else gets thrown.
      \Drupal::logger('AtworkIdirUpdate')->error($e->getMessage());
      // And log it as well
      AtworkIdirLog::errorCollect($e);
    }
    return $dir;
  }
}

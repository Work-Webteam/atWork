<?php
namespace Drupal\atwork_idir_update;
use Drupal\Core\FileTransfer\FTPExtension;
use Drupal\Core\FileTransfer\SSH;

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
      throw new FileTransferException("Cannot connect to FTP Server, check settings");
      return false;
    }
    if (!ftp_login($this->connection, $this->username, $this->password)) {
      throw new FileTransferException("Cannot log in to FTP server. Check username and password");
      return false;
    }
    // if we have not exceptions, then return true.
    return true;
  }
 
 
  
  public final function ftpFile($timestamp, $connection)
  {
    ftp_pasv($this->connection, true);
    $transfer_result = ftp_get( $this->connection, $this->jail . 'idir/' . $timestamp . '/idir_' . $timestamp . ".tsv", "idir.tsv", FTP_BINARY);
    return $transfer_result;
  }

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

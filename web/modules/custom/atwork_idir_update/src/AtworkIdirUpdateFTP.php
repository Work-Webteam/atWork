<?php
namespace Drupal\atwork_idir_update;
use Drupal\Core\FileTransfer\FTPExtension;

/**
 * Extends FTP/Filetransfer classes, to give us FTP functionality
 */
class AtworkIdirUpdateFTP extends FTPExtension 
{

  public final function getFTPFile($timestamp)
  {
    //create dir function
    $file = $this->copyFileJailed("idir.tsv", $this->jail . "/" . 'idir_' . $timestamp . ".tsv");
    //$dir = $this->create_idir_dir($timestamp);
    // if dir exists, then ftpFile($timestamp)
    return $file;
  }
  /*
  private final function ftpFile($timestamp)
  {

    return true;
  }

  private final function create_idir_dir($timestamp)
  {
    $new_dir = 'public://idir/' . $timestamp;
    $dir = $this->createDirectoryJailed($new_dir);
    return $dir;
  }
  */
}

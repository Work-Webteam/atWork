<?php

namespace Drupal\photos;

use Drupal\Component\Utility\Html;
use Drupal\Component\Utility\Unicode;
use Drupal\Core\Cache\Cache;
use Drupal\file\Entity\File;

/**
 * Functions to help with uploading images to albums.
 */
class PhotosUpload {

  /**
   * Rename file with random name.
   */
  public static function rename($name = 0, $ext = 0) {
    // @todo use transliterate? Or add more options.
    if (\Drupal::config('photos.settings')->get('photos_rname')) {
      if ($name) {
        $name_parts = explode('.', $name);
        return round(rand(15770, 967049700)) . REQUEST_TIME . '.' . ($ext ? $ext : end($name_parts));
      }
      if (!empty($_FILES['files'])) {
        foreach ($_FILES['files']['name'] as $field => $filename) {
          $filename_parts = explode('.', $filename);
          $_FILES['files']['name'][$field] = round(rand(15770, 967049700)) . REQUEST_TIME . '.' . ($ext ? $ext : end($filename_parts));
        }
      }
    }
    elseif ($name) {
      return $name;
    }
  }

  /**
   * Write files.
   */
  public static function saveFile($file, $val = []) {
    $errors = [];
    foreach ($val as $function => $args) {
      array_unshift($args, $file);
      $errors = array_merge($errors, call_user_func_array($function, $args));
    }
    if (!empty($errors)) {
      $message = t('The selected file %name could not be uploaded.', ['%name' => $file->filename]);
      if (count($errors) > 1) {
        $message .= '<ul><li>' . implode('</li><li>', $errors) . '</li></ul>';
      }
      else {
        $message .= ' ' . array_pop($errors);
      }
      drupal_set_message($message);
      return 0;
    }
    $file->save();

    return $file->id();
  }

  /**
   * Image written to database.
   */
  public static function saveImage($file) {
    // @todo re-write.
    // @todo check title, description, weight.
    //   - maybe pass file object and array of other vars.
    $exif = ($file->getMimeType() == 'image/jpeg') ? 1 : 0;
    if ($file->id() && isset($file->pid)) {
      $fid = $file->id();
      $pid = $file->pid;
      $db = \Drupal::database();
      $db->merge('photos_image')
        ->key([
          'fid' => $file->id(),
        ])
        ->fields([
          'pid' => $file->pid,
          'title' => isset($file->title) ? $file->title : $file->getFilename(),
          'des' => isset($file->des) ? $file->des : '',
          'wid' => isset($file->wid) ? $file->wid : 0,
          'comcount' => 0,
          'count' => 0,
          'exif' => $exif,
        ])
        ->execute();
      if (isset($fid) && !empty($fid)) {
        if (isset($file->nid)) {
          $db->insert('photos_node')
            ->fields([
              'nid' => $file->nid,
              'fid' => $file->id(),
            ])
            ->execute();
        }
        if (\Drupal::config('photos.settings')->get('photos_user_count_cron')) {
          $user = \Drupal::currentUser();
          PhotosAlbum::setCount('user_image', ($file->getOwnerId ? $file->getOwnerId : $user->id()));
          PhotosAlbum::setCount('node_album', $file->pid);
          if (isset($file->nid)) {
            PhotosAlbum::setCount('node_node', $file->nid);
          }
        }
        // Save file and add file usage.
        $file_usage = \Drupal::service('file.usage');
        $file_usage->add($file, 'photos', 'node', $pid);
        // Check admin setting for maximum image resolution.
        if ($photos_size_max = \Drupal::config('photos.settings')->get('photos_size_max')) {
          // Will scale image if needed.
          file_validate_image_resolution($file, $photos_size_max);
        }
        return TRUE;
      }
    }
    return FALSE;
  }

  /**
   * Prepare photos custom directory structure.
   */
  public static function path($type = 'default', $file = FALSE, $account = FALSE) {
    if (!$account) {
      $user = \Drupal::currentUser();
      $account = $user;
    }
    $path = [];
    if (\Drupal::config('photos.settings')->get('photos_path')) {
      $mm = \Drupal::service('date.formatter')->format(REQUEST_TIME, 'custom', "Y|m|d");
      $m = explode('|', $mm);
      $a = [
        '%uid' => $account->id(),
        '%username' => $account->getUsername(),
        '%Y' => $m[0],
        '%m' => $m[1],
        '%d' => $m[2],
      ];
      $b = strtr(\Drupal::config('photos.settings')->get('photos_path'), $a);
      $path = explode('/', $b);
    }
    else {
      $path[] = 'photos';
    }
    switch ($type) {
      case 'default':
        $scheme = file_default_scheme();
        break;

      case 'private':
        $scheme = 'private';
        break;

      case 'public':
        $scheme = 'public';
        break;
    }
    $dirs = [];
    foreach ($path as $folder) {
      $dirs[] = $folder;
      $t = $scheme . '://' . implode('/', $dirs);
      if (!file_prepare_directory($t, FILE_CREATE_DIRECTORY)) {
        return FALSE;
      }
    }

    return $t;
  }

  /**
   * Unzip archive of image files.
   */
  public static function unzip($source, $value, $scheme = 'default', $account = FALSE) {
    $file_count = 0;
    if (version_compare(PHP_VERSION, '5') >= 0) {
      if (!is_file($source)) {
        drupal_set_message(t('Compressed file does not exist, please check the path: @src', ['@src' => $source]));
        return 0;
      }
      $type = ['jpg', 'gif', 'png', 'jpeg', 'JPG', 'GIF', 'PNG', 'JPEG'];
      $zip = new \ZipArchive();
      // Get relative path.
      $relative_path = \Drupal::service('file_system')->realpath(file_default_scheme() . "://") . '/';
      $source = str_replace(file_default_scheme() . '://', $relative_path, $source);
      // Open zip archive.
      if ($zip->open($source) === TRUE) {
        for ($x = 0; $x < $zip->numFiles; ++$x) {
          $image = $zip->statIndex($x);
          $filename_parts = explode('.', $image['name']);
          $ext = end($filename_parts);
          if (in_array($ext, $type)) {
            $path = file_create_filename(PhotosUpload::rename($image['name'], $ext), PhotosUpload::path($scheme, '', $account));
            if ($temp_file = file_save_data($zip->getFromIndex($x), $path)) {
              // Update file values.
              $temp_file->pid = $value->pid;
              $temp_file->nid = $value->nid;
              // Use image file name as title.
              $temp_file->title = $image['name'];
              $temp_file->des = $value->des;
              // Prepare file entity.
              $file = $temp_file;
              // Save image.
              if (PhotosUpload::saveFile($file)) {
                if (PhotosUpload::saveImage($file)) {
                  $file_count++;
                }
              }
            }
          }
        }
        $zip->close();
        // Delete zip file.
        file_unmanaged_delete($source);
      }
      else {
        drupal_set_message(t('Compressed file does not exist, please try again: @src', ['@src' => $source]), 'warning');
      }
    }

    return $file_count;
  }

  /**
   * Assist batch operation by moving or copying image files to album.
   */
  public static function moveImageFiles($files, $account, $nid, $scheme, $allow_zip, $file_extensions, $copy, &$context) {
    if (empty($context['sandbox'])) {
      $context['sandbox']['progress'] = 0;
      $context['sandbox']['current_id'] = 0;
      $context['sandbox']['max'] = count($files);
      $context['results']['images_processed'] = 0;
      $context['results']['nid'] = $nid;
      $context['results']['uid'] = $account->id();
      $context['results']['copy'] = $copy;
    }
    $limit = 20;

    $process_files = array_slice($files, $context['sandbox']['current_id'], $limit);

    $count = 0;
    foreach ($process_files as $dir_file) {
      $ext = Unicode::substr($dir_file->uri, -3);
      if ($ext <> 'zip' && $ext <> 'ZIP') {
        // Prepare directory.
        $photos_path = PhotosUpload::path($scheme, '', $account);
        $photos_name = PhotosUpload::rename($dir_file->filename);
        $file_uri = file_destination($photos_path . '/' . $photos_name, FILE_EXISTS_RENAME);
        // Display current file name.
        $context['message'] = t('Processing:') . ' ' . Html::escape($photos_name);
        if ($copy) {
          $file_processed = file_unmanaged_copy($dir_file->uri, $file_uri);
        }
        else {
          $file_processed = file_unmanaged_move($dir_file->uri, $file_uri);
        }
        if ($file_processed) {
          // Save file to album. Include title and description.
          $image = \Drupal::service('image.factory')->get($file_uri);
          if ($image->getWidth()) {
            // Create a file entity.
            $file = File::create([
              'uri' => $file_uri,
              'uid' => $account->id(),
              'status' => FILE_STATUS_PERMANENT,
              'pid' => $nid,
              'nid' => $nid,
              'filename' => $photos_name,
              'filesize' => $image->getFileSize(),
              'filemime' => $image->getMimeType(),
            ]);

            if (PhotosUpload::saveFile($file)) {
              PhotosUpload::saveImage($file);
              $count++;
            }
          }
        }
      }
      else {
        // Process zip file.
        if (!\Drupal::config('photos.settings')->get('photos_upzip')) {
          drupal_set_message(t('Please update settings to allow zip uploads.'), 'error');
        }
        else {
          $directory = PhotosUpload::path();
          file_prepare_directory($directory);
          // Display current file name.
          $context['message'] = t('Processing:') . ' ' . Html::escape($dir_file->uri);
          $zip = file_destination($directory . '/' . trim(basename($dir_file->uri)), FILE_EXISTS_RENAME);
          // @todo large zip files could fail here.
          if ($copy) {
            $file_processed = file_unmanaged_copy($dir_file->uri, $zip);
          }
          else {
            $file_processed = file_unmanaged_move($dir_file->uri, $zip);
          }
          if ($file_processed) {
            $value = new \stdClass();
            $value->pid = $nid;
            $value->nid = $nid;
            $value->des = '';
            $value->title = $dir_file->filename;
            if (!$file_count = PhotosUpload::unzip($zip, $value, $scheme, $account)) {
              // Upload failed.
            }
            else {
              $count = $count + $file_count;
            }
          }
        }
      }
      // Update progress.
      $context['sandbox']['progress']++;
      $context['sandbox']['current_id']++;
    }
    $context['results']['images_processed'] += $count;
    // Check if complete.
    if ($context['sandbox']['progress'] != $context['sandbox']['max']) {
      $context['finished'] = $context['sandbox']['progress'] / $context['sandbox']['max'];
    }
  }

  /**
   * Finished batch operation moving image files.
   */
  public static function finishedMovingImageFiles($success, $results, $operations) {
    // Clear node and album page cache.
    Cache::invalidateTags(['node:' . $results['nid'], 'photos:album:' . $results['nid']]);
    // Update count.
    PhotosAlbum::setCount('user_image', $results['uid']);
    PhotosAlbum::setCount('node_album', $results['nid']);
    if ($success) {
      if ($results['copy']) {
        $message = \Drupal::translation()->formatPlural($results['images_processed'], 'One image copied to selected album.', '@count images copied to selected album.');
      }
      else {
        $message = \Drupal::translation()->formatPlural($results['images_processed'], 'One image moved to selected album.', '@count images moved to selected album.');
      }
    }
    else {
      $message = t('Finished with an error.');
    }
    drupal_set_message($message);
  }

}

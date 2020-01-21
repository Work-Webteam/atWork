<?php

namespace Drupal\media_gallery;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\user\EntityOwnerInterface;
use Drupal\Core\Entity\EntityChangedInterface;

/**
 * Provides an interface defining a media gallery entity type.
 */
interface MediaGalleryInterface extends ContentEntityInterface, EntityOwnerInterface, EntityChangedInterface {

  /**
   * Gets the media gallery title.
   *
   * @return string
   *   Title of the media gallery.
   */
  public function getTitle();

  /**
   * Sets the media gallery title.
   *
   * @param string $title
   *   The media gallery title.
   *
   * @return \Drupal\media_gallery\MediaGalleryInterface
   *   The called media gallery entity.
   */
  public function setTitle($title);

  /**
   * Gets the media gallery creation timestamp.
   *
   * @return int
   *   Creation timestamp of the media gallery.
   */
  public function getCreatedTime();

  /**
   * Sets the media gallery creation timestamp.
   *
   * @param int $timestamp
   *   The media gallery creation timestamp.
   *
   * @return \Drupal\media_gallery\MediaGalleryInterface
   *   The called media gallery entity.
   */
  public function setCreatedTime($timestamp);

  /**
   * Returns the media gallery status.
   *
   * @return bool
   *   TRUE if the media gallery is enabled, FALSE otherwise.
   */
  public function isEnabled();

  /**
   * Sets the media gallery status.
   *
   * @param bool $status
   *   TRUE to enable this media gallery, FALSE to disable.
   *
   * @return \Drupal\media_gallery\MediaGalleryInterface
   *   The called media gallery entity.
   */
  public function setStatus($status);

}

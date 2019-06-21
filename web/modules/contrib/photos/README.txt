CONTENTS OF THIS FILE
---------------------

 * Introduction
 * Features
 * Requirements
 * Recommended Modules
 * Installation
 * Configuration
 * Maintainers


INTRODUCTION
------------

The Album Photos module provides a solution for creating photo albums and
uploading multiple images. The module automatically creates the photos content
type which creates a node that contains all the photos (saved as managed files).

The Album Photos module comes with the Photo Access sub-module that provides
settings for each album including open, locked, designated users, or password
required.

 * For a full description of the module visit:
   https://www.drupal.org/project/photos or
   https://www.drupal.org/node/2896620

 * To submit bug reports and feature suggestions, or to track changes visit:
   https://www.drupal.org/project/issues/photos

FEATURES
--------

 * Create photo galleries.
 * Upload and manage images.
 * Upload multiple images with Plupload.
 * Comment on images (@todo).
 * Vote on images (@todo).
 * Integrates with core image styles.
 * Support for Drupal core private file system.


REQUIREMENTS
------------

This module requires no modules outside of Drupal core.


RECOMMENDED MODULES
-------------------

For the best experience install and enable the following modules:
 * Crop API - https://www.drupal.org/project/crop
 * Image Widget Crop - https://www.drupal.org/project/image_widget_crop
 * Image Effects - https://www.drupal.org/project/image_effects
 * Colorbox - https://www.drupal.org/project/colorbox

For multi image upload:
 * Plupload integration - https://www.drupal.org/project/plupload

For auto fix image orientation:
 * EXIF Orientation - https://www.drupal.org/project/exif_orientation


INSTALLATION
------------

Install the Album Photos module as you would normally install a contributed
Drupal module. Visit https://www.drupal.org/node/1897420 for further
information.


CONFIGURATION
-------------

    1. Navigate to Administration > Extend and enable the module. Upon enabling,
       the module creates a "Photos" content type.
    2. Navigate to Administration > Configuration > Media > Photos to edit
       global settings: Basic Settings, Image Sizes, Display Settings,
       Statistics, and Exif Settings.
    3. To edit the Privacy settings, the Photos Access submodule must be
       enabled.
    4. Navigate to Administration > People > Permissions to edit user
       permissions.

For plupload integration.
    1. Install plupload module.
    2. Install plupload library.
    3. Enable plupload setting in photos global settings.
    4. Clear cache.

For cropping:
    1. Install Crop API: https://www.drupal.org/project/crop
    2. Install Image Widget Cropper:
       https://www.drupal.org/project/image_widget_crop
    3. Configure Image Widget Cropper and set up image styles to work with
       photos: admin/config/media/photos.
    4. The image crop widget will appear on the image edit form:
       photos/image/{file}/edit

For watermark:
    1. Install Image Effects https://www.drupal.org/project/image_effects
    2. Update image styles as needed.
    3. Tips: resize or scale image before watermark is added.
       Or use watermark scale option.

For inline editing photo title and description:
    1. TBD (@todo).
    2. Save jquery.jeditable.js AND jquery.jeditable.mini.js
      from http://www.appelsiini.net/projects/jeditable.
    3. Add both files to libraries/jeditable.

For colorbox integration:
    1. Install the Colorbox module (@todo).
    2. On the Colorbox module settings page check "Enable Colorbox load" in
       extra settings and save.

Locked and Password Protected Galleries
 * Please note that locked and password protected galleries will only protect
   the actual image URL if a private file path is set. In settings.php be sure
   to set the private file path ($settings['file_private_path']).

 * NGINX if you are using NGINX the following needs to be added to your config
   file to allow image styles to be created and accessed on the private file
   system:
   # Private image styles
     location ~ ^/system/files/styles/ {
        try_files $uri @rewrite;
     }
   # Private photos image styles
     location ~ ^/system/files/photos/ {
        try_files $uri @rewrite;
     }


MAINTAINERS
-----------

 * Nathaniel Burnett (Nathaniel) - https://www.drupal.org/u/nathaniel
 * eastcn - https://www.drupal.org/u/eastcn

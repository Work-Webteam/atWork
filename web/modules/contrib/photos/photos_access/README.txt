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

The Album Photos sub-module Photo Access provides advanced photo access and
privacy settings for each album including open, locked, designated users, or
password required.

 * For a full description of the module visit:
   https://www.drupal.org/project/photos or
   https://www.drupal.org/node/2896620

 * To submit bug reports and feature suggestions, or to track changes visit:
   https://www.drupal.org/project/issues/photos


FEATURES
--------

 * Lock albums.
 * Password protected albums.
 * Create list of users who can access certain albums (@todo).
 * Create list of users who can edit albums, collaborators (@todo).


REQUIREMENTS
------------

This module is a sub-module of the Album Photos module:
 * https://www.drupal.org/project/photos


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

    1. Navigate to Administration > Extend and enable the Photos and the Photo
       access module. Upon enabling, the module creates a "Photos" content type.
    2. Navigate to Administration > Configuration > Media > Photos to edit
       global settings including photo access.
    3. Open the Basic Settings field set to change the privacy settings.
    4. Open the Privacy Settings field set to edit the settings for deleting
       private image styles: Automatically delete or Never delete. Automatically
       delete to save disk space. Never delete to improve load speed.
    5. Save configuration.


MAINTAINERS
-----------

 * Nathaniel Burnett (Nathaniel) - https://www.drupal.org/u/nathaniel
 * eastcn - https://www.drupal.org/u/eastcn

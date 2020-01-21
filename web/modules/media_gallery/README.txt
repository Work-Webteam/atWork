Media Gallery
https://www.drupal.org/project/media_gallery
============================================

INTRODUCTION
------------

A simple gallery of media.

REQUIREMENTS
------------

* media (core)
* media_library (core)
* colorbox

INSTALLATION
------------

Install as you would normally install a contributed Drupal module. Visit:
https://www.drupal.org/docs/8/extending-drupal-8/installing-drupal-8-modules
for further information.

CONFIGURATION
-------------

Add new galleries: /admin/content/media-gallery

Add own fields to galleries: /admin/structure/media-gallery/fields

All galleries view: /galleries

Change ColorBox to PhotoSwipe:

* Add new media view mode:
  /admin/structure/display-modes/view/add/media

* Enable new view mode and set formatter as PhotoSwipe:
  /admin/structure/media/manage/image/display/media_photoswipe

* Set view mode as PhotoSwipe for the galleries:
  /admin/structure/media-gallery/display

MAINTAINERS
-----------

Current maintainers:
* Andrei Ivnitskii - https://www.drupal.org/u/ivnish

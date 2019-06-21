CONTENTS OF THIS FILE
---------------------

 * Introduction
 * Requirements
 * Recommended Modules
 * Installation
 * Configuration
 * Maintainers


INTRODUCTION
------------

Colorbox Inline allows the user to open content already on the page within a
colorbox.


REQUIREMENTS
------------

Requires the following modules:

 * Colorbox (https://drupal.org/project/colorbox)


RECOMMENDED MODULES
-------------------

To load content via AJAX, use:

 * colorbox_load - https://www.drupal.org/project/colorbox_load


INSTALLATION
------------

Install the Colorbox Inline module as you would normally install a contributed Drupal module.
Visit https://www.drupal.org/node/1897420 for further information.


CONFIGURATION
-------------

To create an element which opens the colorbox on select:

Add the attribute data-colorbox-inline to an element and make its value a
selector for the content you wish to open.
Eg, <a data-colorbox-inline=".user-login">User Login</a>.
Optional extra configuration you can add:

 - `data-width` and `data-height` to the anchor to control the size of the
  modal window.
 - `data-class` to add a class to the colorbox wrapper.
 - `data-rel="[galleryid]` to add a next/previous options to the opened colorboxes.


MAINTAINERS
-----------

Current maintainers:

 * Sam Becker (Sam152) - https://www.drupal.org/user/1485048
 * Renato Gonçalves (RenatoG) - https://www.drupal.org/user/3326031
 * Nick Wilde (NickWilde) - https://www.drupal.org/u/nickwilde

Supporting maintenance and support provided by:

 * PreviousNext - https://www.drupal.org/previousnext
 * CI&T - https://www.drupal.org/cit

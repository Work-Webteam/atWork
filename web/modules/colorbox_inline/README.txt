CONTENTS OF THIS FILE
---------------------

 * Introduction
 * Requirements
 * Installation
 * Configuration
 * Maintainers


INTRODUCTION
------------

Colorbox Inline allows you to open content already on the page within a
colorbox. If you would like to load content via AJAX, you can use colorbox_load.


REQUIREMENTS
------------

Requires the following modules:

 * Colorbox (https://drupal.org/project/colorbox)

To create an element which opens the colorbox on click:

Add a the attribute data-colorbox-inline to an element and make it's value a
selector for the content you wish to open.
Eg, <a data-colorbox-inline=".user-login">User Login</a>.
Optional extra configuration you can add:

 - `data-width` and `data-height` to the anchor to control the size of the
  modal window.
 - `data-class` to add a class to the colorbox wrapper.


INSTALLATION
------------

Install as you would normally install a contributed Drupal module. See:
https://drupal.org/documentation/install/modules-themes/modules-8 for further
information.


CONFIGURATION
-------------

No special requirements


MAINTAINERS
-----------

Current maintainers:
 * Sam Becker (Sam152) - https://www.drupal.org/user/1485048
 * Renato Gonçalves (RenatoG) - https://www.drupal.org/user/3326031
 * Nick Wilde (NickWilde) - https://www.drupal.org/u/nickwilde

CONTENTS OF THIS FILE
---------------------

 * Introduction
 * Requirements
 * Installation
 * Configuration
 * Known issues
 * FAQ


INTRODUCTION
------------

This is a sub-module to Facets module. It provides integration with Views.
With this module enabled, exposed filters and contextual filters can be used
as facet sources on views pages.

 * For a full description of the module, visit the project page:
   https://www.drupal.org/project/core_views_facets

 * To submit bug reports and feature suggestions, or to track changes:
   https://www.drupal.org/project/issues/core_views_facets


REQUIREMENTS
------------

This module requires the following modules:

 * Views (https://www.drupal.org/docs/8/core/modules/views)
 * Facets (https://www.drupal.org/project/facets)

Core Views Facets depends on Facets and Views


INSTALLATION
------------

 * Install as you would normally install a contributed drupal module. See:
   https://www.drupal.org/docs/8/extending-drupal-8/installing-drupal-8-modules
   for further information.


CONFIGURATION
-------------

Before adding a facet, there should be a view and a facet source.

* Create a view to your liking.

* Add at least one "page" display.

* Add at least either an exposed or a contextual filter.

* Create a facet as described by the Facets module.

* The view page(s) you created will each be listed as facet sources twice:

  - a contextual filter facet source

  - an exposed filter facet source

* IMPORTANT - click the edit button on the facet source itself to assign
  the "Core views url processor" instead of the default one.

After adding one of those, you can add a facet on the facets configuration page:
/admin/config/search/facets, there's an `add facet` link, that links to:
admin/config/search/facets/add-facet. Use that page to add the facet by
selecting the correct facet source and field from that source.

Create the facet blocks as described by the Facets module.


KNOWN ISSUES
------------

When choosing the "Hard limit" option on a search_api_db backend, be aware that
the limitation is done internally after sorting on the amount of results ("num")
first and then sorting by the raw value of the facet (e.g. entity-id) in the
second dimension. This can lead to edge cases when there is an equal amount of
results on facets that are exactly on the threshold of the hard limit. In this
case the raw facet value with the lower value is preferred:

| num | value | label |
|-----|-------|-------|
|  3  |   4   | Bar   |
|  3  |   5   | Atom  |
|  2  |   2   | Zero  |
|  2  |   3   | Clown |

"Clown" will be cut off due to its higher internal value (entity-id). For
further details see: https://www.drupal.org/node/2834730


FAQ
---

Q: Why doesn't chosen (or similar javascript dropdown replacement) not work
with the dropdown widget.

A: Because the dropdown we create for the widget is created trough javascript,
the chosen module (and others, probably) doesn't find the select element.
So the library should be attached to the block in custom code, we haven't done
this in facets because we don't want to support all possible frameworks.
See https://www.drupal.org/node/2853121 for more information.

Q: Why are facets results links from another language showing in the facet
results?

A: Facets use the same limitations as the query object passed, so when using
views, add a filter to the view to limit to one language.
Otherwise, this is solved by adding a `hook_search_api_query_alter()` that
limits the results to the current language.

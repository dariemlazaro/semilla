CONTENTS OF THIS FILE
---------------------

 * Introduction
 * Requirements
 * Installation
 * Configuration
 * FAQ
 * Maintainers


INTRODUCTION
------------

This module provides clean up search index built by search module. It is helpful
while deploying large sites whose search index becomes massive.
The reindex button does not clear the search index but rather gradually
replaces existing search data with new data as items are reindexed.

 * For a full description of the module, visit the project page:
   https://www.drupal.org/project/searchindex_wipe

 * To submit bug reports and feature suggestions, or to track changes:
   https://www.drupal.org/project/issues/searchindex_wipe


REQUIREMENTS
------------

This module requires no modules outside of Drupal core.


INSTALLATION
------------

 * Install the Search Index Wipe module as you would normally install a
   contributed Drupal module.
   Visit https://www.drupal.org/node/1897420 for further information.


CONFIGURATION
-------------

    1. Navigate to Administration > Extend and enable the module.
    2. Navigate to Administration > Configuration > Search and metadata >
       Search settings.
    3. Hit on "Wipe Index" button next to "Re-index" site button.


FAQ
---

Q: Why should I use this module?

A: The search index can become massive on large sites, making it difficult
   to transfer the site to another server. Examples include migrating ISPs
   or just creating a test site. Yes, we know it would be better to not delete
   the entire search index, but its sheer size sometimes forces the need.

Q: I wiped my search index, How do I rebuilt it?

A: It is similar to Rebuild index button, we need to run cron on the site to
   rebuilt the index.


MAINTAINERS
-----------

 * Sagar Ramgade (Sagar Ramgade) - http://drupal.org/user/399718
 * Nitesh Pawar (Nitesh Pawar) - http://drupal.org/user/1069334

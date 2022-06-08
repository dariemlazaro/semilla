CONTENTS OF THIS FILE
---------------------

 * Introduction
 * Requirements
 * Installation
 * Configuration
 * Maintainers

INTRODUCTION
------------

This module includes several different technical ways/methods to make
copying/stealing information/images from your site harder than it usually is:

Disable text selection
Disable copy to clipboard
Disable right-click context menu on all site content
Disable right-click context menu only on images (<img> tag)
Place transparent image above all your images - this will protect your real
images from being saved using context menu or drag-and-drop to desktop.
Protect/hide your images from search engine indexes so that your images don't
show up in image searches - add "noimageindex" robots tag and disallow image
files indexing in robots.txt

REQUIREMENTS
------------

This module does not have any dependency on any other module.

INSTALLATION
------------

Install as normal (see http://drupal.org/documentation/install/modules-themes).

CONFIGURATION
-------------

Once installed and enabled then configure the options at
"admin/config/user-interface/copyprevention" - all the methods are disabled by
default. The user permission (admin/people/permissions) "Bypass Copy
prevention" allows to not apply these module methods to trusted user roles.

MAINTAINERS
-----------
Neslee Canil Pinto: https://www.drupal.org/u/neslee-canil-pinto
nehajyoti: https://www.drupal.org/u/nehajyoti

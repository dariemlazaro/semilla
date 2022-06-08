DESCRIPTION
===========

Javascript lightbox library offers very nice mobile browsing features (in
particular swiping to the next picture)!


INSTALLATION
============

1. Download and install this module as usual. For more info visit
  https://www.drupal.org/documentation/install/modules-themes/modules-8

2. Install third party PhotoSwipe software:
  Download PhotoSwipe 4.x source from PhotoSwipe website
  (e.g. https://github.com/dimsemenov/PhotoSwipe/archive/v4.1.0.zip)
  Unarchive it into your "libraries" directory (e.g. /libraries).
  You may need to create the "libraries" directory first.
  Rename it to "photoswipe" (lower case).
  NB: Relying on libraries module to locate 'photoswipe' folder allows you to
  place it in a site specific (e.g. sites/mysite/libraries) or default folder
  (e.g.sites/all/libraries). Site-specific versions are selected preferentially.

3. Enable the PhotoSwipe module.


USAGE
=====

1. Multiple images in nodes
After adding an image field to any content type (e.g. 'article'), you can select
'PhotoSwipe: Preset1 to Preset2' as a display mode in Structure >> Content types
>> MyContentType in the tab 'Manage display'. All possible
combinations of image styles are proposed.

2. Multiple images in Views
To use photoswipe in views you must add a custom CSS class called
'photoswipe-gallery'.
Add the CSS class to Advanced >> Other >> CSS class
in the view settings (bottom right).

3. Single image in node
To load a single image in node you must add data-size="widthxheight"
(the exact size of the image) and the class="photoswipe" to display it properly.
e.g.
<a href="/images/test_img.png" class="photoswipe" data-size="640x400">
 <img src="/images/test_img.png" alt="Test Image" />
</a>
Doing this means that you should probably enable loading the library on all non
admin pages in admin/config/media/photoswipe.

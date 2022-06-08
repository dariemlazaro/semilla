/**
 * @file
 * Javascript behaviors for the fb_likebox module.
 */

(function ($) {

  'use strict';

  Drupal.behaviors.fbLikebox = {

    attach: function (context, settings) {
      if (context !== document) {
        // AJAX request.
        return;
      }
      (function (d, s, id) {
        var js;
        var fjs = d.getElementsByTagName(s)[0];
        if (d.getElementById(id)) {
          return;
        }
        js = d.createElement(s);
        js.id = id;
        js.src = '//connect.facebook.net/' + settings.fbLikeboxLanguage + '/sdk.js#xfbml=1&version=v2.5';
        if (settings.fbLikeboxAppId) {
          js.src += '&appId=' + settings.fbLikeboxAppId;
        }
        fjs.parentNode.insertBefore(js, fjs);
      }(document, 'script', 'facebook-jssdk', settings));
    }
  };

})(jQuery);

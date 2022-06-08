/**
 * @file
 * JavaScript file for the Text Resize module.
 */

(function ($, Drupal, drupalSettings) {
  Drupal.behaviors.textResize = {
    attach: function (context, settings) {
      // Which div or page element are we resizing?
      var textResizeScope = drupalSettings.text_resize.text_resize_scope;
      var textResizeMaximum = drupalSettings.text_resize.text_resize_maximum;
      var textResizeMinimum = drupalSettings.text_resize.text_resize_minimum;
      var textResizeLineHeightAllow = drupalSettings.text_resize.text_resize_line_height_allow;
      var textResizeLineHeightMin = drupalSettings.text_resize.text_resize_line_height_min;
      var textResizeLineHeightMax = drupalSettings.text_resize.text_resize_line_height_max;
      if (textResizeScope) {
        var elementToResize = {};
        if ($('#' + textResizeScope).length > 0) {
          elementToResize = $('#' + textResizeScope);
        }
        else if ($('.' + textResizeScope).length > 0) {
          elementToResize = $('.' + textResizeScope); // CLASS specified by admin.
        }
        else {
          elementToResize = $(textResizeScope); // It's just a tag specified by admin.
        }
      }
      else { // Look for some default scopes that might exist.
        if ($('#page').length > 0) {
          var elementToResize = $('#page'); // Main body div for Bartik.
        }
        else if ($('#content-inner').length > 0) {
          var elementToResize = $('#content-inner'); // Main body div for Zen-based themes.
        }
        else if ($('#squeeze > #content').length > 0) {
          var elementToResize = $('#squeeze > #content'); // Main body div for Zen Classic.
        }
      }
      // Set the initial font size if necessary.
      if ($.cookie('text_resize') != null) {
        elementToResize.css('font-size', parseFloat($.cookie('text_resize')) + 'px');
      }
      if (textResizeLineHeightAllow) {
        // Set the initial line height if necessary.
        if ($.cookie('text_resize_line_height') != null) {
          elementToResize.css('line-height', parseFloat($.cookie('text_resize_line_height')) + 'px');
        }
      }
      // Changer links will change the text size when clicked.
      $('a.changer').once('text-resize').click(function () {
        // Set the current font size of the specified section as a variable.
        var currentFontSize = parseFloat(elementToResize.css('font-size'), 10);
        // Set the current line-height.
        var current_line_height = parseFloat(elementToResize.css('line-height'), 10);
        // Javascript lets us choose which link was clicked, by ID.
        if (this.id == 'text_resize_increase') {
          var new_font_size = currentFontSize * 1.2;
          if (textResizeLineHeightAllow) {
            var new_line_height = current_line_height * 1.2;
          }
          // Allow resizing as long as font size doesn't go above textResizeMaximum.
          if (new_font_size <= textResizeMaximum) {
            $.cookie('text_resize', new_font_size, {path: '/'});
            if (textResizeLineHeightAllow) {
              $.cookie('text_resize_line_height', new_line_height, {path: '/'});
            }
            var allow_change = true;
          }
          else {
            $.cookie('text_resize', textResizeMaximum, {path: '/'});
            if (textResizeLineHeightAllow) {
              $.cookie('text_resize_line_height', textResizeLineHeightMax, {path: '/'});
            }
            var reset_size_max = true;
          }
        }
        else if (this.id == 'text_resize_decrease') {
          var new_font_size = currentFontSize / 1.2;
          if (textResizeLineHeightAllow) {
            var new_line_height = current_line_height / 1.2;
          }
          if (new_font_size >= textResizeMinimum) {
            // Allow resizing as long as font size doesn't go below textResizeMinimum.
            $.cookie('text_resize', new_font_size, {path: '/'});
            if (textResizeLineHeightAllow) {
              $.cookie('text_resize_line_height', new_line_height, {path: '/'});
            }
            var allow_change = true;
          }
          else {
            // If it goes below textResizeMinimum, just leave it at textResizeMinimum.
            $.cookie('text_resize', textResizeMinimum, {path: '/'});
            if (textResizeLineHeightAllow) {
              $.cookie('text_resize_line_height', textResizeLineHeightMin, {path: '/'});
            }
            var reset_size_min = true;
          }
        }
        else if (this.id == 'text_resize_reset') {
          $.cookie('text_resize', null, {path: '/'});
          if (textResizeLineHeightAllow) {
            $.cookie('text_resize_line_height', null, {path: '/'});
          }
          var reset_size_original = true;
        }
        // jQuery lets us set the font size value of the main text div.
        if (allow_change == true) {
          elementToResize.css('font-size', new_font_size + 'px'); // Add 'px' onto the end, otherwise ems are used as units by default.
          if (textResizeLineHeightAllow) {
            elementToResize.css('line-height', new_line_height + 'px');
          }
          return false;
        }
        else if (reset_size_min == true) {
          elementToResize.css('font-size', textResizeMinimum + 'px');
          if (textResizeLineHeightAllow) {
            elementToResize.css('line-height', textResizeLineHeightMin + 'px');
          }
          return false;
        }
        else if (reset_size_max == true) {
          elementToResize.css('font-size', textResizeMaximum + 'px');
          if (textResizeLineHeightAllow) {
            elementToResize.css('line-height', textResizeLineHeightMax + 'px');
          }
          return false;
        }
        else if (reset_size_original == true) {
          elementToResize.css('font-size', '');
          if (textResizeLineHeightAllow) {
            elementToResize.css('line-height', '');
          }
          return false;
        }
      });
    }
  };
})(jQuery, Drupal, drupalSettings);

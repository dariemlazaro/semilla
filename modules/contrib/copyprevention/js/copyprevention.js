(function ($, Drupal, window, document, undefined) {
  Drupal.behaviors.copyprevention = {
    attach: function (context, settings) {
      $.each(settings.copyprevention.body, function (index, value) {
        $('body').bind(value, function (event) {
          return false;
        });
      });
      var $images = $('img', context);
      $.each(settings.copyprevention.images, function (index, value) {
        if (value == 'contextmenu') {
          $images.bind('contextmenu', function (event) {
            return false;
          });
        }
        if (value == 'transparentgif') {
          Drupal.copyprevention.initialize($images, settings);
        }
      });
    }
  };

  Drupal.copyprevention = {
    initialize: function ($images, settings) {
      var self = this;
      $images.each(function (index, Element) {
        if ($(this).hasClass('copyprevention-processed')) {
          return;
        }
        $(this).addClass('copyprevention-processed');

        if (this.naturalWidth == 0 || this.naturalHeight == 0) {
          $(this).bind('load', function () {
            self.process.call(this, settings);
          });
        }
        else {
          self.process.call(this, settings);
        }
      });
    },
    process: function (settings) {
      if (this.naturalWidth > settings.copyprevention.images_min_dimension || this.naturalHeight > settings.copyprevention.images_min_dimension) {
        $(this).bind('mouseover touchstart', function (event) {
          //var pos = $(this).offset();
          var pos = $(this).position();
          var html = '<div class="copyprevention-transparent-gif" style="position: absolute; left: ' + pos.left + 'px; top: ' + pos.top + 'px">' +
            '<img src="' + settings.copyprevention.transparentgif + '" style="width: ' + this.clientWidth + 'px !important; height: ' + this.clientHeight + 'px !important;"></div>';
          //var $overlay = $(html).appendTo('body')
          var $overlay = $(html).insertAfter(this)
            .bind('mouseout', function (e) {
              $overlay.remove();
            });
        });
      }
    }
  };
})(jQuery, Drupal, window, this.document);

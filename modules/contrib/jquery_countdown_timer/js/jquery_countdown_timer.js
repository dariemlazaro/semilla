/**
 * @name        jQuery Countdown Plugin
 * @author        Martin Angelov
 * @version     1.0
 * @url            http://tutorialzine.com/2011/12/countdown-jquery/
 * @license        MIT License
 */

(function ($) {
    "use strict";

    // Creating the plugin
    $.fn.countdown = function (prop) {

        var options = $.extend({
            callback : function (){},
            timestamp : 0,
            font_size : 0
        },prop);

        init(this, options);

        var positions = this.find('.position');

        (function tick(){

            // Time left
            var left = Math.floor((options.timestamp - (new Date())) / 1000);

            if(left < 0){
                left = 0;
            }

            var weeks = Math.floor(left / 60 / 60 / 24 / 7),
                days = Math.floor(left / 60 / 60 / 24) % 7,
                hours = Math.floor((left / (60 * 60)) % 24),
                minutes = Math.floor((left / 60) % 60),
                seconds = left % 60;

            updateDuo(0, 1, weeks);
            updateDuo(2, 3, days);
            updateDuo(4, 5, hours);
            updateDuo(6, 7, minutes);
            updateDuo(8, 9, seconds);

            // Calling an optional user supplied callback
            options.callback(weeks, days, hours, minutes, seconds);

            // Scheduling another call of this function in 1s
            setTimeout(tick, 1000);
        })();

        // This function updates two digit positions at once
        function updateDuo(minor,major,value){
            switchDigit(positions.eq(minor),Math.floor(value / 10) % 10);
            switchDigit(positions.eq(major),value % 10);
        }

        return this;
    };

    function init(elem, options){
        elem.addClass('countdownHolder').css({ 'font-size': options.font_size + 'px' });

        // Creating the markup inside the container
        $.each(['Weeks','Days','Hrs','Mins','Secs'],function (i) {
            $('<span class="count' + this + '">' +
                '<span class="position">' +
                '<span class="digit static">0</span>' +
                '</span>' +
                '<span class="position">' +
                '<span class="digit static">0</span>' +
                '</span>'
            ).appendTo(elem);

            if(this != "Secs"){
                elem.append('<span class="countDiv countDiv' + i + '"></span>');
            }
        });
    }

    // Creates an animated transition between the two numbers
    function switchDigit(position,number){

        var digit = position.find('.digit')

        if(digit.is(':animated')){
            return false;
        }

        if(position.data('digit') == number){
            // We are already showing this number
            return false;
        }

        position.data('digit', number);

        var replacement = $('<span>',{
            'class':'digit',
            css:{
                top:'-2.1em',
                opacity:0
            },
            html:number
        });

        // The .static class is added when the animation
        // completes. This makes it run smoother.

        digit
            .before(replacement)
            .removeClass('static')
            .animate({top:'2.5em',opacity:0},'fast',function () {
                digit.remove();
            });

        replacement
            .delay(100)
            .animate({top:0,opacity:1},'fast',function () {
                replacement.addClass('static');
            });
    }
})(jQuery);

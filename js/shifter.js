(function ($, drupalSettings) {
  'use strict';

  // Some utilities.
  if (typeof Object.shifterDialogBeget !== 'function') {
    Object.shifterDialogBeget = function (o) {
      var F = function () {};
      F.prototype = o;
      return new F();
    };
  }
  var shifter_dialog_integer = function (num) {
    return Math[num < 0 ? 'ceil' : 'floor'](num);
  };
  var shifter_dialog_escape = function (str) {
    return str.replace(/([.?*+^$[\]\\(){}|-])/g, '\\$1');
  };

  // Wrapper of the entire logic.
  var JsShifterDialog = {
    init: function (options, elem) {
      this.$elem = $(elem);
      this.$keysInput = this.$elem.find('input.shifter-dialog-keys').eq(0);
      this.$resultsWrapper = this.$elem.find('ul.shifter-dialog-results').eq(0);
      this.menusList = JSON.parse(drupalSettings.shifterDialog.menusList);
      this.options = $.extend({}, $.fn.slideShifterDialog.options, options);
      this.bindEvents();
    },
    bindEvents: function () {
      var that = this;
      $(document).on('keypress click', $.proxy(that.slideToggle, that));
      $(document).on('keydown', $.proxy(that.handleEscapeBtn, that));
      $(document).on('keydown', $.proxy(that.setAccessibility, that));
      this.$keysInput.on('keyup', $.proxy(that.buildResults, that));
      this.$resultsWrapper.on('mouseenter', $.proxy(that.setResultsHover, that));
    },
    slideToggle: function (event) {
      var $eventTarget = $(event.target);
      if (event.type === 'click' && $eventTarget.closest('.block-shifter-dialog-ajax').length === 0) {
        this.shifterDialogUp(this.options.slideSpeed);
      }
      else if (event.which === 46 && !$eventTarget.is('input, textarea, select')) {
        this.shifterDialogToggle();
      }
    },
    handleEscapeBtn: function (event) {
      if (event.which === 27) {
        this.shifterDialogUp();
      }
    },
    shifterDialogUp: function () {
      if (this.$elem.hasClass('block-shifter-dialog-ajax')) {
        this.clearData();
        this.displayResults([]);
        this.$elem.slideUp(this.options.slideSpeed);
      }
    },
    shifterDialogToggle: function () {
      if (this.$elem.hasClass('block-shifter-dialog-ajax')) {
        var that = this;
        this.$elem.slideToggle(this.options.slideSpeed, function () {
          that.setFocus();
          that.refresh();
        });
      }
    },
    buildFragment: function (results) {
      var that = this;
      if (results.length === 0) {
        this.results = $(that.options.wrapEachWith)
            .addClass('shifter-dialog-no-matches')
            .append(this.options.noMatchesSentence);
        return;
      }
      this.results = $.map(results, function (obj, i) {
        var link = $('<a></a>', {
          href: obj.url,
          text: typeof obj.full_title === 'undefined'
              ? obj.title : obj.full_title,
          title: obj.title,
          tabindex: -1
        });
        if (i === 0) {
          link.addClass('active');
        }
        return $(that.options.wrapEachWith).append(link)[0];
      });
    },
    buildResults: function (event) {
      var controlKeyCodes = [13, 27, 39, 35, 36, 37, 38, 40];
      if (event.ctrlKey || event.altKey || $.inArray(event.which, controlKeyCodes) !== -1) {
        return;
      }

      var inputValue = this.$keysInput.val().toLowerCase();
      var results = [];

      if (inputValue.length === 0) {
        delete this.indexToStartFrom;
        this.displayResults(results);
        return;
      }
      else if (inputValue.length <= 2) {
        delete this.indexToStartFrom;
      }

      if (typeof this.indexToStartFrom === 'undefined') {
        this.indexToStartFrom = this.FindIndexToStartFrom(inputValue.charAt(0));
      }

      results = this.retrieveDataFromJson(inputValue);

      this.displayResults(results);
    },
    retrieveDataFromJson: function (keys) {
      var results = [];
      var regex = new RegExp('^' + shifter_dialog_escape(keys));

      for (var i = this.indexToStartFrom; i < this.menusList.length; i++) {
        if (regex.test(this.menusList[i].title.toLowerCase())) {
          results.push(this.menusList[i]);
        }
        else if (results.length > 0) {
          return results;
        }

        if (results.length === this.options.numberOfResultsToReturn
          || keys.charAt(0) !== this.menusList[i].title.charAt(0).toLowerCase()) {
          return results;
        }
      }
      return results;
    },
    displayResults: function (results) {
      this.buildFragment(results);
      this.$resultsWrapper.html(this.results);
    },
    refresh: function () {
      var that = this;
      $.ajax(drupalSettings.path.baseUrl + 'shifter-dialog-block')
          .error(function () {
            that.menusList = [];
            that.clearData();
            that.displayResults([]);
          });
    },
    FindIndexToStartFrom: function (character) {
      var left = -1;
      var middle;
      var right = this.menusList.length;

      while (right > left + 1) {
        middle = shifter_dialog_integer(((left + right) / 2));
        if (this.menusList[middle].title.charAt(0).toLowerCase() >= character) {
          right = middle;
        }
        else {
          left = middle;
        }
      }
      if (right < this.menusList.length
        && this.menusList[right].title.charAt(0).toLowerCase() === character) {
        return right;
      }
    },
    setFocus: function () {
      this.$keysInput.focus();
    },
    clearData: function () {
      this.$keysInput.val('');
      this.buildFragment([]);
    },
    setResultsHover: function () {
      var $links = this.$resultsWrapper.find('a');
      if ($links.length > 0) {
        this.$resultsWrapper.on('mouseenter', 'a', function () {
          $links.each(function () {
            $(this).removeClass('active');
          });
          $(this).addClass('active');
        });
      }
    },
    setAccessibility: function (event) {
      if (this.$elem.is(':visible') && (event.which === 38 || event.which === 40
        || event.which === 13)) {
        this.setLinksAccessibility(event.which);
        event.preventDefault();
      }
    },
    setLinksAccessibility: function (keycode) {
      var $activeLink = this.$resultsWrapper.find('a.active');
      if (keycode === 38 && $activeLink.closest('li').prev('li').length > 0) {
        $activeLink.removeClass('active');
        $activeLink.closest('li').prev('li').children('a').addClass('active');
      }
      else if (keycode === 40 && $activeLink.closest('li').next('li').length > 0) {
        $activeLink.removeClass('active');
        $activeLink.closest('li').next('li').children('a').addClass('active');
      }
      else if (keycode === 13 && $activeLink.length === 1) {
        window.location.href = $activeLink.attr('href');
        this.shifterDialogUp();
      }
    }
  };
  $.fn.slideShifterDialog = function (options) {
    return this.each(function () {
      var jsShifterDialog = Object.shifterDialogBeget(JsShifterDialog);
      jsShifterDialog.init(options, this);
    });
  };

  $.fn.slideShifterDialog.options = {
    slideSpeed: 30,
    wrapEachWith: '<li></li>',
    numberOfResultsToReturn: 5,
    noMatchesSentence: 'No matches.'
  };
})(jQuery, drupalSettings);

(function ($, drupalSettings) {
  'use strict';
  $(document).ready(function () {
    $('.block-shifter-dialog').slideShifterDialog({
      numberOfResultsToReturn: drupalSettings.shifterDialog.numResultsToReturn,
      noMatchesSentence: drupalSettings.shifterDialog.noMatchesSentence
    });
  });
})(jQuery, drupalSettings);

define(
    [
    'jquery',
    'algoliaInsights'
    ],
    function ($) {
    'use strict';
    var algoliaCookieMixin = {
        _create: function () {
            this._super();
            $(document).on('click', this.options.cookieAllowButtonSelector, function (event) {
                event.preventDefault();
                algoliaInsights.initializeAnalytics(true);
            });
        }
    };

    return function (widget) {
        $.widget('mage.cookieNotices', widget, algoliaCookieMixin);
        return $.mage.cookieNotices;
    };

});

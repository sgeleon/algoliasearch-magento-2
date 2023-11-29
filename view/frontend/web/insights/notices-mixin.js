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
            $(document).on('click', algoliaConfig.cookieConfiguration.cookieAllowButtonSelector, function (event) {
                event.preventDefault();
                algoliaInsights.track(algoliaConfig, true);
            });
        }
    };

    return function (widget) {
        $.widget('mage.cookieNotices', widget, algoliaCookieMixin);
        return $.mage.cookieNotices;
    };

});

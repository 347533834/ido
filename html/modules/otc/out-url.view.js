define(function (require, exports, modules) {
    'use strict';

    require('css!assets/css/coin/out-url.css');

    var template = require('text!./out-url.tpl.html'),
        util = require('util');
    var url;
    var View = Backbone.View.extend({
        el: '#page',
        events: {},
        initialize: function () {
            this.template = _.template(template);
            this.render();
        },
        render: function () {
            this.$el.html(this.template());

            url = util.queryString('url');

            var title = util.queryString('title');
            if (util.isNull(url) || util.isNull(title)) {
                history.go(-1);
                return;
            }

            if (device && device.platform && device.platform.toLowerCase() == 'ios')
                $('.out-url > div > iframe').attr('scrolling', 'no');

            $('.out-url > div').height(($(window).height() - $('header').height()) + "px");

            $('header span').text(decodeURIComponent(title));

            this.loadUser();
        },
        loadUser: function () {
            this.user = window.user.get();
            if (!this.user) {
                Backbone.history.navigate('login', {trigger: true});
                return;
            }
            window.jsonpGet('Otc/show_otc', {
                token: this.user.token,
            }, 'show_otc', function (json) {
                if (json.code == 1) {
                    if (decodeURIComponent(url) == 'https://buy') {
                        $('.out-url iframe').attr('src', json.data.buyListUrl);
                    }
                    if (decodeURIComponent(url) == 'https://sell') {
                        $('.out-url iframe').attr('src', json.data.sellListUrl);
                    }
                } else {
                    window.toast(json.msg);
                }
            });
        },
    });
    return View;
});
define(function (require, exports, modules) {
    'use strict';

    require('css!assets/css/information/information.css');
    require('assets/plugins/md5.js');
    var template = require('text!./information.tpl.html'),
        util = require('util');

    var View = Backbone.View.extend({
        el: '#page',
        events: {

        },
        initialize: function () {
            this.template = _.template(template);
            this.render();
        },
        render: function () {
            this.$el.html(this.template());
            $('header span').text("认证信息");
        },

    });
    return View;
});


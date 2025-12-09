define(function (require, exports, modules) {
    'use strict';

    require('css!assets/css/reset-password/reset-password.css');
    require('assets/plugins/md5.js');
    var template = require('text!./reset-password.tpl.html'),
        util = require('util');

    var View = Backbone.View.extend({
        el: '#page',
        events: {
            'click .modify-nickname1': function () {
                Backbone.history.navigate('modify-nickname', {trigger: true});
            },
        },
        initialize: function () {
            this.template = _.template(template);
            this.render();
        },
        render: function () {
            this.$el.html(this.template());
            $('header span').text("重置资金密码");
        },

    });
    return View;
});


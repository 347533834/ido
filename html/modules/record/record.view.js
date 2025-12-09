define(function (require, exports, modules) {
    'use strict';

    require('css!assets/css/record/record.css');
    require('assets/plugins/md5.js');
    var template = require('text!./record.tpl.html'),
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
            $('header span').text("反馈记录");
        },

    });
    return View;
});


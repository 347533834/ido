define(function (require, exports, modules) {
    'use strict';

    require('css!assets/css/nickname/nickname.css');
    require('assets/plugins/md5.js');
    var template = require('text!./nickname.tpl.html'),
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
            $('header span').text("修改昵称");
          this.loadUser();
        },
      loadUser: function () {
        this.user = window.user.get();
        if (!this.user) {
          Backbone.history.navigate('login', {trigger: true});
          return;
        }
        $('.username').text(this.user.username);
      },

    });
    return View;
});


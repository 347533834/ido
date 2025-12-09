define(function (require, exports, modules) {
    'use strict';

    require('css!assets/css/user/user.css');
    require('assets/plugins/md5.js');
    var template = require('text!./user.tpl.html'),
        util = require('util');

    var View = Backbone.View.extend({
        el: '#page',
        events: {
            'click .team-1': function () {
                Backbone.history.navigate('team', {trigger: true});
            },
            'click .team-2': function () {
                Backbone.history.navigate('teams', {trigger: true});
            },
            'click .bonus-1': function () {
                Backbone.history.navigate('bonus', {trigger: true});
            },
            'click .security-1': function () {
                Backbone.history.navigate('security', {trigger: true});
            },
            'click .service-1': function () {
                Backbone.history.navigate('service', {trigger: true});
            },
            'click .nickname-1': function () {
                Backbone.history.navigate('nickname', {trigger: true});
            },
            'click .banner img': function () {
                Backbone.history.navigate('invitation', {trigger: true});
            },
            'click .notice-btn': function () {
                Backbone.history.navigate('notice', {trigger: true});
            },
            'click .yule-btn': function () {
                window.toast('待开放');
                return false;
            }
        },
        initialize: function () {
            this.template = _.template(template);
            this.render();
        },
        render: function () {
            this.$el.html(this.template());
            this.loadUser();
        },
        loadUser: function () {
            this.user = window.user.get();
            if (!this.user) {
                Backbone.history.navigate('login', {trigger: true});
                return;
            }
            $('.username').text(this.user.username);
            $('.user_level').text((this.user.user_level == 1 ? '新用户' : (this.user.user_level == 2 ? '普通用户' : (this.user.user_level == 3 ? '高级用户' : '合伙人'))));
        },
    });
    return View;
});


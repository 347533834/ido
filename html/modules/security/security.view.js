define(function (require, exports, modules) {
    'use strict';

    require('css!assets/css/security/security.css');
    require('assets/plugins/md5.js');
    var template = require('text!./security.tpl.html'),
        util = require('util');

    var View = Backbone.View.extend({
        el: '#page',
        events: {
            'click .change-password1': function () {
                Backbone.history.navigate('change-password', {trigger: true});
            },
            'click .set-password1': function () {
                Backbone.history.navigate('set-password', {trigger: true});
            },
            'click .certification-1': function () {
                Backbone.history.navigate('certification', {trigger: true});
            },
            'click .changeWechat': function () {
                Backbone.history.navigate('changeWechat', {trigger: true});
            },
            'click .alipay_code': function () {
                Backbone.history.navigate('changeAlipay', {trigger: true});
            },
            'click .logout': function () {
                this.logout();
            },
        },
        initialize: function () {
            this.template = _.template(template);
            this.render();
        },
        render: function () {
            this.$el.html(this.template());
            $('header span').text("安全中心");
            this.loadUser();
        },
        loadUser: function () {
            this.user = window.user.get();
            if (!this.user) {
                Backbone.history.navigate('login', {trigger: true});
                return;
            }
            this.userCard();
        },
        logout: function () {
            window.jsonpGet('user/logout', {token: this.user.token}, 'logout', function (json) {
                window.user.del();
                Backbone.history.navigate('login', {trigger: true});
            });
        },
        userCard: function () {
            window.jsonpGet('user/bind_id_card', {token: this.user.token}, 'userCard', function (data) {
                var data = data.data;
                if (data) {
                    if (data.status == 1) {
                        $('.card-status').html('<span class="green">已审核</span><i></i>');
                    } else if (data.status == -1) {
                        $('.card-status').html('<span class="red">审核未通过</span><i></i>');
                    } else {
                        $('.card-status').html('<span class="red">待审核</span><i></i>');
                    }
                } else {
                    $('.card-status').html('<span class="red">未认证</span><i></i>');
                }
            });
        }

    });
    return View;
});


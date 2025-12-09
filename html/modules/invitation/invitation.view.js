define(function (require, exports, modules) {
    'use strict';

    require('css!assets/css/invitation/invitation.css');
    require('assets/plugins/jquery/jquery.qrcode.min');
    var template = require('text!./invitation.tpl.html'),
        util = require('util');

    var View = Backbone.View.extend({
        el: '#page',
        events: {},
        initialize: function () {
            this.template = _.template(template);
            this.render();
        },
        render: function () {
            this.$el.html(this.template());
            $('header span').text("邀请好友");
            this.loadUser();
            this.LoadQrcode();
            this.load_rule();
        },
        loadUser: function () {
            this.user = window.user.get();
            if (!this.user) {
                Backbone.history.navigate('login', {trigger: true});
                return;
            }
        },
        LoadQrcode: function () {
            $('#invite').text(this.user.invite);
            $('.qr').qrcode({
                correctLevel: 0,
                text: util.domain + '#register?invite=' + this.user.invite
            });
            $('.qr canvas').css({
                "width": "7.5rem",
                "height": "7.5rem",
                "border": "15px solid #ffffff",
                "border-radius": "5px"
            });
        },
        load_rule: function () {
            var _this = this;
            window.jsonpGet('invite/load_rule', {
                token: this.user.token,
            }, 'load_rule', function (json) {
                if (json.code == 1) {
                    $('#rule').html(json.data);
                }
            });
        },
    });
    return View;
});


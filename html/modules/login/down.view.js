define(function (require, exports, modules) {
    'use strict';

    require('css!assets/css/login/down.css');
    require('assets/plugins/jquery/jquery.qrcode.min');

    var action = '';
    var template = require('text!./down.tpl.html'),
        util = require('util');
    var View = Backbone.View.extend({
        el: '#page',
        events: {
            'click .back': function () {
                Backbone.history.navigate('login', {trigger: true});
            }
        },
        initialize: function () {
            this.template = _.template(template);
            // this.Popup = _.template(popup);
            this.render();
        },
        render: function () {
            this.$el.html(this.template());
            // this.$el.append(this.Popup());
            // $('header').css('opacity', "0");
            this.loadQrcode();

            var is_weixin = function () {
                return /MicroMessenger/.test(navigator.userAgent);
            }

            var winHeight = typeof window.innerHeight != 'undefined' ? window.innerHeight : document.documentElement.clientHeight;
            var weixinTip = $('<div id="weixinTip"><p><img style="width: 100%;" src="assets/img/login/ts.png" alt="微信打开"/></p></div>');

            if (is_weixin()) {
                $("body").append(weixinTip);
                $("#weixinTip").css({
                    "position": "fixed",
                    "left": "0",
                    "top": "0",
                    "height": winHeight,
                    "width": "100%",
                    "z-index": "1000",
                    "background-color": "rgba(25,25,25,.9)",
                    "filter": "alpha(opacity=80)",
                });
                $("#weixinTip p").css({
                    "text-align": "center",
                    "margin-top": "10%",
                    "padding-left": "5%",
                    "padding-right": "5%"
                });
            }
        },
        loadQrcode: function () {
            window.jsonpGet('login/app', {}, 'app', function (json) {
                if (json.code == 1) {
                    var data = json.data;
                    /*$('.android .qr').qrcode({
                        correctLevel: 0,
                        text: data.android.download
                    });*/
                    $('.android').click(function () {
                        location.href = data.android.app_store;
                    });
                    /*$('.ios .qr').qrcode({
                        correctLevel: 0,
                        text: data.ios.download
                    });*/
                    $('.ios').click(function(){
                        location.href=data.ios.app_store;
                    });
                } else {
                    window.toast('加载失败！');
                }
            })
            // var invite = this.user.invite;
            // $('.my_download .code').html('推广码：' + invite);
            // $('.my_download .link').html('推广链接：' + location.origin + location.pathname + "#register?invite=" + invite);
            // $('.my_download .qrcode-img').qrcode({
            //     correctLevel: 0,
            //     text: location.origin + location.pathname + "#register?invite=" + invite
            // });
        }
    });
    return View;
});
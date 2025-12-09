define(function (require, exports, modules) {
    'use strict'

    require('css!assets/css/security/changePay.css');
    require('assets/plugins/lrz/lrz.min.js');

    var template = require('text!./changePay.tpl.html'),
        util = require('util');
    var imgs = {};
    var View = Backbone.View.extend({
        el: '#page',
        events: {
            'click .upload button': 'changeAlipay',
            // 'click .jump_task': function () {
            //     Backbone.history.navigate('task', {trigger: true})
            // },
            // 'click .assets-img span:eq(1)': function () {
            //     Backbone.history.navigate('task', {trigger: true})
            // },
        },
        initialize: function () {
            this.template = _.template(template)
            this.render()
        },
        render: function () {
            this.$el.html(this.template())

            this.user = window.user.get();
            this.loadUser();
        },
        loadUser: function () {
            this.user = window.user.get();
            if (!this.user) {
                Backbone.history.navigate('loading', {trigger: true});
                return;
            }
            this.changeHeader();

            $('input:eq(1)').attr('name', 'alipay');
            $('.bind .form-item span').text('支付宝账号');
            $('.bind .form-item input').attr('placeholder', '请输入您的支付宝账号');
            $('.bind .upload p:eq(0)').text('上传支付宝收款码');

            this.getWechat();
            this.inputChange();
        },
        changeHeader: function () {
            $('header span').text("绑定支付宝");
        },
        getWechat: function () {
            if (this.user.alipay != '' && this.user.alipay != undefined) {
                $('input[name="num"]').val(this.user.alipay);
                $('img').attr('src', util.cdn + this.user.alipay_code + '?' + this.user.alipay_addtime);
            }
        },
        inputChange: function () {
            $('input[type="file"]').change(function () {
                var _this = $(this);
                var obj = $(this)[0].files[0];
                var exts = obj.name.split('.');
                var ext = exts[exts.length - 1];
                if (['jpg', 'gif', 'png', 'jpeg'].indexOf(ext.toLowerCase()) == -1) {
                    window.toast(ext.toLowerCase() + '图片格式错误！');
                    return;
                }
                // console.log(obj.size);
                lrz(obj, {
                    width: 500
                }).then(function (rst) {
                    console.log(rst.base64.length);
                    _this.next().attr('src', rst.base64);
                    imgs[_this.attr('name')] = rst.base64;
                });
            });
        },
        changeAlipay: function () {
            var name = $('input[name="num"]').val(),
                _this = this;

            if (name.length == 0 || name == 0) {
                window.toast('请填写支付宝账号！');
                return;
            }

            if (imgs.alipay == undefined) {
                window.toast('请上传支付宝收款码！');
                return;
            }

            window.jsonpPost(util.server + "upload/alipay?token=" + encodeURIComponent(_this.user.token), {
                imgs: JSON.stringify(imgs),
                session_id: _this.user.session_id,
                alipay: name,
            }, 'alipay', function (json) {
                if (json.code == 1) {
                    _this.user.alipay = json.data.alipay;
                    _this.user.alipay_addtime = json.data.alipay_addtime;
                    _this.user.alipay_code = json.data.alipay_code;
                    window.user.set(_this.user);
                    Backbone.history.navigate('security', {trigger: true})
                }
                window.toast(json.msg);
            });
        },
    });

    return View;
})

define(function (require, exports, modules) {
    'use strict'

    require('css!assets/css/security/changePay.css');
    require('assets/plugins/lrz/lrz.min.js');

    var template = require('text!./changePay.tpl.html'),
        util = require('util');
    var imgs={};
    var View = Backbone.View.extend({
        el: '#page',
        events: {
            'click .upload button': 'changeWechat',
            // 'click .jump_login': function () {
            //     Backbone.history.navigate('login', {trigger: true})
            // },
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

            $('input:eq(1)').attr('name','wechat');
            $('.bind .form-item span').text('微信账号');
            $('.bind .form-item input').attr('placeholder','请输入您的微信账号');
            $('.bind .upload p:eq(0)').text('上传微信收款码');

            this.getWechat();
            this.inputChange();

        },
        changeHeader:function(){
            $('header span').text("绑定微信");
        },
        getWechat:function(){
            console.log(this.user.wechat);
            if(this.user.wechat != '' && this.user.wechat != undefined) {
                $('input[name="num"]').val(this.user.wechat);
                $('img').attr('src', util.cdn + this.user.wechat_code + '?' + this.user.wechat_addtime);
            }
        },
        inputChange:function(e){
            $('input[type="file"]').change(function () {
                var _this = $(this);
                var obj = $(this)[0].files[0];
                console.log(obj);
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
                    console.log(rst);
                    console.log(rst.base64.length);
                    _this.next().attr('src', rst.base64);
                    imgs[_this.attr('name')] = rst.base64;
                });
            });
            /*if (device.platform != "browser") {
                $('#filePay').attr({'type':'text','readonly':true});
                $('#filePay').click(function () {
                    var _this = $(this);
                    // 打开图片库
                    navigator.camera.getPicture(function (imageData) {
                        var img_base64 = "data:image/jpeg;base64," + imageData;
                        _this.next().attr('src', img_base64);
                        imgs[_this.attr('name')] = img_base64;
                    }, function (message) {

                    }, {
                        quality: 50,                                       // 相片质量是50
                        sourceType : Camera.PictureSourceType.SAVEDPHOTOALBUM, // 设置从图片库获取
                        destinationType: Camera.DestinationType.DATA_URL       // 以base64返回
                    });
                });
            }*/
        },
        changeWechat:function () {
            var name = $('input[name="num"]').val(),
                _this = this;

            if (name.length == 0 || name == 0) {
                window.toast('请填写微信账号！');
                return;
            }

            if (imgs.wechat==undefined) {
                window.toast('请上传微信收款码！');
                return;
            }

            window.jsonpPost(util.server + "upload/wechat?token=" + encodeURIComponent(_this.user.token), {
                imgs: JSON.stringify(imgs),
                session_id: _this.user.session_id,
                wechat:name,
            }, 'wechat', function (json) {
                if (json.code == 1) {
                    _this.user.wechat = json.data.wechat;
                    _this.user.wechat_addtime = json.data.wechat_addtime;
                    _this.user.wechat_code = json.data.wechat_code;
                    window.user.set(_this.user);
                    Backbone.history.navigate('security', {trigger: true})
                }
                window.toast(json.msg);
            });
        },
    });

    return View;
})

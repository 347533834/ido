define(function (require, exports, modules) {
    'use strict';

    require('css!assets/css/certification/certification.css');
    require('assets/plugins/lrz/lrz.min.js');
    var template = require('text!./certification.tpl.html'),
        is_add = true,
        util = require('util');
    var imgs = {};
    var View = Backbone.View.extend({
        el: '#page',
        events: {
            // 'click .information-1': function () {
            //      Backbone.history.navigate('information', {trigger: true});
            //  },
            'click .confirm': function (e) {
                if (is_add) {
                    is_add = false;
                    this.addIdcard();
                }
            }
        },
        initialize: function () {
            this.template = _.template(template);
            this.render();
        },
        render: function () {
            this.$el.html(this.template());
            $('header span').text("实名认证");
            this.loadUser();

            $('.sample').click(function () {
                $('.real-name').show();
            });
            $('.real-name .know').click(function () {
                $('.real-name').hide();
            });
        },
        loadUser: function () {
            this.user = window.user.get();
            if (!this.user) {
                Backbone.history.navigate('loading', {trigger: true});
                return;
            }
            this.inputChange();
            this.bind_id_card();
        },

        bind_id_card: function () {
            var _this = this;
            window.jsonpGet('user/bind_id_card', {token: this.user.token}, 'bind_id_card', function (json) {
                if (json.code == 1) {
                    var data = json.data;
                    if (!data) return;

                    if (json.data.status == 0) {
                        $('input[name="name"]').val(data.id_name).attr('readOnly', 'readOnly');
                        $('input[name="card"]').val(data.id_card).attr('readOnly', 'readOnly');

                        $('img:eq(0)').attr('src', util.cdn + data.front_url + '?' + data.front_time);
                        $('img:eq(1)').attr('src', util.cdn + data.back_url + '?' + data.back_time);
                        $('img:eq(2)').attr('src', util.cdn + data.hand_url + '?' + data.hand_time);
                        $('.sample').hide();
                        $('.confirm').hide();
                        $('.title').text('认证图片');
                        $('.remark').text('待审核,我们会在一个工作日内进行审核').css('margin-top', '1rem');
                        $('.none').remove();
                    } else if (json.data.status == -1) {
                        if (data.remark != '') {
                            $('.remark').html('<p style="color: red">未通过原因：' + data.remark + '<p/>');
                        } else {
                            $('.remark').html('<p style="color: red">审核不通过,请重新提交资料<p/>');
                        }

                    } else if (json.data.status == 1) {
                        $('input[name="name"]').val(data.id_name).attr('readOnly', 'readOnly');
                        $('input[name="card"]').val(data.id_card).attr('readOnly', 'readOnly');

                        $('img:eq(0)').attr('src', util.cdn + data.front_url + '?' + data.front_time);
                        $('img:eq(1)').attr('src', util.cdn + data.back_url + '?' + data.back_time);
                        $('img:eq(2)').attr('src', util.cdn + data.hand_url + '?' + data.hand_time);
                        $('.sample').hide();
                        $('.title').text('认证图片');
                        $('.confirm').hide();
                        $('.remark').hide();
                        $('.none').remove();
                    }
                }
            })
        },

        inputChange: function () {
            var _this = this;
            $('.files input').change(function () {
                var _this = $(this);
                var obj = $(this)[0].files[0];
                var exts = obj.name.split('.');
                var ext = exts[exts.length - 1];
                if (['jpg', 'gif', 'png', 'jpeg'].indexOf(ext.toLowerCase()) == -1) {
                    window.toast(ext.toLowerCase() + '图片格式错误');
                    return;
                }

                lrz(obj, {
                    width: 500
                }).then(function (rst) {
                    console.log(rst.base64.length);
                    _this.prev().children().attr('src', rst.base64);
                    imgs[_this.attr('name')] = rst.base64;
                });
            });
        },

        addIdcard: function () {
            var name = $('input[name="name"]').val(),
                card = $('input[name="card"]').val(),
                _this = this;

            if (name.length == 0 || card.length == 0 || name == 0 || card == 0) {
                is_add = true;
                window.toast('请填写身份证姓名，号码！');
                return;
            }

            if (imgs.front == undefined && imgs.back == undefined && imgs.hand == undefined) {
                is_add = true;
                window.toast('请上传身份证照片');
                return;
            }
            if ($('img:eq(0)').attr('src') == ('assets/img/user/img-12.png')
                || $('img:eq(1)').attr('src') == ('assets/img/user/img-9.png')
                || $('img:eq(2)').attr('src') == ('assets/img/user/img-10.png')) {
                is_add = true;
                window.toast('请完整选择上传身份证照片');
                return;
            }
            if (!window.filter(name, $('input[name="name"]'))) {
                is_add = true;
                return false;
            }
            if (!window.filter(card, $('input[name="card"]'))) {
                is_add = true;
                return false;
            }
            window.jsonpPost(util.server + "upload/idadd?token=" + encodeURIComponent(_this.user.token), {
                imgs: JSON.stringify(imgs),
                session_id: _this.user.session_id,
                name: name,
                card: card
            }, 'idadd', function (json) {
                window.toast(json.msg);
                if (json.code == 1) {
                    setTimeout(function () {
                        Backbone.history.navigate('user', {trigger: true});
                    }, 1500);
                } else {
                    is_add = true;
                }

            });
        }
    });
    return View;
});


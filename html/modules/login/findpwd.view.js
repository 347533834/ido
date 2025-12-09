define(function (require, exports, modules) {
    'use strict';

    require('css!assets/css/login/findpwd.css');
    require('assets/plugins/md5.js');
    var template = require('text!./findpwd.tpl.html'),
        action = '',
        util = require('util');

    var View = Backbone.View.extend({
        el: '#page',
        events: {
            'click .captcha': function () {
              $('.captcha img').attr('src', util.server + '/captcha.html' + '?' + 'id=' + Math.random()+'&session_id='+this.session_id)
            },
            'click #get_Code': "get_Code",  //短信验证码
            'click .findpwd button': "reset",  //重置密码


        },
        initialize: function () {
            this.template = _.template(template);
            this.render();
        },
        render: function () {
            this.$el.html(this.template());
            $('header span').text('找回密码');
          this.session_id = '';
          var _this = this;
          window.jsonpGet("Login/gt_session_id", {}, 'gt_session_id', function (json) {
            if (json.code == 1) {
              var data = json.data;
              $('.captcha img').attr('src', util.server + '/captcha.html' + '?' + 'id=' + Math.random()+'&session_id='+data.session_id);
              _this.session_id = data.session_id;
            }
          });
            // $('#code').change(function () {
            //     var code = $(this).children('option:selected').val();
            //     $('.code-num').text('+' + code);
            // })
            // this.getCountry();
        },

        // getCountry: function () {
        //     $('.register select').change(function () {
        //         $('.register .country-code').text('+' + $(this).val());
        //     });
        //     window.jsonpGet('login/country', {}, 'register', $.proxy(function (json) {
        //         var option = '';
        //         var lang = window.lang.get();
        //         $.each(json.data, function (key, val) {
        //             var names = val.name.split('|');
        //             option += '<option value="' + val.code + '">' + (lang == 'en' ? names[1] : names[0]) + '</option>';
        //         });
        //         $("select[name=code]").html(option);
        //     }, this));
        // },

        get_Code: function (e) {
            if ($(e.currentTarget).hasClass('sent')) {
                return;
            }
            var mobile = $.trim($('input[name="mobile"]').val()),
                validate_code = $.trim($('input[name="validate_code"]').val());

            // if (!code) {
            //     $('select[name="code"]').focus();
            //     window.toast('请选择国家');
            //     return;
            // }
            if (!mobile) {
                $('input[name="mobile"]').focus();
                window.toast('请输入手机号');
                return;
            }

            if (!validate_code) {
                $('input[name="validate_code"]').focus();
                window.toast('请输入图形验证码');
                return;
            }
            var _this = this;
            window.jsonpGet('Login/get_forget_sms', {
                // code: code,
                mobile: mobile,
                validate_code: validate_code,
              session_id: _this.session_id
            }, '', function (json) {
                if (json.code == 1) {
                    var t_find = 120;
                    window.intervals.push(window.setInterval(function () {
                        if (t_find > 0) {
                            $('#get_Code').addClass('sent').text(window.lang.get() == 'en' ? 'resend after ' + t_find + 's' : t_find + 's重发');
                            t_find--;
                        } else {
                          $('.captcha img').attr('src', util.server + '/captcha.html' + '?' + 'id=' + Math.random()+'&session_id='+data.session_id);
                            $('#get_Code').removeClass('sent').text(window.lang.get() == 'en' ? 'Get SMS code' : '获取验证码');
                            window.clearTimeout(window.intervals[window.intervals.length - 1])
                        }
                    }, 1000));

                    // $('select[name=code]').attr('disabled', true);
                    $('input[name=mobile]').prop('readonly', true);
                    action = json.data.action;
                    // _this.session_id = json.data.session_id;
                    window.toast(json.msg);
                } else {
                  $('.captcha img').attr('src', util.server + '/captcha.html' + '?' + 'id=' + Math.random()+'&session_id='+data.session_id);
                    window.toast(json.msg);
                }
            });
        },

        reset: function () {
            var mobile = $.trim($('input[name=mobile]').val()),
                verify = $.trim($('input[name=verify]').val()),
                pwd1 = $.trim($('input[name=pwd1]').val()),
                pwd2 = $.trim($('input[name=pwd2]').val());

            if (!mobile) {
                $('input[name=mobile]').focus();
                window.toast('请输入手机号码');
                return
            }
            if (!verify) {
                $('input[name=code]').focus();
                window.toast('请输入短信验证码');
                return
            }
            var preg = /(?=.*[0-9])(?=.*[a-zA-Z]).{6,16}/;
            if (!preg.test(pwd1)) {
                window.toast('请设置6-16位密码为字母加数字组合');
                return;
            }
            if (!pwd1) {
                $('input[name=pwd1]').focus();
                window.toast('请输入密码');
                return
            }
            if (!pwd2) {
                $('input[name=pwd2]').focus();
                window.toast('确认密码');
                return
            }
            if (pwd1 !== pwd2) {
                $('input[name=pwd2]').focus();
                window.toast('两次密码输入不一致');
                return
            }
             var _this = this;
            window.jsonpGet('Login/forget', {
                mobile: mobile,
                code: verify,
                password: hex_md5(pwd2),
                action: action,
                session_id :_this.session_id
            }, '', function (json) {
                if (json.code == '1') {
                    window.alert('重置密码成功，使用新密码登录?', function () {
                        Backbone.history.navigate('login', {trigger: true});
                    });
                } else {
                    window.toast(json.msg);
                }
            });
        }

    });
    return View;
});


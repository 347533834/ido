define(function (require, exports, modules) {
    'use strict';

    require('css!assets/css/login/login.css');
    require('assets/plugins/md5.js');
    var template = require('text!./register.tpl.html'),
        action = '',
        util = require('util');

    var View = Backbone.View.extend({
        el: '#page',
        events: {

            'click .captcha': function () {
                // $('.captcha img').attr('src', util.server + '/captcha.html' + '?' + 'id=' + Math.random())
              $('.captcha img').attr('src', util.server + '/captcha.html' + '?' + 'id=' + Math.random()+'&session_id='+this.session_id)
            },

            'click .login-1': function () {
                Backbone.history.navigate('login', {trigger: true});
                // if(this.flat) {
                //     Backbone.history.navigate('login', {trigger: true});
                // }else{
                //     window.alert('请先下载APP，再进行登录操作',function () {
                //         Backbone.history.navigate('down', {trigger: true});
                //     });
                // }
            },

            'click #verificationCode': "verificationCode",  //短信验证码
            'click .register button': 'register',  //注册

            'click p i': function (e) {
                $(e.currentTarget).toggleClass('active');
            },

        },
        initialize: function () {
            var langs = {
                'en': {
                    mobile_placeholder: 'Mobile phone',
                    password_placeholder: 'password',
                    password_confirm_placeholder: 'Confirm password',
                    username_placeholder: 'Username',
                    invite_placeholder: 'Invitation code (required)',
                    verify_placeholder: 'SMS verification code',
                    sms_code: 'Get SMS code',
                    service_agreement: 'I have READ and AGREED <span>MRC AGREEMENT</span>.',
                    have_account: 'Already have an account, Sign in.',

                    register_button: 'SIGN UP',
                    reset_link: 'Forgot password',
                    register_link: 'Sign up',


                    toast_country: 'Please select your Country!',
                    toast_mobile: 'Please enter your mobile phone number!',
                    toast_password: 'Please enter your login password!',
                    toast_password_length: 'Please set 6-16 bit password for the combination of letters and Numbers!',
                    toast_username: 'Please enter your username!',
                    toast_username_length: 'Please enter the Chinese 2-5 bit, English 2-8 bit username!',
                    toast_verify: 'Please enter you SMS code!',

                    toast_invite: 'Please enter invite code!',
                    toast_agreed: 'Please confirm reading and agree to MRC AGREEMENT!'
                },
                'cn': {
                    mobile_placeholder: '请输入手机号码',
                    password_placeholder: '设置登录密码',
                    password_confirm_placeholder: '确认登陆密码',
                    username_placeholder: '设置用户名',
                    invite_placeholder: '输入邀请码（必填）',
                    verify_placeholder: '输入短信验证码',
                    sms_code: '获取验证码',
                    service_agreement: '我已阅读并同意<span>《MRC用户使用协议》。</span>',
                    have_account: '已有账号，直接登录',

                    register_button: '注册',
                    reset_link: '忘记密码',
                    register_link: '注册新用户',

                    toast_country: '请输选择国家',
                    toast_mobile: '请输入手机号！',
                    toast_password: '请输入密码！',
                    toast_password_length: '请输入登录密码！',
                    toast_password_confirm: '两次密码输入不一致！',
                    toast_username: '请输入用户名',
                    toast_username_length: '请输入中文2-5位，英文2-8位的用户名！',
                    toast_verify: '请输入短信验证码！',
                    toast_invite: '请输入邀请码！',
                    toast_agreed: '请确认阅读并同意使用协议！'
                }
            };

            this.data = {
                lang: langs[window.lang.get()]
            };

            this.template = _.template(template);
            this.render();
        },
        render: function () {
            this.$el.html(this.template(), this.data);
            // this.getCountry();
            this.inviteValue = util.queryString('invite');
            if (this.inviteValue) {
                $('input[name=invite]').val(this.inviteValue).attr('readonly', 'readonly');
            }

            this.flat = true;
            if (typeof (device) != 'undefined') {
                var platform = device.platform.toLowerCase();
                if (platform == 'browser') {
                    this.flat = false;
                }
            }

            // $('.captcha img').attr('src', util.server + '/captcha.html' + '?' + 'id=' + Math.random());


            var is_weixin = function () {
                return /MicroMessenger/.test(navigator.userAgent);
            }

            var winHeight = typeof window.innerHeight != 'undefined' ? window.innerHeight : document.documentElement.clientHeight;
            var weixinTip = $('<div id="weixinTip"><p><img style="width: 100%;" src="assets/img/public/ts.png" alt="微信打开"/></p></div>');

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

          this.session_id = '';
          var _this = this;
          window.jsonpGet("Login/gt_session_id", {}, 'gt_session_id', function (json) {
            if (json.code == 1) {
              var data = json.data;
              $('.captcha img').attr('src', util.server + '/captcha.html' + '?' + 'id=' + Math.random()+'&session_id='+data.session_id);
              _this.session_id = data.session_id;
            }
          });

        },

        verificationCode: function (e) {
            if ($(e.currentTarget).hasClass('sent')) {
                return;
            }
            var mobile = $.trim($('input[name="mobile"]').val()),
                password = $.trim($('input[name="password"]').val()),
                validate_code = $.trim($('input[name="validate_code"]').val());

            if (!mobile) {
                window.toast('请输入手机号');
                return;
            }
            if (!password) {
                window.toast('请输入密码');
                return;
            }
            var preg = /(?=.*[0-9])(?=.*[a-zA-Z]).{6,16}/;
            if (!preg.test(password)) {
                window.toast('请设置6-16位密码为字母加数字组合');
                return;
            }

            if (!validate_code) {
                window.toast('请输入图形验证码');
                return;
            }

            var _this = this;
            window.jsonpGet('Login/gt_reg_sms', {
                mobile: mobile,
                validate_code: validate_code,
                session_id: _this.session_id
            }, '', function (json) {
                if (json.code == 1) {
                    var t_find = 120;
                    window.intervals.push(window.setInterval(function () {
                        if (t_find > 0) {
                            $('#verificationCode').addClass('sent').text(window.lang.get() == 'en' ? 'resend after ' + t_find + 's' : t_find + 's重发');
                            t_find--;
                        } else {
                            $('#validate_code').val('');
                            // $('.captcha img').attr('src', util.server + '/captcha.html' + '?' + 'id=' + Math.random());
                          $('.captcha img').attr('src', util.server + '/captcha.html' + '?' + 'id=' + Math.random()+'&session_id='+_this.session_id);
                            $('#verificationCode').removeClass('sent').text(window.lang.get() == 'en' ? 'Get SMS code' : '获取验证码');
                            window.clearTimeout(window.intervals[window.intervals.length - 1])
                        }
                    }, 1000));

                    $('select[name=code]').attr('disabled', true);
                    $('input[name=mobile]').prop('readonly', true);
                    action = json.data.action;
                    // _this.session_id = json.data.session_id;
                    window.toast(json.msg);
                } else {
                    // $('.captcha img').attr('src', util.server + '/captcha.html' + '?' + 'id=' + Math.random());
                  $('.captcha img').attr('src', util.server + '/captcha.html' + '?' + 'id=' + Math.random()+'&session_id='+_this.session_id);
                    window.toast(json.msg);
                }
            });
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

        register: function () {
            var
                _this = this ,
              // code = $.trim($('select[name="code"]').val()),
                mobile = $.trim($('input[name="mobile"]').val()),
                password = $.trim($('input[name="password"]').val()),
                // username = $.trim($('input[name="username"]').val()),
                invite = $.trim($('input[name="invite"]').val()),
                // validate_code = $.trim($('input[name="validate_code"]').val()),
                verify = $.trim($('input[name="verify"]').val());

            // if (!code) {
            //     // $('select[name="code"]').focus();
            //     window.toast('请选择国家');
            //     return;
            // }
            if (!mobile) {
                // $('input[name="mobile"]').focus();
                window.toast('请输入手机号');
                return;
            }
            if (!password) {
                // $('input[name="password"]').focus();
                window.toast('请输入密码');
                return;
            }
            var preg = /(?=.*[0-9])(?=.*[a-zA-Z]).{6,16}/;
            if (!preg.test(password)) {
                // $('input[name=password]').focus();
                window.toast('请设置6-16位密码为字母加数字组合');
                return;
            }
            // if (!username) {
            //     // $('input[name="username"]').focus();
            //     window.toast('请输入用户名');
            //     return;
            // }
            // if (username.length > 8 || username.length < 2) {
            //     // $('input[name=username]').focus();
            //     window.toast('请输入中文2-5位，英文2-8位的用户名！');
            //     return;
            // }
            // if (!invite) {
            //     // $('input[name="invite"]').focus();
            //     window.toast('请输入邀请码');
            //     return;
            // }
            // if (!validate_code) {
            //     // $('input[name="validate_code"]').focus();
            //     window.toast('请输入图形验证码');
            //     return;
            // }

            if (!verify) {
                // $('input[name=verify]').focus();
                window.toast(this.data.lang.toast_verify);
                return
            }

            if (!$('p i').hasClass('active')) {
                window.toast('请确认阅读并同意使用协议');
                return
            }
              // if(!window.filter(username,$('input[name="username"]'))){
              //   return false;
              // }
            var _this = this;
            window.jsonpGet("login/register", {
                mobile: mobile,
                // code: code,
                password: hex_md5(password),
                // username: username,
                invite: invite,
                verify: verify,
                action: action,
                // validate_code: validate_code,
                session_id :_this.session_id
            }, 'register', function (json) {
                if (json.code == 1) {
                    var data = json.data ,
                        msg = json.msg,
                        yes = data.yes,
                        no = data.no;
                    if(!_this.flat){
                        msg = data.down_msg;
                        yes = data.down_yes;
                        no = data.down_no;
                    }
                    window.confirm(msg, function () {
                        Backbone.history.navigate(yes, {trigger: true});
                    }, function () {
                        if (data.no) {
                            Backbone.history.navigate(no, {trigger: true});
                        } else {
                            location.reload();
                        }
                    });
                } else {
                    window.toast(json.msg);
                }

            });
        }

    });
    return View;
});


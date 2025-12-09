define(function (require, exports, modules) {
    'use strict';

    require('css!assets/css/work-list/work-list.css');
    require('assets/plugins/lrz/lrz.min.js');
    require('css!assets/plugins/page/pagination.css');

    var template = require('text!./work-list.tpl.html'),
        util = require('util');
    var imgs={};
    var is_add=true;
    var View = Backbone.View.extend({
        el: '#page',
        events: {
            // 'click .captcha': function () {
            //     $('#img2').attr('src', util.server + '/captcha.html' + '?' + 'id=' + Math.random())
            // },
            'click #btn': function () {
                if(is_add){
                    is_add = false;
                    this.changeWort();
                }
            },
        },
        initialize: function () {
            var langs = {
                'en': {
                    'First': 'First',
                    'Last': 'Last',
                    'Prev': 'Prev',
                    'Next': 'Next',
                    'Submit': 'Submit ',

                    'subject':'Please fill in the subject',
                    'description':'Please fill in the description',
                    'fill':'Please fill in the contact number',
                    'number':'number',
                    'time':'Time',
                    'operation':'Operation',
                    'work_order':'Work order',
                    'feedback':'Problem feedback',
                    'motivation':'Welcome feedback question, your opinion and suggestion is our motivation!',
                    'Suggestions':'We will carefully check each question you feedback, and reply to you as soon as possible. Here you can put forward the questions you have encountered, and also publish your own Suggestions and ideas',
                    'theme':'Theme',
                    'describe':'Describe',
                    'Problem':'Problem screenshots',
                    'screenshot':'Please upload the problem screenshot',
                    'contact':'contact number',
                    'Feedback':'Feedback record',
                    'No_record':'No record',
                    'details':'Details',
                    'Verification':'Verification Code',
                    'code':'Please input verification code',
                    'questions':'Please fill in the questions you encountered',
                    'gongdan':'Your work order has been submitted',
                },
                'cn': {
                    'First': '首页',
                    'Last': '末页',
                    'Prev': '上页',
                    'Next': '下页',
                    'Submit': '提交',

                    'subject':'请填写主题',
                    'description':'请填写描述内容',
                    'fill':'请填写联系电话',
                    'number':'编号',
                    'time':'时间',
                    'operation':'操作',
                    'work_order':'工单中心',
                    'feedback':'问题反馈',
                    'motivation':'欢迎反馈问题,您的意见与建议就是我们的动力!',
                    'Suggestions':'我们会认真查阅您反馈的每一个问题,并尽快给您答复,在这里您可以提出遇到的问题,也可以发表自己的建议和想法',
                    'theme':'主题',
                    'describe':'描述',
                    'Problem':'问题截图',
                    'screenshot':'请上传问题截图',
                    'contact':'注册手机号',
                    'Feedback':'反馈记录',
                    'No_record':'暂无记录',
                    'details':'详情',
                    'Verification':'验证码',
                    'code':'请输入验证码',
                    'questions':'请填写您遇到的问题',
                    'gongdan':'您的工单已提交',
                }
            };

            this.data = {
                lang: langs[window.lang.get()],
                toLang: window.lang.get() == 'en' ? 'cn' : 'en',
            };
            this.render();
        },
        render: function () {
            this.$el.append(_.template(template, this.data));
            $('header span').text(this.data.lang.feedback);
            $('header .ok').text(this.data.lang.Feedback).click((function () {
                Backbone.history.navigate('feedback-record', {trigger: true});
            }));
            this.loadUser();
            // $('#img2').attr('src', util.server + '/captcha.html' + '?' + 'id=' + Math.random());

        },
        loadUser: function () {
            this.user = window.user.get();
            if (!this.user) {
                Backbone.history.navigate('login', {trigger: true});
                return;
            }
            this.inputChange();
          //  this.user_work(1);
        },
        inputChange:function(e){
            $('input[type="file"]').change(function () {
                var _this = $(this);
                var obj = $(this)[0].files[0];
                var exts = obj.name.split('.');
                var ext = exts[exts.length - 1];
                if (['jpg', 'gif', 'png', 'jpeg'].indexOf(ext.toLowerCase()) == -1) {
                    window.toast(ext.toLowerCase() + this.data.lang.Image_error);
                    return;
                }

                lrz(obj, {
                    width: 500
                }).then(function (rst) {
                    $('#img1').attr('src', rst.base64);
                    imgs[_this.attr('name')] = rst.base64;
                });
            });
        },
        changeWort:function () {
            var title = $.trim($('input[name="title"]').val()),
                mobile = $.trim($('input[name="mobile"]').val()),
                content = $.trim($('#content').val()),
                validate_code = $.trim($('input[name="validate_code"]').val()),

                _this = this;

            if (title.length == 0 || title == 0) {
                is_add=true;
                window.toast(this.data.lang.subject);
                return;
            }

            if (content.length == 0 || content == 0) {
                is_add=true;
                window.toast(this.data.lang.description);
                return;
            }
/*            if (validate_code.length == 0 || validate_code == 0) {
                is_add=true;
                window.toast(this.data.lang.code);
                return;
            }*/
            if (!mobile) {
                is_add=true;
                window.toast(this.data.lang.fill);
                return;
            }

           if(!window.filter(title,$('input[name="title"]'))){
                is_add = true;
                return false;
            }

            if(!window.filter(content,$('#content'))){
                is_add = true;
                return false;
            }

            if(!window.filter(mobile,$('input[name="mobile"]'))){
                is_add = true;
                return false;
            }
            console.log(typeof(validate_code));
            window.jsonpPost(util.server + "upload/work?token=" + encodeURIComponent(_this.user.token), {
                title:title,
                content:content,
                mobile:mobile,
                validate_code:validate_code,
                imgs: JSON.stringify(imgs),
                session_id: _this.user.session_id,

            }, 'work', function (json) {

                if (json.code == 1) {
                    is_add=true;
                   // window.toast(json.msg);
                    $('.box-mask').show();
                    setTimeout(function () {
                        window.location.reload();
                    },1200);
                }else{
                    is_add=true;
                    // $('#img2').attr('src', util.server + '/captcha.html' + '?' + 'id=' + Math.random());
                    window.toast(json.msg);
                }

            });
        },


    });
    return View;
});





































































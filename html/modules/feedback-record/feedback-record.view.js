define(function (require, exports, modules) {
    'use strict';

    require('css!assets/css/feedback-record/feedback-record.css');
    require('assets/plugins/jquery/jquery.qrcode.min');

    var action = '';
    var template = require('text!./feedback-record.tpl.html'),
        util = require('util');
    var View = Backbone.View.extend({
        el: '#page',
        events: {
            'click .more': function () {

                this.user_work(this.open_orders_page);
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
                    'contact':'联系电话',
                    'Feedback':'反馈记录',
                    'No_record':'暂无记录',
                    'details':'详情',
                    'Verification':'验证码',
                    'code':'请输入验证码',
                    'questions':'请填写您遇到的问题',

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
           $('header span').text('反馈记录');


            this.loadUser();
        },
        loadUser: function () {
            this.user = window.user.get();
            if (!this.user) {
                Backbone.history.navigate('login', {trigger: true});
                return;
            }
            this.open_orders_page = 1;
            this.user_work(this.open_orders_page);

        },
        user_work:function (p) {
            var page = p ? p : 1;
            var _this=this;
            window.jsonpGet('user/user_work', {token:this.user.token,page:page}, 'user_work', function (json) {
                if (json.code == 1) {
                    var data = json.data.data;
                    var str = '';
                    for (var i in data) {
                        var dt = new Date(data[i].addtime * 1000);
                        if (i != 'randomSort') {
                            str += '<li><p class="clearfix"><span class="left">编号：<span>' + data[i].work_id + '</span> </span> <span class="right"  data-id="' + data[i].work_id + '"><span>详情</span><i class="ARROW">></i></span></p><div><h1>主题：<span>' + data[i].title + '</span></h1><p>提交时间：<span>' + dt.format('yyyy-mm-dd hh:ii') + '</span></p></div></li>'
                        }
                    }


                    $('#ul').append(str);
                    $('.right').click(function () {
                        var work_id = $(this).data('id');
                        Backbone.history.navigate('feedback-record-details?work_id=' + work_id, {trigger: true});
                    });
                    if (json.data.current_page >= json.data.last_page) {
                        /*  console.log(json.data.current_page);
                          console.log(json.data.last_page);*/
                        $('.more').hide();
                    } else {
                        $('.more').show();
                    }
                    _this.open_orders_page++;
                }else {
                    $('.no_data').show();

                }
            });
        }

    });
    return View;
});
























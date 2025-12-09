define(function (require, exports, modules) {
    'use strict';

    require('css!assets/css/feedback-record-details/feedback-record-details.css');
    require('assets/plugins/jquery/jquery.qrcode.min');
    require('assets/plugins/lrz/lrz.min.js');
    var action = '';
    var template = require('text!./feedback-record-details.tpl.html'),
        util = require('util');
    var is_add=true;
    var View = Backbone.View.extend({
        el: '#page',
        events: {
             'click .img-box img': function () {
                 $(".img-box img").toggleClass("img-up-big");
             },
            'click #btn': function () {
                if(is_add){
                    is_add = false;
                    this.reply();
                }
            },
            'click .more': function () {
                this.log_work(this.open_orders_page);
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
                    'work_order':'Work order',
                    'theme':'Theme',
                    'describe':'Describe',
                    'Problem':'Problem screenshots',
                    'reply':'Please fill in the reply',
                    'details':'Details'
                },
                'cn': {
                    'First': '首页',
                    'Last': '末页',
                    'Prev': '上页',
                    'Next': '下页',
                    'Submit': '提交',
                    'work_order':'工单中心',
                    'theme':'主题',
                    'describe':'描述',
                    'Problem':'问题截图',
                    'reply':'请填写回复内容',
                    'details':'详情'



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
            this.log_work(this.open_orders_page);
        },

        reply: function () {

            var content = $.trim($('#text').val());
            var work_id = util.queryString('work_id');
            if (content.length == 0 || content == 0) {
                is_add=true;
                window.toast(this.data.lang.reply);
                return;
            }

            if(!window.filter(content,$('#content'))){
                is_add = true;
                return false;
            }

            var _this = this;
            window.jsonpGet('user/reply', {token: _this.user.token,work_id: work_id,content: content}, 'reply', function (json) {
                if (json.code == 1) {
                    is_add=true;
                    window.toast(json.msg);
                    setTimeout(function () {
                        window.location.reload();
                    },1200);
                }else{
                    is_add=true;
                    window.toast(json.msg);
                }
            });

        },

        log_work:function (p) {
            var page = p ? p : 1;
            var work_id = util.queryString('work_id');
            var _this=this;
            window.jsonpGet('user/log_work', {token:this.user.token,work_id:work_id,page:page}, 'log_work', function (json) {
                if (json.code == 1) {
                    var data = json.data.log.data;
                    var work = json.data.work;
                    var str = '';

                    $('#p1').text(work.title);
                    $('#box').text(work.content);
                    if(work.img){
                        $('.img-box').html('<div class="img-box"><img src="'+ util.cdn+work.img+'?'+work.datatime +'"></div>');
                    }
                        for (var i in data) {
                            var dt = new Date(data[i].addtime * 1000);
                            if (i != 'randomSort') {
                                str +='<div class="customer d-b"><p>'+data[i].name+'</p><div>'+data[i].content+'</div><p>'+dt.format('mm-dd hh:ii')+'</p></div>';

                        }
                    }

                    $('.service').append(str);
                    if (data.length == json.data.pageSize) {
                        $('.more').show();
                    } else {
                        $('.more').hide();
                    }
                    _this.open_orders_page++;
                }else {
                    $('.no_data').show();


                }
            })
        }

    });
    return View;
});
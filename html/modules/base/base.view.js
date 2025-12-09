/**
 * @Description 登陆view
 * @author lirenyi
 * @update 2016-06-10
 */
define(function (require, exports, modules) {
    'use strict'

    require('css!assets/plugins/layer/need/layer.css')
    require('assets/plugins/layer/layer.js')

    // require('assets/plugins/fastclick.js')
    var template = require('text!./base.tpl.html'),
        footer = require('text!../component/footer.html'),
        header = require('text!../component/header.html'),
        util = require('util')

    var View = Backbone.View.extend({
        el: '#app',
        events: {
            'click .header .header-back': function () {
                window.history.back()
            },
            'click .back': function () {
                history.go(-1);
            },
            'click .footer .tab-nav li': function (e) {
                var path = $(e.currentTarget).attr('data-path');

                var user = window.user.get();
                if (user) {
                    window.user.set(user);
                    window.storage.set('user', user);
                }

                Backbone.history.navigate(path, {trigger: true});
            },
            'click .out-url': 'jumpURL',
        },

        initialize: function () {
            this.template = _.template(template);
            this.render();

        },
        render: function () {
            this.$el.html(this.template())

            this.version();
            this.user = window.user.get();
            // window.toast(util.currentHash())
            if (util.currentHash && $.inArray(util.currentHash, ['user', 'down', 'loading', 'login', 'register']) == -1)
                this.$el.prepend(_.template(header));
            // 'loading', 'down',

            if (util.currentHash && $.inArray(util.currentHash, ['invitation', 'information', 'certification', 'reset-password', 'modify-nickname', 'nickname', 'record', 'opinion', 'service', 'set-password', 'change-password', 'changeWechat', 'changeAlipay', 'security', 'bonus', 'team', 'teams', 'bill', 'extract', 'historical-order', 'historical-order2', 'my-commission', 'my-commission2', 'market_lists', 'buy_market', 'notice', 'down', 'loading', 'login', 'findpwd', 'register', 'notice-details', 'bonus-details', 'work-list', 'feedback-record', 'feedback-record-details']) == -1)

                this.$el.append(_.template(footer));

            if (util.currentHash && $.inArray(util.currentHash, ['coin', 'coin2', 'trade']) != -1)
                var currTab = this.$el.find('.footer .tab-nav .coin-link');
            else if (util.currentHash && $.inArray(util.currentHash, ['out-url']) != -1)
                var currTab = this.$el.find('.footer .tab-nav .otc-link');
            else
                var currTab = this.$el.find('.footer .tab-nav .' + util.currentHash + '-link');
            if (currTab.length > 0) {
                if (!currTab.hasClass('active')) {
                    currTab.addClass('active').siblings().removeClass('active');
                }
            } else {
                this.$el.find('.footer .tab-nav .nav-home').addClass('active');
            }


            for (var i in window.timeouts) {
                window.clearTimeout(window.timeouts[i]);
            }

            for (var i in window.intervals) {
                window.clearInterval(window.intervals[i]);
            }

            var token = this.user.token;
            if (!token) return;

            if (window.config.get()) return;
            window.jsonpGet('index/config', {token: this.user.token}, 'config', function (json) {
                if (json.code == 1) {
                    // var config = window.config.get();
                    // if (config && config.version == json.data.version)
                    //     return false;

                    window.config.set(json.data);
                    console.log('config loaded!');
                } else {
                    window.toast('系统配置加载失败，请关闭应用后重试！');
                }
            }, null, true);
            /*弹出提示*/

            // var user = window.user.get();
            // console.log(user);
            // console.log(user.token);
            // var time =new Date(user.addtime*1000).format('yyyy-m-d h:i:s');
            // console.log(time);
            // var asd =  new Date();
            // console.log(asd);
            // var year = asd.getFullYear();
            // var month = asd.getMonth()+1;
            // var day = asd.getDate();
            // var todaytime = year + "-" + month + "-" + day;
            // console.log(todaytime);
            // //console.log(util.isNull(user.token));
            // if(!util.isNull(user.token) && todaytime !== user.today){
            //     window.jsonpGet('home/sign', {token:user.token}, '', function (json) {
            //         console.log(json)
            //         if (json.code == 1) {
            //             user.today = todaytime;
            //             window.user.set(user);
            //             if(json.msg != '已签到'){
            //               window.alert(json.msg)
            //             }
            //         }
            //     })
            // }

        },

        version: function () {
            var _this = this;
            if (typeof (device) == 'undefined') {
                return;
            }
            var platform = device.platform.toLowerCase();
            if (platform == 'browser') {
                return;
            }

            cordova.getAppVersion.getVersionNumber().then(function (version) {
                window.jsonpGet('login/app', {t: new Date()}, 'version', function (json) {
                    // console.log(json);
                    if (json.code != 1) {
                        return;
                    }

                    var data;
                    switch (platform) {
                        case 'android':
                            data = json.data.android;
                            break;
                        case 'ios':
                            var data = json.data.ios;
                            break;
                    }

                    if (util.version_compare(version, data.version)) {
                        window.alert('发现新版本：v' + data.version + '，立即更新？', function () {
                            cordova.InAppBrowser.open(data.download, '_system');
                            if (navigator.app) navigator.app.exitApp();
                            else navigator.device.exitApp();
                        })
                    }
                });
            });
        },
        jumpURL: function (e) {
            var url = encodeURIComponent($(e.currentTarget).data('jump'));

            if (url == '') {
                return;
            }
            if (url.indexOf('http') == 0) {
                var title = encodeURIComponent($(e.currentTarget).data('title'));
                Backbone.history.navigate('out-url?title=' + title + '&url=' + url, {trigger: true})
            } else {
                Backbone.history.navigate(url, {trigger: true})
            }
        }
    });

    return View
})

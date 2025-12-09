define(function (require, exports, modules) {
    'use strict'

    require('css!assets/css/loading/loading.css');
    require('css!assets/plugins/swiper/swiper-3.4.2.min.css');
    require('assets/plugins/swiper/swiper-3.4.2.min');

    var template = require('text!./loading.tpl.html'),
        util = require('util');
    var View = Backbone.View.extend({
        el: '#page',
        events: {
            'click .btn-toLogin': function () {
                Backbone.history.navigate('login', {trigger: true})
            },
        },
        initialize: function () {
            this.template = _.template(template)
            this.render()
        },
        render: function () {
            this.$el.html(this.template())

            this.user = window.user.get();
            this.loading();
            // this.chkToken();
        },
        loading: function () {
            window.jsonpGet('login/loading', null, 'login', function (json) {
                if (json.code == 1) {
                    var data = json.data;
                    if (data.length > 0) {
                        var str = '';
                        for (var i = 0; i < data.length; i++) {
                            str += '<div class="swiper-slide">' + (i == data.length - 1 ? '<div class="btn-toLogin">立即体验</div>' : '') + '<img src="' + util.cdn + data[i].url + '?' + data[i].addtime + '" alt="' + i + '" style="width:100%;display: block;"  data-jump="' + data[i].jump_url + '" data-title="' + data[i].title + '" /></div>';
                        }
                        $('div.swiper-wrapper').append(str);

                        var mySwiper = new Swiper('.swiper-container', {
                            direction: 'horizontal',
                            // 如果需要分页器
                            pagination: '.swiper-pagination',
                        });
                    }
                }
            });
          
        },

      chkToken: function () {
        var _this = this;

        var token = util.queryString('token');
        if (token) {
          window.jsonpGet('login/app_login', {token: token}, 'app_login', $.proxy(function (json) {
              if (json.code == '1') {
                var data = json.data;
                if (data == null || data.length == 0) {
                  window.alert('有错误发生！');
                  return
                }
                window.storage.set('user', data); // 保存user到cookie
                window.storage.set('mobile', data.mobile); // 保存登录手机号到cookie
                Backbone.history.navigate('home', {trigger: true})
              } else {
                window.alert(json.msg)
              }
            }, this)
          );
        } else {
          var user = this.user;
          if (user) {
            window.jsonpGet('user/user_refresh', {token: user.token}, 'user_refresh', $.proxy(function (json) {
              if (json.code == 1) {
                window.user.set(json.data);
                Backbone.history.navigate('index', {trigger: true});
              } else {
                _this.loading();
              }
            }, this));
          } else {
            _this.loading();
          }
        }
      },
    });

    return View;
})

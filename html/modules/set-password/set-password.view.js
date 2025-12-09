define(function (require, exports, modules) {
  'use strict';

  require('css!assets/css/set-password/set-password.css');
  require('assets/plugins/md5.js');
  var template = require('text!./set-password.tpl.html'),
    action='',
    is_add=true,
    util = require('util');

  var View = Backbone.View.extend({
    el: '#page',
    events: {
      'click button': function () {
        if (is_add) {
          is_add = false;
          this.editMarketPwd();
        }
      },
      'click .verification-code': 'getCode',
    },
    initialize: function () {
      this.template = _.template(template);
      this.render();
    },
    render: function () {
      this.$el.html(this.template());
      $('header span').text("设置资金密码");
      this.loadUser();
    },
    loadUser: function () {
      this.user = window.user.get();
      if (!this.user) {
        Backbone.history.navigate('login', {trigger: true});
        return;
      }
    },

    getCode: function () {
      if ($('.verification-code').hasClass('sent')) return;

      var t_find = 120;
      var _this = this;
      var handlerPopupMobile = function () {

        jsonpGet("user/marketPwd_sms", {
          token: _this.user.token,
        }, 'marketPwd_sms', function (res) {
          if (res.code == 1) {
            if(res.data){

              window.intervals.push(window.setInterval(function(){
                if (t_find > 0) {
                  $('.verification-code').addClass('sent').text(t_find + 'S重发');
                  t_find--;
                } else {
                  $('.verification-code').removeClass('sent').text('获取验证码');
                  window.clearTimeout(window.intervals[window.intervals.length-1])
                }
              }, 1000));
            }
            action = res.data;
            window.toast(res.msg);
          } else {
            window.toast(res.msg);
          }
        });
      };
      handlerPopupMobile();
    },

    editMarketPwd:function(){
      var _this = this;
      var newpswd = $('input[name="pwd"]').val();
      var newpswd2 = $('input[name="pwdAgain"]').val();
      var code = $('input[name="code"]').val();

      var preg = new RegExp(/^\d{6}$/);
      if(!preg.test(newpswd)){
        is_add=true;
        window.toast('请输入六位数字密码');
        return;
      }

      if (newpswd != newpswd2) {
        is_add=true;
        window.toast('两次密码输入不一致');
        return;
      }

      if (action == '') {
        is_add=true;
        window.toast('请获取验证码');
        return;
      }

      if(!code || code.length==0){
        is_add=true;
        window.toast('请输入验证码');
        return;
      }

      var data ={
        token: _this.user.token,
        code:code,
        newpswd:hex_md5(newpswd),
        action:action
      }
      window.jsonpGet('user/edit_marketPwd', data, 'edit_marketPwd', function (json) {
        if (json.code == 1) {
          is_add=true;
          window.toast(json.msg);
          setTimeout(function () {
            Backbone.history.navigate('user', {trigger: true});
          },1500);

        }else{
          is_add=true;
          $('input[name="pwd"]').val('');
          $('input[name="pwdAgain"]').val('');
          window.toast(json.msg);
        }

      });
    }

  });
  return View;
});


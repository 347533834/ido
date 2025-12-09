define(function (require, exports, modules) {
    'use strict';

    require('css!assets/css/change-password/change-password.css');
    require('assets/plugins/md5.js');
    var template = require('text!./change-password.tpl.html'),
        is_add=true,
        util = require('util');

    var View = Backbone.View.extend({
        el: '#page',
        events: {
          'click button': function () {
            if (is_add) {
              is_add = false;
              this.modifyPwd();
            }
          }
        },
        initialize: function () {
            this.template = _.template(template);
            this.render();
        },
        render: function () {
            this.$el.html(this.template());
            $('header span').text("修改登录密码");
          this.loadUser();
        },
      loadUser: function () {
        this.user = window.user.get();
        if (!this.user) {
          Backbone.history.navigate('login', {trigger: true});
          return;
        }
      },

      modifyPwd: function () {
        var password = $('input[name="originalPwd"]').val();
        var newpswd = $('input[name="newPwd"]').val();
        var newpswd2 = $('input[name="againPwd"]').val();

        if(!password){
          is_add = true;
          window.toast('原始密码不能为空');
          return;
        }

        if(password.length < 6){
          is_add = true;
          window.toast('原始密码格式错误');
          return;
        }

        var preg = /(?=.*[0-9])(?=.*[a-zA-Z]).{6,16}/;
        if(!preg.test(newpswd)){
          is_add = true;
          window.toast('请输入6-16位密码为字母加数字组合');
          return;
        }

        if (newpswd != newpswd2) {
          is_add = true;
          window.toast('两次密码输入不一致');
          return;
        }

        var data ={
          token:this.user.token,
          password:hex_md5(password),
          newpswd:hex_md5(newpswd),
        }

        window.jsonpGet('user/edit_password', data, 'edit_password', function (json) {
          if (json.code == 1) {
            is_add = true;
            window.toast(json.msg);
            setTimeout(function () {
              Backbone.history.navigate('user', {trigger: true});
            },1500);

          }else{
            is_add = true;
            $('input[name="originalPwd"]').val('');
            $('input[name="newPwd"]').val('');
            $('input[name="againPwd"]').val('');
            window.toast(json.msg);
          }

        });
      }

    });
    return View;
});


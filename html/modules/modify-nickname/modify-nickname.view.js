define(function (require, exports, modules) {
  'use strict';

  require('css!assets/css/modify-nickname/modify-nickname.css');
  var template = require('text!./modify-nickname.tpl.html'),
    is_add = true,
    util = require('util');

  var View = Backbone.View.extend({
    el: '#page',
    events: {

    },
    initialize: function () {
      this.template = _.template(template);
      this.render();
    },
    render: function () {
      this.$el.html(this.template());
      var _this=this;
      $('header span').text("修改昵称");
      $('header .ok').text("保存").click(function () {
        if(is_add){
          is_add = false;
          _this.edit_name();
        }
      });
      this.loadUser();

    },
    loadUser: function () {
      this.user = window.user.get();
      if (!this.user) {
        Backbone.history.navigate('login', {trigger: true});
        return;
      }
      $('#username').attr('placeholder',this.user.username);
    },

    edit_name: function () {

      var _this = this;
      var username = $.trim($('#username').val());
      if (!username) {
        is_add = true;
        $('input[name="username"]').focus();
        window.toast('请输入用户名');
        return;
      }
      if (username.length > 8 || username.length < 2) {
        is_add = true;
        $('input[name=username]').focus();
        window.toast('请输入中文2-5位，英文2-8位的用户名！');
        return;
      }
      if(!window.filter(username,$('input[name="username"]'))){
        is_add = true;
        return false;
      }
      $('.editor-nickname').hide();
      window.jsonpGet('user/edit_name', {token: this.user.token,username:username}, 'edit_name', function (json) {
        window.toast(json.msg);
        if(json.code==1){
          is_add = true;
          _this.user.username = json.data.username;
          window.user.set(_this.user);
          $('#username').attr('placeholder',_this.user.username);
          setTimeout(function () {
            Backbone.history.navigate('user', {trigger: true});
          },1500)
        }
      });
    }

  });
  return View;
});


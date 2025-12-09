define(function (require, exports, modules) {
  'use strict';

  require('css!assets/css/login/login.css');
  require('assets/plugins/md5.js');
  var template = require('text!./login.tpl.html'),
    util = require('util');

  var View = Backbone.View.extend({
    el: '#page',
    events: {
      'click .register-1': function () {
        Backbone.history.navigate('register', {trigger: true});
      },

      'click .findpwd-1': function () {
        Backbone.history.navigate('findpwd', {trigger: true});
      },


      'click .login button.denglu': 'toHome',

    },
    initialize: function () {
      this.template = _.template(template);
      this.render();
    },
    render: function () {
      this.$el.html(this.template());
      $('header span').text('');

      if (typeof (device) == 'undefined') {
        return;
      }
      var platform = device.platform.toLowerCase();
      if (platform == 'browser') {
        $('#down').show();
        return;
      }

    },

    toHome: function () {
      var mobile = $.trim($('input[name="mobile"]').val()),
        password = $.trim($('input[name="password"]').val());
      if (!mobile) {
        $('input[name=mobile]').focus();
        window.toast('请输入手机号码');
        return
      }

      if (!password) {
        $('input[name=password]').focus();
        window.toast('请输入登录密码');
        return
      }

      window.jsonpGet("login/login", {mobile: mobile, password: hex_md5(password)}, 'login', function (json) {
        if (json.code == 1) {
          var data = json.data;
          window.user.set(data); // 保存user到cookie
          Backbone.history.navigate('index', {trigger: true});
        } else {
          window.alert(json.msg);
        }
      });

    }

  });
  return View;
});


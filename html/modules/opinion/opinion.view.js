define(function (require, exports, modules) {
  'use strict';

  require('css!assets/css/opinion/opinion.css');
  require('assets/plugins/md5.js');
  var template = require('text!./opinion.tpl.html'),
    is_add = true,
    util = require('util');

  var View = Backbone.View.extend({
    el: '#page',
    events: {
      'click .work-btn':function () {
        if(is_add){
          is_add = false;
          this.AddWork();
        }
      }
    },
    initialize: function () {
      this.template = _.template(template);
      this.render();
    },
    render: function () {
      this.$el.html(this.template());
      $('header span').text("意见反馈");
      $('header .ok').text("反馈记录");
      $('header .ok').click(function () {
        Backbone.history.navigate('record', {trigger: true});
      })
      this.loadUser();
    },
    loadUser: function () {
      this.user = window.user.get();
      if (!this.user) {
        Backbone.history.navigate('loading', {trigger: true});
        return;
      }
    },
    AddWork: function () {

      var _this = this;
      var content = $.trim($('#content').val());
      if (!content) {
        is_add = true;
        window.toast('请输入反馈内容');
        return;
      }
      // if (content.length > 8 || content.length < 2) {
      //   is_add = true;
      //   window.toast('请输入中文2-5位，英文2-8位的用户名！');
      //   return;
      // }
      if(!window.filter(content,$('#content'))){
        is_add = true;
        return false;
      }
      window.jsonpGet('user/AddWork', {token: this.user.token,content:content}, 'AddWork', function (json) {
        window.toast(json.msg);
        setTimeout(function () {
          window.location.reload();
        },1500)
      });
    }

  });
  return View;
});


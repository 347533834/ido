define(function (require, exports, modules) {
  'use strict';

  require('css!assets/css/service/service.css');
  require('assets/plugins/md5.js');
  var template = require('text!./service.tpl.html'),
    util = require('util');

  var View = Backbone.View.extend({
    el: '#page',
    events: {
      'click .opinion-1': function () {
        // Backbone.history.navigate('opinion', {trigger: true});
        Backbone.history.navigate('work-list', {trigger: true});
      },

    },
    initialize: function () {
      this.template = _.template(template);
      this.render();
    },
    render: function () {
      this.$el.html(this.template());
      $('header span').text("联系客服");
      this.loadUser();
    },
    loadUser: function () {
      this.user = window.user.get();
      if (!this.user) {
        Backbone.history.navigate('loading', {trigger: true});
        return;
      }
      this.service();
    },

    service: function () {
      window.jsonpGet('user/service', {
        token: this.user.token,
      }, 'service', function (json) {
        if (json.code == 1) {
          $('.email').text(json.data.value);
          if(json.data.num>0){
            $('.news_num').show().text(json.data.num);
          }
        }
      })
    },

  });
  return View;
});


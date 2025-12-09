define(function (require, exports, modules) {
  'use strict';

  require('css!assets/css/notice/notice-details.css');
  var template = require('text!./notice-details.tpl.html'),
    util = require('util');

  var View = Backbone.View.extend({
    el: '#page',
    events: {},
    initialize: function () {
      this.template = _.template(template);
      this.render();
    },
    render: function () {
      this.$el.html(this.template());
      $('header span').text('详情');
      this.loadUser();
    },
    loadUser: function () {
      this.user = window.user.get();
      if (!this.user) {
        Backbone.history.navigate('login', {trigger: true});
        return;
      }
      this.LoadNoticeDetails();
    },

    LoadNoticeDetails:function(){
      var _this =this;
      var id = util.queryString('id');
      window.jsonpGet('user/LoadNoticeDetails', {token: this.user.token,id:id}, 'LoadNoticeDetails', function (json) {
        if (json.code == 1) {
          var dt = new Date(json.data.addtime * 1000);
          $('.notice-details article').html('<h2 style="text-align: center;">'+json.data.title+'</h2><p class="time"><time>'+ dt.format('yyyy-mm-dd') +'</time></p><section>'+ json.data.content +'</section>');
        }
      });
    },
  });
  return View;
});


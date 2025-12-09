define(function (require, exports, modules) {
  'use strict';

  require('css!assets/css/bonus/bonus.css');
  var template = require('text!./bonus-details.tpl.html'),
    is_add = true,
    util = require('util');

  var View = Backbone.View.extend({
    el: '#page',
    events: {
      'click .more': function () {
        this.bonusDetails(this.page);
      }
    },
    initialize: function () {
      this.template = _.template(template);
      this.render();
    },
    render: function () {
      this.$el.html(this.template());
      $('header span').text("合伙人分红详情");
      this.loadUser();
    },
    loadUser: function () {
      this.user = window.user.get();
      if (!this.user) {
        Backbone.history.navigate('login', {trigger: true});
        return;
      }
      this.page = 1;
      this.bonusDetails(this.page);
    },

    bonusDetails: function (p) {
      var _this = this;
      var page = p ? p : 1;
      var id = util.queryString('id');
      window.jsonpGet('user/bonusDetails', {
        token: this.user.token,
        id:id,
        page: page
      }, 'bonusDetails', function (json) {
        if (json.code == 1) {
          var data = json.data.data, str = '',status='';
          for (var i = 0; i < data.length; i++) {
            var dt = new Date(data[i].addtime * 1000);

            str +='<dd data-id="' + data[i].id + '"><span class="to-date">'+dt.format('mm-dd')+'</span><span class="to-date">' + data[i].username + '</span><span class="to-date">' + data[i].name + '</span><span class="to-date">' + parseFloat(parseFloat(data[i].num).toFixed(4)) + '</span><span class="to-date">' + parseFloat(parseFloat(data[i].returns).toFixed(4)) + '</span></dd>';
          }
          $('#bonus-list').append(str);
          if (json.data.current_page >= json.data.last_page) {
            $('.more').hide();
          } else {
            $('.more').show();
          }
          _this.page++;
          if(json.data.total<1){
            $('.no_data').show();
          }
        }

      })
    },

  });
  return View;
});


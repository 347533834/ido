define(function (require, exports, modules) {
  'use strict';

  require('css!assets/css/bonus/bonus.css');
  require('assets/plugins/md5.js');
  var template = require('text!./bonus.tpl.html'),
    is_add = true,
    util = require('util');

  var View = Backbone.View.extend({
    el: '#page',
    events: {
      'click .more': function () {
        this.LoadBonus(this.page);
      },
      'click #to_pick': function (e) {
        var obj = $(e.currentTarget);
        var id = obj.attr('data-id');
        if(is_add){
          is_add = false;
          this.to_bonus(id);
        }

      },
      'click .pick': function (e) {
        var obj = $(e.currentTarget);
        var id = obj.attr('data-id');
        $('.box-mask.rule-1').show();
        $('#to_pick').attr('data-id', id);
      },
      
      'click #close_pick': function () {
        $('.box-mask.rule-1').hide();
      },
    },
    initialize: function () {
      this.template = _.template(template);
      this.render();
    },
    render: function () {
      this.$el.html(this.template());
      $('header span').text("合伙人分红");
      this.loadUser();
    },
    loadUser: function () {
      this.user = window.user.get();
      if (!this.user) {
        Backbone.history.navigate('login', {trigger: true});
        return;
      }
      this.page = 1;
      this.LoadBonus(this.page);
    },
    LoadBonus: function (p) {
      var _this = this;
      var page = p ? p : 1;
      window.jsonpGet('user/LoadBonus', {
        token: this.user.token,
        page: page,
      }, 'LoadFriend', function (json) {
        if (json.code == 1) {
          var data = json.data.list.data, str = '',status='';
          $('.coin_count').text(json.data.coin_count);
          for (var i = 0; i < data.length; i++) {
            var dt = new Date(data[i].addtime * 1000);

            if (data[i].status == 1) {
              status = '<span>已提取</span>';
            } else if (data[i].status == 0) {
              status = '<button class="btn-blue pick" data-id="' + data[i].id + '" style="margin-top: 0.5rem;">提取</button>';
            }

            str +='<dd data-id="' + data[i].id + '"><span class="to-date">'+dt.format('mm-dd')+'</span><span class="to-date">' + data[i].username + '</span><span class="to-date">' + data[i].name + '</span><span class="to-date">' + parseFloat(parseFloat(data[i].num).toFixed(4)) + '</span><span class="to-date">' + parseFloat(parseFloat(data[i].returns).toFixed(4)) + '</span>'+status+'</dd>';
          }
          $('#bonus-list').append(str);
          if (json.data.list.current_page >= json.data.list.last_page) {
            $('.more').hide();
          } else {
            $('.more').show();
          }
          _this.page++;
          if(json.data.list.total<1){
            $('.no_data').show();
          }
        }
        
        $('.to-date').click(function () {
         var id = $(this).parent().data('id');
          Backbone.history.navigate('bonus-details?id=' + id, {trigger: true});        });
      })
    },

    to_bonus: function (id) {
      var _this = this;
      window.jsonpGet('user/to_bonus', {
        token: this.user.token,
        id: id,
      }, 'to_bonus', function (json) {
        if (json.code == 1) {
          $('.box-mask.rule-1').hide();
          window.confirm(json.msg, function () {
            window.location.reload();
          })
        } else {
          is_add = true;
          window.toast(json.msg);
        }
      })
    },


  });
  return View;
});


define(function (require, exports, modules) {
  'use strict';

  require('css!assets/css/bill/bill.css');
  require('assets/plugins/md5.js');
  var template = require('text!./bill.tpl.html'),
    util = require('util');

  var View = Backbone.View.extend({
    el: '#page',
    events: {
      'click .extract-1': function () {
        Backbone.history.navigate('extract', {trigger: true});
      },
      'click .more': function () {
        this.log_coin(this.page);
      },
    },
    initialize: function () {
      this.template = _.template(template);
      this.render();
    },
    render: function () {
      this.$el.html(this.template());
      // $('header span').text("USDT账单");
      this.loadUser();
    },
    loadUser: function () {
      this.user = window.user.get();
      if (!this.user) {
        Backbone.history.navigate('loading', {trigger: true});
        return;
      }
      this.page = 1;
      this.log_coin(this.page);
    },
    log_coin: function (p) {
      var _this = this;
      var page = p ? p : 1;
      var id = util.queryString('coin_id');
      window.jsonpGet('coin/log_coin', {
        token: this.user.token,
        id:id,
        page: page,
      }, 'LoadFriend', function (json) {
        if (json.code == 1) {
          var data = json.data.data, str = '';
          $('header span').text(json.data.name+'账单');

          for (var i = 0; i < data.length; i++) {
            var dt = new Date(data[i].addtime * 1000);
            str+='<li data-id="'+data[i].union_id+'" data-union="'+data[i].union+'" data-type="'+data[i].type+'" data-log="'+data[i].id+'"><p><span>'+dt.format('yyyy-mm-dd hh:ii')+'</span></p><p class="clearfix"><span>'+json.data.type[data[i].type]+(data[i].type==8?'（'+(data[i].status==1?'提现成功':(data[i].status==-1?'提现撤销':' 等待审核'))+'）':'')+'</span><span>'+(data[i].type>=parseInt(51)?'+':'-')+parseFloat(data[i].num).toFixed(4)+'</span></p></li>';
          }
          $('#coin-list').append(str);
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


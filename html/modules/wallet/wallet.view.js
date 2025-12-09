define(function (require, exports, modules) {
  'use strict';

  require('css!assets/css/wallet/wallet.css');
  require('assets/plugins/qrcode.min');
  var template = require('text!./wallet.tpl.html'),
    util = require('util');

  var View = Backbone.View.extend({
    el: '#page',
    events: {
      'click .extract-1': function () {
        Backbone.history.navigate('extract?coin_id=2', {trigger: true});
      },

      'click .more': function () {
        this.CoinList(this.page);
      },
      'click .usdt-btn':function () {
        this.LoadWallet(2);
      }
    },
    initialize: function () {
      this.template = _.template(template);
      this.render();
    },
    render: function () {
      this.$el.html(this.template());
      $('header span').text("钱包");
      this.loadUser();
      $('.close').click(function () {
        $('.recharge').hide()
      });

      var _this = this;
      window.intervals.push(window.setInterval(function () {
        _this.refresh();
      }, 10000));


      var clipboard = new ClipboardJS('.copy', {});
      clipboard.on('success', function (e) {
        window.toast('复制成功');
      });
      clipboard.on('error', function (e) {
        window.alert('请长按地址手动复制');
      });
    },

    loadUser: function () {
      this.user = window.user.get();
      if (!this.user) {
        Backbone.history.navigate('login', {trigger: true});
        return;
      }
      this.page = 1;
      this.CoinList(this.page);
    },

    refresh: function () {
      var _this = this;
      window.jsonpGet('coin/trx', {token: this.user.token}, 'trx', function (json) {
        if (json.data) {
          _this.CoinList(1);
        }
      });
    },

    CoinList: function (p) {
      var _this = this;
      var page = p ? p : 1;
      window.jsonpGet('coin/CoinList', {
        token: this.user.token,
        page: page,
      }, 'LoadFriend', function (json) {
        if (json.code == 1) {
          var data = json.data.coin.data, str = '';
          $('.coin_count').text(json.data.coin_count);
          var num=0;
          for (var i = 0; i < data.length; i++) {
            var total = data[i].total;

            num += parseFloat(data[i].balance != null ? parseFloat(data[i].balance): 0)*total;

            str +='<li style="border-bottom: 9px solid #f9f9f9;" data-id="' + data[i].coin_id + '">'+
              '<div class="top clearfix">'+
              '<p><img src="' + util.cdn + data[i].logo + '?' + data[i].addtime + '"></p>'+
              '<p><span>'+data[i].name+'</span><span>冻结<i>' + parseFloat(parseFloat(data[i].trade_num != null ? parseFloat(data[i].trade_num) : 0)+parseFloat(data[i].cash_num != null ? parseFloat(data[i].cash_num) : 0)+parseFloat(data[i].market_num != null ? parseFloat(data[i].market_num) : 0)+parseFloat(data[i].tt_num != null ? parseFloat(data[i].tt_num) : 0)+parseFloat(data[i].tr_num != null ? parseFloat(data[i].tr_num) : 0)).toFixed(4) + '</i></span></p>'+
              '<p><span>' + (data[i].balance != null ? parseFloat(data[i].balance).toFixed(4) : '0.0000') + '</span><span>折合(￥)<i>' + (data[i].balance != null ? parseFloat(data[i].balance * total).toFixed(2) : '0.00') + '</i></span></p>'+
              '</div>'+
              // '<div class="bottom">'+
              // '<p class="recharges" data-id="' + data[i].coin_id + '" data-recharge="' + data[i].is_recharge + '"><i><img src="assets/img/wallet/chongbi.png"></i>&nbsp;&nbsp;<span>充币</span></p>'+
              // '<p class="draw" data-id="' + data[i].coin_id + '" data-draw="' + data[i].is_draw + '"><i><img src="assets/img/wallet/tibi.png"></i>&nbsp;&nbsp;<span>提币</span></p>'+
              // '<p class="bill-1" data-id="' + data[i].coin_id + '"><i><img src="assets/img/wallet/zhangdan.png"></i>&nbsp;&nbsp;<span>账单</span></p>'+
              // '</div>'+
              '</li>';
          }
          $('.usdt-num').text(parseFloat(num).toFixed(2));
          $('.coin-list').append(str);
          if (json.data.coin.current_page >= json.data.coin.last_page) {
            $('.more').hide();
          } else {
            $('.more').show();
          }
          _this.page++;


          $('.recharges').click(function () {
            var recharge = $(this).data('recharge');
            var id = $(this).data('id');
            if (recharge == 1) {
              _this.LoadWallet(id);
            } else {
              window.toast('暂未开放');
            }
          });

          $('.draw').click(function () {
            var draw = $(this).data('draw');
            var id = $(this).data('id');
            if (draw == 1) {
              Backbone.history.navigate('extract?coin_id=' + id, {trigger: true});
            } else {
              window.toast('暂未开放');
            }
          });

          $('.coin-list li').click(function () {
            Backbone.history.navigate('bill?coin_id='+$(this).data('id'), {trigger: true});
          });

        }
      })
    },

    LoadWallet: function (id) {
      window.jsonpGet('coin/show_recharge', {
        token: this.user.token,
        coin_id: id
      }, 'show_recharge', function (json) {
        if (json.code == 1) {
          var data = json.data;
          $('.title').text('充值'+data.name);
          $('.sm-btn').text('扫码转入'+data.name);
          $('#copy').val(data.wallet);
          $('#copy-show').text(util.hideMid(data.wallet,7,7,'***'));
          $('.recharge').show();
          $('#qr-code').html('');
          new QRCode('qr-code', {
            text: data.wallet,
            width: 136,
            height: 136,
            colorDark: '#000000',
            colorLight: '#ffffff',
            correctLevel: QRCode.CorrectLevel.H
          });

        } else {
          window.toast(json.msg);
        }
      })
    }

  });
  return View;
});


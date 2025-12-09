define(function (require, exports, modules) {
  'use strict';

  require('css!assets/css/extract/extract.css');
  require('assets/plugins/md5.js');
  var template = require('text!./extract.tpl.html'),
    util = require('util');
  var pwd = '';
  var View = Backbone.View.extend({
    el: '#page',
    events: {
      'click .extract-1': function () {
        Backbone.history.navigate('extract', {trigger: true});
      },
      'click .paypass': function () {
        Backbone.history.navigate('set-password', {trigger: true});
      },
      // 'keyup #num': 'keyup_buy_num',
      // 'blur #num': 'check_buy_num',
    },
    initialize: function () {
      this.template = _.template(template);
      this.render();
    },
    render: function () {
      this.$el.html(this.template());
      // $('header span').text("提取USDT");
      this.loadUser();
      var _this=this;
      $('.d-btn button:eq(0)').click(function () {
        $('#number').val('')
        $('#fee-num').text('');
        $('#fee_num').val('');

        $('.box-extract').hide();
      });


      $('.d-btn button:eq(1)').click(function () {
        // $('.box-extract').hide();
        _this.load_extract();
      });


      $('.coin-btn').click(function () {
        var num = parseFloat($('#num').val());
        var wallet = $('#wallet').val();
        var fee = parseFloat($('.rate_num').text());
        var balance = parseFloat($('.balance').text());
        var min = parseFloat($('#min-num').val());
        var usdt = parseFloat($('#usdt').val());
        var coin_id = util.queryString('coin_id');
        if (isNaN(num) || num < 0) {
          num = '';
          $('#num').val('');
          window.toast('请输入提现数量');
          return
        }

        if (parseFloat(num) < parseFloat(min)) {
          $('#num').val('');
          window.toast('最小提现数量' + min);
          return
        }
        if(coin_id==2){
          if (parseFloat(num) + fee > parseFloat(balance)) {
            $('#num').val('');
            window.toast('余额不足');
            return
          }
        }else {
          if (parseFloat(num) > parseFloat(balance)) {
            $('#num').val('');
            window.toast('余额不足');
            return
          }
          if(isNaN(usdt) || usdt<5){
            $('#num').val('');
            window.toast('手续需扣除5USDT，账户余额不足');
            return
          }
        }

        if (wallet.length < 1) {
          window.toast('请输入钱包地址');
          return;
        }
        if (!window.filter(wallet, $('#wallet'))) {
          return false;
        }
        $('.fee-num').text(',手续费' + fee);
        $('.number').text(num);
        $('#add_wallet').val(wallet);
        $(".box-extract").removeClass('none');
      });


      $(".ipt-real-nick").on("input", function () {
        var $input = $(".ipt-fake-box input");
        if (!$(this).val()) {//无值光标顶置
          // $('.ipt-active-nick').css('left',$input.eq(0).offset().left-parseInt($('.ipt-box-nick').parent().css('padding-left'))+'px');
          // console.log($input.eq(0).offset().left-parseInt($('.ipt-box-nick').parent().css('padding-left')));
        }
        if (/^[0-9a-zA-Z]+$/.test($(this).val())) {//有值只能是数字
          //console.log($(this).val());
          pwd = $(this).val().trim();
          for (var i = 0, len = pwd.length; i < len; i++) {
            $input.eq(i).val(pwd[i]);
            if ($input.eq(i).next().length) {//模拟光标，先将图片容器定位，控制left值而已
            }
          }
          console.log(len);
          $input.each(function () {//将有值的当前input后的所有input清空
            var index = $(this).index();
            if (index >= (len)) {
              $(this).val("");
            }
          });
          if (len == 6) {
            //执行其他操作
            // console.log('输入完整，执行操作');
          }
        } else {//清除val中的非数字，返回纯number的value
          var arr = $(this).val().match(/^[0-9a-zA-Z]+$/);
          console.log(arr);
          try {
            $(this).val($(this).val().slice(0, $(this).val().lastIndexOf(arr[arr.length - 1]) + 1));
          } catch (e) {
            console.log(e.message)
            //清空
            $(this).val("");
            $input.val("");
            $('.ipt-fake-box input:eq(0)').val('');
          }

          //console.log($(this).val());
        }
      });
    },

    loadUser: function () {
      this.user = window.user.get();
      if (!this.user) {
        Backbone.history.navigate('login', {trigger: true});
        return;
      }
      this.loadBalance();
      // this.user_wallet();
    },

    //数量检查
    keyup_buy_num: function (e) {
      var num = parseFloat($('#num').val()).toFixed(4);
      var balance = parseFloat($('.balance').text()).toFixed(4);
      var min = parseFloat($('#min-num').val());
      if (isNaN(num) || num < 0) {
        num = '';
        $('#num').val('');
      }
    },
    //数量检查
    check_buy_num: function () {
      var num = parseFloat($('#num').val()).toFixed(4);
      var balance = parseFloat($('.balance').text()).toFixed(4);
      var fee = parseFloat($('.rate_num').text());
      var min = parseFloat($('#min-num').val());
      if (isNaN(num) || num < 0) {
        num = '';
        $('#num').val('');
      }

      if (parseFloat(num) < parseFloat(min)) {
        $('#num').val('');
        window.toast('最小提现数量' + min);
        return
      }

      if (parseFloat(num) + fee > parseFloat(balance)) {
        $('#num').val('');
        window.toast('余额不足,手续费需扣除' + fee + '个');
      }

    },

    loadBalance: function () {
      var _this = this;
      window.jsonpGet('coin/user_balance', {
        token: this.user.token,
        coin_id: util.queryString('coin_id')
      }, 'user_balance', function (json) {
        if (json.code == 1) {
          $('header span').text("提取"+json.data.name);
          $('.token').text(json.data.name);
          $('.balance').text(parseFloat(json.data.balance));
          $('.rate_num').text(json.data.rate_num);
          $('#min-num').val(json.data.min);
          // $('#fee').val(json.data.fee);
          $('#usdt').val(json.data.usdt);
          $('#num').attr('placeholder', '最少提取' + parseFloat(json.data.min))
        }
      })
    },
    load_extract: function () {
      var coin_id = util.queryString('coin_id');
      var user_wallet = $('#add_wallet').val();
      var num = $('.number').text();
      // var remark = $.trim($('input[name="remark"]').val());
      // var validate = $.trim($('input[name="validate"]').val());

      // if (remark.length > 100) {
      //   window.toast('备注最多可输入100字符');
      //   return
      // }

      // if (action == '') {
      //   window.toast('请获取手机验证码');
      //   return
      // }

      // if (!validate) {
      //   $('input[name=validate]').focus();
      //   window.toast('请输入手机验证码');
      //   return
      // }
      if (!window.filter(user_wallet, $('#add_wallet'))) {
        return false;
      }

      // if (!window.filter(remark, $('input[name="remark"]'))) {
      //   return false;
      // }

      if(pwd.length<6){
        window.toast('请输入完整的密码');
        return false;
      }

      window.jsonpGet('coin/extract', {
        token: this.user.token,
        user_wallet: user_wallet,
        num: num,
        coin_id: coin_id,
        pwd: hex_md5(pwd),
        // remark: remark,
        // action: action,
      }, 'extract', function (json) {
        if (json.code == 1) {
          window.toast(json.msg);
          Backbone.history.navigate('wallet', {trigger: true});
        } else {
          $('#validate').val('');
          window.toast(json.msg);
        }

      });
    },
  });
  return View;
});


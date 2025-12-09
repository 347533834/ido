define(function (require, exports, modules) {
    'use strict';

    require('css!assets/css/coin/coin.css');
    require('assets/plugins/md5.js');
    var template = require('text!./buy_market.tpl.html'),
        util = require('util');

    var buy_max = 0,
        fee_rate = 0,
        price = 0;

    var View = Backbone.View.extend({
        el: '#page',
        events: {
            'keyup #afternoon_buy_num': function () {
                this.keyup_afternoon_buy_num();
            },
            'blur #afternoon_buy_num': function () {
                this.blur_afternoon_buy_num();
            },

            'click #market_buy': function (e) {
                // var _this = this;
                // window.confirm('确认购买吗？', function () {
                //     _this.market_buy();
                // });
                this.market_buy();
            },
        },
        initialize: function () {
            this.template = _.template(template);
            this.render();
        },
        render: function () {
            this.$el.html(this.template());
            $('header span').text('购买');

            this.loadUser();
        },
        loadUser: function () {
            this.user = window.user.get();
            if (!this.user) {
                Backbone.history.navigate('login', {trigger: true});
                return;
            }
            var id = parseInt(util.queryString('id'));
            this.market_detail(id);
        },

        market_detail: function (id) {
            var _this = this;
            window.jsonpGet('Trade/market_detail', {
                token: _this.user.token,
                id: id,
            }, 'market_detail', function (json) {
                if (json.code == 1) {
                    var data = json.data;
                    $('.exch_coin_name').text(data.name);
                    buy_max = parseFloat(data.left_num);
                    fee_rate = parseFloat(data.fee_rate);
                    price = parseFloat(data.price);

                    $('#num').html(parseFloat(data.num));
                    $('#price').html(parseFloat(data.price));
                    $('#total').html(parseFloat(data.total));
                    $('#fee').html(parseFloat(data.fee));
                    $('#deal_num').html(parseFloat(data.deal_num));
                    $('#left_num').html(parseFloat(data.left_num));

                    var max = window.avg((parseFloat(data.usdt_balance) / (1 + fee_rate) / price), 2);

                    if (max >= buy_max) {
                        $('#afternoon_buy_num').attr('placeholder', '可买' + buy_max);
                    } else {
                        buy_max = max;
                        $('#afternoon_buy_num').attr('placeholder', '可买' + max);
                    }
                }
            });
        },

        // keyup检查数量
        keyup_afternoon_buy_num: function () {
            var num = parseFloat($('#afternoon_buy_num').val());
            if (isNaN(num) || num <= 0) {
                num = 0;
            }

            var total = parseFloat(window.toDecimal((parseFloat(((num * 10000) * (price * 10000))) / 100000000), 8));
            var fee = parseFloat(window.toDecimal((total * fee_rate), 8));
            $('#afternoon_buy_total').text(total);
            $('#afternoon_buy_fee').text(fee);
            $('#afternoon_buy_sum').text(parseFloat(window.toDecimal((total + fee), 8)));
        },

        // blur检查数量
        blur_afternoon_buy_num: function () {
            var num = parseFloat(parseFloat($('#afternoon_buy_num').val()).toFixed(2));
            if (isNaN(num) || num <= 0) {
                $('#num').val('');
                return false;
            }

            if (parseFloat(num - buy_max) > 0) {
                num = buy_max;
            }

            var total = parseFloat(window.toDecimal((parseFloat(((num * 10000) * (price * 10000))) / 100000000), 8));
            var fee = parseFloat(window.toDecimal((total * fee_rate), 8));

            $('#afternoon_buy_num').val(num);
            $('#afternoon_buy_total').text(total);
            $('#afternoon_buy_fee').text(fee);
            $('#afternoon_buy_sum').text(parseFloat(window.toDecimal((total + fee), 8)));
        },

        market_buy: function () {
            var num = parseFloat(parseFloat($('#afternoon_buy_num').val()).toFixed(2));
            if (isNaN(num) || num <= 0) {
                $('#afternoon_buy_num').val('');
                window.toast('请输入正确的数量');
                return false;
            }

            var _this = this;
            window.confirm('确认购买吗？', function () {
                window.jsonpGet('Trade/market_buy', {
                    token: _this.user.token,
                    id: parseInt(util.queryString('id')),
                    num: num,
                }, 'market_buy', function (json) {
                    if (json.code == 1) {
                        window.toast(json.msg);
                        window.setTimeout(function () {
                            window.location.reload();
                        }, 1500)
                    } else if (json.code == 9) {
                        window.toast(json.msg);
                        window.setTimeout(function () {
                            Backbone.history.navigate('certification', {trigger: true});
                        }, 1500)
                    } else {
                        window.toast(json.msg);
                    }
                });
            });
        },

    });
    return View;
});


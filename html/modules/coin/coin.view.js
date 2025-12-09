define(function (require, exports, modules) {
    'use strict';

    require('css!assets/css/coin/coin.css');
    require('assets/plugins/md5.js');
    var template = require('text!./coin.tpl.html'),
        util = require('util');

    var morning_price = 0,
        fee_rate_sell = 0,
        fee_rate_buy = 0,
        morning_buy_mini = 0,
        morning_buy_max = 0,
        morning_sell_mini = 0,
        morning_sell_max = 0,
        afternoon_price_mini = 0,
        afternoon_sell_max = 0,
        trade_name;

    var View = Backbone.View.extend({
        el: '#page',
        events: {
            'click #morning_current_order': function () {
                Backbone.history.navigate('my-commission', {trigger: true});
            },
            'click #morning_history_order': function () {
                Backbone.history.navigate('historical-order', {trigger: true});
            },
            'click #afternoon_current_order': function () {
                Backbone.history.navigate('my-commission2', {trigger: true});
            },
            'click #afternoon_history_order': function () {
                Backbone.history.navigate('historical-order2', {trigger: true});
            },
            'click #market_lists': function () {
                Backbone.history.navigate('market_lists', {trigger: true});
            },
            'click .box-mask.box-coin li': function (e) {
                var obj = $(e.currentTarget);
                var trade = obj.attr('data-trade');
                var is_trade = obj.attr('data-is_trade');
                if (is_trade == 1) {
                    //window.toast('暂未开放');
                    Backbone.history.navigate('trade?trade=' + trade + '&type=1', {trigger: true});
                } else {
                    //window.location.reload();
                    Backbone.history.navigate('coin?trade=' + trade, {trigger: true});
                }
            },
            'keyup #morning_buy_num': function () {
                this.keyup_morning_buy_num();
            },
            'blur #morning_buy_num': function () {
                this.blur_morning_buy_num();
            },
            'keyup #morning_sell_num': function () {
                this.keyup_morning_sell_num();
            },
            'blur #morning_sell_num': function () {
                this.blur_morning_sell_num();
            },

            'keyup #afternoon_sell_num': function () {
                this.keyup_afternoon_sell_num();
            },
            'blur #afternoon_sell_num': function () {
                this.blur_afternoon_sell_num();
            },
            'keyup #afternoon_price': function () {
                this.keyup_afternoon_sell_num();
            },
            'blur #afternoon_price': function () {
                this.blur_afternoon_sell_num();
            },

            'click #trade_buy': function (e) {
                // var _this = this;
                // window.confirm('确认挂买吗？', function () {
                //     _this.trade_buy();
                // });
                this.trade_buy();
            },
            'click #trade_sell': function (e) {
                // var _this = this;
                // window.confirm('确认挂卖吗？', function () {
                //     _this.trade_sell();
                // });
                this.trade_sell();
            },
            'click #market_sell': function (e) {
                // var _this = this;
                // window.confirm('确认提交吗？', function () {
                //     _this.market_sell();
                // });
                this.market_sell();
            },
            'click #change_morning': function (e) {
                var obj = $(e.currentTarget);
                obj.next().removeClass('active');
                obj.addClass('active');

                $('.sec-2').removeClass('none');
                $('.sec-3').addClass('none');
                this.show_morning();

                $('#change_rate').hide();
            },
            'click #change_afternoon': function (e) {
                // var obj = $(e.currentTarget);
                // obj.prev().removeClass('active');
                // obj.addClass('active');
                //
                // $('.sec-2').addClass('none');
                // $('.sec-3').removeClass('none');
                // this.show_afternoon();
                //
                // $('#change_rate').show();
                var trade = util.queryString('trade');
                Backbone.history.navigate('coin2?trade=' + trade, {trigger: true});
            },
        },
        initialize: function () {
            this.template = _.template(template);

            this.render();
        },
        render: function () {
            this.$el.html(this.template());
            //$('header .ok').text('历史订单');
            // $(".ok").click(function () {
            //     Backbone.history.navigate('historical-order', {trigger: true});
            // });

            $("header span").click(function () {
                $('.content.coin .box-mask.box-coin').show();
            });

            $(".box-mask.box-coin").click(function (e) {
                if (e.target.className === 'box-mask box-coin none') {
                    $(".content.coin .box-mask.box-coin").hide();
                }
            });

            // $(".box-mask.box-coin").click(function (e) {
            //     if (parseInt(util.queryString('type')) == 2) {
            //         if (e.target.className === 'box-mask box-coin') {
            //             $(".content.coin .box-mask.box-coin").hide();
            //         }
            //     } else {
            //         if (e.target.className === 'box-mask box-coin none') {
            //             $(".content.coin .box-mask.box-coin").hide();
            //         }
            //     }
            // });
            this.load_coin_trade();
            // if (parseInt(util.queryString('type')) == 2) {
            //     $('.box-mask.box-coin').removeClass('none');
            // }

            var trade = util.queryString('trade');
            $('header span').text(trade);
            $("header span").append("<img class='down' src='assets/img/coin/img-1.png'>");

            trade = trade.split('/');
            $('.exch_coin_name').text(trade[0]);
            $('.main_coin_name').text(trade[1]);
            trade_name = trade.join('');
            if(trade_name != 'FIDUSDT'){
                $('.site').show();
            }else{
                $('#morning_current_order').find('span').eq(0).text('当前委托');
                $('#morning_history_order').find('span').eq(0).text('历史订单');
            }
            $('#coin_logo').attr('src', 'assets/img/coin/' + trade[0] + '.png');

            this.loadUser();
        },
        loadUser: function () {
            this.user = window.user.get();
            if (!this.user) {
                Backbone.history.navigate('login', {trigger: true});
                return;
            }
            this.show_morning();
        },
        load_coin_trade: function () {
            window.jsonpGet('index/coin_trade_show', null, 'coin_trade', function (json) {
                if (json.code == 1) {
                    var data = json.data.data;
                    var str = '';
                    for (var i in data) {
                        if (i != 'randomSort') {
                            str += '<li data-trade="' + data[i].exch_coin_name + '/' + data[i].main_coin_name + '" data-is_trade="' + data[i].is_trade + '">\n' +
                                '                    <p><img src="' + util.cdn + data[i].logo + '?' + data[i].addtime + '"></p>\n' +
                                '                    <p>' + data[i].exch_coin_name + '/' + data[i].main_coin_name + '</p>\n' +
                                '                </li>'
                        }
                    }
                    $('.content.coin .box-mask.box-coin ul').html(str);
                }
            });
        },

        show_morning: function () {
            var _this = this;
            window.jsonpGet('Trade/show_morning', {
                token: _this.user.token,
                trade_name: trade_name,
            }, 'show_morning', function (json) {
                if (json.code == 1) {
                    var data = json.data;
                    morning_price = parseFloat(data.price);
                    fee_rate_sell = parseFloat(data.fee_sell);
                    fee_rate_buy = parseFloat(data.fee_buy);
                    morning_buy_mini = parseFloat(data.trade_buy_mini);
                    morning_sell_mini = parseFloat(data.trade_sell_mini);

                    // if (data.is_idaudit < 1) {
                    //     $('.red.clearfix').removeClass('none');
                    //     $('.yellow.clearfix').addClass('none');
                    //     $('.btn.btn-red').attr('class', 'btn btn-light-red')
                    // }

                    $('#morning_notice').html('每天' + data.morning_start + '到' + data.morning_end + '以平台标价兑换，先到先得！');
                    $('#new_price').html(parseFloat(data.price) + ' USDT');
                    $('#new_price_cny').html('≈' + (parseFloat(data.cny_price)) + ' CNY');
                    morning_buy_max = window.avg((parseFloat(data.main_coin_balance) / (1 + parseFloat(data.fee_buy)) / parseFloat(data.price)), 2);
                    $('#morning_buy_num').attr('placeholder', '可买' + morning_buy_max);
                    $('.morning_price').html(parseFloat(data.price));
                    $('#main_coin_balance').attr('placeholder', '可用' + parseFloat(data.main_coin_balance));
                    $('#morning_sell_num').attr('placeholder', '可用' + parseFloat(data.exch_coin_balance));
                    morning_sell_max = window.avg(parseFloat(data.main_coin_balance) / parseFloat(data.fee_sell) / parseFloat(data.price), 2);

                    if (morning_sell_max > parseFloat(data.exch_coin_balance)) {
                        morning_sell_max = parseFloat(data.exch_coin_balance);
                    }
                } else {
                    window.toast(json.msg);
                }
            });
        },

        show_afternoon: function () {
            var _this = this;
            window.jsonpGet('Trade/show_afternoon', {
                token: _this.user.token,
                trade_name: trade_name,
            }, 'show_afternoon', function (json) {
                if (json.code == 1) {
                    var data = json.data;

                    // if (data.is_idaudit < 1) {
                    //     $('.red.clearfix').removeClass('none');
                    //     $('.yellow.clearfix').addClass('none');
                    //     $('.btn.btn-red').attr('class', 'btn btn-light-red')
                    // }
                    $('#afternoon_notice').html('每天' + data.afternoon_start + '到' + data.afternoon_end + '可自由交易');
                    $('#afternoon_sell_num').attr('placeholder', '可用' + window.avg(parseFloat(data.exch_coin_balance), 2));

                    $('#new_price').html(parseFloat(data.last_price) + ' USDT');
                    $('#new_price_cny').html('≈' + (parseFloat(data.last_price) * parseFloat(data.usdt_price)).toFixed(2) + ' CNY');
                    $('#afternoon_price').attr('placeholder', '最低' + parseFloat(data.price));

                    var change = ((parseFloat(data.last_price) - parseFloat(data.open_price)) / parseFloat(data.open_price) * 100).toFixed(2);

                    if (change > 0) {
                        $('.green').html('+' + change + '%');
                    } else if (change < 0) {
                        $('.green').attr('style', 'color:#FF6060');
                        $('.green').html(change + '%');
                    } else {
                        $('.green').html('0.00%');
                    }

                    afternoon_price_mini = parseFloat(data.price);
                    afternoon_sell_max = window.avg(parseFloat(data.exch_coin_balance), 2);
                }
            });
        },

        // keyup检查数量
        keyup_morning_buy_num: function () {
            var num = parseFloat($('#morning_buy_num').val());
            if (isNaN(num) || num <= 0) {
                num = 0;
            }

            var total = parseFloat(window.toDecimal((parseFloat(((num * 10000) * (morning_price * 10000))) / 100000000), 8));
            var fee = parseFloat(window.toDecimal((total * fee_rate_buy), 8));
            $('#main_coin_balance').val(total);
            $('#morning_buy_fee').text(fee);
        },

        // blur检查数量
        blur_morning_buy_num: function () {
            var num = parseFloat(parseFloat($('#morning_buy_num').val()).toFixed(2));
            if (isNaN(num) || num <= 0) {
                $('#morning_buy_num').val('');
                return false;
            }

            if (parseFloat(num - morning_buy_mini) < 0) {
                num = morning_buy_mini;
            }
            if (parseFloat(num - morning_buy_max) > 0) {
                num = morning_buy_max;
            }

            var total = parseFloat(window.toDecimal((parseFloat(((num * 10000) * (morning_price * 10000))) / 100000000), 8));
            var fee = parseFloat(window.toDecimal((total * fee_rate_buy), 8));

            $('#morning_buy_num').val(num);
            $('#main_coin_balance').val(total);
            $('#morning_buy_fee').text(fee);
        },

        // keyup检查数量
        keyup_morning_sell_num: function () {
            var num = parseFloat($('#morning_sell_num').val());
            if (isNaN(num) || num <= 0) {
                num = 0;
            }

            var total = parseFloat(window.toDecimal((parseFloat(((num * 10000) * (morning_price * 10000))) / 100000000), 8));
            var fee = parseFloat(window.toDecimal((total * fee_rate_sell), 8));
            $('#morning_sell_total').text(total);
            $('#morning_sell_fee').text(fee);
        },

        // blur检查数量
        blur_morning_sell_num: function () {
            var num = parseFloat(parseFloat($('#morning_sell_num').val()).toFixed(2));
            if (isNaN(num) || num <= 0) {
                $('#morning_sell_num').val('');
                return false;
            }

            if (parseFloat(num - morning_sell_mini) < 0) {
                num = morning_sell_mini;
            }

            if (parseFloat(num - morning_sell_max) > 0) {
                num = morning_sell_max;
            }

            var total = parseFloat(window.toDecimal((parseFloat(((num * 10000) * (morning_price * 10000))) / 100000000), 8));
            var fee = parseFloat(window.toDecimal((total * fee_rate_sell), 8));
            $('#morning_sell_num').val(num);
            $('#morning_sell_total').text(total);
            $('#morning_sell_fee').text(fee);
        },

        trade_buy: function () {
            var num = parseFloat(parseFloat($('#morning_buy_num').val()).toFixed(2));
            if (isNaN(num) || num <= 0) {
                $('#morning_buy_num').val('');
                window.toast('请输入正确的数量');
                return false;
            }

            var _this = this;
            window.confirm('确认挂买吗？', function () {
                window.jsonpGet('Trade/trade_buy', {
                    token: _this.user.token,
                    num: num,
                    trade_name: trade_name,
                }, 'trade_buy', function (json) {
                    if (json.code == 1) {
                        window.toast(json.msg);
                        window.setTimeout(function () {
                            if (parseInt(util.queryString('type')) == 2) {
                                Backbone.history.navigate('coin', {trigger: true});
                            } else {
                                window.location.reload();
                            }
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

        trade_sell: function () {
            var num = parseFloat(parseFloat($('#morning_sell_num').val()).toFixed(2));
            if (isNaN(num) || num <= 0) {
                $('#morning_sell_num').val('');
                window.toast('请输入正确的数量');
                return false;
            }

            var _this = this;
            window.confirm('确认挂卖吗？', function () {
                window.jsonpGet('Trade/trade_sell', {
                    token: _this.user.token,
                    num: num,
                    trade_name: trade_name,
                }, 'trade_sell', function (json) {
                    if (json.code == 1) {
                        window.toast(json.msg);
                        window.setTimeout(function () {
                            if (parseInt(util.queryString('type')) == 2) {
                                Backbone.history.navigate('coin', {trigger: true});
                            } else {
                                window.location.reload();
                            }
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

        // keyup检查数量
        keyup_afternoon_sell_num: function () {
            var num = parseFloat($('#afternoon_sell_num').val());
            if (isNaN(num) || num <= 0) {
                num = 0;
            }
            var afternoon_price = parseFloat($('#afternoon_price').val());
            if (isNaN(afternoon_price)) {
                afternoon_price = 0;
                $('#afternoon_price').val('');
            }

            var total = parseFloat(window.toDecimal((parseFloat(((num * 10000) * (afternoon_price * 10000))) / 100000000), 8));
            var fee = parseFloat(window.toDecimal((total * fee_rate_sell), 8));
            $('#afternoon_sell_total').text(total);
            $('#afternoon_sell_fee').text(fee);
        },

        // blur检查数量
        blur_afternoon_sell_num: function () {
            var num = parseFloat($('#afternoon_sell_num').val());
            if (isNaN(num) || num <= 0) {
                ('#afternoon_sell_num').val('');
                return false;
            }
            if (parseFloat(num - afternoon_sell_max) > 0) {
                num = afternoon_sell_max;
            }

            var afternoon_price = parseFloat($('#afternoon_price').val());
            if (isNaN(afternoon_price)) {
                afternoon_price = 0;
                $('#afternoon_price').val('');
            }
            if (afternoon_price < afternoon_price_mini) {
                afternoon_price = afternoon_price_mini;
                $('#afternoon_price').val(afternoon_price);
            }

            var total = parseFloat(window.toDecimal((parseFloat(((num * 10000) * (afternoon_price * 10000))) / 100000000), 8));
            var fee = parseFloat(window.toDecimal((total * fee_rate_sell), 8));
            $('#afternoon_sell_num').val(num);
            $('#afternoon_sell_total').text(total);
            $('#afternoon_sell_fee').text(fee);

        },

        market_sell: function () {
            var num = parseFloat(parseFloat($('#afternoon_sell_num').val()).toFixed(2));
            if (isNaN(num) || num <= 0) {
                $('#afternoon_sell_num').val('');
                window.toast('请输入正确的数量');
                return false;
            }

            var price = parseFloat(parseFloat($('#afternoon_price').val()).toFixed(4));

            var _this = this;
            window.confirm('确认挂卖吗？', function () {
                window.jsonpGet('Trade/market_sell', {
                    token: _this.user.token,
                    num: num,
                    price: price,
                    trade_name: trade_name,
                }, 'market_sell', function (json) {
                    if (json.code == 1) {
                        window.toast(json.msg);
                        window.setTimeout(function () {
                            if (parseInt(util.queryString('type')) == 2) {
                                Backbone.history.navigate('coin', {trigger: true});
                            } else {
                                window.location.reload();
                            }
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


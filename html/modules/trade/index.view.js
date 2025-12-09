define(function (require, exports, modules) {
    'use strict';

    require('css!assets/css/transaction/transaction.css');
    require('assets/plugins/md5.js');
    var template = require('text!./index.tpl.html'),
        util = require('util');

    var res = [],
        main_coin_balance,
        exch_coin_balance,
        main_coin_name,
        exch_coin_name,
        num_min,
        trade_name,
        coin_price,
        coins_price = [],
        all_trade = [];

    var View = Backbone.View.extend({
        el: '#page',
        events: {
            'click .title-2 li': function (e) {
                var obj = $(e.currentTarget);
                var trade = obj.attr('data-trade');
                Backbone.history.navigate('transaction?trade=' + trade + '&type=1', {trigger: true});
            },
            'click #buy_form': function () {
                $('.transaction-inline >li').removeClass('active');
                $('#buy_form').addClass('active');
                $('#change_rate span').removeClass('blue-active');
                $('#trade_tab').show();
                $('#open_orders_tab').hide();
                $('#order_history_tab').hide();
                $('.purchase-left >button').attr('style', 'background-color: #03C086;');
                $('.purchase-left >button').attr('class', 'buy');
                $('#price').val($('.change_price.sell0').attr('data-price'));
                $('#num').val('');
                $('#total').val('');
                if (this.user) {
                    $('.purchase-left >button').text('买入' + exch_coin_name);
                }
            },
            'click #sell_form': function () {
                $('.transaction-inline >li').removeClass('active');
                $('#sell_form').addClass('active');
                $('#change_rate span').removeClass('blue-active');
                $('#trade_tab').show();
                $('#open_orders_tab').hide();
                $('#order_history_tab').hide();
                $('.purchase-left >button').attr('style', 'background-color: #FF565F;');
                $('.purchase-left >button').attr('class', 'sell');
                $('#price').val($('.change_price.buy0').attr('data-price'));
                $('#num').val('');
                $('#total').val('');
                if (this.user) {
                    $('.purchase-left >button').text('卖出' + exch_coin_name);
                }
            },
            'click #open_orders': function () {
                if (this.user) {
                    $('#open_orders_tab .no_data').hide();
                    $('.transaction-inline >li').removeClass('active');
                    $('#open_orders').addClass('active');
                    $('#trade_tab').hide();
                    $('#open_orders_tab').show();
                    $('#order_history_tab').hide();
                    var str = '<dt> ' +
                        '<span>' + this.data.lang.Date + '</span> ' +
                        '<span>' + this.data.lang.Type + '</span> ' +
                        '<span>' + this.data.lang.Price + '(' + main_coin_name + ')' + '</span> ' +
                        '<span>' + this.data.lang.Amount + '(' + exch_coin_name + ')' + '</span> ' +
                        '<span>' + this.data.lang.Action + '</span> ' +
                        '</dt>';
                    $('#open_orders_tab >dl').html(str);
                    $('#open_orders_tab >dd').remove();
                    this.open_orders_page = 1;
                    this.open_orders(this.open_orders_page, trade_name);
                } else {
                    window.confirm(this.data.lang.Please_login, function () {
                        Backbone.history.navigate('login', {trigger: true});
                    });
                }
            },
            'click #order_history': function () {
                if (this.user) {
                    $('#order_history_tab .no_data').hide();
                    $('.transaction-inline >li').removeClass('active');
                    $('#order_history').addClass('active');
                    $('#trade_tab').hide();
                    $('#open_orders_tab').hide();
                    $('#order_history_tab').show();
                    var str = '<dt> ' +
                        '<span>' + this.data.lang.Date + '</span> ' +
                        '<span>' + this.data.lang.Type + '</span> ' +
                        '<span>' + this.data.lang.Price + '(' + main_coin_name + ')' + '</span> ' +
                        '<span>' + this.data.lang.Amount + '(' + exch_coin_name + ')' + '</span> ' +
                        '<span>' + this.data.lang.Status + '</span> ' +
                        /*'<span>' + this.data.lang.Action + '</span> ' +*/
                        '</dt>';
                    $('#order_history_tab >dl').html(str);
                    $('#order_history_tab >dd').remove();
                    this.order_history_page = 1;
                    this.order_history(this.order_history_page, trade_name);
                } else {
                    window.confirm(this.data.lang.Please_login, function () {
                        Backbone.history.navigate('login', {trigger: true});
                    });
                }
            },
            'click .change_price': function (e) {
                var obj = $(e.currentTarget);
                var price = obj.attr('data-price');
                $('#price').val(price);
                $('#price_cny').text(parseFloat(coin_price * price).toFixed(3));
                this.blur_check_num();
            },
            'click #change_rate span': function (e) {
                var obj = $(e.currentTarget);
                $('#change_rate span').removeClass('blue-active');
                obj.addClass('blue-active')
                if (this.user) {
                    var price = parseFloat(parseFloat($('#price').val()).toFixed(4));
                    if (isNaN(price) || price <= 0) {
                        return
                    }
                    var rate = parseFloat(obj.attr('data-rate'));
                    var type = $('.purchase-left >button').attr('class');
                    console.log(type);
                    if (type == 'buy') {
                        var max_buy_num = window.avg(parseFloat(main_coin_balance / price), 4);
                        $('#num').val(window.avg(parseFloat(max_buy_num * rate), 4));
                    } else {
                        var max_sell_num = exch_coin_balance;
                        $('#num').val(window.avg(parseFloat(exch_coin_balance * rate), 4));
                    }
                    this.blur_check_num();
                }
            },
            'keyup #price': function () {
                this.keyup_check_num();
            },
            'keyup #num': function () {
                this.keyup_check_num();
            },
            'blur #price': function () {
                this.blur_check_num();
            },
            'blur #num': function () {
                this.blur_check_num();
            },
            'click .buy': function () {
                window.toast('暂未开放');
                return false;
                this.to_buy();
            },
            'click .sell': function () {
                window.toast('暂未开放');
                return false;
                this.to_sell();
            },
            'click #open_orders_tab .more': function () {
                this.open_orders(this.open_orders_page, trade_name);
            },
            'click #order_history_tab .more': function () {
                this.order_history(this.order_history_page, trade_name);
            },
            'click .cancel': function (e) {
                var _this = this;
                window.confirm(this.data.lang.Sure_Cancel, function () {
                    var obj = $(e.currentTarget);
                    var id = obj.attr('data-id');
                    var exch_coin_name = obj.attr('data-exch_coin_name');
                    var main_coin_name = obj.attr('data-main_coin_name');
                    _this.cancel_trade(id, exch_coin_name, main_coin_name);

                })
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
        },
        initialize: function () {
            var langs = {
                'en': {
                    Login: 'Login ',
                    Or: 'or ',
                    Register: 'Register ',
                    View_Order: 'to view the order',
                    Markets: 'Markets',
                    Marked: 'Marked',
                    Coin: 'Coin',
                    Last_Price: 'Last Price ',
                    Change: 'Change',
                    High: 'High',
                    Low: 'Low',
                    Vol: ' Vol',
                    Announcement: 'Announcement',
                    to_trade: ' to trade',
                    Limit_Order: 'Limit Order',
                    Available: 'Available',
                    Buy_Price: 'Price',
                    Buy_Amount: 'Amount',
                    Sell_Price: 'Price',
                    Sell_Amount: 'Amount',
                    Free: 'Free',
                    Trade_Total: 'Total',
                    Order: 'Order',
                    Open_Orders: 'Open Orders',
                    Orde_History: 'Order History',
                    Transaction_History: 'Transaction History',
                    More: 'More',
                    Fee: 'Fee',
                    Deposit: 'Deposit',
                    Buy: 'Buy ',
                    Sell: 'Sell ',
                    Date: 'Date',
                    Pairs: 'Pairs',
                    Type: 'Type',
                    Price: 'Price',
                    Amount: 'Amount',
                    Sum: 'Sum',
                    Group: 'Group',
                    Total: 'Total',
                    Total_Executed: 'Total',
                    Average_Price: 'Average Price',
                    Status: 'Status',
                    Executed: 'Executed',
                    Unexecuted: 'Unexecuted',
                    Cancelled: 'Cancelled',
                    Action: 'Action',
                    Detail: 'Detail',
                    Cancel: 'Cancel',
                    Trade_type: 'Type',
                    Part: 'Part done',
                    All: 'All done',
                    Sure_Cancel: 'Are you sure about canceling？',
                    Trading: 'Trading',
                    Canceled: 'Canceled',
                    Completed: 'Completed',
                    Market_Depth: 'Market Depth',
                    Market_Trades: 'Market Trades',
                    Coin_Basics: 'Coin Basics',
                    Invite_friends: 'Invite friends to register',
                    Get_fees: 'Get cashback of trading  50% fees',
                    Introduction: 'Introduction',
                    Issue_Time: 'Issue Time',
                    Total_Amount: 'Total Amount',
                    Circulation: 'Circulation',
                    Token_Price: 'Token Price',
                    White_Paper: 'White Paper',
                    Official_Website: 'Official Website',
                    Block_Explorer: 'Block Explorer',
                    Please_login: 'Please login！',
                    Correct_amount: 'Please enter the correct amount！',
                    Correct_price: 'Please enter the correct price！',
                    Firm_order: 'Firm order',
                    Trade_password: 'Trade password',
                    Enter_password: 'Please enter password',
                    Balance_less: 'Balance less than the minimum requirements',
                    Less_than_zero: 'The amount of trade after deducting handling fee is less than 0',
                    next: 'Next',
                    prev: 'Prev',
                    first: 'First',
                    last: 'Last',
                },
                'cn': {
                    Login: '登录',
                    Or: '或',
                    Register: '注册',
                    View_Order: '查看订单',
                    Markets: '市场',
                    Marked: '自选',
                    Coin: '币种',
                    Last_Price: '最新价',
                    Change: '涨幅',
                    High: '高',
                    Low: '低',
                    Vol: '量',
                    Announcement: '公告',
                    to_trade: '开始交易',
                    Limit_Order: '限价交易',
                    Available: '可用',
                    Buy_Price: '买入价',
                    Buy_Amount: '买入量',
                    Sell_Price: '卖出价',
                    Sell_Amount: '卖出量',
                    Free: '免手续费',
                    Trade_Total: '交易额',
                    Order: '订单',
                    Open_Orders: '当前委托',
                    Orde_History: '委托历史',
                    Transaction_History: '成交明细',
                    More: '更多',
                    Fee: '手续费',
                    Deposit: '充币',
                    Buy: '买入',
                    Sell: '卖出',
                    Date: '时间',
                    Pairs: '交易对',
                    Type: '方向',
                    Price: '价格',
                    Amount: '数量',
                    Sum: '累计',
                    Group: '深度',
                    Total: '委托总额',
                    Total_Executed: '成交额',
                    Average_Price: '成交均价',
                    Status: '状态',
                    Executed: '已成交',
                    Unexecuted: '未成交',
                    Cancelled: '已撤销',
                    Action: '操作',
                    Detail: '详情',
                    Cancel: '撤单',
                    Trade_type: '交易类型',
                    Part: '部分成交',
                    All: '全部成交',
                    Sure_Cancel: '确定要撤销该委托吗？',
                    Trading: '挂单中',
                    Canceled: '已撤单',
                    Completed: '已完成',
                    Market_Depth: '深度图',
                    Market_Trades: '实时成交',
                    Coin_Basics: '币种资料',
                    Invite_friends: '邀请好友注册',
                    Get_fees: '可获得交易手续费50%',
                    Introduction: '简介',
                    Issue_Time: '发行时间',
                    Total_Amount: '发行总量',
                    Circulation: '流通总量',
                    Token_Price: '众筹价格',
                    White_Paper: '白皮书',
                    Official_Website: '官网',
                    Block_Explorer: '区块查询',
                    Please_login: '请登录后再进行操作！',
                    Correct_amount: '请输入正确的数量！',
                    Correct_price: '请输入正确的价格！',
                    Firm_order: '确认订单',
                    Trade_password: '交易密码',
                    Enter_password: '请输入交易密码',
                    Balance_less: '余额不足挂单最低要求',
                    Less_than_zero: '扣除手续费后的交易额小于0',
                    next: '下页',
                    prev: '上页',
                    first: '首页',
                    last: '末页',
                }
            };
            this.data = {
                lang: langs[window.lang.get()],
                toLang: window.lang.get() == 'en' ? 'en' : 'cn',
            };
            this.render();
        },
        render: function () {
            this.$el.append(_.template(template, this.data));

            var trade = util.queryString('trade');

            $('header span').text(trade);
            $("header span").append("<img class='down' src='assets/img/coin/img-1.png'>");

            $("header span").click(function () {
                $('.content.transaction .box-mask.box-coin').show();
            });

            $(".box-mask.box-coin").click(function (e) {
                if (parseInt(util.queryString('show')) == 1) {
                    if (e.target.className === 'box-mask box-coin') {
                        $(".content.transaction .box-mask.box-coin").hide();
                    }
                } else {
                    if (e.target.className === 'box-mask box-coin none') {
                        $(".content.transaction .box-mask.box-coin").hide();
                    }
                }
            });


            // $(".box-mask.box-coin").click(function (e) {
            //     if (e.target.className === 'box-mask box-coin none') {
            //         $(".content.transaction .box-mask.box-coin").hide();
            //     }
            // });

            if (!trade) {
                location.href = './';
                return;
            }
            trade = trade.split('/');
            main_coin_name = trade[1];
            exch_coin_name = trade[0];
            trade_name = trade.join('');
            $('.main_coin_name').text(main_coin_name);
            $('.exch_coin_name').text(exch_coin_name);
            $('.main_coin_balance').attr('placeholder', '可用' + main_coin_name + ': 0.0000');
            $('.exch_coin_balance').attr('placeholder', '可用' + exch_coin_name + ': 0.0000');

            this.loadUser();
            this.load_coin_trade();
            if (parseInt(util.queryString('show')) == 1) {
                $('.box-mask.box-coin').removeClass('none');
            }
            this.load_depth(trade_name);
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
                    $('.content.transaction .box-mask.box-coin ul').html(str);
                }
            });
        },
        load_depth: function (trade) {
            window.jsonpGet('Trade/load_depth', {
                token: this.user.token,
                trade: trade,
            }, 'LoadAsset', function (json) {
                if (json.code == 1) {
                    var data = json.data;
                    var sell = data.asks,
                        buy = data.bids;
                    coin_price = parseFloat(data.usdt_cny);
                    var bids = new Array(), asks = new Array();
                    $('.purchase-right li').css('opacity', 1);
                    if (sell.length != 0) {
                        for (var i = 0; i < 7; i++) {
                            var obj = $('.purchase-right .sell' + i);
                            if (sell[i]) {
                                obj.css('opacity', 1);
                                obj.attr('data-price', parseFloat(sell[i][0]).toFixed(4));
                                obj.find('span:eq(0)').text((i + 1));
                                obj.find('span:eq(1)').text(parseFloat(sell[i][0]).toFixed(4));
                                obj.find('span:eq(2)').text(parseFloat(sell[i][1]).toFixed(4));
                            } else {
                                obj.css('opacity', 0).find('span:eq(0)').text(' ');
                            }
                        }
                    }
                    if (buy.length != 0) {
                        for (var i = 0; i < 7; i++) {
                            var obj = $('.purchase-right .buy' + i);
                            if (buy[i]) {
                                obj.css('opacity', 1);
                                obj.attr('data-price', parseFloat(buy[i][0]).toFixed(4));
                                obj.find('span:eq(0)').text((i + 1));
                                obj.find('span:eq(1)').text(parseFloat(buy[i][0]).toFixed(4));
                                obj.find('span:eq(2)').text(parseFloat(buy[i][1]).toFixed(4));
                            } else {
                                obj.css('opacity', 0).find('span:eq(0)').text(' ');
                            }
                        }
                        $('#last_price').text(buy[0][0]);
                        $('#last_price_cny').text(' ≈￥' + parseFloat(coin_price * buy[0][0]).toFixed(2));
                    }
                    var type = $('.purchase-left >button').attr('class');
                    if (type == 'buy') {
                        if ($('#price').val() == '0.0000') {
                            $('#price').val(parseFloat(sell[0][0]).toFixed(4));
                            $('#price_cny').text(parseFloat(coin_price * sell[0][0]).toFixed(3));
                        }
                    } else {
                        if ($('#price').val() == '0.0000') {
                            $('#price').val(parseFloat(buy[0][0]).toFixed(4));
                            $('#price_cny').text(parseFloat(coin_price * buy[0][0]).toFixed(3));
                        }
                    }
                }
            });
        },
        loadUser: function () {
            this.user = window.user.get();
            if (this.user) {
                this.LoadAsset();
                if (util.queryString('type') == 1) {
                    $('#buy_form').addClass('active');
                    $('.purchase-left >button').attr('style', 'background-color: #03C086;');
                    $('.purchase-left >button').attr('class', 'buy').text('买入' + exch_coin_name);
                } else if (util.queryString('type') == 2) {
                    $('#sell_form').addClass('active');
                    $('.purchase-left >button').attr('style', 'background-color: #FF565F;');
                    $('.purchase-left >button').attr('class', 'sell').text('卖出' + exch_coin_name);
                } else {
                    location.href = './';
                    return;
                }
            } else {
                if (util.queryString('type') == 1) {
                    $('#buy_form').addClass('active');
                    $('.purchase-left >button').attr('style', 'background-color: #03C086;');
                    $('.purchase-left >button').attr('class', 'buy');
                } else if (util.queryString('type') == 2) {
                    $('#sell_form').addClass('active');
                    $('.purchase-left >button').attr('style', 'background-color: #FF565F;');
                    $('.purchase-left >button').attr('class', 'sell');
                } else {
                    location.href = './';
                    return;
                }
            }
        },
        LoadAsset: function () {
            var _this = this;
            if (this.user) {
                window.jsonpGet('Trade/LoadAsset', {
                    token: this.user.token,
                    trade_name: trade_name,
                }, 'LoadAsset', function (json) {
                    if (json.code == 1) {
                        var data = json.data;
                        main_coin_balance = window.avg(parseFloat(data.main_coin_balance) ? parseFloat(data.main_coin_balance) : 0, 4);
                        exch_coin_balance = window.avg(parseFloat(data.exch_coin_balance) ? parseFloat(data.exch_coin_balance) : 0, 4);
                        num_min = parseFloat(data.num_min);
                        $('.main_coin_balance').attr('placeholder', _this.data.lang.Available + main_coin_name + ':' + main_coin_balance);
                        $('.exch_coin_balance').attr('placeholder', _this.data.lang.Available + exch_coin_name + ':' + exch_coin_balance);
                    }
                });
            } else {
                $('#main_coin_balance').html('<p class=""><a href="#login" style="color: #03E7EF">' + _this.data.lang.Login + '</a>' + _this.data.lang.Or + '<a href="#register" style="color: #03E7EF">' + _this.data.lang.Register + '</a>' + _this.data.lang.to_trade + '</p>')
            }
        },
        // keyup检查数量
        keyup_check_num: function () {
            var type = $('.purchase-left >button').attr('class');
            if (type == 'buy') {
                var price = parseFloat($('#price').val());
                if (isNaN(price)) {
                    price = 0;
                    $('#price').val('');
                }
                //$('#buy_price_cny').text('≈' + (price * cny_price).toFixed(2) + 'CNY');

                var num = parseFloat($('#num').val());
                if (isNaN(num) || num <= 0) {
                    //$('#buy_num').val(num);
                    num = 0;
                }
                var total = parseFloat(window.toDecimal((parseFloat(((num * 10000) * (price * 10000))) / 100000000), 8));
                $('#total').val(total);
            } else {
                var price = parseFloat($('#price').val());
                if (isNaN(price)) {
                    price = 0;
                    $('#price').val('');
                }
                //$('#price_cny').text('≈' + (price * cny_price).toFixed(2) + 'CNY');

                var num = parseFloat($('#num').val());
                if (isNaN(num) || num <= 0) {
                    //$('#num').val(num);
                    num = 0;
                }
                var total = parseFloat(window.toDecimal((parseFloat(((num * 10000) * (price * 10000))) / 100000000), 8));
                $('#total').val(total);
            }
        },
        // blur检查数量
        blur_check_num: function () {
            var type = $('.purchase-left >button').attr('class');
            if (type == 'buy') {
                var price = parseFloat($('#price').val()).toFixed(4);
                if (isNaN(price) || price <= 0) {
                    price = 0;
                    $('#price').val(price);
                }
                $('#price').val(price);
                //$('#buy_price_cny').text('≈' + (price * cny_price).toFixed(2) + 'CNY');

                var num = parseFloat($('#num').val()).toFixed(4);
                if (isNaN(num) || num <= 0) {
                    $('#num').val('');
                    //window.toast(this.data.lang.Correct_amount);
                    return false;
                }
                // 若数量小于最小交易数量，则自动变为最小交易数量
                console.log(num_min);
                if (parseFloat(num - num_min) < 0) {
                    num = num_min;
                }

                //计算总交易额，若大于资产余额，则自动变为余额能买入的最大值
                var total = parseFloat(window.toDecimal((parseFloat(((num * 10000) * (price * 10000))) / 100000000), 8));
                if (parseFloat(main_coin_balance - total) < 0) {
                    num = window.avg(parseFloat(main_coin_balance / price), 4);
                    if (parseFloat(num - num_min) < 0) {
                        window.toast(this.data.lang.Balance_less);
                        return false;
                    }
                }
                total = parseFloat(window.toDecimal((parseFloat(((num * 10000) * (price * 10000))) / 100000000), 4));
                //重新写入数量、总交易额
                $('#num').val(num);
                $('#total').val(total);
            } else {
                var price = parseFloat(parseFloat($('#price').val()).toFixed(4));
                if (isNaN(price) || price <= 0) {
                    price = 0;
                    $('#price').val(price);
                }
                $('#price').val(price);
                //$('#price_cny').text('≈' + (price * cny_price).toFixed(2) + 'CNY');

                var num = parseFloat(parseFloat($('#num').val()).toFixed(4));
                if (isNaN(num) || num <= 0) {
                    $('#num').val('');
                    return false;
                }

                //若数量大于余额，则自动变为余额
                if (parseFloat(exch_coin_balance - num) < 0) {
                    num = exch_coin_balance;
                }
                //若数量小于最小交易数量，则自动变为最小交易数量
                if (parseFloat(num - num_min) < 0) {
                    num = num_min;
                }
                if (parseFloat(exch_coin_balance - num) < 0) {
                    window.toast(this.data.lang.Balance_less);
                    return false;
                }
                var total = parseFloat(window.toDecimal((parseFloat(((num * 10000) * (price * 10000))) / 100000000), 4));
                //重新写入数量、总交易额
                $('#num').val(num);
                $('#total').val(total);
            }
        },
        // 买入
        to_buy: function () {
            if (!this.user) {
                //window.alert(this.data.lang.Please_login);
                return;
            }
            var price = parseFloat(parseFloat($('#price').val()).toFixed(4));
            if (isNaN(price) || price <= 0) {
                price = 0;
                $('#price').val(price);
            }
            $('#price').val(price);
            //$('#buy_price_cny').text('≈' + (price * cny_price).toFixed(2) + 'CNY');

            var num = parseFloat(parseFloat($('#num').val()).toFixed(4));
            if (isNaN(num) || num <= 0) {
                $('#num').val('');
                window.toast(this.data.lang.Correct_amount);
                return false;
            }
            //若数量小于最小交易数量，则自动变为最小交易数量
            if (parseFloat(num - num_min) < 0) {
                num = num_min;
            }
            //计算总交易额，若大于资产余额，则自动变为余额能买入的最大值
            var total = parseFloat(window.toDecimal((parseFloat(((num * 10000) * (price * 10000))) / 100000000), 8));
            if (parseFloat(main_coin_balance - total) < 0) {
                num = window.avg(parseFloat(main_coin_balance / price), 4);
                if (parseFloat(num - num_min) < 0) {
                    window.toast(this.data.lang.Balance_less);
                    return false;
                }
            }
            total = parseFloat(window.toDecimal((parseFloat(((num * 10000) * (price * 10000))) / 100000000), 8));
            //重新写入数量、总交易额
            $('#num').val(num);
            $('#total').val(total);

            var _this = this;
            if (_this.user.is_paypw != 1) {
                var str = '<div style="padding-bottom: 10px">'
                    + '<span style="color: black">' + this.data.lang.Trade_password + ' : </span>'
                    + '<input id="buy_password" type="password" class="form-control" placeholder="' + this.data.lang.Enter_password + '" style="border: 1px solid #DFDFDF;height:28px;color:#DFDFDF;padding-left: 10px;width: 50%;">'
                    + '</div>';
            } else {
                var str = '';
            }
            layer.open({
                id: 1,
                type: 1,
                title: this.data.lang.Firm_order,
                skin: 'layui-layer-lan',
                area: ['450px', 'auto'],
                content: ' <div class="row" style="width: 280px;  padding-left:50px;line-height: 24px;font-size: 13px;box-sizing: border-box">'
                    + '<div>'
                    + '<span style="color: black">' + this.data.lang.Buy_Price + ' : </span>'
                    + '<span style="color: black">' + price + ' ' + main_coin_name + '</span>'
                    + '</div>'
                    + '<div>'
                    + '<span style="color: black">' + this.data.lang.Buy_Amount + ' : </span>'
                    + '<span style="color: black">' + num + ' ' + exch_coin_name + '</span>'
                    + '</div>'
                    + '<div>'
                    + '<span style="color: black">' + this.data.lang.Trade_Total + ' : </span>'
                    + '<span style="color: black">' + total + ' ' + main_coin_name + '</span>'
                    + '</div>'
                    + str
                    + '</div>'
                ,
                btn: ['确认', '取消'],
                yes: function (index, layero) {
                    var obj = $('.buy');
                    if (obj.hasClass('clicked')) return;
                    obj.addClass('clicked');

                    var paypass = $('#buy_password').val();
                    if (_this.user.is_paypw != 1) {
                        if (!paypass) {
                            obj.removeClass('clicked');
                            window.alert(_this.data.lang.Enter_password);
                            return false;
                        } else {
                            paypass = hex_md5(paypass);
                        }
                    }
                    window.jsonpGet('Trade/to_buy', {
                        token: _this.user.token,
                        trade_name: trade_name,
                        price: price,
                        num: num,
                        paypass: paypass,
                    }, 'to_buy', function (json) {
                        if (json.code == 1) {
                            layer.close(index);
                            window.alert(json.msg);
                            main_coin_balance = parseFloat(parseFloat(json.data.main_coin_balance).toFixed(4));
                            exch_coin_balance = parseFloat(parseFloat(json.data.exch_coin_balance).toFixed(4));
                            $('.main_coin_balance').attr('placeholder', _this.data.lang.Available + main_coin_name + ': ' + main_coin_balance);
                            $('.exch_coin_balance').attr('placeholder', _this.data.lang.Available + exch_coin_name + ': ' + exch_coin_balance);
                            $('#num').val('');
                            $('#total').val('');
                        } else if (json.code == 20) {
                            layer.close(index);
                            window.confirm(json.msg, function () {
                                Backbone.history.navigate('transaction-password', {trigger: true});
                            })
                        } else {
                            window.alert(json.msg);
                        }
                        obj.removeClass('clicked');
                    });
                },
                no: function (index, layero) {
                    layer.close(index);
                }
            });
        },
        to_sell: function () {
            if (!this.user) {
                //window.alert(this.data.lang.Please_login);
                return;
            }
            var price = parseFloat(parseFloat($('#price').val()).toFixed(4));
            if (isNaN(price) || price <= 0) {
                price = 0;
                $('#price').val(price);
            }
            $('#price').val(price);
            //$('#price_cny').text('≈' + (price * cny_price).toFixed(2) + 'CNY');

            var num = parseFloat(parseFloat($('#num').val()).toFixed(4));
            if (isNaN(num) || num <= 0) {
                $('#num').val('');
                window.toast(this.data.lang.Correct_amount);
                return false;
            }


            //若数量大于余额，则自动变为余额
            if (parseFloat(exch_coin_balance - num) < 0) {
                num = exch_coin_balance;
            }
            //若数量小于最小交易数量，则自动变为最小交易数量
            if (parseFloat(num - num_min) < 0) {
                num = num_min;
            }
            if (parseFloat(exch_coin_balance - num) < 0) {
                window.toast(this.data.lang.Balance_less);
                return false;
            }
            var total = parseFloat(window.toDecimal((parseFloat(((num * 10000) * (price * 10000))) / 100000000), 8));
            //重新写入数量、总交易额
            $('#num').val(num);
            $('#total').text(total + ' ' + main_coin_name);

            var _this = this;

            if (_this.user.is_paypw != 1) {
                var str = '<div style="padding-bottom: 10px">'
                    + '<span style="color: black">' + this.data.lang.Trade_password + ' : </span>'
                    + '<input id="sell_password" type="password" class="form-control" placeholder="' + this.data.lang.Enter_password + '" style="border: 1px solid #DFDFDF;height:28px;color:#DFDFDF;padding-left: 10px;width: 50%;">'
                    + '</div>';
            } else {
                var str = '';
            }
            layer.open({
                id: 1,
                type: 1,
                title: this.data.lang.Firm_order,
                skin: 'layui-layer-lan',
                area: ['450px', 'auto'],
                content: ' <div class="row" style="width: 280px;  padding-left:50px;line-height: 24px;font-size: 13px;box-sizing: border-box">'
                    + '<div>'
                    + '<span style="color: black">' + this.data.lang.Sell_Price + ' : </span>'
                    + '<span style="color: black">' + price + ' ' + main_coin_name + '</span>'
                    + '</div>'
                    + '<div>'
                    + '<span style="color: black">' + this.data.lang.Sell_Amount + ' : </span>'
                    + '<span style="color: black">' + num + ' ' + exch_coin_name + '</span>'
                    + '</div>'
                    + '<div>'
                    + '<span style="color: black">' + this.data.lang.Trade_Total + ' : </span>'
                    + '<span style="color: black">' + total + ' ' + main_coin_name + '</span>'
                    + '</div>'
                    + str
                    + '</div>'
                ,
                btn: ['确认', '取消'],
                yes: function (index, layero) {
                    var obj = $('.sell');
                    if (obj.hasClass('clicked')) return;
                    obj.addClass('clicked');

                    var paypass = $('#sell_password').val();
                    if (_this.user.is_paypw != 1) {
                        if (!paypass) {
                            obj.removeClass('clicked');
                            window.alert(_this.data.lang.Enter_password);
                            return false;
                        } else {
                            paypass = hex_md5(paypass);
                        }
                    }
                    window.jsonpGet('Trade/to_sell', {
                        token: _this.user.token,
                        trade_name: trade_name,
                        price: price,
                        num: num,
                        paypass: paypass,
                    }, 'to_sell', function (json) {
                        if (json.code == 1) {
                            layer.close(index);
                            window.alert(json.msg);
                            main_coin_balance = parseFloat(parseFloat(json.data.main_coin_balance).toFixed(4));
                            exch_coin_balance = parseFloat(parseFloat(json.data.exch_coin_balance).toFixed(4));
                            $('.main_coin_balance').attr('placeholder', _this.data.lang.Available + main_coin_name + ': ' + main_coin_balance);
                            $('.exch_coin_balance').attr('placeholder', _this.data.lang.Available + exch_coin_name + ': ' + exch_coin_balance);
                            $('#num').val('');
                            $('#total').val('');
                        } else if (json.code == 20) {
                            layer.close(index);
                            window.confirm(json.msg, function () {
                                Backbone.history.navigate('transaction-password', {trigger: true});
                            })
                        } else {
                            window.alert(json.msg);
                        }
                        obj.removeClass('clicked');
                    });
                },
                no: function (index, layero) {
                    layer.close(index);
                }
            });
        },
        open_orders: function (p, market) {
            $('#open_orders_tab .no_data').show();
            // var _this = this;
            // var page = p ? p : 1;
            // window.jsonpGet('Trade/OpenOrders', {
            //     token: this.user.token,
            //     market: market,
            //     page: page,
            // }, 'OpenOrders', function (json) {
            //     var data = json.data.data;
            //     if (json.code == 1) {
            //         var data = json.data.data,
            //             type,
            //             str = '';
            //         for (var i = 0; i < data.length; i++) {
            //             if (data[i].side == 2) {
            //                 type = '<span style="color: #03C086">' + _this.data.lang.Buy + '</span>';
            //             } else {
            //                 type = '<span style="color: #FF565F">' + _this.data.lang.Sell + '</span>';
            //             }
            //
            //             str += '<dd data-id="' + data[i].id + '"> ' +
            //                 '<span>' + new Date(data[i].ctime * 1000).format('hh:ii:ss') + '</span> ' +
            //                 type +
            //                 '<span>' + data[i].price + '</span> ' +
            //                 '<span>' + data[i].amount + '</span> ' +
            //                 '<span><a style="color: #FF565F" href="javascript:;" data-id="' + data[i].id + '" data-exch_coin_name="' + data[i].exch_coin_name + '" data-main_coin_name="' + data[i].main_coin_name + '" class="cancel">' + _this.data.lang.Cancel + '</a></span> ' +
            //                 '</dd>';
            //         }
            //         $('#open_orders_tab >dl').append(str);
            //         if (json.data.current_page >= json.data.last_page) {
            //             $('#open_orders_tab .more').hide();
            //         } else {
            //             $('#open_orders_tab .more').show();
            //         }
            //         _this.open_orders_page++;
            //     } else {
            //         $('#open_orders_tab .no_data').show();
            //     }
            // });
        },
        //撤单
        cancel_trade: function (id, exch_coin_name, main_coin_name) {
            var _this = this;
            window.jsonpGet('Trade/cancel_trade', {
                token: user.user.token,
                id: id,
                exch_coin_name: exch_coin_name,
                main_coin_name: main_coin_name,
            }, 'cancel_trade', function (json) {
                if (json.code == 1) {
                    $("dd[data-id=" + id + "]").remove();
                    window.alert(json.msg);
                    main_coin_balance = parseFloat(parseFloat(json.data.main_coin_balance).toFixed(4));
                    exch_coin_balance = parseFloat(parseFloat(json.data.exch_coin_balance).toFixed(4));
                    $('.main_coin_balance').attr('placeholder', _this.data.lang.Available + main_coin_name + ': ' + main_coin_balance);
                    $('.exch_coin_balance').attr('placeholder', _this.data.lang.Available + exch_coin_name + ': ' + exch_coin_balance);
                } else {
                    window.alert(json.msg);
                }
            });
        },
        order_history: function (p, market) {
            $('#order_history_tab .no_data').show();
            // var _this = this;
            // var page = p ? p : 1;
            // window.jsonpGet('Trade/TransactionDetail', {
            //     token: this.user.token,
            //     market: market,
            //     page: page,
            // }, 'TransactionDetail', function (json) {
            //     if (json.code == 1) {
            //         var data = json.data.data,
            //             type,
            //             str = '',
            //             Action_str;
            //         for (var i = 0; i < data.length; i++) {
            //             if (data[i].side == 2) {
            //                 type = '<span style="color: #03C086">' + _this.data.lang.Buy + '</span>';
            //             } else {
            //                 type = '<span style="color: #FF565F">' + _this.data.lang.Sell + '</span>';
            //             }
            //
            //             if (data[i].deal_stock == 0) {
            //                 Action_str = '<span style="color: #FF565F">' + _this.data.lang.Cancelled + '</span><!--<span></span>-->'
            //             } else {
            //                 Action_str = '<span style="color: #03C086">' + _this.data.lang.Executed + '</span>'//<span style="cursor: pointer;color: #7a98f7" data-id="' + data[i].id + '" class="order_detail">' + _this.data.lang.Detail + '</span>
            //             }
            //
            //             str += '<dd> ' +
            //                 '<span>' + new Date(data[i].ftime * 1000).format('hh:ii:ss') + '</span> ' +
            //                 type +
            //                 '<span>' + data[i].price + '</span> ' +
            //                 '<span>' + data[i].amount + '</span> ' +
            //                 Action_str +
            //                 '</dd>';
            //         }
            //         $('#order_history_tab >dl').append(str);
            //         if (data.length == json.data.pageSize) {
            //             $('#order_history_tab .more').show();
            //         } else {
            //             $('#order_history_tab .more').hide();
            //         }
            //         _this.order_history_page++;
            //     } else {
            //         if (page == 1) {
            //             $('#order_history_tab .no_data').show();
            //         } else {
            //             $('#order_history_tab .no_data').hide();
            //         }
            //         $('#order_history_tab .more').hide();
            //     }
            // });
        },
    });
    return View;
});


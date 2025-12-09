define(function (require, exports, modules) {
    'use strict';

    require('css!assets/css/historical-order/historical-order.css');
    require('assets/plugins/md5.js');
    var template = require('text!./historical-order.tpl.html'),
        util = require('util');

    var View = Backbone.View.extend({
        el: '#page',
        events: {
            'click .historical-order .more': function () {
                this.load_morning_history_order(this.page);
            },
        },
        initialize: function () {
            this.template = _.template(template);
            this.render();
        },
        render: function () {
            this.$el.html(this.template());
            $('header span').text("历史订单");

            this.loadUser();
        },
        loadUser: function () {
            this.user = window.user.get();
            if (!this.user) {
                Backbone.history.navigate('login', {trigger: true});
                return;
            }
            this.page = 1;
            this.load_morning_history_order(this.page);
        },

        load_morning_history_order: function (p) {
            var _this = this;
            var page = p ? p : 1;
            window.jsonpGet('Trade/load_morning_history_order', {
                token: this.user.token,
                page: page,
            }, 'load_morning_history_order', function (json) {
                var data = json.data.data;
                if (json.code == 1) {
                    var data = json.data.data,
                        type,
                        str = '';
                    for (var i = 0; i < data.length; i++) {
                        str += '<li>\n' +
                            '           <div class="scene">\n' +
                            '               <p>\n' +
                            '                   <span>' + (data[i].type == 1 ? '购买' : '出售') + '(' + data[i].exch_coin_name + ')</span>\n' +
                            '                   <span style="background-color: ' + (data[i].status == 2 ? 'green' : '') + '">' + (data[i].status == 2 ? '全部成交' : '部分成交') + '</span>\n' +
                            '               </p>\n' +
                            '               <p><span>' + new Date(data[i].addtime * 1000).format('yy-mm-dd hh:ii:ss') + '</span></p>\n' +
                            '           </div>\n' +
                            '            <div class="number">\n' +
                            '                <div>\n' +
                            '                    <p>数量(' + data[i].exch_coin_name + ')</p>\n' +
                            '                    <p><span>' + parseFloat(data[i].num) + '</span></p>\n' +
                            '                </div>\n' +
                            '                <div>\n' +
                            '                    <p>单价(' + data[i].main_coin_name + ')</p>\n' +
                            '                    <p><span>' + parseFloat(data[i].price) + '</span></p>\n' +
                            '                </div>\n' +
                            '                <div>\n' +
                            '                    <p>总额(' + data[i].main_coin_name + ')</p>\n' +
                            '                    <p><span>' + parseFloat(data[i].total) + '</span></p>\n' +
                            '                </div>\n' +
                            '            </div>\n' +
                            '            <div class="number">\n' +
                            '                <div>\n' +
                            '                    <p>手续费(' + data[i].main_coin_name + ')</p>\n' +
                            '                    <p><span>' + parseFloat(data[i].fee) + '</span></p>\n' +
                            '                </div>\n' +
                            '                <div>\n' +
                            '                    <p>已成交(' + data[i].exch_coin_name + ')</p>\n' +
                            '                    <p><span>' + parseFloat(data[i].deal_num) + '</span></p>\n' +
                            '                </div>\n' +
                            '                <div>\n' +
                            '                    <p>未成交(' + data[i].exch_coin_name + ')</p>\n' +
                            '                    <p><span>' + parseFloat(data[i].left_num) + '</span></p>\n' +
                            '                </div>\n' +
                            '            </div>\n' +
                            // '            <div class="number">\n' +
                            // '                <div>\n' +
                            // '                    <p>成交手续费</p>\n' +
                            // '                    <p><span>' + parseFloat(data[i].deal_fee).toFixed(4) + '</span></p>\n' +
                            // '                </div>\n' +
                            // '                <div>\n' +
                            // '                    <p>已成交(' + data[i].main_coin_name + ')</p>\n' +
                            // '                    <p><span>' + parseFloat(data[i].deal_total).toFixed(4) + '</span></p>\n' +
                            // '                </div>\n' +
                            // '                <div>\n' +
                            // '                    <p>未成交(' + data[i].main_coin_name + ')</p>\n' +
                            // '                    <p><span>' + parseFloat(data[i].left_total).toFixed(4) + '</span></p>\n' +
                            // '                </div>\n' +
                            // '            </div>\n' +
                            '        </li>';
                    }
                    $('.historical-order >ul').append(str);
                    if (json.data.current_page >= json.data.last_page) {
                        $('.historical-order .more').hide();
                    } else {
                        $('.historical-order .more').show();
                    }
                    _this.page++;
                } else {
                    $('.historical-order .no_data').show();
                }
            });
        },

    });
    return View;
});


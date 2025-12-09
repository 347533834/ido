define(function (require, exports, modules) {
    'use strict';

    require('css!assets/css/my-commission/my-commission.css');
    require('assets/plugins/md5.js');
    var template = require('text!./market_lists.tpl.html'),
        util = require('util');

    var View = Backbone.View.extend({
        el: '#page',
        events: {
            'click .my-commission .more': function () {
                this.market_lists(this.page);
            },
            'click .buy_market': function (e) {
                var obj = $(e.currentTarget);
                var id = obj.attr('data-id');
                Backbone.history.navigate('buy_market?id=' + id, {trigger: true});
            },

        },
        initialize: function () {
            this.template = _.template(template);
            this.render();
        },
        render: function () {
            this.$el.html(this.template());
            $('header span').text("购买列表");

            this.loadUser();
        },
        loadUser: function () {
            this.user = window.user.get();
            if (!this.user) {
                Backbone.history.navigate('login', {trigger: true});
                return;
            }
            this.page = 1;
            this.market_lists(this.page);
        },
        market_lists: function (p) {
            var _this = this;
            var page = p ? p : 1;
            window.jsonpGet('Trade/market_lists', {
                token: this.user.token,
                page: page,
            }, 'market_lists', function (json) {
                var data = json.data.data;
                if (json.code == 1) {
                    var data = json.data.data,
                        type,
                        str = '';
                    for (var i = 0; i < data.length; i++) {
                        str += '<li>\n' +
                            '            <div class="purchase">\n' +
                            '                <p><span>' + (data[i].type == 1 ? '购买' : '出售') + data[i].name + '</span></p>\n' +
                            '                <p><span>' + new Date(data[i].addtime * 1000).format('yy-mm-dd hh:ii:ss') + '</span></p>\n' +
                            '            </div>\n' +
                            '            <div class="number">\n' +
                            '                <div>\n' +
                            '                    <p>数量(' + data[i].name + ')</p>\n' +
                            '                    <p><span>' + parseFloat(data[i].num) + '</span></p>\n' +
                            '                </div>\n' +
                            '                <div>\n' +
                            '                    <p>单价(USDT)</p>\n' +
                            '                    <p><span>' + parseFloat(data[i].price) + '</span></p>\n' +
                            '                </div>\n' +
                            '                <div>\n' +
                            '                    <p>总额(USDT)</p>\n' +
                            '                    <p><span>' + parseFloat(data[i].total) + '</span></p>\n' +
                            '                </div>\n' +
                            '            </div>\n' +
                            '            <div class="number">\n' +
                            '                <div>\n' +
                            '                    <p>手续费(USDT)</p>\n' +
                            '                    <p><span>' + parseFloat(data[i].fee) + '</span></p>\n' +
                            '                </div>\n' +
                            '                <div>\n' +
                            '                    <p>已成交(' + data[i].name + ')</p>\n' +
                            '                    <p><span>' + parseFloat(data[i].deal_num) + '</span></p>\n' +
                            '                </div>\n' +
                            '                <div>\n' +
                            '                    <p>未成交(' + data[i].name + ')</p>\n' +
                            '                    <p><span>' + parseFloat(data[i].left_num) + '</span></p>\n' +
                            '                </div>\n' +
                            '            </div>\n' +
                            '            <button class="buy_market" data-id="' + data[i].market_id + '">购买</button>'
                            '        </li>';
                    }
                    $('.my-commission >ul').append(str);
                    if (json.data.current_page >= json.data.last_page) {
                        $('.my-commission .more').hide();
                    } else {
                        $('.my-commission .more').show();
                    }
                    _this.page++;
                } else {
                    $('.my-commission .no_data').show();
                }
            });
        },


    });
    return View;
});


define(function (require, exports, modules) {
    'use strict';

    require('css!assets/css/index/index.css');
    require('css!assets/plugins/swiper/swiper-3.4.2.min.css');
    require('assets/plugins/swiper/swiper-3.4.2.min.js');
    require('assets/plugins/md5.js');

    require('assets/plugins/echarts');
    var template = require('text!./index.tpl.html'),
        util = require('util');

    var View = Backbone.View.extend({
        el: '#page',
        events: {
            'click .inform': function () {
                Backbone.history.navigate('notice', {trigger: true});
            },
            'click .volume': function (e) {
                $("#huobi-chart").remove();
                var obj = $(e.currentTarget);
                var trade = obj.attr('data-trade');

                if (obj.next().hasClass('none')) {
                    $('.trend-chart').addClass('none');
                    obj.next().removeClass('none');
                    var trade = obj.attr('data-trade');
                    obj.next().html('<div id="huobi-chart"></div>');
                    this.tradingview(trade, '1D', '1天');
                    // if (trade == 'IDO_USDT') {
                    //     this.tradingview(trade, '1D', '1天');
                    // } else {
                    //     this.tradingview(trade, 1, '1分');
                    // }
                } else {
                    $('.trend-chart').addClass('none');
                }
            },
        },
        initialize: function () {
            this.template = _.template(template);
            this.render();
        },
        render: function () {
            this.$el.html(this.template());
            $('header span').html('<img src="assets/img/index/logo-1.png" alt="">');
            $('header >.back').hide();

            this.first_picture();
            this.coin_trade();
            this.load_notice();
        },

        first_picture: function () {
            window.jsonpGet('index/first_picture', null, 'first_picture', function (json) {
                if (json.code == 1) {
                    var data = json.data;
                    var str = '';
                    for (var i in data) {
                        if (i != 'randomSort') {
                            //str += '<div class="swiper-slide"><a href="' + (data[i].jump_url == "" ? 'javascript:void(0)' : (data[i].jump_url.indexOf('http') == 0 ? data[i].jump_url : '#' + data[i].jump_url)) + '"><img src="' + util.cdn + data[i].url + '?' + data[i].addtime + '" alt=""/></a></div>';
                            str += ' <div class="swiper-slide"><img data-jump="' + data[i].jump_url + '" data-title="' + data[i].title + '" src="' + util.cdn + data[i].url + '?' + data[i].addtime + '" alt="" style="width: 100%;"/></a></div>'
                        }
                    }
                    $('div.swiper-wrapper').append(str);
                    var mySwiper = new Swiper('.swiper-container', {
                        direction: 'horizontal',
                        loop: true,
                        paginationClickable: true,
                        pagination: '.swiper-pagination',
                        autoplay: 5000,//自动播放时间
                        autoplayDisableOnInteraction: false,
                    })
                    $('.swiper-wrapper .swiper-slide img').click(function () {
                        var jump = $(this).data('jump');
                        if (jump) {
                            Backbone.history.navigate(jump, {trigger: true});
                        }

                    });
                }
            });
        },
        load_notice: function () {
            window.jsonpGet('index/load_notice', null, 'load_notice', function (json) {
                if (json.code == 1) {
                    var data = json.data;

                    $('#notice').html(json.data);

                }
            });
        },
        coin_trade: function () {
            var _this = this;
            window.jsonpGet('index/coin_trade', null, 'coin_trade', function (json) {
                if (json.code == 1) {
                    var data = json.data.data;
                    var str = '';
                    for (var i in data) {
                        if (i != 'randomSort') {
                            if (data[i].trade_name == 'IDOUSDT') {
                                var volume = parseFloat(json.data.ido_volume ? json.data.ido_volume : 0);
                                //var change = ((parseFloat(data[i].price) - parseFloat(json.data.ido_open_price)) / parseFloat(json.data.ido_open_price) * 100).toFixed(2);
                                data[i].price = parseFloat(json.data.ido_open_price);
                                data[i].price_cny = parseFloat(json.data.ido_open_price_cny);
                                var change = ((parseFloat(json.data.ido_open_price) - parseFloat(json.data.ido_open_price_old)) / parseFloat(json.data.ido_open_price) * 100).toFixed(2);
                            } else if (data[i].trade_name == 'ACHUSDT') {
                                var volume = parseFloat(json.data.ach_volume ? json.data.ach_volume : 0);
                                //var change = ((parseFloat(data[i].price) - parseFloat(json.data.ach_open_price)) / parseFloat(json.data.ach_open_price) * 100).toFixed(2);
                                data[i].price = parseFloat(json.data.ach_open_price);
                                data[i].price_cny = parseFloat(json.data.ach_open_price_cny);
                                var change = ((parseFloat(json.data.ach_open_price) - parseFloat(json.data.ach_open_price_old)) / parseFloat(json.data.ach_open_price) * 100).toFixed(2);
                            } else if (data[i].trade_name == 'FIDUSDT') {
                                var volume = parseFloat(json.data.fid_volume ? json.data.fid_volume : 0);
                                //var change = ((parseFloat(data[i].price) - parseFloat(json.data.ach_open_price)) / parseFloat(json.data.ach_open_price) * 100).toFixed(2);
                                data[i].price = parseFloat(json.data.fid_open_price);
                                data[i].price_cny = parseFloat(json.data.fid_open_price_cny);
                                var change = ((parseFloat(json.data.fid_open_price) - parseFloat(json.data.fid_open_price_old)) / parseFloat(json.data.fid_open_price) * 100).toFixed(2);
                            } else {
                                var volume = parseFloat(data[i].volume);
                                var change = ((parseFloat(data[i].price) - parseFloat(data[i].open_price)) / parseFloat(data[i].open_price) * 100).toFixed(2);
                                data[i].price_cny = parseFloat(data[i].price * json.data.usdt_cny).toFixed(4);
                            }
                            if (change > '0.00') {
                                var change_str = '<div style="background-color:#10BF90"><span>+' + change + '%</span></div>';
                                var price_str = '<p><span class="green">$' + parseFloat(data[i].price) + '</span></p>';
                            } else if (change < '0.00') {
                                var change_str = '<div style="background-color:#FF6060"><span>' + change + '%</span></div>';
                                var price_str = '<p><span class="red">$' + parseFloat(data[i].price) + '</span></p>';
                            } else {
                                var change_str = '<div style="background-color:#10BF90"><span>+0.00 %</span></div>';
                                var price_str = '<p><span class="green">$' + parseFloat(data[i].price) + '</span></p>';
                            }

                            str += '<dd>\n' +
                                '            <div class="volume" data-trade="' + data[i].exch_coin_name + '_' + data[i].main_coin_name + '">\n' +
                                '                <div>\n' +
                                '                    <img src="' + util.cdn + data[i].logo + '?' + data[i].addtime + '">\n' +
                                '                </div>\n' +
                                '                <div>\n' +
                                '                    <p><span>' + data[i].exch_coin_name + '</span></p>\n' +
                                '                    <p>量:<span>' + volume + '</span></p>\n' +
                                '                </div>\n' +
                                '                <div>\n' +
                                price_str +
                                '                    <p><span>￥' + data[i].price_cny + '</span></p>\n' +
                                '                </div>\n' +
                                change_str +
                                '            </div>\n' +
                                '            <div class="trend-chart none">\n' +
                                // '                <div id="huobi-chart">\n' +
                                // '                </div>\n' +
                                '            </div>\n' +
                                '        </dd>'
                        }
                    }
                    $('.content.index >dl').append(str);
                }
            });
        },
        tradingview: function (e, interval, type) {
            // var i = $("#locale").val();
            var _this = this;
            // SHTCOIN
            TradingView.onready(function () {
                var t = window.tvWidget = new TradingView.widget({
                    debug: true,
                    fullscreen: false,
                    symbol: e,
                    interval: interval,
                    container_id: "huobi-chart", // tv_chart_container
                    datafeed: new Datafeeds.UDFCompatibleDatafeed(util.server + "TradingView"),
                    library_path: "assets/plugins/tradingview/charting_library/",
                    locale: 'zh',
                    drawings_access: {
                        type: "black",
                        tools: [{
                            name: "Regression Trend"
                        }]
                    },
                    disabled_features: ["compare_symbol", "display_market_status", "go_to_date", "header_chart_type", "header_compare", "header_interval_dialog_button", "header_resolutions", "header_screenshot", "header_symbol_search", "header_undo_redo", "show_hide_button_in_legend", "show_interval_dialog_on_key_press", "snapshot_trading_drawings", "symbol_info", "timeframes_toolbar", "use_localstorage_for_settings", "volume_force_overlay"],
                    enabled_features: ["header_settings", "dont_show_boolean_study_arguments", "hide_last_na_study_output", "move_logo_to_main_pane", "same_data_requery", "side_toolbar_in_fullscreen_mode", "legend_context_menu"],
                    charts_storage_api_version: "1.1",
                    client_id: "bihaow.com",
                    user_id: "public_user_id",
                    loading_screen: {
                        backgroundColor: "＃000000"
                    },
                    timezone: "Asia/Shanghai",
                    toolbar_bg: "transparent",
                    overrides: {
                        "paneProperties.background": "#181B2A",
                        "paneProperties.vertGridProperties.color": "#454545",
                        "paneProperties.horzGridProperties.color": "#454545",
                        "symbolWatermarkProperties.transparency": 90,
                        "scalesProperties.textColor": "#AAA",
                        "paneProperties.legendProperties.showLegend": 0,
                        volumePaneSize: "medium",
                        "paneProperties.vertGridProperties.color": "#11253E",
                        "paneProperties.vertGridProperties.style": 0,
                        "paneProperties.horzGridProperties.color": "#11253E",
                        "paneProperties.horzGridProperties.style": 0,
                        "mainSeriesProperties.style": 9
                    },
                    studies_overrides: {
                        "macd.histogram.color": "red",
                        "macd.macd.color": "#55B3A8",
                        "macd.signal.color": "#C08DF0"
                    }
                });

                t.onChartReady(function () {
                    function i(e, i) {
                        t.chart().createStudy("Moving Average", !1, !1, [e], null, {
                            "plot.color.0": r[i]
                        })
                    }

                    t.chart().executeActionById("drawingToolbarAction"),
                        t.chart().executeActionById("hideAllMarks");
                    var r = ["yellow", "#84aad5", "#55b263"];
                    [5, 10, 30].forEach(i),
                        t.chart().createStudy("Moving Average", !1, !1, 5, function (e) {
                            t.chart().getStudyById(e).mergeDown()
                        }, {
                            "plot.color": "yellow"
                        }),
                        t.chart().createStudy("Moving Average", !1, !1, 10, function (e) {
                            t.chart().getStudyById(e).mergeDown()
                        }, {
                            "plot.color": "#84aad5"
                        }),
                        t.onContextMenu(function (e, i) {
                            return [{
                                position: "top",
                                text: "First top menu item, time: " + e + ", price: " + i,
                                click: function () {
                                    alert("First clicked.")
                                }
                            }, {
                                text: "-",
                                position: "top"
                            }, {
                                text: "-Objects Tree..."
                            }, {
                                position: "top",
                                text: "Second top menu item 2",
                                click: function () {
                                    alert("Second clicked.")
                                }
                            }, {
                                position: "bottom",
                                text: "Bottom menu item",
                                click: function () {
                                    alert("Third clicked.")
                                }
                            }]
                        });
                    if (e != 'IDO_USDT' && e != 'ACH_USDT') {
                        var n = [{
                            slug: "分时",
                            resolution: "1",
                            chartType: 3,
                            isMobile: !0
                        }, {
                            slug: "1分",
                            resolution: "1"
                        }, {
                            slug: "5分",
                            resolution: "5"
                        }, {
                            slug: "15分",
                            resolution: "15"
                        }, {
                            slug: "30分",
                            resolution: "30"
                        }, {
                            slug: "1小时",
                            resolution: "60"
                        }, {
                            slug: "1天",
                            resolution: "1D"
                        }, {
                            slug: "1周",
                            resolution: "1W"
                        }]
                            , a = function (e) {
                            var i = e.resolution
                                , t = e.chartType;
                            return "interval-" + i + "-" + (void 0 === t ? 1 : t)
                        }
                            , l = function (i) {
                            var r = i.resolution
                                , n = i.chartType
                                , l = void 0 === n ? 1 : n;
                            t.changingInterval || (t.setSymbol(e, r),
                            t.chart().chartType() !== l && t.applyOverrides({
                                "mainSeriesProperties.style": l
                            }),
                                t.selectedIntervalClass = a({
                                    resolution: r,
                                    chartType: l
                                }),
                                t.changingInterval = !1)
                        };
                    } else {
                        var n = [{
                            slug: "1天",
                            resolution: "1D"
                        }]
                            , a = function (e) {
                            var i = e.resolution
                                , t = e.chartType;
                            return "interval-" + i + "-" + (void 0 === t ? 1 : t)
                        }
                            , l = function (i) {
                            var r = i.resolution
                                , n = i.chartType
                                , l = void 0 === n ? 1 : n;
                            t.changingInterval || (t.setSymbol(e, r),
                            t.chart().chartType() !== l && t.applyOverrides({
                                "mainSeriesProperties.style": l
                            }),
                                t.selectedIntervalClass = a({
                                    resolution: r,
                                    chartType: l
                                }),
                                t.changingInterval = !1)
                        };
                    }

                    n.map(function (e) {
                        return t.createButton({
                            align: "left"
                        }).attr("title", "" + e.slug).addClass("1min" == e.slug || type == e.slug ? "selected" : "").on("click", function () {
                            l(e),
                                $(this).addClass("selected").parent().siblings().find("div").removeClass("selected")
                        }).append("<span id='" + e.resolution + "'>" + function (e) {
                            return top.window.LANG && top.window.LANG.kline && top.window.LANG.kline[e] || e
                        }(e.slug) + "</span>")
                    })
                })
            }())
        },
    });
    return View;
});


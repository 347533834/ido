define(function (require, exports, modules) {
    'use strict';

    require('css!assets/css/coin/coin.css');
    require('assets/plugins/md5.js');
    var template = require('text!./index.tpl.html'),
        util = require('util');

    var View = Backbone.View.extend({
        el: '#page',
        events: {
            'click #otc_buy': function (e) {
                this.otc_buy();
            },

            'click #otc_sell': function (e) {
                this.otc_sell();
            },

            'change #payType_sell': function (e) {
                var payType_sell = $(e.currentTarget).val();
                if (payType_sell == 'Bank') {
                    $('.select_bank').show();
                } else {
                    $('.select_bank').hide();
                }
            },

        },
        initialize: function () {
            this.template = _.template(template);

            this.render();
        },
        render: function () {
            this.$el.html(this.template());

            $('header span').text('OTC交易');

            this.loadUser();
        },
        loadUser: function () {
            this.user = window.user.get();
            if (!this.user) {
                Backbone.history.navigate('login', {trigger: true});
                return;
            }
            //this.show_otc();
        },

        // show_otc: function () {
        //     var _this = this;
        //     window.jsonpGet('Otc/show_otc', {
        //         token: _this.user.token,
        //     }, 'show_otc', function (json) {
        //         if (json.code == 1) {
        //             $('#buyListUrl').attr('data-jump', json.data.buyListUrl);
        //             $('#sellListUrl').attr('data-jump', json.data.sellListUrl);
        //         } else {
        //             window.toast(json.msg);
        //         }
        //     });
        // },

        otc_buy: function () {
            var num = parseFloat(parseFloat($('#count').val()).toFixed(2));
            var payType = $('#payType').val();
            if (isNaN(num) || num <= 0) {
                window.toast('请输入正确的购买数量');
                return false;
            }

            if (payType == '') {
                window.toast('请选择支付方式');
                return false;
            }

            var _this = this;
            window.confirm('确认购买吗？', function () {
                window.jsonpGet('otc/otc_buy', {
                    token: _this.user.token,
                    num: num,
                    payType: payType,
                }, 'otc_buy', function (json) {
                    console.log(json.code);
                    if (json.code == 1) {
                        window.alert(json.msg + ',请前往买单列表进行支付',function () {
                            window.location.reload();
                        });
                        // window.setTimeout(function () {
                        //     location.href = json.data;
                        // }, 1500)
                    } else {
                        window.toast(json.msg);
                        // if (json.data != '') {
                        //     window.setTimeout(function () {
                        //         location.href = json.data;
                        //     }, 1500)
                        // }
                    }
                });
            });
        },

        otc_sell: function () {
            var num = parseFloat(parseFloat($('#count_sell').val()).toFixed(2));
            var payType = $('#payType_sell').val();
            var real_name = $('#real_name').val();
            var bankCode = $('#bankCode').val();
            var no = $('#no').val();
            var bankBranch = $('#bankBranch').val();

            if (isNaN(num) || num <= 0) {
                window.toast('请输入正确的出售数量');
                return false;
            }

            if (payType == '') {
                window.toast('请选择收款方式');
                return false;
            }

            if (real_name == '') {
                window.toast('请输入真实姓名');
                return false;
            }

            if (payType == 'Bank') {
                if (!bankCode) {
                    window.toast('请选择收款银行');
                    return false;
                }
                if (!no) {
                    window.toast('请填写银行卡号');
                    return false;
                }
                if (!bankBranch) {
                    window.toast('请填写支行名称');
                    return false;
                }
            }

            var _this = this;
            window.confirm('确认出售吗？', function () {
                window.jsonpGet('otc/otc_sell', {
                    token: _this.user.token,
                    num: num,
                    payType: payType,
                    real_name: real_name,
                    bankCode: bankCode,
                    no: no,
                    bankBranch: bankBranch,
                }, 'otc_buy', function (json) {
                    if (json.code == 1) {
                        window.alert(json.msg, function () {
                            window.location.reload();
                        });
                        // window.toast(json.msg);
                        // window.setTimeout(function () {
                        //     location.href = json.data;
                        // }, 1500)
                    } else if (json.code == 22) {
                        window.toast(json.msg);
                        window.setTimeout(function () {
                            Backbone.history.navigate('security', {trigger: true});
                        }, 1500)
                    } else {
                        window.toast(json.msg);
                        // if (json.data != '') {
                        //     window.setTimeout(function () {
                        //         location.href = json.data;
                        //     }, 1500)
                        // }
                    }
                });
            });
        },

    });
    return View;
});


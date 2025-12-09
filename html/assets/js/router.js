/**
 * @author:         lirenyi
 * @update:         2016-06-10
 * @Description:    应用路由文件
 */

define(function (require, exports, modules) {

    // 配置baseUrl
    var baseUrl = document.getElementById('main').getAttribute('data-baseurl')

    var Backbone = require('backbone'),
        util = require('util'),
        BaseController = require('assets/js/baseController')

    // 路由表
    var routesMap = {
        // 默认路由
        'default': 'modules/loading/loading.ctl',
        'index': 'modules/index/index.ctl', // 首页

        'register': 'modules/login/register.ctl',  // 注册
        'login': 'modules/login/login.ctl',  // 登录
        'findpwd': 'modules/login/findpwd.ctl',  // 找回密码
        'down': 'modules/login/down.ctl', // 下载
        'notice': 'modules/notice/notice.ctl', // 公告
        'coin': 'modules/coin/coin.ctl', // 币币
        'coin2': 'modules/coin/coin2.ctl', // 币币
        'trade': 'modules/trade/index.ctl', // 币币
        'wallet': 'modules/wallet/wallet.ctl', // 钱包
        'my-commission': 'modules/my-commission/my-commission.ctl', // 我的委托
        'my-commission2': 'modules/my-commission/my-commission2.ctl', // 我的委托
        'market_lists': 'modules/my-commission/market_lists.ctl', // 下午场购买列表
        'buy_market': 'modules/my-commission/buy_market.ctl', // 下午场购买
        'historical-order': 'modules/historical-order/historical-order.ctl', // 历史订单
        'historical-order2': 'modules/historical-order/historical-order2.ctl', // 历史订单
        'extract': 'modules/extract/extract.ctl', // 提取USDT
        'bill': 'modules/bill/bill.ctl', // 账单
        'user': 'modules/user/user.ctl', // 我的
        'team': 'modules/team/team.ctl', // 我的直推
        'teams': 'modules/team/teams.ctl', // 我的团队
        'bonus': 'modules/bonus/bonus.ctl', // 分红
        'bonus-details': 'modules/bonus/bonus-details.ctl', // 分红
        'security': 'modules/security/security.ctl', // 安全中心
        'change-password': 'modules/change-password/change-password.ctl', // 修改密码
        'set-password': 'modules/set-password/set-password.ctl', // 设置资金密码
        'service': 'modules/service/service.ctl', // 联系客服
        'opinion': 'modules/opinion/opinion.ctl', //意见反馈
        'record': 'modules/record/record.ctl', //反馈记录
        'nickname': 'modules/nickname/nickname.ctl', //修改昵称
        'modify-nickname': 'modules/modify-nickname/modify-nickname.ctl', //修改昵称2
        'reset-password': 'modules/reset-password/reset-password.ctl', //重置资金密码
        'certification': 'modules/certification/certification.ctl', //实名认证
        'information': 'modules/information/information.ctl', //认证信息
        'invitation': 'modules/invitation/invitation.ctl', //邀请好友

        'notice-details': 'modules/notice/notice-details.ctl',// 公告详情

        'work-list': 'modules/work-list/work-list.ctl',//工单
        'feedback-record': 'modules/feedback-record/feedback-record.ctl',//反馈记录
        'feedback-record-details': 'modules/feedback-record-details/feedback-record-details.ctl',//反馈记录 详情

        //'otc': 'modules/otc/index.ctl',//otc
        'changeWechat': 'modules/security/changeWechat.ctl', // 微信收款码
        'changeAlipay': 'modules/security/changeAlipay.ctl', // 支付宝收款码

        'out-url': 'modules/otc/out-url.ctl', //外部链接页面
    };

    var Router = Backbone.Router.extend({
        routes: {
            '': 'navigator',
            ':p1': 'navigator',
            ':p1/:p2': 'navigator',
            ':p1/:p2/:p3': 'navigator',
            ':p1/:p2/:p3/:p4': 'navigator',
            ':p1/:p2/:p3/:p4/p5': 'navigator',
            ':p1/:p2/:p3/:p4/p5/p6': 'navigator',
            ':p1/:p2/:p3/:p4/p5/p6/p7': 'navigator',
            ':p1/:p2/:p3/:p4/p5/p6/p7(/*path)': 'navigator'
        },
        initialize: function () {
            util.baseController = util.baseController || new BaseController()
            util.baseController.on('change:router', function () {
                if (this.attributes.view) {
                    this.get('view').stopListening()
                    this.get('view').remove()
                }
                var url = arguments[1]
                var _this = this,
                    viewPath = url[0],
                    params = [].slice.call(url, 1)
                require([viewPath], function (Controller) {
                    _this.controller = new Controller({
                        rp: params,
                        baseController: _this
                    })
                })
            })
        },
        navigator: function () {
            var parameters = arguments.length > 1 ? [].slice.call(arguments, arguments.length - 1) : [],
                viewPath = [].slice.call(arguments, 0, arguments.length - 1).join('/')
            if (typeof routesMap[viewPath] == 'undefined') viewPath = 'default'

            util.currentHash = arguments[0]; // Backbone.history.getHash()
            util.currentPath = Backbone.history.getHash();
            util.baseController.set('router', _.union([baseUrl + routesMap[viewPath]], parameters))
        }
    });
    var router = new Router();

    return router
});

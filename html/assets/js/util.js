define(function (require, exports, modules) {

    // 当前是否为调试模式
    var isDebug = true; // TODO 发布时修改为 false, 开发时为 true

    // 定义工具类
    var util = util || {};
    util.isDebug = isDebug;

    var protocol = 'https:'
    if (isDebug) {
        protocol = document.location.protocol
    }


    // 服务器 IP
      if (isDebug) {
        // 测试
        util.domain = 'html.changex.info';
        util.host = '47.98.170.119:1001';
        util.port = '';
        util.cdn = 'trade-changex-oss.oss-cn-shanghai.aliyuncs.com';
      } else {
        // 生产
        util.domain = 'html.changex.info';
        util.host = 'api.changex.info';
        util.host_port = '';
        util.cdn = 'trade-changex-oss.oss-cn-shanghai.aliyuncs.com';
      }

    util.cdn = protocol + '//' + util.cdn + '/';
    util.domain = protocol + '//' + util.domain + '/';
    util.server = protocol + '//' + util.host + (util.host_port ? ':' + util.host_port : '') + '/';
    util.node = protocol + '//' + util.node + (util.node_port ? ':' + util.node_port : '') + '/';

    /**
     * 从请求链接中获取参数
     * @param name
     */
    util.queryString = function (name) {
        var reg = new RegExp('(\\?|&)' + name + '=([^(&|#)]*)(#|&|$)');
        var r = window.location.href.match(reg);
        return r ? r[2] : null;
    };

    /**
     * 加载模板
     * @param $elm
     * @returns {string}
     */
    util.loadTpl = function ($elm) {
        return $elm.length ? $elm.html() : ''; // .replace(/\<\&/g, '<%').replace(/\&\>/g, '%>')
    };

    /**
     * 版本号对比
     * @param local
     * @param online
     * @returns {boolean}
     */
    util.version_compare = function (local, online) {
        if (local == online) return false;
        var a1 = local.split('.');
        var a2 = online.split('.');
        for (var i = 0; i < a1.length; i++) {
            if (a1[i] != a2[i])
                return parseInt(a1[i]) < parseInt(a2[i]);
        }

        return true;
    };

    /**
     * 映射接口
     * @param api
     * @param params
     * @param callback
     * @param fn
     * @param err
     * @param loading
     * @returns {*}
     */
    window.jsonpGet = function (api, params, callback, fn, err, loading) {
        // if (!loading) window.loading.show()
        return $.ajax({
            dataType: 'jsonp',
            url: util.server + api,
            data: params,
            cache: false,
            success: function (reJson) {
                // if (!loading) window.loading.close();
                if (reJson.code == '11' || reJson.code == '12') {
                    window.user.del();
                    window.alert('会话超时，是否重新登录？', function () {
                        Backbone.history.navigate('loading', {trigger: true})
                    });
                } else if (reJson.code == '0') {
                    Backbone.history.navigate('loading', {trigger: true})
                } else if (reJson.code == 110) {
                    layer.open({
                        content: reJson.msg
                        , btn: '我知道了'
                        , yes: function (index) {
                            Backbone.history.navigate('loading', {trigger: true});
                            layer.close(index);
                        }
                    });
                } else {
                    if (reJson.msg) {
                        var msg = reJson.msg
                        if (msg.indexOf('|') > -1) reJson.msg = msg.split('|')[0]
                    }
                    if (fn) fn(reJson)
                }
            },
            error: function (e) {
                // window.loading.close()
                // toast('您的网络异常，请重试')
                if (err) err()
            }
        })
    };

    window.jsonPost = function (api, params, header, success, error) {
        window.loading.show()
        if (typeof (header) == "function") {
            if (success != undefined) error = success;
            success = header;
            header = {};
        }
        return $.ajax({
            url: util.server + api,
            data: params,
            type: 'post',
            header: header,
            dataType: 'json',
            cache: false,
            // contentType: 'application/json',
            success: function (reJson) {
                window.loading.close();
                if (success)
                    success(reJson);
            },
            error: function (e) {
                window.loading.close();
                if (error)
                    error(e);
            }
        })
    };

    window.jsonpPost = function (api, params, callback, fn, err, loading) {
        if (!loading) window.loading.show()
        return $.ajax({
            url: api,
            type: 'POST',
            dataType: 'JSON',
            data: params,
            success: function (reJson) {
                if (!loading) window.loading.close()
                if (reJson.code == 11 || reJson.code == 12) {
                    window.user.del()
                    window.confirm('会话超时，是否重新登录？', function () {
                        Backbone.history.navigate('loading', {trigger: true})
                    })
                } else if (reJson.code == 0) {
                    Backbone.history.navigate('loading', {trigger: true})
                } else if (reJson.code == 110) {
                    window.user.del()
                    layer.open({
                        content: reJson.msg
                        , btn: '我知道了'
                        , yes: function (index) {
                            Backbone.history.navigate('loading', {trigger: true});
                            layer.close(index);
                        }
                    });
                } else if (reJson.code == 9) {
                    window.toast(reJson.msg)
                    Backbone.history.navigate('my_idname', {trigger: true})
                } else if (reJson.code == 111) {
                    window.user.del();
                    layer.open({
                        content: reJson.msg
                        , btn: '我知道了'
                        , yes: function (index) {
                            window.location.href = reJson.data.url;
                            layer.close(index);
                        }
                    });
                } else {
                    if (reJson.msg) {
                        var msg = reJson.msg
                        if (msg.indexOf('|') > -1) reJson.msg = msg.split('|')[0]
                    }
                    if (fn) fn(reJson)
                }
            },
            error: function (e) {
                window.loading.close();
                //toast('您的网络异常，请重试');
                if (err) err();
            }
        });
    };

    /**
     * 公链节点接口
     * @param url
     * @param data
     * @param method
     * @param header
     * @param success
     * @param error
     */
    util.fetch = function (url, data, method, header, success, error) {
        if (typeof (header) == "function") {
            if (success != undefined) error = success;
            success = header;
            header = {};
        }
        $.ajax({
            url: util.node + url,
            data: data,
            type: method,
            headers: header,
            dataType: 'json',
            // contentType: 'application/json',
            cache: false,
            success: function (result) {
                if (success)
                    success(result);
            },
            error: function (e) {
                if (error)
                    error(e)
            }
        });
    };
    /**
     * 判断为空 或 全空格
     * @param str
     * @returns {boolean}
     */
    util.isVacancy = function (str) {
        if (str == "") return true;
        var regu = "^[ ]+$";
        var re = new RegExp(regu);
        return re.test(str);
    }


    /**
     * JS 判空
     * @param x
     * @returns {boolean}
     */
    util.isNull = function (x) {
        return x == null || x == undefined || x == '';
    };


  /**
   * 字符串显示首尾
   * @param s
   * @returns {string}
   */
  util.hideMid = function (s, l1, l2, m, t) {
    if (!l1) l1 = 8;
    if (!l2) l2 = 10;
    if (!m) m = '...';
    if (!t) t = '';
    return s ? s.substr(0, l1) + m + s.substr(s.length - l2) : t;
  };


  /**
     * 转换 int
     * @param int
     * @returns {number | *}
     */
    util.parseInt = function (int) {
        int = parseInt(int);
        if (isNaN(int)) int = 0;
        return int;
    };

    /**
     * 转换 float
     * @param float
     * @param digits
     * @returns {any}
     */
    util.parseFloat = function (float, digits) {
        float = parseFloat(float);
        if (isNaN(float)) float = 0;

        return digits ? float.toFixed(digits) : float;
    };

    /**
     * 转换余额
     * @param balance 余额
     * @param digits 小数位数
     * @param precision 精度
     * @returns {*}
     */
    util.balance = function (balance, digits, precision) {
        balance = parseFloat(balance);
        if (isNaN(balance) || balance == 0) return 0;

        precision = parseInt(precision);
        if (isNaN(precision)) precision = util.asset.precision;

        balance = balance / Math.pow(10, precision);
        return digits ? balance.toFixed(digits) : balance;
    };

    /**
     * 交易类型
     * @param val
     * @returns {string}
     */
    util.trsType = function (val) {
        const TYPE_LABEL = [
            'TRS_TRANSFER',
            'TRS_SECOND_PASSWORD',
            'TRS_DELEGATE',
            'TRS_VOTE',
            'TRS_MULTISIGNATURE',
            'TRS_DAPP',
            'TRS_DEPOSIT',
            'TRS_WITHDRAWAL',
            'TRS_STORAGE',
            'TRS_UIA_ISSUER',
            'TRS_UIA_ASSET',
            'TRS_UIA_FLAGS',
            'TRS_UIA_ACL',
            'TRS_UIA_ISSUE',
            'TRS_UIA_TRANSFER'
        ];
        return val === 100 ? 'TRS_TYPE_LOCK' : TYPE_LABEL[val];
    };

    /**
     * 交易类型中文
     * @param val
     * @returns {string}
     */
    util.trsTypeText = function (val) {
        /*
        // transaction type filter
        TRS_TYPE_TRANSFER: '转账',
        TRS_TYPE_SECOND_PASSWORD: '二级密码',
        TRS_TYPE_DELEGATE: '受托人',
        TRS_TYPE_VOTE: '投票',
        TRS_TYPE_MULTISIGNATURE: '多重签名',
        TRS_TYPE_DAPP: '注册应用',
        TRS_TYPE_DEPOSIT: '应用充值',
        TRS_TYPE_WITHDRAWAL: '应用提现',
        TRS_TYPE_STORAGE: '存储',
        TRS_TYPE_UIA_ISSUER: '注册发行商',
        TRS_TYPE_UIA_ASSET: '注册资产',
        TRS_TYPE_UIA_FLAGS: '资产设置',
        TRS_TYPE_UIA_ACL: '资产访问控制',
        TRS_TYPE_UIA_ISSUE: '资产发行',
        TRS_TYPE_UIA_TRANSFER: '资产转账',
        TRS_TYPE_LOCK: '锁仓'
        */
        const TYPE_LABEL = [
            '转账',
            '设置密码',
            '注册矿工',
            '投票',
            '多重签名',
            '注册应用',
            '应用充值',
            '应用提现',
            '存储',
            '注册发行商',
            '注册资产',
            '资产设置',
            '资产访问控制',
            '发行资产',
            '资产转账'
        ];
        return val === 100 ? '锁仓' : TYPE_LABEL[val];
    }

    return util
});

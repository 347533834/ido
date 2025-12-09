/**
 * Created by baby520 on 2016/7/6.
 */
;!function (window, undefined) {

  window.JSON = JSON || {
    stringify: function (o) {
      return $.toJSON(o) || '{}'
    },
    parse: function (s) {
      return $.parseJSON(s) || {}
    }
  };

  /**
   * 提示 3 秒后自动关闭
   * @param text
   * @param time
   */
  window.toast = function (text, time) {
    if (isNaN(parseInt(time))) time = 3;
    layer.open({content: text, skin: 'msg', time: time});
  };

  /**
   * 过滤非法字符
   * @param text
   * @param time
   */
  window.filter = function (val,obj) {
    var pattern = RegExp("['</>!#$%^&*()+=:]");
    if(pattern.test(val)){
      obj.val('');
      window.toast('非法字符!');
      return false;
    }else{
      return true
    }
  };


  /**
   * 信息框
   * @param msg
   */
  window.alert = function (text, yes) {
    layer.open({
      content: text, shadeClose: false, btn: '确定', yes: function (index) {
        layer.close(index);
        if (yes) yes();
      }
    });
  };

  /**
   * 询问框
   * @param text
   * @param yes
   * @param no
   */
  window.confirm = function (text, yes, no) {
    layer.open({
      content: text,
      shadeClose: false,
      btn: ['确定', '取消'],
      yes: function (index) {
        layer.close(index);
        if (yes) yes();
      },
      no: function (index) {
        layer.close(index);
        if (no) no();
      }
    });
  };

  /**
   * 判断是否微信
   * @returns {boolean}
   */
  window.isWeiXin = function () {
    var ua = window.navigator.userAgent.toLowerCase();
    //console.log(ua);//mozilla/5.0 (iphone; cpu iphone os 9_1 like mac os x) applewebkit/601.1.46 (khtml, like gecko)version/9.0 mobile/13b143 safari/601.1
    if (ua.match(/MicroMessenger/i) == 'micromessenger') {
      return true;
    } else {
      return false;
    }
  };

  /**
   * 获取URL参数
   * @param name
   * @returns {*}
   */
  window.getQueryString = function (name) {
    var reg = new RegExp("(^|&)" + name + "=([^&]*)(&|$)", "i");
    var r = window.location.search.substr(1).match(reg);
    if (r != null) return unescape(r[2]);
    return null;
  };

  /**
   * loading 类
   * @type {{index: number, show: Window.loading.show, close: Window.loading.close}}
   */
  window.loading = {
    index: 0,
    show: function () {
      this.index = layer.open({type: 2, shadeClose: false});
    },
    close: function () {
      layer.close(this.index)
    }
  };

  /**
   * 存储类
   * @type {{set: Window.storage.set, get: Window.storage.get, del: Window.storage.del}}
   */
  window.storage = {
    set: function (key, val) {
      if (typeof(val) !== "string")
        val = JSON.stringify(val);

      return window.localStorage.setItem(key, val);
    },
    get: function (key) {
      return window.localStorage.getItem(key);
    },
    del: function (key) {
      return window.localStorage.removeItem(key);
    }
  }

  /**
   * 用户操作
   * @type {{user: {}, set: Window.user.set, get: Window.user.get, del: Window.user.del}}
   */
  window.user = {
    user: {},
    set: function (user) {
      window.storage.set('user', user);
      this.user = user;
    },
    get: function () {
      var userInfo = window.storage.get('user');
      if (!userInfo) return false;

      var user = JSON.parse(userInfo);
      if (!user||!user.mobile) return false;

      return this.user = user;
    },
    del: function () {
      this.user = false;
      window.storage.del('user');
    }
  };

  window.lang = {
    lang: null,
    set: function (lang) {
      window.storage.set('lang', lang);
      this.lang = lang;
    },
    get: function () {
      if (this.lang) return this.lang;

      this.lang = window.storage.get('lang');
      if (this.lang) return this.lang;

      this.lang = 'cn';
      this.set(this.lang);
      return this.lang;
    },
    switch: function () {
      this.lang = this.get() == 'en' ? 'cn' : 'en';
      this.set(this.lang);
      return this.lang;
    }
  };

  window.toDecimal = function (x, e) {
    var f = parseFloat(x);
    if (isNaN(f)) {
      return false;
    }
    var t = 1, y = e;
    for (; e > 0; t *= 10, e--) ;
    for (; e < 0; t /= 10, e++) ;

    var f = Math.round(x * t) / t;
    var s = f.toString();
    var rs = s.indexOf('.');
    if (rs < 0) {
      rs = s.length;
      s += '.';
    }
    while (s.length <= rs + y) {
      s += '0';
    }
    return s;
  };

	/**
     * 搜索历史
	 * @type {{searchHistory: {}, set: Window.searchHistory.set, get: Window.searchHistory.get, del: Window.searchHistory.del}}
	 */
  window.searchHistory = {
	  searchHistory: {},
      set: function (user) {
          window.storage.set('searchHistory', user);
          this.searchHistory = searchHistory;
      },
      get: function () {
          var userInfo = window.storage.get('searchHistory');
          if (!userInfo) return false;

          var user = JSON.parse(userInfo);
          if (!user.mobile) return false;

          return this.searchHistory = searchHistory;
      },
      del: function () {
          this.searchHistory = false;
          window.storage.del('searchHistory');
      }
  };

  window.config = {
    config: {},
    set: function (config) {
      window.storage.set('config', config);
      this.config = config;
    },
    get: function () {
      var configInfo = window.storage.get('config');
      if (!configInfo) return false;

      return this.config = JSON.parse(configInfo);
    },
    del: function () {
      this.config = false;
      window.storage.del('config');
    }
  };

}(window);

// 格式化时间
if (!Date.prototype.format) {
  Date.prototype.format = function (format) {
    var dateRegExpList = {
      'm+': this.getMonth() + 1,
      'd+': this.getDate(),
      'h+': this.getHours(),
      'i+': this.getMinutes(),
      's+': this.getSeconds()
    }
    if (/(y+)/.test(format)) {
      format = format.replace(RegExp.$1, this.getFullYear().toString().substr(4 - RegExp.$1.length))
    }

    _.each(dateRegExpList, function (val, regExp) {
      if (new RegExp('(' + regExp + ')').test(format)) {
        var numStr = '0' + val.toString()
        format = format.replace(RegExp.$1, numStr.length > 2 ? numStr.substr(numStr.lastIndexOf('0', numStr.length - 2) + 1) : numStr)
      }
    })

    return format
  }
}

// 将日期转换成 timestamp
if (!String.prototype.toTime) {
  String.prototype.toTime = function () {
    return new Date(Date.parse(this.replace(/-/g, '/')))
  }
}

if (!Array.prototype.randomSort) {
  Array.prototype.randomSort = function () {
    var arr = this;
    var i, j, temp;
    for (i = arr.length - 1; i > 0; i--) {
      j = Math.floor(Math.random() * (i + 1));
      temp = arr[i];
      arr[i] = arr[j];
      arr[j] = temp;
    }
    return arr;
  }
}

function isEmptyObject(obj) {
  for (var key in obj) {
    return false;
  }
  return true;
}

function minute(i) {
  if (i > 1440) return Math.ceil(i / 1440) + '天';
  if (i > 60) return Math.ceil(i / 60) + '小时';
  return i + '分钟';
}

/**
 * 倒计时
 * @param addtime
 * @returns {string}
 */
// function countDown(addtime) {
//   var leftSecond = (addtime - new Date().getTime()) / 1000;
//   if (leftSecond <= 0)
//     return '';
//
//   var day = Math.floor(leftSecond / (60 * 60 * 24));
//   var str = '';
//   if (day > 0) str += day + ' ';
//
//   var hour = Math.floor((leftSecond - day * 24 * 60 * 60) / 3600);
//   if (hour > 0) str += hour + ':';
//
//   var minute = Math.floor((leftSecond - day * 24 * 60 * 60 - hour * 3600) / 60);
//   if (minute > 0) str += minute + ':'
//
//   return str + Math.floor(leftSecond - day * 24 * 60 * 60 - hour * 3600 - minute * 60);
// }

function countDown(addtime,nowtime) {
  if (!nowtime) nowtime = new Date().getTime();
  var leftSecond = (addtime - nowtime) / 1000;
  if (leftSecond <= 0)
    return '';

  // var day = Math.floor(leftSecond / (60 * 60 * 24));
  var str = '';
  // if (day > 0) str += day + ' ';

  var hour = Math.floor(leftSecond / 3600);
  if (hour > 0) str += hour + ':';

  var minute = Math.floor((leftSecond - hour * 3600) / 60);
  if (minute > 0) str += minute + ':'

  return str + Math.floor(leftSecond - hour * 3600 - minute * 60);
}

/**
 * 大金额显示
 * @param f
 * @returns {string}
 */
function showAmount(amount, digit) {
  if (!digit) digit = 2;
  return amount > 100000000 ? (amount / 100000000).toFixed(4) + ' 亿' : amount.toFixed(digit);
}

//保留x位只舍不入
window.avg = function (a,x) {
    var y = Math.pow(10,x);
    var res = parseInt(a * y) / y;
    return res;
}

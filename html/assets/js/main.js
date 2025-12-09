/**
 * @author:         lirenyi
 * @update:         2016-06-10
 * @Description:    应用入口文件
 */

'use strict'

;(function (win) {
  // 配置baseUrl
  var baseUrl = document.getElementById('main').getAttribute('data-baseurl')

  /*
   * 文件依赖
   */
  var config = {
    baseUrl: baseUrl, // 依赖相对路径
    // urlArgs: "v=" + (new Date()).getTime(),
    paths: { // 如果某个前缀的依赖不是按照baseUrl拼接这么简单，就需要在这里指出
      jquery: 'assets/plugins/jquery/jquery-2.1.4.min',
      underscore: 'assets/plugins/underscore',
      backbone: 'assets/plugins/backbone',
      text: 'assets/plugins/text', // 用于 requirejs 导入html类型的依赖
      css: 'assets/plugins/css', // 用于 requirejs 导入css类型的依赖
      util: 'assets/js/util',
      router: 'assets/js/router',
      common: 'assets/js/common',
      echarts: 'assets/plugins/echarts',
      fastclick: 'assets/plugins/fastclick',
      l10n: 'assets/plugins/l10n.min',
      // qrcode: 'assets/plugins/jquery/jquery.qrcode.min'
    },
    shim: { // 引入没有使用 requirejs 模块写法的类库。backbone 依赖 underscore
      'underscore': {
        exports: '_'
      },
      'jquery': {
        exports: '$'
      },
      'util': {
        deps: ['jquery'],
        exports: 'util'
      },
      'common': {
        deps: ['jquery']
      },
      'backbone': {
        deps: ['underscore', 'jquery'],
        exports: 'Backbone'
      },
      'router': {
        deps: ['backbone']
      },
      // 'l10n': {
      //   exports: 'L10N'
      // }
    },
  }

  require.config(config)

  // Backbone会把自己加到全局变量中
  require(['backbone', 'underscore', 'router', 'util', 'common', 'fastclick', 'l10n'], function () { //
    Backbone.history.start(); // 开始监控 url 变化

    window.timeouts = [];
    window.intervals = [];

    // console.log(window.sercjs);
  })
})(window);

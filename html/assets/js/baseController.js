/**
 * @Description 默认控制器
 * @author lirenyi
 * @update 2016-06-10
 */
define(function (require, exports, modules) {
    'use strict';

    var Controller = Backbone.Model.extend({
        defaults: function () {
            return {
                router: "",
                view: null,
                base: null
            };
        }
    });

    return Controller;
});
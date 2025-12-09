
define(function (require, exports, modules){
    'use strict';
    var Base = require('modules/base/base.ctl'),
        View = require('./login.view');

    var controller = function () {
        if(arguments[0].baseController.attributes.base){
            arguments[0].baseController.get('base').render();
        }else{
            new Base(arguments[0]);
        }
        var view = new View();
        arguments[0].baseController.set('view' , view);
    };

    return controller;
});
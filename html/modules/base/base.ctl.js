/**
 * @Description loading页面
 * @author lirenyi
 * @update 2016-08-18
 */
define(function (require, exports, modules){
	'use strict';
	var View = require('./base.view');

	var controller = function () {
		if(arguments[0].baseController.attributes.base){
    		arguments[0].baseController.get('base').render();
    	}else{
    		var view = new View();
    		arguments[0].baseController.set('base' , view);
    	}
	};
    
	return controller;
});
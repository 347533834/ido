//头部二级菜单的显示隐藏
$("#dropBtn").click(function(){
	$("#dropMeun").toggleClass("show");
});

//二级菜单中的选项卡
//$("#dropMeun>.title .title-item").click(function(){
//	var index = $(this).index();
//	$(this).addClass("active").siblings().removeClass("active");	//标题样式
//	
//	//内容的显示影藏
//	$("#dropMeun .details").eq(index).addClass("show").siblings().removeClass("show");
//	
//});
//



//tabbar点击
$("#tabbar>.tabbar-item").click(function(){
	$("#tabbar>.tabbar-item").removeClass("active");
	$(this).addClass("active");
});

//tranNav  交易中心顶部导航斜杠
$("#tranNav>.nav-item").click(function(){
	$(this).addClass("active").siblings(".nav-item").removeClass("active");
});



//是否成功卖出
var isSale = 3;
//弹框JDC
$("#sale").click(function(){
	if(isSale == 0){//提交成功
		$("#okSaleBox").show();
	}else if(isSale == 1){//无法交易
		$("#notJDCbox").show();
		setTimeout(function(){
			$("#notJDCbox").fadeOut(1000);
		},1000);
	}else if(isSale == 2){//支付方式
		$("#receiptBox").show();
	}else if(isSale == 3){ //加单成功
		$("#hangSucceedBox").show();
		setTimeout(function(){
			$("#hangSucceedBox").fadeOut(1000);
		},1000);
	}
});

$("#close-ok").click(function(){
	$("#okSaleBox").hide();
});

$("#receipt-wx,#receipt-zfb").click(function(){
	$("#receiptBox").hide();
});


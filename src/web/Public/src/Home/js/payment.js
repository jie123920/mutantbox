// JavaScript Document
$.fn.extend({
	Payment:function(user_opt){
		var default_opt = {
				payway:2,//0-支付宝；1-adyen；2-paypmentwall;3-paypal
			//callback:function(index,action){}
		};
		
		opt = jQuery.extend(true,{},default_opt,user_opt);
		var k = {};			
		k.selector = $(this).selector;
		if($(this).length>1){
			$.error('Payment error[More than one selected object]');
			return false;	
		}
		k.self = this;		
		
		k.run = function(){			
			$('.pay_language .hd').click(function () {
	            if ($(this).parent().hasClass('on')) {
	                $(this).parent().removeClass('on');
	            } else {
	                $(this).parent().addClass('on');
	            }
	        })
	        
	        $(".slideTxtBox").slide({trigger: "click", delayTime: 0, startFun:function( i,c,s ){
	        	var sid = $('.game_'+i).val();
	        	if(sid != '')
	        		opt.gameid = sid;

	        	opt.payway = i;
	        }});

			//内容初始化 
	    	if(opt.gameid != ''){	    		
	    		$.each(opt.gamelist, function(i, n){
	    			if(n.game_id == opt.gameid){	    			
	    				$('[id='+opt.paylistname+']').each(function(w){
	    					if(w == 0){
	    						$(this).load(opt.paylist,{gameid:opt.gameid,areaid:n.server[0].server_id});
	    					}else if(w == 1){
	    						$(this).load('/adyen/index.html',{gameid:opt.gameid,areaid:n.server[0].server_id});
	    					}else{	    						
	    						$(this).load('/paymentwall/index.html',{gameid:opt.gameid,areaid:n.server[0].server_id});
	    					}	    					
	    				})
	    				
	    				$.each(n.server, function(k,v){
	    					$('[id='+opt.areaname+']').each(function(){
			    				$(this).append("<option value='"+v.server_id+"'>"+'S'+v.server_id+'-'+v.server_name+"</option>");
			    			});
	    				});
			    		
	    			}

	    		});
				
				$(".hd ul li:visible").trigger("click");
	    	}
	    	
	    	//选择游戏改进
	    	$('.game_'+(opt.payway)).change(function(){
	    		opt.gameid = $('.game_'+opt.payway).val() == ''?opt.firstgameid:$('.game_'+opt.payway).val();
	    		if(opt.gameid == 2)
	    			opt.gameid = 14;
	    		$('.area_'+opt.payway).empty();
	    		$('.area_'+opt.payway).append("<option selected='selected'>Select a server</option>");
	   			
	   			$('.paylist_'+opt.payway).empty();
	   			var k=0;
	   			if(parseInt(opt.gameid) != 'NaN'){
	   				$.each(opt.jslist[opt.gameid]['child'], function(key, val) {
		    			if(k == 0){
		    				$('.paylist_'+opt.payway).load(opt.paylist,{gameid:opt.gameid,areaid:key});
		    			}
		    			$('.area_'+opt.payway).append("<option value='"+key+"'>"+val.name+"</option>");
						k++;
					});
	   			}	    		
	    	});
	    	
	    	$('.game_1').change(function(){
	    		opt.gameid = $('.game_'+opt.payway).val() == ''?opt.firstgameid:$('.game_'+opt.payway).val();
	    		$('.area_'+opt.payway).empty();
	    		$('.area_'+opt.payway).append("<option selected='selected'>Select a server</option>");
	   			
	   			$('.paylist_'+opt.payway).empty();
	   			var k=0;
	    		$.each(opt.jslist[opt.gameid]['child'], function(key, val) {
	    			if(k == 0){
	    				$('.paylist_'+opt.payway).load('/adyen/index.html',{gameid:opt.gameid,areaid:key});
	    			}
	    			$('.area_'+opt.payway).append("<option value='"+key+"'>"+val.name+"</option>");
					k++;
				});
	    	});
	    	
	    	$('.game_2').change(function(){
	    		opt.gameid = $('.game_'+opt.payway).val() == ''?opt.firstgameid:$('.game_'+opt.payway).val();
	    		$('.area_'+opt.payway).empty();
	    		$('.area_'+opt.payway).append("<option selected='selected'>Select a server</option>");
	   			
	   			$('.paylist_'+opt.payway).empty();
	   			var k=0;
	    		$.each(opt.jslist[opt.gameid]['child'], function(key, val) {
	    			if(k == 0){
	    				$('.paylist_'+opt.payway).load('/paymentwall/index.html',{gameid:opt.gameid,areaid:key});
	    			}
	    			$('.area_'+opt.payway).append("<option value='"+key+"'>"+val.name+"</option>");
					k++;
				});
	    	});
	    	
	    	//选择服务器改进
	    	$('.area_'+opt.payway).change(function(){
	    		opt.areaid = $('.area_'+opt.payway).val();
	    		if(!isNaN(opt.areaid)){
	    			$('.paylist_'+opt.payway).empty();
		    		$('.paylist_'+opt.payway).load(opt.paylist,{gameid:opt.gameid,areaid:opt.areaid});
		    		
		    		$.get("/payment/getuname",{serverid:opt.areaid}, function(data){
		    			$('.role_name_'+opt.payway).empty();
		    			$('.role_name_'+opt.payway).append(data);
	    			});
	    		}
	    	});	
	    	
	    	
	    	$('.area_1').change(function(){
	    		opt.areaid = $('.area_'+opt.payway).val();
	    		if(!isNaN(opt.areaid)){
	    			$('.paylist_'+opt.payway).empty();
		    		$('.paylist_'+opt.payway).load('/adyen/index.html',{gameid:opt.gameid,areaid:opt.areaid});
		    		
		    		$.get("/payment/getuname",{serverid:opt.areaid}, function(data){
		    			$('.role_name_'+opt.payway).empty();
		    			$('.role_name_'+opt.payway).append(data);
	    			});
	    		}
	    	});
	    	
	    	$('.area_2').change(function(){
	    		opt.areaid = $('.area_'+opt.payway).val();
	    		if(!isNaN(opt.areaid)){
	    			$('.paylist_'+opt.payway).empty();
		    		$('.paylist_'+opt.payway).load('/paymentwall/index.html',{gameid:opt.gameid,areaid:opt.areaid});
		    		
		    		$.get("/payment/getuname",{serverid:opt.areaid}, function(data){
		    			$('.role_name_'+opt.payway).empty();
		    			$('.role_name_'+opt.payway).append(data);
	    			});
	    		}
	    	});	    	
   	
		};
		
		k.run();
	}	
});
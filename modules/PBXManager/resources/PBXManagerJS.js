/*+***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 *************************************************************************************/

var Vtiger_PBXManager_Js = {
        //SalesPlatform.ru begin PBXManager porting
        showPnotify : function(customParams) {
            return $.pnotify($.extend({
                sticker: false,
                delay: '3000',
                type: 'error',
                pnotify_history: false
            }, customParams));
        },
        //SalesPlatform.ru end PBXManager porting
	/**
	 * Function registers PBX for popups
	 */
	registerPBXCall : function() {
            Vtiger_PBXManager_Js.requestPBXgetCalls();
	},

	/**
	 * Function registers PBX for Outbound Call
	 */
	registerPBXOutboundCall : function(number,record) {
		Vtiger_PBXManager_Js.makeOutboundCall(number,record);
	},
	/**
	 * Function request for PBX popups
	 */
	requestPBXgetCalls : function() {
		var url = 'index.php?module=PBXManager&action=IncomingCallPoll&mode=searchIncomingCalls';
                // Salesplatform.ru begin PBXManager porting
		app.request.get({url : url}).then(function(err, data){
                    if (typeof data === 'string') {
                        location.href = 'index.php';
                    }
                    
                    
			if(!err) {
				for(var i = 0; i < data.length; i++) {
					var record = data[i];
                                        //app.request.get(url).then(function(data){
                                        //if(data.success && data.result) {
                                        //        for(i=0; i< data.result.length; i++) {
                                        //                var record = data.result[i];
                                        //SalesPlatform.ru end PBXManager porting
					if(jQuery('#pbxcall_'+record.pbxmanagerid+'').size()== 0 ) {
                                            Vtiger_PBXManager_Js.showPBXIncomingCallPopup(record);
                                        } else {
                                            Vtiger_PBXManager_Js.updatePBXIncomingCallPopup(record);
					}
				}
                        }
		});
		Vtiger_PBXManager_Js.removeCompletedCallPopup();
	},

	/**
	 * Function display the PBX popup
	 */
	showPBXIncomingCallPopup : function(record) {
            // Salesplatform.ru begin PBXManager porting
            var contactFieldStyle = ((record.customer != null && record.customer != '') ? 'hide' : '');
            //SalesPlatform.ru end PBXManager porting
		var params = {
			title: app.vtranslate('JS_PBX_INCOMING_CALL'),
			//SalesPlatform.ru begin
            text: '<div class="row-fluid pbxcall" id="pbxcall_'+record.pbxmanagerid+'" callid='+record.pbxmanagerid+' style="color:black">' + 
                    '<span class="span12" id="caller" value="'+record.customernumber+'">'+app.vtranslate('JS_PBX_CALL_FROM')+' : '+record.customernumber+'</span><span class="span12 ' + contactFieldStyle + '" id="contactsave_'+record.pbxmanagerid+'">\n\
                   <span><input class="span3" id="email_'+record.pbxmanagerid+'" type="text" placeholder="' + app.vtranslate('Enter Email-id') + '"></input>&nbsp;&nbsp;&nbsp;<select class="input-medium" id="module_'+record.pbxmanagerid+'"><option value="Select" selected>' + app.vtranslate('Select') + '</option></select><h5 class="alert-danger hide span3" id="alert_msg">'+app.vtranslate('JS_PBX_FILL_ALL_FIELDS')+'</h5>\n\
                   <button class="btn btn-success pull-right"  id="pbxcontactsave_'+record.pbxmanagerid+'" recordid="'+record.pbxmanagerid+'" type="submit">' + app.vtranslate('Save') + '</button>\n\
                   </span></span><br/><span class="span12" style="display:none" id="owner">'+app.vtranslate('JS_LBL_ASSIGNED_TO')+'&nbsp;:&nbsp;<span id="ownername"></span></span></div>',
                
            //text: '<div class="row-fluid pbxcall" id="pbxcall_'+record.pbxmanagerid+'" callid='+record.pbxmanagerid+' style="color:black"><span class="span12" id="caller" value="'+record.customernumber+'">'+app.vtranslate('JS_PBX_CALL_FROM')+' : '+record.customernumber+'</span><span class="hide span12" id="contactsave_'+record.pbxmanagerid+'">\n\
            //        <span><input class="span3" id="email_'+record.pbxmanagerid+'" type="text" placeholder="Enter Email-id"></input>&nbsp;&nbsp;&nbsp;<select class="input-small" id="module_'+record.pbxmanagerid+'" placeholder="Select"><option>Select</option></select><h5 class="alert-danger hide span3" id="alert_msg">'+app.vtranslate('JS_PBX_FILL_ALL_FIELDS')+'</h5>\n\
            //        <button class="btn btn-success pull-right"  id="pbxcontactsave_'+record.pbxmanagerid+'" recordid="'+record.pbxmanagerid+'" type="submit">Save</button>\n\
            //        </span></span><br/><span class="span12" style="display:none" id="answeredby"><i class="icon-headphones"></i>&nbsp;<span id="answeredbyname"></span></span></div>',
            //SalesPlatform.ru end
            width: '28%',
			min_height: '75px',
			addclass:'vtCall',
			icon: 'vtCall-icon',
			hide : false,
			closer : false,
			type:'info',
			after_open:function(p) {
				jQuery(p).data('info', record);
			}
		};
                // Salesplatform.ru begin PBXManager porting
                Vtiger_PBXManager_Js.showPnotify(params);
                //Vtiger_Helper_Js.showPnotify(params);
                //SalesPlatform.ru end PBXManager porting
                    
		//To remove the popup for all users except answeredby (existing record)
		if(record.user) {
                    if(record.user != record.current_user_id) {
                            Vtiger_PBXManager_Js.removeCallPopup(record.pbxmanagerid);
                    }
		}

		// To check if it is new or existing contact
		Vtiger_PBXManager_Js.checkIfRelatedModuleRecordExist(record);

		if(record.answeredby!=null){
			jQuery('#answeredbyname','#pbxcall_'+record.pbxmanagerid+'').text(record.answeredby);
			jQuery('#answeredby','#pbxcall_'+record.pbxmanagerid+'').show();
		}

		jQuery('#pbxcontactsave_'+record.pbxmanagerid+'').bind('click', function(e) {
			var pbxmanagerid = jQuery(e.currentTarget).attr('recordid');

			if(jQuery('#module_'+pbxmanagerid+'').val() == 'Select'){
				jQuery('#alert_msg').show();
				return false;
			}
			if(jQuery('#email_'+pbxmanagerid+'').val() == ""){
				jQuery('#alert_msg').show();
				return false;
			}

			Vtiger_PBXManager_Js.createRecord(e, record);
			//To restrict the save button action to one click
			jQuery('#pbxcontactsave_'+record.pbxmanagerid+'').unbind('click');
		});
	},

	createRecord: function(e, record) {
		var pbxmanagerid = jQuery(e.currentTarget).attr('recordid');
		var email = jQuery('#email_'+pbxmanagerid+'').val();
		var moduleName = jQuery('#module_'+pbxmanagerid+'').val();

		var number = jQuery('#caller','#pbxcall_'+pbxmanagerid+'').attr("value");
		var url = 'index.php?module=PBXManager&action=IncomingCallPoll&mode=createRecord&number='+encodeURIComponent(number)+'&email='+encodeURIComponent(email)+'&callid='+record.sourceuuid+'&modulename='+moduleName;
		// Salesplatform.ru begin PBXManager porting
                app.request.get({'url': url}).then(function(err, data){
			if(!err) {
                //app.request.get(url).then(function(data){
                //      if(data.success && data.result) {
                //SalesPlatform.ru end PBXManager porting
				jQuery('#contactsave_'+pbxmanagerid+'').hide();
			}
		 });
	},

	checkIfRelatedModuleRecordExist: function(record) {
		switch(record.callername){
			case null:	var url = 'index.php?module=PBXManager&action=IncomingCallPoll&mode=checkModuleViewPermission&view=EditView';
                            // Salesplatform.ru begin PBXManager porting
                            app.request.get({'url': url}).then(function(err, data){
                            //app.request.get(url).then(function(data){
                            //var responsedata = JSON.parse(data);
                            //SalesPlatform.ru end PBXManager porting   
							var showSaveOption = false;
                                                        // Salesplatform.ru begin PBXManager porting
							var moduleList = data.modules;
							var contents = jQuery('#module_'+record.pbxmanagerid+'');
							var newEle;
							for(var module in moduleList){
								if(moduleList.hasOwnProperty(module)) {
									if(moduleList[module]){
									   newEle = '<option id="select_'+module+'" value="'+module+'">'+app.vtranslate(module)+'</option>'; 
									   contents.append(newEle);
									   showSaveOption = true;
									}
								}
							}
                                                        // Salesplatform.ru begin PBXManager porting
							if(data && showSaveOption) {
                                                            // if(responsedata.success && showSaveOption)
                                                            //SalesPlatform.ru end PBXManager porting 
                                                        jQuery('#contactsave_'+record.pbxmanagerid+'').show();
                                                        // Salesplatform.ru begin PBXManager porting
                                                    }
                                                    //SalesPlatform.ru end PBXManager porting 
						});
						break;
			default:	jQuery('#caller','#pbxcall_'+record.pbxmanagerid+'').html(app.vtranslate('JS_PBX_CALL_FROM')+' :&nbsp;<a href="index.php?module='+record.customertype+'&view=Detail&record='+record.customer+'">'+record.callername+'</a>');
						// SalesPlatform.ru begin
                        jQuery('#ownername','#pbxcall_'+record.pbxmanagerid+'').text(record.ownername);
                        jQuery('#owner','#pbxcall_'+record.pbxmanagerid+'').show();
                        // SalesPlatform.ru end
                        break;
		}
	},

	 /**
	 * Function to update the popup with answeredby, hide contactsave option e.t.c.,
	 */
	updatePBXIncomingCallPopup: function(record){
		if(record.answeredby!=null){
			jQuery('#answeredbyname','#pbxcall_'+record.pbxmanagerid+'').text(record.answeredby);
			jQuery('#answeredby','#pbxcall_'+record.pbxmanagerid+'').show();
		}
		if(record.customer!=null && record.customer!=''){
			jQuery('#caller','#pbxcall_'+record.pbxmanagerid+'').html(app.vtranslate('JS_PBX_CALL_FROM')+' :&nbsp;<a href="index.php?module='+record.customertype+'&view=Detail&record='+record.customer+'">'+record.callername+'</a>');
			jQuery('#contactsave_'+record.pbxmanagerid+'').hide();
		}
		//To remove the popup for all users except answeredby (new record)
		if(record.user) {
			if(record.user != record.current_user_id) {
				 Vtiger_PBXManager_Js.removeCallPopup(record.pbxmanagerid);
			}
		}
	},

	 /**
	 * Function to remove the call popup which is completed
	 */
	removeCompletedCallPopup:function(){
		var callid = null;
		var pbxcall = jQuery('.pbxcall');
		for(var i=0; i<pbxcall.length;i++){
			callid = pbxcall[i].getAttribute('callid');
			var url = 'index.php?module=PBXManager&action=IncomingCallPoll&mode=getCallStatus&callid='+encodeURIComponent(callid)+'';
                        // Salesplatform.ru begin PBXManager porting
			//app.request.get(url).then(function(data){
                        //if(data.result){
                        app.request.get({url : url}).then(function(err, data){
                            if(data && data !='in-progress' && data !='ringing'){	
                        //SalesPlatform.ru end PBXManager porting 
                                Vtiger_PBXManager_Js.removeCallPopup(callid);	
                            }
			});
		}
	},

	/**
	 * Function to remove call popup
	 */
	removeCallPopup: function(callid) {
		jQuery('#pbxcall_'+callid+'').parent().parent().parent().remove();
	},

	 /**
	 * To get contents holder based on the view
	 */
	getContentHolder:function(view){
		if(view == 'List')
			return jQuery('.listViewContentDiv');
		else
			return jQuery('.detailViewContainer');
	},

	 /**
	 * Function to forward call to number
	 */
	makeOutboundCall : function(number, record){
		var params = {
                    // Salesplatform.ru begin PBXManager porting
                    data: {
                    // Salesplatform.ru end PBXManager porting    
                        number : number,
			record : record,
			module : 'PBXManager',
			action : 'OutgoingCall',
                    // Salesplatform.ru begin PBXManager porting
                    }
                    // Salesplatform.ru end PBXManager porting

                        
		}
                // Salesplatform.ru begin PBXManager porting
		app.request.get(params).then(function(err, data){
                    if(!err){
                    //app.request.get(params).then(function(data){
                    //if(data.result){                            
                    // Salesplatform.ru end PBXManager porting  
                        params = {
                            text : app.vtranslate('JS_PBX_OUTGOING_SUCCESS'),
                            type : 'info'
                        }
                    }else{
                        params = {
                            text : app.vtranslate('JS_PBX_OUTGOING_FAILURE'),
                            type : 'error'
                        }
                    }
                    // Salesplatform.ru begin PBXManager porting
                    Vtiger_PBXManager_Js.showPnotify(params);
                    //Vtiger_Helper_Js.showPnotify(params);
                    // Salesplatform.ru end PBXManager porting  
		});
	},

	 /**
		* Function to register required events
		*/
	 registerEvents : function(){
		var thisInstance = this;
		//for polling
		var url = 'index.php?module=PBXManager&action=IncomingCallPoll&mode=checkPermissionForPolling';
                
                // Salesplatform.ru begin PBXManager porting
		app.request.get({url : url}).then(function(err, data){
			if(!err) {
                            Vtiger_PBXManager_Js.registerPBXCall();
                            Visibility.every(4000, function () {
                                Vtiger_PBXManager_Js.registerPBXCall();
                            });
                //app.request.get(url).then(function(data){
                //      if(data && data.result) {
                //Vtiger_PBXManager_Js.registerPBXCall();
                //setInterval("Vtiger_PBXManager_Js.registerPBXCall()", 3000);
                //SalesPlatform.ru end PBXManager porting
			}
		});
	}

}

//On Page Load
jQuery(document).ready(function() {
    Vtiger_PBXManager_Js.registerEvents();
});
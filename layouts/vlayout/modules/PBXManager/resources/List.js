/*+**********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.1
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is: SalesPlatform Ltd
 * The Initial Developer of the Original Code is SalesPlatform Ltd.
 * All Rights Reserved.
 * If you have any questions or comments, please email: devel@salesplatform.ru
 ************************************************************************************/
Vtiger_List_Js("PBXManager_List_Js",{},{
    
    registerRowClickEvent: function(){
		var thisInstance = this;
		var listViewContentDiv = this.getListViewContentContainer();
		listViewContentDiv.on('click','.listViewEntries',function(e){
			if(jQuery(e.target, jQuery(e.currentTarget)).is('td:first-child')) return;
			if(jQuery(e.target).is('input[type="checkbox"]')) return;
			var elem = jQuery(e.currentTarget);
			var recordUrl = elem.data('recordurl');
            if(typeof recordUrl == 'undefined') {
                return;
            }
            
            if(!elem.find('audio').is(':focus')) {
                window.location.href = recordUrl;
            }
		});
	},

    registerEvents : function(){
        this._super();
        this.registerRowClickEvent
        
    }
});

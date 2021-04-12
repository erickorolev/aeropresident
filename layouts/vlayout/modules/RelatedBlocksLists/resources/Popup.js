/* ********************************************************************************
 * The content of this file is subject to the Related Blocks & Lists ("License");
 * You may not use this file except in compliance with the License
 * The Initial Developer of the Original Code is VTExperts.com
 * Portions created by VTExperts.com. are Copyright(C) VTExperts.com.
 * All Rights Reserved.
 * ****************************************************************************** */


Vtiger_Popup_Js("RelatedBlocksLists_Popup_Js",{},{

	/**
	 * Function to get complete params
	 */
	getCompleteParams : function(){
		var params = {};
		params['view'] = this.getView();
		params['src_module'] = jQuery('#src_module').val();
		params['src_record'] = jQuery('#src_record').val();
		params['src_field'] = this.getSourceField();
		params['search_key'] =  this.getSearchKey();
		params['search_value'] =  this.getSearchValue();
		params['orderby'] =  this.getOrderBy();
		params['sortorder'] =  this.getSortOrder();
		params['page'] = this.getPageNumber();
		params['related_module'] = jQuery('#related_module').val();
		params['related_parent_id'] = this.getRelatedParentRecord();
		params['module'] = app.getModuleName();

		if(this.isMultiSelectMode()) {
			params['multi_select'] = true;
		}
		return params;
	},
	/**
	 * Function to handle search event
	 */
	searchHandler : function(){
		var aDeferred = jQuery.Deferred();
		var completeParams = this.getCompleteParams();
		completeParams['page'] = 1;
		return this.getPageRecords(completeParams).then(
			function(data){
				aDeferred.resolve(data);
			},

			function(textStatus, errorThrown){
				aDeferred.reject(textStatus, errorThrown);
			});
		return aDeferred.promise();
	},
});

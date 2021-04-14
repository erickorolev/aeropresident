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
        params['search_params'] = JSON.stringify(this.getPopupListSearchParams());
        params['selected_id'] = jQuery('#selectedIds').val();
        params['module'] = 'RelatedBlocksLists';

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
	/**
	 * Function to register event for popup list Search
	 */
	registerEventForRelatedPopupListSearch : function(){
		var thisInstance = this;
        var popupPageContainer = this.getPopupPageContainer();
        popupPageContainer.on('click','[data-trigger="PopupRelatedBlockListSearch"]',function(e){
            jQuery('#searchvalue').val("");
            jQuery('#totalPageCount').text("");
			thisInstance.searchHandler().then(function(data){
				jQuery('#pageNumber').val(1);
				jQuery('#pageToJump').val(1);
				thisInstance.updatePagination();
			});
        }).on('keypress',function(e){
			var code = e.keyCode || e.which;
			if(code == 13){
				var element = popupPageContainer.find('[data-trigger="PopupRelatedBlockListSearch"]');
				jQuery(element).trigger('click');
			}
		});
	},
	registerCustomEvents: function(){
		this.registerEventForRelatedPopupListSearch();
	}
});
jQuery(document).ready(function() {
	app.event.on("post.Popup.Load",function(event,params){
        vtUtils.applyFieldElementsView(jQuery('.myModal'));
		var Related_Popup_Js = new RelatedBlocksLists_Popup_Js();
        var eventToTrigger = params.eventToTrigger;
        if(typeof eventToTrigger != "undefined"){
            Related_Popup_Js.setEventName(params.eventToTrigger);
        }
        Related_Popup_Js.registerCustomEvents();
        Related_Popup_Js.registerPostPopupLoadEvents();
    });
});
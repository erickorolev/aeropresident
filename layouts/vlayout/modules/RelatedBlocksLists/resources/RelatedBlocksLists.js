/* ********************************************************************************
 * The content of this file is subject to the Related Blocks & Lists ("License");
 * You may not use this file except in compliance with the License
 * The Initial Developer of the Original Code is VTExperts.com
 * Portions created by VTExperts.com. are Copyright(C) VTExperts.com.
 * All Rights Reserved.
 * ****************************************************************************** */

jQuery.Class("RelatedBlocksLists_Js",{
    ___init: function () {
        var sPageURL = window.location.search.substring(1);
        var targetModule = '';
        var targetView = '';
        var sourceModule = '';
        var mode = '';

        var sURLVariables = sPageURL.split('&');
        for (var i = 0; i < sURLVariables.length; i++) {
            var sParameterName = sURLVariables[i].split('=');
            if (sParameterName[0] == 'module') {
                targetModule = sParameterName[1];
            }
            else if (sParameterName[0] == 'view') {
                targetView = sParameterName[1];
            }
            else if (sParameterName[0] == 'sourceModule') {
                sourceModule = sParameterName[1];
            }
            else if (sParameterName[0] == 'mode') {
                mode = sParameterName[1];
            }
        }
        var viewMode = '';
        if(jQuery('#detailView [name="viewMode"]').length == 0){
            var viewMode = 'full';
        }
        if (targetModule != 'LayoutEditor' && ((targetView == 'Detail' && (mode == 'showDetailViewByMode' || mode == '') && viewMode == 'full') || targetView == 'Edit')) {
            var instance = new RelatedBlocksLists_Js();
            instance.registerEvents();

        }
    }
},{
    getBlockHeader : function (after_block) {
        var thisInstance = this;
        var blockHeader = jQuery(document).find('.blockHeader').eq(after_block);
        if(blockHeader.length == 0) {
            after_block--;
            blockHeader = thisInstance.getBlockHeader(after_block);
        }
        return blockHeader;
    },

    checkAndGenerateBlocks : function(container) {
        var thisInstance = this;
        // Check enable
        var params = {};
        params.action = 'ActionAjax';
        params.module = 'RelatedBlocksLists';
        params.mode = 'checkEnable';
        AppConnector.request(params).then(function (data) {
                if (data.result.enable == '1') {
                    var btnSave = jQuery('button[type=submit]');
                    btnSave.disable();
                    //var module = jQuery(document).find('input[name="module"]').val();
                    var module = jQuery('#module').val();
                    var mode = 'generateDetailView';
                    var record = "";
                    if (container.attr('id') == 'EditView') {
                        mode = "generateEditView";
                        record = jQuery(document).find('input[name="record"]').val();
                        //var lastTable = container.find('table.showInlineTable:last');
                    } else {
                        var record = jQuery("#recordId").val();
                        //var lastTable = container.find('table.detailview-table:last');
                    }

                    //to integrate with Custom View & Form
                    if(module == "CustomFormsViews"){
                        var top_url = window.location.href.split('?');
                        var array_url = thisInstance.getQueryParams(top_url[1]);
                        module = array_url.currentModule;
                        var customviewid = array_url.customviewid;
                    }

                    var params = {};
                    params['module'] = 'RelatedBlocksLists';
                    params['action'] = 'ActionAjax';
                    params['mode'] = 'getConfiguredBlock';
                    params['source_module'] = module;
                    params['customviewid'] = customviewid;
                    AppConnector.request(params).then(
                        function(data) {
                            if(data['success']) {
                                var blocks=data.result;
                                var arrBlockId = [];
                                jQuery.each(blocks,function (blockid, content) {
                                    after_block = content[0];
                                    after_block_label = content[1];
                                    if(arrBlockId[after_block]){
                                        arrBlockId[after_block].push(blockid);
                                    }else{
                                        arrBlockId[after_block] = [blockid];
                                    }
                                });
                                if(blocks) {
                                    jQuery.each(blocks, function (blockid, content) {
                                        after_block = content[0];
                                        after_block_label = content[1];
                                        var viewParams = {
                                            "type": "POST",
                                            "url": 'index.php?module=RelatedBlocksLists',
                                            "dataType": "html",
                                            "data": {
                                                'record': record,
                                                'blockid': blockid,
                                                'view': 'MassActionAjax',
                                                'mode': mode,
                                                'source_module': module
                                            }

                                        };

                                        var blockHeader = thisInstance.getBlockHeader(after_block);

                                        container.find('.relatedblockslists' + blockid).remove();
                                        if(mode == 'generateEditView'){
                                            var preBlock = jQuery(".blockContainer .blockHeader:contains('" + after_block_label + "')").first().closest(".blockContainer");
                                            if (preBlock.length == 0){
                                                preBlock = $("table.blockContainer").last();
                                            }
                                        }else{
                                            var preBlock = container.find(".detailview-table .blockHeader img[data-id=" + after_block + "]").first().closest(".detailview-table");
                                            if (preBlock.length == 0){
                                                preBlock = $("table.detailview-table").last();
                                            }
                                        }
                                        var progressIndicatorInstance = jQuery.progressIndicator();
                                        AppConnector.request(viewParams).then(
                                            function (data) {
                                                if (data) {
                                                    waitUntil(
                                                        function () {
                                                            after_block = blocks[blockid][0];
                                                            if(arrBlockId[after_block].length > 1){
                                                                var currentPos = arrBlockId[after_block].indexOf(blockid);
                                                                if(currentPos == 0){
                                                                    return true;
                                                                }else{
                                                                    var preBlockId =arrBlockId[after_block][currentPos-1];
                                                                    if(jQuery('table[data-block-id="'+ preBlockId +'"]').length > 0){
                                                                        return true;
                                                                    }
                                                                    return false;
                                                                }
                                                            }
                                                            return true;
                                                        },
                                                        function () {
                                                            btnSave.enable();
                                                            progressIndicatorInstance.hide();
                                                            preBlock.after(data);
                                                            if (mode == "generateDetailView") {
                                                                thisInstance.registerDetailViewEvents(jQuery('div.relatedblockslists' + blockid));
                                                            } else {
                                                                thisInstance.registerEditViewEvents(jQuery('div.relatedblockslists' + blockid));
                                                            }
                                                            thisInstance.registerEventForSelectExistingRecordButton(jQuery('div.relatedblockslists' + blockid));
                                                        }
                                                    );
                                                }
                                            }
                                        );

                                    })
                                }
                            }
                        },
                        function(error) {
                            btnSave.enable();
                            //TODO : Handle error
                        }
                    );
                    btnSave.enable();
                }
            }
        )
    },

    registerDetailViewEvents: function (container) {

        var thisInstance = this;
        app.registerEventForDatePickerFields(container.find('.relatedRecords'));
        app.registerEventForTimeFields(container.find('.relatedRecords'));
        app.changeSelectElementView(container.find('.relatedRecords'));
        //register all select2 Elements
        app.showSelect2ElementView(container.find('.relatedRecords select.select2'));

        thisInstance.registerHoverEditEvent(container.find('.relatedRecords'));
        thisInstance.registerEventForDeleteButton(container);
        thisInstance.registerEventForDetailAddMoreButton(container);
        jQuery('.relatedRecords', container).each(function(i,e) {
            var basicRow = jQuery(e);
            thisInstance.registerDetailEventForPicklistDependencySetup(basicRow);
        });
        thisInstance.registerEventForPaging();
    },
    registerEventForPaging: function () {
        var thisInstance = this;
        jQuery('.listViewPagingInput').unbind('keypress');
        jQuery('.relatedViewActions .dropdown-menu').on('click', function (e) {
            e.stopImmediatePropagation();
        }).on('keypress','.listViewPagingInput',function(e){
            if(e.which == 13){
                e.stopImmediatePropagation();
                var element = jQuery(e.currentTarget);
                var response = Vtiger_WholeNumberGreaterThanZero_Validator_Js.invokeValidation(element);
                if(typeof response != "undefined"){
                    element.validationEngine('showPrompt',response,'',"topLeft",true);
                } else {
                    element.validationEngine('hideAll');
                    var actionContainer = element.closest('.relatedViewActions');
                    var currentPageElement = actionContainer.find('.listViewPageJump');
                    var currentPageNumber =  currentPageElement.data('page-number');
                    var newPageNumber = parseInt(jQuery(e.currentTarget).val());
                    var totalPages = parseInt(jQuery('.totalPageCount',actionContainer).text());
                    if(newPageNumber > totalPages){
                        e.preventDefault();
                        var error = app.vtranslate('JS_PAGE_NOT_EXIST');
                        element.validationEngine('showPrompt',error,'',"topLeft",true);
                        return;
                    }
                    if(newPageNumber == currentPageNumber){
                        e.preventDefault();
                        var message = app.vtranslate('JS_YOU_ARE_IN_PAGE_NUMBER')+" "+newPageNumber;
                        var params = {
                            text: message,
                            type: 'info'
                        };
                        Vtiger_Helper_Js.showMessage(params);
                        return;
                    }
                    var recordId =app.getRecordId();
                    var container = element.closest('table.blockContainer');
                    var blockId =  container.data('block-id');

                    thisInstance.loadRelatedListByPaging(recordId, blockId, container,newPageNumber);
                }
                return false;
            }
        });
        jQuery(".listViewNextPageButton,.listViewPreviousPageButton").on('click',function(e){
            var element = jQuery(e.currentTarget);
            var recordId =app.getRecordId();
            var page = element.data('page-number');
            var container = element.closest('table.blockContainer');
            var blockId =  container.data('block-id');
            thisInstance.loadRelatedListByPaging(recordId, blockId, container,page);
        });
    },
    loadRelatedListByPaging: function(recordId, blockId, container,page,mode){
        var thisInstance = this;
        var progressIndicatorElement = jQuery.progressIndicator({
            'position' : 'html',
            'blockInfo' : {
                'enabled' : true
            }
        });
        var view = app.getViewName();
        if(view == 'Detail'){
            var mode = 'generateDetailView';
        }else{
            var mode = 'generateEditView';
        }
        var viewParams = {
            "type": "POST",
            "url": 'index.php?module=RelatedBlocksLists',
            "dataType": "html",
            "data": {
                'record': recordId,
                'blockid': blockId,
                'view': 'MassActionAjax',
                'source_module': app.getModuleName(),
                'mode': mode,
                'ajax': '0',
                'page': page
            }
        };

        AppConnector.request(viewParams).then(
            function (data) {
                if (data) {
                    var nextContainer = container.next();
                    var breakLineEle = container.prev();
                    var preContainer = container.prev().prev();
                    nextContainer.remove();
                    container.remove();
                    breakLineEle.remove();
                    preContainer.after(data);
                    var newContainer = preContainer.nextAll(".relatedblockslists_records").first();
                    if(view == 'Detail'){
                        thisInstance.registerDetailViewEvents(newContainer);
                    }else{
                        thisInstance.registerEditViewEvents(newContainer);
                    }
                    thisInstance.registerEventForSelectExistingRecordButton(jQuery('div.relatedblockslists' + blockId));
                    progressIndicatorElement.progressIndicator({'mode' : 'hide'});
                }
            }
        )
    },
    registerEventForSelectExistingRecordButton : function (container){
        var thisInstance = this;
        container.find('.relatedBtnSelectExisting').on('click', function (e) {
            var element = jQuery(e.currentTarget);
            var viewMode=element.data('view-mode');
            var url=element.data('url');
            var blockid=element.data('block-id');
            var block_type=element.data('type');
            var record=element.data('record');
            var relatedblockslists = element.closest('.relatedblockslists_records');
            var selectedId = [];
            container.find('.relatedRecords').each(function(i,e){
                var id = jQuery(e).data('id');
                if(!isNaN(id)){
                    selectedId.push(id);
                }
            });
            url += "&selected_id="+ selectedId.join(',');
            thisInstance.showSelectRelationPopup(relatedblockslists, viewMode,url,blockid, block_type,record) ;
        });
    },

    showSelectRelationPopup : function(container, viewMode,url,blockid, block_type,record){
        var aDeferred = jQuery.Deferred();
        var thisInstance = this;
        var popupInstance = Vtiger_Popup_Js.getInstance();
        popupInstance.show(url, function(responseString){
                var responseData = JSON.parse(responseString);
                var relatedIdList = Object.keys(responseData);

                if(viewMode == 'detail') {
                    var data = {};
                    data['module'] = 'RelatedBlocksLists';
                    data['action'] = 'ActionAjax';
                    data['mode'] = 'addExistedRecords';
                    data['blockid'] = blockid;
                    data['recordid'] = record;
                    data['relatedIdList'] = relatedIdList;
                    AppConnector.request(data).then(
                        function(data) {
                            if (data['success']) {
                                var related_records = data.result.related_records;
                                var listViewEntriesTable = container.find('table.listViewEntriesTable');
                                var params = {};
                                params.text = app.vtranslate('Records Added');
                                Vtiger_Helper_Js.showMessage(params);
                                jQuery.each(related_records, function (i, related_record) {
                                    if (block_type == 'block') {
                                        var newRow = '<div class="relatedRecords" data-id = "'+related_record+'"></div>';
                                        container.find('div.relatedAddMoreBtn').before(newRow);
                                        newRow = container.find('div.relatedRecords:last');
                                        newRow.data('id',related_record);
                                        thisInstance.loadRelatedRecordDetail(record, related_record, blockid, newRow);
                                        thisInstance.registerHoverEditEvent(newRow);
                                    } else {
                                        //var newRow = thisInstance.getBasicRow(container).addClass('relatedRecords');
                                        var newRow = '<tr class="relatedRecords" data-id = "'+related_record+'"></tr>';
                                        listViewEntriesTable.find('tr:last').after(newRow);
                                        newRow = container.find('tr.relatedRecords:last');
                                        newRow.data('id',related_record);
                                        thisInstance.loadRelatedRecordDetail(record, related_record, blockid, newRow);
                                        thisInstance.registerHoverEditEvent(newRow);
                                    }

                                });
                            }
                        },
                        function(error) {
                            //TODO : Handle error
                        }
                    );
                }else{
                    if(block_type == 'block') {

                        jQuery.each(relatedIdList,function(i,related_record) {
                            var currentRowNumber=jQuery('.relatedRecords', container).length;
                            var sequenceNumber=currentRowNumber+1;

                            var newRow = '<div class="relatedRecords" data-row-no="'+sequenceNumber+'"></div>';

                            container.find('.relatedBtnAddMore').closest('div.row-fluid').before(newRow);
                            newRow = container.find('div.relatedRecords:last');
                            newRow.data('id',related_record);
                            thisInstance.loadRelatedRecordEdit(record, related_record, blockid, block_type, newRow);
                        });
                    }else{
                        var listViewEntriesTable = container.find('table.listViewEntriesTable');
                        jQuery.each(relatedIdList,function(i,related_record) {
                            var currentRowNumber=jQuery('.relatedRecords', listViewEntriesTable).length;
                            var sequenceNumber=currentRowNumber+1;
                            //var newRow = thisInstance.getBasicRow(container).addClass('relatedRecords');
                            var newRow = '<tr class="relatedRecords" data-row-no="'+sequenceNumber+'"></tr>';
                            listViewEntriesTable.find('tr:last').after(newRow);
                            newRow = container.find('tr.relatedRecords:last');
                            newRow.data('id',related_record);
                            thisInstance.loadRelatedRecordEdit(record, related_record, blockid, block_type, newRow);


                        });
                    }

                }
            }
        );
        return aDeferred.promise();
    },


    // Register event for add more button
    registerEventForDetailAddMoreButton: function (container) {
        var thisInstance = this;
        container.find('.relatedBtnAddMore').on('click', function (e) {
            var element = jQuery(e.currentTarget);
            var relatedblockslists = element.closest('.relatedblockslists_records');
            var blockId=element.data('block-id');
            var type=element.data('type');
            var relModule=element.data('rel-module');
            var currentRowNumber=jQuery('.relatedRecords', relatedblockslists).length;
            var sequenceNumber=currentRowNumber+1;
            if(type=='block') {
                // Generate new block
                var actionParams = {
                    "type":"POST",
                    "url": "index.php?module=RelatedBlocksLists&view=MassActionAjax",
                    "dataType":"html",
                    "data" : {
                        "relmodule" : relModule,
                        "blockid" : blockId,
                        "mode" : 'generateNewBlock',
                        "modeView" : 'Detail',
                    }
                };
                AppConnector.request(actionParams).then(
                    function(data) {
                        if(data) {
                            var newRow='<div class="relatedRecords" data-row-no="'+sequenceNumber+'">'+data+'</div>';
                            element.closest('div.relatedAddMoreBtn').before(newRow);
                            //relatedblockslists.find('div.relatedRecords:last').after(newRow);

                            newRow=relatedblockslists.find('div.relatedRecords:last');
                            app.registerEventForDatePickerFields(newRow);
                            app.registerEventForTimeFields(newRow);
                            app.changeSelectElementView(newRow);
                            //register all select2 Elements
                            app.showSelect2ElementView(newRow.find('select.select2'));

                            thisInstance.registerAutoCompleteFields(newRow);
                            thisInstance.registerClearReferenceSelectionEvent(newRow);
                            thisInstance.registerEventForDeleteButton(newRow);
                            thisInstance.registerEventForDetailSaveButton(newRow);
                        }
                    }
                );
            } else {
                var listViewEntriesTable=container.find('table.listViewEntriesTable');
                var newRow = thisInstance.getBasicRow(container).addClass('relatedRecords');
                //newRow.append('<input type="hidden" name="related_module" value="'+relModule+'"/>');
                listViewEntriesTable.find('tr:last').after(newRow);
                newRow.find('input,select').each(function (idx,ele) {
                    if(jQuery(ele).hasClass('input-medium')) {
                        jQuery(ele).removeClass('input-medium').addClass('input-small');
                    }else if(jQuery(ele).hasClass('input-large') || jQuery(ele).is('select')) {
                        jQuery(ele).removeClass('input-large');//.addClass('input-medium');
                        jQuery(ele).css('width', '130px')
                    }else if(jQuery(ele).hasClass('dateField')) {
                        jQuery(ele).css('width', '60px')
                    }
                });

                app.registerEventForDatePickerFields(newRow);
                app.registerEventForTimeFields(newRow);
                app.changeSelectElementView(newRow);
                //register all select2 Elements
                app.showSelect2ElementView(newRow.find('select.select2'));
                thisInstance.registerAutoCompleteFields(newRow);
                thisInstance.registerClearReferenceSelectionEvent(newRow);
                thisInstance.registerEventForDeleteButton(newRow);
                thisInstance.registerEventForDetailSaveButton(newRow);
            }
        });
    },

    registerEventForDetailSaveButton: function (container) {
        var thisInstance = this;
        container.on('click','.relatedBtnSave', function (e) {
            var progressIndicatorElement = jQuery.progressIndicator({
                'position' : 'html',
                'blockInfo' : {
                    'enabled' : true
                }
            });
            var blockId=jQuery(e.currentTarget).data('block-id');
            var data = {};
            data['module'] = 'RelatedBlocksLists';
            data['action'] = 'ActionAjax';
            data['mode'] = 'saveRelatedRecord';
            data['blockid'] = blockId;
            data['recordid'] = jQuery('#recordId').val();

            var relatedRecords=jQuery(e.currentTarget).closest('.relatedRecords');
            relatedRecords.find(':input').each(function(i,e) {
                if(typeof jQuery(e).attr('name') != 'undefined') {
                    data[jQuery(e).attr('name')] = jQuery(e).val();
                }
            });
            AppConnector.request(data).then(
                function(data) {
                    if(data['success']) {
                        var related_record = data.result.related_record;
                        progressIndicatorElement.progressIndicator({'mode' : 'hide'});
                        var params = {};
                        params.text = app.vtranslate('Record Saved');
                        Vtiger_Helper_Js.showMessage(params);
                        thisInstance.loadRelatedRecordDetail(jQuery('#recordId').val(),related_record, blockId,container);
                    }
                },
                function(error) {
                    progressIndicatorElement.progressIndicator({'mode' : 'hide'});
                    //TODO : Handle error
                }
            );
        });
    },

    loadRelatedRecordDetail: function (recordId,related_record, blockId, container) {
        var progressIndicatorElement = jQuery.progressIndicator({
            'position' : 'html',
            'blockInfo' : {
                'enabled' : true
            }
        });
        var thisInstance = this;
        var viewParams = {
            "type": "POST",
            "url": 'index.php?module=RelatedBlocksLists',
            "dataType": "html",
            "data": {
                'record': recordId,
                'blockid': blockId,
                'related_record': related_record,
                'source_module': app.getModuleName(),
                'view': 'MassActionAjax',
                'mode': 'generateRecordDetailView',
                'ajax': '1'
            }
        };

        AppConnector.request(viewParams).then(
            function (data) {
                if (data) {
                    progressIndicatorElement.progressIndicator({'mode' : 'hide'});
                    container.html(data);
                    thisInstance.registerDetailViewEvents(container);
                }
            }
        )
    },

    loadRelatedRecordEdit: function (recordId,related_record, blockId, block_type, newRow) {
        var thisInstance = this;
        var sequenceNumber = newRow.data('row-no');
        var module = jQuery('#module').val();
        var viewParams = {
            "type": "POST",
            "url": 'index.php?module=RelatedBlocksLists',
            "dataType": "html",
            "data": {
                'record': recordId,
                'blockid': blockId,
                'related_record': related_record,
                'view': 'MassActionAjax',
                'mode': 'generateRecordEditView',
                'rowno': sequenceNumber,
                'source_module': module,
                'ajax': '1'
            }
        };


        AppConnector.request(viewParams).then(
            function (data) {
                if (data) {
                    newRow.html(data);

                    if(block_type=='block') {

                    }else {
                        newRow.find('input,select').each(function (idx, ele) {
                            if (jQuery(ele).hasClass('input-medium')) {
                                jQuery(ele).removeClass('input-medium').addClass('input-small');
                            } else if (jQuery(ele).hasClass('input-large') || jQuery(ele).is('select')) {
                                jQuery(ele).removeClass('input-large');//.addClass('input-medium');
                                jQuery(ele).css('width', '190px')
                            } else if (jQuery(ele).hasClass('dateField')) {
                                jQuery(ele).css('width', '90px')
                            }
                        });

                    }
                    app.registerEventForDatePickerFields(newRow);
                    app.registerEventForTimeFields(newRow);
                    app.changeSelectElementView(newRow);
//register all select2 Elements
                    app.showSelect2ElementView(newRow.find('select.select2'));
                    thisInstance.registerAutoCompleteFields(newRow);
                    thisInstance.registerClearReferenceSelectionEvent(newRow);
                    thisInstance.registerEventForDeleteButton(newRow);
                    thisInstance.updateLineItemsElementWithSequenceNumber(newRow, blockId, sequenceNumber);
                }
            }
        )
    },

    loadRelatedBlocksList: function (recordId, blockId, container) {
        var progressIndicatorElement = jQuery.progressIndicator({
            'position' : 'html',
            'blockInfo' : {
                'enabled' : true
            }
        });
        var thisInstance = this;
        var viewParams = {
            "type": "POST",
            "url": 'index.php?module=RelatedBlocksLists',
            "dataType": "html",
            "data": {
                'record': recordId,
                'blockid': blockId,
                'view': 'MassActionAjax',
                'mode': 'generateDetailView',
                'ajax': '1'
            }
        };

        AppConnector.request(viewParams).then(
            function (data) {
                if (data) {
                    progressIndicatorElement.progressIndicator({'mode' : 'hide'});
                    container.html(data);
                    thisInstance.registerDetailViewEvents(container);
                }
            }
        )
    },

    registerHoverEditEvent: function(container) {
        var thisInstance = this;
        container.on('click','td.fieldValue', function(e) {
            var currentTdElement = jQuery(e.currentTarget);
            thisInstance.ajaxEditHandling(container, currentTdElement);
        });
        container.on('click','.hoverEditCancel', function(e) {
            var currentElement = jQuery(e.currentTarget);
            var currentTdElement = currentElement.closest('td');
            var detailViewValue = jQuery('.value',currentTdElement);
            var editElement = jQuery('.edit',currentTdElement);
            editElement.addClass('hide');
            detailViewValue.removeClass('hide');
            e.stopPropagation();
        });
        container.on('click','.hoverEditSave', function(e) {
            var currentElement = jQuery(e.currentTarget);
            var currentTdElement = currentElement.closest('td');
            var detailViewValue = jQuery('.value',currentTdElement);
            var editElement = jQuery('.edit',currentTdElement);

            var relModule=currentElement.data('rel-module');
            var recordId=currentElement.data('record-id');
            var fieldName=currentElement.data('field-name');
            var fldValue=editElement.find('[name="'+fieldName+'"]').val();
            var fieldElement = editElement.find('[name="'+fieldName+'"]');
            var fieldInfo = Vtiger_Field_Js.getInstance(fieldElement.data('fieldinfo'));
            if(fieldInfo.getType() == 'multipicklist') {
                var multiPicklistFieldName = fieldName.split('[]');
                fieldName = multiPicklistFieldName[0];
            }
            var errorExists = fieldElement.validationEngine('validate');
            //If validation fails
            if(errorExists) {
                return;
            }
            currentTdElement.progressIndicator();
            // Save value
            if(relModule=='Calendar' || relModule=='Events' ){
                var timeStart = '';
                var timeEnd = '';
                if(fieldName == 'date_start'){
                    timeStart= editElement.find('[name="time_start"]').val();
                }else if(relModule == 'Events' && fieldName == 'due_date'){
                    timeEnd= editElement.find('[name="time_end"]').val();
                }
                var actionParams = {
                    "type":"POST",
                    "url":'index.php?module=RelatedBlocksLists',
                    "dataType":"json",
                    "data" : {
                        'action':'SaveCalendarAjax',
                        'record' : recordId,
                        'field' : fieldName,
                        'value' : fldValue,
                        'time_start': timeStart,
                        'time_end': timeEnd,
                        'rel_module': relModule
                    }
                };
            }else{
                var actionParams = {
                    "type":"POST",
                    "url":'index.php?module='+relModule,
                    "dataType":"json",
                    "data" : {
                        'action':'SaveAjax',
                        'record' : recordId,
                        'field' : fieldName,
                        'value' : fldValue
                    }
                };
            }
            AppConnector.request(actionParams).then(
                function(data) {
                    currentTdElement.progressIndicator({'mode':'hide'});
                    detailViewValue.html(data.result[fieldName].display_value);
                    editElement.addClass('hide');
                    detailViewValue.removeClass('hide');
                    currentElement.data('selectedValue', fldValue);
                    //After saving source field value, If Target field value need to change by user, show the edit view of target field.
                    if(thisInstance.targetPicklistChange) {
                        thisInstance.targetPicklist.trigger('click');
                        thisInstance.targetPicklistChange = false;
                        thisInstance.targetPicklist = false;
                    }
                    e.stopPropagation();
                }
            );
        });
    },
    ajaxEditHandling: function(container, currentTdElement) {
        var thisInstance = this;
        var detailViewValue = jQuery('.value',currentTdElement);
        var editElement = jQuery('.edit',currentTdElement);
        var fieldnameElement = jQuery('.fieldname', editElement);
        var fieldName = fieldnameElement.val();
        var fieldElement = jQuery('[name="'+ fieldName +'"]', editElement);

        if(editElement.length == 0) {
            return;
        }

        detailViewValue.addClass('hide');
        editElement.removeClass('hide').show().children().filter('input[type!="hidden"]input[type!="image"],select').filter(':first').focus();
    },


    registerDetailEventForPicklistDependencySetup: function(container) {
        var thisInstance = this;
        var picklistDependcyElemnt = jQuery('[name="picklistDependency"]', container.closest('form'));
        if (picklistDependcyElemnt.length <= 0) {
            return;
        }
        var picklistDependencyMapping = JSON.parse(picklistDependcyElemnt.val());
        var sourcePicklists = Object.keys(picklistDependencyMapping);
        if (sourcePicklists.length <= 0) {
            return;
        }

        var sourcePickListNames = "";
        for (var i = 0; i < sourcePicklists.length; i++) {
            sourcePickListNames += '[name="' + sourcePicklists[i] + '"],';
        }
        sourcePickListNames = sourcePickListNames.substring(0, sourcePickListNames.length - 1);
        var sourcePickListElements = container.find(sourcePickListNames);
        sourcePickListElements.on('change', function(e) {
            var currentElement = jQuery(e.currentTarget);
            var sourcePicklistname = currentElement.attr('name');

            var configuredDependencyObject = picklistDependencyMapping[sourcePicklistname];
            var selectedValue = currentElement.val();
            var targetObjectForSelectedSourceValue = configuredDependencyObject[selectedValue];
            var picklistmap = configuredDependencyObject["__DEFAULT__"];

            if (typeof targetObjectForSelectedSourceValue == 'undefined') {
                targetObjectForSelectedSourceValue = picklistmap;
            }
            jQuery.each(picklistmap, function(targetPickListName, targetPickListValues) {
                var targetPickListMap = targetObjectForSelectedSourceValue[targetPickListName];
                if (typeof targetPickListMap == "undefined") {
                    targetPickListMap = targetPickListValues;
                }
                var targetPickList = jQuery('[name="' + targetPickListName + '"]', container);
                if (targetPickList.length <= 0) {
                    return;
                }

                thisInstance.targetPicklistChange = true;
                thisInstance.targetPicklist = targetPickList.closest('td');

                var listOfAvailableOptions = targetPickList.data('availableOptions');
                if (typeof listOfAvailableOptions == "undefined") {
                    listOfAvailableOptions = jQuery('option', targetPickList);
                    targetPickList.data('available-options', listOfAvailableOptions);
                }

                var targetOptions = new jQuery();
                var optionSelector = [];
                optionSelector.push('');
                for (var i = 0; i < targetPickListMap.length; i++) {
                    optionSelector.push(targetPickListMap[i]);
                }

                jQuery.each(listOfAvailableOptions, function(i, e) {
                    var picklistValue = jQuery(e).val();
                    if (jQuery.inArray(picklistValue, optionSelector) != -1) {
                        targetOptions = targetOptions.add(jQuery(e));
                    }
                });
                var targetPickListSelectedValue = '';
                targetPickListSelectedValue = targetOptions.filter('[selected]').val();
                if (targetPickListMap.length == 1) {
                    targetPickListSelectedValue = targetPickListMap[0]; // to automatically select picklist if only one picklistmap is present.
                }
                targetPickList.html(targetOptions).val(targetPickListSelectedValue).trigger("change");
            })

        });
        //To Trigger the change on load
        sourcePickListElements.trigger('change');
    },

    registerEditViewEvents: function (container) {
        var thisInstance = this;

        // Update width of input in related list
        var listViewEntriesTable = container.find('.listViewEntriesTable');
        listViewEntriesTable.find('input,select').each(function (idx,ele) {
            if(jQuery(ele).hasClass('input-medium')) {
                jQuery(ele).removeClass('input-medium').addClass('input-small');
            }else if(jQuery(ele).hasClass('input-large') || jQuery(ele).is('select')) {
                jQuery(ele).removeClass('input-large');//.addClass('input-medium');
                jQuery(ele).css('width', '190px')
            }else if(jQuery(ele).hasClass('dateField')) {
                jQuery(ele).css('width', '90px')
            }
        });

        app.registerEventForDatePickerFields(container.find('.relatedRecords'));
        app.registerEventForTimeFields(container.find('.relatedRecords'));
        app.changeSelectElementView(container.find('.relatedRecords'));
        //register all select2 Elements
        app.showSelect2ElementView(container.find('.relatedRecords select.select2'));

        thisInstance.registerEventForAddMoreButton(container);
        thisInstance.registerEventForDeleteButton(container);
        thisInstance.updateRelatedRecordsFieldsInfo(container);
        thisInstance.registerClearReferenceSelectionEvent(container);
        thisInstance.registerEventForPaging();
        //thisInstance.registerSubmitEvent(container);
        //thisInstance.registerEventForSelectExistingRecordButton(container);

    },

    registerSubmitEvent: function(container) {
        var form=jQuery('#EditView');
        form.submit(function(e){
            container.find('.relatedRecordsClone').remove();
        });
    },

    updateRelatedRecordsFieldsInfo: function (container) {
        var thisInstance = this;
        container.each(function (i,e) {
            var relatedblockslists = jQuery(e);
            var blockId=relatedblockslists.data('block-id');
            var selected_fields= jQuery('#selected_fields'+blockId).val();
            var multipicklist_fields= jQuery('#multipicklist_fields'+blockId).val();
            var reference_fields= jQuery('#reference_fields'+blockId).val();
            relatedblockslists.find('.relatedRecords').each(function (idx,el) {
                var relatedRecord = jQuery(el);
                var rowNo=relatedRecord.data('row-no');
                var moduleName=jQuery('input[name="relatedblockslists['+blockId+']['+rowNo+'][module]"]').val();
                var arrFields=selected_fields.split(',');
                for(var idIndex in arrFields ) {
                    var elementName = arrFields[idIndex];
                    if(multipicklist_fields.indexOf(elementName) != -1) {
                        var expectedElementId = 'relatedblockslists['+blockId+']['+rowNo+']['+elementName+'][]';
                        //TASKID: 1083447 - DEV: tiennguyen - DATE: 2018/11/6 - START
                        //NOTE: get name select tag of multipicklist
                        var multipicklist=elementName.replace(moduleName+'_','');
                        // elementName = elementName+'[]';
                        //TASKID: 1083447 - DEV: tiennguyen - DATE: 2018/11/6 - END
                    }else{
                        var expectedElementId = 'relatedblockslists['+blockId+']['+rowNo+']['+elementName+']';
                        if(reference_fields.indexOf(elementName) != -1) {
                            var valElement = relatedRecord.find('[name="' + elementName + '"]');
                            var fieldContainer = valElement.closest('.fieldValue');
                            fieldContainer.append(valElement);
                            relatedRecord.find('[name="' + elementName + '_display"]').attr('id', expectedElementId+'_display')
                                .filter('[name="' + elementName + '_display"]').attr('name', expectedElementId+'_display');
                        }
                    }
                    //TASKID: 1083447 - DEV: tiennguyen - DATE: 2018/11/6 - START
                    //NOTE: support ACF multipicklist field
                    if(multipicklist){
                        var selectElement=relatedRecord.find('[name="'+multipicklist+'[]"]');
                        selectElement.attr('id', 'relatedblockslists_'+blockId+"_"+rowNo+"_"+elementName).attr('name', expectedElementId)
                            .data('fieldname',elementName);
                    }
                    //TASKID: 1083447 - DEV: tiennguyen - DATE: 2018/11/6 - END
                    var inputElement=relatedRecord.find('[name="' + elementName + '"]').attr('id', 'relatedblockslists_'+blockId+"_"+rowNo+"_"+elementName)
                        .filter('[name="' + elementName + '"]').attr('name', expectedElementId)
                        .data('fieldname',elementName);
                }
                thisInstance.registerEventForPicklistDependencySetup(relatedRecord,rowNo,blockId);
                thisInstance.registerAutoCompleteFields(relatedRecord);
            });
        });
    },

    /**
     * Function to register event for setting up picklistdependency
     * for a module if exist on change of picklist value
     */
    registerEventForPicklistDependencySetup : function(container,row, id){
        var picklistDependcyElemnt = jQuery('[name="picklistDependency"]', container.closest('form'));
        if(picklistDependcyElemnt.length <= 0) {
            return;
        }
        var picklistDependencyMapping = JSON.parse(picklistDependcyElemnt.val());

        var sourcePicklists = Object.keys(picklistDependencyMapping);
        if(sourcePicklists.length <= 0){
            return;
        }

        var sourcePickListNames = "";
        for(var i=0;i<sourcePicklists.length;i++) {
            sourcePickListNames += '[name="relatedblockslists['+id+']['+row+']['+sourcePicklists[i]+']"],';
        }
        sourcePickListNames = sourcePickListNames.substring(0, sourcePickListNames.length - 1);
        var sourcePickListElements = container.find(sourcePickListNames);

        sourcePickListElements.on('change',function(e){
            var currentElement = jQuery(e.currentTarget);
            var sourcePicklistname = currentElement.data('fieldname');

            var configuredDependencyObject = picklistDependencyMapping[sourcePicklistname];
            var selectedValue = currentElement.val();
            var targetObjectForSelectedSourceValue = configuredDependencyObject[selectedValue];
            var picklistmap = configuredDependencyObject["__DEFAULT__"];

            if(typeof targetObjectForSelectedSourceValue == 'undefined'){
                targetObjectForSelectedSourceValue = picklistmap;
            }
            jQuery.each(picklistmap,function(targetPickListName,targetPickListValues){
                var targetPickListMap = targetObjectForSelectedSourceValue[targetPickListName];
                if(typeof targetPickListMap == "undefined"){
                    targetPickListMap = targetPickListValues;
                }
                //
                var targetPickList = jQuery('[name="relatedblockslists['+id+']['+row+']['+targetPickListName+']"]',container);
                if(targetPickList.length <= 0){
                    return;
                }

                var listOfAvailableOptions = targetPickList.data('availableOptions');
                if(typeof listOfAvailableOptions == "undefined"){
                    listOfAvailableOptions = jQuery('option',targetPickList);
                    targetPickList.data('available-options', listOfAvailableOptions);
                }

                var targetOptions = new jQuery();
                var optionSelector = [];
                optionSelector.push('');
                for(var i=0; i<targetPickListMap.length; i++){
                    optionSelector.push(targetPickListMap[i]);
                }

                jQuery.each(listOfAvailableOptions, function(i,e) {
                    var picklistValue = jQuery(e).val();
                    if(jQuery.inArray(picklistValue, optionSelector) != -1) {
                        targetOptions = targetOptions.add(jQuery(e));
                    }
                });

                var targetPickListSelectedValue = '';
                var targetPickListSelectedValue = targetOptions.filter('[selected]').val();

                targetPickList.html(targetOptions).val(targetPickListSelectedValue).trigger("change");
            })
        });

        //To Trigger the change on load
        sourcePickListElements.trigger('change');
    },

    // Register event for add more button
    registerEventForAddMoreButton: function (container) {
        var thisInstance = this;
        container.find('.relatedBtnAddMore').on('click', function (e) {
            var element = jQuery(e.currentTarget);
            var relatedblockslists = element.closest('.relatedblockslists_records');
            var blockId=element.data('block-id');
            var type=element.data('type');
            var relModule=element.data('rel-module');
            var currentRowNumber=jQuery('.relatedRecords', relatedblockslists).length;
            var sequenceNumber=currentRowNumber+1;
            if(type=='block') {
                // Generate new block
                var actionParams = {
                    "type":"POST",
                    "url": "index.php?module=RelatedBlocksLists&view=MassActionAjax",
                    "dataType":"html",
                    "data" : {
                        "relmodule" : relModule,
                        "blockid" : blockId,
                        "mode" : 'generateNewBlock',

                    }
                };
                AppConnector.request(actionParams).then(
                    function(data) {
                        if(data) {
                            var newRow='<div class="relatedRecords" data-row-no="'+sequenceNumber+'"><input type="hidden" name="relatedblockslists['+blockId+']['+sequenceNumber+'][module]" value="'+relModule+'"/>'+data+'</div>';
                            element.closest('div.row-fluid').before(newRow);
                            //relatedblockslists.find('div.relatedRecords:last').after(newRow);

                            newRow=relatedblockslists.find('div.relatedRecords:last');

                            thisInstance.updateLineItemsElementWithSequenceNumber(newRow,blockId,sequenceNumber);
                            app.registerEventForDatePickerFields(newRow);
                            app.registerEventForTimeFields(newRow);
                            app.changeSelectElementView(newRow);
                            //register all select2 Elements
                            app.showSelect2ElementView(newRow.find('select.select2'));

                            thisInstance.registerAutoCompleteFields(newRow);
                            thisInstance.referenceModuleChangeEvent(newRow);
                            thisInstance.registerClearReferenceSelectionEvent(newRow);
                            thisInstance.registerEventForDeleteButton(newRow);

                        }
                    }
                );
            } else {
                var listViewEntriesTable=container.find('table.listViewEntriesTable');
                var newRow = thisInstance.getBasicRow(container).addClass('relatedRecords');
                newRow.append('<input type="hidden" name="relatedblockslists['+blockId+']['+sequenceNumber+'][module]" value="'+relModule+'"/>');
                listViewEntriesTable.find('tr:last').after(newRow);
                newRow.find('input,select').each(function (idx,ele) {
                    if(jQuery(ele).hasClass('input-medium')) {
                        jQuery(ele).removeClass('input-medium').addClass('input-small');
                    }else if(jQuery(ele).hasClass('input-large') || jQuery(ele).is('select')) {
                        jQuery(ele).removeClass('input-large');//.addClass('input-medium');
                        jQuery(ele).css('width', '190px')
                    }else if(jQuery(ele).hasClass('dateField')) {
                        jQuery(ele).css('width', '90px')
                    }
                });
                thisInstance.updateLineItemsElementWithSequenceNumber(newRow,blockId,sequenceNumber);
                app.registerEventForDatePickerFields(newRow);
                app.registerEventForTimeFields(newRow);
                app.changeSelectElementView(newRow);
                //register all select2 Elements
                app.showSelect2ElementView(newRow.find('select.select2'));
                thisInstance.registerAutoCompleteFields(newRow);
                thisInstance.registerClearReferenceSelectionEvent(newRow);
                thisInstance.registerEventForDeleteButton(newRow);
            }
        });
    },

    referenceModuleChangeEvent : function(container){
        var thisInstance = this;
        container.find('.referenceModulesList').chosen().change(function(e){
            var element = jQuery(e.currentTarget);
            var closestTD = element.closest('td').next();
            var popupReferenceModule = element.val();
            var referenceModuleElement = jQuery('input[name="popupReferenceModule"]', closestTD);
            var prevSelectedReferenceModule = referenceModuleElement.val();
            referenceModuleElement.val(popupReferenceModule);

            //If Reference module is changed then we should clear the previous value
            if(prevSelectedReferenceModule != popupReferenceModule) {
                closestTD.find('.clearReferenceSelection').trigger('click');
            }
        });
    },

    /***
     * Function which will update the line item row elements with the sequence number
     * @params : lineItemRow - tr line item row for which the sequence need to be updated
     *			 currentSequenceNUmber - existing sequence number that the elments is having
     *			 expectedSequenceNumber - sequence number to which it has to update
     *
     * @return : row element after changes
     */
    updateLineItemsElementWithSequenceNumber : function(lineItemRow,id,expectedSequenceNumber){
        var selected_fields= jQuery('#selected_fields'+id).val();
        if(typeof selected_fields != 'undefined') {
            var multipicklist_fields= jQuery('#multipicklist_fields'+id).val();
            var reference_fields= jQuery('#reference_fields'+id).val();
            var arrFields=selected_fields.split(',');
            var moduleName=jQuery('input[name="relatedblockslists['+id+']['+expectedSequenceNumber+'][module]"]').val();
            for(var idIndex in arrFields ) {
                var elementName = arrFields[idIndex];
                if (elementName != '') {
                    var actualElementName = elementName;
                    if(multipicklist_fields.indexOf(elementName) != -1) {
                        var expectedElementId = 'relatedblockslists['+id+']['+expectedSequenceNumber+']['+elementName+'][]';
                        //TASKID: 1083447 - DEV: tiennguyen - DATE: 2018/11/6 - START
                        //NOTE: get name select tag of multipicklist
                        var multipicklist=elementName.replace(moduleName+'_','');
                        // actualElementName = actualElementName+'[]';
                        //TASKID: 1083447 - DEV: tiennguyen - DATE: 2018/11/6 - END
                    }else{
                        var expectedElementId = 'relatedblockslists['+id+']['+expectedSequenceNumber+']['+elementName+']';
                        if(reference_fields.indexOf(elementName) != -1) {
                            var valElement = lineItemRow.find('[name="' + elementName + '"]');
                            var fieldContainer = valElement.closest('.fieldValue');
                            fieldContainer.append(valElement);
                            lineItemRow.find('[name="' + actualElementName + '_display"]').attr('id', expectedElementId+'_display')
                                .filter('[name="' + actualElementName + '_display"]').attr('name', expectedElementId+'_display');

                            var referenceModulesList = lineItemRow.find('.referenceModulesList');
                            if(referenceModulesList.length >0) {
                                jQuery.each(referenceModulesList, function (idx, elm) {
                                    var referenceModulesElm=jQuery(elm);
                                    var referenceModulesElmId=referenceModulesElm.attr('id');
                                    var referenceModulesElmExpectedId=referenceModulesElmId+'_'+id+'_'+expectedSequenceNumber;
                                    referenceModulesElm.attr('id',referenceModulesElmExpectedId);
                                });
                            }
                        }
                    }
                    //TASKID: 1083447 - DEV: tiennguyen - DATE: 2018/11/6 - START
                    //NOTE: support ACF multipicklist field
                    if(multipicklist){
                        var selectElement=lineItemRow.find('[name="'+multipicklist+'[]"]');
                        selectElement.attr('id', 'relatedblockslists_'+id+"_"+expectedSequenceNumber+"_"+elementName).attr('name', expectedElementId)
                            .data('fieldname',elementName);
                    }
                    //TASKID: 1083447 - DEV: tiennguyen - DATE: 2018/11/6 - END
                    var expectedRowId = 'row'+expectedSequenceNumber;
                    lineItemRow.find('[name="' + actualElementName + '"]').attr('id', 'relatedblockslists_'+id+"_"+expectedSequenceNumber+"_"+elementName)
                        .filter('[name="' + actualElementName + '"]').attr('name', expectedElementId)
                        .data('fieldname',elementName);
                }
            }
        }

        return lineItemRow;
    },

    /**
     * Function which will register reference field clear event
     * @params - container <jQuery> - element in which auto complete fields needs to be searched
     */
    registerClearReferenceSelectionEvent : function(container) {
        container.find('.clearReferenceSelection').on('click', function(e){
            var element = jQuery(e.currentTarget);
            var parentTdElement = element.closest('td');
            var fieldNameElement = parentTdElement.find('.sourceField');
            var fieldName = fieldNameElement.attr('name');
            fieldNameElement.val('');
            parentTdElement.find('[name="'+fieldName+'_display"]').removeAttr('readonly').val('');
            element.trigger(Vtiger_Edit_Js.referenceDeSelectionEvent);
            e.preventDefault();
        })
    },

    getReferencedModuleName : function(parenElement){
        return jQuery('input[name="popupReferenceModule"]',parenElement).val();
    },

    searchModuleNames : function(params) {
        var aDeferred = jQuery.Deferred();

        if(typeof params.module == 'undefined') {
            params.module = app.getModuleName();
        }

        if(typeof params.action == 'undefined') {
            params.action = 'BasicAjax';
        }
        AppConnector.request(params).then(
            function(data){
                aDeferred.resolve(data);
            },
            function(error){
                //TODO : Handle error
                aDeferred.reject();
            }
        );
        return aDeferred.promise();
    },

    /**
     * Function to get reference search params
     */
    getReferenceSearchParams : function(element){
        var tdElement = jQuery(element).closest('td');
        var params = {};
        var searchModule = this.getReferencedModuleName(tdElement);
        params.search_module = searchModule;
        return params;
    },

    /**
     * Function which will handle the reference auto complete event registrations
     * @params - container <jQuery> - element in which auto complete fields needs to be searched
     */
    registerAutoCompleteFields : function(container) {
        var thisInstance = this;
        container.find('input.autoComplete').autocomplete({
            'minLength' : '3',
            'source' : function(request, response){
                //element will be array of dom elements
                //here this refers to auto complete instance
                var inputElement = jQuery(this.element[0]);
                var searchValue = request.term;
                var params = thisInstance.getReferenceSearchParams(inputElement);
                params.search_value = searchValue;
                thisInstance.searchModuleNames(params).then(function(data){
                    var reponseDataList = [];
                    var serverDataFormat = data.result;
                    if(serverDataFormat.length <= 0) {
                        jQuery(inputElement).val('');
                        serverDataFormat = new Array({
                            'label' : app.vtranslate('JS_NO_RESULTS_FOUND'),
                            'type'  : 'no results'
                        });
                    }
                    for(var id in serverDataFormat){
                        var responseData = serverDataFormat[id];
                        reponseDataList.push(responseData);
                    }
                    response(reponseDataList);
                });
            },
            'select' : function(event, ui ){
                var selectedItemData = ui.item;
                //To stop selection if no results is selected
                if(typeof selectedItemData.type != 'undefined' && selectedItemData.type=="no results"){
                    return false;
                }
                selectedItemData.name = selectedItemData.value;
                var element = jQuery(this);
                var tdElement = element.closest('td');
                thisInstance.setReferenceFieldValue(tdElement, selectedItemData);

                var sourceField = tdElement.find('input[class="sourceField"]').attr('name');
                var fieldElement = tdElement.find('input[name="'+sourceField+'"]');

                fieldElement.trigger(Vtiger_Edit_Js.postReferenceSelectionEvent,{'data':selectedItemData});
            },
            'change' : function(event, ui) {
                var element = jQuery(this);
                //if you dont have readonly attribute means the user didnt select the item
                if(element.attr('readonly')== undefined) {
                    element.closest('td').find('.clearReferenceSelection').trigger('click');
                }
            },
            'open' : function(event,ui) {
                //To Make the menu come up in the case of quick create
                jQuery(this).data('autocomplete').menu.element.css('z-index','100001');

            }
        });
    },

    setReferenceFieldValue : function(container, params) {
        var sourceField = container.find('input[class="sourceField"]').attr('name');
        var fieldElement = container.find('input[name="'+sourceField+'"]');
        var sourceFieldDisplay = sourceField+"_display";
        var fieldDisplayElement = container.find('input[name="'+sourceFieldDisplay+'"]');
        var popupReferenceModule = container.find('input[name="popupReferenceModule"]').val();

        var selectedName = params.name;
        var id = params.id;

        fieldElement.val(id);
        fieldDisplayElement.val(selectedName).attr('readonly',true);
        fieldElement.trigger(Vtiger_Edit_Js.referenceSelectionEvent, {'source_module' : popupReferenceModule, 'record' : id, 'selectedName' : selectedName});

        fieldDisplayElement.validationEngine('closePrompt',fieldDisplayElement);
    },

    getBasicRow : function(container) {
        var basicRow = container.find('.relatedRecordsClone');
        var newRow = basicRow.clone(true,true);
        return newRow.removeClass('hide relatedRecordsClone');
    },

    registerEventForDeleteButton : function(container,id){
        var thisInstance = this;
        container.on('click','.relatedBtnDelete',function(e){
            var src_record = app.getRecordId();
            var element = jQuery(e.currentTarget);
            // Delete record
            var record=element.data('record-id');
            if(record) {
                var relModule = element.data('rel-module');
                var params = {};
                params.action = 'RelationAjax';
                params.mode = 'deleteRelation';
                params.related_module = relModule;
                params.src_record = src_record;
                params.related_record_list = [record];
                params.module = jQuery('#module').val();
                AppConnector.request(params).then(
                    function (data) {
                        element.closest('.relatedRecords').remove();
                        var headerContainer = container.prev();
                        var blockId =  headerContainer.data('block-id');
                        var page = headerContainer.find('.listViewPageJump').data('page-number');
                        thisInstance.loadRelatedListByPaging(src_record, blockId, headerContainer, page);
                    },
                    function(textStatus, errorThrown){

                    }
                );
            }else{
                element.closest('.relatedRecords').remove();
            }
        });
    },

    registerEvents: function() {
        var container = jQuery(document).find('form');
        this.checkAndGenerateBlocks(container);
    },

    getQueryParams:function(qs) {
        if(typeof(qs) != 'undefined' ){
            qs = qs.toString().split('+').join(' ');
            var params = {},
                tokens,
                re = /[?&]?([^=]+)=([^&]*)/g;
            while (tokens = re.exec(qs)) {
                params[decodeURIComponent(tokens[1])] = decodeURIComponent(tokens[2]);
            }
            return params;
        }
    },
});
jQuery(document).ready(function(){
    RelatedBlocksLists_Js.___init();
});
app.listenPostAjaxReady(function () {
    RelatedBlocksLists_Js.___init();
});
function waitUntil(waitFor,toDo){
    if(waitFor()) {
        toDo();
    } else {
        setTimeout(function() {
            waitUntil(waitFor, toDo);
        }, 300);
    }
}
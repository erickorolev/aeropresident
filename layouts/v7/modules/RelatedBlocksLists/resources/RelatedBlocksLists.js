var global_flag = false;
var idxItem = 0;

Vtiger.Class("RelatedBlocksLists_Js",{
    ___init: function (url) {
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
        var blockHeader = jQuery(document).find('.fieldBlockHeader').eq(after_block);
        if(blockHeader.length == 0) {
            after_block--;
            blockHeader = thisInstance.getBlockHeader(after_block);
        }
        return blockHeader;
    },

    checkAndGenerateBlocks : function(container) {
        window.onbeforeunload = null;
        var thisInstance = this;
        // Check enable
        var params = {};
        params.action = 'ActionAjax';
        params.module = 'RelatedBlocksLists';
        params.mode = 'checkEnable';
        app.request.post({data:params}).then(function (err,data) {
            if (data.enable == '1') {
                var btnSave = jQuery('button[type=submit]');
                btnSave.disable();
                var module = jQuery(document).find('input[name="module"]').val();
                var module = app.getModuleName();
                var mode = 'generateDetailView';
                var record = "";
                if (container.attr('id') == 'EditView') {
                    var mode = "generateEditView";
                    record = jQuery(document).find('input[name="record"]').val();
                    //var lastTable = container.find('table.showInlineTable:last');
                } else{
                    record = jQuery("#recordId").val();
                    // var mode = 'generateDetailView';
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
                params['parent_record'] = record;
                app.request.post({data:params}).then(
                    function(err,data) {
                        if(err == null) {
                            var blocks=data;
                            blocks = jQuery.parseJSON(blocks);
                            blocks = blocks.reverse();
                            var arrBlockId = [];
                            blocks.forEach(function (item){
                                var after_block = item.blockData[0];
                                var after_block_label = item.blockData[1];
                                var blockid = item.blockId;
                                if(arrBlockId[after_block]){
                                    arrBlockId[after_block].push(blockid);
                                }else{
                                    arrBlockId[after_block] = [blockid];
                                }
                                if(item.blockData[3].length > 2){
                                    jQuery('[name="picklistDependency"]', container.closest('form')).attr('value',item.blockData[3]);
                                }
                            });
                            if(blocks) {
                                blocks.forEach(function (item){

                                    var after_block = item.blockData[0];
                                    var after_block_label = item.blockData[1];
                                    var blockid = item.blockId;
                                    var viewParams = {
                                        module:'RelatedBlocksLists',
                                        view:'MassActionAjax',
                                        mode: mode,
                                        record:record,
                                        blockid:blockid,
                                        source_module:module
                                    };
                                    if(mode == 'generateEditView'){
                                        var preBlock = jQuery(".fieldBlockContainer .fieldBlockHeader:contains('" + after_block_label + "')").first().closest(".fieldBlockContainer");
                                        if (preBlock.length == 0){
                                            preBlock = $(".fieldBlockContainer").last();
                                        }
                                    }else{
                                        //var preBlock = container.find('div.block').eq(after_block);
                                        var preBlock = container.find(".block .textOverflowEllipsis img[data-id=" + after_block + "]").first().closest(".block");
                                        if (preBlock.length == 0){
                                            preBlock = $("#detailView .block").last();
                                        }
                                    }

                                    //app.helper.showProgress();
                                    app.request.post({data:viewParams}).then(
                                        function (err,data) {
                                            if (err == null) {
                                                waitUntil(
                                                    function () {
                                                        //after_block = blocks[blockid][0];
                                                        if(arrBlockId[after_block].length > 1){
                                                            var currentPos = arrBlockId[after_block].indexOf(blockid);
                                                            if(currentPos == 0){
                                                                return true;
                                                            }else{
                                                                var preBlockId =arrBlockId[after_block][currentPos-1];
                                                                if(jQuery('div[data-block-id="'+ preBlockId +'"]').length > 0){
                                                                    return true;
                                                                }
                                                                return false;
                                                            }
                                                        }
                                                        return true;
                                                    },
                                                    function () {
                                                        btnSave.enable();
                                                        app.helper.hideProgress();
                                                        if (mode == "generateDetailView") {
                                                            if(jQuery('.blockContainer[data-block-id="'+blockid+'"]').length == 0){
                                                                preBlock.after(data);
                                                                var rbl_item = jQuery('div.relatedblockslists' + blockid);
                                                                thisInstance.registerDetailViewEvents(rbl_item);
                                                                //hide inline edit when chk_detail_inline_edit = 0
                                                                var chk_detail_inline_edit = rbl_item.find('.chk_detail_inline_edit').val();
                                                                if(chk_detail_inline_edit == 0){
                                                                    rbl_item.find('span.edit').remove();
                                                                }
                                                            }
                                                        } else {
                                                            preBlock.after(data);
                                                            var rbl_item = jQuery('div.relatedblockslists' + blockid);
                                                            thisInstance.registerEditViewEvents(rbl_item);
                                                            var chk_edit_inline_edit = rbl_item.find('input.chk_edit_inline_edit').val();
                                                            if(chk_edit_inline_edit == 0){
                                                                rbl_item.find(':input:not(.relatedBtnAddMore)').attr('disabled',true);
                                                                //rbl_item.fadeTo('slow', 0.6);
                                                            }
                                                        }
                                                        thisInstance.registerEventForSelectExistingRecordButton(jQuery('div.relatedblockslists' + blockid));
                                                    }
                                                );
                                                if(module!='Calendar'){
                                                    $('#EditView').vtValidate();
                                                }
                                            }
                                        }
                                    );

                                })
                            }
                        }else{
                            btnSave.enable();
                        }
                    }
                );
                btnSave.enable();
            }
        });
    },
    registerDetailViewEvents: function (container) {

        var thisInstance = this;
        var child = '.blockData';
        vtUtils.applyFieldElementsView(container.find(child));

        thisInstance.registerHoverEditEvent(container.find(child));
        thisInstance.registerEventForDeleteButton(container);
        thisInstance.registerEventShowChildRelatedRecords(container);
        thisInstance.registerEventForDetailAddMoreButton(container);
        jQuery(child, container).each(function(i,e) {
            var basicRow = jQuery(e);
            thisInstance.registerDetailEventForPicklistDependencySetup(basicRow);
            thisInstance.applyWidthForFields(basicRow,false);
        });
        thisInstance.registerEventForPaging();
    },
    registerEventForPaging: function () {
        var thisInstance = this;
        jQuery('.listViewPagingInput').unbind('keypress');
        jQuery('.relatedViewActions .dropdown-menu').on('click', function (e) {
            e.stopImmediatePropagation();
        }).on('click','.listViewPagingInputSubmit',function(e){
            e.stopImmediatePropagation();
            var buttonEle = jQuery(e.currentTarget);
            var element = buttonEle.prev();
            if(thisInstance.checkPositiveNumber(element)){
                vtUtils.hideValidationMessage(element);
                var actionContainer = element.closest('.relatedViewActions');
                var currentPageElement = actionContainer.find('.listViewPageJump');
                var currentPageNumber =  currentPageElement.data('page-number');
                var newPageNumber = parseInt(element.val());
                var totalPages = parseInt(jQuery('.totalPageCount',actionContainer).text());
                if(newPageNumber > totalPages){
                    e.preventDefault();
                    var error = app.vtranslate('JS_PAGE_NOT_EXIST');
                    vtUtils.showValidationMessage(element, error, {
                        position : {
                            my: 'top left',
                            at: 'bottom left',
                            container: element.closest('ul')
                        }
                    });
                    return;
                }
                if(newPageNumber == currentPageNumber){
                    e.preventDefault();
                    var message = app.vtranslate('JS_YOU_ARE_IN_PAGE_NUMBER')+" "+newPageNumber;
                    vtUtils.showValidationMessage(element, message, {
                        position : {
                            my: 'top left',
                            at: 'bottom left',
                            container: element.closest('ul')
                        }
                    });
                    return;
                }
                var recordId =jQuery('[name="record"]').val();
                if(recordId == undefined){
                    recordId =jQuery('[name="record_id"]').val();
                }
                var container = element.closest('div.blockContainer');
                var blockId =  container.data('block-id');

                thisInstance.loadRelatedListByPaging(recordId, blockId, container,newPageNumber);
            }
            return false;
        });

        jQuery('.relatedViewActions .dropdown-menu').on('keypress','.listViewPagingInput',function(e){
            if(e.which == 13){
                var element = jQuery(e.currentTarget);
                element.next().trigger('click');
            }
        });
        //fix pagig issue - 411127 - start
        /*
        jQuery(".listViewNextPageButton,.listViewPreviousPageButton").on('click',function(e){
            var element = jQuery(e.currentTarget);
            var recordId =jQuery('[name="record"]').val();
            if(recordId == undefined){
                recordId =jQuery('[name="record_id"]').val();
            }
            var page = element.data('page-number');
            var container = element.closest('table.blockContainer');
            var blockId =  container.data('block-id');
            thisInstance.loadRelatedListByPaging(recordId, blockId, container,page);
        });
        */
        jQuery(".listViewNextPageButton,.listViewPreviousPageButton").off('click');
        jQuery(".listViewNextPageButton,.listViewPreviousPageButton").on('click',function(e){
            var element = jQuery(e.currentTarget);
            var recordId =jQuery('[name="record"]').val();
            if(recordId == undefined){
                recordId =jQuery('[name="record_id"]').val();
            }
            var page = element.data('page-number');
            var container = element.closest('.blockContainer');
            var blockId =  container.data('block-id');
            thisInstance.loadRelatedListByPaging(recordId, blockId, container,page);
        });
        //fix pagig issue - 411127 - end
    },
    checkPositiveNumber : function(currentEle) {
        var fieldValue = currentEle.val();
        var negativeRegex= /(^[-]+\d+)$/ ;
        if(fieldValue == 0) {
            var errorInfo = app.vtranslate('JS_VALUE_SHOULD_BE_GREATER_THAN_ZERO');
            vtUtils.showValidationMessage(currentEle, errorInfo, {
                position : {
                    my: 'top left',
                    at: 'bottom left',
                    container: currentEle.closest('.listViewBasicAction')
                }
            });
            return false;
        }else if(isNaN(fieldValue) || fieldValue < 0 || fieldValue.match(negativeRegex)){
            errorInfo = app.vtranslate('JS_ACCEPT_POSITIVE_NUMBER');
            vtUtils.showValidationMessage(currentEle, errorInfo, {
                position : {
                    my: 'top left',
                    at: 'bottom left',
                    container: currentEle.closest('.listViewBasicAction')
                }
            });
            return false;
        }
        return true;
    },
    loadRelatedListByPaging: function(recordId, blockId, container,page,mode){
        var thisInstance = this;
        app.helper.showProgress();
        var view = app.view();
        var query_string = window.location.href.split('?');
        var array_url = thisInstance.getQueryParams(query_string[1]);
        var exist_relmodule = array_url.relatedModule;
        if(typeof exist_relmodule !== "undefined" && exist_relmodule && view == 'Detail'){
            var mode = 'replaceRelatedBlockLists';
        }else if(view == 'Detail'){
            var mode = 'generateDetailView';
        }else{
            var mode = 'generateEditView';
        }
        var viewParams = {
            "data": {
                'module':'RelatedBlocksLists',
                'record': recordId,
                'blockid': blockId,
                'view': 'MassActionAjax',
                'source_module': app.getModuleName(),
                'mode': mode,
                'ajax': '0',
                'page': page
            }
        };

        app.request.post(viewParams).then(
            function (err,data) {
                if (err == null && data) {
                    var parentContainer = container.parent();
                    var breakLineEle = parentContainer.prev();
                    var preContainer = parentContainer.prev().prev();
                    parentContainer.remove();
                    breakLineEle.remove();
                    preContainer.after(data);
                    var newContainer = $('.relatedblockslists_records[data-block-id="'+blockId+'"]');
                    if(view == 'Detail'){
                        thisInstance.registerDetailViewEvents(newContainer);
                    }else{
                        thisInstance.registerEditViewEvents(newContainer);
                    }
                    thisInstance.registerEventForSelectExistingRecordButton(jQuery('div.relatedblockslists' + blockId));
                    app.helper.hideProgress();
                    var rbl_item = jQuery('div.relatedblockslists' + blockId);
                    var chk_detail_inline_edit = rbl_item.find('.chk_detail_inline_edit').val();
                    if(chk_detail_inline_edit == 0){
                        rbl_item.find('span.edit').remove();
                    }
                }
            }
        )
    },
    registerEventForSelectExistingRecordButton : function (container){
        var thisInstance = this;
        container.find('.relatedBtnSelectExisting').on('click', function (e) {
            var element = jQuery(e.currentTarget);
            var viewMode=element.data('view-mode');
            var view = app.getViewName();
            var baseRecordId = '';
            var record = jQuery('[name="record"]');
            var recordId = app.getRecordId();
            if(record.length) {
                baseRecordId = record.val();
            } else if(recordId) {
                baseRecordId = recordId;

            }
            if(view == 'Edit' && baseRecordId == ''){
                viewMode = 'Edit';
            }


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
        var params=thisInstance.getQueryParams(url);
        popupInstance.showPopup(params, function(responseString){
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
                    app.request.post({data:data}).then(
                        function(err,data) {
                            if (err == null && data) {
                                var related_records = data.related_records;
                                var listViewEntriesTable = container.find('table.listViewEntriesTable');
                                var params = {};
                                params.message = app.vtranslate('Records Added');
                                app.helper.showSuccessNotification(params);
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
                        }
                    );
                }else{
                    if(block_type == 'block') {

                        jQuery.each(relatedIdList,function(i,related_record) {
                            var currentRowNumber=jQuery('.relatedRecords', container).length;
                            var sequenceNumber=currentRowNumber+1;

                            var newRow = '<div class="relatedRecords" data-row-no="'+sequenceNumber+'"></div>';

                            container.find('.relatedBtnAddMore').closest('div.row').before(newRow);
                            newRow = container.find('div.relatedRecords:last');
                            newRow.data('id',related_record);
                            thisInstance.loadRelatedRecordEdit(record, related_record, blockid, block_type, newRow);
                        });
                    }else{
                        var listViewEntriesTable = container.find('table');
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
    registerEventHilightNewRow:function(selected_records){
        if(selected_records != undefined && selected_records != ''){
            selected_records.forEach(function(val){
                var row = $('tr.flex-list-record[data-id="'+val+'"]');
                row.css({'background-color':'#ffffd9'});
                setTimeout(function(){
                    row.css({'background-color':'#ffffe8'});
                    setTimeout(function(){
                        row.css({'background-color':'#fdfdf3'});
                        setTimeout(function(){
                            row.css({'background-color':'#fdfdf8'});
                            setTimeout(function(){
                                row.css({'background-color':'#ffffff'})
                            },500);
                        },500);
                    },500);
                },500);
            });
        }
    },
    reLoadFlexList:function(blockId,record,module,selected_records){
        var self = this;
        var actionParams = {
            "data" : {
                module: 'RelatedBlocksLists',
                view: 'MassActionAjax',
                mode: 'generateDetailView',
                record: record,
                blockid: blockId,
                loadtype: 'reload_flex_list',
                currently_view: app.getViewName(),
                source_module: module
            }
        };
        app.request.post(actionParams).then(
            function(err,data) {
                var prev = $('.flex-list[data-block-id="'+blockId+'"]').prev();
                $('.flex-list[data-block-id="'+blockId+'"]').remove();
                prev.after(data);
                self.registerEventForFlexList(blockId);
                self.registerEventHilightNewRow(selected_records);
            }
        );
    },
    registerFlexListPopupEvent:function(popup,blockId,record,module,relmodule){
        var self = this;
        popup.find('button.select').on('click',function(){
            var tr = popup.find('table.listViewEntriesTable tr.listViewEntries');
            var selected_records = [];
            tr.each(function(k,item){
                var record_id = $(item).data('id');
                var checkbox = $(item).find('input.entryCheckBox');
                if(checkbox[0].checked == true){
                    selected_records.push(record_id);
                }
            });
            self.AddRecordToFlexList(selected_records,blockId,record,module,relmodule);
        });
        popup.find('tr.listViewEntries td.listViewEntryValue').on('click',function(){
            var tr = $(this).closest('tr.listViewEntries');
            var record_id = tr.data('id');
            var selected_records = [];
            selected_records.push(record_id);
            self.AddRecordToFlexList(selected_records,blockId,record,module,relmodule);
        });
    },
    FlexListOpenModal:function(relmodule,blockId){
        var self = this;
        if(app.getViewName() == 'Edit'){
            var record = $('#EditView').find('input[name="record"]').val();
        }else{
            var record = app.getRecordId();
        }
        var module = app.getModuleName();
        var params = {
            module: 'RelatedBlocksLists',
            related_module: relmodule,
            block_type: 'flexlist',
            src_module: module,
            src_record: record,
            multi_select: 1,
            view: 'PopupFlexList'
        };
        var popupInstance = Vtiger_Popup_Js.getInstance();
        popupInstance.showPopup(params,Vtiger_Edit_Js.popupSelectionEvent,function() {
            var popup = $('#popupPageContainer');
            popup.append('<input type="hidden" id="block_type" value="flexlist">');
            popup.append('<input type="hidden" id="src_module" value="'+module+'">');
            popup.append('<input type="hidden" id="src_record" value="'+record+'">');
            popup.append('<input type="hidden" id="related_module" value="'+relmodule+'">');
            popup.find('input#module[type=""]')
            popup.find('button.select').removeAttr('disabled');
            self.registerFlexListPopupEvent(popup,blockId,record,module,relmodule);
            $(document).ajaxComplete(function(a,b,settings){
                if(settings.data != undefined && settings.data.indexOf('view=PopupFlexListAjax') != -1){
                    self.registerFlexListPopupEvent(popup,blockId,record,module,relmodule);
                }
            });
        });
    },
    AddRecordToFlexList:function(selected_records,blockId,record,module,relmodule){
        var self= this;
        var actionParams = {
            "data" : {
                module: 'RelatedBlocksLists',
                action: 'ActionAjax',
                selected_records:selected_records,
                src_record: record,
                src_module: module,
                relmodule: relmodule,
                blockid: blockId,
                mode: 'addFlexListRecords'
            }
        };
        app.request.post(actionParams).then(function(){
            app.helper.showSuccessNotification({message:'Selected records have been added'});
            app.helper.hideModal();
            self.reLoadFlexList(blockId,record,module,selected_records);
        });
    },
    deleteFlexListRecord:function(rel_record,blockId){
        var self = this;
        var actionParams = {
            "data" : {
                module: 'RelatedBlocksLists',
                action: 'ActionAjax',
                mode: 'deleteFlexListRecord',
                rel_record: rel_record
            }
        };
        app.helper.showConfirmationBox({message:'Do you want to unlink this record?'}).then(function(){
            app.request.post(actionParams).then(
                function(err,data) {
                    if(err == null && data) {
                        var module = app.getModuleName();
                        if(app.getViewName() == 'Edit'){
                            var record = $('#EditView').find('input[name="record"]').val();
                        }else{
                            var record = app.getRecordId();
                        }
                        var selected_records = '';
                        self.reLoadFlexList(blockId,record,module,selected_records);
                    }
                }
            );
        });
    },
    registerEventForFlexList:function(blockId){
        //#1195383 BEGIN
        var self = this;
        $('.fieldBlockContainer .blockContainer[data-block-id="'+blockId+'"]').find('.relatedFlexListAddButton').off('click');
        $('.fieldBlockContainer .blockContainer[data-block-id="'+blockId+'"]').find('.relatedFlexListAddButton').on('click', function (e) {
            var relmodule = $(this).data('relmodule');
            self.FlexListOpenModal(relmodule,blockId);
        });
        var table = $('.relatedblockslists_records[data-block-id="'+blockId+'"] table.listViewEntriesTable');
        $('.relatedblockslists_records[data-block-id="'+blockId+'"] table.listViewEntriesTable tr.relatedRecords .flexlist-module-label').off('click');
        $('.relatedblockslists_records[data-block-id="'+blockId+'"] table.listViewEntriesTable tr.relatedRecords .flexlist-module-label').on('click', function (e) {
            var relmodule = $(this).data('relmodule');
            self.FlexListOpenModal(relmodule,blockId);
        });
        $('.relatedblockslists_records[data-block-id="'+blockId+'"] table.listViewEntriesTable tr.relatedRecords .flexlist-delete-record').off('click');
        $('.relatedblockslists_records[data-block-id="'+blockId+'"] table.listViewEntriesTable tr.relatedRecords .flexlist-delete-record').on('click', function (e) {
            var rel_record = $(this).data('rel-record-id');
            self.deleteFlexListRecord(rel_record,blockId);
        });
        table.find('tr.relatedRecords span[data-field-type="reference"] a').attr('target','_blank');
        //#1195383 END
    },
    // Register event for add more button
    registerEventForDetailAddMoreButton: function (container) {
        var thisInstance = this;
        // Add disable to template row of list
        var relatedRecordsClone = container.find('.relatedRecordsClone');
        relatedRecordsClone.find(':input').attr("disabled","disabled");
        var blockId=container.data('block-id');
        thisInstance.registerEventForFlexList(blockId);
        container.find('.relatedBtnAddMore').on('click', function (e) {
            var element = jQuery(e.currentTarget);
            var relatedblockslists = element.closest('.relatedblockslists_records');
            var blockId=element.data('block-id');
            var type=element.data('type');
            var relModule=element.data('rel-module');
            var currentRowNumber=jQuery('.relatedRecords', relatedblockslists).length;
            var sequenceNumber=currentRowNumber+1;
            var recordid = $('#recordId').val();
            if(type=='block') {
                // Generate new block
                var actionParams = {
                    "data" : {
                        'module':'RelatedBlocksLists',
                        'view': 'MassActionAjax',
                        "relmodule" : relModule,
                        "parent_module" : app.getModuleName(),
                        "parent_record" : recordid,
                        "blockid" : blockId,
                        "mode" : 'generateNewBlock',
                        "modeView" : 'Detail',
                    }
                };
                app.request.post(actionParams).then(
                    function(err,data) {
                        if(err == null && data) {
                            var newRow=jQuery('<div class="relatedRecords" data-row-no="'+sequenceNumber+'">'+data+'</div>');
                            thisInstance.applyWidthForFields(newRow,true);
                            element.closest('div.relatedAddMoreBtn').before(newRow);
                            // relatedblockslists.find('div.relatedRecords:last').after(newRow)
                            vtUtils.applyFieldElementsView(newRow);

                            thisInstance.registerEventForDeleteButton(newRow);
                            thisInstance.registerEventForDetailSaveButton(newRow);
                            var indexInstance = Vtiger_Index_Js.getInstance();
                            indexInstance.referenceModulePopupRegisterEvent(newRow);
                            indexInstance.registerReferenceCreate(newRow);
                            indexInstance.registerAutoCompleteFields(newRow);
                            indexInstance.registerClearReferenceSelectionEvent(newRow);
                            thisInstance.registerValidateFieldOnChange(newRow);
                            thisInstance.registerDetailEventForPicklistDependencySetup(newRow);
                            var container = element.closest('div.relatedblockslists_records');
                            var chk_detail_inline_edit = container.find('.chk_detail_inline_edit').val();
                            if(chk_detail_inline_edit == 0){
                                newRow.find(':input').attr("disabled",true);
                            }
                            $('#detailView').vtValidate();
                        }
                    }
                );
            } else {
                var container = element.closest('div.relatedblockslists_records');
                var listViewEntriesTable=container.find('table.listViewEntriesTable');
                var newRow = thisInstance.getBasicRow(container).addClass('relatedRecords');
                newRow.find(':input').removeAttr("disabled");
                //newRow.append('<input type="hidden" name="related_module" value="'+relModule+'"/>');
                listViewEntriesTable.find('tr:last').after(newRow);
                thisInstance.applyWidthForFields(newRow,true);
                newRow.find(".select2-container.inputElement.select2").remove();
                vtUtils.applyFieldElementsView(newRow);

                //newRow.find(".referencefield-wrapper").css({"width": "300px","display": "inline-block"});
                //newRow.find(".referencefield-wrapper .input-group").css({"width": "229px"});
                thisInstance.registerEventForDeleteButton(newRow);
                thisInstance.registerEventForDetailSaveButton(newRow);
                var indexInstance = Vtiger_Index_Js.getInstance();
                indexInstance.referenceModulePopupRegisterEvent(newRow);
                indexInstance.registerReferenceCreate(newRow);
                indexInstance.registerAutoCompleteFields(newRow);
                indexInstance.registerClearReferenceSelectionEvent(newRow);
                thisInstance.registerValidateFieldOnChange(newRow);
                thisInstance.registerDetailEventForPicklistDependencySetup(newRow);
                var chk_detail_inline_edit = container.find('.chk_detail_inline_edit').val();
                if(chk_detail_inline_edit == 0){
                    newRow.find(':input').attr("disabled",true);
                }
                //START
                //TASKID: 1030263 - DEV: tuan@vtexperts.com - DATE: 25/09/2018
                //NOTES: Fix https://sc.vtedev.com/tuan/Snagit10.mp4
                newRow.find('.select2-container-disabled').remove();
                //END
                $('#detailView').vtValidate();
            }
        });
    },

    registerEventForDetailSaveButton: function (container) {
        var thisInstance = this;
        container.on('click','.relatedBtnSave', function (e) {
            var blockId=jQuery(e.currentTarget).data('block-id');
            var relmodule=jQuery(e.currentTarget).data('rel-module');
            var data = {};
            data['module'] = 'RelatedBlocksLists';
            data['action'] = 'ActionAjax';
            data['mode'] = 'saveRelatedRecord';
            data['blockid'] = blockId;
            data['parent_module'] = app.getModuleName();
            data['parent_record'] = jQuery('#recordId').val();
            data['blockid'] = blockId;
            data['recordid'] = jQuery('#recordId').val();
            var relatedRecords=jQuery(e.currentTarget).closest('.relatedRecords');
            var check = true;
            var file_data;
            var file_name;
            relatedRecords.find(':input').each(function(i,e) {
                if(typeof jQuery(e).attr('name') != 'undefined') {
                    if(jQuery(e).attr('type') == 'checkbox') {
                        if(jQuery(e).attr('checked') == 'checked'){
                            data[jQuery(e).attr('name')] = 1;
                        }else{
                            data[jQuery(e).attr('name')] = 0;
                        }
                    }else if(jQuery(e).attr('type') == 'file') {
                        file_data = $(e).prop('files')[0];
                        file_name = file_data.name;
                        $('[name=notes_title]').val(file_name);

                    }else{
                        data[jQuery(e).attr('name')] = jQuery(e).val();
                    }


                    var attr = $(e).attr('data-rule-required');
                    if (typeof attr !== typeof undefined && attr !== false) {
                        if(jQuery(e).val() == '' || jQuery(e).val() == undefined){
                            if(relmodule=='Documents' && jQuery(e).attr('name')=='notes_title'){
                                check = true;
                            }else{
                                check = false;
                            }

                        }
                    }
                }
            });
            if(check){
                if(relmodule=='Documents'){
                    var form_data = new FormData();
                    $.each(data, function( index, value ) {
                        if(index=='notes_title' && value==''){
                            value=file_name;
                        }
                        form_data.append(index, value);
                    });
                    form_data.append('filename', file_data);
                    form_data.append('filelocationtype', 'I');
                    app.helper.showProgress();
                    $.ajax({
                        url: 'index.php',
                        dataType: 'json',
                        cache: false,
                        contentType: false,
                        processData: false,
                        data: form_data,
                        type: 'post',
                        success: function (res) {
                            var related_record = res.result.related_record;
                            var params = {};
                            params.message = app.vtranslate('Record Saved');
                            app.helper.showSuccessNotification(params);
                            thisInstance.loadRelatedRecordDetail(jQuery('#recordId').val(),related_record, blockId,container);
                            app.helper.hideProgress();
                        }
                    });
                }else{
                    app.helper.showProgress();
                    app.request.post({data:data}).then(
                        function(err,data) {
                            if(err == null && data) {
                                app.helper.hideProgress();
                                var related_record = data.related_record;
                                var params = {};
                                params.message = app.vtranslate('Record Saved');
                                app.helper.showSuccessNotification(params);
                                thisInstance.loadRelatedRecordDetail(jQuery('#recordId').val(),related_record, blockId,container);
                            }
                        }
                    );
                }
            }else{
                app.helper.showAlertNotification({message:'Required fields may not be empty !'});
            }
        });
    },
    registerValidateFieldOnChange:function (newRow) {
        newRow.find('input,select').on('change',function() {
            var data_rule_currency = $(this).data('rule-currency');
            var data_rule_required = $(this).data('rule-required');
            var field_type =  $(this).closest('td').data('field-type');
            if (typeof data_rule_currency !== "undefined" && data_rule_currency) {
                if(jQuery(this).val() != ''){
                    var check_num = parseFloat(jQuery(this).val());
                    if(isNaN(check_num) || check_num < 0){
                        jQuery(this).addClass('input-error');
                        var errorInfo = app.vtranslate('JS_PLEASE_ENTER_VALID_VALUE');
                        vtUtils.showValidationMessage(jQuery(this), errorInfo);
                        return false;
                    }
                    else{
                        vtUtils.hideValidationMessage(jQuery(this));
                        jQuery(this).removeClass('input-error');
                    }
                }
                else{
                    vtUtils.hideValidationMessage(jQuery(this));
                    jQuery(this).removeClass('input-error');
                }
            }
            else if(typeof data_rule_required !== "undefined" && data_rule_required) {
                if(jQuery(this).val() == ''){
                    jQuery(this).addClass('input-error');
                    var errorInfo = app.vtranslate('JS_REQUIRED_FIELD');
                    vtUtils.showValidationMessage(jQuery(this), errorInfo);
                    return false;
                }
                else{
                    vtUtils.hideValidationMessage(jQuery(this));
                    jQuery(this).removeClass('input-error');
                }
            }
            else if(typeof field_type !== "undefined" && (field_type == 'percentage' || field_type == 'double' )) {
                if(jQuery(this).val() != '' || jQuery(this).val() != undefined){
                     var check_num = parseFloat(jQuery(this).val());
                    if(isNaN(check_num)){
                        jQuery(this).addClass('input-error');
                        var errorInfo = app.vtranslate('JS_PLEASE_ENTER_VALID_VALUE');
                        vtUtils.showValidationMessage(jQuery(this), errorInfo);
                        return false;
                    }
                    else{
                        vtUtils.hideValidationMessage(jQuery(this));
                        jQuery(this).removeClass('input-error');
                    }
                }
                else{
                    vtUtils.hideValidationMessage(jQuery(this));
                    jQuery(this).removeClass('input-error');
                }
            }
            else if(typeof field_type !== "undefined" && field_type == 'integer') {
                if(jQuery(this).val() != '' || jQuery(this).val() != undefined){
                    var check_num = parseInt(jQuery(this).val());
                    if(isNaN(check_num)){
                        jQuery(this).addClass('input-error');
                        var errorInfo = app.vtranslate('JS_PLEASE_ENTER_INTEGER_VALUE');
                        vtUtils.showValidationMessage(jQuery(this), errorInfo);
                        return false;
                    }
                    else{
                        vtUtils.hideValidationMessage(jQuery(this));
                        jQuery(this).removeClass('input-error');
                    }
                }
            }
        });
    },
    loadRelatedRecordDetail: function (recordId,related_record, blockId, container) {
        //app.helper.showProgress();
        var thisInstance = this;
        var viewParams = {
            "data": {
                'module': 'RelatedBlocksLists',
                'record': recordId,
                'blockid': blockId,
                'related_record': related_record,
                'source_module': app.getModuleName(),
                'view': 'MassActionAjax',
                'mode': 'generateRecordDetailView',
                'ajax': '1'
            }
        };

        app.request.post(viewParams).then(
            function (err,data) {
                if (err == null && data) {
                    app.helper.hideProgress();
                    container.html(data);
                    thisInstance.registerDetailViewEvents(container);
                }
            }
        )
    },

    loadRelatedRecordEdit: function (recordId,related_record, blockId, block_type, newRow) {
        var thisInstance = this;
        var sequenceNumber = newRow.data('row-no');
        var module = app.getModuleName();
        var viewParams = {
            "data": {
                'module':'RelatedBlocksLists',
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
        app.request.post(viewParams).then(
            function (err,data) {
                if (err == null && data) {
                    newRow.html(data);

                    if(block_type=='block') {

                    }else {
                        thisInstance.applyWidthForFields(newRow);
                    }
                    vtUtils.applyFieldElementsView(newRow);
                    var indexInstance = Vtiger_Index_Js.getInstance();
                    indexInstance.registerAutoCompleteFields(newRow);
                    thisInstance.registerClearReferenceSelectionEvent(newRow);
                    thisInstance.registerEventForDeleteButton(newRow);
                    thisInstance.updateLineItemsElementWithSequenceNumber(newRow, blockId, sequenceNumber);
                    thisInstance.registerDetailEventForPicklistDependencySetup(newRow);
                }
            }
        )
    },

    loadRelatedBlocksList: function (recordId, blockId, container) {
        //app.helper.showProgress();
        var thisInstance = this;
        var viewParams = {
            "data": {
                'module':'RelatedBlocksLists',
                'record': recordId,
                'blockid': blockId,
                'view': 'MassActionAjax',
                'mode': 'generateDetailView',
                'ajax': '1'
            }
        };
        app.request.post(viewParams).then(
            function (data) {
                if (data) {
                    app.helper.hideProgress();
                    container.html(data);
                    thisInstance.registerDetailViewEvents(container);
                }
            }
        )
    },

    registerHoverEditEvent: function(container) {
        var thisInstance = this;
        container.on('click','td.fieldValue div.row-fluid', function(e) {
            var target = e.target;
            if ($(target).is('a')){
                return true;
            }
            $('td.fieldValue .row-fluid').css('width','auto');
            var currentTdElement = jQuery(e.currentTarget);
            thisInstance.ajaxEditHandling(container, currentTdElement);
            thisInstance.applyWidthForFields(currentTdElement);
            //#1196020
            //thisInstance.registerDetailEventForPicklistDependencySetup(currentTdElement.closest('tr'));
            //#1196020 end
            thisInstance.registerValidateFieldOnChange(currentTdElement.closest('tr'));
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
            if(editElement.find('[name="'+fieldName+'"]')[1] != undefined && editElement.find('[name="'+fieldName+'"]')[1].type == 'checkbox'){
                if(editElement.find('[name="'+fieldName+'"]')[1].checked == true){
                    fldValue = 1;
                }else{
                    fldValue = 0;
                }
            }else{
                var fldValue=editElement.find('[name="'+fieldName+'"]').val();
            }
            var fieldElement = editElement.find('[name="'+fieldName+'"]');
            var fieldInfo = Vtiger_Field_Js.getInstance(fieldElement.data('fieldinfo'));
            if(fieldInfo.getType() == 'multipicklist' ||  fieldElement.data('fieldtype') == 'multipicklist') {
                var multiPicklistFieldName = fieldName.split('[]');
                fieldName = multiPicklistFieldName[0];
            }
            var is_required =  fieldElement.data('rule-required');
            if(typeof is_required != "undefined" && is_required){
                if(fieldElement.val() == "") {
                    return;
                }
            }
            var errorExists = fieldElement.validationEngine('validate');
            //If validation fails
            if(errorExists) {
                return;
            }
            app.helper.showProgress();
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
                    "data" : {
                        'module':'RelatedBlocksLists',
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
                    "data" : {
                        'module': relModule,
                        'action':'SaveAjax',
                        'record' : recordId,
                        'field' : fieldName,
                        'value' : fldValue
                    }
                };
            }
            app.request.post(actionParams).then(
                function(err,data) {
                    if(err == null) {
                        app.helper.hideProgress();
                        var contentval = detailViewValue.html();
                        if(contentval.indexOf('href')>0){
                            detailViewValue.find('a').html(data[fieldName].display_value);
                        }else {
                            var displayVal = data[fieldName].display_value;
                            if(data[fieldName].colormap !='undefined'){
                                if(data[fieldName].colormap[displayVal]){
                                    displayVal ='<span class="picklist-color" style="background-color: '+data[fieldName].colormap[displayVal]+'; line-height:15px; color: white;">'+displayVal+'</span>';
                                }
                            }
                            detailViewValue.html(displayVal);
                        }
                        editElement.addClass('hide');
                        detailViewValue.removeClass('hide');
                        currentElement.data('selectedValue', fldValue);
                        //After saving source field value, If Target field value need to change by user, show the edit view of target field.
                        if(thisInstance.targetPicklistChange) {
                            // thisInstance.targetPicklist.trigger('click');
                            // thisInstance.targetPicklistChange = false;
                            // thisInstance.targetPicklist = false;
                        }
                        vtUtils.hideValidationMessage(fieldElement);
                        jQuery(this).removeClass('input-error');
                        e.stopPropagation();
                    } else {
                        app.helper.hideProgress();
                    }
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
        $('div.relatedblockslists_records table.listViewEntriesTable td.fieldValue .textAreaElement').removeClass('col-lg-12').css({width:'200px',height:'74px'});
        detailViewValue.addClass('hide');
        editElement.removeClass('hide').show();
    },
    registerDetailEventForPicklistDependencySetup: function(container) {
        var thisInstance = this;
        var picklistDependcyElemnt = jQuery('[name="picklistDependency"]', container.closest('form'));
        if (picklistDependcyElemnt.length <= 0) {
            var eleRelatedBlock = container.closest('div.relatedblockslists_records');
            picklistDependcyElemnt = jQuery('[name="picklistDependency"]', eleRelatedBlock);
        }
        if (picklistDependcyElemnt.length <= 0) {
            return;
        }
        var picklistDependencyMapping = JSON.parse(picklistDependcyElemnt.val());
        var sourcePicklists = Object.keys(picklistDependencyMapping);

        if (sourcePicklists.length <= 0) {
            return;
        }

        var sourcePickListNames = "";
        var view = app.getViewName();
        var relmodule = container.closest('div.relatedblockslists_records').data('rel-module');
        for (var i = 0; i < sourcePicklists.length; i++) {
            if(view == "Edit"){
                sourcePickListNames += '[data-fieldname="' + relmodule+"_" + sourcePicklists[i] + '"],';
            }
            else sourcePickListNames += '[name="' + sourcePicklists[i] + '"],';
        }
        sourcePickListNames = sourcePickListNames.substring(0, sourcePickListNames.length - 1);
        var sourcePickListElements = container.find(sourcePickListNames);
        if(sourcePickListElements.length > 0 && !sourcePickListElements.closest('tr').hasClass('relatedRecordsClone')){
                sourcePickListElements.on('change', function(e) {
                    var currentElement = jQuery(e.currentTarget);
                    var sourcePicklistname = currentElement.attr('name');
                    if(view == "Edit"){
                        sourcePicklistname = currentElement.data('fieldname');
                    }
                    sourcePicklistname = sourcePicklistname.replace(relmodule + "_","");
                    var configuredDependencyObject = picklistDependencyMapping[sourcePicklistname];
                    if(typeof configuredDependencyObject !== "undefined"){
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
                            if(view == "Edit"){
                                targetPickList = jQuery('[data-fieldname="' + relmodule + "_" + targetPickListName + '"]', container);
                            }
                            if (targetPickList.length <= 0) {
                                return;
                            }

                            //#1659840 tuannm 06192019 START
                            var targetSourceValue = configuredDependencyObject[selectedValue];
                            if(typeof targetSourceValue != 'undefined' && Object.keys(picklistmap).length>1){
                                jQuery('[name="picklistmapfield"]', eleRelatedBlock).val(JSON.stringify(targetSourceValue));
                            }

                            var optionsTargetPickList = jQuery('[name="picklistmapfield"]', eleRelatedBlock).val();
                            var arrPickListValues=[];
                            var arrtargetPickList=[];
                            if(optionsTargetPickList !=''){
                                arrPickListValues = JSON.parse(optionsTargetPickList);
                                arrtargetPickList = arrPickListValues[targetPickListName];
                            }
                            //#1659840 tuannm 06192019 END

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
                            //#1659840 tuannm 06192019 START
                            for (var i = 0; i < targetPickListMap.length; i++) {
                                if(typeof arrtargetPickList !='undefined' && arrtargetPickList.length>0){
                                    if(jQuery.inArray(targetPickListMap[i], arrtargetPickList) != -1) {
                                        optionSelector.push(targetPickListMap[i]);
                                    }
                                }else {
                                    optionSelector.push(targetPickListMap[i]);
                                }
                            }
                            //#1659840 tuannm 06192019 END
                            var existed_index = [];
                            jQuery.each(listOfAvailableOptions, function(i, e) {
                                var picklistValue = jQuery(e).val();
                                if (jQuery.inArray(picklistValue, optionSelector) !== -1 && jQuery.inArray(picklistValue,existed_index) === -1) {
                                    targetOptions = targetOptions.add(jQuery(e));
                                    existed_index.push(picklistValue);
                                }
                            });
                            var targetPickListSelectedValue = '';
                            targetPickListSelectedValue = targetOptions.filter('[selected]').val();
                            if (targetPickListMap.length == 1) {
                                targetPickListSelectedValue = targetPickListMap[0]; // to automatically select picklist if only one picklistmap is present.
                            }
                            else{1
                                targetPickListSelectedValue = "";
                            }
                            targetPickList.html(targetOptions).val(targetPickListSelectedValue).trigger("liszt:updated");
                        });
                    }
                });
                //To Trigger the change on load
                sourcePickListElements.trigger('change');
         }
    },

    registerEditViewEvents: function (container) {
        var thisInstance = this;
        var type = container.closest('.fieldBlockContainer').data('block-type');
        if(type != 'flexlist'){
            // Update width of input in related list
            var listViewEntriesTable = container.find('.listViewEntriesTable');
            if(listViewEntriesTable.length == 0) listViewEntriesTable = container.find('.relatedRecords');
            thisInstance.applyWidthForFields(listViewEntriesTable);
            vtUtils.applyFieldElementsView(container.find('.relatedRecords'));
            thisInstance.registerEventForAddMoreButton(container);
            thisInstance.registerEventForDeleteButton(container);
            thisInstance.registerEventShowChildRelatedRecords(container);
            thisInstance.updateRelatedRecordsFieldsInfo(container);
            thisInstance.registerClearReferenceSelectionEvent(container);
            thisInstance.registerEventForPaging();
            container.find('.relatedRecords').each(function (idx,ele) {
                thisInstance.registerDetailEventForPicklistDependencySetup(jQuery(ele));
                thisInstance.registerValidateFieldOnChange(jQuery(ele));
            });

            thisInstance.registerSubmitEvent(container);
            //thisInstance.registerEventForSelectExistingRecordButton(container);
        }else{
            var blockId = container.data('block-id');
            thisInstance.registerEventForFlexList(blockId);
        }
    },
    applyWidthForFields: function (listViewEntriesTable,is_new) {
        listViewEntriesTable.find('input:not(:checkbox):not(:radio),select').each(function (idx,ele) {
            var parent_td = jQuery(ele).closest('td');
            //parent_td.closest('table').find('th').css('width', 'auto');
            parent_td.closest('table').find('th').each(function (i,e) {
                if(i > 0) jQuery(e).css('width', 'auto');
                });
            var field_width_config = parent_td.data('field-width');
            var parent_div = parent_td.find('div.input-group');

            if(field_width_config){
                if(parent_div.length > 0){
                    parent_div.removeClass('input-group');
                    parent_div.css('min-width', field_width_config);
                    parent_div.css('float', 'left');
                    parent_div.css('position', 'relative');
                    parent_div.css('display', 'table');
                    parent_div.css('border-collapse', 'separate');
                    parent_div.css('width', field_width_config);
                }
                var referencefield_div = $(ele).closest('div.referencefield-wrapper');
                if(referencefield_div.length > 0){
                    referencefield_div.attr('style','width:' + field_width_config+';');
                }
                if(is_new){
                    parent_td.attr('style','width:' + field_width_config+';');
                }
                var data_type = parent_td.data('field-type');
                var view = app.getViewName();
                if(data_type == "multipicklist" && view == "Detail"){
                    parent_td.find('select.select2').select2({ width: field_width_config });
                    if(is_new) parent_td.find('div.select2-container').css({ display: 'none' });
                }
                if(view == "Detail"){
                    parent_td.find('div.row-fluid').find('span.value').css('width', field_width_config);
                }
                jQuery(ele).css('width', field_width_config);
                //th.css('width', field_width_config);
            }
            else {
                if(jQuery(ele).hasClass('input-medium')) {
                    jQuery(ele).removeClass('input-medium').addClass('input-small');
                }else if(jQuery(ele).hasClass('input-large') || jQuery(ele).is('select')) {
                    jQuery(ele).removeClass('input-large');//.addClass('input-medium');
                    jQuery(ele).css('width', '190px')
                }else if(jQuery(ele).hasClass('dateField')) {
                    jQuery(ele).css('width', '100%')
                }
            }
        });
        listViewEntriesTable.find('td.fieldValue').each(function (idx,ele) {
            var td_width = jQuery(ele).data('field-width');
            jQuery(ele).css('width', td_width);
            jQuery(ele).find('div:first').css('width', td_width);
        });
        listViewEntriesTable.find('textarea').each(function (idx,ele) {
            var field_width_config = jQuery(ele).closest('td').data('field-width');
            if(field_width_config){
                jQuery(ele).css('width', field_width_config);
                jQuery(ele).css('max-width', field_width_config);
                var view = app.getViewName();
                if(view == "Detail"){
                    var parent_td = jQuery(ele).closest('td');
                    parent_td.find('div.row-fluid').find('span.value').css('width', field_width_config);
                }
            }
            else if(jQuery(ele).hasClass('textAreaElement')) {
                jQuery(ele).css({width : '200px',height : '74px'})
            }
        });
    },
    registerSubmitEvent: function(container) {
        var form=jQuery('#EditView');
        form.submit(function(e){
            // by Pham for fix issue could not save unit_price when enable module
            // https://crm.vtedev.com/index.php?module=ProjectTask&view=Detail&record=1547449&app=PROJECT
            var field = $('[name="unit_price"]');
            if(field.length >0) {
                var unit_price = field.val();
                if(unit_price == ''){
                    unit_price = 0;
                }else{
                    var fieldData = field.data();
                    //As replace is doing replace of single occurence and using regex
                    //replace has a problem with meta characters  like (.,$),so using split and join
                    var strippedValue = unit_price.split(fieldData.groupSeparator);
                    strippedValue = strippedValue.join("");
                    strippedValue = strippedValue.replace(fieldData.decimalSeparator, '.');
                    unit_price = strippedValue;
                }
                if(unit_price > 0){
                    var base_currency = $('[name="base_currency"]').val();
                    $('[name="'+base_currency+'"]').val(unit_price);
                    var curr_id = base_currency.replace("curname","");
                    var cur_conv_rate = "cur_conv_rate"+curr_id;
                    if($('[name="'+cur_conv_rate+'"]').length == 0){
                        $('<input>', {
                            type: 'hidden',
                            name: cur_conv_rate,
                            value: curr_id
                        }).appendTo('#EditView');
                    }
                    if($('[name="base_currency_input"]').length == 0){
                        $('<input>', {
                            type: 'hidden',
                            name: 'base_currency_input',
                            value: base_currency
                        }).appendTo('#EditView');
                    }
                }
            }

            //End
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
                var arrFields=selected_fields.split(',');
                for(var idIndex in arrFields ) {
                    var elementName = arrFields[idIndex];
                    if(multipicklist_fields.indexOf(elementName) != -1) {
                        var expectedElementId = 'relatedblockslists['+blockId+']['+rowNo+']['+elementName+'][]';
                        elementName = elementName+'[]';
                    }else{
                        var expectedElementId = 'relatedblockslists['+blockId+']['+rowNo+']['+elementName+']';
                        if(reference_fields.indexOf(elementName) != -1) {
                            var valElement = relatedRecord.find('[name="' + elementName + '"]');
                            var fieldContainer = valElement.closest('.fieldValue');
                            // fieldContainer.append(valElement);
                            relatedRecord.find('[name="' + elementName + '_display"]').attr('id', expectedElementId+'_display')
                                .filter('[name="' + elementName + '_display"]').attr('name', expectedElementId+'_display');
                        }
                    }
                    var arr_elementName = RegExp(/_(.*)/).exec(elementName);
                    var inputElement=relatedRecord.find('[name="' + elementName + '"]');
                    if(inputElement.length == 0) inputElement=relatedRecord.find('[name="' + arr_elementName[1] + '"]');
                    inputElement.attr('id', 'relatedblockslists_'+blockId+"_"+rowNo+"_"+elementName).attr('name', expectedElementId)
                        .data('fieldname',elementName);
                }
                thisInstance.registerEventForPicklistDependencySetup(relatedRecord,rowNo,blockId);
                var indexInstance = Vtiger_Index_Js.getInstance();
                indexInstance.registerAutoCompleteFields(relatedRecord);

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
        var process_container = container;
        container.find('.relatedBtnAddMore').on('click', function (e) {
            var element = jQuery(e.currentTarget);
            var relatedblockslists = element.closest('.relatedblockslists_records');
            var blockId=element.data('block-id');
            var type=element.data('type');
            var relModule=element.data('rel-module');
            var currentRowNumber=jQuery('.relatedRecords', relatedblockslists).length;
            var sequenceNumber=currentRowNumber+1;
            var recordid = $('[name="record"],[name="recordid"]').val();
            if(type=='block') {
                // Generate new block
                var actionParams = {
                    "data" : {
                        "module":"RelatedBlocksLists",
                        "view": "MassActionAjax",
                        "relmodule" : relModule,
                        "blockid" : blockId,
                        "parent_module" : app.getModuleName(),
                        "parent_record" : recordid,
                        "mode" : 'generateNewBlock'
                    }
                };
                app.request.post(actionParams).then(
                    function(err,data) {
                        if(err == null && data) {
                            var newRow=jQuery('<div class="relatedRecords" data-row-no="'+sequenceNumber+'"><input type="hidden" name="relatedblockslists['+blockId+']['+sequenceNumber+'][module]" value="'+relModule+'"/>'+data+'</div>');

                            element.closest('div.row').before(newRow);
                            //thisInstance.applyWidthForFields(newRow);
                            //relatedblockslists.find('div.relatedRecords:last').after(newRow);

                            thisInstance.updateLineItemsElementWithSequenceNumber(newRow,blockId,sequenceNumber);
                            vtUtils.applyFieldElementsView(newRow);
                            thisInstance.registerEventForDeleteButton(newRow);
                            var indexInstance = Vtiger_Index_Js.getInstance();
                            indexInstance.referenceModulePopupRegisterEvent(newRow);
                            // indexInstance.registerReferenceCreate(newRow);
                            indexInstance.registerAutoCompleteFields(newRow);
                            thisInstance.registerClearReferenceSelectionEvent(newRow);
                            thisInstance.registerValidateFieldOnChange(newRow);
                            var container = $(newRow).closest('.relatedblockslists_records');
                            thisInstance.updateRelatedRecordsFieldsInfo(container);
                            var container = element.closest('div.relatedblockslists_records');
                            var chk_edit_inline_edit = container.find('.chk_edit_inline_edit').val();
                            if(chk_edit_inline_edit == 0){
                                newRow.find(':input').attr("disabled",true);
                            }
                            $('#EditView').vtValidate();
                            $('input[type="text"]').on('change',function(){$(this).focus();$(this).blur()})
                            thisInstance.applyWidthForFields(newRow,true);
                        }
                    }
                );
            } else {
                var listViewEntriesTable=process_container.find('table.listViewEntriesTable');
                var newRow = thisInstance.getBasicRow(process_container).addClass('relatedRecords');
                newRow.append('<input type="hidden" name="relatedblockslists['+blockId+']['+sequenceNumber+'][module]" value="'+relModule+'"/>');
                listViewEntriesTable.find('tr:last').after(newRow);
                thisInstance.updateLineItemsElementWithSequenceNumber(newRow,blockId,sequenceNumber);
                vtUtils.applyFieldElementsView(newRow);
                thisInstance.registerEventForDeleteButton(newRow);
                var indexInstance = Vtiger_Index_Js.getInstance();
                indexInstance.referenceModulePopupRegisterEvent(newRow);
                // indexInstance.registerReferenceCreate(newRow);
                indexInstance.registerAutoCompleteFields(newRow);
                thisInstance.registerClearReferenceSelectionEvent(newRow);
                thisInstance.registerValidateFieldOnChange(newRow);
                thisInstance.registerDetailEventForPicklistDependencySetup(newRow);
                var new_container = element.closest('div.relatedblockslists_records');
                var chk_edit_inline_edit = new_container.find('.chk_edit_inline_edit').val();
                if(chk_edit_inline_edit == 0){
                    newRow.find(':input').attr("disabled",true);
                }
                $('#EditView').vtValidate();
                $('input[type="text"]').on('change',function(){$(this).focus();$(this).blur()});
                thisInstance.applyWidthForFields(newRow,true);
            }
        });
    },

    referenceModuleChangeEvent : function(container){
        var thisInstance = this;
        container.find('.referenceModulesList').select2().change(function(e){
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
            for(var idIndex in arrFields ) {
                var elementName = arrFields[idIndex];
                if (elementName != '') {
                    var actualElementName = elementName;
                    if(multipicklist_fields.indexOf(elementName) != -1) {
                        var expectedElementId = 'relatedblockslists['+id+']['+expectedSequenceNumber+']['+elementName+'][]';
                        actualElementName = actualElementName+'[]';
                    }else{
                        var expectedElementId = 'relatedblockslists['+id+']['+expectedSequenceNumber+']['+elementName+']';
                        if(reference_fields.indexOf(elementName) != -1) {
                            var valElement = lineItemRow.find('[name="' + elementName + '"]');
                            var fieldContainer = valElement.closest('.fieldValue');
                            // fieldContainer.append(valElement);
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
            parentTdElement.find('[name="'+fieldName+'_display"]').removeAttr("disabled").removeAttr('readonly').val('');
            element.trigger(Vtiger_Edit_Js.referenceDeSelectionEvent);
            e.preventDefault();
        });

        container.find('.sourceField').on(Vtiger_Edit_Js.postReferenceSelectionEvent,function(e,result){
            var fieldName = jQuery(this).attr("name");
            var element = container.find('[name="'+fieldName+'_display"]');
            element.attr("disabled","disabled");
        });
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
        app.request.post({data:params}).then(
            function(err,data){
                if(err == null){
                    aDeferred.resolve(data);
                }else{
                    aDeferred.reject();
                }
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
                    var serverDataFormat = data;
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
        fieldDisplayElement.val(selectedName).attr('readonly',true).attr("disabled","disabled");
        fieldElement.trigger(Vtiger_Edit_Js.referenceSelectionEvent, {'source_module' : popupReferenceModule, 'record' : id, 'selectedName' : selectedName});

        fieldDisplayElement.validationEngine('closePrompt',fieldDisplayElement);
    },

    getBasicRow : function(container) {
        var basicRow = container.find('.relatedRecordsClone');
        var newRow = basicRow.clone();
        return newRow.removeClass('hide relatedRecordsClone');
    },

    registerEventForDeleteButton : function(container,id){
        var thisInstance = this;
        var button = container.find('.relatedBtnDelete');
        // 
        button.unbind().click(function(e) {
            //container.on('click','.relatedBtnDelete',function(e){
            var element = jQuery(e.currentTarget);
            var src_record =jQuery('[name="record"]').val();
            if(src_record == undefined){
                src_record =jQuery('[name="record_id"]').val();
            }
            // Delete record
            var record=element.data('record-id');
            if(record) {
                var deleteMessage = 'Do you want delete this record ?';
                app.helper.showConfirmationBox({
                    message: deleteMessage
                }).then(function () {
                    var relModule = element.data('rel-module');
                    if(relModule=="Events"){
                        relModule='Calendar';
                    }
                    var params = {};
                    params.action = 'RelationAjax';
                    params.mode = 'deleteRelation';
                    params.related_module = relModule;
                    params.src_record = src_record;
                    params.related_record_list = [record];
                    params.module = app.getModuleName();
                    app.request.post({data:params}).then(
                        function (err,data) {
                            var headerContainer = element.closest('div.relatedblockslists_records').prev().prev();
                            var blockId =  headerContainer.data('block-id');
                            var page = headerContainer.find('.listViewPageJump').data('page-number');
                            element.closest('.relatedRecords').remove();
                            element.closest('.blockData').remove();

                            //#1165708
                            // dont reload list after delete
                            //thisInstance.loadRelatedListByPaging(src_record, blockId, headerContainer, page);
                        },
                        function(textStatus, errorThrown){

                        }
                    );
                });

            }else{
                element.closest('.relatedRecords').remove();
            }
        });
    },
    collapseExpandBlock : function(){
        $('.related-blocks-lists-blockToggle').off('click');
        $('body').delegate('.related-blocks-lists-blockToggle','click',function(e){
            var element = jQuery(e.currentTarget);
            var mode = element.data('mode');
            $(this).addClass('hide');
            var block = $(this).closest('div.block');
            var title =  $(this).closest('.textOverflowEllipsis');
            if(mode == 'hide'){
                title.find('img.related-blocks-lists-blockToggle[data-mode="show"]').removeClass('hide');
                block.find('div.relatedblockslists_records').removeClass('hide');
            }else{
                title.find('img.related-blocks-lists-blockToggle[data-mode="hide"]').removeClass('hide');
                block.find('div.relatedblockslists_records').addClass('hide');
            }
        });
    },
    registerEventShowChildRelatedRecords : function(container,id){
        var thisInstance = this;
        var spanCollapsed = container.find('.vtetoggle');
        spanCollapsed.unbind().click(function(e) {
            var element = jQuery(e.currentTarget);
            //Gets all <tr>'s  of greater depth
            //below element in the table
            var findChildren = function (tr) {
                var depth = tr.data('depth');
                return tr.nextUntil($('tr').filter(function () {
                    return $(this).data('depth') <= depth;
                }));
            };

            var el = $(this);
            var tr = element.closest('tr'); //Get <tr> parent of toggle button
            var children = findChildren(tr);

            //Remove already collapsed nodes from children so that we don't
            //make them visible.
            //(Confused? Remove this code and close Item 2, close Item 1
            //then open Item 1 again, then you will understand)
            var subnodes = children.filter('.expand');
            subnodes.each(function () {
                var subnode = $(this);
                var subnodeChildren = findChildren(subnode);
                children = children.not(subnodeChildren);
            });
            //Change icon and hide/show children
            if (tr.hasClass('vteCollapse')) {
                tr.removeClass('vteCollapse').addClass('expand');
                children.toggleClass('slideToggle');
                children.find('td').css({'padding':'0px','border-top':'none'})
                //children.fadeOut();
            } else {
                tr.removeClass('expand').addClass('vteCollapse');
                children.toggleClass('slideToggle');
                children.find('td').css({'padding':'3px','border-top':'1px solid #ddd'})
                //children.fadeIn(600);
            }
            return children;
        });
    },
    registerEvents: function() {
        // Only load when loadHeaderScript=1 BEGIN #241208
        if (typeof VTECheckLoadHeaderScript == 'function') {
            if (!VTECheckLoadHeaderScript('RelatedBlocksLists')) {
                return;
            }
        }
        // Only load when loadHeaderScript=1 END #241208

        var container = jQuery(document).find('form');
        this.checkAndGenerateBlocks(container);
        this.collapseExpandBlock();
        var self = this;
        jQuery(document).ajaxComplete(function(){
            self.collapseExpandBlock();
        });
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

var RBL_relatedblockslists_records = null;

jQuery(document).ready(function(){
    RelatedBlocksLists_Js.___init();
    app.event.on("post.relatedListLoad.click",function(event, container){
        RelatedBlocksLists_Js.___init();
    });

    // Load jquery if not exist
    if ($("<input/>").validationEngine == undefined){
        loadScript('libraries/jquery/posabsolute-jQuery-Validation-Engine/js/jquery.validationEngine.js');
    }

    $("body").mousemove(function(e){
        var p1 = $(e.target);
        if (!p1.hasClass('fieldBlockContainer')){
            p1 = $(e.target).closest('.fieldBlockContainer');
        }
        if (p1.hasClass('fieldBlockContainer')){
            var p2 = p1.find(".relatedblockslists_records");
            if (p2.length > 0){
                RBL_relatedblockslists_records = p2;
            }
        }
    });

    $(document).keydown(function (e) {
        if( e.which === 65 && e.altKey ) {
            if (RBL_relatedblockslists_records){
                RBL_relatedblockslists_records.find(".relatedBtnAddMore").trigger("click");
            } else {
                $(".relatedblockslists_records").first().find(".relatedBtnAddMore").trigger("click");
            }
        }
    });
    window.onbeforeunload = null;
});
/**
 * @Link http://stackoverflow.com/questions/950087/how-to-include-a-javascript-file-in-another-javascript-file#answer-950146
 */
function loadScript(url, callback)
{
    // Adding the script tag to the head as suggested before
    var head = document.getElementsByTagName('head')[0];
    var script = document.createElement('script');
    script.type = 'text/javascript';
    script.src = url;

    // Then bind the event to the callback function.
    // There are several events for cross browser compatibility.
    script.onreadystatechange = callback;
    script.onload = callback;

    // Fire the loading
    head.appendChild(script);
}


function waitUntil(waitFor,toDo){
    if(waitFor()) {
        toDo();
    } else {
        setTimeout(function() {
            waitUntil(waitFor, toDo);
        }, 300);
    }
}

jQuery(document).ajaxComplete( function (event, request, settings) {
    var url = settings.data;

    if(typeof url == 'undefined' && settings.url) url = settings.url;
    if(url == undefined) return;
    if (Object.prototype.toString.call(url) =='[object String]') {
        var targetModule = '';
        var targetView = '';
        var sourceModule = '';
        var mode = '';
        var viewMode = '';
        var record = '';
        var relatedModule = '';
        var sURLVariables = url.split('&');
        for (var i = 0; i < sURLVariables.length; i++) {
            var sParameterName = sURLVariables[i].split('=');
            if (sParameterName[0] == 'module') {
                targetModule = sParameterName[1];
            } else if (sParameterName[0] == 'view') {
                targetView = sParameterName[1];
            } else if (sParameterName[0] == 'sourceModule') {
                sourceModule = sParameterName[1];
            } else if (sParameterName[0] == 'mode') {
                mode = sParameterName[1];
            } else if (sParameterName[0] == 'requestMode') {
                viewMode = sParameterName[1];
            } else if (sParameterName[0] == 'record') {
                record = sParameterName[1];
            } else if (sParameterName[0] == 'relatedModule') {
                relatedModule = sParameterName[1];

            }
        }
        if (mode == 'showRelatedList') {
            var params = {};
            var mode = 'replaceRelatedBlockLists';
            var module = app.getModuleName();
            params['module'] = 'RelatedBlocksLists';
            params['action'] = 'ActionAjax';
            params['mode'] = 'getBlockRelatedLists';
            params['source_module'] = module;
            params['parent_record'] = record;
            params['relatedModule'] = relatedModule;

            app.request.post({data:params}).then(
                function(err,data) {
                    if(err == null) {
                        var blocks=data;
                        blocks = jQuery.parseJSON(blocks);
                        blocks = blocks.reverse();
                        var arrBlockId = [];
                         global_flag = false;
                        if(blocks.length) {
                            $('.relatedContainer').css('display','none');
                             app.helper.showProgress();
                            $('.relatedContainer').html('');
                            blocks.forEach(function (item){
                                var after_block = item.blockData[0];
                                var after_block_label = item.blockData[1];
                                var blockid = item.blockId;
                                if(arrBlockId[after_block]){
                                    arrBlockId[after_block].push(blockid);
                                }else{
                                    arrBlockId[after_block] = [blockid];
                                }
                                if(item.blockData[3].length > 2){
                                    jQuery('[name="picklistDependency"]','form').attr('value',item.blockData[3]);
                                }
                                var html = '<div id="related_block_content_'+item.blockData[0]+'"></div>';
                                $('.relatedContainer').append(html);
                            });
			     var related_list = '';
                            var totalItems = blocks.length;
                            idxItem = 0;
                           
                            blocks.forEach(function (item){
                                idxItem += 1;
                                var after_block = item.blockData[0];
                                var after_block_label = item.blockData[1];
                                var blockid = item.blockId;
                                var viewParams = {
                                    module:'RelatedBlocksLists',
                                    view:'MassActionAjax',
                                    mode: mode,
                                    record:record,
                                    blockid:blockid,
                                    source_module:module,
                                    relatedModule:relatedModule,
                                };
                                 //app.helper.showProgress();
                                app.request.post({data:viewParams}).then(
                                    function (err,data) {
                                        if (err == null) {
                                            app.helper.hideProgress();
                                            $('#related_block_content_'+item.blockData[0]).html(data);
                                            var rbl_item = jQuery('div.relatedblockslists' + blockid);
                                            var RelatedClass = new RelatedBlocksLists_Js()
                                            RelatedClass.registerDetailViewEvents(rbl_item);
                                            RelatedClass.registerEventForSelectExistingRecordButton(rbl_item);
                                            var chk_detail_inline_edit = rbl_item.find('.chk_detail_inline_edit').val();
                                            if(chk_detail_inline_edit == 0){
                                                rbl_item.find('span.edit').remove();
                                            }
                                            if (idxItem == totalItems){
                                                global_flag = true;
                                            }
                                        }
                                    }
                                );
                            })
                        }
                        
                        var showBlockInterval = setInterval(function(){
                            if (global_flag){
                              $('.relatedContainer').css('display','block');
                              clearInterval(showBlockInterval);
                            }
                      }, 2000);
                    }
                }
            );

        }

    }
});

jQuery(document).ready(function(){
    var url = window.location.href;
    if (Object.prototype.toString.call(url) =='[object String]') {
        var targetModule = '';
        var targetView = '';
        var sourceModule = '';
        var mode = '';
        var viewMode = '';
        var record = '';
        var relatedModule = '';
        var sURLVariables = url.split('&');
        for (var i = 0; i < sURLVariables.length; i++) {
            var sParameterName = sURLVariables[i].split('=');
            if (sParameterName[0] == 'module') {
                targetModule = sParameterName[1];
            } else if (sParameterName[0] == 'view') {
                targetView = sParameterName[1];
            } else if (sParameterName[0] == 'sourceModule') {
                sourceModule = sParameterName[1];
            } else if (sParameterName[0] == 'mode') {
                mode = sParameterName[1];
            } else if (sParameterName[0] == 'requestMode') {
                viewMode = sParameterName[1];
            } else if (sParameterName[0] == 'record') {
                record = sParameterName[1];
            } else if (sParameterName[0] == 'relatedModule') {
                relatedModule = sParameterName[1];

            }
        }
        if (mode == 'showRelatedList') {
            var params = {};
            var mode = 'replaceRelatedBlockLists';
            var module = app.getModuleName();
            params['module'] = 'RelatedBlocksLists';
            params['action'] = 'ActionAjax';
            params['mode'] = 'getBlockRelatedLists';
            params['source_module'] = module;
            params['parent_record'] = record;
            params['relatedModule'] = relatedModule;

            app.request.post({data:params}).then(
                function(err,data) {
                    if(err == null) {
                        var blocks=data;
                        blocks = jQuery.parseJSON(blocks);
                        blocks = blocks.reverse();
                        var arrBlockId = [];
                         global_flag = false;
                        if(blocks.length) {
                            $('.relatedContainer').css('display','none');
                             app.helper.showProgress();
                            $('.relatedContainer').html('');
                            blocks.forEach(function (item){
                                var after_block = item.blockData[0];
                                var after_block_label = item.blockData[1];
                                var blockid = item.blockId;
                                if(arrBlockId[after_block]){
                                    arrBlockId[after_block].push(blockid);
                                }else{
                                    arrBlockId[after_block] = [blockid];
                                }
                                if(item.blockData[3].length > 2){
                                    jQuery('[name="picklistDependency"]', 'form').attr('value',item.blockData[3]);
                                }
                                var html = '<div id="related_block_content_'+item.blockData[0]+'"></div>';
                                $('.relatedContainer').append(html);
                            });

                            var totalItems = blocks.length;
                            idxItem = 0;
                           
                            blocks.forEach(function (item){
                                idxItem += 1;
                                var after_block = item.blockData[0];
                                var after_block_label = item.blockData[1];
                                var blockid = item.blockId;
                                var viewParams = {
                                    module:'RelatedBlocksLists',
                                    view:'MassActionAjax',
                                    mode: mode,
                                    record:record,
                                    blockid:blockid,
                                    source_module:module,
                                    relatedModule:relatedModule,
                                };
                                //app.helper.showProgress();
                                app.request.post({data:viewParams}).then(
                                    function (err,data) {
                                        if (err == null) {
                                            app.helper.hideProgress();
                                            $('#related_block_content_'+item.blockData[0]).html(data);
                                            var rbl_item = jQuery('div.relatedblockslists' + blockid);
                                            var RelatedClass = new RelatedBlocksLists_Js()
                                            RelatedClass.registerDetailViewEvents(rbl_item);
                                            RelatedClass.registerEventForSelectExistingRecordButton(rbl_item);

                                            var chk_detail_inline_edit = rbl_item.find('.chk_detail_inline_edit').val();
                                            if(chk_detail_inline_edit == 0){
                                                rbl_item.find('span.edit').remove();
                                            }
                                            if (idxItem == totalItems){
                                                global_flag = true;
                                            }
                                        }
                                    }
                                );

                            })
                        }
                        
                        var showBlockInterval = setInterval(function(){
                            if (global_flag){
                              $('.relatedContainer').css('display','block');
                              clearInterval(showBlockInterval);
                            }
                      }, 2000);
                    }
                }
            );

        }
    }
});
// add sequence for if "Replace related list" is selected).
$('body').on('change','select[name="after_block"]',function(){
    var module = $(this).find('option:selected').text();
    var parent = $(this).parent().parent().parent();
    if(module.indexOf('Replace') !=-1){
        var html = '<div class="col-sm-12 col-xs-12 input-group"><div class="form-group">';
        html += '<label class="col-sm-4 control-label fieldLabel"><strong>Sequence</strong></label>';
        html += '<div class="fieldValue col-lg-3 col-md-3 col-sm-3 input-group">';
        html += '<input type="text" class="inputElement" name="sequence" value="">';
        html + '</div></div></div>';

        $('.sequence-group').html(html);
    }else{
        $('.sequence-group').html('');
    }
});

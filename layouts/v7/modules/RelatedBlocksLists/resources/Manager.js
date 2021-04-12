/* ********************************************************************************
 * The content of this file is subject to the Related Blocks & Lists ("License");
 * You may not use this file except in compliance with the License
 * The Initial Developer of the Original Code is VTExperts.com
 * Portions created by VTExperts.com. are Copyright(C) VTExperts.com.
 * All Rights Reserved.
 * ****************************************************************************** */

Vtiger.Class("RelatedBlocksLists_Manager_Js",{
    instance:false,
    getInstance: function(){
        if(RelatedBlocksLists_Manager_Js.instance == false){
            var instance = new RelatedBlocksLists_Manager_Js();
            RelatedBlocksLists_Manager_Js.instance = instance;
            return instance;
        }
        return RelatedBlocksLists_Manager_Js.instance;
    }
},{
    updatedBlockSequence: {},
    sortBlockSequence: false,
    sortBlockAfterBlock: false,
    registerEventForRelatedBlocksListsButtons: function (container) {
        var thisInstance = this;
        container.find('.btnRelatedBlocksLists').on('click', function(e){
            var element=jQuery(e.currentTarget);
            var url=element.data('url');
            thisInstance.showEditView(url);
        });
    },

    /*
     * function to show editView for Add/Edit block
     * @params: url - add/edit url
     */
    showEditView : function(url) {
        var self = this;
        var thisInstance = this;
        app.helper.showProgress();
        var actionParams = {
            "url":url
        };
        app.request.post(actionParams).then(
            function(err,data) {
                app.helper.hideProgress();
                if(data) {
                    var callBackFunction = function(data) {
                        var form = jQuery('#relatedblockslists_form');
                        form.submit(function(e) {
                            e.preventDefault();
                        });
                        var params = {
                            submitHandler: function(form) {
                                jQuery("button[name='saveButton']").attr("disabled","disabled");
                                thisInstance.saveBlockDetails();
                            }
                        };
                        form.vtValidate(params);
                        var moduleName = $('select[name="select_module"]').val();
                        var urlParam = app.convertUrlToDataParams(url);
                        if(urlParam.blockid == undefined){
                            self.changeFilterPicklist(moduleName);
                            self.changeSortPicklist(moduleName);
                        }
                    };
                    app.helper.showModal(data,{'cb' : function(data){
                        if(typeof callBackFunction == 'function'){
                            callBackFunction(data);
                        }
                        thisInstance.registerPopupEvents();
                    }});
                }
            }
        );
    },

    registerPopupEvents: function () {
        var container=jQuery('#relatedblockslists_form');
        this.registerPopupSelectModuleEvent(container);
        this.makeFieldsListSortable(container);
        this.arrangeSelectChoicesInOrder(container);
    },

    registerPopupSelectModuleEvent: function (container) {
        var thisInstance = this;
        container.on("change",'[name="select_module"]', function(e) {
            app.helper.showProgress();
            var select_module=jQuery(this).val();
            var actionParams = {
                module:"RelatedBlocksLists",
                view:"MassSettingsAjax",
                mode:"getFields",
                select_module:select_module,
            };
            app.request.post({data:actionParams}).then(
                function(err,data) {
                    app.helper.hideProgress();
                    if(err == null && data) {
                        container.find('#fields').html(data);
                        // TODO Make it better with jQuery.on
                        vtUtils.applyFieldElementsView(container);
                        //register all select2 Elements
                        thisInstance.makeFieldsListSortable(container);
                    }
                }
            );
        })
    },

    /**
     * Function which will arrange the selected element choices in order
     */
    arrangeSelectChoicesInOrder : function(container) {
        var selectElement = container.find('#selected_fields');
        var select2Element = app.getSelect2ElementFromSelect(selectElement);

        var choicesContainer = select2Element.find('ul.select2-choices');
        var choicesList = choicesContainer.find('li.select2-search-choice');
        var selectedOptions = selectElement.find('option:selected');
        var selectedOrder = JSON.parse(jQuery('input[name="topFieldIdsList"]', container).val());
        var selectedValuesByOrder = {};
        for(var index=selectedOrder.length ; index > 0 ; index--) {
            var selectedValue = selectedOrder[index-1];
            var option = selectedOptions.filter('[value="'+selectedValue+'"]');
            choicesList.each(function(choiceListIndex,element){
                var liElement = jQuery(element);
                if(liElement.find('div').html() == option.html()){
                    selectedValuesByOrder[index-1] = selectedValue;
                    choicesContainer.prepend(liElement);
                    return false;
                }
            });
        }
        container.find('input[name="selectedFieldsList"]').val(JSON.stringify(selectedValuesByOrder));
    },

    makeFieldsListSortable : function (container) {
        var thisInstance = this;
        var selectElement = container.find('#selected_fields');
        var select2Element = app.getSelect2ElementFromSelect(selectElement);
        var select2ChoiceElement = select2Element.find('ul.select2-choices');
        select2ChoiceElement.sortable({
            'containment': select2ChoiceElement,
            start: function() { },
            update: function() {
                var selectedValuesByOrder = {};
                var selectedOptions = selectElement.find('option:selected');
                var orderedSelect2Options = select2Element.find('li.select2-search-choice').find('div');
                var i = 1;
                orderedSelect2Options.each(function(index,element){
                    var chosenOption = jQuery(element);
                    selectedOptions.each(function(optionIndex, domOption){
                        var option = jQuery(domOption);
                        if(option.html() == chosenOption.html()) {
                            selectedValuesByOrder[i++] = option.val();
                            return false;
                        }
                    });
                });
                container.find('input[name="selectedFieldsList"]').val(JSON.stringify(selectedValuesByOrder));
            }
        });
    },

    updateFieldOrder : function(container) {
        var selectedValuesByOrder = {};
        var selectElement = container.find('#selected_fields');
        var select2Element = app.getSelect2ElementFromSelect(selectElement);
        var selectedOptions = selectElement.find('option:selected');
        var orderedSelect2Options = select2Element.find('li.select2-search-choice').find('div');
        var i = 1;
        orderedSelect2Options.each(function(index,element){
            var chosenOption = jQuery(element);
            selectedOptions.each(function(optionIndex, domOption){
                var option = jQuery(domOption);
                if(option.html() == chosenOption.html()) {
                    selectedValuesByOrder[i++] = option.val();
                    return false;
                }
            });
        });
        container.find('input[name="selectedFieldsList"]').val(JSON.stringify(selectedValuesByOrder));
    },

    /**
     * This function will save the block detail
     */
    saveBlockDetails : function() {
        var form = jQuery('#relatedblockslists_form');
        var thisInstance = this;
        thisInstance.updateFieldOrder(form);
        app.helper.showProgress();
        var data = form.serializeFormData();
        data['action'] = 'SaveAjax';
        data['module'] = 'RelatedBlocksLists';

        app.request.post({data:data}).then(
            function(err,data) {
                app.helper.hideProgress();
                if(err == null && data) {
                    app.helper.showSuccessNotification({'message': app.vtranslate('Block Saved')});
                    var blockid = data.blockid;
                    var after_block = data.after_block;
                    var length = true;
                    thisInstance.loadListBlocks(blockid, after_block,length);
                    app.helper.hideModal();
                }
            }
        );
    },

    loadListBlocks: function (blockid,after_block,length) {
        var relatedBlockAdd = $('.relatedBlockAdd_after_'+after_block);
        if(relatedBlockAdd.length == 0){
            jQuery(document).find("#moduleBlocks #block_" + after_block).after('<div class="related-block-list-contents relatedBlockAdd_after_'+after_block+'"></div>');
        }
        var thisInstance = this;
        app.helper.showProgress();
        var container = jQuery('#detailViewLayout');
        var sourceModule = jQuery('[name="layoutEditorModules"]').val();
        var actionParams = {
            module:"RelatedBlocksLists",
            view:"MassSettingsAjax",
            mode:"getBlocks",
            blockid:blockid
        };

        app.request.post({data:actionParams}).then(
            function(err,data) {
                app.helper.hideProgress();
                if(err == null && data) {
                    container.find('.relatedblock_'+blockid).remove();
                    $('.relatedBlockAdd_after_'+after_block).append(data);
                    thisInstance.registerBlockEvents(container.find('.relatedblock_'+blockid));
                    jQuery("input[name='related-blocks-lists-collapseBlock']").bootstrapSwitch();
                    jQuery("input[name='related-blocks-lists-collapseBlock']").bootstrapSwitch('handleWidth', '27px');
                    jQuery("input[name='related-blocks-lists-collapseBlock']").bootstrapSwitch('labelWidth', '25px');
                    jQuery("a.related-block-list-editFieldDetails").on('click',function(e){
                        var currentElement = jQuery(e.currentTarget);
                        var block_id = currentElement.data('block-id');
                        var field_name = currentElement.data('field-name');
                        var field_label = currentElement.data('field-label');
                        var sourceModule = currentElement.data('source-module');
                        var params = {
                            module:"RelatedBlocksLists",
                            view:"MassSettingsAjax",
                            mode:"showEditFields",
                            sourceModule:sourceModule,
                            blockid: block_id,
                            field_label: field_label,
                            field_name: field_name
                        }
                        app.request.post({data: params}).then(function (error, data) {
                            if (data) {
                                var callBackFunction = function(data) {

                                };
                                app.helper.showModal(data,{'cb' : function(data){
                                    if(typeof callBackFunction == 'function'){
                                        callBackFunction(data);
                                    }
                                }});
                            }
                        });
                    });
                    $('body').delegate('form#relatedblockslists_edit_fields_form button[name="saveButton"]','click',function(e){
                        var form = jQuery('#relatedblockslists_edit_fields_form');
                        var fData = form.serializeFormData();
                        params = fData;
                        app.request.post({data: params}).then(function (error, data) {
                            if (data) {
                                app.helper.hideModal();
                            }
                        });
                    });

                    thisInstance.realtedBlockListMakeBlocksListSortable();
                    var related_block_load_sequence = $('#related_block_load_sequence').val();
                    var next = Number(related_block_load_sequence) + 1;
                    $('#related_block_load_sequence').val(next);
                    if(next == length){
                        thisInstance.sortLoadListBlocks();
                    }
                }
            }
        );

    },
    registerBlockEvents: function (container) {
        var thisInstance = this;
        container.find('.blockEditBtn').on('click', function (e) {
            var element=jQuery(e.currentTarget);
            var url=element.data('url');
            thisInstance.showEditView(url);
        });

        container.find('.blockDeleteBtn').on('click', function (e) {
            var element=jQuery(e.currentTarget);
            var message = jQuery(element).data('message-delete');
            app.helper.showConfirmationBox({'message' : message}).then(
                function(e) {
                    var blockId = jQuery(element).data('related-block-id');
                    var params = {};
                    params['module'] = 'RelatedBlocksLists';
                    params['action'] = 'ActionAjax';
                    params['mode'] = 'deleteBlock';
                    params['blockid'] = blockId;
                    app.request.post({data:params}).then(
                        function(data) {
                            container.remove();
                            //thisInstance.loadListBlocks();
                        }
                    );
                },
                function(error, err){
                }
            );
        });
    },

    loadConfiguredBlocks : function () {
        var thisInstance = this;
        var sourceModule = jQuery('[name="layoutEditorModules"]').val();
        var params = {};
        params['module'] = 'RelatedBlocksLists';
        params['action'] = 'ActionAjax';
        params['mode'] = 'getConfiguredBlock';
        params['source_module'] = sourceModule;
        params['isSetting'] = 1;
        app.request.post({data:params}).then(
            function(err,data) {
                if(err == null && data != null) {
                    var blocks=data;
                    blocks = jQuery.parseJSON(blocks);
                    if(blocks) {
                        var blockSort = new Array();
                        var length = blocks.length;

                        blocks.forEach(function(item){
                            var after_block = item.blockData[0];
                            var after_block_label = item.blockData[1];
                            var blockid = item.blockId;
                            thisInstance.loadListBlocks(blockid, after_block,length);
                            blockSort.push(blockid);

                            thisInstance.sortBlockAfterBlock = after_block;
                        });
                        thisInstance.sortBlockSequence = blockSort;

                    }
                }
            }
        );
    },
    sortLoadListBlocks:function(){
        var thisInstance = this;
        var blockSort = thisInstance.sortBlockSequence;
        var sortBlockAfterBlock = thisInstance.sortBlockAfterBlock;

        var sortBlockConten = $('div.relatedBlockAdd_after_'+sortBlockAfterBlock);
        blockSort.forEach(function(blockID){
            $('#relatedblock_'+blockID).appendTo(sortBlockConten);
        });
    },
    registerEventForFilterValue:function(){
        var self = this;
        $('body').delegate('#related_block_list_filter','change',function(e){
            var ele = jQuery(e.currentTarget);
            var rel_module = ele.data('rel-module');
            var fieldName = this.value;
            var json = $('#all_pick_lists_values').val();
            json = jQuery.parseJSON(json);
            //var arr = Object.keys(json).map(function(k) { return json[k] });
            var arr = json[fieldName];
            $('#related_block_list_value').html('<option value="" >Select an option</option>');
            if(arr.length > 0){
                arr.forEach(function(item){
                    $('#related_block_list_value').append($('<option>', {
                        value: item,
                        text: item
                    }));
                });
            }
        });

        $('body').delegate('#relatedblockslists_form [name="select_module"]','change',function(e){
            var moduleName = this.value;
            self.changeFilterPicklist(moduleName);
            self.changeSortPicklist(moduleName);
        });
    },
    changeSortPicklist : function(moduleName){
        var all_fields_of_all_module = $('#all_fields_of_all_module').val();

        all_fields_of_all_module = jQuery.parseJSON(all_fields_of_all_module);

        var arr = all_fields_of_all_module[moduleName];

        $('#related_block_list_sortfield').html('<option value="" >None</option>');
        if(arr !='' && arr != undefined){
            for(var key in arr) {

                var value = arr[key];
                $('#related_block_list_sortfield').append($('<option>', {
                    value: key,
                    text: value
                }));
            }
        }
    },
    realtedBlockListMakeBlocksListSortable: function () {
        var thisInstance = this;
        var contents = jQuery('#layoutEditorContainer').find('.related-block-list-contents');
        var table = contents.find('.related-blockSortable');
        contents.sortable({
            'containment': contents,
            'items': table,
            'revert': true,
            'tolerance': 'pointer',
            'cursor': 'move',
            'update': function (e, ui) {
                thisInstance.realtedBlockListUpdateBlockSequence();
            }
        });
    },
    realtedBlockListUpdateBlockSequence: function () {
        var thisInstance = this;
        app.helper.showProgress();
        var sequence = JSON.stringify(thisInstance.realtedBlockListUpdateBlocksListByOrder());
        var params = {};
        params['module'] = 'RelatedBlocksLists';
        params['action'] = 'ActionAjax';
        params['mode'] = 'updateSequenceNumber';
        params['sequence'] = sequence;
        app.request.post({'data': params}).then(
            function (err, data) {
                app.helper.hideProgress();
                if (err === null) {
                    app.helper.showSuccessNotification({'message': app.vtranslate('JS_BLOCK_SEQUENCE_UPDATED')});
                } else {
                    app.helper.showErrorNotification({'message': err.message});
                }
            });
    },
    realtedBlockListUpdateBlocksListByOrder: function () {
        var thisInstance = this;
        var contents = jQuery('#layoutEditorContainer').find('.related-block-list-contents');
        contents.find('.related-blockSortable').each(function (index, domElement) {
            var blockTable = jQuery(domElement);
            var blockId = blockTable.data('related-block-id');
            var actualBlockSequence = blockTable.data('sequence');
            var expectedBlockSequence = (index+1);

            if (expectedBlockSequence != actualBlockSequence) {
                blockTable.data('sequence', expectedBlockSequence);
            }
            thisInstance.updatedBlockSequence[blockId] = expectedBlockSequence;
        });
        return thisInstance.updatedBlockSequence;
    },
    changeFilterPicklist:function(moduleName){
        var all_pick_lists_of_all_module = $('#all_pick_lists_of_all_module').val();
        all_pick_lists_of_all_module = jQuery.parseJSON(all_pick_lists_of_all_module);
        var arr = all_pick_lists_of_all_module[moduleName];
        $('#related_block_list_filter').html('<option value="" >Select an option</option>');
        var arrValue = {};
        if(arr !='' && arr != undefined){
            for(var key in arr) {
                var value = arr[key];
                var filter = key.split(',');
                $('#related_block_list_filter').append($('<option>', {
                    value: filter[0],
                    text: filter[1]
                }));
                arrValue[filter[0]] = value;
            }
        }
        arrValue = JSON.stringify(arrValue);
        $('#all_pick_lists_values').val(arrValue);
    },
    registerEventForCollapseSwitch:function(){
        jQuery("body").off('switchChange.bootstrapSwitch',"input[name='related-blocks-lists-collapseBlock']");
        jQuery("body").on('switchChange.bootstrapSwitch',"input[name='related-blocks-lists-collapseBlock']",function(e){
            var currentElement = jQuery(e.currentTarget);
            if (currentElement.val() == 1) {
                currentElement.attr('value', 0);
            } else {
                currentElement.attr('value', 1);
            }

            var moduleName = app.getModuleName();
            if (moduleName != 'LayoutEditor') {
                moduleName = 'LayoutEditor';
            }
            var params = {
                module:"RelatedBlocksLists",
                view:"MassSettingsAjax",
                mode:"collapseExpandBlocks",
                blockid: currentElement.data('block-id'),
                display_status: currentElement.val()
            }

            app.request.post({data: params}).then(function (error, data) {
                if (data) {
                    app.helper.showSuccessNotification({
                        message: data
                    });
                }
            });
        });
    },
    registerEvents: function(){
        var container = jQuery('#detailViewLayout');
        this.registerEventForRelatedBlocksListsButtons(container);
        this.loadConfiguredBlocks();
        this.registerEventForFilterValue();
        this.registerEventForCollapseSwitch();
        $('body').append('<input type="hidden" value="0" id="related_block_load_sequence"/>');
    }
});

jQuery(document).ready(function () {
    var sPageURL = window.location.search.substring(1);
    var targetModule = '';
    var targetView = '';
    var sourceModule = '';
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
    }

    if (targetModule == 'LayoutEditor') {
        // Check enable
        var params = {};
        params.action = 'ActionAjax';
        params.module = 'RelatedBlocksLists';
        params.mode = 'checkEnable';
        app.request.post({data:params}).then(
            function (err,data) {
                if (err == null && data.enable == '1') {
                    if(sourceModule == '') {
                        sourceModule = jQuery('[name="layoutEditorModules"]').val();
                    }
                    var addCustomBlockButton = jQuery('.addCustomBlock');
                    var relatedBlocksListsButtons=jQuery('<button style="margin-left:5px" data-url="index.php?module=RelatedBlocksLists&view=MassSettingsAjax&mode=showSettingsForm&type=block&sourceModule=' + sourceModule + '" type="button" class="btn addButton btnRelatedBlocksLists"><i class="fa fa-plus"></i>&nbsp; Add Related Block</button><button style="margin-left:5px" data-url="index.php?module=RelatedBlocksLists&view=MassSettingsAjax&mode=showSettingsForm&type=list&sourceModule=' + sourceModule + '" type="button" class="btn addButton btnRelatedBlocksLists"><i class="fa fa-plus"></i>&nbsp; Add Related List</button>');
                    addCustomBlockButton.after(relatedBlocksListsButtons);

                    // Add related block settings
                    /*var moduleBlocks = jQuery('#moduleBlocks');
                    moduleBlocks.append('<div id="RelatedBlocksLists_Blocks"></div>');*/
                    var instance = new RelatedBlocksLists_Manager_Js();
                    instance.registerEvents();
                }
            }
        );
    }
});

jQuery( document ).ajaxComplete(function(event, xhr, settings) {
    var url = settings.url;
    var instance = new RelatedBlocksLists_Js();
    var top_url = url.split('?');
    var array_url = instance.getQueryParams(top_url[1]);
    if(typeof array_url != 'undefined' && array_url.module == 'LayoutEditor') {
        var sourceModule = array_url.sourceModule;
        // Check enable
        var params = {};
        params.action = 'ActionAjax';
        params.module = 'RelatedBlocksLists';
        params.mode = 'checkEnable';
        app.request.post({data:params}).then(
            function (err,data) {
                if (err == null && data.enable == '1') {
                    var addCustomBlockButton = jQuery('.addCustomBlock');
                    if(addCustomBlockButton.closest('#detailViewLayout').find('.btnRelatedBlocksLists').length == 0) {
                        var relatedBlocksListsButtons = jQuery('<button style="margin-left:5px" data-url="index.php?module=RelatedBlocksLists&view=MassSettingsAjax&mode=showSettingsForm&type=block&sourceModule=' + sourceModule + '" type="button" class="btn addButton btnRelatedBlocksLists"><i class="icon-plus"></i><strong>Add Related Block</strong></button><button style="margin-left:5px" data-url="index.php?module=RelatedBlocksLists&view=MassSettingsAjax&mode=showSettingsForm&type=list&sourceModule=' + sourceModule + '" type="button" class="btn addButton btnRelatedBlocksLists"><i class="icon-plus"></i><strong>Add Related List</strong></button>');
                        addCustomBlockButton.after(relatedBlocksListsButtons);

                        // Add related block settings
                        var instance = new RelatedBlocksLists_Manager_Js();
                        instance.registerEvents();
                    }
                }
            }
        );
    }
});
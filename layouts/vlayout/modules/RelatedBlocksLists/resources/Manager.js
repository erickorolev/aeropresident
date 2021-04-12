/* ********************************************************************************
 * The content of this file is subject to the Related Blocks & Lists ("License");
 * You may not use this file except in compliance with the License
 * The Initial Developer of the Original Code is VTExperts.com
 * Portions created by VTExperts.com. are Copyright(C) VTExperts.com.
 * All Rights Reserved.
 * ****************************************************************************** */

jQuery.Class("RelatedBlocksLists_Manager_Js",{
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
        var thisInstance = this;
        var progressIndicatorElement = jQuery.progressIndicator();
        var actionParams = {
            "type":"POST",
            "url":url,
            "dataType":"html",
            "data" : {}
        };
        AppConnector.request(actionParams).then(
            function(data) {
                progressIndicatorElement.progressIndicator({'mode' : 'hide'});
                if(data) {
                    var callBackFunction = function(data) {
                        var form = jQuery('#relatedblockslists_form');
                        var params = app.validationEngineOptions;
                        params.onValidationComplete = function(form, valid){
                            if(valid) {
                                thisInstance.saveBlockDetails(form);
                                return valid;
                            }
                        };
                        form.validationEngine(params);

                        form.submit(function(e) {
                            e.preventDefault();
                        })
                    };
                    app.showModalWindow(data, function(data){
                        if(typeof callBackFunction == 'function'){
                            callBackFunction(data);
                        }
                        thisInstance.registerPopupEvents();
                    }, {'width':'600px'})
                }
            }
        );
    },

    registerPopupEvents: function () {
        var container=jQuery('#massEditContainer');
        this.registerPopupSelectModuleEvent(container);
        this.makeFieldsListSortable(container);
        this.arrangeSelectChoicesInOrder(container);
    },

    registerPopupSelectModuleEvent: function (container) {
        var thisInstance = this;
        container.on("change",'[name="select_module"]', function(e) {
            var progressIndicatorElement = jQuery.progressIndicator();
            var select_module=jQuery(this).val();
            var actionParams = {
                "type":"POST",
                "url": "index.php?module=RelatedBlocksLists&view=MassSettingsAjax&mode=getFields",
                "dataType":"html",
                "data" : {
                    "select_module" : select_module
                }
            };
            AppConnector.request(actionParams).then(
                function(data) {
                    progressIndicatorElement.progressIndicator({'mode' : 'hide'});
                    if(data) {
                        container.find('#fields').html(data);
                        // TODO Make it better with jQuery.on
                        app.changeSelectElementView(container);
                        //register all select2 Elements
                        app.showSelect2ElementView(container.find('select.select2'));
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
    saveBlockDetails : function(form) {
        var thisInstance = this;
        thisInstance.updateFieldOrder(form);

        var progressIndicatorElement = jQuery.progressIndicator({
            'position' : 'html',
            'blockInfo' : {
                'enabled' : true
            }
        });

        var data = form.serializeFormData();
        data['module'] = 'RelatedBlocksLists';
        data['action'] = 'SaveAjax';

        AppConnector.request(data).then(
            function(data) {
                if(data['success']) {
                    progressIndicatorElement.progressIndicator({'mode' : 'hide'});
                    app.hideModalWindow();
                    var params = {};
                    params.text = app.vtranslate('Block Saved');
                    Settings_Vtiger_Index_Js.showMessage(params);
                    var blockid = data.result.blockid;
                    var after_block = data.result.after_block;
                    thisInstance.loadListBlocks(blockid, after_block);
                }
            },
            function(error) {
                progressIndicatorElement.progressIndicator({'mode' : 'hide'});
                //TODO : Handle error
            }
        );
    },

    loadListBlocks: function (blockid,after_block) {
        var thisInstance = this;
        var progressIndicatorElement = jQuery.progressIndicator({
            'position' : 'html',
            'blockInfo' : {
                'enabled' : true
            }
        });
        var container = jQuery('#detailViewLayout');
        var sourceModule = jQuery('[name="layoutEditorModules"]').val();
        var actionParams = {
            "type":"POST",
            "url": "index.php?module=RelatedBlocksLists&view=MassSettingsAjax",
            "dataType":"html",
            "data" : {
                "blockid" : blockid,
                "mode" : 'getBlocks'
            }
        };
        AppConnector.request(actionParams).then(
            function(data) {
                if(data) {
                    progressIndicatorElement.progressIndicator({'mode' : 'hide'});
                    var editFieldsTable = jQuery(document).find("#moduleBlocks #block_" + after_block);
                    container.find('.relatedblock_'+blockid).remove();
                    editFieldsTable.after(data);
                    thisInstance.registerBlockEvents(container.find('.relatedblock_'+blockid));
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
            Vtiger_Helper_Js.showConfirmationBox({'message' : message}).then(
                function(e) {
                    var blockId = jQuery(element).data('block-id');
                    var params = {};
                    params['module'] = 'RelatedBlocksLists';
                    params['action'] = 'ActionAjax';
                    params['mode'] = 'deleteBlock';
                    params['blockid'] = blockId;

                    AppConnector.request(params).then(
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
        AppConnector.request(params).then(
            function(data) {
                if(data['success']) {
                    var blocks=data.result;
                    if(blocks) {
                        jQuery.each(blocks, function (blockid, content) {
                            after_block = content[0];
                            after_block_label = content[1];
                            thisInstance.loadListBlocks(blockid, after_block);
                        })
                    }
                }
            },
            function(error) {
                //TODO : Handle error
            }
        );
    },

    registerEvents: function(){
        var container = jQuery('#detailViewLayout');
        this.registerEventForRelatedBlocksListsButtons(container);
        this.loadConfiguredBlocks();
    }
});

function convertUrlToDataParams(url) {
    var params = {};
    if (typeof url !== 'undefined' && url.indexOf('?') !== -1) {
        var urlSplit = url.split('?');
        url = urlSplit[1];
    }
    var queryParameters = url.split('&');
    for (var index = 0; index < queryParameters.length; index++) {
        var queryParam = queryParameters[index];
        var queryParamComponents = queryParam.split('=');
        params[queryParamComponents[0]] = queryParamComponents[1];
    }
    return params;
}

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
        AppConnector.request(params).then(
            function (data) {
                if (data.result.enable == '1') {
                    if(sourceModule == '') {
                        sourceModule = jQuery('[name="layoutEditorModules"]').val();
                    }
                    var addCustomBlockButton = jQuery('.addCustomBlock');
                    var relatedBlocksListsButtons=jQuery('<button style="margin-left:5px" data-url="index.php?module=RelatedBlocksLists&view=MassSettingsAjax&mode=showSettingsForm&type=block&sourceModule=' + sourceModule + '" type="button" class="btn addButton btnRelatedBlocksLists"><i class="icon-plus"></i><strong>Add Related Block</strong></button><button style="margin-left:5px" data-url="index.php?module=RelatedBlocksLists&view=MassSettingsAjax&mode=showSettingsForm&type=list&sourceModule=' + sourceModule + '" type="button" class="btn addButton btnRelatedBlocksLists"><i class="icon-plus"></i><strong>Add Related List</strong></button>');
                    addCustomBlockButton.after(relatedBlocksListsButtons);

                    // Add related block settings
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
        AppConnector.request(params).then(
            function (data) {
                var addCustomBlockButton = jQuery('.addCustomBlock');
                var relatedBlocksListsButtons = jQuery('<button style="margin-left:5px" data-url="index.php?module=RelatedBlocksLists&view=MassSettingsAjax&mode=showSettingsForm&type=block&sourceModule=' + sourceModule + '" type="button" class="btn addButton btnRelatedBlocksLists"><i class="icon-plus"></i><strong>Add Related Block</strong></button><button style="margin-left:5px" data-url="index.php?module=RelatedBlocksLists&view=MassSettingsAjax&mode=showSettingsForm&type=list&sourceModule=' + sourceModule + '" type="button" class="btn addButton btnRelatedBlocksLists"><i class="icon-plus"></i><strong>Add Related List</strong></button>');
                addCustomBlockButton.after(relatedBlocksListsButtons);

                // Add related block settings
                var instance = new RelatedBlocksLists_Manager_Js();
                instance.registerEvents();
            }
        )
    }
});
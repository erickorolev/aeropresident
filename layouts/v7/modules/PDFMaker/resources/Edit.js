/*********************************************************************************
 * The content of this file is subject to the PDF Maker license.
 * ("License"); You may not use this file except in compliance with the License
 * The Initial Developer of the Original Code is IT-Solutions4You s.r.o.
 * Portions created by IT-Solutions4You s.r.o. are Copyright(C) IT-Solutions4You s.r.o.
 * All Rights Reserved.
 ********************************************************************************/
Vtiger_Edit_Js("PDFMaker_Edit_Js",{

    duplicateCheckCache : {},
    advanceFilterInstance : false,
    formElement : false,

    getForm : function(){
        if(this.formElement == false){
                this.setForm(jQuery('#EditView'));
        }
        return this.formElement;
    },
    setForm : function(element){
        this.formElement = element;
        return this;
    },    
    registerRecordPreSaveEvent : function(form){
        if(typeof form == 'undefined'){
                form = this.getForm();
        }
        form.on(Vtiger_Edit_Js.recordPreSave, function(e, data){
            var error = 0;
            if (!PDFMaker_EditJs.ControlNumber('margin_top', true) || !PDFMaker_EditJs.ControlNumber('margin_bottom', true) || !PDFMaker_EditJs.ControlNumber('margin_left', true) || !PDFMaker_EditJs.ControlNumber('margin_right', true)){
                error++;
            }
            if (!PDFMaker_EditJs.CheckCustomFormat()){
                error++;
            }

            if (error == 0){
                return true;
            } else {
                return false;
            }
            e.preventDefault();
        })
    },
    registerBasicEvents: function(container){
        this._super(container);
        this.registerButtons(container);
    },

    getSelectedEditor : function() {

        var selectedTab2 = jQuery('#ContentEditorTabs').find('.active').data('type');

        if (typeof selectedTab2 == "undefined") selectedTab2 = "body";

        if (selectedTab2 == "body")
            var oEditor = CKEDITOR.instances.body;
        else if (selectedTab2 == "header")
            var oEditor = CKEDITOR.instances.header_body;
        else if (selectedTab2 == "footer")
            var oEditor = CKEDITOR.instances.footer_body;

        return oEditor;
    },
    registerButtons: function(container) {
        var thisInstance = this;
        var selectElement1 = jQuery('.InsertIntoTemplate');
        selectElement1.on('click', function() {
            var selectedType = jQuery(this).data('type');
            thisInstance.InsertIntoTemplate(selectedType,false);
        });
        var selectElement2 = jQuery('.InsertLIntoTemplate');
        selectElement2.on('click', function() {
            var selectedType = jQuery(this).data('type');
            thisInstance.InsertIntoTemplate(selectedType,true);
        });

        var selectElement3 = jQuery('.InsertIconIntoTemplate');
        selectElement3.on('click', function() {
            var oEditor = thisInstance.getSelectedEditor();
            var selecticon = jQuery("#fontawesomeicons").val();

            if (selecticon != "") {
                selecticon = String.fromCodePoint('0x'+selecticon);
                oEditor.insertHtml('<i class="fa">' + selecticon + '</i>');
                oEditor.insertHtml(' ');
            }
        });

        var selectElement = container.find('[name="fontawesomeicons"]');
        selectElement.on('change', function(e) {

            var currentElement = jQuery(e.currentTarget);
            var selectedOption = currentElement.find('option:selected');
            var selectedClass = selectedOption.data('classname');

            var fontawesomepreview = jQuery('#fontawesomepreview');
            fontawesomepreview.removeAttr('class').attr('class', '');
            fontawesomepreview.addClass('fa');
            fontawesomepreview.addClass(selectedClass);
        })

    },
    InsertIntoTemplate: function(element,islabel){
        var thisInstance = this;

        selectField = document.getElementById(element).value;
        if (selectField == ""){
            return;
        }

        var oEditor = thisInstance.getSelectedEditor();

        if (element == "relatedmodulefields") {
            var tmpArr = selectField.split('|', 2);
            selectField = 'R_' + tmpArr[1];
        }

        if (islabel){
            oEditor.insertHtml('%' + selectField + '%');
        } else {
            if (element != 'header_var' && element != 'footer_var' && element != 'hmodulefields' && element != 'fmodulefields' && element != 'dateval'){
                if (selectField == 'ORGANIZATION_STAMP_SIGNATURE')
                    insert_value = jQuery('#div_company_stamp_signature').html();
                else if (selectField == 'ORGANIZATION_HEADER_SIGNATURE')
                    insert_value = jQuery('#div_company_header_signature').html();
                else if (selectField == 'VATBLOCK')
                    insert_value = jQuery('#div_vat_block_table').html();
                else {
                    if (element == "articelvar" || selectField == "LISTVIEWBLOCK_START" || selectField == "LISTVIEWBLOCK_END")
                        insert_value = '#' + selectField + '#';
                    else if (element == "productbloctpl" || element == "productbloctpl2")
                        insert_value = selectField;
                    else if (element == "global_lang")
                        insert_value = '%G_' + selectField + '%';
                    else if (element == "module_lang")
                        insert_value = '%M_' + selectField + '%';
                    else if (element == "custom_lang")
                        insert_value = '%' + selectField + '%';
                    else if (element == "barcodeval")
                        insert_value = '[BARCODE|' + selectField + '=YOURCODE|BARCODE]';
                    else if (element == "customfunction")
                        insert_value = '[CUSTOMFUNCTION|' + selectField + '|CUSTOMFUNCTION]';
                    else
                        insert_value = '$' + selectField + '$';
                }
                oEditor.insertHtml(insert_value);
            } else {
                if (element == 'hmodulefields' || element == 'fmodulefields'){
                    oEditor.insertHtml('$' + selectField + '$');
                } else {
                    oEditor.insertHtml(selectField);
                }
            }
        }
    },


    registerSelectWatermarkTypeOption : function(editViewForm) {
        var thisInstance = this;
        var selectElement = editViewForm.find('[name="watermark_type"]');
        selectElement.on('change', function(e) {
            var currentElement = jQuery(e.currentTarget);
            var selectedOption = currentElement.find('option:selected');
            var watermarktype = selectedOption.val();

            var watermarkImageTrElement = jQuery('#watermark_image_tr');
            if (watermarktype == "image") {
                watermarkImageTrElement.removeClass('hide');
            } else {
                watermarkImageTrElement.addClass('hide');
            }

            var watermarkTextTrElement = jQuery('#watermark_text_tr');
            if (watermarktype == "text") {
                watermarkTextTrElement.removeClass('hide');
            } else {
                watermarkTextTrElement.addClass('hide');
            }

            var watermarkAlphaTrElement = jQuery('#watermark_alpha_tr');
            if (watermarktype == "none") {
                watermarkAlphaTrElement.addClass('hide');
            } else {
                watermarkAlphaTrElement.removeClass('hide');
            }
        });

        var deleteWatermarkFileElement = editViewForm.find('.deleteWatermarkFile');
        deleteWatermarkFileElement.on('click', function() {

            editViewForm.find('[name="watermark_img_id"]').val('');

            jQuery('#uploadedWatermarkFileImage').removeClass('hide');
            jQuery('#uploadedWatermarkFileName').addClass('hide');

        });



    },

    registerSelectModuleOption : function(content) {
        var thisInstance = this;
        var selectElement = jQuery('[name="modulename"]');
        selectElement.on('change', function() {
            
            if (selected_module != '') {
                question = confirm(app.vtranslate("LBL_CHANGE_MODULE_QUESTION"));
                if (question) {
                    var oEditor = CKEDITOR.instances.body;
                    oEditor.setData("");
                    oEditor = CKEDITOR.instances.header_body;
                    oEditor.setData("");
                    oEditor = CKEDITOR.instances.footer_body;
                    oEditor.setData("");
                    jQuery("#nameOfFile").val('');
                    jQuery("#PDFPassword").val('');
                } else {
                    selectElement.val(selected_module);
                    return;
                }
            }
            var selectedOption = selectElement.find('option:selected');
            var moduleName = selectedOption.val();
            thisInstance.getFields(content,moduleName,"modulefields","");
            PDFMaker_EditJs.fill_module_lang_array(moduleName);
            PDFMaker_EditJs.fill_related_blocks_array(moduleName);
            PDFMaker_EditJs.fill_content_blocks_array(moduleName);
        });
    },
    registerSelectRelatedModuleOption : function(content, type) {
        var thisInstance = this;
        var selectElement = content.find('[name="relatedmodulesorce'+type+'"]');
        selectElement.on('change', function(e) {
            var currentElement = jQuery(e.currentTarget);
            var selectedOption = currentElement.find('option:selected');
            var moduleName = selectedOption.data('module');
            var fieldName = selectedOption.val();

            if (type == "2") {
                moduleName = jQuery('[name="relatedmodulesorce"]').find('option[value="'+fieldName+'"]').data('module');
            }

            thisInstance.getFields(content,moduleName,"relatedmodulefields"+type,fieldName);
        });		
    },
    
    getFields : function(content,moduleName,selectname,fieldName) {
        var thisInstance = this;
        var urlParams = {
            "module": "PDFMaker",
            "formodule" : moduleName,
            "forfieldname" : fieldName,
            "action" : "IndexAjax",
            "mode" : "getModuleFields"            
        }

        app.request.get({'data' : urlParams}).then(
            function(err,response) {
                thisInstance.updateFields(content,response,selectname);
            }
        );
    },
    updateFields: function(content,response,selectname){
        var thisInstance = this;
        var result = response['success'];

        if(result == true) {
            var ModuleFieldsElement = content.find('#'+selectname);
            ModuleFieldsElement.empty();

            if (selectname == "filename_fields") {
                jQuery.each(response['filename_fields'], function (i, fields) {
                    var optgroup = jQuery('<optgroup/>');
                    optgroup.attr('label',i);
                    jQuery.each(fields, function (key, field) {
                        optgroup.append(jQuery('<option>', { 
                            value: key,
                            text : field 
                        }));
                    });
                    ModuleFieldsElement.append(optgroup);
                });                   
            }

            jQuery.each(response['fields'], function (i, fields) {
                var optgroup = jQuery('<optgroup/>');
                optgroup.attr('label',i);

                jQuery.each(fields, function (key, field) {
                    optgroup.append(jQuery('<option>', { 
                        value: key,
                        text : field 
                    }));
                });
                ModuleFieldsElement.append(optgroup);
            });
            ModuleFieldsElement.select2("destroy");
            ModuleFieldsElement.select2();

            if (selectname == "modulefields") {
                var RelatedModuleSourceElement = jQuery('#relatedmodulesorce');
                RelatedModuleSourceElement.empty();
                jQuery.each(response['related_modules'], function (i, item) {

                    RelatedModuleSourceElement.append(jQuery('<option>', { 
                        value: item[3] + '|' + item[0],
                        text : item[2] + " (" + item[1] + ")"
                    }).data("module",item[3]));
                });

                RelatedModuleSourceElement.select2("destroy");
                RelatedModuleSourceElement.select2();
                RelatedModuleSourceElement.trigger('change');
                thisInstance.updateFields(content,response,"filename_fields");
            } 
        }
    },
    registerToogleShareList : function() {
        jQuery('[data-toogle-members]').on('change',function(e){
            var element = jQuery(e.currentTarget);
            if(element.val() == 'share'){
                jQuery('#memberList').addClass('fadeInx').data('rule-required',true);
            } else {
                jQuery('#memberList').removeClass('fadeInx').data('rule-required',false);
            }
        });
    },
    registerCSSStyles: function(){
        jQuery('.CodeMirrorContent').each(function(index,Element) {
            var StyleElementId = jQuery(Element).attr('id');
            CodeMirror.runMode(document.getElementById(StyleElementId).value, "css",
                document.getElementById(StyleElementId+"Output"));
        });
    },
    registerSelectBlockOption : function() {
        var thisInstance = this;

        var bodyTabElement = jQuery("#bodyDivTab");
        var bodyContentTabElement = jQuery("#body_div2");

        jQuery('.blocktypeselect').find("select").each(function(index,Element) {

            var currentElement = jQuery(Element);
            var blocktype = currentElement.data("type");
            var blocktypeElement = jQuery("#blocktype"+blocktype);
            var blocktypeTabElement = jQuery("#"+blocktype+"DivTab");

            var blocktypeElementVal = currentElement.find('option:selected').val();

            if (blocktypeElementVal != "custom") {
                blocktypeTabElement.addClass("hide");
            }

            if (blocktypeElement.find('option').length == "0"){
                currentElement.find('option[value="fromlist"]').attr('disabled','disabled');
            }

            currentElement.on('change', function(e) {

                var selectedOption = currentElement.find('option:selected').val();
                jQuery(".ContentEditorTab").removeClass("active");
                jQuery(".ContentTabPanel").removeClass("active");

                if (selectedOption == "custom") {
                    blocktypeElement.addClass("hide");
                    blocktypeTabElement.removeClass("hide");
                    blocktypeTabElement.addClass("active");
                    jQuery("#" + blocktype + "_div2").addClass("active");
                } else {
                    blocktypeElement.removeClass("hide");
                    blocktypeTabElement.addClass("hide");
                    bodyTabElement.addClass("active");
                    bodyContentTabElement.addClass("active");
                }
            });

        });
    },
    registerValidation : function () {
        var editViewForm = this.getForm();
        this.formValidatorInstance = editViewForm.vtValidate({
            submitHandler : function() {

                var e = jQuery.Event(Vtiger_Edit_Js.recordPresaveEvent);
                app.event.trigger(e);
                if(e.isDefaultPrevented()) {
                    return false;
                }
                var error = 0;

                if (!PDFMaker_EditJs.ControlNumber('margin_top', true) || !PDFMaker_EditJs.ControlNumber('margin_bottom', true) || !PDFMaker_EditJs.ControlNumber('margin_left', true) || !PDFMaker_EditJs.ControlNumber('margin_right', true)){
                    error++;
                }
                if (!PDFMaker_EditJs.CheckCustomFormat()){
                    error++;
                }

                if (error > 0){
                    return false;
                }

                window.onbeforeunload = null;
                editViewForm.find('.saveButton').attr('disabled',true);
                return true;
            }
        });
    },

    getPopUp: function (editViewForm) {
        var thisInstance = this;
        if (typeof editViewForm == 'undefined') {
            editViewForm = thisInstance.getForm();
        }

        var contentDiv = jQuery('.contents');

        var isPopupShowing = false;
        editViewForm.on('click', '.getPopupUi', function (e) {

            if(isPopupShowing) {
                return false;
            }
            var fieldValueElement = jQuery(e.currentTarget);
            var fieldValue = fieldValueElement.val();

            var clonedPopupUi = contentDiv.find('.popupUi').clone(true, true).removeClass('hide').removeClass('popupUi').addClass('clonedPopupUi');

            clonedPopupUi.find('select').addClass('select2');
            clonedPopupUi.find('.fieldValue').val(fieldValue);
            clonedPopupUi.find('.fieldValue').removeClass('hide');

            var callBackFunction = function (data) {
                isPopupShowing = false;
                data.find('.clonedPopupUi').removeClass('hide');

                var module = editViewForm.find('#modulename').val();

                jQuery.each( [ "filename_fields", "relatedmodulesorce", "relatedmodulefields" ], function( i, l ){
                    var modulefields_content = editViewForm.find('[name="'+l+'"]').html();
                    data.find('[name="'+l+'2"]').html(modulefields_content);

                    if (l == "relatedmodulesorce") {
                        var sel = editViewForm.find('[name="'+l+'"]').val();
                        data.find('[name="'+l+'2"]').val(sel).change();
                    }
                });

                thisInstance.registerSelectRelatedModuleOption(data,'2');

                var selectElement2 = data.find('.InsertIntoTextarea');
                selectElement2.on('click', function() {
                    var selectedType = jQuery(this).data('type');
                    //thisInstance.InsertIntoTemplate(selectedType,true);

                    var selectField = data.find('[name="'+selectedType+'"]').val();

                    if (selectedType == "relatedmodulefields2") {
                        var tmpArr = selectField.split('|', 2);
                        var insert_value = '$R_' + tmpArr[1] + '$';
                    } else {
                        var insert_value = '$' + selectField + '$';
                    }

                    var fieldValueVal = data.find('.fieldValue').val();
                    data.find('.fieldValue').val(fieldValueVal+insert_value);
                });
                /*
                var moduleNameElement = editViewForm.find('[name="modulename"]');
                if (moduleNameElement.length > 0) {
                    var moduleName = moduleNameElement.val();
                    data.find('.useFieldElement').addClass('hide');
                    jQuery(data.find('[name="' + moduleName + '"]').get(0)).removeClass('hide');
                }*/
                thisInstance.registerPopUpSaveEvent(data, fieldValueElement);
                thisInstance.registerRemoveModalEvent(data);
                data.find('.fieldValue').filter(':visible').trigger('focus');
            }

            contentDiv.find('.clonedPopUp').html(clonedPopupUi);
            jQuery('.clonedPopupUi').on('shown', function () {
                if (typeof callBackFunction == 'function') {
                    callBackFunction(jQuery('.clonedPopupUi', contentDiv));
                }
            });
            isPopupShowing = true;



            app.helper.showModal(jQuery('.clonedPopUp', contentDiv).find('.clonedPopupUi'), {cb: callBackFunction});

        });
    },

    registerRemoveModalEvent: function (data) {
        data.on('click', '.closeModal', function (e) {
            data.modal('hide');
        });
    },
    registerPopUpSaveEvent: function (data, fieldValueElement) {
        jQuery('[name="saveButton"]', data).on('click', function (e) {
            var fieldValue = data.find('.fieldValue').filter(':visible').val();
            fieldValueElement.val(fieldValue);
            data.modal('hide');
        });
    },

    registerEvents: function(){
        var editViewForm = this.getForm();
        var statusToProceed = this.proceedRegisterEvents();
        if(!statusToProceed){
                return;
        }
        this.registerBasicEvents(this.getForm());
        this.registerSelectModuleOption(editViewForm);
        this.registerSelectWatermarkTypeOption(editViewForm);
        this.registerSelectBlockOption();
        this.registerSelectRelatedModuleOption(editViewForm,'');
        this.registerValidation();
        this.registerToogleShareList();
        this.registerCSSStyles();

        this.getPopUp(editViewForm);


        if (typeof this.registerLeavePageWithoutSubmit == 'function'){
            this.registerLeavePageWithoutSubmit(editViewForm);
        }             
    }
});
if (typeof(PDFMaker_EditJs) == 'undefined'){
    PDFMaker_EditJs = {
        reportsColumnsList : false,
        advanceFilterInstance : false,
        availListObj : false,
        selectedColumnsObj : false,
    
        clearRelatedModuleFields: function(){
            second = document.getElementById("relatedmodulefields");
            lgth = second.options.length - 1;
            second.options[lgth] = null;
            if (second.options[lgth])
                optionTest = false;
            if (!optionTest)
                return;
            var box2 = second;
            var optgroups = box2.childNodes;
            for (i = optgroups.length - 1; i >= 0; i--){
                box2.removeChild(optgroups[i]);
            }

            objOption = document.createElement("option");
            objOption.innerHTML = app.vtranslate("LBL_SELECT_MODULE_FIELD");
            objOption.value = "";
            box2.appendChild(objOption);
        },
        change_relatedmodulesorce: function(first, second_name){
            second = document.getElementById(second_name);
            optionTest = true;
            lgth = second.options.length - 1;
            second.options[lgth] = null;
            if (second.options[lgth])
                optionTest = false;
            if (!optionTest)
                return;
            var box = first;
            var number = box.options[box.selectedIndex].value;
            if (!number)
                return;
            
            var params = {
                            module : app.getModuleName(),
                            view : 'IndexAjax',
                            source_module : number,
                            mode : 'getModuleConditions'
            }
            var actionParams = {
                "type": "POST",
                "url": 'index.php',
                "dataType": "html",
                "data": params
            };

            var box2 = second;
            var optgroups = box2.childNodes;
            for (i = optgroups.length - 1; i >= 0; i--){
                box2.removeChild(optgroups[i]);
            }

            var list = all_related_modules[number];
            for (i = 0; i < list.length; i += 2){
                objOption = document.createElement("option");
                objOption.innerHTML = list[i];
                objOption.value = list[i + 1];
                box2.appendChild(objOption);
            }

            PDFMaker_EditJs.clearRelatedModuleFields();
        },
        change_relatedmodule: function(first, second_name){
            second = document.getElementById(second_name);
            optionTest = true;
            lgth = second.options.length - 1;
            second.options[lgth] = null;
            if (second.options[lgth])
                optionTest = false;
            if (!optionTest)
                return;
            var box = first;
            var number = box.options[box.selectedIndex].value;
            if (!number)
                return;
            var box2 = second;
            var optgroups = box2.childNodes;
            for (i = optgroups.length - 1; i >= 0; i--){
                box2.removeChild(optgroups[i]);
            }

            if (number == "none"){
                objOption = document.createElement("option");
                objOption.innerHTML = app.vtranslate("LBL_SELECT_MODULE_FIELD");
                objOption.value = "";
                box2.appendChild(objOption);
            } else {
                var tmpArr = number.split('|', 2);
                var moduleName = tmpArr[0];
                number = tmpArr[1];
                var blocks = module_blocks[moduleName];
                for (b = 0; b < blocks.length; b += 2){
                    var list = related_module_fields[moduleName + '|' + blocks[b + 1]];
                    if (list.length > 0){
                        optGroup = document.createElement('optgroup');
                        optGroup.label = blocks[b];
                        box2.appendChild(optGroup);
                        for (i = 0; i < list.length; i += 2){
                            objOption = document.createElement("option");
                            objOption.innerHTML = list[i];
                            var objVal = list[i + 1];
                            var newObjVal = objVal.replace(moduleName.toUpperCase() + '_', number.toUpperCase() + '_');
                            objOption.value = newObjVal;
                            optGroup.appendChild(objOption);
                        }
                    }
                }
            }
        },
        change_acc_info: function(element){            
            jQuery('.au_info_div').css('display','none');            
            switch (element.value){
                case "Assigned":
                    var div_name = 'user_info_div';
                    break;
                case "Logged":
                    var div_name = 'logged_user_info_div';
                    break;
                case "Modifiedby":
                    var div_name = 'modifiedby_user_info_div';
                    break; 
                case "Creator":
                    var div_name = 'smcreator_user_info_div';
                    break; 
                default:
                    var div_name = 'user_info_div';
                    break;
            }            
            jQuery('#'+div_name).css('display','inline');
        },
        ControlNumber: function(elid, final){
            var control_number = document.getElementById(elid).value;
            var re = new Array();
            re[1] = new RegExp("^([0-9])");
            re[2] = new RegExp("^[0-9]{1}[.]$");
            re[3] = new RegExp("^[0-9]{1}[.][0-9]{1}$");
            if (control_number.length > 3 || !re[control_number.length].test(control_number) || (final == true && control_number.length == 2)){
                alert(app.vtranslate("LBL_MARGIN_ERROR"));
                document.getElementById(elid).focus();
                return false;
            } else {
                return true;
            }
        },
        showHideTab3: function(tabname){
            document.getElementById(tabname + '_tab2').className = 'active';
            if (tabname == 'body'){
                document.getElementById('body_variables').style.display = '';
                document.getElementById('related_block_tpl_row').style.display = '';
                document.getElementById('listview_block_tpl_row').style.display = '';
            } else {
                document.getElementById('header_variables').style.display = '';
                document.getElementById('body_variables').style.display = 'none';
                document.getElementById('related_block_tpl_row').style.display = 'none';
                document.getElementById('listview_block_tpl_row').style.display = 'none';
            }


            document.getElementById(tabname + '_div2').style.display = 'block';
            box = document.getElementById('modulename')
            var module = box.options[box.selectedIndex].value;

        },
        fill_module_lang_array: function(module, selected){
            
            var urlParams = {
                "module" : "PDFMaker",
                "handler" : "fill_lang",
                "action" : "AjaxRequestHandle",
                "langmod" : module            
            }

            app.request.get({'data' : urlParams}).then(
                function(err,response) {
                    var result = response['success'];

                    if(result == true) {
                        var moduleLangElement = jQuery('#module_lang');

                        moduleLangElement.empty();

                        jQuery.each(response['labels'], function (key, langlabel) {

                             moduleLangElement.append(jQuery('<option>', {
                                        value: key,
                                        text : langlabel
                            }));
                        })
                    }
            })
        },
        fill_related_blocks_array: function(module, selected){
            var urlParams = {
                "module" : "PDFMaker",
                "handler" : "fill_relblocks",
                "action" : "AjaxRequestHandle",
                "selmod" : module            
            }

            app.request.get({'data' : urlParams}).then(
                function(err,response) {
                var result = response['success'];

                if(result == true) {    
                    var relatedBlockElement = jQuery('#related_block');
                    relatedBlockElement.empty();

                    jQuery.each(response['relblocks'], function (key, blockname) {
     
                        if (selected != undefined && key == selected) {
                            var is_selected = true;
                        } else {
                            var is_selected = false;
                        }
                        relatedBlockElement.append(jQuery('<option>', { 
                                    value: key,
                                    text : blockname
                        }).attr("selected",is_selected));
                    })
                }
            })
        },
        refresh_related_blocks_array: function(selected){
            var module = document.getElementById('modulename').value;
            PDFMaker_EditJs.fill_related_blocks_array(module, selected);
        },

        fill_block_list: function(type, data){

            var blockListElement = jQuery('#blocktype'+type+'_list');
            var selected = blockListElement.find('option:selected').val();

            if (typeof selected == 'undefined') selected = '';

            blockListElement.empty();

            var fromListElementVal = jQuery('#blocktype'+type+'_val').find('option[value="fromlist"]');

            var count = 0;
            jQuery.each(data, function(i,v) { count++; });

            if (count > 0) {

                jQuery.each(data, function (key, blockname) {
                    if (key == selected) {
                        var is_selected = true;
                    } else {
                        var is_selected = false;
                    }

                    blockListElement.append(jQuery('<option>', {
                        value: key,
                        text : blockname
                    }).attr("selected",is_selected));
                })
                fromListElementVal.removeAttr('disabled');
            } else {
                fromListElementVal.attr('disabled','disabled');
                jQuery('#blocktype'+type).addClass('hide');
                jQuery('#'+type+'DivTab').removeClass('hide');
            }

        },

        fill_content_blocks_array: function(module){
            var thisInstance = this;

            var urlParams = {
                "module" : "PDFMaker",
                "mode" : "fillContentBlockLists",
                "action" : "IndexAjax",
                "selmod" : module
            }

            app.request.get({'data' : urlParams}).then(
                function(err,response) {
                    var result = response['success'];
                    if(result == true) {
                        thisInstance.fill_block_list('header',response['header']);
                        thisInstance.fill_block_list('footer',response['footer']);
                    }
                })
        },



        InsertRelatedBlock: function(){
            var relblockid = document.getElementById('related_block').value;
            if (relblockid == '')
                return false;
            var oEditor = CKEDITOR.instances.body;
            var ajax_url = 'index.php?module=PDFMaker&action=AjaxRequestHandle&handler=get_relblock&relblockid=' + relblockid;
            jQuery.ajax(ajax_url).success(function(response){
                oEditor.insertHtml(response);
            }).error(function(){
            });
        },
        EditRelatedBlock: function(){
            var relblockid = document.getElementById('related_block').value;
            if (relblockid == ''){
                alert(app.vtranslate('LBL_SELECT_RELBLOCK'));
                return false;
            }

            var popup_url = 'index.php?module=PDFMaker&view=EditRelatedBlock&record=' + relblockid;
            window.open(popup_url, "Editblock", "width=1230,height=700,scrollbars=yes");
        },
        CreateRelatedBlock: function(){
            var pdf_module = document.getElementById("modulename").value;
            if (pdf_module == ''){
                alert(app.vtranslate("LBL_MODULE_ERROR"));
                return false;
            }
            var popup_url = 'index.php?module=PDFMaker&view=EditRelatedBlock&pdfmodule=' + pdf_module;
            window.open(popup_url, "Editblock", "width=1230,height=700,scrollbars=yes");
        },
        DeleteRelatedBlock: function(){
            var relblockid = document.getElementById('related_block').value;
            var result = false;
            if (relblockid == ''){
                alert(app.vtranslate('LBL_SELECT_RELBLOCK'));
                return false;
            } else {
                var message = app.vtranslate('LBL_DELETE_RELBLOCK_CONFIRM') + " " + jQuery("#related_block option:selected").text();

                app.helper.showConfirmationBox({'message': message}).then(function (e) {
                    var params = {
                        "module": "PDFMaker",
                        "action" : "AjaxRequestHandle",
                        "handler" : "delete_relblock",
                        "relblockid" : relblockid
                    };
                    app.helper.showProgress();

                    app.request.get({'data' : params}).then(
                        function(err,response) {
                            app.helper.hideProgress();
                            if(err === null){
                                PDFMaker_EditJs.refresh_related_blocks_array();
                            }
                        }
                    );
                });
            }
        },
        insertFieldIntoFilename: function(val){
            if (val != '')
                document.getElementById('nameOfFile').value += '$' + val + '$';
        },
        CustomFormat: function(){
            var selObj;
            selObj = document.getElementById('pdf_format');

            if (selObj.value == 'Custom'){
                document.getElementById('custom_format_table').style.display = 'table';
            } else {
                document.getElementById('custom_format_table').style.display = 'none';
            }
        },
        ConfirmIsPortal: function(oCheck){
            var module = document.getElementById('modulename').value;
            var curr_templatename = document.getElementById('filename').value;

            if (oCheck.defaultChecked == true && oCheck.checked == false){
                return confirm(app.vtranslate('LBL_UNSET_PORTAL') + '\n' + app.vtranslate('ARE_YOU_SURE'));
            } else if (oCheck.defaultChecked == false && oCheck.checked == true){
                var ajax_url = 'index.php?module=PDFMaker&action=AjaxRequestHandle&handler=confirm_portal&langmod=' + module + '&curr_templatename=' + curr_templatename;
                app.request.post({'url':ajax_url}).then(
                    function(err,response) {
                        app.helper.hideProgress();
                        if(err === null){
                            if (confirm(response + '\n' + app.vtranslate('ARE_YOU_SURE')) == false)
                                oCheck.checked = false;
                        }
                    }
                );

                return true;
            }
        },
        isLvTmplClicked: function(source){
            var oTrigger = document.getElementById('isListViewTmpl');
            var oButt = jQuery("#listviewblocktpl_butt");
            var oDlvChbx = document.getElementById('is_default_dv');

            var listViewblockTPLElement = jQuery("#listviewblocktpl");

            listViewblockTPLElement.attr("disabled",!(oTrigger.checked));
            oButt.attr("disabled",!(oTrigger.checked));

            if (source != 'init'){
                oDlvChbx.checked = false;
            }
            
            oDlvChbx.disabled = oTrigger.checked;
        },
        hf_checkboxes_changed: function(oChck, oType){
            var prefix;
            var optionsArr;
            if (oType == 'header'){
                prefix = 'dh_';
                optionsArr = new Array('allid', 'firstid', 'otherid');
            } else {
                prefix = 'df_';
                optionsArr = new Array('allid', 'firstid', 'otherid', 'lastid');
            }

            var tmpArr = oChck.id.split("_");
            var sufix = tmpArr[1];
            var i;
            if (sufix == 'allid'){
                for (i = 0; i < optionsArr.length; i++){
                    document.getElementById(prefix + optionsArr[i]).checked = oChck.checked;
                }
            } else {
                var allChck = document.getElementById(prefix + 'allid');
                var allChecked = true;
                for (i = 1; i < optionsArr.length; i++){
                    if (document.getElementById(prefix + optionsArr[i]).checked == false){
                        allChecked = false;
                        break;
                    }
                }
                allChck.checked = allChecked;
            }
        },
        templateActiveChanged: function(activeElm){
            var is_defaultElm1 = document.getElementById('is_default_dv');
            var is_defaultElm2 = document.getElementById('is_default_lv');

            if (activeElm.value == '1'){
                is_defaultElm1.disabled = false;
                is_defaultElm2.disabled = false;
            } else {
                is_defaultElm1.checked = false;
                is_defaultElm1.disabled = true;
                is_defaultElm2.checked = false;
                is_defaultElm2.disabled = true;
            }
        },
        CheckCustomFormat: function(){
            if (document.getElementById('pdf_format').value == 'Custom'){
                var pdfWidth = document.getElementById('pdf_format_width').value;
                var pdfHeight = document.getElementById('pdf_format_height').value;
                if (pdfWidth > 2000 || pdfHeight > 2000 || pdfWidth < 1 || pdfHeight < 1 || isNaN(pdfWidth) || isNaN(pdfHeight)){
                    alert(app.vtranslate('LBL_CUSTOM_FORMAT_ERROR'));
                    document.getElementById('pdf_format_width').focus();
                    return false;
                }
            }
            return true;
        },
        ChangeBlockType: function(blocktype){

        }
    }
}
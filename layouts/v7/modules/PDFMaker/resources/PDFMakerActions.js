/*********************************************************************************
 * The content of this file is subject to the PDF Maker license.
 * ("License"); You may not use this file except in compliance with the License
 * The Initial Developer of the Original Code is IT-Solutions4You s.r.o.
 * Portions created by IT-Solutions4You s.r.o. are Copyright(C) IT-Solutions4You s.r.o.
 * All Rights Reserved.
 ********************************************************************************/
jQuery.Class("PDFMaker_Actions_Js",{
    templatesElements : {},
    controlModal : function(container) {
        var aDeferred = jQuery.Deferred();
        if (container.find('.modal-content').length > 0) {
            app.helper.hideModal().then(
                function () {
                    aDeferred.resolve();
                }
            );
        } else {
            aDeferred.resolve();
        }
        return aDeferred.promise();
    },
    getSelectedTemplates : function(container) {
        var selectElement = jQuery('[name="use_common_template"]', container);
        var select2Element = jQuery('#s2id_use_common_template', container);

        var selectedValuesByOrder = [];
        var selectedOptions = selectElement.find('option:selected');
        var orderedSelect2Options = select2Element.find('li.select2-search-choice').find('div');
        orderedSelect2Options.each(function(index,element){
            var chosenOption = jQuery(element);
            selectedOptions.each(function(optionIndex, domOption){
                var option = jQuery(domOption);
                if(option.html() == chosenOption.html()) {
                    selectedValuesByOrder.push(jQuery.trim(option.val()));
                    return false;
                }
            });
        });
        var rowValues = '';
        if(selectedValuesByOrder.length > 0){
            rowValues = selectedValuesByOrder.join(';');
        }
        return rowValues;
    },
    registerCreateDocumentEvent : function(container) {
        var self = this;
        jQuery('#js-create-document', container).on('click', function() {
            var form = container.find('form');
            if(form.valid()) {
                self._createDocument(form);
            }
        });
    },
    _createDocument : function(form) {
        var self = this;
        var formData = form.serializeFormData();
        app.helper.showProgress();
        var moreParams = self.getMoreParams();
        var data = jQuery.extend(formData, moreParams);

        app.request.post({'data':data}).then(function(e,res) {
            app.helper.hideProgress();
            if (e === null) {
                app.helper.hideModal();
                app.helper.showSuccessNotification({
                    'message' : res.message
                });
                var folderid = form.find('[name="folderid"]').val();
                app.event.trigger('post.documents.save', {'folderid' : folderid});

                var forview_val = app.view();
                if (forview_val == 'Detail') {
                    var relatedController = self.getRelatedController('Documents');
                    if (relatedController) {
                            relatedController.loadRelatedList();
                    }
                }
            } else {
                app.event.trigger('post.save.failed', e);
            }

        });
    },
    getRelatedModuleName : function() {
        return jQuery('.relatedModuleName').val();
    },
    getRelatedController : function(forRelatedModuleName) {
        var self = this;
        var recordId = app.getRecordId();
        var moduleName = app.getModuleName();
        var selectedTabElement = self.getSelectedTab();
        var relatedModuleName = self.getRelatedModuleName();
        var relatedListClass = 'Vtiger_RelatedList_Js';
        if(typeof window[relatedListClass] != 'undefined'){
            var relatedController = Vtiger_RelatedList_Js.getInstance(recordId, moduleName, selectedTabElement, relatedModuleName);

            var AllTabs = self.getAllTabs();
            AllTabs.each(function () {
                var TabElement = jQuery(this);
                if (TabElement.data('module') == 'Documents') {
                    relatedController.updateRelatedRecordsCount(TabElement.data('relation-id'));
                }
            });

            if (relatedModuleName == forRelatedModuleName) {
                return relatedController;
            }
        }
        return null;
    },
    getSelectedTab : function() {
        var tabContainer = this.getTabContainer();
        return tabContainer.find('li.active');
    },
    getAllTabs : function() {
        var tabContainer = this.getTabContainer();
        return tabContainer.find('li');
    },
    getTabContainer : function() {
        return jQuery('div.related-tabs');
    },
    savePDFToDoc: function (templateids,pdflanguage) {
        var self = this;
        var recordId = app.getRecordId();
        var params = self.getDefaultParams('DocSelect',templateids,pdflanguage);

        params["return_module"]  = app.getModuleName();
        params["return_id"]  = recordId;

        app.helper.showProgress();
        app.request.get({data:params}).then(function(err,response){

                var callback = function(container) {
                    self.registerCreateDocumentEvent(container);
                };
                var data = {};
                data['cb'] = callback;
                app.helper.hideProgress();
                app.helper.showModal(response,data);
        });
    },
    getPDFSelectLanguage: function(container) {
        return container.find('#template_language').val();
    },
    getMoreParams: function() {
        var forview_val = app.view();

        if (forview_val == 'Detail') {
                var params = {
                    record : app.getRecordId()
                };

        } else if (forview_val == 'List') {
            var listInstance = window.app.controller();

            var selectedRecordCount = listInstance.getSelectedRecordCount();
            if (selectedRecordCount > 500) {
                app.helper.showErrorNotification({message: app.vtranslate('JS_MASS_EDIT_LIMIT')});
                return;
            }
            params = listInstance.getListSelectAllParams(true);
        }
        return params;
    },
    getDefaultParams: function(viewtype,templateids,pdflanguage) {

        var forview_val = app.view();

        var params = {
            module: 'PDFMaker',
            source_module : app.getModuleName(),
            formodule : app.getModuleName(),
            forview: forview_val,
        };

        if (viewtype != '') {
            params['view'] = viewtype;
        }
        if (templateids != '') {
            params['pdftemplateid'] = templateids;
        }

        if (pdflanguage != '') {
            params['language'] = pdflanguage;
        }
		
		if ((app.getModuleName()=="Potentials")&&((jQuery('.relatedModuleName').val()=="Quotes")||(jQuery('.relatedModuleName').val()=="SalesOrder")))
		{
			let selected = [];
			var count=0;
			
			$("#listview-table tr").each(function( index ) {
				if ($(this).find(".pdfExport").prop('checked'))
				{
					selected[count]=$(this).attr("data-id");
					count++;
				}
			});
			
			if (count>0)
			{					
				params['selected_ids'] =selected;
				params['formodule'] = jQuery('.relatedModuleName').val();
				params['forview'] = "List";
				params['source_module'] = jQuery('.relatedModuleName').val();
			}
		}
		
        var moreParams = this.getMoreParams();
        jQuery.extend(params, moreParams);

        return params;
    },
    sendEmailPDF: function(templateids,pdflanguage) {

            var params = this.getDefaultParams('SendEmail',templateids,pdflanguage);
            params['mode'] = 'composeMailData';

            var recordId = app.getRecordId();
            params['record'] = recordId;
            app.helper.showProgress();

            app.request.get({data:params}).then(function(err,response){

                var callback = function() {
                    var emailEditInstance = new Emails_MassEdit_Js();
                    emailEditInstance.registerEvents();
                };
                var data = {};
                data['cb'] = callback;
                app.helper.hideProgress();
                app.helper.showModal(response,data);
            });
    },
    applyEditor : function(elementid, container) {
        var element = container.find("#" + elementid);
        this.templatesElements[elementid] = new Vtiger_CkEditor_Js();

        var new_height = this.getModalNewHeight(container);

        this.templatesElements[elementid].loadCkEditor(element, {'height' : (new_height - 230)});
    },
    registerCKEditor : function(container){
        var self = this;

        var templateids =  container.find('[name="commontemplateid"]').val();

        var templateidsarray = templateids.split(';');
        for(index=0; index < templateidsarray.length; index++) {
            var templateid = templateidsarray[index];
            self.applyEditor('body' + templateid , container);
            self.applyEditor('header' + templateid , container);
            self.applyEditor('footer' + templateid , container);
        }
    },
    editPDF: function(templateids,pdflanguage) {

        var self = this;
        var params = this.getDefaultParams('IndexAjax',templateids,pdflanguage);
        params['mode'] = 'EditAndExport';
        app.helper.showProgress();
        app.request.get({data:params}).then(function(err,response){

            app.helper.hideProgress();
            app.helper.showModal(response, {
                'cb' : function(modalContainer) {
                    self.registerCKEditor(modalContainer);
                    self.setMaxModalHeight(modalContainer,'CKEditors');

                    modalContainer.find('.downloadButton').on('click', function(){
                        var form = modalContainer.find('#editPDFForm');
                        form.submit();
                    });


                }
            });
        });
    },
    getModalNewHeight: function (modalContainer){
        return jQuery(window).height() - modalContainer.find('.modal-header').height() - modalContainer.find('.modal-footer').height() - 100;
    },
    setMaxModalHeight : function (modalContainer,modaltype){

            var new_height = this.getModalNewHeight(modalContainer);

        var params1 = {
            setHeight:new_height+'px'
        };

        app.helper.showVerticalScroll(modalContainer.find('.modal-body'), params1);

        if (modaltype == 'iframe'){
            var params2 = {
                setHeight:(new_height-35)+'px'
            };
            app.helper.showVerticalScroll(modalContainer.find(modaltype), params2);
        }
    },
    checkIfAny: function (modalContainer){

        var j = 0;
        var LineItemCheckboxes = modalContainer.find('.LineItemCheckbox');
        jQuery.each(LineItemCheckboxes,function(i,e) {
            if (jQuery(e).is(":checked")) {
                j++;
            }
        });
        var settingscheckboxes_el = modalContainer.find('.settingsCheckbox');
        if (j == 0){
            settingscheckboxes_el.removeAttr('checked');
            settingscheckboxes_el.attr( "disabled" ,"disabled" );
        } else {
            settingscheckboxes_el.removeAttr('disabled');
        }

    },
    showPDFMakerModal : function (modetype) {
        var self = this;
        var params = {
            module: 'PDFMaker',
            return_id:  app.getRecordId(),
            view: 'IndexAjax',
            mode: modetype
        };

        app.helper.showProgress();
        app.request.get({data:params}).then(function(err,response){

            app.helper.hideProgress();
            app.helper.showModal(response, {
                'cb' : function(modalContainer) {
                    if (modetype == "PDFBreakline") {
                        modalContainer.find('.LineItemCheckbox').on('click', function(){
                            self.checkIfAny(modalContainer);
                        });
                    }

                    modalContainer.find('#js-save-button').on('click', function(){
                        PDFMaker_Actions_Js.savePDFMakerModal(modalContainer, modetype);
                    });
                }
            });
        });

    },
    savePDFMakerModal: function (modalContainer,modetype) {
        var form = modalContainer.find('#Save' + modetype + 'Form');
        var params = form.serializeFormData();
        app.helper.hideModal();
        app.helper.showProgress();

        app.request.post({"data":params}).then(function (err) {
            if (err == null) {
                app.helper.hideProgress();
                app.helper.showSuccessNotification({"message":''});
            } else {
                app.helper.showErrorNotification({"message":''});
            }
        });
    },
	 getPDFListViewPopup2v2: function (e,source_module) {
        this.showPDFTemplatesSelectModalV2(source_module);
    },
    getPDFListViewPopup2: function (e,source_module) {
        this.showPDFTemplatesSelectModal();
    },
    controlPDFSelectInput : function(container,element) {
        var fieldVal = element.val();
        if (fieldVal === null) {
            container.find('.btn-success').attr('disabled', 'disabled');
            container.find('.PDFMakerTemplateAction').hide();
        } else {
            container.find('.btn-success').removeAttr('disabled');
            container.find('.PDFMakerTemplateAction').show();
        }
    },
    registerPDFSelectInput : function(container) {
        var self = this;

        TemplateElement = jQuery("#use_common_template",container);
        self.controlPDFSelectInput(container,TemplateElement);

        TemplateElement.change(function(){
            var e = jQuery(this);
            self.controlPDFSelectInput(container,e);
        });
    },
	showPDFTemplatesSelectModalV2: function (source_module){

		var self = this;
        var view = app.view();
		
		var params = this.getDefaultParams('IndexAjax');
		params['mode'] = 'PDFTemplatesSelect';	

        app.helper.showProgress();
        app.request.get({data:params}).then(function(err,response){
            var callback = function(container) {
                var TemplateElement = container.find('#use_common_template');
                vtUtils.showSelect2ElementView(TemplateElement);

                var TemplateLanguageElement = container.find('#template_language');
                if (TemplateLanguageElement.attr('type') != 'hidden') {
                    TemplateLanguageElement.select2();
                }
                self.controlPDFSelectInput(container,TemplateElement);
                self.registerPDFActionsButtons(container);
                self.registerPDFSelectInput(container);
            };
            var data = {};
            data['cb'] = callback;
            app.helper.hideProgress();
            app.helper.showModal(response,data);
        });
    },
    showPDFTemplatesSelectModal: function (){
        var self = this;
        var view = app.view();

        var params = this.getDefaultParams('IndexAjax');
        params['mode'] = 'PDFTemplatesSelect';

		app.helper.showProgress();
        app.request.get({data:params}).then(function(err,response){
            var callback = function(container) {
                var TemplateElement = container.find('#use_common_template');
                vtUtils.showSelect2ElementView(TemplateElement);

                var TemplateLanguageElement = container.find('#template_language');
                if (TemplateLanguageElement.attr('type') != 'hidden') {
                    TemplateLanguageElement.select2();
                }
                self.controlPDFSelectInput(container,TemplateElement);
                self.registerPDFActionsButtons(container);
                self.registerPDFSelectInput(container);
            };
            var data = {};
            data['cb'] = callback;
            app.helper.hideProgress();
            app.helper.showModal(response,data);
        });
    },
    showPDFPreviewModal: function (templateids, pdflanguage) {
        var self = this;
        var view = app.view();

        var params2 = this.getDefaultParams('IndexAjax',templateids, pdflanguage);
        params2['mode'] = 'getPreview';

        app.helper.showProgress();
		
        app.request.get({data: params2}).then(function(err, data) {

            app.helper.showModal(data, {
                'cb' : function(modalContainer) {
                    modalContainer.find('#use_common_template').select2();
                    self.registerPDFPreviewActionsButtons(modalContainer,templateids,pdflanguage);
                    self.setMaxModalHeight(modalContainer,'iframe');
                }
            });

            app.helper.hideProgress();
        });
    },
    registerPDFPreviewActionsButtons: function (modalContainer,templateids,pdflanguage){

        var self = this;

        modalContainer.find('.downloadButton').on('click', function(e){
            window.location.href = jQuery(e.currentTarget).data('desc');
        });

        modalContainer.find('.printButton').on('click', function(){
            var PDF = document.getElementById("PDFMakerPreviewContent");
            PDF.focus();
            PDF.contentWindow.print();
        });

        modalContainer.find('.sendEmailWithPDF').on('click', function(e){
                app.helper.hideModal().then(function() {
                    var email_function = jQuery(e.currentTarget).data('sendtype');
                    if( email_function == 'EMAILMaker') {
                        EMAILMaker_Actions_Js.emailmaker_sendMail(templateids,pdflanguage);
                    } else {
                        self.sendEmailPDF(templateids, pdflanguage);
                    }
                });
        });

        modalContainer.find('.editPDF').on('click', function(){
                app.helper.hideModal().then(function() {
                        self.editPDF(templateids,pdflanguage);
                });
        });

        modalContainer.find('.savePDFToDoc').on('click', function(){
                app.helper.hideModal().then(function() {
                    self.savePDFToDoc(templateids,pdflanguage);
                });
        });
    },

    registerPDFActionsButtons: function (container){

        var self = this;

        container.find('.PDFMakerDownloadPDF').on('click', function(){
            var templateids = self.getSelectedTemplates(container);
            var pdflanguage = self.getPDFSelectLanguage(container);

            var params = self.getDefaultParams('',templateids,pdflanguage);
            params["action"]  = 'CreatePDFFromTemplate'; 
			console.log(params);
			
            var paramsUrl = jQuery.param(params);

            window.location.href = "index.php?" + paramsUrl;
        });

        container.find('.PDFModalPreview').on('click', function(){
            var templateids = self.getSelectedTemplates(container);
            var pdflanguage = self.getPDFSelectLanguage(container);
            self.controlModal(container).then(function() {
                self.showPDFPreviewModal(templateids, pdflanguage);
            });
        });

        container.find('.exportListPDF').on('click', function(){
            var form = container.find('#exportListPDFMakerForm');
            form.submit();
        });

        container.find('.sendEmailWithPDF').on('click', function(e){
            var templateids = self.getSelectedTemplates(container);
            var pdflanguage = self.getPDFSelectLanguage(container);
            self.controlModal(container).then(function() {
                var email_function = jQuery(e.currentTarget).data('sendtype');
                if( email_function == 'EMAILMaker') {
                    EMAILMaker_Actions_Js.emailmaker_sendMail(templateids,pdflanguage);
                } else {
                    self.sendEmailPDF(templateids, pdflanguage);
                }
            });
        });

        container.find('.editPDF').on('click', function(){
            var templateids = self.getSelectedTemplates(container);
            var pdflanguage = self.getPDFSelectLanguage(container);
            self.controlModal(container).then(function() {
                self.editPDF(templateids,pdflanguage);
            });
        });

        container.find('.savePDFToDoc').on('click', function(){
            var templateids = self.getSelectedTemplates(container);
            var pdflanguage = self.getPDFSelectLanguage(container);
            self.controlModal(container).then(function() {
                self.savePDFToDoc(templateids,pdflanguage);
            });
        });

        container.find('.showPDFBreakline').on('click', function(){
            self.showPDFMakerModal('PDFBreakline');
        });

        container.find('.showProductImages').on('click', function(){
            self.showPDFMakerModal('ProductImages');
        });

    }

},{

    registerEvents: function (){
        var self = this;
        var recordId = app.getRecordId();
        var view = app.view();

        var params = {
            module: 'PDFMaker',
            source_module : app.getModuleName(),
            view : 'GetPDFActions',
            record: recordId,
            mode : 'getButtons'
        };

        var detailViewButtonContainerDiv = jQuery('.detailview-header');

        if (detailViewButtonContainerDiv.length > 0) {

            app.request.get({'data' : params}).then(
                function(err,response) {

                    if(err === null){
                        if (response != ""){
                            detailViewButtonContainerDiv.append(response);
                            //detailViewButtonContainerDiv.find('#use_common_template').select2();
                            vtUtils.showSelect2ElementView(detailViewButtonContainerDiv.find('#use_common_template'));

                            var TemplateLanguageElement = detailViewButtonContainerDiv.find('#template_language');
                            if (TemplateLanguageElement.attr('type') != 'hidden') {
                                TemplateLanguageElement.select2();
                            }

                            if (view == 'Detail'){
                                var pdfmakercontent = detailViewButtonContainerDiv.find('#PDFMakerContentDiv');
                                PDFMaker_Actions_Js.registerPDFActionsButtons(pdfmakercontent);
                                PDFMaker_Actions_Js.registerPDFSelectInput(pdfmakercontent);
                            }

                            detailViewButtonContainerDiv.find('.selectPDFTemplates').on('click', function(){
                                PDFMaker_Actions_Js.showPDFTemplatesSelectModal();
                            });
                        }
                    }
                }
            );
        }
    }
});

jQuery(document).ready(function(){
	var instance = new PDFMaker_Actions_Js();
	instance.registerEvents();
});


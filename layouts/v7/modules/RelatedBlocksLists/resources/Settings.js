/* ********************************************************************************
 * The content of this file is subject to the Related Blocks & Lists ("License");
 * You may not use this file except in compliance with the License
 * The Initial Developer of the Original Code is VTExperts.com
 * Portions created by VTExperts.com. are Copyright(C) VTExperts.com.
 * All Rights Reserved.
 * ****************************************************************************** */
Vtiger_Index_Js("Settings_RelatedBlocksLists_Settings_Js",{
    instance:false,
    getInstance: function(){
        if(RelatedBlocksLists_Settings_Js.instance == false){
            var instance = new RelatedBlocksLists_Settings_Js();
            RelatedBlocksLists_Settings_Js.instance = instance;
            return instance;
        }
        return RelatedBlocksLists_Settings_Js.instance;
    }
},{
    /* For License page - Begin */
    init : function() {
        this.initiate();
    },
    /*
     * Function to initiate the step 1 instance
     */
    initiate : function(){
        var step=jQuery(".installationContents").find('.step').val();
        this.initiateStep(step);
    },
    /*
     * Function to initiate all the operations for a step
     * @params step value
     */
    initiateStep : function(stepVal) {
        var step = 'step'+stepVal;
        this.activateHeader(step);
    },

    activateHeader : function(step) {
        var headersContainer = jQuery('.crumbs ');
        headersContainer.find('.active').removeClass('active');
        jQuery('#'+step,headersContainer).addClass('active');
    },

    registerActivateLicenseEvent : function() {
        var aDeferred = jQuery.Deferred();
        jQuery(".installationContents").find('[name="btnActivate"]').click(function() {
            var license_key=jQuery('#license_key');
            if(license_key.val()=='') {
                app.helper.showAlertBox({message:"License Key cannot be empty"});
                aDeferred.reject();
                return aDeferred.promise();
            }else{
                var progressIndicatorElement = jQuery.progressIndicator({
                    'position' : 'html',
                    'blockInfo' : {
                        'enabled' : true
                    }
                });
                var params = {};
                params['module'] = app.getModuleName();
                params['action'] = 'Activate';
                params['mode'] = 'activate';
                params['license'] = license_key.val();

                AppConnector.request(params).then(
                    function(data) {
                        progressIndicatorElement.progressIndicator({'mode' : 'hide'});
                        if(data.success) {
                            var message=data.result.message;
                            if(message !='Valid License') {
                                jQuery('#error_message').html(message);
                                jQuery('#error_message').show();
                            }else{
                                document.location.href="index.php?module=RelatedBlocksLists&parent=Settings&view=Settings&mode=step3";
                            }
                        }
                    },
                    function(error) {
                        progressIndicatorElement.progressIndicator({'mode' : 'hide'});
                    }
                );
            }
        });
    },

    registerValidEvent: function () {
        jQuery(".installationContents").find('[name="btnFinish"]').click(function() {
            app.helper.showProgress();
            var data = {};
            data['module'] = app.getModuleName();
            data['action'] = 'Activate';
            data['mode'] = 'valid';
            app.request.post({data:data}).then(
                function (err,data) {
                    if(err == null){
                        app.helper.hideProgress();
                        if (data) {
                            document.location.href = "index.php?module=RelatedBlocksLists&parent=Settings&view=Settings";
                        }
                    }
                }
            );
        });
    },
    /* For License page - End */
    registerEnableModuleEvent:function() {
        jQuery('.summaryWidgetContainer').find('#enable_module').change(function(e) {
            app.helper.showProgress();
            var element=e.currentTarget;
            var value=0;
            var text="Related Blocks & Lists Disabled";
            if(element.checked) {
                value=1;
                text = "Related Blocks & Lists Enabled";
            }
            var params = {};
            var data = {
                action:'ActionAjax',
                module: 'RelatedBlocksLists',
                value: value,
                mode:'enableModule'
            };
            params.data = data;
            app.request.post(params).then(
                function(err,data){
                    if(err == null){
                        app.helper.hideProgress();
                        app.helper.showSuccessNotification({'message': text});
                    }
                }
            );
        });
    },
    registerEvents: function(){
        this.registerEnableModuleEvent();
        /* For License page - Begin */
        this.registerActivateLicenseEvent();
        this.registerValidEvent();
        /* For License page - End */
    }
});

jQuery(document).ready(function() {
    Vtiger_Index_Js.getInstance().registerEvents();
});
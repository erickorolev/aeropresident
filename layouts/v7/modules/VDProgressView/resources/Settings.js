Vtiger.Class("VDProgressView_Settings_Js",{
    instance:false,
    getInstance: function(){
        if(VDProgressView_Settings_Js.instance == false){
            var instance = new VDProgressView_Settings_Js();
            VDProgressView_Settings_Js.instance = instance;
            return instance;
        }
        return VDProgressView_Settings_Js.instance;
    }
},{
    
    registerSwitchStatus:function(){
        jQuery("input[name='progress_status']").bootstrapSwitch();
        jQuery('input[name="progress_status"]').on('switchChange.bootstrapSwitch', function (e) {
            var currentElement = jQuery(e.currentTarget);
            var active = false;
            var progress_module = currentElement.data('module');
            var record_id = currentElement.data('id');
            if(currentElement.val() == 'on'){
                currentElement.attr('value','off');
            } else {
                active = true;
                currentElement.attr('value','on');
            }
            var params = {
                module : 'VDProgressView',
                'action' : 'ActionAjax',
                'mode' : 'UpdateStatus',
                'record' : record_id,
                'progress_module' :progress_module,
                'status' : currentElement.val()
            }
            AppConnector.request(params).then(function(data){
                if(data){
                    app.helper.showSuccessNotification({
                        message : app.vtranslate('JS_STATUS_CHANGED')
                    });
                    if(active){
                        location.reload();
                    }
                }
            });
        });
    },
    registerEnableModuleEvent:function() {
        jQuery('.summaryWidgetContainer').find('#enable_module').change(function(e) {
            var progressIndicatorElement = jQuery.progressIndicator({
                'position' : 'html',
                'blockInfo' : {
                    'enabled' : true
                }
            });

            var element=e.currentTarget;
            var value=0;
            var text="Progress Disabled";
            if(element.checked) {
                value=1;
                text = "Progress Enabled";
            }
            var params = {};
            params.action = 'ActionAjax';
            params.module = 'VDProgressView';
            params.value = value;
            params.mode = 'enableModule';
            AppConnector.request(params).then(
                function(data){
                    progressIndicatorElement.progressIndicator({'mode' : 'hide'});
                    var params = {};
                    params['text'] = text;
                    Settings_Vtiger_Index_Js.showMessage(params);
                },
                function(error){
                    //TODO : Handle error
                    progressIndicatorElement.progressIndicator({'mode' : 'hide'});
                }
            );
        });
    },
    registerDeleteProgress:function(){
        $('a#vdprogressview_delete').on('click',function(e){
            var currentElement = jQuery(e.currentTarget);
            app.helper.showConfirmationBox({
                message: app.vtranslate('JS_DELETE_RECORD')
            }).then(function () {
                var params = {
                    module : 'VDProgressView',
                    'action' : 'ActionAjax',
                    'mode' : 'DeleteRecord',
                    'record' : currentElement.data('id')
                }
                AppConnector.request(params).then(function(data){
                    if(data){
                        window.location.reload();
                    }
                });
            });
        });
    },
    registerRowClick:function(){
        $('tr.listViewEntries td:not(:first-child):not(:last-child)').on('click',function(e){
            var currentElement = jQuery(e.currentTarget);
            var url = currentElement.parent().data('url');
            window.location = url;
        });
    },
    registerEvents: function(){
        this.registerEnableModuleEvent();
        this.registerSwitchStatus();
        this.registerDeleteProgress();
        this.registerRowClick();
    }
});
jQuery(document).ready(function() {
    var instance = new VDProgressView_Settings_Js();
    instance.registerEvents();
    Vtiger_Index_Js.getInstance().registerEvents();
});
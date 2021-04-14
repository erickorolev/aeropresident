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
    }
});

jQuery(document).ready(function() {
    Vtiger_Index_Js.getInstance().registerEvents();
});
/*+***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 *************************************************************************************/
Vtiger.Class("Control_Layout_Fields_Js",{
},{
    fieldValuesCache: {},
    /*
     * Function to register the change module filter
     */
    registerModuleFilterChange:function(){
        jQuery('#clfModuleFilter').on('change',function(){
            var filter_value = jQuery(this).val();
            window.location.href = 'index.php?module=ControlLayoutFields&parent=Settings&view=ListAll&mode=listAll&ModuleFilter=' + filter_value;
        });
    },
    registerPagingAction:function(){
        var current_page = jQuery('#current_page').val();
        var filter_value = jQuery('#clfModuleFilter').val();
        jQuery('#clfListViewNextPageButton').on('click',function(){
            window.location.href = 'index.php?module=ControlLayoutFields&parent=Settings&view=ListAll&mode=listAll&ModuleFilter=' + filter_value+'&page='+(parseInt(current_page) + 1);
        });
        jQuery('#clfListViewPreviousPageButton').on('click',function(){
            window.location.href = 'index.php?module=ControlLayoutFields&parent=Settings&view=ListAll&mode=listAll&ModuleFilter=' + filter_value+'&page='+(parseInt(current_page) - 1);
        });

    },
    registerDeleteAction:function(){
        var current_page = jQuery('#current_page').val();
        var filter_value = jQuery('#clfModuleFilter').val();
        jQuery('.removeCLF').on('click',function(){
            var recordId = jQuery(this).data('id');
            var message = app.vtranslate('Are you sure you want to delete this row?');
            app.helper.showConfirmationBox({'message' : message}).then(
                function(){
                    window.location.href = 'index.php?module=ControlLayoutFields&parent=Settings&view=ListAll&mode=delete&record='+recordId+'&ModuleFilter=' + filter_value+'&page='+current_page;
                },
                function(error, err){
                }
            );
        });
    },
    registerEventClickOnRow:function(){
        jQuery("[id^='ControlLayoutFields_listView_row_'] td").on('click',function(e){
            if (e.target.nodeName == 'TD'){
                var url = $(this).closest("tr").attr("data-recordurl");
                window.location.href = url;
            }
        });
    },
    displayByClf:function(module,field_name_changed,new_value,blockid){
        var thisInstance = this;
        //to integrate with Custom View & Form
        if(module == "CustomFormsViews"){
            var top_url = window.location.href.split('?');
            var array_url = thisInstance.getQueryParams(top_url[1]);
            module = array_url.currentModule;
        }
        var params = {
            module : 'ControlLayoutFields',
            action : 'ActionAjax',
            mode : 'checkCLFForModule',
            current_module : module
        };
        app.request.post({'data': params}).then(
            function(err,data){
                if(err === null) {
                    if(!jQuery.isEmptyObject(data)){
                        var role_id = data.role_id;
                        jQuery.each(data.clf_info,function(k,v){
                            var all_condition = v.condition.all;
                            var any_condition = v.condition.any;
                            var actions = v.actions;
                            var condition_key = k;
                            if(blockid > 0){
                                console.log("blockid:" + blockid);
                                var is_block = false;
                                var fields_on_conditions = thisInstance.getFieldOnConditions(all_condition,any_condition);
                                if(fields_on_conditions.length > 0) {
                                    $.each(fields_on_conditions, function (index,field_name) {
                                        var this_field = $('.relatedblockslists' +blockid ).find("[name*='" + field_name + "']");
                                        $.each(this_field, function (i, e) {
                                            var parent_tr = $(e).closest('tr.relatedRecords');
                                            if (!parent_tr.hasClass('relatedRecordsClone')) {
                                                var check_condition = thisInstance.checkConditionToForm(all_condition, any_condition, $(e), '', role_id, $(e).val());
                                                if (check_condition) {
                                                    jQuery.each(actions, function (index, value) {
                                                        var form_element = parent_tr.find("[name *='" + value.field + "']");
                                                        var target_td = form_element.closest('td');
                                                        var data_info = form_element.data('fieldname');
                                                        if(target_td.length == 0) {
                                                            is_block = true;
                                                            form_element = parent_tr.closest('table').find("[name *='" + value.field + "']");
                                                            target_td = form_element.closest('td');
                                                        }
                                                        //return;
                                                        if (value.option == 'hide') {
                                                            if(!is_block) {
                                                                target_td.children().hide();
                                                                form_element.addClass(condition_key+'-clf-hide');
                                                            }
                                                            else{
                                                                form_element.addClass(condition_key+'-clf-hide');
                                                                target_td.children().hide();
                                                                target_td.prev('td.fieldLabel').attr("data-label",target_td.prev('td.fieldLabel').html())
                                                                target_td.prev('td.fieldLabel').html('');
                                                            }
                                                        }
                                                        else if (value.option == 'read_only') {
                                                            form_element.attr('readonly','readonly');
                                                            //form_element.attr('disabled','disabled');
                                                            form_element.css('background','rgb(235, 235, 228)');
                                                            form_element.addClass(condition_key+'-clf-read-only');
                                                            if (typeof data_info != 'undefined'){
                                                                if(data_info.type == 'reference'){
                                                                    var parent_span = form_element.closest('span');
                                                                    parent_span.find('div:first').hide();
                                                                }else if(data_info.type == 'multipicklist'){
                                                                    form_element.select2('disable');
                                                                }
                                                            }
                                                        }
                                                        else if (value.option == 'mandatory') {
                                                            form_element.attr('data-rule-required','true');
                                                            form_element.addClass(condition_key+'-clf-mandatory');
                                                        }
                                                    });
                                                }
                                                else{
                                                    jQuery.each(actions,function(key,value){
                                                        var form_element = parent_tr.find("[name *='" + value.field + "']");
                                                        var target_td = form_element.closest('td');
                                                        var data_info = form_element.data('fieldname');
                                                        if(target_td.length == 0) {
                                                            is_block = true;
                                                            form_element = parent_tr.closest('table').find("[name *='" + value.field + "']");
                                                            target_td = form_element.closest('td');
                                                        }
                                                        //return;
                                                        //for Multiple Value control
                                                        if(form_element.length > 0){
                                                            var data_info = form_element.data('fieldinfo');
                                                            if(form_element.hasClass(condition_key+'-clf-mandatory')){
                                                                form_element.removeAttr('data-rule-required');
                                                                form_element.removeAttr('aria-required');
                                                                form_element.removeAttr('aria-invalid');
                                                                form_element.removeClass(condition_key+'-clf-mandatory');
                                                            }
                                                            else if(form_element.hasClass(condition_key+'-clf-read-only')){
                                                                form_element.removeAttr('readonly');
                                                                form_element.removeAttr('disabled');
                                                                form_element.css('background','white');
                                                                form_element.removeClass(condition_key+'-clf-read-only');
                                                                if (typeof data_info != 'undefined'){
                                                                    if(data_info.type == 'reference'){
                                                                        var parent_span = form_element.closest('span');
                                                                        parent_span.find('div:first').show();
                                                                    }else if(data_info.type == 'multipicklist'){
                                                                        form_element.select2('enable');
                                                                    }
                                                                }
                                                            }
                                                            else if(form_element.hasClass(condition_key+'-clf-hide')){
                                                                form_element.removeClass(condition_key+'-clf-hide');
                                                                form_element.closest('div').show();
                                                                if(!is_block) {
                                                                    target_td.children().show();
                                                                }
                                                                else{
                                                                    form_element.show();
                                                                    target_td.children().show();
                                                                    target_td.prev('td.fieldLabel').html( target_td.prev('td.fieldLabel').data('label'));
                                                                }
                                                            }
                                                            if(form_element.is('select')) form_element.trigger('liszt:updated');
                                                        }
                                                    });
                                                }
                                            }
                                        });
                                    });
                                }
                            }
                            else{
                                var check_condition = thisInstance.checkConditionToForm(all_condition,any_condition,field_name_changed,'',role_id,new_value);
                                if(check_condition){
                                    jQuery.each(actions,function(key,value){
                                        var field_name_action = value.field;
                                        var form_element = jQuery('#EditView').find('[name="'+field_name_action+'"]');
                                        //for Multiple Value control
                                        if(form_element.attr('type') == 'hidden'){
                                            form_element = jQuery('#EditView').find('[name="'+field_name_action+'[]"]');
                                            if(form_element.length === 0){
                                                //for reference control uitype 10
                                                form_element = jQuery('#EditView').find('[name="'+field_name_action+'_display"]');

                                                if(form_element.length === 0){
                                                    //for Checkbox control
                                                    form_element = jQuery('#EditView').find('[name="'+field_name_action+'"]').last();
                                                }
                                            }
                                        }
                                        var data_info = form_element.data('fieldname');
                                        var this_td = form_element.closest('td');
                                        if(value.option == 'mandatory'){
                                            this_td.show();
                                            this_td.prev().show();
                                            form_element.attr('data-rule-required','true');
                                            form_element.addClass(condition_key+'-clf-mandatory');
                                            var field_label = form_element.closest('td').prev();
                                            if(!field_label.find('span').length) field_label.append('<span class="redColor">*</span>');
                                        }else if(value.option == 'read_only'){
                                            this_td.show();
                                            this_td.prev().show();
                                            form_element.attr('readonly','readonly');
                                            form_element.attr('disabled','disabled');
                                            form_element.css('background','rgb(235, 235, 228)');
                                            form_element.addClass(condition_key+'-clf-read-only');
                                            if (typeof data_info != 'undefined'){
                                                if(data_info.type == 'reference'){
                                                    var parent_span = form_element.closest('span');
                                                    parent_span.find('div:first').hide();
                                                }else if(data_info.type == 'multipicklist'){
                                                    form_element.select2('disable');
                                                }
                                            }
                                        }else if(value.option == 'hide'){
                                            form_element.addClass(condition_key+'-clf-hide');
                                            this_td.hide();
                                            this_td.prev().hide();
                                            var this_tr = this_td.closest('tr');
                                            thisInstance.hideTr(this_tr);
                                        }
                                        if(form_element.is('select')) form_element.trigger('liszt:updated');
                                        //END
                                    });
                                }
                                else{
                                    jQuery.each(actions,function(key,value){
                                        var field_name_action = value.field;
                                        var form_element = jQuery('#EditView').find('[name="'+field_name_action+'"]');
                                        //for Multiple Value control
                                        if(form_element.attr('type') == 'hidden'){
                                            form_element = jQuery('#EditView').find('[name="'+field_name_action+'[]"]');
                                            if(form_element.length === 0){
                                                //for reference control uitype 10
                                                form_element = jQuery('#EditView').find('[name="'+field_name_action+'_display"]');
                                                if(form_element.length === 0){
                                                    //for Checkbox control
                                                    form_element = jQuery('#EditView').find('[name="'+field_name_action+'"]').last();
                                                }
                                            }
                                        }
                                        var data_info = form_element.data('fieldinfo');
                                        if(form_element.hasClass(condition_key+'-clf-mandatory')){
                                            var field_label = form_element.closest('td').prev();
                                            if(field_label.length) field_label.find('span.redColor').remove();
                                            form_element.removeAttr('data-rule-required');
                                            form_element.removeAttr('aria-required');
                                            form_element.removeAttr('aria-invalid');
                                            form_element.removeAttr('data-rule-illegal');
                                            form_element.removeClass(condition_key+'-clf-mandatory');
                                        }
                                        if(form_element.hasClass(condition_key+'-clf-read-only')){
                                            form_element.removeAttr('readonly');
                                            form_element.removeAttr('disabled');
                                            form_element.css('background','white');
                                            form_element.removeClass(condition_key+'-clf-read-only');
                                            if (typeof data_info != 'undefined'){
                                                if(data_info.type == 'reference'){
                                                    var parent_span = form_element.closest('span');
                                                    parent_span.find('div:first').show();
                                                }else if(data_info.type == 'multipicklist'){
                                                    form_element.select2('enable');
                                                }
                                            }
                                        }
                                        if(form_element.hasClass(condition_key+'-clf-hide')){
                                            form_element.removeClass(condition_key+'-clf-hide');
                                            var this_td = form_element.closest('td');
                                            //this_td.find('div:first').show();
                                            this_td.show();
                                            this_td.children().show();
                                            this_td.prev().show();
                                            this_td.prev().children().show();
                                            if(form_element.hasClass('chzn-select') || form_element.hasClass('select2')) form_element.hide();
                                            // Handler for custom upload field
                                            if(this_td.find('#frm_'+field_name_action).length > 0) {
                                                form_element.hide();
                                            }
                                            var this_tr = this_td.closest('tr');
                                            thisInstance.hideTr(this_tr);
                                        }
                                        if(form_element.is('select')) form_element.trigger('liszt:updated');
                                    });
                                    //END
                                }
                            }
                            condition_key++;
                        });
                    }
                }
            }
        );
    },
    hideTr:function(this_tr){
        var count_td_hide = 1;
        this_tr.find('td').each (function() {
            if ( jQuery(this).children(":first").css('display') == 'none' || jQuery(this).children().length == 0){
                count_td_hide ++;
            }
        });
        if(count_td_hide >=5)  this_tr.hide();
        else  this_tr.show();
    },
    getFieldOnConditions: function(all_condition,any_condition){
        var list_fields = new Array();
        jQuery.each(all_condition,function(key,value) {
            if($.inArray(value.columnname,list_fields) == -1) list_fields.push(value.columnname) ;
        });
        jQuery.each(any_condition,function(key,value) {
            if($.inArray(value.columnname,list_fields) == -1) list_fields.push(value.columnname) ;
        });
        return list_fields;
    },
    displayByClfOnDetail:function(moduleName,requestMode,record_id,blockid,new_value){
        var thisInstance = this;
        //to integrate with Custom View & Form
        if(moduleName == "CustomFormsViews"){
            var top_url = window.location.href.split('?');
            var array_url = thisInstance.getQueryParams(top_url[1]);
            moduleName = array_url.currentModule;
        }
        var params = {
            module : 'ControlLayoutFields',
            action : 'ActionAjax',
            mode : 'checkCLFForModule',
            current_module : moduleName,
            record_id:record_id
        };
        app.request.post({'data': params}).then(
            function(err,data){
                if(err === null) {
                    if (!jQuery.isEmptyObject(data)) {
                        var record_info = data.record_info;
                        var role_id = data.role_id;
                        if($('#hd_clf_info_' + moduleName).length == 0){
                            jQuery('<input>').attr({
                                type: 'hidden',
                                id: 'hd_clf_info_' + moduleName,
                                value:JSON.stringify(data.clf_info)
                            }).appendTo(jQuery('#detailView'));
                        }
                        else{
                            $('#hd_clf_info_' + moduleName).val(JSON.stringify(data.clf_info));
                        }
                        jQuery.each(data.clf_info,function(k,v) {
                            var all_condition = v.condition.all;
                            var any_condition = v.condition.any;
                            var actions = v.actions;
                            var condition_key = k;
                            if(blockid > 0){
                                var is_block = false;
                                var fields_on_conditions = thisInstance.getFieldOnConditions(all_condition,any_condition);
                                if(fields_on_conditions.length > 0) {
                                    $.each(fields_on_conditions, function (index,field_name) {
                                        var this_field = $('.relatedblockslists' +blockid ).find("[name *='" + field_name + "']");
                                        $.each(this_field, function (i, e) {
                                            var parent_tr = $(e).closest('tr.relatedRecords');
                                            var record_row_id =  parent_tr.data('id');
                                            if(typeof record_row_id == "undefined"){
                                                //is_block = true;
                                                record_row_id = parent_tr.closest('div.blockData').data('id');
                                            }
                                            if(record_id > 0 && new_value != "" && record_row_id > 0){
                                                if(record_row_id != record_id) return;
                                            }
                                            if (!parent_tr.hasClass('relatedRecordsClone')) {
                                                var value_to_check = $(e).val();
                                                if(new_value != "") value_to_check = new_value;
                                                var check_condition = thisInstance.checkConditionToForm(all_condition, any_condition, $(e), record_info, role_id, value_to_check);
                                                if (check_condition) {
                                                    jQuery.each(actions, function (index, value) {
                                                        var this_action_field = parent_tr.find("[name *='" + value.field + "']");
                                                        var target_td = this_action_field.closest('td');
                                                        console.log( value.field + ":" + this_action_field.attr('name') + ", len:" + this_action_field.length);
                                                        if(this_action_field.length == 0) {
                                                            is_block = true;
                                                            this_action_field = parent_tr.closest('table').find("[name *='" + value.field + "']");
                                                            target_td = this_action_field.closest('td');
                                                        }
                                                        var target_value = this_action_field.val();
                                                        //return;
                                                        if (value.option == 'hide') {
                                                            var saved_html = target_td.html();
                                                            if(!is_block){
                                                                target_td.html('');
                                                                target_td.append('<div class="hide tempDiv">'+saved_html+'</div>');
                                                            }
                                                            else{
                                                                target_td.html('');
                                                                target_td.append('<div class="hide tempDiv">'+saved_html+'</div>');
                                                                target_td.prev('td').children().hide();
                                                            }
                                                        }
                                                        else if (value.option == 'read_only') {
                                                            //target_td.children().hide();
                                                            var saved_html = target_td.html();
                                                            target_td.html('');
                                                            target_td.html('<span class="tmpLabel">' +target_value+'<span>');
                                                            target_td.append('<div class="hide tempDiv">'+saved_html+'</div>');
                                                        }
                                                        else if (value.option == 'mandatory') {
                                                            var target_element = target_td.find('[name*=' + value.field + ']');
                                                            if (target_element.length > 0) {
                                                                target_element.attr('data-rule-required', 'true');
                                                                if (target_element.is('select')) target_element.trigger('liszt:updated');
                                                            }
                                                        }
                                                    });
                                                }
                                                else{
                                                    jQuery.each(actions, function (index, value) {
                                                        var this_action_field = parent_tr.find("[name *='" + value.field + "']");
                                                        var target_td = this_action_field.closest('td');
                                                        if(target_td.length == 0) {
                                                            is_block = true;
                                                            this_action_field = parent_tr.closest('table').find("[name ='" + value.field + "']");
                                                            target_td = this_action_field.closest('td');
                                                        }
                                                        var target_value = this_action_field.val();
                                                        //return;
                                                        if (value.option == 'hide') {
                                                            var saved_temp_div = target_td.find('div.tempDiv');
                                                            if(saved_temp_div.length > 0){
                                                                var saved_html = saved_temp_div.html();
                                                                saved_temp_div.remove();
                                                                target_td.html(saved_html);
                                                                if(is_block){
                                                                    target_td.find('span.value').removeClass('hide').show();
                                                                    target_td.find('span.edit').hide();
                                                                }
                                                                else{
                                                                    target_td.find('div:first').show();
                                                                    target_td.find('span.value').removeClass('hide').show();
                                                                    target_td.find('span.edit').hide();
                                                                    target_td.prev('td').children().show();
                                                                }
                                                            }
                                                        }
                                                        else if (value.option == 'read_only') {
                                                            var saved_temp_div = target_td.find('div.tempDiv');
                                                            if(saved_temp_div.length > 0){
                                                                var saved_html = saved_temp_div.html();
                                                                saved_temp_div.remove();
                                                                target_td.html(saved_html);
                                                                if(is_block){
                                                                    target_td.find('span.value').removeClass('hide').show();
                                                                    target_td.find('span.edit').hide();
                                                                }
                                                                else{
                                                                    target_td.find('div:first').show();
                                                                    target_td.find('span.value').removeClass('hide').show();
                                                                    target_td.find('span.edit').hide();
                                                                }
                                                            }
                                                        }
                                                        else if (value.option == 'mandatory') {
                                                            var target_element = target_td.find('[name=' + value.field + ']');
                                                            if (target_element.length > 0) {
                                                                target_element.removeAttr('data-rule-required');
                                                                if (target_element.is('select')) target_element.trigger('liszt:updated');
                                                            }
                                                        }
                                                    });
                                                }
                                            }
                                        });
                                    });
                                }
                            }
                            else{
                                var check_condition = thisInstance.checkConditionToForm(all_condition, any_condition, 'clf_details',record_info,role_id,'');
                                if (check_condition) {
                                    jQuery.each(actions, function (index, value) {
                                        if (typeof requestMode != 'undefined' && requestMode == 'full') {
                                            var target_td = jQuery("#" + moduleName + "_detailView_fieldValue_" + value.field);
                                            var target_value = target_td.children('span:first').html();
                                            if (target_td.children('span:first').data('field-type') == 'url') {
                                                target_value = target_td.children('span:first').children('a').html();
                                            }
                                            if (!target_value) target_value = '';
                                            if (value.option == 'hide') {
                                                target_td.prev().html("");
                                                target_td.html("");
                                                var tr = target_td.closest('tr');
                                                var table = tr.closest('table');
                                                var empty_tr = true;
                                                var count_item = 0;
                                                var saved_td = [];
                                                jQuery.each(tr.find('td'), function () {
                                                    count_item++;
                                                    if (jQuery(this).html().trim() != '') {
                                                        empty_tr = false;
                                                        saved_td[count_item] = jQuery(this).html();
                                                    }

                                                });
                                                if (empty_tr) tr.remove();

                                            }
                                            else if (value.option == 'read_only') {
                                                if(target_td.children('span:first').length >0) {
                                                    target_td.html(target_value);
                                                }
                                            }
                                            else if (value.option == 'mandatory') {
                                                var target_element = target_td.find('[name='+value.field+']');
                                                if(target_element.length > 0){
                                                    target_element.attr('data-rule-required', 'true');
                                                    if(target_element.is('select')) target_element.trigger('liszt:updated');
                                                }
                                            }
                                        }
                                        else {
                                            var target_td = jQuery("#" + moduleName + "_detailView_fieldValue_" + value.field);
                                            if(target_td.length == 0){
                                                //Summary view
                                                var target_element = jQuery('[data-name='+value.field+']');
                                                var target_span = target_element.closest('span');
                                                if (value.option == 'hide') {
                                                    //target_span.closest('tr').remove();
                                                    var parent_tr = target_span.closest('tr');
                                                    if(parent_tr.find('td').length < 4){
                                                        target_span.closest('tr').remove();
                                                    }
                                                }
                                                else if (value.option == 'read_only') {
                                                    target_span.next().remove();
                                                    target_span.remove();
                                                }
                                                else if (value.option == 'mandatory') {
                                                    target_element.attr('data-rule-required', 'true');
                                                }
                                            }else{
                                                //Detail View
                                                var target_td = jQuery("#" + moduleName + "_detailView_fieldValue_" + value.field);
                                                var target_value = target_td.children('span:first').html();
                                                if (target_td.children('span:first').data('field-type') == 'url') {
                                                    target_value = target_td.children('span:first').children('a').html();
                                                }
                                                if (!target_value) target_value = '';
                                                if (value.option == 'hide') {
                                                    target_td.prev().html("");
                                                    target_td.html("");
                                                    var tr = target_td.closest('tr');
                                                    var table = tr.closest('table');
                                                    var empty_tr = true;
                                                    var count_item = 0;
                                                    var saved_td = [];
                                                    jQuery.each(tr.find('td'), function () {
                                                        count_item++;
                                                        if (jQuery(this).html().trim() != '') {
                                                            empty_tr = false;
                                                            saved_td[count_item] = jQuery(this).html();
                                                        }

                                                });
                                                if (empty_tr) tr.remove();
                                            }
                                            else if (value.option == 'read_only') {
                                                if(target_td.children('span:first').length >0) {
                                                    target_td.html(target_value);
                                                }
                                            }
                                            else if (value.option == 'mandatory') {
                                                var target_element = $('[data-name='+value.field+']');
                                                target_element.attr('data-rule-required', 'true');
                                                if(target_element.is('select')) target_element.trigger('liszt:updated');
                                            }
                                        }
                                    }
                                });
                                //remove all empty block
                                jQuery.each(jQuery('#detailView').find('.detailview-table'), function () {
                                    var row_count = jQuery(this).find('tr').length;
                                    if (row_count == 0) jQuery(this).hide();
                                });

                                //return false;
                                }
                            }
                        });
                    }
                }
            }
        );
    },
    checkCondition: function(form_element_value,comparator,field_value,field_name_changed,field_name){
        var thisInstace = this;
        //#1385692 BEGIN
        var form_element_value = form_element_value.toString();
        var field_value = field_value.toString();
        //#1385692 END
        switch(comparator) {
            case 'is':
                var arrVal = field_value.split(',');
                if(arrVal.includes(form_element_value)){
                    return true;
                }
                return false;
                break;
            case 'is not':
                var arrVal = field_value.split(',');
                if(arrVal.includes(form_element_value)){
                    return false;
                }
                return true;
                break;
            case 'contains':
                return ( form_element_value.indexOf(field_value) !== -1 );
                break;
            case 'does not contain':
                return ( form_element_value.indexOf(field_value) == -1 );
                break;
            case 'starts with':
                return (form_element_value.startsWith(field_value));
                break;
            case 'ends with':
                return (form_element_value.endsWith(field_value));
                break;
            case 'is empty':
                return (form_element_value == '');
                break;
            case 'is not empty':
                return (form_element_value != '');
                break;
            case 'has changed':
                return (field_name_changed == field_name);
                break;
            case 'has changed to':
                var arrVal = field_value.split(',');
                if(arrVal.includes(form_element_value)){
                    return true;
                }
                return false;
                break;
            case 'equal to':
                return (parseFloat(form_element_value) == parseFloat(field_value));
                break;
            case 'less than':
                return (parseFloat(form_element_value) < parseFloat(field_value));
                break;
            case 'greater than':
                return (parseFloat(form_element_value) > parseFloat(field_value));
                break;
            case 'does not equal':
                return (parseFloat(form_element_value) != parseFloat(field_value));
                break;
            case 'less than or equal to':
                return (parseFloat(form_element_value) <= parseFloat(field_value));
                break;
            case 'greater than or equal to':
                return (parseFloat(form_element_value) >= parseFloat(field_value));
                break;
            //date
            case 'between':
                var arr_date = field_value.split(",");
                return ((thisInstace.newDate(form_element_value) >= thisInstace.newDate(arr_date[0])) && (thisInstace.newDate(form_element_value) <= thisInstace.newDate(arr_date[1])));
                break;
            case 'before':
                return (thisInstace.newDate(form_element_value) < thisInstace.newDate(field_value));
                break;
            case 'after':
                return (thisInstace.newDate(form_element_value) > thisInstace.newDate(field_value));
                break;
            case 'is today':
                return (thisInstace.newDate(form_element_value) == thisInstace.newDate());
                break;
            case 'less than days ago':
                var num_day = parseInt(field_value);
                var date_inputed = thisInstace.newDate(form_element_value);
                var today = thisInstace.newDate();
                var date_check = thisInstace.newDate(today.getFullYear(), today.getMonth(), today.getDate() - num_day);
                return (date_inputed >= date_check);
                break;
            case 'more than days ago':
                var num_day = parseInt(field_value);
                var date_inputed = thisInstace.newDate(form_element_value);
                var today = thisInstace.newDate();
                var date_check = thisInstace.newDate(today.getFullYear(), today.getMonth(), today.getDate() + num_day);
                return (date_inputed >=date_check);
                break;
            case 'days ago':
                var num_day = parseInt(field_value);
                var date_inputed = thisInstace.newDate(form_element_value);
                var today = thisInstace.newDate();
                var date_check = thisInstace.newDate(today.getFullYear(), today.getMonth(), today.getDate() - num_day);
                return (date_inputed >date_check);
                break;
            case 'days later':
                var num_day = parseInt(field_value);
                var date_inputed = thisInstace.newDate(form_element_value);
                var today = thisInstace.newDate();
                var date_check = thisInstace.newDate(today.getFullYear(), today.getMonth(), today.getDate() + num_day);
                return (date_inputed >date_check);
                break;
            case 'in less than':
                return (thisInstace.newDate(form_element_value) <= thisInstace.newDate(field_value));
                break;
            case 'in more than':
                return (thisInstace.newDate(form_element_value) >= thisInstace.newDate(field_value));
                break;

        }
    },
    newDate:function(_date){
        var _format = "mm-dd-yyyy";
        var _delimiter = "-";
        var formatLowerCase=_format.toLowerCase();
        var formatItems=formatLowerCase.split(_delimiter);
        var dateItems=_date.split(_delimiter);
        var monthIndex=formatItems.indexOf("mm");
        var dayIndex=formatItems.indexOf("dd");
        var yearIndex=formatItems.indexOf("yyyy");
        var month=parseInt(dateItems[monthIndex]);
        month-=1;
        return new Date(dateItems[yearIndex],month,dateItems[dayIndex]);
    },
    //this function to check condition from config to dispay control on form
    checkConditionToForm:function(all_condition,any_condition,field_name_changed,record_info,role_id,new_value){
        var thisInstance = this;
        var is_all = false;
        var is_any = false;
        if(all_condition.length == 0 && any_condition.length == 0){
            return true;
        }
        //TASKID:13034 - DEV: tiennguyen - DATE: 2018-10-16 - START
        //NOTE correct logic for condition
        if(all_condition.length == 0 && any_condition.length > 0 ) is_all = true;
        if(all_condition.length > 0 && any_condition.length == 0 ) is_any = true;
        //TASKID:13034 - DEV: tiennguyen - DATE: 2018-10-16 - END
	var fields_on_conditions = thisInstance.getFieldOnConditions(all_condition,any_condition);
        jQuery.each(all_condition,function(key,value){
            var field_name = value.columnname;
            //console.log("field_name:"+field_name);
            var field_value =  value.value;
            var comparator =  value.comparator;
            var main_form = jQuery('#EditView');
            var form_element = main_form.find('[name="'+field_name+'"]');
            if(field_name_changed == 'clf_details'){
                main_form = jQuery('#detailView');
                form_element = main_form.find('[data-name="'+field_name+'"]');
            }
            if(typeof form_element == 'undefined' && field_name == 'total'){
                form_element = jQuery('#EditView').find('[name="grandTotal"]');
            }
            if(!form_element.length){
                form_element = jQuery('[data-name="' +field_name+ '"]');
                form_element.val(form_element.attr('data-value'));
            }
            var form_element_value = form_element.val();
            if(!form_element.length && field_name == "accountname"){
                form_element_value = jQuery('[name="account_id"],[name="related_to"]').data('displayvalue');
            }
            if(form_element.length && form_element.hasClass('sourceField')){
                form_element_value = form_element.data('displayvalue');
            }
            if(field_name_changed == 'clf_details') {
                if(form_element.data("type") != "reference"){
                    form_element_value = form_element.data("value");
                }
                else{
                    var link = form_element.data("displayvalue");
                    form_element_value = $(link).html();
                }

            }
            if(typeof form_element_value == 'undefined' && field_name_changed == 'clf_details'){
                //var record_info = thisInstance.getRecordIdAndModule();
                if(typeof thisInstance.fieldValuesCache[field_name] == 'undefined') {
                    form_element_value = record_info[field_name];
                    thisInstance.fieldValuesCache[field_name] = form_element_value;
                }else{
                    form_element_value = thisInstance.fieldValuesCache[field_name];
                }
            }
            if(new_value != '' && typeof form_element_value == 'undefined') form_element_value = new_value;
            //console.log("form_element_value:"+form_element_value);
            //for Multiple Value control
            if(form_element.attr('type') == 'hidden'){
                form_element = form_element.next();
                if(!form_element.is('input')){
                    form_element = form_element.next('select');
                    if(form_element.val()) form_element_value = form_element.val().join(',');
                }
                else{
                    if(form_element.attr('type') == 'checkbox'){
                        if (form_element.is(":checked"))
                        {
                            form_element_value = 1;
                        }
                        else{
                            form_element_value = 0;
                        }

                    }
                }
            }
            if(field_name == "roleid"){
                form_element_value = role_id;
            }
            //START
            //TASKID: 1030263 - DEV: tuan@vtexperts.com - DATE: 25/09/2018
            //NOTES: For working with RBL
            if(typeof form_element_value == "undefined"){
                form_element = main_form.find('[name*="'+field_name+'"]');
                if(form_element.length > 0) form_element_value = form_element.val();
            }
            if(form_element.length >= 2) {
                $.each(form_element, function (i, e) {
                    var parent_tr = $(e).closest('tr');
                    if (parent_tr.hasClass('relatedRecords')){
                        form_element_value = $(e).val();
                        return;
                    }
                });
            }
//             if($.inArray(field_name_changed,fields_on_conditions) > 0 && new_value != undefined && new_value != ''){
//                 form_element_value = new_value;
//             }
            ////END
            //#1115263
            // fix issue all condition not correct.
            if( typeof form_element_value != "undefined"){
                var result = thisInstance.checkCondition(form_element_value,comparator,field_value,field_name_changed,field_name);
                if(result == true){
                    is_all = result;
                }else{
                    is_all = false;
                }
            }
        });
        jQuery.each(any_condition,function(key,value){
            var field_name = value.columnname;
            var field_value =  value.value;
            var comparator =  value.comparator;
            var form_element = jQuery('#EditView').find('[name="'+field_name+'"]');
            if(field_name_changed == 'clf_details'){
                form_element = jQuery('#detailView').find('[name="'+field_name+'"]');
            }
            if(typeof form_element == 'undefined' && field_name == 'total'){
                form_element = jQuery('#detailView').find('[name="grandTotal"]');
            }
            if(!form_element.length){
                form_element = jQuery('[data-name="' +field_name+ '"]');
                form_element.val(form_element.attr('data-value'));
            }
            if(!form_element.length){
                //return; //687370 - need to get value from record_info
            }
            var form_element_value = form_element.val();
            if(typeof form_element_value == 'undefined' && field_name_changed == 'clf_details'){
                var record_info = thisInstance.getRecordIdAndModule();
                if(typeof thisInstance.fieldValuesCache[field_name] == 'undefined') {
                    //form_element_value = thisInstance.getFieldValue(field_name,record_info[0],record_info[1]);
                    jQuery.each(record_info,function(key,value){
                        if(key == field_name) form_element_value = value;
                    });
                    thisInstance.fieldValuesCache[field_name] = form_element_value;
                }else{
                    form_element_value = thisInstance.fieldValuesCache[field_name];
                }
            }
            //for Multiple Value control
            if(form_element.attr('type') == 'hidden'){
                form_element = form_element.next();
                if(!form_element.is('input')){
                    form_element = form_element.next('select');
                    if(form_element.val()) form_element_value = form_element.val().join(',');
                }
                else{
                    if(form_element.attr('type') == 'checkbox'){
                        if (form_element.is(":checked"))
                        {
                            form_element_value = 1;
                        }
                        else{
                            form_element_value = 0;
                        }

                    }
                }
            }
            if(typeof form_element_value == "undefined") return false;
            var result = thisInstance.checkCondition(form_element_value,comparator,field_value,field_name_changed,field_name);
            //#1115263
            // only need one condition true then is_any true.
            if(result){
                is_any = true;
            }
        });
        // return is_all || is_any;
        //TASKID:13034 - DEV: tiennguyen - DATE: 2018-10-16 - START
        //NOTE correct logic for condition
        return is_all && is_any;
        //TASKID:13034 - DEV: tiennguyen - DATE: 2018-10-16 - END

    },
    registerFormChange:function(module){
        var thisInstance = this;
        jQuery("#EditView,.relatedblockslists_records").on("change","input,select,textarea", function () {
            var field_name = jQuery(this).attr('name');
            //TASKID:13034 - DEV: tiennguyen - DATE: 2018-10-16 - START
            //NOTE missing value for function
            var new_value=jQuery(this).val();
            if(field_name) thisInstance.displayByClf(module,field_name,new_value);
            if(field_name) thisInstance.displayByClf(module,field_name,new_value);
            //TASKID:13034 - DEV: tiennguyen - DATE: 2018-10-16 - START
	    var this_val = jQuery(this).val();
            if(field_name){
                var relatedRecords = jQuery(this).closest('.relatedRecords');
                if(relatedRecords.length > 0){
                    var blockid = relatedRecords.closest('div.relatedblockslists_records').data('block-id');
                    thisInstance.displayByClf(module,field_name,this_val,blockid);
                }
                else thisInstance.displayByClf(module,field_name);
            }
        });
    },
    getRecordIdAndModule: function(){
        var return_arr = [];
        var url = window.location.href.split('?');
        var array_url = this.getQueryParams(url[1]);
        return_arr.push(array_url.module);
        return_arr.push(array_url.record);
        return return_arr;
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

    /*
     * Function to register the list view row click event
     */
    registerRowClickEvent: function(){
        var listViewContentDiv = jQuery('.listViewEntriesTable');
        listViewContentDiv.on('click','.listViewEntryValue',function(e){
            var editUrl = jQuery(this).closest('tr').data('recordurl');
            window.location.href = editUrl;
        });
    },
    /*
     * Function to register inlineAjaxSave click event
     */
    registerInlineAjaxSaveClickEvent: function(){
        var listViewContentDiv = jQuery('.fieldValue ');
        listViewContentDiv.on('click','.inlineAjaxSave',function(e){
            var rule_required_field = jQuery(this).closest('td').find('input.fieldBasicData').data('rule-required');
            var field_id = jQuery(this).closest('td').find('input.fieldBasicData').data('name');
            var this_field = jQuery(this).closest('td').find('[name="'+field_id+'"]');
            if(rule_required_field){
                if(this_field.val()==''){
                    this_field.attr('aria-required','true');
                    this_field.attr('aria-invalid','true');
                    this_field.addClass('input-error');
                    var msg = app.vtranslate('JS_REQUIRED_FIELD');
                    var params = {};
                    params.position = {
                        my: 'bottom left',
                        at: 'top left',
                        container:jQuery("#detailView")
                    };
                    vtUtils.showValidationMessage(this_field,msg,params);
                    return false;
                }
                else{
                    vtUtils.hideValidationMessage(this_field);
                    return true;
                }
            }
            else{
                return true;
            }
        });
        listViewContentDiv.on('click','.inlineAjaxSave',function(e){

        });
    },
    waitUntil : function (waitFor,toDo){
        if(waitFor()) {
            toDo();
        } else {
            setTimeout(function() {
                waitUntil(waitFor, toDo);
            }, 300);
        }
    },
    registerEvents : function(){
        this.registerModuleFilterChange();
        this.registerPagingAction();
        this.registerDeleteAction();
        this.registerEventClickOnRow();
    },
    //1205661 BEGIN
    // make ConditionLayout work witd VTEButton popup
    //pham@vtexperts.com
    checkConditionToButtonPopupForm:function(all_condition,any_condition,field_name_changed,record_info,role_id,new_value){
        var thisInstance = this;
        var is_all = false;
        var is_any = false;
        if(all_condition.length == 0 && any_condition.length == 0){
            return true;
        }
        if(all_condition.length == 0 && any_condition.length > 0 ) is_all = true;
        if(all_condition.length > 0 && any_condition.length == 0 ) is_any = true;
        var fields_on_conditions = thisInstance.getFieldOnConditions(all_condition,any_condition);
        jQuery.each(all_condition,function(key,value){
            var field_name = value.columnname;
            var field_value =  value.value;
            var comparator =  value.comparator;
            var main_form = jQuery('#vteButtonQuickEdit');
            var form_element = main_form.find('[name="'+field_name+'"]');
            if(field_name_changed == 'clf_details'){
                main_form = jQuery('#detailView');
                form_element = main_form.find('[data-name="'+field_name+'"]');
            }
            if(typeof form_element == 'undefined' && field_name == 'total'){
                form_element = jQuery('#vteButtonQuickEdit').find('[name="grandTotal"]');
            }
            if(!form_element.length){
                form_element = jQuery('[data-name="' +field_name+ '"]');
                form_element.val(form_element.attr('data-value'));
            }
            var form_element_value = form_element.val();
            if(!form_element.length && field_name == "accountname"){
                form_element_value = jQuery('[name="account_id"],[name="related_to"]').data('displayvalue');
            }
            if(form_element.length && form_element.hasClass('sourceField')){
                form_element_value = form_element.data('displayvalue');
            }
            if(field_name_changed == 'clf_details') {
                if(form_element.data("type") != "reference"){
                    form_element_value = form_element.data("value");
                }
                else{
                    var link = form_element.data("displayvalue");
                    form_element_value = $(link).html();
                }

            }
            if(typeof form_element_value == 'undefined' && field_name_changed == 'clf_details'){
                if(typeof thisInstance.fieldValuesCache[field_name] == 'undefined') {
                    form_element_value = record_info[field_name];
                    thisInstance.fieldValuesCache[field_name] = form_element_value;
                }else{
                    form_element_value = thisInstance.fieldValuesCache[field_name];
                }
            }
            if(new_value != '' && typeof form_element_value == 'undefined') form_element_value = new_value;
            if(form_element.attr('type') == 'hidden'){
                form_element = form_element.next();
                if(!form_element.is('input')){
                    form_element = form_element.next('select');
                    if(form_element.val()) form_element_value = form_element.val().join(',');
                }
                else{
                    if(form_element.attr('type') == 'checkbox'){
                        if (form_element.is(":checked"))
                        {
                            form_element_value = 1;
                        }
                        else{
                            form_element_value = 0;
                        }

                    }
                }
            }
            if(field_name == "roleid"){
                form_element_value = role_id;
            }
            if(typeof form_element_value == "undefined"){
                form_element = main_form.find('[name*="'+field_name+'"]');
                if(form_element.length > 0) form_element_value = form_element.val();
            }
            if(form_element.length >= 2) {
                $.each(form_element, function (i, e) {
                    var parent_tr = $(e).closest('tr');
                    if (parent_tr.hasClass('relatedRecords')){
                        form_element_value = $(e).val();
                        return;
                    }
                });
            }
            if(record_info != undefined && record_info[field_name] != undefined && record_info[field_name] != '' ){
                form_element_value = record_info[field_name];
            }
            if( typeof form_element_value != "undefined"){
                var result = thisInstance.checkCondition(form_element_value,comparator,field_value,field_name_changed,field_name);
                if(result == true){
                    is_all = result;
                }else{
                    is_all = false;
                }
            }
        });
        jQuery.each(any_condition,function(key,value){
            var field_name = value.columnname;
            var field_value =  value.value;
            var comparator =  value.comparator;
            var form_element = jQuery('#vteButtonQuickEdit').find('[name="'+field_name+'"]');
            if(field_name_changed == 'clf_details'){
                form_element = jQuery('#detailView').find('[name="'+field_name+'"]');
            }
            if(typeof form_element == 'undefined' && field_name == 'total'){
                form_element = jQuery('#detailView').find('[name="grandTotal"]');
            }
            if(!form_element.length){
                form_element = jQuery('[data-name="' +field_name+ '"]');
                form_element.val(form_element.attr('data-value'));
            }
            if(!form_element.length){
                //return; //687370 - need to get value from record_info
            }
            var form_element_value = form_element.val();
            if(typeof form_element_value == 'undefined' && field_name_changed == 'clf_details'){
                var record_info = thisInstance.getRecordIdAndModule();
                if(typeof thisInstance.fieldValuesCache[field_name] == 'undefined') {
                    jQuery.each(record_info,function(key,value){
                        if(key == field_name) form_element_value = value;
                    });
                    thisInstance.fieldValuesCache[field_name] = form_element_value;
                }else{
                    form_element_value = thisInstance.fieldValuesCache[field_name];
                }
            }
            //for Multiple Value control
            if(form_element.attr('type') == 'hidden'){
                form_element = form_element.next();
                if(!form_element.is('input')){
                    form_element = form_element.next('select');
                    if(form_element.val()) form_element_value = form_element.val().join(',');
                }
                else{
                    if(form_element.attr('type') == 'checkbox'){
                        if (form_element.is(":checked"))
                        {
                            form_element_value = 1;
                        }
                        else{
                            form_element_value = 0;
                        }

                    }
                }
            }
            if(record_info != undefined && record_info[field_name] != undefined && record_info[field_name] != '' ){
                form_element_value = record_info[field_name];
            }
            if(typeof form_element_value == "undefined") return false;
            var result = thisInstance.checkCondition(form_element_value,comparator,field_value,field_name_changed,field_name);
            if(result){
                is_any = true;
            }
        });
        return is_all && is_any;

    },
    displayByClfOnButtonPopup:function(e){
        var thisInstance = this;
        if(e != undefined){
            var field_name = jQuery(e).attr('name');
            var field_name_changed = field_name;
            var new_value=jQuery(e).val();
        }else{
            field_name_changed = 'clf_details';
            new_value = '';
        }
        var module = app.getModuleName();
        var vtebuttons_id = $('input[name="vtebuttons_id"]').val();
        var params = {
            module : 'ControlLayoutFields',
            action : 'ActionAjax',
            mode : 'checkCLFForModule',
            extension : 'VTEButton',
            vtebuttons_id : vtebuttons_id,
            current_module : module
        };
        app.request.post({'data': params}).then(
            function(err,data){
                if(err === null) {
                    if(!jQuery.isEmptyObject(data)){
                        var role_id = data.role_id;
                        var record_info = data.record_info;
                        jQuery.each(data.clf_info,function(k,v){
                            var all_condition = v.condition.all;
                            var any_condition = v.condition.any;
                            var actions = v.actions;
                            var condition_key = k;
                            var check_condition = thisInstance.checkConditionToButtonPopupForm(all_condition,any_condition,field_name_changed,record_info,role_id,new_value);
                            if(check_condition){
                                jQuery.each(actions,function(key,value){
                                    var field_name_action = value.field;
                                    var form_element = jQuery('#vteButtonQuickEdit').find('[name="'+field_name_action+'"]');
                                    //for Multiple Value control
                                    if(form_element.attr('type') == 'hidden'){
                                        form_element = jQuery('#vteButtonQuickEdit').find('[name="'+field_name_action+'[]"]');
                                        if(form_element.length === 0){
                                            //for reference control uitype 10
                                            form_element = jQuery('#vteButtonQuickEdit').find('[name="'+field_name_action+'_display"]');

                                            if(form_element.length === 0){
                                                //for Checkbox control
                                                form_element = jQuery('#vteButtonQuickEdit').find('[name="'+field_name_action+'"]').last();
                                            }
                                        }
                                    }
                                    var data_info = form_element.data('fieldname');
                                    var this_td = form_element.closest('td');
                                    if(value.option == 'mandatory'){
                                        this_td.show();
                                        this_td.prev().show();
                                        form_element.attr('data-rule-required','true');
                                        form_element.addClass(condition_key+'-clf-mandatory');
                                        var field_label = form_element.closest('td').prev();
                                        if(!field_label.find('span').length) field_label.append('<span class="redColor">*</span>');
                                    }else if(value.option == 'read_only'){
                                        this_td.show();
                                        this_td.prev().show();
                                        form_element.attr('readonly','readonly');
                                        //form_element.attr('disabled','disabled');
                                        form_element.css('background','rgb(235, 235, 228)');
                                        form_element.addClass(condition_key+'-clf-read-only');
                                        if (typeof data_info != 'undefined'){
                                            if(data_info.type == 'reference'){
                                                var parent_span = form_element.closest('span');
                                                parent_span.find('div:first').hide();
                                            }else if(data_info.type == 'multipicklist'){
                                                form_element.select2('disable');
                                            }
                                        }
                                    }else if(value.option == 'hide'){
                                        form_element.addClass(condition_key+'-clf-hide');
                                        this_td.hide();
                                        this_td.prev().hide();
                                        var this_tr = this_td.closest('tr');
                                        thisInstance.hideTr(this_tr);
                                    }
                                    if(form_element.is('select')) form_element.trigger('liszt:updated');
                                    //END
                                });
                            }
                            else{
                                jQuery.each(actions,function(key,value){
                                    var field_name_action = value.field;
                                    var form_element = jQuery('#vteButtonQuickEdit').find('[name="'+field_name_action+'"]');
                                    //for Multiple Value control
                                    if(form_element.attr('type') == 'hidden'){
                                        form_element = jQuery('#vteButtonQuickEdit').find('[name="'+field_name_action+'[]"]');
                                        if(form_element.length === 0){
                                            //for reference control uitype 10
                                            form_element = jQuery('#vteButtonQuickEdit').find('[name="'+field_name_action+'_display"]');
                                            if(form_element.length === 0){
                                                //for Checkbox control
                                                form_element = jQuery('#vteButtonQuickEdit').find('[name="'+field_name_action+'"]').last();
                                            }
                                        }
                                    }
                                    var data_info = form_element.data('fieldinfo');
                                    if(form_element.hasClass(condition_key+'-clf-mandatory')){
                                        form_element.removeAttr('data-rule-required');
                                        form_element.removeAttr('aria-required');
                                        form_element.removeAttr('aria-invalid');
                                        form_element.removeAttr('data-rule-illegal');
                                        form_element.removeClass(condition_key+'-clf-mandatory');
                                    }
                                    if(form_element.hasClass(condition_key+'-clf-read-only')){
                                        form_element.removeAttr('readonly');
                                        form_element.removeAttr('disabled');
                                        form_element.css('background','white');
                                        form_element.removeClass(condition_key+'-clf-read-only');
                                        if (typeof data_info != 'undefined'){
                                            if(data_info.type == 'reference'){
                                                var parent_span = form_element.closest('span');
                                                parent_span.find('div:first').show();
                                            }else if(data_info.type == 'multipicklist'){
                                                form_element.select2('enable');
                                            }
                                        }
                                    }
                                    if(form_element.hasClass(condition_key+'-clf-hide')){
                                        form_element.removeClass(condition_key+'-clf-hide');
                                        var this_td = form_element.closest('td');
                                        //this_td.find('div:first').show();
                                        this_td.show();
                                        this_td.children().show();
                                        this_td.prev().show();
                                        this_td.prev().children().show();
                                        if(form_element.hasClass('chzn-select') || form_element.hasClass('select2')) form_element.hide();
                                        // Handler for custom upload field
                                        if(this_td.find('#frm_'+field_name_action).length > 0) {
                                            form_element.hide();
                                        }
                                        var this_tr = this_td.closest('tr');
                                        thisInstance.hideTr(this_tr);
                                    }
                                    if(form_element.is('select')) form_element.trigger('liszt:updated');
                                });
                                //END
                            }
                            condition_key++;
                        });
                    }
                }
            }
        );
    },
    registerVTEButtonPopupEvents:function(){
        var thisInstance = this;
        thisInstance.displayByClfOnButtonPopup();
        var vteButtonQuickEditForm = $('#vteButtonQuickEdit');
        vteButtonQuickEditForm.on("change","input,select,textarea", function () {
            thisInstance.displayByClfOnButtonPopup(this);
        });
    }
    //1205661 END
});
jQuery(document).ready(function(){
    // Only load when loadHeaderScript=1 BEGIN #241208
    var vtetabdonotworking = false;
    var vterbldonotworking = false;
    var test = false;
    if (typeof VTECheckLoadHeaderScript == 'function') {
        if (!VTECheckLoadHeaderScript('ControlLayoutFields')) {
            return;
        }
        if (!VTECheckLoadHeaderScript('VTETabs')) {
            vtetabdonotworking = true;
        }
        if (!VTECheckLoadHeaderScript('RelatedBlocksLists')) {
            vterbldonotworking = true;
        }
    }
    // Only load when loadHeaderScript=1 END #241208

    var clfInstance = new Control_Layout_Fields_Js();
    clfInstance.registerEvents();
    var view = app.view();
    var module = app.getModuleName();
	var ignore_module = ["Workflows"];
    if($.inArray(module,ignore_module) !== -1) return;
    var vtetabs = jQuery("script[src*='VTETabs.js']");
    var relatedBlocksLists = jQuery("script[src*='RelatedBlocksLists.js']");
    if(view == 'Edit'){
        clfInstance.displayByClf(module,false,'');
        clfInstance.registerFormChange(module);
        $('input.sourceField').each(function(){
            var field_name_changed = jQuery(this).attr('name');
            jQuery(this).on(Vtiger_Edit_Js.referenceSelectionEvent, function(e, data){
                var new_value = data['selectedName'];
                clfInstance.displayByClf(module,field_name_changed,new_value);
                clfInstance.displayByClf(module,field_name_changed,new_value);
            });
        });
    }
    if(view == 'Detail' && ((vtetabs.length == 0) || ( vtetabs.length == 1 && vtetabdonotworking ))){
        var url = window.location.href.split('?');
        var array_url = clfInstance.getQueryParams(url[1]);
        if(typeof array_url == 'undefined') return false;
        var request_mode = array_url.requestMode;
        var record_id = jQuery('#recordId').val();
        clfInstance.displayByClfOnDetail(module,request_mode,record_id);
        clfInstance.registerInlineAjaxSaveClickEvent();
    }
});
// Listen post ajax event for add product action
jQuery( document ).ajaxComplete(function(event, xhr, settings) {
    // Only load when loadHeaderScript=1 BEGIN #241208
    if (typeof VTECheckLoadHeaderScript == 'function') {
        if (!VTECheckLoadHeaderScript('ControlLayoutFields') && !VTECheckLoadHeaderScript('RelatedBlocksLists')) {
            return;
        }
    }
    // Only load when loadHeaderScript=1 END #241208

    var url = settings.data;
    if(typeof url == 'undefined' && settings.url) url = settings.url;
    var instance = new Control_Layout_Fields_Js();
    var top_url = window.location.href.split('?');
    var array_url = instance.getQueryParams(top_url[1]);
    if(typeof array_url == 'undefined') return false;
    var other_url = instance.getQueryParams(url);
    var request_mode = array_url.requestMode;
    if(array_url.view == 'Detail' && other_url.action == 'SaveAjax'){
        var modified_field = other_url.field;
        var is_on_condition = false;
        var moduleName = other_url.module;
        var hd_clf_info = jQuery('#hd_clf_info_' + moduleName).val();
        if(hd_clf_info){
            var list_condition = jQuery.parseJSON(hd_clf_info);
            jQuery.each(list_condition,function(key,value){
                var condition_all = value.condition.all;
                var condition_any = value.condition.any;
                jQuery.each(condition_all,function(index,val){
                    if(val.columnname == modified_field){
                        is_on_condition = true;
                        return false;
                    }
                });
                jQuery.each(condition_any,function(index,val){
                    if(val.columnname == modified_field){
                        is_on_condition = true;
                        return false;
                    }
                });
            });
        }
        if(is_on_condition){
            var link_active = jQuery('ul.nav').find('li.active');
            var record_id = other_url.record;
            //START
            //TASKID: 1030263 - DEV: tuan@vtexperts.com - DATE: 10/01/2018
            //NOTES: For working with RBL
            var relatedblockslists_records = $(document).find('[data-id="'+record_id+'"]');
            var new_value = other_url.value;
            if(relatedblockslists_records.length > 0){
                var block_id = relatedblockslists_records.closest('.relatedblockslists_records').data('block-id');
                var module_reference = other_url.module;
                instance.displayByClfOnDetail(module_reference,request_mode,record_id,block_id,new_value);
            }else{
                $('.tab-item.active').trigger('click');
            }
            //END
        }
    }
    else if(array_url.view == 'Detail'&& other_url.mode == 'showDetailViewByMode'){
        var record_id = jQuery('#recordId').val();
        instance.displayByClfOnDetail(array_url.module,request_mode,record_id);
        instance.registerInlineAjaxSaveClickEvent();
    }
    else{
        //Working with VTETabs
        if(other_url.module == 'VTETabs' && other_url.view == 'Edit' && other_url.mode == 'showModuleEditView') {
            instance.displayByClf(array_url.module,false);
            instance.displayByClf(array_url.module,false);
            instance.registerFormChange(array_url.module);
        }
        if(other_url.module == 'VTETabs' && other_url.view == 'DetailViewAjax' && other_url.mode == 'showModuleDetailView') {
            var record_id = jQuery('#recordId').val();
            instance.displayByClfOnDetail(array_url.module,request_mode,record_id);
            instance.registerInlineAjaxSaveClickEvent();
        }
        //START
        //TASKID: 1030263 - DEV: tuan@vtexperts.com - DATE: 27/09/2018
        //NOTES: For working with RBL
        if(other_url.module == 'RelatedBlocksLists' && other_url.view == 'MassActionAjax' && other_url.mode == 'generateEditView') {
            var blockid = other_url.blockid;
            var relatedblockslists_records = $(document).find('div.relatedblockslists' + blockid);
            if(relatedblockslists_records.length > 0){
                var module = relatedblockslists_records.data('rel-module');
                instance.displayByClf(module,false,'',blockid);
                instance.registerFormChange(module);
            }
        }
        if(other_url.module == 'RelatedBlocksLists' && other_url.view == 'MassActionAjax' &&  other_url.mode == 'generateDetailView') {
            var record_id = other_url.record;
            var blockid = other_url.blockid;
            var relatedblockslists_records = $(document).find('div.relatedblockslists' + blockid);
            if(relatedblockslists_records.length > 0){
                var module = relatedblockslists_records.data('rel-module');
                instance.displayByClfOnDetail(module,request_mode,record_id,blockid,"");
                instance.registerInlineAjaxSaveClickEvent();
                instance.registerFormChange(module);
            }
        }
        //END
    }
    //1205661 BEGIN
    // make ConditionLayout work witd VTEButton popup
    //pham@vtexperts.com
    if(Object.prototype.toString.call(url) =='[object String]' && url.indexOf('module=VTEButtons') != -1 && url.indexOf('view=QuickEditAjax') != -1){
        var clfInstance = new Control_Layout_Fields_Js();
        clfInstance.registerVTEButtonPopupEvents();
    }
    //1205661 END
});

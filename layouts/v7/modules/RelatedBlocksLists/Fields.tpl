{*/* ********************************************************************************
* The content of this file is subject to the Related Blocks & Lists ("License");
* You may not use this file except in compliance with the License
* The Initial Developer of the Original Code is VTExperts.com
* Portions created by VTExperts.com. are Copyright(C) VTExperts.com.
* All Rights Reserved.
* ****************************************************************************** */*}

<label class="col-sm-4 control-label fieldLabel">
    <strong>{vtranslate('LBL_FIELDS',$QUALIFIED_MODULE)}</strong>
    <span class="redColor">*</span>
</label>
<div class="fieldValue col-lg-3 col-md-3 col-sm-3 input-group">
    <select class="select2" id="selected_fields" multiple="true" data-rule-required="true" name="fields[]" style="width: 100%">
        {foreach from=$FIELDS key=FIELD_NAME item=FIELD_DATA}
            {if $FIELD_DATA->isEditable()}
            <option {if in_array($FIELD_DATA->getName(), $BLOCK_DATA['fields'])} selected="" {/if} value="{$FIELD_DATA->getName()}" data-id={$FIELD_DATA->get('id')} data-fieldname="{$FIELD_DATA->getName()}">{vtranslate($FIELD_DATA->get('label'),$SELECTED_MODULE_NAME)}</option>
            {/if}
        {/foreach}
    </select>
    <input type="hidden" name="selectedFieldsList" />
    <input type="hidden" name="topFieldIdsList" value='{ZEND_JSON::encode($BLOCK_DATA['fields'])}' />
</div>

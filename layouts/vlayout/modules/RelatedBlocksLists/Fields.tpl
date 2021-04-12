{*/* ********************************************************************************
* The content of this file is subject to the Related Blocks & Lists ("License");
* You may not use this file except in compliance with the License
* The Initial Developer of the Original Code is VTExperts.com
* Portions created by VTExperts.com. are Copyright(C) VTExperts.com.
* All Rights Reserved.
* ****************************************************************************** */*}

<div>
    <label class="muted control-label">
        &nbsp;<strong>{vtranslate('LBL_FIELDS',$QUALIFIED_MODULE)}</strong>
    </label>
    <div class="controls row-fluid">
        <select class="select2 span8" id="selected_fields" multiple="true" name="fields[]">
            {foreach from=$FIELDS key=FIELD_NAME item=FIELD_DATA}
                {if $FIELD_DATA->isEditable()}
                <option {if in_array($FIELD_DATA->getName(), $BLOCK_DATA['fields'])} selected="" {/if} value="{$FIELD_DATA->getName()}" data-id={$FIELD_DATA->get('id')} data-fieldname="{$FIELD_DATA->getName()}">{vtranslate($FIELD_DATA->get('label'),$SELECTED_MODULE_NAME)}</option>
                {/if}
            {/foreach}
        </select>
    </div>
    <input type="hidden" name="selectedFieldsList" />
    <input type="hidden" name="topFieldIdsList" value='{ZEND_JSON::encode($BLOCK_DATA['fields'])}' />
</div>
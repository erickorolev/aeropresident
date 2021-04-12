{*/* ********************************************************************************
* The content of this file is subject to the Related Blocks & Lists ("License");
* You may not use this file except in compliance with the License
* The Initial Developer of the Original Code is VTExperts.com
* Portions created by VTExperts.com. are Copyright(C) VTExperts.com.
* All Rights Reserved.
* ****************************************************************************** */*}
{assign var=IS_MODULE_EDITABLE value=$RELMODULE_MODEL->isPermitted('EditView')}
{assign var=IS_MODULE_DELETABLE value=$RELMODULE_MODEL->isPermitted('Delete')}
{assign var=WIDTHTYPE value=$USER_MODEL->get('rowheight')}
<table class="table table-bordered blockContainer showInlineTable equalSplit">
    <tbody>
    <tr>
        {assign var=COUNTER value=0}
        {foreach key=FIELD_NAME item=FIELD_MODEL from=$FIELDS_LIST name=blockfields}
        {if $RELATED_RECORD_MODEL}
            {assign var=FIELD_MODEL value=$FIELD_MODEL->set('fieldvalue',$RELATED_RECORD_MODEL->get($FIELD_MODEL->getFieldName()))}
        {/if}
        {assign var="isReferenceField" value=$FIELD_MODEL->getFieldDataType()}
        {if $FIELD_MODEL->get('uitype') eq "20" or $FIELD_MODEL->get('uitype') eq "19"}
        {if $COUNTER eq '1'}
        <td class="{$WIDTHTYPE}"></td><td class="{$WIDTHTYPE}"></td>
    </tr>
    <tr>
        {assign var=COUNTER value=0}
        {/if}
        {/if}
        {if $COUNTER eq 2}
    </tr>
    <tr>
        {assign var=COUNTER value=1}
        {else}
        {assign var=COUNTER value=$COUNTER+1}
        {/if}
        <td class="fieldLabel {$WIDTHTYPE}">
            {if $isReferenceField neq "reference"}<label class="muted pull-right marginRight10px">{/if}
                {if $FIELD_MODEL->isMandatory() eq true && $isReferenceField neq "reference"} <span class="redColor">*</span> {/if}
                {if $isReferenceField eq "reference"}
                    {assign var="REFERENCE_LIST" value=$FIELD_MODEL->getReferenceList()}
                    {assign var="REFERENCE_LIST_COUNT" value=count($REFERENCE_LIST)}
                    {if $REFERENCE_LIST_COUNT > 1}
                        {assign var="DISPLAYID" value=$FIELD_MODEL->get('fieldvalue')}
                        {assign var="REFERENCED_MODULE_STRUCT" value=$FIELD_MODEL->getUITypeModel()->getReferenceModule($DISPLAYID)}
                        {if !empty($REFERENCED_MODULE_STRUCT)}
                            {assign var="REFERENCED_MODULE_NAME" value=$REFERENCED_MODULE_STRUCT->get('name')}
                        {/if}
                        <span class="pull-right">
                                        {if $FIELD_MODEL->isMandatory() eq true} <span class="redColor">*</span> {/if}
                            <select id="{$RELMODULE_NAME}_editView_fieldName_{$FIELD_MODEL->getName()}_dropDown" class="chzn-select referenceModulesList streched" style="width:160px;">
                                <optgroup>
                                    {foreach key=index item=value from=$REFERENCE_LIST}
                                        <option value="{$value}" {if $value eq $REFERENCED_MODULE_NAME} selected {/if}>{vtranslate($value, $RELMODULE_NAME)}</option>
                                    {/foreach}
                                </optgroup>
                            </select>
                                    </span>
                    {else}
                        <label class="muted pull-right marginRight10px">{if $FIELD_MODEL->isMandatory() eq true} <span class="redColor">*</span> {/if}{vtranslate($FIELD_MODEL->get('label'), $RELMODULE_NAME)}</label>
                    {/if}
                {else if $FIELD_MODEL->get('uitype') eq "83"}
                    {include file=vtemplate_path($FIELD_MODEL->getUITypeModel()->getTemplateName(),$RELMODULE_NAME) COUNTER=$COUNTER MODULE=$RELMODULE_NAME}
                {else}
                    {vtranslate($FIELD_MODEL->get('label'), $RELMODULE_NAME)}
                {/if}
                {if $isReferenceField neq "reference"}</label>{/if}
        </td>
        {if $FIELD_MODEL->get('uitype') neq "83"}
            <td class="fieldValue {$WIDTHTYPE}" {if $FIELD_MODEL->get('uitype') eq '19' or $FIELD_MODEL->get('uitype') eq '20'} colspan="3" {assign var=COUNTER value=$COUNTER+1} {/if}>
                <div class="row-fluid">
                            <span class="span10">
                                {include file=vtemplate_path($FIELD_MODEL->getUITypeModel()->getTemplateName(),$RELMODULE_NAME) BLOCK_FIELDS=$FIELDS_LIST MODULE_MODEL =$RELMODULE_MODEL}
                            </span>
                </div>
            </td>
        {/if}
        {if $FIELDS_LIST|@count eq 1 and $FIELD_MODEL->get('uitype') neq "19" and $FIELD_MODEL->get('uitype') neq "20" and $FIELD_MODEL->get('uitype') neq "30" and $FIELD_MODEL->get('name') neq "recurringtype"}
            <td class="{$WIDTHTYPE}"></td><td class="{$WIDTHTYPE}"></td>
        {/if}
        {if $RELMODULE_NAME eq 'Events' && $BLOCK_LABEL eq 'LBL_EVENT_INFORMATION' && $smarty.foreach.blockfields.last }
            {include file=vtemplate_path('uitypes/FollowUp.tpl',$RELMODULE_NAME) COUNTER=$COUNTER}
        {/if}
        {/foreach}
        {* adding additional column for odd number of fields in a block *}
        {if $FIELDS_LIST|@end eq true and $FIELDS_LIST|@count neq 1 and $COUNTER eq 1}
            <td class="fieldLabel {$WIDTHTYPE}"></td><td class="{$WIDTHTYPE}"></td>
        {/if}
    </tr>
    {*<tr>
        <td colspan="4">
            <button style="margin-right:60px;" class="btn btn-danger pull-right relatedBtnDelete" data-record-id="{$RELATED_RECORD_MODEL->getId()}" data-rel-module="{$RELMODULE_NAME}" type="button"><strong><i class="icon-minus"></i> </strong></button>
        </td>
    </tr>*}
    </tbody>
</table>
<div class="row-fluid relatedRecordActions" style="background-color: #cccccc;padding: 2px 0px">
    {if ($IS_MODULE_DELETABLE && $RELATED_RECORD_MODEL->getId() neq '') || $RELATED_RECORD_MODEL->getId() eq ''}
        <a class="relatedBtnDelete pull-right" data-rel-module="{$RELMODULE_NAME}" data-record-id="{$RELATED_RECORD_MODEL->getId()}" style="margin-right:25px; color: #0088cc">{vtranslate('LBL_DELETE')}</a>
    {/if}
    {if $smarty.request.modeView eq 'Detail'}
        <a class="relatedBtnSave pull-right" data-block-id="{$BLOCKID}" style="margin-right:40px; color: #0088cc">{vtranslate('LBL_SAVE')}</a>
    {/if}
    {*<a class="relatedBtnView pull-right" style="margin-right:50px; color: #0088cc">{vtranslate('LBL_VIEW','RelatedBlocksLists')}</a>*}
</div>
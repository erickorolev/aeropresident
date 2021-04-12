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
<table class="table table-borderless">
    <tr>
        {assign var=COUNTER value=0}
        {foreach key=FIELD_NAME item=FIELD_MODEL from=$FIELDS_LIST name=blockfields}
        {if $RELATED_RECORD_MODEL}
            {assign var=FIELD_MODEL value=$FIELD_MODEL->set('fieldvalue',$RELATED_RECORD_MODEL->get($FIELD_MODEL->getFieldName()))}
        {/if}
        {assign var="isReferenceField" value=$FIELD_MODEL->getFieldDataType()}
        {assign var="refrenceList" value=$FIELD_MODEL->getReferenceList()}
        {assign var="refrenceListCount" value=count($refrenceList)}
        {if $FIELD_MODEL->isEditable() eq true}
        {if $FIELD_MODEL->get('uitype') eq "19"}
        {if $COUNTER eq '1'}
        <td></td><td></td></tr><tr>
        {assign var=COUNTER value=0}
        {/if}
        {/if}
        {if $COUNTER eq 2}
    </tr><tr>
        {assign var=COUNTER value=1}
        {else}
        {assign var=COUNTER value=$COUNTER+1}
        {/if}
        <td class="fieldLabel alignMiddle">
            {if $isReferenceField eq "reference"}
                {if $refrenceListCount > 1}
                    {assign var="DISPLAYID" value=$FIELD_MODEL->get('fieldvalue')}
                    {assign var="REFERENCED_MODULE_STRUCTURE" value=$FIELD_MODEL->getUITypeModel()->getReferenceModule($DISPLAYID)}
                    {if !empty($REFERENCED_MODULE_STRUCTURE)}
                        {assign var="REFERENCED_MODULE_NAME" value=$REFERENCED_MODULE_STRUCTURE->get('name')}
                    {/if}
                    <select style="width: 140px;" class="select2 referenceModulesList">
                        {foreach key=index item=value from=$refrenceList}
                            <option value="{$value}" {if $value eq $REFERENCED_MODULE_NAME} selected {/if}>{vtranslate($value, $value)}</option>
                        {/foreach}
                    </select>
                {else}
                    {vtranslate($FIELD_MODEL->get('label'), $MODULE)}
                {/if}
            {else if $FIELD_MODEL->get('uitype') eq "83"}
                {include file=vtemplate_path($FIELD_MODEL->getUITypeModel()->getTemplateName(),$MODULE) COUNTER=$COUNTER MODULE=$MODULE}
                {if $TAXCLASS_DETAILS}
                    {assign 'taxCount' count($TAXCLASS_DETAILS)%2}
                    {if $taxCount eq 0}
                        {if $COUNTER eq 2}
                            {assign var=COUNTER value=1}
                        {else}
                            {assign var=COUNTER value=2}
                        {/if}
                    {/if}
                {/if}
            {else}
                {if $MODULE eq 'Documents' && $FIELD_MODEL->get('label') eq 'File Name'}
                    {assign var=FILE_LOCATION_TYPE_FIELD value=$RECORD_STRUCTURE['LBL_FILE_INFORMATION']['filelocationtype']}
                    {if $FILE_LOCATION_TYPE_FIELD}
                        {if $FILE_LOCATION_TYPE_FIELD->get('fieldvalue') eq 'E'}
                            {vtranslate("LBL_FILE_URL", $MODULE)}&nbsp;<span class="redColor">*</span>
                        {else}
                            {vtranslate($FIELD_MODEL->get('label'), $MODULE)}
                        {/if}
                    {else}
                        {vtranslate($FIELD_MODEL->get('label'), $MODULE)}
                    {/if}
                {else}
                    {vtranslate($FIELD_MODEL->get('label'), $MODULE)}
                {/if}
            {/if}
            &nbsp;{if $FIELD_MODEL->isMandatory() eq true} <span class="redColor">*</span> {/if}
        </td>
        {if $FIELD_MODEL->get('uitype') neq '83'}
            <td class="fieldValue" {if $FIELD_MODEL->getFieldDataType() eq 'boolean'} style="width:25%" {/if} {if $FIELD_MODEL->get('uitype') eq '19'} colspan="3" {assign var=COUNTER value=$COUNTER+1} {/if}>
                {assign var = FIELD_NAME value= $FIELD_MODEL->getName()}
                {if empty($FIELD_MODEL->get('fieldvalue'))}
                    {assign var=FIELD_MODEL_CLONE value=$FIELD_MODEL->set('fieldvalue',$FIELD_MODEL->get('defaultvalue'))}
                {else}
                    {assign var=FIELD_MODEL_CLONE value=$FIELD_MODEL}
                {/if}

                {include file=vtemplate_path($FIELD_MODEL_CLONE->getUITypeModel()->getTemplateName(),$MODULE) BLOCK_FIELDS=$FIELDS_LIST FIELD_NAME =$FIELD_NAME}
            </td>
        {/if}
        {/if}
        {/foreach}
        {*If their are odd number of fields in edit then border top is missing so adding the check*}
        {if $COUNTER is odd}
            <td></td>
            <td></td>
        {/if}
    </tr>
</table>
<div class="row relatedRecordActions" style="background-color: #EFEFEF;padding: 5px 0px;margin: 0;">
    {if ($IS_MODULE_DELETABLE && $RELATED_RECORD_MODEL->getId() neq '') || $RELATED_RECORD_MODEL->getId() eq ''}
        <a class="relatedBtnDelete pull-right" data-rel-module="{$RELMODULE_NAME}" data-record-id="{$RELATED_RECORD_MODEL->getId()}" style="margin-right:25px; color: #0088cc">{vtranslate('LBL_DELETE')}</a>
    {/if}
    {if $smarty.request.modeView eq 'Detail'}
        <a class="relatedBtnSave pull-right" data-block-id="{$BLOCKID}" style="margin-right:40px; color: #0088cc">{vtranslate('LBL_SAVE')}</a>
    {/if}
    {*<a class="relatedBtnView pull-right" style="margin-right:50px; color: #0088cc">{vtranslate('LBL_VIEW','RelatedBlocksLists')}</a>*}
</div>
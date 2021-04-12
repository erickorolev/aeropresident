{*/* ********************************************************************************
* The content of this file is subject to the Related Blocks & Lists ("License");
* You may not use this file except in compliance with the License
* The Initial Developer of the Original Code is VTExperts.com
* Portions created by VTExperts.com. are Copyright(C) VTExperts.com.
* All Rights Reserved.
* ****************************************************************************** */*}

{assign var=WIDTHTYPE value=$USER_MODEL->get('rowheight')}
{assign var=IS_MODULE_EDITABLE value=$RELMODULE_MODEL->isPermitted('EditView')}
{assign var=IS_MODULE_DELETABLE value=$RELMODULE_MODEL->isPermitted('Delete')}
<input type="hidden" name="relatedblockslists[{$BLOCKID}][{$ROWNO}][module]" value="{$RELMODULE_NAME}"/>
<input type="hidden" name="relatedblockslists[{$BLOCKID}][{$ROWNO}][recordId]" value="{$RELATED_RECORD_MODEL->getId()}"/>
{if $BLOCKTYPE eq 'block'}
    {include file=vtemplate_path('BlockEditFields.tpl',$QUALIFIED_MODULE) RELMODULE_MODEL=$RELMODULE_MODEL RELMODULE_NAME=$RELMODULE_NAME FIELDS_LIST=$FIELDS_LIST RELATED_RECORD_MODEL=$RELATED_RECORD_MODEL BLOCKID=$BLOCKID}
{else}
    {foreach item=FIELD_MODEL from=$FIELDS_LIST name=fields_list_data}
        {assign var=LAST_FIELD value=$FIELD_MODEL@last}
        {assign var=FIELD_MODEL value=$FIELD_MODEL->set('fieldvalue',$RELATED_RECORD_MODEL->get($FIELD_MODEL->getFieldName()))}
        {if $FIELD_MODEL->isEditable() eq 'true'}
            <td class="fieldValue">
                {include file=vtemplate_path($FIELD_MODEL->getUITypeModel()->getTemplateName(),$RELMODULE_NAME) BLOCK_FIELDS=$FIELDS_LIST MODULE=$RELMODULE_NAME}
            </td>
        {/if}
    {/foreach}

    <td>
        <div class="actions pull-right" style="padding-top:7px; padding-right:10px;">
            {if $IS_MODULE_DELETABLE}
                &nbsp;<a class="relatedBtnDelete" data-record-id="{$RELATED_RECORD_MODEL->getId()}" data-rel-module="{$RELMODULE_NAME}"><i title="{vtranslate('LBL_DELETE', $MODULE)}" class="icon-trash alignMiddle"></i></a>
            {/if}
        </div>
    </td>
{/if}
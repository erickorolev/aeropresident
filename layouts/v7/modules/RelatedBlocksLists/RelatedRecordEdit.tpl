{*/* ********************************************************************************
* The content of this file is subject to the Related Blocks & Lists ("License");
* You may not use this file except in compliance with the License
* The Initial Developer of the Original Code is VTExperts.com
* Portions created by VTExperts.com. are Copyright(C) VTExperts.com.
* All Rights Reserved.
* ****************************************************************************** */*}
{assign var=WIDTHTYPE value=$USER_MODEL->get('rowheight')}
{assign var=CUSTOMIZABLE_OPTIONS value = RelatedBlocksLists_Module_Model::getCustomizableOptionsForBlock($BLOCKID)}
{assign var=IS_MODULE_VIEWABLE value= $RELMODULE_MODEL->isPermitted('View') && $CUSTOMIZABLE_OPTIONS->chk_edit_view_icon}
{assign var=IS_MODULE_EDITABLE value= $RELMODULE_MODEL->isPermitted('EditView') && $CUSTOMIZABLE_OPTIONS->chk_edit_edit_icon}
{assign var=IS_MODULE_DELETABLE value=$RELMODULE_MODEL->isPermitted('Delete')  && $CUSTOMIZABLE_OPTIONS->chk_edit_delete_icon}
{if $BLOCKTYPE eq 'list'}<td class="hide">{/if}
    <input type="hidden" name="relatedblockslists[{$BLOCKID}][{$ROWNO}][module]" value="{$RELMODULE_NAME}"/>
    <input type="hidden" name="relatedblockslists[{$BLOCKID}][{$ROWNO}][recordId]" value="{$RELATED_RECORD_MODEL->getId()}"/>
{if $BLOCKTYPE eq 'list'}</td>{/if}
{if $BLOCKTYPE eq 'block'}
    {include file=vtemplate_path('BlockEditFields.tpl',$QUALIFIED_MODULE) RELMODULE_MODEL=$RELMODULE_MODEL RELMODULE_NAME=$RELMODULE_NAME FIELDS_LIST=$FIELDS_LIST RELATED_RECORD_MODEL=$RELATED_RECORD_MODEL BLOCKID=$BLOCKID}
{else}
    {if $IS_MODULE_VIEWABLE || ($IS_MODULE_EDITABLE && $CUSTOMIZABLE_OPTIONS->chk_detail_edit_icon) || $IS_MODULE_DELETABLE}
        <td>
            <div class="actions pull-right" style="padding-top:7px; padding-right:3px;width: {if $IS_MODULE_VIEWABLE && $IS_MODULE_EDITABLE && $IS_MODULE_DELETABLE}76{else}{if ($IS_MODULE_VIEWABLE && $IS_MODULE_EDITABLE) || ($IS_MODULE_VIEWABLE && $IS_MODULE_DELETABLE) || ($IS_MODULE_EDITABLE && $IS_MODULE_DELETABLE)}53{else}30{/if}{/if}px;">
                {if $IS_MODULE_VIEWABLE}
                    <a target="_blank" href="index.php?module={$RELMODULE_NAME}&amp;view=Detail&amp;record={$RELATED_RECORD_MODEL->getId()}&amp;mode=showDetailViewByMode&amp;requestMode=full">
                        <i class="fa fa-eye icon alignMiddle" title="Complete Details"></i>
                    </a>&nbsp;&nbsp;
                {/if}
                {if $IS_MODULE_EDITABLE}
                    <a href="index.php?module={$RELMODULE_NAME}&amp;view=Edit&amp;record={$RELATED_RECORD_MODEL->getId()}&sourceModule={$SOURCE_MODULE}&sourceRecord={$SOURCE_RECORD}&relationOperation=true">
                        <i class="fa fa-pencil alignMiddle" title="Edit"></i>
                    </a>&nbsp;&nbsp;
                {/if}
                {if $IS_MODULE_DELETABLE}
                    <a class="relatedBtnDelete" data-record-id="{$RELATED_RECORD_MODEL->getId()}" data-rel-module="{$RELMODULE_NAME}">
                        <i title="{vtranslate('LBL_DELETE', $MODULE)}" class="fa fa-trash"></i>
                    </a>
                {/if}
            </div>
        </td>
    {/if}
    {foreach item=FIELD_MODEL from=$FIELDS_LIST name=fields_list_data}
        {assign var=LAST_FIELD value=$FIELD_MODEL@last}
        {if $RELATED_RECORD_MODEL->get($FIELD_MODEL->getFieldName())!=''}
            {assign var=FIELD_MODEL value=$FIELD_MODEL->set('fieldvalue',$RELATED_RECORD_MODEL->get($FIELD_MODEL->getFieldName()))}
        {else}
            {if $BLOCKDATA['filtervalue']!='' && $FIELD_MODEL->getFieldName() == $BLOCKDATA['filterfield']}
                {assign var=FIELD_MODEL value=$FIELD_MODEL->set('fieldvalue',$BLOCKDATA['filtervalue'])}
            {else}
                {assign var=FIELD_MODEL value=$FIELD_MODEL->set('fieldvalue','')}
            {/if}
        {/if}
        {if $FIELD_MODEL->isEditable() eq 'true'}
            <td class="fieldValue}" data-field-width="{RelatedBlocksLists_Module_Model::getWidthForField($FIELD_MODEL->getName(),$BLOCKID)}" data-field-type="{$FIELD_MODEL->getFieldDataType()}">
                {assign var=FIELD_TABID value=RelatedBlocksLists_Module_Model::getRelatedTabIdForField($FIELD_MODEL->getId())}
                {*{if ($FIELD_MODEL->get('uitype') eq '51' || $FIELD_MODEL->get('uitype') eq '10') && ($CURRENT_TABID eq $FIELD_TABID)}
                    {$RECORD_MODEL ->getDisplayValue($FIELD_MODEL->getName())}
                {else}*}
                    {if $RELMODULE_NAME=='Documents' && $FIELD_MODEL->get('name')=='filename'}
                        {include file=vtemplate_path($FIELD_MODEL->getUITypeModel()->getTemplateName(),'RelatedBlocksLists') BLOCK_FIELDS=$FIELDS_LIST MODULE=$RELMODULE_NAME}
                    {else}
                        {include file=vtemplate_path($FIELD_MODEL->getUITypeModel()->getTemplateName(),$RELMODULE_NAME) BLOCK_FIELDS=$FIELDS_LIST MODULE=$RELMODULE_NAME}
                    {/if}
                {*{/if}*}
            </td>
        {/if}
    {/foreach}
{/if}
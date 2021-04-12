{*/* ********************************************************************************
* The content of this file is subject to the Related Blocks & Lists ("License");
* You may not use this file except in compliance with the License
* The Initial Developer of the Original Code is VTExperts.com
* Portions created by VTExperts.com. are Copyright(C) VTExperts.com.
* All Rights Reserved.
* ****************************************************************************** */*}
{strip}
    {assign var=WIDTHTYPE value=$USER_MODEL->get('rowheight')}
    {foreach from=$BLOCKS_LIST key=BLOCKID item=BLOCKDATA}
        {assign var="RELMODULE_MODEL" value=$BLOCKDATA['relmodule']}
        {assign var="RELMODULE_NAME" value=$RELMODULE_MODEL->getName()}
        {assign var=IS_MODULE_EDITABLE value=$RELMODULE_MODEL->isPermitted('EditView')}
        {assign var=IS_MODULE_DELETABLE value=$RELMODULE_MODEL->isPermitted('Delete')}
        {assign var="FIELDS_LIST" value=$BLOCKDATA['fields']}
        {assign var="RELATED_RECORDS" value=$BLOCKDATA['data']}
        {assign var="RELATED_STRUCTURE_RECORDS" value=$BLOCKDATA['data_structure']}
        {assign var="RELATED_STRUCTURE_RECORDS_BASE" value=$BLOCKDATA['data_structure_base']}
        {assign var="SELECTED_FIELDS" value=$BLOCKDATA['selected_fields']}
        {assign var="MULTIPICKLIST_FIELDS" value=$BLOCKDATA['multipicklist_fields']}
        {assign var="REFERENCE_FIELDS" value=$BLOCKDATA['reference_fields']}
        {assign var=IS_MODULE_DELETABLE value=$RELMODULE_MODEL->isPermitted('Delete')}
        <br>

        <table class="table table-bordered blockContainer showInlineTable equalSplit" data-block-id="{$BLOCKID}">
            <thead>
            <tr>
                <th class="blockHeader" colspan="4">
                    <div class="row-fluid">
                        <span class="span6 RBLHeader">
                            {vtranslate('LBL_RELATED', 'RelatedBlocksLists')} {if $BLOCKDATA['type'] eq 'block'}{vtranslate('LBL_BLOCK', 'RelatedBlocksLists')}{else}{vtranslate('LBL_LIST', 'RelatedBlocksLists')}{/if} : {vtranslate($RELMODULE_NAME, $RELMODULE_NAME)}
                        </span>
                        {if $IS_MODULE_EDITABLE}
                        <span class="span6 relatedViewActions">
                            <style>
                                .vtGlyph{
                                    vertical-align: baseline !important;
                                }
                            </style>
                            {include file=vtemplate_path('RelatedViewActions.tpl',$QUALIFIED_MODULE) PAGE_INFO =$BLOCKDATA['page_info'] moduleName = $RELMODULE_NAME }
                        </span>
                        {/if}
                    </div>
                </th>
            </tr>
            </thead>
        </table>
        {if $IS_MODULE_EDITABLE}
        <div class="relatedblockslists_records relatedblockslists{$BLOCKID}" data-block-id="{$BLOCKID}" data-rel-module="{$RELMODULE_NAME}">
            <input type="hidden" id="selected_fields{$BLOCKID}" value="{$SELECTED_FIELDS}"/>
            <input type="hidden" id="multipicklist_fields{$BLOCKID}" value="{$MULTIPICKLIST_FIELDS}"/>
            <input type="hidden" id="reference_fields{$BLOCKID}" value="{$REFERENCE_FIELDS}"/>
            {if $BLOCKDATA['type'] eq 'block'}
                {foreach from=$RELATED_RECORDS item=RELATED_RECORD_MODEL name=related_records_block key = index}
                    <div class="relatedRecords"  data-row-no="{$smarty.foreach.related_records_block.iteration}" data-id = "{$RELATED_RECORD_MODEL->getId()}">
                        {include file=vtemplate_path('RelatedRecordEdit.tpl',$QUALIFIED_MODULE) RECORD_STRUCTURE_MODEL=$RELATED_STRUCTURE_RECORDS.$index RELMODULE_MODEL=$RELMODULE_MODEL RELMODULE_NAME=$RELMODULE_NAME FIELDS_LIST=$FIELDS_LIST RELATED_RECORD_MODEL=$RELATED_RECORD_MODEL BLOCKID=$BLOCKID BLOCKTYPE='block' ROWNO=$smarty.foreach.related_records_block.iteration}

                    </div>
                {/foreach}
            {else}
                <table class="table table-bordered table-condensed listViewEntriesTable">
                    <thead>
                        <tr class="listViewHeaders" >
                            {assign var=COUNT value=$FIELDS_LIST|count}
                            {assign var=CELLWIDTH value=95/($COUNT+1)}
                            {foreach item=FIELD_MODEL from=$FIELDS_LIST name=fields_list_header}
                                {if $FIELD_MODEL->isEditable() eq 'true'}
                                    <th class="fieldLabel {$WIDTHTYPE}" {if $FIELD_MODEL@last} colspan="2" style="width:{$CELLWIDTH+5}%;" {else} style="width:{$CELLWIDTH}%;"  {/if} ><strong>{if $FIELD_MODEL->isMandatory() eq true} <span class="redColor">*</span> {/if}{vtranslate($FIELD_MODEL->get('label'), $RELMODULE_NAME)}</strong></th>
                                {/if}
                            {/foreach}
                        </tr>
                    </thead>
                    <tbody>
                    <tr class="relatedRecordsClone hide">
                        {foreach item=FIELD_MODEL from=$FIELDS_LIST name=fields_list_clone}
                            {if $FIELD_MODEL->isEditable() eq 'true'}
                                <td class="fieldValue">
                                    {include file=vtemplate_path($FIELD_MODEL->getUITypeModel()->getTemplateName(),$RELMODULE_NAME) BLOCK_FIELDS=$FIELDS_LIST MODULE=$RELMODULE_NAME RECORD_STRUCTURE_MODEL =  $RELATED_STRUCTURE_RECORDS_BASE}
                                    {*{if $FIELD_MODEL@last}*}
                                        {*<div class="actions pull-right" style="padding-top:7px; padding-right:10px;">*}
                                            {*&nbsp;<a class="relatedBtnDelete"><i title="{vtranslate('LBL_DELETE', $MODULE)}" class="icon-trash alignMiddle"></i></a>*}
                                        {*</div>*}
                                    {*{/if}*}
                                </td>
                            {/if}
                        {/foreach}
                        <td>
                            <div class="actions pull-right" style="padding-top:7px; padding-right:10px;">
                                &nbsp;<a class="relatedBtnDelete"><i title="{vtranslate('LBL_DELETE', $MODULE)}" class="icon-trash alignMiddle"></i></a>
                            </div>
                        </td>
                    </tr>
                    {foreach from=$RELATED_RECORDS item=RELATED_RECORD_MODEL name=related_records_list key = index}
                        <tr class="relatedRecords" data-row-no="{$smarty.foreach.related_records_list.iteration}" data-id = "{$RELATED_RECORD_MODEL->getId()}">
                            {include file=vtemplate_path('RelatedRecordEdit.tpl',$QUALIFIED_MODULE) RELMODULE_MODEL=$RELMODULE_MODEL RELMODULE_NAME=$RELMODULE_NAME FIELDS_LIST=$FIELDS_LIST RELATED_RECORD_MODEL=$RELATED_RECORD_MODEL BLOCKID=$BLOCKID BLOCKTYPE='list' ROWNO=$smarty.foreach.related_records_list.iteration RECORD_STRUCTURE_MODEL =  $RELATED_STRUCTURE_RECORDS.$index}
                        </tr>
                    {/foreach}
                    </tbody>
                </table>
            {/if}
            {*<br>*}
            <div class="row-fluid">
                <div style="text-align: center">
                    {if $IS_MODULE_EDITABLE}
                    <button type="button" class="btn btn-success relatedBtnAddMore" data-rel-module="{$RELMODULE_NAME}" data-block-id="{$BLOCKID}" data-type="{$BLOCKDATA['type']}" style="padding: 0px 10px;{if $BLOCKDATA['type'] eq 'block'}margin-top:-43px;{/if}"><strong>{vtranslate('LBL_CREATE_ANOTHER', 'RelatedBlocksLists')}</strong></button>
                    <button type="button" class="btn btn-success relatedBtnSelectExisting" data-view-mode="edit" data-rel-module="{$RELMODULE_NAME}" data-block-id="{$BLOCKID}" data-type="{$BLOCKDATA['type']}" style="padding: 0px 10px;{if $BLOCKDATA['type'] eq 'block'}margin-top:-43px;{/if}" data-url="module=RelatedBlocksLists&related_module={$RELMODULE_NAME}&src_module={$SOURCE_MODULE}&src_record={$SOURCE_RECORD}&view=Popup" data-record="{$SOURCE_RECORD}"><strong>{vtranslate('Select Existing Record', 'RelatedBlocksLists')}</strong></button>
                    {/if}
                </div>
            </div>
        </div>
        {/if}
    {/foreach}
{/strip}
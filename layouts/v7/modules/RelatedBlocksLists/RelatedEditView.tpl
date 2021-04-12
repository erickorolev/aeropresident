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
        <div class="fieldBlockContainer">
            <div class="clearfix blockContainer" data-block-id="{$BLOCKID}">
                <h4 class="fieldBlockHeader pull-left">{vtranslate($RELMODULE_NAME, $RELMODULE_NAME)} {if $BLOCKDATA['filtervalue'] != '' && $BLOCKDATA['filterfield'] != ''}({$BLOCKDATA['filtervalue']}){/if}</h4>
                {if $IS_MODULE_EDITABLE}
				<span class="pull-right relatedViewActions">
                    <style>
                        .vtGlyph{
                            vertical-align: baseline !important;
                        }
                    </style>
                    {include file=vtemplate_path('RelatedViewActions.tpl',$QUALIFIED_MODULE) PAGE_INFO =$BLOCKDATA['page_info'] moduleName = $RELMODULE_NAME }
                </span>
				{/if}
            </div>
			{if $IS_MODULE_EDITABLE}
            <hr style="margin-top: 1px;">
            <div style="width: 100%;overflow-x: auto;" class="relatedblockslists_records relatedblockslists{$BLOCKID}" data-block-id="{$BLOCKID}" data-rel-module="{$RELMODULE_NAME}">
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
                    <table class="table table-condensed listViewEntriesTable">
                        <thead>
                            <tr class="listViewHeaders" >
                                {assign var=COUNT value=$FIELDS_LIST|count}
                                {assign var=CELLWIDTH value=95/($COUNT+1)}
                                <th class="norwap" style="width: 5%"></th>
                                {foreach item=FIELD_MODEL from=$FIELDS_LIST name=fields_list_header}
                                    {if $FIELD_MODEL->isEditable() eq 'true'}
                                        <th class="norwap" {$WIDTHTYPE}" {if $FIELD_MODEL@last}  style="width:{$CELLWIDTH+5}%;" {else} style="width:{$CELLWIDTH}%;"  {/if} ><span class="listViewContentHeaderValues">{if $FIELD_MODEL->isMandatory() eq true} <span class="redColor">*</span> {/if}{vtranslate($FIELD_MODEL->get('label'), $RELMODULE_NAME)}</span></th>
                                    {/if}
                                {/foreach}
                            </tr>
                        </thead>
                        <tbody>
                        <tr class="relatedRecordsClone hide">
                            <td>
                                <div class="actions pull-right" style="padding-top:7px; padding-right:10px; width: 76px;">
                                    &nbsp;<a class="relatedBtnDelete"><i title="{vtranslate('LBL_DELETE', $MODULE)}" class="fa fa-trash alignMiddle"></i></a>
                                </div>
                            </td>
                            {foreach item=FIELD_MODEL from=$FIELDS_LIST name=fields_list_clone}
                                {if $FIELD_MODEL->isEditable() eq 'true'}
                                    <!--set default value-->
                                    {assign var=FIELD_MODEL_CLONE value=$FIELD_MODEL->set('fieldvalue',$FIELD_MODEL->get('defaultvalue'))}
                                    <td class="fieldValue">
                                        {include file=vtemplate_path($FIELD_MODEL_CLONE->getUITypeModel()->getTemplateName(),$RELMODULE_NAME) BLOCK_FIELDS=$FIELDS_LIST MODULE=$RELMODULE_NAME RECORD_STRUCTURE_MODEL =  $RELATED_STRUCTURE_RECORDS_BASE}
                                        {*{if $FIELD_MODEL@last}*}
                                            {*<div class="actions pull-right" style="padding-top:7px; padding-right:10px;">*}
                                                {*&nbsp;<a class="relatedBtnDelete"><i title="{vtranslate('LBL_DELETE', $MODULE)}" class="icon-trash alignMiddle"></i></a>*}
                                            {*</div>*}
                                        {*{/if}*}
                                    </td>
                                {/if}
                            {/foreach}

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
                <div class="row" style="margin-right: 0px; margin-left: 0px;">
                    <div style="text-align: center">
					{if $IS_MODULE_EDITABLE}
                        <button type="button" class="btn btn-default relatedBtnAddMore" data-rel-module="{$RELMODULE_NAME}" data-block-id="{$BLOCKID}" data-type="{$BLOCKDATA['type']}" style="margin-right: 5px; margin-bottom: 10px;{if $BLOCKDATA['type'] eq 'block'}margin-top:10px;{/if}"><i class="fa fa-plus"></i> {vtranslate('LBL_ADD', $RELMODULE_NAME)} {vtranslate($RELMODULE_NAME, $RELMODULE_NAME)}</button>
					{/if}
                    {if $IS_SELECT_BUTTON eq true}
                        <button type="button" class="btn btn-default relatedBtnSelectExisting" data-view-mode="detail" data-rel-module="{$RELMODULE_NAME}" data-block-id="{$BLOCKID}" data-type="{$BLOCKDATA['type']}" style="margin-right: 5px; margin-bottom: 10px;{if $BLOCKDATA['type'] eq 'block'}margin-top:10px;{/if}" data-url="module=RelatedBlocksLists&related_module={$RELMODULE_NAME}&src_module={$SOURCE_MODULE}&src_record={$SOURCE_RECORD}&view=Popup" data-record="{$SOURCE_RECORD}"><i class="fa fa-search"></i> {vtranslate('Select Existing Record', 'RelatedBlocksLists')}</button>
                    {/if}
                    </div>
                </div>
            </div>
			{/if}
        </div>
    {/foreach}
{/strip}
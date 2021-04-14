{*/* ********************************************************************************
* The content of this file is subject to the Related Blocks & Lists ("License");
* You may not use this file except in compliance with the License
* The Initial Developer of the Original Code is VTExperts.com
* Portions created by VTExperts.com. are Copyright(C) VTExperts.com.
* All Rights Reserved.
* ****************************************************************************** */*}

{strip}

    {foreach from=$BLOCKS_LIST item=BLOCKDATA key=BLOCKID}
        {assign var="RELMODULE_MODEL" value=$BLOCKDATA['relmodule']}
        {assign var="RELMODULE_NAME" value=$RELMODULE_MODEL->getName()}
        {assign var="FIELDS_LIST" value=$BLOCKDATA['fields']}
        <div style="border-radius: 4px 4px 0px 0px;background: white;" data-sequence="{$BLOCKDATA['sequence']}" data-related-block-id="{$BLOCKID}" class="editFieldsTable relatedblock_{$BLOCKID} marginBottom10px border1px related-blockSortable " id="relatedblock_{$BLOCKID}">
            <div class="col-sm-12">
                <div class="layoutBlockHeader row">
                    <div class="blockLabel col-sm-3 padding10 marginLeftZero">
                        <img class="cursorPointerMove" src="{vimage_path('drag.png')}" />&nbsp;&nbsp;
                        <strong class="translatedBlockLabel">{vtranslate($RELMODULE_NAME, $RELMODULE_NAME)} {if $BLOCKDATA['filtervalue'] != '' && $BLOCKDATA['filterfield'] != ''}({$BLOCKDATA['filtervalue']}){/if}</strong>
                    </div>
                    <div class="col-sm-9 padding10 marginLeftZero">
                        <div class="blockActions" style="float:right !important;">
                            <span>
                                <i class="fa fa-info-circle" title="Collapse the block in detail view"></i>&nbsp; Collapse Block&nbsp;
                                <input style="opacity: 0;" type="checkbox" {if $BLOCKDATA['expand'] eq 1}checked{else}{/if} {if $BLOCKDATA['expand'] eq 1}value='1'{else}value='0'{/if}  class ='cursorPointer bootstrap-switch' name="related-blocks-lists-collapseBlock" data-on-text="Yes" data-off-text="No" data-on-color="primary" data-block-id="{$BLOCKID}"/>
                            </span>
                            <button class="blockEditBtn addButton btn btn-default btn-sm" data-related-block-id="{$BLOCKID}" data-url="index.php?module=RelatedBlocksLists&view=MassSettingsAjax&mode=showSettingsForm&type=block&sourceModule={$SOURCE_MODULE}&blockid={$BLOCKID}">{vtranslate('LBL_EDIT')}</button>&nbsp;&nbsp;
                            <button class="blockDeleteBtn addButton btn btn-default btn-sm" data-related-block-id="{$BLOCKID}" data-message-delete="{vtranslate('JS_LBL_ARE_YOU_SURE_YOU_WANT_TO_DELETE', 'RelatedBlocksLists')}">{vtranslate('LBL_DELETE')}</button>
                        </div>
                    </div>
                </div>
            </div>
            <div style="padding:5px;min-height: 27px" class="blockFieldsList row">
                <ul style="list-style-type: none; float: left;min-height: 1px;padding:2px;" class="connectedSortable col-sm-6">
                    {foreach item=FIELD_MODEL from=$FIELDS_LIST name=fieldlist}
                        {if $smarty.foreach.fieldlist.index % 2 eq 0}
                            <li>
                                <div class="row border1px" style="padding: 10px 10px 10px 20px;">
                                    <span class="fieldLabel">
                                        <b>{vtranslate($FIELD_MODEL->get('label'), $RELMODULE_NAME)}</b>&nbsp;
                                        {if $FIELD_MODEL->isMandatory() eq true}<span class="redColor">*</span>{/if}
                                    </span>
                                    <!--<a href="javascript:void(0)" data-source-module="{$RELMODULE_NAME}" data-field-label="{$FIELD_MODEL->get('label')}" data-block-id="{$BLOCKID}" data-field-name="{$FIELD_MODEL->get('name')}" class="related-block-list-editFieldDetails pull-right"><i class="fa fa-pencil" title="Edit"></i></a>-->
                                    {assign var=IS_MANDATORY value=$FIELD_MODEL->isMandatory()}
                                    <div class="row field-width" style="float: right;margin-right: 10px;">
                                        <span class="fieldLabel">
                                            <b>{vtranslate('Witdh', $RELMODULE_NAME)}</b>&nbsp;
                                        </span>
                                        <span style="float: right;margin-left:10px;">
                                            <input name="width" type="text" disabled="disabled" class="inputElement vte_related_block_list_field_width" style="width: 80px;height:21px;" value="{RelatedBlocksLists_Module_Model::getWidthForField($FIELD_MODEL->getName(),$BLOCKID)}" />
                                            <a title="Edit" style="margin-left: 5px;" href="javascript:void(0)" class="enabled_edit_field_width"><i class="fa fa-pencil"></i></a>
                                            <a style="margin-left:5px;width: 38px;padding: 0px;height: 18px;font-size: 11px;" class="hide btn btn-success save_related_block_list_field_width" data-fieldname = {$FIELD_MODEL->getName()}>Save</a>
                                        </span>
                                    </div>
                                </div>
                            </li>
                        {/if}
                    {/foreach}
                </ul>
                <ul style="list-style-type: none; float: left;min-height: 1px;padding:2px;" class="connectedSortable col-sm-6">
                    {foreach item=FIELD_MODEL from=$FIELDS_LIST name=fieldlist1}
                        {if $smarty.foreach.fieldlist1.index % 2 neq 0}
                            <li>
                                <div class="row border1px" style="padding: 10px 10px 10px 20px;">
                                    <span class="fieldLabel">
                                        <b>{vtranslate($FIELD_MODEL->get('label'), $RELMODULE_NAME)}</b>&nbsp;
                                        {if $FIELD_MODEL->isMandatory() eq true}<span class="redColor">*</span>{/if}
                                    </span>
                                    <!--<a href="javascript:void(0)" data-source-module="{$RELMODULE_NAME}" data-field-label="{$FIELD_MODEL->get('label')}" data-block-id="{$BLOCKID}" data-field-name="{$FIELD_MODEL->get('name')}" class="related-block-list-editFieldDetails pull-right"><i class="fa fa-pencil" title="Edit"></i></a>-->
                                    {assign var=IS_MANDATORY value=$FIELD_MODEL->isMandatory()}
                                    <div class="row field-width" style="float: right;margin-right: 10px;">
                                        <span class="fieldLabel">
                                            <b>{vtranslate('Witdh', $RELMODULE_NAME)}</b>&nbsp;
                                        </span>
                                        <span style="float: right;margin-left:10px;">
                                            <input name="width" disabled="disabled" type="text" class="inputElement vte_related_block_list_field_width" data-fieldname = {$FIELD_MODEL->getName()} value="{RelatedBlocksLists_Module_Model::getWidthForField($FIELD_MODEL->getName(),$BLOCKID)}" style="width: 80px;height:21px;">
                                            <a title="Edit"  style="margin-left: 5px;" href="javascript:void(0)" class="enabled_edit_field_width"><i class="fa fa-pencil"></i></a>
                                            <a style="margin-left:5px;width: 38px;padding: 0px;height: 18px;font-size: 11px;" class="hide btn btn-success save_related_block_list_field_width" data-fieldname = {$FIELD_MODEL->getName()} value="{RelatedBlocksLists_Module_Model::getWidthForField($FIELD_MODEL->getName(),$BLOCKID)}">Save</a>
                                        </span>

                                    </div>
                                </div>
                            </li>
                        {/if}
                    {/foreach}
                </ul>
            </div>
        </div>
    {/foreach}

{/strip}
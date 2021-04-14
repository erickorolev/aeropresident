{*/* ********************************************************************************
* The content of this file is subject to the Related Blocks & Lists ("License");
* You may not use this file except in compliance with the License
* The Initial Developer of the Original Code is VTExperts.com
* Portions created by VTExperts.com. are Copyright(C) VTExperts.com.
* All Rights Reserved.
* ****************************************************************************** */*}

{strip}
<div class="modal-dialog">
    <div class="modal-content">
        <form class="form-horizontal" action="index.php" id="relatedblockslists_form">
            <input type="hidden" name="blockid" value="{$BLOCKID}" />
            <input type="hidden" name="type" value="{$TYPE}" />
            <input type="hidden" name="sourceModule" value="{$SOURCE_MODULE_NAME}" />
            <div class="modal-header">
                <div class="clearfix">
                    <div class="pull-right " >
                        <button type="button" class="close" aria-label="Close" data-dismiss="modal">
                            <span aria-hidden="true" class='fa fa-close'></span>
                        </button>
                    </div>
                    <h4 class="pull-left">
                        {if $BLOCKID}{vtranslate('LBL_EDIT')}{else}{vtranslate('LBL_ADD')}{/if} {vtranslate('LBL_RELATED', 'RelatedBlocksLists')} {if $TYPE eq 'block'}{vtranslate('LBL_BLOCK', 'RelatedBlocksLists')}{else}{vtranslate('LBL_LIST', 'RelatedBlocksLists')}{/if}
                    </h4>
                </div>
            </div>

            <div class="modal-body">
                <div class="col-sm-12 col-xs-12 input-group">
                    <div class="form-group">
                        <label class="col-sm-4 control-label fieldLabel">
                            <strong>{vtranslate('LBL_RELATED_MODULE', 'RelatedBlocksLists')}</strong>
                            <span class="redColor">*</span>
                        </label>
                        <div class="fieldValue col-lg-3 col-md-3 col-sm-3 input-group">
                            <select class="select2 span5" name="select_module" data-rule-required="true">
                                {foreach item=MODULE_NAME from=$RELATED_MODULES}
                                    <option value="{$MODULE_NAME}" {if $MODULE_NAME eq $BLOCK_DATA['module']}selected{/if}>{vtranslate($MODULE_NAME, $MODULE_NAME)}</option>
                                {/foreach}
                            </select>
                        </div>
                    </div>
                </div>
                <div class="col-sm-12 col-xs-12 input-group">
                    <div class="form-group" id="fields">
                        {include file='Fields.tpl'|@vtemplate_path:$QUALIFIED_MODULE}
                    </div>
                </div>

                <div class="col-sm-12 col-xs-12 input-group">
                    <div class="form-group">
                        <label class="col-sm-4 control-label fieldLabel">
                            <strong>{vtranslate('LBL_ADD_AFTER', 'RelatedBlocksLists')}</strong>
                            <span class="redColor">*</span>
                        </label>
                        <div class="fieldValue col-lg-3 col-md-3 col-sm-3 input-group">
                            <select data-rule-required="true" class="select2 span5" name="after_block">
                                {foreach from=$ALL_BLOCK_LABELS key=BLOCK_ID item=BLOCK_LBL}
                                    <option value="{$BLOCK_ID}" {if $BLOCK_ID eq $BLOCK_DATA['after_block']}selected{/if}>{vtranslate($BLOCK_LBL,$SOURCE_MODULE_NAME)}</option>
                                {/foreach}
                            </select>
                        </div>
                    </div>
                </div>
                <div class="col-sm-12 col-xs-12 input-group">
                    <div class="form-group">
                        <label  class="col-sm-4 control-label fieldLabel">
                            <strong>{vtranslate('LBL_LIMIT_PER_PAGE', 'RelatedBlocksLists')}</strong>
                            <span class="redColor">*</span>
                        </label>
                        <div class="fieldValue col-lg-3 col-md-3 col-sm-3 input-group">
                            <input type="text" data-rule-required="true" class="inputElement" name="limit_per_page" value="{if $BLOCK_DATA['limit_per_page']}{$BLOCK_DATA['limit_per_page']}{else}5{/if}">
                        </div>
                    </div>
                </div>
                <input type="hidden" value='{$ALL_PICK_LISTS_VALUES}' id="all_pick_lists_values">
                <input type="hidden" value='{$ALL_PICK_LISTS_OF_ALL_MODULE}' id="all_pick_lists_of_all_module">
                <input type="hidden" value='{$ALL_FIELDS_OF_ALL_MODULE}' id="all_fields_of_all_module">
                <div class="col-sm-12 col-xs-12 input-group">
                    <div class="form-group">
                        <label  class="col-sm-4 control-label fieldLabel">
                            <strong>Sort By</strong>
                        </label>
                        <div class="fieldValue col-lg-3 col-md-3 col-sm-3 input-group">
                            <select name="sortfield" style="width: 150px;" class="select2 span5" id="related_block_list_sortfield" data-rel-module="{$BLOCK_DATA['module']}" name="filterfield">
                                <option value="" >None</option>

                                {foreach from=$SELECTED_FIELDS_SORT_OF_RELMODULE key=PICKLIST_FIELD_ID item=PICKLIST_FIELD_LABEL}
                                    <option {if $BLOCK_DATA['sortfield'] eq $PICKLIST_FIELD_ID}selected {/if} value="{$PICKLIST_FIELD_ID}">{$PICKLIST_FIELD_LABEL}</option>
                                {/foreach}
                            </select>
                            <select name="sorttype" id="related_block_list_sorttype" class="select2 span5">
                                <option value="ASC" {if $BLOCK_DATA['sorttype'] eq 'ASC'}selected {/if}>ASC</option>
                                <option value="DESC" {if $BLOCK_DATA['sorttype'] eq 'DESC'}selected {/if}>DESC</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="col-sm-12 col-xs-12 input-group">
                    <div class="form-group">
                        <label  class="col-sm-4 control-label fieldLabel">
                            <strong>Filter</strong>
                        </label>
                        <div class="fieldValue col-lg-3 col-md-3 col-sm-3 input-group">
                            <select style="width: 150px;" class="select2 span5" id="related_block_list_filter" data-rel-module="{$BLOCK_DATA['module']}" name="filterfield">
                                <option value="" >Select an option</option>
                                {foreach from=$ALL_PICK_LISTS key=PICKLIST_FIELD_ID item=PICKLIST_FIELD_LABEL}
                                    <option {if $BLOCK_DATA['filterfield'] eq $PICKLIST_FIELD_ID}selected {/if} value="{$PICKLIST_FIELD_ID}">{$PICKLIST_FIELD_LABEL}</option>
                                {/foreach}
                            </select>
                        </div>
                    </div>
                </div>
                <div class="col-sm-12 col-xs-12 input-group">
                    <div class="form-group">
                        <label  class="col-sm-4 control-label fieldLabel">
                            <strong>Value</strong>
                        </label>
                        <div class="fieldValue col-lg-3 col-md-3 col-sm-3 input-group">
                            <select style="width: 150px;" class="select2 span5" id="related_block_list_value" name="filtervalue">
                                <option value="" >Select an option</option>
                                {foreach from=$SELECTED_PICK_LISTS_VALUE key=KEY item=PICKLIST_FIELD_LABEL}
                                    <option {if $BLOCK_DATA['filtervalue'] eq $PICKLIST_FIELD_LABEL}selected {/if} value="{$PICKLIST_FIELD_LABEL}">{$PICKLIST_FIELD_LABEL}</option>
                                {/foreach}
                            </select>
                        </div>
                    </div>
                </div>
                <div class="col-sm-12 col-xs-12 input-group">
                    <div class="form-group">
                        <label  class="col-sm-4 control-label fieldLabel">
                            <strong>{vtranslate('LBL_ACTIVE', 'RelatedBlocksLists')}</strong>
                            <span class="redColor">*</span>
                        </label>
                        <div class="fieldValue col-lg-3 col-md-3 col-sm-3 input-group">
                            <select class="select2 span5" name="status" data-rule-required="true">
                                <option value="1" {if $BLOCK_DATA['status'] eq '1'}selected{/if}>{vtranslate('LBL_YES')}</option>
                                <option value="0" {if $BLOCK_DATA['status'] eq '0'}selected{/if}>{vtranslate('LBL_NO')}</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="col-sm-12 col-xs-12 input-group">
                    <div class="form-group">
                        <label  class="col-sm-4 control-label fieldLabel">
                            <strong>{vtranslate('Advanced query box', 'RelatedBlocksLists')}</strong>
                        </label>
                        <div class="fieldValue col-lg-7 col-md-7 col-sm-7 input-group">
                            <textarea name="advanced_query" row="5" style="width: 75%;height: 100px;">{$BLOCK_DATA['advanced_query']}</textarea>
                        </div>
                    </div>
                </div>
                <div class="col-sm-12 col-xs-12 input-group">
                    <div class="form-group" style="width: 100%">
                        <label  class="col-sm-8 control-label fieldLabel" style="margin-left: 220px;">
                            <strong>{vtranslate('Customizable options', 'RelatedBlocksLists')}</strong>
                        </label>
                    </div>
                    <hr>
                    <div class="form-group">
                        <div class="fieldValue col-lg-3 col-md-3 col-sm-3 input-group">
                            {assign var=CUSTOMIZABLE_OPTIONS value= $BLOCK_DATA['customizable_options']}
                            <table style="margin-left: 137px;">
                                <tr>
                                    <th style="text-align: right;"><label class="fieldLabel">{vtranslate('Option', 'RelatedBlocksLists')}</label></th>
                                    <th style="width: 25%;"><label class="fieldLabel">{vtranslate('Detail', 'RelatedBlocksLists')}</label></th>
                                    <th style="width: 25%;"><label class="fieldLabel">{vtranslate('Edit', 'RelatedBlocksLists')}</label></th>
                                </tr>
                                <tr>
                                    <td style="text-align: right;"><label class="fieldLabel" style="width: 150px;">{vtranslate('View icon:', 'RelatedBlocksLists')}</label></td>
                                    <td><input type="checkbox" name="chk_detail_view_icon" class="inputElement" style="margin-left: 15px;margin-bottom: 5px;" {if $CUSTOMIZABLE_OPTIONS ->chk_detail_view_icon eq 1 || $CUSTOMIZABLE_OPTIONS ->chk_detail_view_icon === null}checked{/if}></td>
                                    <td><input type="checkbox" name="chk_edit_view_icon" class="inputElement" style="margin-left: 15px;margin-bottom: 5px;" {if $CUSTOMIZABLE_OPTIONS ->chk_edit_view_icon eq 1 || $CUSTOMIZABLE_OPTIONS ->chk_edit_view_icon === null}checked{/if}> </td>
                                </tr>
                                <tr>
                                    <td style="text-align: right;"><label class="fieldLabel"  style="width: 150px;">{vtranslate('Edit icon:', 'RelatedBlocksLists')}</label></td>
                                    <td><input type="checkbox"  name="chk_detail_edit_icon" class="inputElement" style="margin-left: 15px;margin-bottom: 5px;" {if $CUSTOMIZABLE_OPTIONS ->chk_detail_edit_icon eq 1 || $CUSTOMIZABLE_OPTIONS ->chk_detail_edit_icon === null}checked{/if}></td>
                                    <td><input type="checkbox"  name="chk_edit_edit_icon" class="inputElement" style="margin-left: 15px;margin-bottom: 5px;" {if $CUSTOMIZABLE_OPTIONS ->chk_edit_edit_icon  eq 1 || $CUSTOMIZABLE_OPTIONS ->chk_edit_edit_icon === null}checked{/if}></td>
                                </tr>
                                <tr>
                                    <td style="text-align: right;"><label class="fieldLabel"  style="width: 150px;">{vtranslate('Delete icon:', 'RelatedBlocksLists')}</label></td>
                                    <td><input type="checkbox"  name="chk_detail_delete_icon" class="inputElement" style="margin-left: 15px;margin-bottom: 5px;" {if $CUSTOMIZABLE_OPTIONS ->chk_detail_delete_icon eq 1 || $CUSTOMIZABLE_OPTIONS ->chk_detail_delete_icon === null}checked{/if}></td>
                                    <td><input type="checkbox"  name="chk_edit_delete_icon" class="inputElement" style="margin-left: 15px;margin-bottom: 5px;" {if $CUSTOMIZABLE_OPTIONS ->chk_edit_delete_icon  eq 1 || $CUSTOMIZABLE_OPTIONS ->chk_edit_delete_icon === null}checked{/if}></td>
                                </tr>
                                <tr>
                                    <td style="text-align: right;"><label class="fieldLabel"  style="width: 150px;">{vtranslate('[+Add Reccord] button:', 'RelatedBlocksLists')}</label></td>
                                    <td><input type="checkbox"  name="chk_detail_add_btn" class="inputElement" style="margin-left: 15px;margin-bottom: 5px;" {if $CUSTOMIZABLE_OPTIONS ->chk_detail_add_btn eq 1 || $CUSTOMIZABLE_OPTIONS ->chk_detail_add_btn === null}checked{/if}></td>
                                    <td><input type="checkbox"  name="chk_edit_view_add_btn" class="inputElement" style="margin-left: 15px;margin-bottom: 5px;" {if $CUSTOMIZABLE_OPTIONS ->chk_edit_view_add_btn  eq 1 || $CUSTOMIZABLE_OPTIONS ->chk_edit_view_add_btn === null}checked{/if}></td>
                                </tr>
                                <tr>
                                    <td style="text-align: right;"><label class="fieldLabel" style="width: 150px;">{vtranslate('[Select Reccord] button:', 'RelatedBlocksLists')}</label></td>
                                    <td><input type="checkbox"  name="chk_detail_select_btn" class="inputElement" style="margin-left: 15px;margin-bottom: 5px;" {if $CUSTOMIZABLE_OPTIONS ->chk_detail_select_btn eq 1 || $CUSTOMIZABLE_OPTIONS ->chk_detail_select_btn === null}checked{/if}></td>
                                    <td><input type="checkbox"  name="chk_edit_select_btn" class="inputElement" style="margin-left: 15px;margin-bottom: 5px;" {if $CUSTOMIZABLE_OPTIONS ->chk_edit_select_btn  eq 1 || $CUSTOMIZABLE_OPTIONS ->chk_edit_select_btn === null}checked{/if}></td>
                                </tr>
                                <tr>
                                    <td style="text-align: right;"><label class="fieldLabel"  style="width: 150px;">{vtranslate('In-line Editing:', 'RelatedBlocksLists')}</label></td>
                                    <td><input type="checkbox"  name="chk_detail_inline_edit" class="inputElement" style="margin-left: 15px;margin-bottom: 5px;" {if $CUSTOMIZABLE_OPTIONS ->chk_detail_inline_edit  eq 1 || $CUSTOMIZABLE_OPTIONS ->chk_detail_inline_edit === null}checked{/if}></td>
                                    <td><input type="checkbox"  name="chk_edit_inline_edit" class="inputElement" style="margin-left: 15px;margin-bottom: 5px;" {if $CUSTOMIZABLE_OPTIONS ->chk_edit_inline_edit eq 1 || $CUSTOMIZABLE_OPTIONS ->chk_edit_inline_edit === null}checked{/if}></td>
                                </tr>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <div class="modal-footer">
                <div class="pull-right cancelLinkContainer" style="margin-top:0px;">
                    <a class="cancelLink" type="reset" data-dismiss="modal">{vtranslate('LBL_CANCEL', $MODULE)}</a>
                </div>
                <button class="btn btn-success" type="submit" name="saveButton"><strong>{vtranslate('LBL_SAVE', $MODULE)}</strong></button>
            </div>
        </form>
    </div>
</div>
{/strip}
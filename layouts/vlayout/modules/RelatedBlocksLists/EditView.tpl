{*/* ********************************************************************************
* The content of this file is subject to the Related Blocks & Lists ("License");
* You may not use this file except in compliance with the License
* The Initial Developer of the Original Code is VTExperts.com
* Portions created by VTExperts.com. are Copyright(C) VTExperts.com.
* All Rights Reserved.
* ****************************************************************************** */*}

{strip}
    <div id="massEditContainer" class='modelContainer'>
        <div id="massEdit">
            <div class="modal-header contentsBackground">
                <button type="button" class="close " data-dismiss="modal" aria-hidden="true">&times;</button>
                <h3 id="massEditHeader">{if $BLOCKID}{vtranslate('LBL_EDIT')}{else}{vtranslate('LBL_ADD')}{/if} {vtranslate('LBL_RELATED', 'RelatedBlocksLists')} {if $TYPE eq 'block'}{vtranslate('LBL_BLOCK', 'RelatedBlocksLists')}{else}{vtranslate('LBL_LIST', 'RelatedBlocksLists')}{/if}</h3>
            </div>
            <form class="form-horizontal" action="index.php" id="relatedblockslists_form">
                <input type="hidden" name="blockid" value="{$BLOCKID}" />
                <input type="hidden" name="type" value="{$TYPE}" />
                <input type="hidden" name="sourceModule" value="{$SOURCE_MODULE_NAME}" />

                <div name='massEditContent' class="row-fluid">
                    <div class="modal-body">
                        <div class="control-group">
                            <label class="muted control-label">
                                &nbsp;<strong>{vtranslate('LBL_RELATED_MODULE', 'RelatedBlocksLists')}</strong>
                            </label>
                            <div class="controls row-fluid">
                                <select class="select2 span5" name="select_module" data-validation-engine='validate[required]]'>
                                    {foreach item=MODULE_NAME from=$RELATED_MODULES}
                                            <option value="{$MODULE_NAME}" {if $MODULE_NAME eq $BLOCK_DATA['module']}selected{/if}>{vtranslate($MODULE_NAME, $MODULE_NAME)}</option>
                                    {/foreach}
                                </select>
                            </div>
                        </div>
                        <div id="fields" class="control-group">
                            {include file='Fields.tpl'|@vtemplate_path:$QUALIFIED_MODULE}
                        </div>
                        <div class="control-group">
                            <label class="muted control-label">
                                &nbsp;<strong>{vtranslate('LBL_ADD_AFTER', 'RelatedBlocksLists')}</strong>
                            </label>
                            <div class="controls row-fluid">
                                <select class="select2 span5" name="after_block">
                                    {foreach from=$ALL_BLOCK_LABELS key=BLOCK_ID item=BLOCK_LBL}
                                        <option value="{$BLOCK_ID}" {if $BLOCK_ID eq $BLOCK_DATA['after_block']}selected{/if}>{vtranslate($BLOCK_LBL,$SOURCE_MODULE_NAME)}</option>
                                    {/foreach}
                                </select>
                            </div>
                        </div>
                        <div class="control-group">
                            <label class="muted control-label">
                                &nbsp;<strong>{vtranslate('LBL_LIMIT_PER_PAGE', 'RelatedBlocksLists')}</strong>
                            </label>
                            <div class="controls row-fluid">
                                <input type="text" name="limit_per_page" value="{if $BLOCK_DATA['limit_per_page']}{$BLOCK_DATA['limit_per_page']}{else}5{/if}">
                            </div>
                        </div>
                        <div class="control-group">
                            <label class="muted control-label">
                                &nbsp;<strong>{vtranslate('LBL_ACTIVE', 'RelatedBlocksLists')}</strong>
                            </label>
                            <div class="controls row-fluid">
                                <select class="select2 span5" name="status">
                                    <option value="1" {if $BLOCK_DATA['status'] eq '1'}selected{/if}>{vtranslate('LBL_YES')}</option>
                                    <option value="0" {if $BLOCK_DATA['status'] eq '0'}selected{/if}>{vtranslate('LBL_NO')}</option>
                                </select>
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
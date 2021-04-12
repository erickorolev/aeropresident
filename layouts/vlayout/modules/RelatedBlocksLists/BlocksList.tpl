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
        <div style="border-radius: 4px 4px 0px 0px;background: white;" data-sequence="1" data-block-id="{$BLOCKID}" class="editFieldsTable relatedblock_{$BLOCKID} marginBottom10px border1px" id="relatedblock_{$BLOCKID}">
            <div class="row-fluid layoutBlockHeader">
                <div class="blockLabel span5 padding10 marginLeftZero">
                    &nbsp;&nbsp;<strong>{vtranslate('LBL_RELATED', 'RelatedBlocksLists')} {if $BLOCKDATA['type'] eq 'block'}{vtranslate('LBL_BLOCK', 'RelatedBlocksLists')}{else}{vtranslate('LBL_LIST', 'RelatedBlocksLists')}{/if} : {vtranslate($RELMODULE_NAME, $RELMODULE_NAME)}</strong>
                </div>
                <div style="float:right !important;" class="span6 marginLeftZero">
                    <div style="margin: 4px;" class="pull-right btn-toolbar blockActions">
                        <div class="btn-group">
                            <button data-toggle="dropdown" class="btn dropdown-toggle">
                                <strong>{vtranslate('LBL_ACTION')}</strong>&nbsp;&nbsp;<i class="caret"></i>
                            </button>
                            <ul class="dropdown-menu pull-right" style="min-width:0px;">
                                <li class="blockEditBtn" data-block-id="{$BLOCKID}" data-url="index.php?module=RelatedBlocksLists&view=MassSettingsAjax&mode=showSettingsForm&type=block&sourceModule={$SOURCE_MODULE}&blockid={$BLOCKID}">
                                    <a href="javascript:void(0)">&nbsp;{vtranslate('LBL_EDIT')}</a></li>
                                <li class="blockDeleteBtn" data-block-id="{$BLOCKID}" data-message-delete="{vtranslate('JS_LBL_ARE_YOU_SURE_YOU_WANT_TO_DELETE', 'RelatedBlocksLists')}">
                                    <a href="javascript:void(0)">{vtranslate('LBL_DELETE')}</a>
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
            <div style="padding:5px;min-height: 27px" class="blockFieldsList row-fluid">
                <ul style="list-style-type: none; float: left;min-height: 1px;padding:2px;" class="span6">
                    {foreach item=FIELD_MODEL from=$FIELDS_LIST name=fieldlist}
                        {if $smarty.foreach.fieldlist.index % 2 eq 0}
                            <li>
                                <div class="opacity editFields marginLeftZero border1px">
                                    <div class="row-fluid padding1per">
                                        <span class="span1">&nbsp;</span>
                                        <div style="word-wrap: break-word;" class="span11 marginLeftZero">
                                            <span class="fieldLabel">{vtranslate($FIELD_MODEL->get('label'), $RELMODULE_NAME)}&nbsp;</span>
                                        </div>
                                    </div>
                                </div>
                            </li>
                        {/if}
                    {/foreach}
                </ul>
                <ul style="list-style-type: none; margin: 0; float: left;min-height: 1px;padding:2px;" class="span6">
                    {foreach item=FIELD_MODEL from=$FIELDS_LIST name=fieldlist1}
                        {if $smarty.foreach.fieldlist1.index % 2 neq 0}
                            <li>
                                <div class="opacity editFields marginLeftZero border1px">
                                    <div class="row-fluid padding1per">
                                        <span class="span1">&nbsp;</span>
                                        <div style="word-wrap: break-word;" class="span11 marginLeftZero">
                                            <span class="fieldLabel">{vtranslate($FIELD_MODEL->get('label'), $RELMODULE_NAME)}&nbsp;</span>
                                        </div>
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
{*<!--
/*********************************************************************************
* The content of this file is subject to the PDF Maker license.
* ("License"); You may not use this file except in compliance with the License
* The Initial Developer of the Original Code is IT-Solutions4You s.r.o.
* Portions created by IT-Solutions4You s.r.o. are Copyright(C) IT-Solutions4You s.r.o.
* All Rights Reserved.
********************************************************************************/
-->*}
{strip}
<div class="contents tabbable ui-sortable">
    <form class="form-horizontal recordEditView" id="EditView" name="EditView" method="post" action="index.php" enctype="multipart/form-data">
        <input type="hidden" name="module" value="PDFMaker">
        <input type="hidden" name="parenttab" value="{$PARENTTAB}">
        <input type="hidden" name="templateid" id="templateid" value="{$SAVETEMPLATEID}">
        <input type="hidden" name="action" value="SavePDFTemplate">
        <input type="hidden" name="redirect" value="true">
        <input type="hidden" name="return_module" value="{$smarty.request.return_module}">
        <input type="hidden" name="return_view" value="{$smarty.request.return_view}">
        <input type="hidden" name="selectedTab" id="selectedTab" value="properties">
        <input type="hidden" name="selectedTab2" id="selectedTab2" value="body">
        <ul class="nav nav-tabs layoutTabs massEditTabs">
            <li class="detailviewTab active">
                <a data-toggle="tab" href="#pdfContentEdit" aria-expanded="true"><strong>{vtranslate('LBL_BASIC_TAB',$MODULE)}</strong></a>
            </li>
            <li class="detailviewTab">
                <a data-toggle="tab" href="#pdfContentOther" aria-expanded="false"><strong>{vtranslate('LBL_OTHER_INFO',$MODULE)}</strong></a>
            </li>
            <li class="detailviewTab">
                <a data-toggle="tab" href="#pdfContentLabels" aria-expanded="false"><strong>{vtranslate('LBL_LABELS',$MODULE)}</strong></a>
            </li>
            {if $IS_BLOCK neq true}
                <li class="detailviewTab">
                    <a data-toggle="tab" href="#pdfContentProducts" aria-expanded="false"><strong>{vtranslate('LBL_ARTICLE',$MODULE)}</strong></a>
                </li>
                <li class="detailviewTab">
                    <a data-toggle="tab" href="#pdfContentHeaderFooter" aria-expanded="false"><strong>{vtranslate('LBL_HEADER_TAB',$MODULE)} / {vtranslate('LBL_FOOTER_TAB',$MODULE)}</strong></a>
                </li>
                <li class="detailviewTab">
                    <a data-toggle="tab" href="#editTabProperties" aria-expanded="false"><strong>{vtranslate('LBL_PROPERTIES_TAB',$MODULE)}</strong></a>
                </li>
                <li class="detailviewTab">
                    <a data-toggle="tab" href="#editTabSettings" aria-expanded="false"><strong>{vtranslate('LBL_SETTINGS_TAB',$MODULE)}</strong></a>
                </li>
                <li class="detailviewTab">
                    <a data-toggle="tab" href="#editTabSharing" aria-expanded="false"><strong>{vtranslate('LBL_SHARING_TAB',$MODULE)}</strong></a>
                </li>
            {/if}
        </ul>
        <div >
            {********************************************* Settings DIV *************************************************}
            <div>
                <div class="row" >
                    <div class="left-block col-xs-4">
                        <div>
                            <div class="tab-content layoutContent themeTableColor overflowVisible">
                                <div class="tab-pane active" id="pdfContentEdit">

                                    <div class="edit-template-content col-lg-4" style="position:fixed;z-index:1000;">
                                        <br />
                                        {********************************************* PROPERTIES DIV*************************************************}
                                        <div class="properties_div">
                                            {* pdf module name *}
                                            <div class="form-group">
                                                <label class="control-label fieldLabel col-sm-3" style="font-weight: normal">
                                                    {vtranslate('LBL_PDF_NAME',$MODULE)}:&nbsp;<span class="redColor">*</span>
                                                </label>
                                                <div class="controls col-sm-9">
                                                    <input name="filename" id="filename" type="text" value="{$FILENAME}" data-rule-required="true" class="inputElement nameField" tabindex="1">
                                                </div>
                                            </div>
                                            {if $IS_BLOCK eq true}
                                                <div class="form-group">
                                                    <label class="control-label fieldLabel col-sm-3" style="font-weight: normal">
                                                        {vtranslate('LBL_TYPE',$MODULE)}:
                                                    </label>
                                                    <div class="controls col-sm-9">
                                                        {if $SAVETEMPLATEID neq "" && $TEMPLATEBLOCKTYPE neq ""}
                                                            {$TEMPLATEBLOCKTYPEVAL}
                                                            <input type="hidden" name="blocktype" id="blocktype" value="{$TEMPLATEBLOCKTYPE}">
                                                        {else}
                                                            <select name="blocktype" id="blocktype" class="select2 form-control" data-rule-required="true">
                                                                <option value="header" {if $TEMPLATEBLOCKTYPE eq 'header'}selected{/if}>{vtranslate('Header',$MODULE)}</option>
                                                                <option value="footer" {if $TEMPLATEBLOCKTYPE eq 'footer'}selected{/if}>{vtranslate('Footer',$MODULE)}</option>
                                                            </select>
                                                        {/if}
                                                    </div>
                                                </div>

                                                <div class="form-group">
                                                    <label class="control-label fieldLabel col-sm-3" style="font-weight: normal">
                                                        {vtranslate('LBL_DESCRIPTION',$MODULE)}:
                                                    </label>
                                                    <div class="controls col-sm-9">
                                                        <input name="description" type="text" value="{$DESCRIPTION}" class="inputElement" tabindex="2">
                                                    </div>
                                                </div>
                                                {* pdf header variables*}
                                                <div class="form-group" id="header_variables">
                                                    <label class="control-label fieldLabel col-sm-3" style="font-weight: normal">
                                                        {vtranslate('LBL_HEADER_FOOTER_VARIABLES',$MODULE)}:
                                                    </label>
                                                    <div class="controls col-sm-9">
                                                        <div class="input-group">
                                                            <select name="header_var" id="header_var" class="select2 form-control">
                                                                {html_options  options=$HEAD_FOOT_VARS selected=""}
                                                            </select>
                                                            <div class="input-group-btn">
                                                                <button type="button" class="btn btn-success InsertIntoTemplate" data-type="header_var" title="{vtranslate('LBL_INSERT_TO_TEXT',$MODULE)}"><i class="fa fa-usd"></i></button>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            {/if}
                                            {* pdf source module and its available fields *}
                                            <div class="form-group">
                                                <label class="control-label fieldLabel col-sm-3" style="font-weight: normal">
                                                    {vtranslate('LBL_MODULENAMES',$MODULE)}:{if $TEMPLATEID eq "" && $IS_BLOCK neq true}&nbsp;<span class="redColor">*</span>&nbsp;{/if}
                                                </label>
                                                <div class="controls col-sm-9">
                                                    <select name="modulename" id="modulename" class="select2 form-control" {if $IS_BLOCK neq true}data-rule-required="true"{/if}>
                                                        {if $TEMPLATEID neq "" || $SELECTMODULE neq ""}
                                                            {html_options  options=$MODULENAMES selected=$SELECTMODULE}
                                                        {else}
                                                            {html_options  options=$MODULENAMES}
                                                        {/if}
                                                    </select>
                                                </div>
                                            </div>
                                            <div class="form-group">
                                                <label class="control-label fieldLabel col-sm-3" style="font-weight: normal">
                                                </label>
                                                <div class="controls col-sm-9">
                                                    <div class="input-group">
                                                        <select name="modulefields" id="modulefields" class="select2 form-control">
                                                            {if $TEMPLATEID eq "" && $SELECTMODULE eq ""}
                                                                <option value="">{vtranslate('LBL_SELECT_MODULE_FIELD',$MODULE)}</option>
                                                            {else}
                                                                {html_options  options=$SELECT_MODULE_FIELD}
                                                            {/if}
                                                        </select>
                                                        <div class="input-group-btn">
                                                            <button type="button" class="btn btn-success InsertIntoTemplate" data-type="modulefields" title="{vtranslate('LBL_INSERT_VARIABLE_TO_TEXT',$MODULE)}"><i class="fa fa-usd"></i></button>
                                                            <button type="button" class="btn btn-warning InsertLIntoTemplate" data-type="modulefields" title="{vtranslate('LBL_INSERT_LABEL_TO_TEXT',$MODULE)}"><i class="fa fa-text-width"></i></button>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            {* related modules and its fields *}
                                            <div class="form-group">
                                                <label class="control-label fieldLabel col-sm-3" style="font-weight: normal">
                                                    {vtranslate('LBL_RELATED_MODULES',$MODULE)}:
                                                </label>
                                                <div class="controls col-sm-9">
                                                    <select name="relatedmodulesorce" id="relatedmodulesorce" class="select2 form-control">
                                                        <option value="">{vtranslate('LBL_SELECT_MODULE',$MODULE)}</option>
                                                        {foreach item=RelMod from=$RELATED_MODULES}
                                                            <option value="{$RelMod.3}|{$RelMod.0}" data-module="{$RelMod.3}">{$RelMod.1} ({$RelMod.2})</option>
                                                        {/foreach}
                                                    </select>
                                                </div>
                                            </div>
                                            <div class="form-group">
                                                <label class="control-label fieldLabel col-sm-3" style="font-weight: normal">
                                                </label>
                                                <div class="controls col-sm-9">
                                                    <div class="input-group">
                                                        <select name="relatedmodulefields" id="relatedmodulefields" class="select2 form-control">
                                                            <option value="">{vtranslate('LBL_SELECT_MODULE_FIELD',$MODULE)}</option>
                                                        </select>
                                                        <div class="input-group-btn">
                                                            <button type="button" class="btn btn-success InsertIntoTemplate" data-type="relatedmodulefields" title="{vtranslate('LBL_INSERT_VARIABLE_TO_TEXT',$MODULE)}"><i class="fa fa-usd"></i></button>
                                                            <button type="button" class="btn btn-warning InsertLIntoTemplate" data-type="relatedmodulefields" title="{vtranslate('LBL_INSERT_LABEL_TO_TEXT',$MODULE)}"><i class="fa fa-text-width"></i></button>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            {* related bloc tpl *}
                                            {if $IS_BLOCK neq true}
                                                <div class="form-group" id="related_block_tpl_row">
                                                    <label class="control-label fieldLabel col-sm-3" style="font-weight: normal">
                                                        {vtranslate('LBL_RELATED_BLOCK_TPL',$MODULE)}:
                                                    </label>
                                                    <div class="controls col-sm-9">
                                                        <div class="input-group">
                                                            <select name="related_block" id="related_block" class="select2 form-control" >
                                                                {html_options options=$RELATED_BLOCKS}
                                                            </select>
                                                            <div class="input-group-btn">
                                                                <button type="button" class="btn btn-success marginLeftZero" onclick="PDFMaker_EditJs.InsertRelatedBlock();" title="{vtranslate('LBL_INSERT_TO_TEXT',$MODULE)}"><i class="fa fa-usd"></i></button>
                                                                <button type="button" class="btn addButton marginLeftZero" onclick="PDFMaker_EditJs.CreateRelatedBlock();" title="{vtranslate('LBL_CREATE')}"><i class="fa fa-plus"></i></button>
                                                                <button type="button" class="btn marginLeftZero" onclick="PDFMaker_EditJs.EditRelatedBlock();" title="{vtranslate('LBL_EDIT')}"><i class="fa fa-edit"></i></button>
                                                                <button type="button" class="btn btn-danger marginLeftZero" class="crmButton small delete" onclick="PDFMaker_EditJs.DeleteRelatedBlock();" title="{vtranslate('LBL_DELETE')}"><i class="fa fa-trash"></i></button>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            {/if}
                                            <div class="form-group">
                                                <label class="control-label fieldLabel col-sm-3" style="font-weight: normal">
                                                    {vtranslate('LBL_COMPANY_INFO',$MODULE)}:
                                                </label>
                                                <div class="controls col-sm-9">
                                                    <div class="input-group">
                                                        <select name="acc_info" id="acc_info" class="select2 form-control">
                                                            {html_options  options=$ACCOUNTINFORMATIONS}
                                                        </select>
                                                        <div id="acc_info_div" class="input-group-btn">
                                                            <button type="button" class="btn btn-success InsertIntoTemplate" data-type="acc_info" title="{vtranslate('LBL_INSERT_VARIABLE_TO_TEXT',$MODULE)}"><i class="fa fa-usd"></i></button>
                                                            <button type="button" class="btn btn-warning InsertLIntoTemplate" data-type="acc_info" title="{vtranslate('LBL_INSERT_LABEL_TO_TEXT',$MODULE)}"><i class="fa fa-text-width"></i></button>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="form-group">
                                                <label class="control-label fieldLabel col-sm-3" style="font-weight: normal">
                                                    {vtranslate('LBL_SELECT_USER_INFO',$MODULE)}:
                                                </label>
                                                <div class="controls col-sm-9">
                                                    <select name="acc_info_type" id="acc_info_type" class="select2 form-control" onChange="PDFMaker_EditJs.change_acc_info(this)">
                                                        {html_options  options=$CUI_BLOCKS}
                                                    </select>
                                                </div>
                                            </div>
                                            <div class="form-group">
                                                <label class="control-label fieldLabel col-sm-3" style="font-weight: normal"></label>
                                                <div class="controls col-sm-9">
                                                    <div id="user_info_div" class="au_info_div">
                                                        <div class="input-group">
                                                            <select name="user_info" id="user_info" class="select2 form-control">
                                                                {html_options  options=$USERINFORMATIONS['a']}
                                                            </select>
                                                            <div class="input-group-btn">
                                                                <button type="button" class="btn btn-success InsertIntoTemplate" data-type="user_info" title="{vtranslate('LBL_INSERT_VARIABLE_TO_TEXT',$MODULE)}"><i class="fa fa-usd"></i></button>
                                                                <button type="button" class="btn btn-warning InsertLIntoTemplate" data-type="user_info" title="{vtranslate('LBL_INSERT_LABEL_TO_TEXT',$MODULE)}"><i class="fa fa-text-width"></i></button>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div id="logged_user_info_div" class="au_info_div" style="display:none;">
                                                        <div class="input-group">
                                                            <select name="logged_user_info" id="logged_user_info" class="select2 form-control">
                                                                {html_options  options=$USERINFORMATIONS['l']}
                                                            </select>
                                                            <div class="input-group-btn">
                                                                <button type="button" class="btn btn-success InsertIntoTemplate" data-type="logged_user_info" title="{vtranslate('LBL_INSERT_VARIABLE_TO_TEXT',$MODULE)}"><i class="fa fa-usd"></i></button>
                                                                <button type="button" class="btn btn-warning InsertLIntoTemplate" data-type="logged_user_info" title="{vtranslate('LBL_INSERT_LABEL_TO_TEXT',$MODULE)}"><i class="fa fa-text-width"></i></button>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div id="modifiedby_user_info_div" class="au_info_div" style="display:none;">
                                                        <div class="input-group">
                                                            <select name="modifiedby_user_info" id="modifiedby_user_info" class="select2 form-control">
                                                                {html_options  options=$USERINFORMATIONS['m']}
                                                            </select>
                                                            <div class="input-group-btn">
                                                                <button type="button" class="btn btn-success InsertIntoTemplate" data-type="modifiedby_user_info" title="{vtranslate('LBL_INSERT_VARIABLE_TO_TEXT',$MODULE)}"><i class="fa fa-usd"></i></button>
                                                                <button type="button" class="btn btn-warning InsertLIntoTemplate" data-type="modifiedby_user_info" title="{vtranslate('LBL_INSERT_LABEL_TO_TEXT',$MODULE)}"><i class="fa fa-text-width"></i></button>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div id="smcreator_user_info_div" class="au_info_div" style="display:none;">
                                                        <div class="input-group">
                                                            <select name="smcreator_user_info" id="smcreator_user_info" class="select2 form-control">
                                                                {html_options  options=$USERINFORMATIONS['c']}
                                                            </select>
                                                            <div class="input-group-btn">
                                                                <button type="button" class="btn btn-success InsertIntoTemplate" data-type="smcreator_user_info" title="{vtranslate('LBL_INSERT_VARIABLE_TO_TEXT',$MODULE)}"><i class="fa fa-usd"></i></button>
                                                                <button type="button" class="btn btn-warning InsertLIntoTemplate" data-type="smcreator_user_info" title="{vtranslate('LBL_INSERT_LABEL_TO_TEXT',$MODULE)}"><i class="fa fa-text-width"></i></button>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="tab-pane" id="pdfContentOther">
                                    <div class="edit-template-content col-lg-4" style="position:fixed;z-index:1000;">
                                        <br />
                                        {if $IS_BLOCK neq true}
                                            <div class="form-group" id="listview_block_tpl_row">
                                                <label class="control-label fieldLabel col-sm-3" style="font-weight: normal">
                                                    <input type="checkbox" name="is_listview" id="isListViewTmpl" {if $IS_LISTVIEW_CHECKED eq "yes"}checked="checked"{/if} onclick="PDFMaker_EditJs.isLvTmplClicked();" title="{vtranslate('LBL_LISTVIEW_TEMPLATE',$MODULE)}" />&nbsp;{vtranslate('LBL_LISTVIEWBLOCK',$MODULE)}:
                                                </label>
                                                <div class="controls col-sm-9">
                                                    <div class="input-group">
                                                        <select name="listviewblocktpl" id="listviewblocktpl" class="select2 form-control" {if $IS_LISTVIEW_CHECKED neq "yes"}disabled{/if}>
                                                            {html_options  options=$LISTVIEW_BLOCK_TPL}
                                                        </select>
                                                        <div class="input-group-btn">
                                                            <button type="button" id="listviewblocktpl_butt" class="btn btn-success InsertIntoTemplate" data-type="listviewblocktpl" title="{vtranslate('LBL_INSERT_TO_TEXT',$MODULE)}" {if $IS_LISTVIEW_CHECKED neq "yes"}disabled{/if}><i class="fa fa-usd"></i></button>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        {/if}
                                        <div class="form-group">
                                            <label class="control-label fieldLabel col-sm-3" style="font-weight: normal">
                                                {vtranslate('TERMS_AND_CONDITIONS',$MODULE)}:
                                            </label>
                                            <div class="controls col-sm-9">
                                                <div class="input-group">
                                                    <select name="invterandcon" id="invterandcon" class="select2 form-control">
                                                        {html_options  options=$INVENTORYTERMSANDCONDITIONS}
                                                    </select>
                                                    <div class="input-group-btn">
                                                        <button type="button" class="btn btn-success InsertIntoTemplate" data-type="invterandcon" title="{vtranslate('LBL_INSERT_TO_TEXT',$MODULE)}"><i class="fa fa-usd"></i></button>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="form-group">
                                            <label class="control-label fieldLabel col-sm-3" style="font-weight: normal">
                                                {vtranslate('LBL_CURRENT_DATE',$MODULE)}:
                                            </label>
                                            <div class="controls col-sm-9">
                                                <div class="input-group">
                                                    <select name="dateval" id="dateval" class="select2 form-control">
                                                        {html_options  options=$DATE_VARS}
                                                    </select>
                                                    <div class="input-group-btn">
                                                        <button type="button" class="btn btn-success InsertIntoTemplate" data-type="dateval" title="{vtranslate('LBL_INSERT_TO_TEXT',$MODULE)}"><i class="fa fa-usd"></i></button>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        {***** BARCODES *****}
                                        <div class="form-group">
                                            <label class="control-label fieldLabel col-sm-3" style="font-weight: normal">
                                                {vtranslate('LBL_BARCODES',$MODULE)}:
                                            </label>
                                            <div class="controls col-sm-9">
                                                <div class="input-group">
                                                    <select name="barcodeval" id="barcodeval" class="select2 form-control">
                                                        <optgroup label="{vtranslate('LBL_BARCODES_TYPE1',$MODULE)}">
                                                            <option value="EAN13">EAN13</option>
                                                            <option value="ISBN">ISBN</option>
                                                            <option value="ISSN">ISSN</option>
                                                        </optgroup>

                                                        <optgroup label="{vtranslate('LBL_BARCODES_TYPE2',$MODULE)}">
                                                            <option value="UPCA">UPCA</option>
                                                            <option value="UPCE">UPCE</option>
                                                            <option value="EAN8">EAN8</option>
                                                        </optgroup>

                                                        <optgroup label="{vtranslate('LBL_BARCODES_TYPE3',$MODULE)}">
                                                            <option value="EAN2">EAN2</option>
                                                            <option value="EAN5">EAN5</option>
                                                            <option value="EAN13P2">EAN13P2</option>
                                                            <option value="ISBNP2">ISBNP2</option>
                                                            <option value="ISSNP2">ISSNP2</option>
                                                            <option value="UPCAP2">UPCAP2</option>
                                                            <option value="UPCEP2">UPCEP2</option>
                                                            <option value="EAN8P2">EAN8P2</option>
                                                            <option value="EAN13P5">EAN13P5</option>
                                                            <option value="ISBNP5">ISBNP5</option>
                                                            <option value="ISSNP5">ISSNP5</option>
                                                            <option value="UPCAP5">UPCAP5</option>
                                                            <option value="UPCEP5">UPCEP5</option>
                                                            <option value="EAN8P5">EAN8P5</option>
                                                        </optgroup>

                                                        <optgroup label="{vtranslate('LBL_BARCODES_TYPE4',$MODULE)}">
                                                            <option value="IMB">IMB</option>
                                                            <option value="RM4SCC">RM4SCC</option>
                                                            <option value="KIX">KIX</option>
                                                            <option value="POSTNET">POSTNET</option>
                                                            <option value="PLANET">PLANET</option>
                                                        </optgroup>

                                                        <optgroup label="{vtranslate('LBL_BARCODES_TYPE5',$MODULE)}">
                                                            <option value="C128A">C128A</option>
                                                            <option value="C128B">C128B</option>
                                                            <option value="C128C">C128C</option>
                                                            <option value="EAN128C">EAN128C</option>
                                                            <option value="C39">C39</option>
                                                            <option value="C39+">C39+</option>
                                                            <option value="C39E">C39E</option>
                                                            <option value="C39E+">C39E+</option>
                                                            <option value="S25">S25</option>
                                                            <option value="S25+">S25+</option>
                                                            <option value="I25">I25</option>
                                                            <option value="I25+">I25+</option>
                                                            <option value="I25B">I25B</option>
                                                            <option value="I25B+">I25B+</option>
                                                            <option value="C93">C93</option>
                                                            <option value="MSI">MSI</option>
                                                            <option value="MSI+">MSI+</option>
                                                            <option value="CODABAR">CODABAR</option>
                                                            <option value="CODE11">CODE11</option>
                                                        </optgroup>

                                                        <optgroup label="{vtranslate('LBL_QRCODE',$MODULE)}">
                                                            <option value="QR">QR</option>
                                                        </optgroup>
                                                    </select>
                                                    <div class="input-group-btn">
                                                        <button type="button" class="btn btn-success InsertIntoTemplate" data-type="barcodeval" title="{vtranslate('LBL_INSERT_BARCODE_TO_TEXT',$MODULE)}"><i class="fa fa-usd"></i></button>&nbsp;&nbsp;
                                                        <a href="index.php?module=PDFMaker&view=IndexAjax&mode=showBarcodes" target="_new"><button type="button" class="btn"><i class="fa fa-info"></i></button></a>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        {if $TYPE eq "professional"}
                                            <div class="form-group">
                                                <label class="control-label fieldLabel col-sm-3" style="font-weight: normal">
                                                    {vtranslate('CUSTOM_FUNCTIONS',$MODULE)}:
                                                </label>
                                                <div class="controls col-sm-9">
                                                    <div class="input-group">
                                                        <select name="customfunction" id="customfunction" class="select2 form-control">
                                                            {html_options options=$CUSTOM_FUNCTIONS}
                                                        </select>
                                                        <div class="input-group-btn">
                                                            <button type="button" class="btn btn-success InsertIntoTemplate" data-type="customfunction" title="{vtranslate('LBL_INSERT_TO_TEXT',$MODULE)}"><i class="fa fa-usd"></i></button>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        {/if}
                                        <div class="form-group">
                                            <label class="control-label fieldLabel col-sm-3" style="font-weight: normal">
                                                {vtranslate('LBL_FONT_AWESOME',$MODULE)}:
                                            </label>
                                            <div class="controls col-sm-9">
                                                <div class="input-group">
                                                    <select name="fontawesomeicons" id="fontawesomeicons" class="select2 form-control">
                                                        {foreach item=FONTAWESOMEDATA from=$FONTAWESOMEICONS}
                                                            {if $SELECTEDFONTAWESOMEICON eq ""}{assign var=SELECTEDFONTAWESOMEICON value=$FONTAWESOMEDATA.name}{/if}
                                                            <option value="{$FONTAWESOMEDATA.code}" data-classname="{$FONTAWESOMEDATA.name}" {if $SELECTEDFONTAWESOMEICON eq $FONTAWESOMEDATA.name}selected="selected"{/if}>{$FONTAWESOMEDATA.name}</option>
                                                        {/foreach}

                                                    </select>
                                                    <div class="input-group-btn">
                                                        <button type="button" class="btn btn-warning InsertIconIntoTemplate" data-type="awesomeicon" title="{vtranslate('LBL_INSERT_TO_TEXT',$MODULE)}"><i id="fontawesomepreview" class="fa {$SELECTEDFONTAWESOMEICON}"></i></button><a href="index.php?module=PDFMaker&view=IndexAjax&mode=getAwesomeInfoPDF" target="_new"><button type="button" class="btn"><i class="fa fa-info"></i></button></a>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                    </div>
                                </div>
                                <div class="tab-pane" id="pdfContentLabels">
                                    <div class="edit-template-content col-lg-4" style="position:fixed;z-index:1000;">
                                        <br />
                                        {********************************************* Labels *************************************************}
                                        <div class="form-group" id="labels_div">
                                            <label class="control-label fieldLabel col-sm-3" style="font-weight: normal">
                                                {vtranslate('LBL_GLOBAL_LANG',$MODULE)}:
                                            </label>
                                            <div class="controls col-sm-9">
                                                <div class="input-group">
                                                    <select name="global_lang" id="global_lang" class="select2 form-control">
                                                        {html_options  options=$GLOBAL_LANG_LABELS}
                                                    </select>
                                                    <span class="input-group-btn">
                                                        <button type="button" class="btn btn-warning InsertIntoTemplate" data-type="global_lang" title="{vtranslate('LBL_INSERT_LABEL_TO_TEXT',$MODULE)}"><i class="fa fa-text-width"></i></button>
                                                    </span>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="form-group">
                                            <label class="control-label fieldLabel col-sm-3" style="font-weight: normal">
                                                {vtranslate('LBL_MODULE_LANG',$MODULE)}:
                                            </label>
                                            <div class="controls col-sm-9">
                                                <div class="input-group">
                                                    <select name="module_lang" id="module_lang" class="select2 form-control">
                                                        {html_options  options=$MODULE_LANG_LABELS}
                                                    </select>
                                                    <span class="input-group-btn">
                                                        <button type="button" class="btn btn-warning InsertIntoTemplate" data-type="module_lang" title="{vtranslate('LBL_INSERT_LABEL_TO_TEXT',$MODULE)}"><i class="fa fa-text-width"></i></button>
                                                    </span>
                                                </div>
                                            </div>
                                        </div>
                                        {if $TYPE eq "professional"}
                                            <div class="form-group">
                                                <label class="control-label fieldLabel col-sm-3" style="font-weight: normal">
                                                    {vtranslate('LBL_CUSTOM_LABELS',$MODULE)}:
                                                </label>
                                                <div class="controls col-sm-9">
                                                    <div class="input-group">
                                                        <select name="custom_lang" id="custom_lang" class="select2 form-control">
                                                            {html_options  options=$CUSTOM_LANG_LABELS}
                                                        </select>
                                                        <span class="input-group-btn">
                                                            <button type="button" class="btn btn-warning InsertIntoTemplate" data-type="custom_lang" title="{vtranslate('LBL_INSERT_LABEL_TO_TEXT',$MODULE)}"><i class="fa fa-text-width"></i></button>
                                                        </span>
                                                    </div>
                                                </div>
                                            </div>
                                        {/if}
                                    </div>
                                </div>
                                {if $IS_BLOCK neq true}
                                    <div class="tab-pane" id="pdfContentProducts">
                                        <div class="edit-template-content col-lg-4" style="position:fixed;z-index:1000;">
                                            <br />
                                            {*********************************************Products bloc DIV*************************************************}
                                            <div id="products_div">
                                                {* product bloc tpl which is the same as in main Properties tab*}
                                                <div class="form-group">
                                                    <label class="control-label fieldLabel col-sm-4" style="font-weight: normal">
                                                        {vtranslate('LBL_PRODUCT_BLOC_TPL',$MODULE)}:
                                                    </label>
                                                    <div class="controls col-sm-8">
                                                        <div class="input-group">
                                                            <select name="productbloctpl2" id="productbloctpl2" class="select2 form-control">
                                                                {html_options  options=$PRODUCT_BLOC_TPL}
                                                            </select>
                                                            <span class="input-group-btn">
                                                                <button type="button" class="btn btn-success InsertIntoTemplate" data-type="productbloctpl2" title="{vtranslate('LBL_INSERT_TO_TEXT',$MODULE)}"><i class="fa fa-usd"></i></button>
                                                            </span>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="form-group">
                                                    <label class="control-label fieldLabel col-sm-4" style="font-weight: normal">
                                                        {vtranslate('LBL_ARTICLE',$MODULE)}:
                                                    </label>
                                                    <div class="controls col-sm-8">
                                                        <div class="input-group">
                                                            <select name="articelvar" id="articelvar" class="select2 form-control">
                                                                {html_options  options=$ARTICLE_STRINGS}
                                                            </select>
                                                            <span class="input-group-btn">
                                                                <button type="button" class="btn btn-success InsertIntoTemplate" data-type="articelvar" title="{vtranslate('LBL_INSERT_TO_TEXT',$MODULE)}"><i class="fa fa-usd"></i></button>
                                                            </span>
                                                        </div>
                                                    </div>
                                                </div>
                                                {* insert products & services fields into text *}
                                                <div class="form-group">
                                                    <label class="control-label fieldLabel col-sm-4" style="font-weight: normal">
                                                        *{vtranslate('LBL_PRODUCTS_AVLBL',$MODULE)}:
                                                    </label>
                                                    <div class="controls col-sm-8">
                                                        <div class="input-group">
                                                            <select name="psfields" id="psfields" class="select2 form-control">
                                                                {html_options  options=$SELECT_PRODUCT_FIELD}
                                                            </select>
                                                            <span class="input-group-btn">
                                                                <button type="button" class="btn btn-success InsertIntoTemplate" data-type="psfields" title="{vtranslate('LBL_INSERT_VARIABLE_TO_TEXT',$MODULE)}"><i class="fa fa-usd"></i></button>
                                                            </span>
                                                        </div>
                                                    </div>
                                                </div>
                                                {* products fields *}
                                                <div class="form-group">
                                                    <label class="control-label fieldLabel col-sm-4" style="font-weight: normal">
                                                        *{vtranslate('LBL_PRODUCTS_FIELDS',$MODULE)}:
                                                    </label>
                                                    <div class="controls col-sm-8">
                                                        <div class="input-group">
                                                            <select name="productfields" id="productfields" class="select2 form-control">
                                                                {html_options  options=$PRODUCTS_FIELDS}
                                                            </select>
                                                            <span class="input-group-btn">
                                                                <button type="button" class="btn btn-success InsertIntoTemplate" data-type="productfields" title="{vtranslate('LBL_INSERT_VARIABLE_TO_TEXT',$MODULE)}"><i class="fa fa-usd"></i></button>
                                                            </span>
                                                        </div>
                                                    </div>
                                                </div>
                                                {* services fields *}
                                                <div class="form-group">
                                                    <label class="control-label fieldLabel col-sm-4" style="font-weight: normal">
                                                        *{vtranslate('LBL_SERVICES_FIELDS',$MODULE)}:
                                                    </label>
                                                    <div class="controls col-sm-8">
                                                        <div class="input-group">
                                                            <select name="servicesfields" id="servicesfields" class="select2 form-control">
                                                                {html_options  options=$SERVICES_FIELDS}
                                                            </select>
                                                            <span class="input-group-btn">
                                                                 <button type="button" class="btn btn-success InsertIntoTemplate" data-type="servicesfields" title="{vtranslate('LBL_INSERT_VARIABLE_TO_TEXT',$MODULE)}"><i class="fa fa-usd"></i></button>
                                                            </span>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="form-group">
                                                    <div class="controls col-sm-12">
                                                        <label class="muted">{vtranslate('LBL_PRODUCT_FIELD_INFO',$MODULE)}</label>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="tab-pane" id="pdfContentHeaderFooter">
                                        <div class="edit-template-content col-lg-4" style="position:fixed;z-index:1000;">
                                            <br />
                                            {********************************************* Header/Footer *************************************************}
                                            <div id="headerfooter_div">
                                                {if $IS_BLOCK neq true}
                                                    {* pdf format settings *}
                                                    {foreach from=$BLOCK_TYPES key=BLOCKID item=BLOCK_TYPE}
                                                    <div class="form-group">
                                                        <label class="control-label fieldLabel col-sm-3" style="font-weight: normal">
                                                            {$BLOCK_TYPE["name"]}:
                                                        </label>
                                                        <div class="controls col-sm-9">
                                                            <div class="blocktypeselect">
                                                                <select name="blocktype{$BLOCKID}_val" id="blocktype{$BLOCKID}_val" data-type="{$BLOCKID}" class="select2 col-sm-12">
                                                                    {html_options  options=$BLOCK_TYPE["types"] selected=$BLOCK_TYPE["selected"]}
                                                                </select>
                                                            </div>
                                                            <div id="blocktype{$BLOCKID}" class="{if $BLOCK_TYPE["selected"] eq "custom"}hide{/if}">
                                                                <select name="blocktype{$BLOCKID}_list" id="blocktype{$BLOCKID}_list" class="select2 col-sm-12">
                                                                    {foreach  item=BLOCK_TYPE_DATA from=$BLOCK_TYPE["list"]}
                                                                        <option value="{$BLOCK_TYPE_DATA["templateid"]}" {if $BLOCK_TYPE_DATA["templateid"] eq $BLOCK_TYPE["selectedid"]}selected{/if}>{$BLOCK_TYPE_DATA["name"]}</option>
                                                                    {/foreach}
                                                                </select>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    {/foreach}
                                                {/if}


                                                {* pdf header variables*}
                                                <div class="form-group" id="header_variables">
                                                    <label class="control-label fieldLabel col-sm-3" style="font-weight: normal">
                                                        {vtranslate('LBL_HEADER_FOOTER_VARIABLES',$MODULE)}:
                                                    </label>
                                                    <div class="controls col-sm-9">
                                                        <div class="input-group">
                                                            <select name="header_var" id="header_var" class="select2 form-control">
                                                                {html_options  options=$HEAD_FOOT_VARS selected=""}
                                                            </select>
                                                            <div class="input-group-btn">
                                                                <button type="button" class="btn btn-success InsertIntoTemplate" data-type="header_var" title="{vtranslate('LBL_INSERT_TO_TEXT',$MODULE)}"><i class="fa fa-usd"></i></button>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                                {* don't display header on first page *}
                                                <div class="form-group">
                                                    <label class="control-label fieldLabel col-sm-3" style="font-weight: normal">
                                                        {vtranslate('LBL_DISPLAY_HEADER',$MODULE)}:
                                                    </label>
                                                    <div class="controls col-sm-9">
                                                        <b>{vtranslate('LBL_ALL_PAGES',$MODULE)}</b>&nbsp;<input type="checkbox" id="dh_allid" name="dh_all" onclick="PDFMaker_EditJs.hf_checkboxes_changed(this, 'header');" {$DH_ALL}/>
                                                        &nbsp;&nbsp;
                                                        {vtranslate('LBL_FIRST_PAGE',$MODULE)}&nbsp;<input type="checkbox" id="dh_firstid" name="dh_first" onclick="PDFMaker_EditJs.hf_checkboxes_changed(this, 'header');" {$DH_FIRST}/>
                                                        &nbsp;&nbsp;
                                                        {vtranslate('LBL_OTHER_PAGES',$MODULE)}&nbsp;<input type="checkbox" id="dh_otherid" name="dh_other" onclick="PDFMaker_EditJs.hf_checkboxes_changed(this, 'header');" {$DH_OTHER}/></div>
                                                </div>
                                                <div class="form-group">
                                                    <label class="control-label fieldLabel col-sm-3" style="font-weight: normal">
                                                        {vtranslate('LBL_DISPLAY_FOOTER',$MODULE)}:
                                                    </label>
                                                    <div class="controls col-sm-9">
                                                        <b>{vtranslate('LBL_ALL_PAGES',$MODULE)}</b>&nbsp;<input type="checkbox" id="df_allid" name="df_all" onclick="PDFMaker_EditJs.hf_checkboxes_changed(this, 'footer');" {$DF_ALL}/>
                                                        &nbsp;&nbsp;
                                                        {vtranslate('LBL_FIRST_PAGE',$MODULE)}&nbsp;<input type="checkbox" id="df_firstid" name="df_first" onclick="PDFMaker_EditJs.hf_checkboxes_changed(this, 'footer');" {$DF_FIRST}/>
                                                        &nbsp;&nbsp;
                                                        {vtranslate('LBL_OTHER_PAGES',$MODULE)}&nbsp;<input type="checkbox" id="df_otherid" name="df_other" onclick="PDFMaker_EditJs.hf_checkboxes_changed(this, 'footer');" {$DF_OTHER}/>
                                                        &nbsp;&nbsp;
                                                        {vtranslate('LBL_LAST_PAGE',$MODULE)}&nbsp;<input type="checkbox" id="df_lastid" name="df_last" onclick="PDFMaker_EditJs.hf_checkboxes_changed(this, 'footer');" {$DF_LAST}/>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="tab-pane" id="editTabProperties">
                                        <br />
                                        <div id="properties_div">
                                            {* pdf format settings *}
                                            <div class="form-group">
                                                <label class="control-label fieldLabel col-sm-3" style="font-weight: normal">
                                                    {vtranslate('LBL_PDF_FORMAT',$MODULE)}:
                                                </label>
                                                <div class="controls col-sm-9">
                                                    <select name="pdf_format" id="pdf_format" class="select2 col-sm-12" onchange="PDFMaker_EditJs.CustomFormat();">
                                                        {html_options  options=$FORMATS selected=$SELECT_FORMAT}
                                                    </select>
                                                    <table class="table showInlineTable" id="custom_format_table" {if $SELECT_FORMAT neq 'Custom'}style="display:none"{/if}>
                                                        <tr>
                                                            <td align="right" nowrap>{vtranslate('LBL_WIDTH',$MODULE)}</td>
                                                            <td>
                                                                <input type="text" name="pdf_format_width" id="pdf_format_width" class="inputElement" value="{$CUSTOM_FORMAT.width}" style="width:50px">
                                                            </td>
                                                            <td align="right" nowrap>{vtranslate('LBL_HEIGHT',$MODULE)}</td>
                                                            <td>
                                                                <input type="text" name="pdf_format_height" id="pdf_format_height" class="inputElement" value="{$CUSTOM_FORMAT.height}" style="width:50px">
                                                            </td>
                                                        </tr>
                                                    </table>
                                                </div>
                                            </div>
                                            {* pdf orientation settings *}
                                            <div class="form-group">
                                                <label class="control-label fieldLabel col-sm-3" style="font-weight: normal">
                                                    {vtranslate('LBL_PDF_ORIENTATION',$MODULE)}:
                                                </label>
                                                <div class="controls col-sm-9">
                                                    <select name="pdf_orientation" id="pdf_orientation" class="select2 col-sm-12">
                                                        {html_options  options=$ORIENTATIONS selected=$SELECT_ORIENTATION}
                                                    </select>
                                                </div>
                                            </div>
                                            {* pdf margin settings *}
                                            {assign var=margin_input_width value='50px'}
                                            {assign var=margin_label_width value='50px'}
                                            <div class="form-group">
                                                <label class="control-label fieldLabel col-sm-3" style="font-weight: normal">
                                                    {vtranslate('LBL_MARGINS',$MODULE)}:
                                                </label>
                                                <div class="controls col-sm-9">
                                                    <table class="table table-bordered">
                                                        <tr>
                                                            <td align="right" nowrap>{vtranslate('LBL_TOP',$MODULE)}</td>
                                                            <td>
                                                                <input type="text" name="margin_top" id="margin_top" class="inputElement" value="{$MARGINS.top}" style="width:{$margin_input_width}" onKeyUp="PDFMaker_EditJs.ControlNumber('margin_top', false);">
                                                            </td>
                                                        </tr>
                                                        <tr>
                                                            <td align="right" nowrap>{vtranslate('LBL_BOTTOM',$MODULE)}</td>
                                                            <td>
                                                                <input type="text" name="margin_bottom" id="margin_bottom" class="inputElement" value="{$MARGINS.bottom}" style="width:{$margin_input_width}" onKeyUp="PDFMaker_EditJs.ControlNumber('margin_bottom', false);">
                                                            </td>
                                                        </tr>
                                                        <tr>
                                                            <td align="right" nowrap>{vtranslate('LBL_LEFT',$MODULE)}</td>
                                                            <td>
                                                                <input type="text" name="margin_left"  id="margin_left" class="inputElement" value="{$MARGINS.left}" style="width:{$margin_input_width}" onKeyUp="PDFMaker_EditJs.ControlNumber('margin_left', false);">
                                                            </td>
                                                        </tr>
                                                        <tr>
                                                            <td align="right" nowrap>{vtranslate('LBL_RIGHT',$MODULE)}</td>
                                                            <td>
                                                                <input type="text" name="margin_right" id="margin_right" class="inputElement" value="{$MARGINS.right}" style="width:{$margin_input_width}" onKeyUp="PDFMaker_EditJs.ControlNumber('margin_right', false);">
                                                            </td>
                                                        </tr>
                                                    </table>
                                                </div>
                                            </div>
                                            {* decimal settings *}
                                            <div class="form-group">
                                                <label class="control-label fieldLabel col-sm-3" style="font-weight: normal">
                                                    {vtranslate('LBL_DECIMALS',$MODULE)}:
                                                </label>
                                                <div class="controls col-sm-9">
                                                    <table class="table table-bordered">
                                                        <tr>
                                                            <td align="right" nowrap>{vtranslate('LBL_DEC_POINT',$MODULE)}</td>
                                                            <td><input type="text" maxlength="2" name="dec_point" class="inputElement" value="{$DECIMALS.point}" style="width:{$margin_input_width}"/></td>
                                                        </tr>
                                                        <tr>
                                                            <td align="right" nowrap>{vtranslate('LBL_DEC_DECIMALS',$MODULE)}</td>
                                                            <td><input type="text" maxlength="2" name="dec_decimals" class="inputElement" value="{$DECIMALS.decimals}" style="width:{$margin_input_width}"/></td>
                                                        </tr>
                                                        <tr>
                                                            <td align="right" nowrap>{vtranslate('LBL_DEC_THOUSANDS',$MODULE)}</td>
                                                            <td><input type="text" maxlength="2" name="dec_thousands" class="inputElement" value="{$DECIMALS.thousands}" style="width:{$margin_input_width}"/></td>
                                                        </tr>
                                                    </table>
                                                </div>
                                            </div>
                                            {* watemark settings *}
                                            <div class="form-group">
                                                <label class="control-label fieldLabel col-sm-3" style="font-weight: normal">
                                                    {vtranslate('Watermark',$MODULE)}:
                                                </label>
                                                <div class="controls col-sm-9">
                                                    <table class="table table-bordered">
                                                        <tr>
                                                            <td align="right" nowrap width="20%">{vtranslate('Type',$MODULE)}</td>
                                                            <td>
                                                                <select name="watermark_type" id="watermark_type" class="select2 col-sm-12">
                                                                    {html_options options=$WATERMARK.types selected=$WATERMARK.type}
                                                                </select>
                                                            </td>
                                                        </tr>
                                                        <tr id="watermark_image_tr" {if $WATERMARK.type neq "image"}class="hide"{/if}>
                                                            <td align="right" nowrap >{vtranslate('Image',$MODULE)}</td>
                                                            <td>
                                                                <input type="hidden" name="watermark_img_id" class="inputElement" value="{$WATERMARK.image_id}"/>
                                                                <div id="uploadedWatermarkFileImage" {if $WATERMARK.image_name neq ""}class="hide"{/if}>
                                                                    <input type="file" name="watermark_image" class="inputElement"/>
                                                                    <div class="uploadedFileDetails">
                                                                        <div class="uploadedFileSize"></div>
                                                                        <div class="uploadFileSizeLimit redColor">
                                                                            {vtranslate('LBL_MAX_UPLOAD_SIZE',$MODULE)}&nbsp;<span class="maxUploadSize" data-value="{$MAX_UPLOAD_LIMIT_BYTES}">{$MAX_UPLOAD_LIMIT_MB}{vtranslate('MB',$MODULE)}</span>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                                <div id="uploadedWatermarkFileName" {if $WATERMARK.image_name eq ""}class="hide"{/if}>
                                                                    <a href="{$WATERMARK.image_url}">{$WATERMARK.image_name}</a>
                                                                    <span class="deleteWatermarkFile cursorPointer col-lg-1">
                                                                        <i class="alignMiddle fa fa-trash"></i>
                                                                    </span>
                                                                </div>
                                                            </td>
                                                        </tr>
                                                        <tr id="watermark_text_tr" {if $WATERMARK.type neq "text"}class="hide"{/if}>
                                                            <td align="right" nowrap>{vtranslate('Text',$MODULE)}</td>
                                                            <td><input type="text" name="watermark_text" class="inputElement getPopupUi" value="{$WATERMARK.text}"/></td>
                                                        </tr>
                                                        <tr id="watermark_alpha_tr" {if $WATERMARK.type eq "none"}class="hide"{/if}>
                                                            <td align="right" nowrap>{vtranslate('Alpha',$MODULE)}</td>
                                                            <td><input type="text" name="watermark_alpha" class="inputElement" {if $WATERMARK.alpha eq ""}placeholder="0.1"{/if} value="{$WATERMARK.alpha}"/></td>
                                                        </tr>

                                                    </table>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="tab-pane" id="editTabSettings">
                                        <br />
                                        <div id="settings_div">
                                            <div class="form-group">
                                                <label class="control-label fieldLabel col-sm-3" style="font-weight: normal">
                                                    {vtranslate('LBL_FILENAME',$MODULE)}:
                                                </label>
                                                <div class="controls col-sm-9">
                                                    <input type="text" name="nameOfFile" value="{$NAME_OF_FILE}" id="nameOfFile" class="inputElement getPopupUi">
                                                </div>
                                            </div>
                                            <div class="form-group hide">
                                                <label class="control-label fieldLabel col-sm-3" style="font-weight: normal">
                                                </label>
                                                <div class="controls col-sm-9">
                                                    <select name="filename_fields" id="filename_fields" class="select2 form-control" onchange="PDFMaker_EditJs.insertFieldIntoFilename(this.value);">
                                                        <option value="">{vtranslate('LBL_SELECT_MODULE_FIELD',$MODULE)}</option>
                                                        <optgroup label="{vtranslate('LBL_COMMON_FILEINFO',$MODULE)}">
                                                            {html_options  options=$FILENAME_FIELDS}
                                                        </optgroup>
                                                        {if $TEMPLATEID neq "" || $SELECTMODULE neq ""}
                                                            {html_options  options=$SELECT_MODULE_FIELD_FILENAME}
                                                        {/if}
                                                    </select>
                                                </div>
                                            </div>
                                            <div class="form-group">
                                                <label class="control-label fieldLabel col-sm-3" style="font-weight: normal">
                                                    {vtranslate('LBL_PDF_PASSWORD',$MODULE)}:
                                                </label>
                                                <div class="controls col-sm-9">
                                                    <input type="text" name="PDFPassword" value="{$PDF_PASSWORD}" id="PDFPassword" class="getPopupUi inputElement">
                                                </div>
                                            </div>
                                            <div class="form-group">
                                                <label class="control-label fieldLabel col-sm-3" style="font-weight: normal">
                                                    {vtranslate('LBL_DESCRIPTION',$MODULE)}:
                                                </label>
                                                <div class="controls col-sm-9">
                                                    <input name="description" type="text" value="{$DESCRIPTION}" class="inputElement" tabindex="2">
                                                </div>
                                            </div>

                                            {* ignored picklist values settings *}
                                            <div class="form-group">
                                                <label class="control-label fieldLabel col-sm-3" style="font-weight: normal">
                                                    {vtranslate('LBL_IGNORE_PICKLIST_VALUES',$MODULE)}:
                                                </label>
                                                <div class="controls col-sm-9">
                                                    <input type="text" name="ignore_picklist_values" value="{$IGNORE_PICKLIST_VALUES}" class="inputElement"/>
                                                </div>
                                            </div>

                                            {* status settings *}
                                            <div class="form-group">
                                                <label class="control-label fieldLabel col-sm-3" style="font-weight: normal">
                                                    {vtranslate('LBL_STATUS',$MODULE)}:
                                                </label>
                                                <div class="controls col-sm-9">
                                                    <select name="is_active" id="is_active" class="select2 col-sm-12" onchange="PDFMaker_EditJs.templateActiveChanged(this);">
                                                        {html_options options=$STATUS selected=$IS_ACTIVE}
                                                    </select>
                                                </div>
                                            </div>
                                            {* is default settings *}
                                            <div class="form-group">
                                                <label class="control-label fieldLabel col-sm-3" style="font-weight: normal">
                                                    {vtranslate('LBL_SETASDEFAULT',$MODULE)}:
                                                </label>
                                                <div class="controls col-sm-9">
                                                    {vtranslate('LBL_FOR_DV',$MODULE)} <input {if $IS_LISTVIEW_CHECKED eq "yes"}disabled="true"{/if} type="checkbox" id="is_default_dv" name="is_default_dv" {$IS_DEFAULT_DV_CHECKED}/>
                                                    &nbsp;&nbsp;
                                                    {vtranslate('LBL_FOR_LV',$MODULE)}&nbsp;&nbsp;<input type="checkbox" id="is_default_lv" name="is_default_lv" {$IS_DEFAULT_LV_CHECKED}/>
                                                    {* hidden variable for template order settings *}
                                                    <input type="hidden" name="tmpl_order" value="{$ORDER}" />
                                                </div>
                                            </div>
                                            {* is designated for customerportal *}
                                            <div class="form-group" id="is_portal_row">
                                                <label class="control-label fieldLabel col-sm-3" style="font-weight: normal">
                                                    {vtranslate('LBL_SETFORPORTAL',$MODULE)}:
                                                </label>
                                                <div class="controls col-sm-9">
                                                    <input type="checkbox" id="is_portal" name="is_portal" {$IS_PORTAL_CHECKED} onclick="return PDFMaker_EditJs.ConfirmIsPortal(this);"/>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    {********************************************* Sharing DIV *************************************************}
                                    <div class="tab-pane" id="editTabSharing">
    <br>
                                        <div id="sharing_div">
                                            <div class="form-group">
                                                <label class="control-label fieldLabel col-sm-3" style="font-weight: normal">
                                                    {vtranslate('LBL_TEMPLATE_OWNER',$MODULE)}:
                                                </label>
                                                <div class="controls col-sm-9">
                                                    <select name="template_owner" id="template_owner" class="select2 col-sm-12">
                                                        {html_options  options=$TEMPLATE_OWNERS selected=$TEMPLATE_OWNER}
                                                    </select>
                                                </div>
                                            </div>

                                            <div class="form-group">
                                                <label class="control-label fieldLabel col-sm-3" style="font-weight: normal">
                                                    {vtranslate('LBL_SHARING_TAB',$MODULE)}:
                                                </label>
                                                <div class="controls col-sm-9">
                                                    <select name="sharing" id="sharing" data-toogle-members="true" class="select2 col-sm-12">
                                                        {html_options options=$SHARINGTYPES selected=$SHARINGTYPE}
                                                    </select><br><br>
                                                    <select id="memberList" class="select2 col-sm-12 members op0{if $SHARINGTYPE eq "share"} fadeInx{/if}" multiple="true" name="members[]" data-placeholder="{vtranslate('LBL_ADD_USERS_ROLES', $MODULE)}" style="margin-bottom: 10px;" data-rule-required="{if $SHARINGTYPE eq "share"}true{else}false{/if}">

                                                        {foreach from=$MEMBER_GROUPS key=GROUP_LABEL item=ALL_GROUP_MEMBERS}
                                                            {assign var=TRANS_GROUP_LABEL value=$GROUP_LABEL}
                                                            {if $GROUP_LABEL eq 'RoleAndSubordinates'}
                                                                {assign var=TRANS_GROUP_LABEL value='LBL_ROLEANDSUBORDINATE'}
                                                            {/if}
                                                            {assign var=TRANS_GROUP_LABEL value={vtranslate($TRANS_GROUP_LABEL)}}
                                                            <optgroup label="{$TRANS_GROUP_LABEL}">
                                                                {foreach from=$ALL_GROUP_MEMBERS item=MEMBER}
                                                                    <option value="{$MEMBER->getId()}" data-member-type="{$GROUP_LABEL}" {if isset($SELECTED_MEMBERS_GROUP[$GROUP_LABEL][$MEMBER->getId()])}selected="true"{/if}>{$MEMBER->getName()}</option>
                                                                {/foreach}
                                                            </optgroup>
                                                        {/foreach}
                                                    </select>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                {/if}
                            </div>
                        </div>
                    </div>

                    {************************************** END OF TABS BLOCK *************************************}
                    <div class="middle-block col-xs-8">
                        {if $IS_BLOCK neq true}
                            <div id="ContentEditorTabs">
                                <ul class="nav nav-pills">
                                    <li id="bodyDivTab" class="ContentEditorTab active" data-type="body" style="margin-right: 5px">
                                        <a href="#body_div2" aria-expanded="false" data-toggle="tab">{vtranslate('LBL_BODY',$MODULE)}</a>
                                    </li>
                                    <li id="headerDivTab" class="ContentEditorTab" data-type="header" style="margin: 0px 5px 0px 5px">
                                        <a href="#header_div2" aria-expanded="false" data-toggle="tab">{vtranslate('LBL_HEADER_TAB',$MODULE)}</a>
                                    </li>
                                    <li id="footerDivTab" class="ContentEditorTab" data-type="footer" style="margin: 0px 5px 0px 5px">
                                        <a href="#footer_div2" aria-expanded="false" data-toggle="tab">{vtranslate('LBL_FOOTER_TAB',$MODULE)}</a>
                                    </li>
                                    {if $STYLES_CONTENT neq ""}
                                        <li data-type="templateCSSStyleTabLayout" class="ContentEditorTab" style="margin: 0px 5px 0px 5px">
                                            <a href="#cssstyle_div2" aria-expanded="false" data-toggle="tab">{vtranslate('LBL_CSS_STYLE_TAB',$MODULE)}</a>
                                        </li>
                                    {/if}
                                </ul>
                            </div>
                        {/if}
                        {*********************************************BODY DIV*************************************************}
                        <div class="tab-content">
                            <div class="tab-pane ContentTabPanel active" id="body_div2">
                                <textarea name="body" id="body" style="width:90%;height:700px" class=small tabindex="5">{$BODY}</textarea>
                            </div>
                            {if $IS_BLOCK neq true}
                                {*********************************************Header DIV*************************************************}
                                <div class="tab-pane ContentTabPanel" id="header_div2">
                                    <textarea name="header_body" id="header_body" style="width:90%;height:200px" class="small">{$HEADER}</textarea>
                                </div>
                                {*********************************************Footer DIV*************************************************}
                                <div class="tab-pane ContentTabPanel" id="footer_div2">
                                    <textarea name="footer_body" id="footer_body" style="width:90%;height:200px" class="small">{$FOOTER}</textarea>
                                </div>

                                {if $ITS4YOUSTYLE_FILES neq ""}
                                    <div class="tab-pane ContentTabPanel" id="cssstyle_div2">
                                        {foreach item=STYLE_DATA from=$STYLES_CONTENT}
                                            <div class="hide">
                                                <textarea class="CodeMirrorContent" id="CodeMirrorContent{$STYLE_DATA.id}"   style="border: 1px solid black; " class="CodeMirrorTextarea " tabindex="5">{$STYLE_DATA.stylecontent}</textarea>
                                            </div>
                                            <table class="table table-bordered">
                                                <thead>
                                                <tr class="listViewHeaders">
                                                    <th>
                                                        <div class="pull-left">
                                                            <a href="index.php?module=ITS4YouStyles&view=Detail&record={$STYLE_DATA.id}" target="_blank">{$STYLE_DATA.name}</a>
                                                        </div>
                                                        <div class="pull-right actions">
                                                            <a href="index.php?module=ITS4YouStyles&view=Detail&record={$STYLE_DATA.id}" target="_blank"><i title="{vtranslate('LBL_SHOW_COMPLETE_DETAILS', $MODULE)}" class="icon-th-list alignMiddle"></i></a>&nbsp;
                                                            {if $STYLE_DATA.iseditable eq "yes"}
                                                                <a href="index.php?module=ITS4YouStyles&view=Edit&record={$STYLE_DATA.id}" target="_blank" class="cursorPointer"><i class="icon-pencil alignMiddle" title="{vtranslate('LBL_EDIT', $MODULE)}"></i></a>
                                                            {/if}
                                                        </div>
                                                    </th>
                                                </tr>
                                                </thead>
                                                <tbody>
                                                <tr>
                                                    <td id="CodeMirrorContent{$STYLE_DATA.id}Output" class="cm-s-default">

                                                    </td>
                                                </tr>
                                                </tbody>
                                            </table>
                                            <br>
                                        {/foreach}
                                    </div>
                                {/if}
                            {/if}
                        </div>
                        <div class="hide">
                            <textarea id="fontawesomeclass">
                                {$FONTAWESOMECLASS}
                            </textarea>
                        </div>
                        <script type="text/javascript">
                            {literal} jQuery(document).ready(function(){{/literal}
                                {literal}
                                var stylecontent = jQuery("#fontawesomeclass").val();
                                CKEDITOR.addCss(stylecontent);

                                {/literal}
                                {if $ITS4YOUSTYLE_FILES neq ""}
                                    {literal}
                                    jQuery('.CodeMirrorContent').each(function(index,Element) {
                                        var stylecontent = jQuery(Element).val();
                                        CKEDITOR.addCss(stylecontent);
                                    });
                                    {/literal}
                                {/if}
                                {literal}CKEDITOR.replace('body', {height: '1000'});{/literal}
                                {if $IS_BLOCK neq true}
                                    {literal}
                                    CKEDITOR.replace('header_body', {height: '1000'});
                                    CKEDITOR.replace('footer_body', {height: '1000'});
                                    {/literal}
                                {/if}
                            {literal}}){/literal}
                        </script>
                    </div>
                </div>
            </div>
        </div>
        <div class="modal-overlay-footer row-fluid">
            <div class="textAlignCenter ">
                <button class="btn" type="submit" onclick="document.EditView.redirect.value = 'false';" ><strong>{vtranslate('LBL_APPLY',$MODULE)}</strong></button>&nbsp;&nbsp;
                <button class="btn btn-success" type="submit" ><strong>{vtranslate('LBL_SAVE', $MODULE)}</strong></button>
                {if $smarty.request.return_view neq ''}
                    <a class="cancelLink" type="reset" onclick="window.location.href = 'index.php?module={if $smarty.request.return_module neq ''}{$smarty.request.return_module}{else}PDFMaker{/if}&view={$smarty.request.return_view}{if $smarty.request.templateid neq ""  && $smarty.request.return_view neq "List"}&templateid={$smarty.request.templateid}{/if}';">{vtranslate('LBL_CANCEL', $MODULE)}</a>
                {else}
                    <a class="cancelLink" type="reset" onclick="javascript:window.history.back();">{vtranslate('LBL_CANCEL', $MODULE)}</a>
                {/if}            			
            </div>
            <div align="center" class="small" style="color: rgb(153, 153, 153);">{vtranslate('PDF_MAKER',$MODULE)} {$VERSION} {vtranslate('COPYRIGHT',$MODULE)}</div>
        </div>
    </form>
    <div class="hide" style="display: none">
        <div id="div_vat_block_table">{$VATBLOCK_TABLE}</div>
        <div id="div_company_header_signature">{$COMPANY_HEADER_SIGNATURE}</div>
        <div id="div_company_stamp_signature">{$COMPANY_STAMP_SIGNATURE}</div>
        <div class="popupUi modal-dialog modal-md" data-backdrop="false">
            <div class="modal-content">
                {assign var=HEADER_TITLE value={vtranslate('LBL_SET_VALUE',$QUALIFIED_MODULE)}}
                {include file="ModalHeader.tpl"|vtemplate_path:$MODULE TITLE=$HEADER_TITLE}
                <div class="modal-body">
                    <div class="row">
                        <div class="col-sm-12" >
                            <div class="form-group">
                                <label class="control-label fieldLabel col-sm-3" style="font-weight: normal">{vtranslate('LBL_MODULENAMES',$MODULE)}:
                                </label>
                                <div class="controls col-sm-9">
                                    <div class="input-group">
                                        <select name="filename_fields2" id="filename_fields2" class="form-control">
                                            {if $TEMPLATEID eq "" && $SELECTMODULE eq ""}
                                                <option value="">{vtranslate('LBL_SELECT_MODULE_FIELD',$MODULE)}</option>
                                            {else}
                                                {html_options  options=$SELECT_MODULE_FIELD}
                                            {/if}
                                        </select>
                                        <div class="input-group-btn">
                                            <button type="button" class="btn btn-success InsertIntoTextarea" data-type="filename_fields2" title="{vtranslate('LBL_INSERT_TO_TEXT',$MODULE)}"><i class="fa fa-usd"></i></button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <br>
                    <div class="row">
                        <div class="col-sm-12" >
                            <div class="form-group">
                                <label class="control-label fieldLabel col-sm-3" style="font-weight: normal">
                                    {vtranslate('LBL_RELATED_MODULES',$MODULE)}:
                                </label>
                                <div class="controls col-sm-9">
                                    <select name="relatedmodulesorce2" id="relatedmodulesorce2" class="form-control">
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>
                    <br>
                    <div class="row">
                        <div class="col-sm-12">
                            <div class="form-group">
                                <label class="control-label fieldLabel col-sm-3" style="font-weight: normal">
                                </label>
                                <div class="controls col-sm-9">
                                    <div class="input-group">
                                        <select name="relatedmodulefields2" id="relatedmodulefields2" class="form-control">
                                            <option value="">{vtranslate('LBL_SELECT_MODULE_FIELD',$MODULE)}</option>
                                        </select>
                                        <div class="input-group-btn">
                                            <button type="button" class="btn btn-success InsertIntoTextarea" data-type="relatedmodulefields2" title="{vtranslate('LBL_INSERT_TO_TEXT',$MODULE)}"><i class="fa fa-usd"></i></button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <br>
                    <div class="row fieldValueContainer">
                        <div class="col-sm-12">
                            <textarea data-textarea="true" class="fieldValue inputElement hide" style="height: inherit;"></textarea>
                        </div>
                    </div><br>

                </div>
                {include file="ModalFooter.tpl"|vtemplate_path:$MODULE}
            </div>
        </div>
    </div>
    <div class="clonedPopUp"></div>
</div>
<script type="text/javascript">
    var selectedTab = 'properties';
    var selectedTab2 = 'body';
    var module_blocks = new Array();
 
    var selected_module = '{$SELECTMODULE}';

    var constructedOptionValue;
    var constructedOptionName;

    jQuery(document).ready(function() {

        jQuery.fn.scrollBottom = function() {
            return jQuery(document).height() - this.scrollTop() - this.height();
        };

        var $el = jQuery('.edit-template-content');
        var $window = jQuery(window);
        var top = 127;

        $window.bind("scroll resize", function() {

            var gap = $window.height() - $el.height() - 20;
            var scrollTop = $window.scrollTop();

            if (scrollTop < top - 125) {
                $el.css({
                    top: (top - scrollTop) + "px",
                    bottom: "auto"
                });
            } else {
                $el.css({
                    top: top  + "px",
                    bottom: "auto"
                });
            }
        }).scroll();
    });
</script>
{/strip}
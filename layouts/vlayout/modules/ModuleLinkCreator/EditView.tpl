{*<!--
/* ********************************************************************************
 * The content of this file is subject to the Module & Link Creator ("License");
 * You may not use this file except in compliance with the License
 * The Initial Developer of the Original Code is VTExperts.com
 * Portions created by VTExperts.com. are Copyright(C) VTExperts.com.
 * All Rights Reserved.
 * ****************************************************************************** */
-->*}

{strip}
    <div class="container-fluid editViewContainer">
        <div id="js_currentUser" class="hide noprint">{Zend_Json::encode($USER_PROFILE)}</div>
        <div id="js_config" class="hide noprint">{Zend_Json::encode($CONFIG)}</div>
        <div id="js_settings" class="hide noprint">{Zend_Json::encode($SETTINGS)}</div>

        <form class="form-horizontal recordEditView" id="EditView" name="EditView" method="post" action="index.php"
              enctype="multipart/form-data">
            <input type="hidden" name="module" value="{$MODULE}">
            <input type="hidden" name="record" value="{$RECORD_ID}">
            <input type="hidden" name="action" value="Save">
            <div class="contentHeader row-fluid">
                <h3 class="span8 textOverflowEllipsis">{vtranslate($MODULE, $MODULE)}</h3>
                <span class="pull-right">
                    <button class="btn btn-success" type="submit" disabled="disabled">
                        <strong>{vtranslate('LBL_SAVE', $MODULE)}</strong>
                    </button>
                    <a class="cancelLink" href="index.php?module={$MODULE}&view=List">{vtranslate('LBL_CANCEL', $MODULE)}</a>
                </span>
            </div>

            <div class="contentHeader row-fluid">
                <div class="alert alert-warning">
                    {vtranslate('LBL_NOTE', $MODULE)}
                </div>
            </div>

            <table id="module-link-creator-edit-table" class="table table-bordered blockContainer showInlineTable equalSplit">
                <thead>
                <tr>
                    <th class="blockHeader" colspan="4">{vtranslate('LBL_MODULE_DETAILS', $MODULE)}</th>
                </tr>
                </thead>
                <tbody>
                <tr>
                    <td class="fieldLabel medium">
                        <label class="muted pull-right marginRight10px">
                            <span class="redColor">*</span> {vtranslate('LBL_MODULE_LABEL', $MODULE)}
                        </label>
                    </td>
                    <td class="fieldValue medium">
                        <div class="row-fluid">
                            <span class="span10">
                                <input id="{$MODULE}_editView_fieldName_module_label"
                                       type="text" class="input-large nameField"
                                       name="module_label" required="required"
                                       value="CM{$RECORD->get('module_label')}"

                                       {if $RECORD_ID} readonly="readonly" {/if}/>
                            </span>
                        </div>
                    </td>
                </tr>
                <tr>
                    <td class="fieldLabel medium">
                        <label class="muted pull-right marginRight10px">
                            <span class="redColor">*</span> {vtranslate('LBL_SINGULAR_MODULE_LABEL', $MODULE)}
                        </label>
                    </td>
                    <td class="fieldValue medium">
                        <div class="row-fluid">
                            <span class="span10">
                                <input id="{$MODULE}_editView_fieldName_module_label"
                                       type="text" class="input-large nameField"
                                       name="singular_module_label" required="required"
                                       value="CM{$RECORD->get('singular_module_label')}"

                                       {if $RECORD_ID} readonly="readonly" {/if}/>
                            </span>
                        </div>
                    </td>
                </tr>
                <tr>
                    <td class="fieldLabel medium">
                        <label class="muted pull-right marginRight10px">{vtranslate('LBL_MODULE_NAME', $MODULE)}</label>
                    </td>
                    <td class="fieldValue medium">
                        <div class="row-fluid">
                            <span class="span10">
                                <input id="{$MODULE}_editView_fieldName_module_name"
                                       type="text" class="input-large nameField"
                                       name="module_name" readonly="readonly" required="required"
                                       value="CM{$RECORD->get('module_name')}">
                            </span>
                        </div>
                    </td>
                </tr>

                <tr>
                    <td class="fieldLabel medium">
                        <label class="muted pull-right marginRight10px">{vtranslate('LBL_MODULE_TYPE', $MODULE)}</label>
                    </td>
                    <td class="fieldValue medium">
                        <div class="row-fluid">
                            <span class="span10">
                                <select name="module_type" id="{$MODULE}_editView_fieldName_module_type"
                                        class="select2" readonly="readonly" required="required">
                                    {foreach key=ID item=LABEL from=$MODULE_TYPES}
                                        <option value="{$ID}" {if $MOULE_TYPE_VALUE eq $ID} selected="selected" {/if}>
                                            {vtranslate($LABEL, $MODULE)}
                                        </option>
                                    {/foreach}
                                </select>
                            </span>
                        </div>
                    </td>
                </tr>
                <tr>
                    <td class="fieldLabel medium">
                        <label class="muted pull-right marginRight10px">{vtranslate('LBL_FIELDS', $MODULE)}</label>
                    </td>
                    <td class="fieldValue medium">
                        <div class="row-fluid">
                            <span class="span10">
                                <select name="module_fields[]" id="{$MODULE}_editView_fieldName_module_fields"
                                        class="select2" readonly="readonly" multiple="multiple">
                                    {foreach key=KEY item=ITEM from=$MODULE_FIELDS}
                                        <option value="{$KEY}" data-info='{Zend_Json::encode($ITEM)}'
                                                selected="selected">
                                            {vtranslate($ITEM['fieldlabel'], $MODULE)}
                                        </option>
                                    {/foreach}
                                </select>
                            </span>
                        </div>
                    </td>
                </tr>
                <tr>
                    <td class="fieldLabel medium">
                        <label class="muted pull-right marginRight10px">
                            {vtranslate('LBL_LIST_VIEW_FILTER_FIELDS', $MODULE)}
                        </label>
                    </td>
                    <td class="fieldValue medium">
                        <div class="row-fluid">
                            <span class="span10">
                                <select id="{$MODULE}_editView_fieldName_module_list_view_filter_fields"
                                        name="module_list_view_filter_fields[]"
                                        class="select2" readonly="readonly" multiple="multiple">
                                    {foreach key=KEY item=ITEM from=$MODULE_LIST_VIEW_FILTER_FIELDS}
                                        <option value="{$KEY}" data-info='{Zend_Json::encode($ITEM)}'
                                                selected="selected">
                                            {vtranslate($ITEM['fieldlabel'], $MODULE)}
                                        </option>
                                    {/foreach}
                                </select>
                            </span>
                        </div>
                    </td>
                </tr>
                <tr>
                    <td class="fieldLabel medium">
                        <label class="muted pull-right marginRight10px">{vtranslate('LBL_SUMMARY_FIELDS', $MODULE)}</label>
                    </td>
                    <td class="fieldValue medium">
                        <div class="row-fluid">
                            <span class="span10">
                                <select id="{$MODULE}_editView_fieldName_module_summary_fields" name="module_summary_fields[]"
                                        class="select2" readonly="readonly" multiple="multiple">
                                    {foreach key=KEY item=ITEM from=$MODULE_SUMMARY_FIELDS}
                                        <option value="{$KEY}" data-info='{Zend_Json::encode($ITEM)}'
                                                selected="selected">
                                            {vtranslate($ITEM['fieldlabel'], $MODULE)}
                                        </option>
                                    {/foreach}
                                </select>
                            </span>
                        </div>
                    </td>
                </tr>
                <tr>
                    <td class="fieldLabel medium">
                        <label class="muted pull-right marginRight10px">
                            {vtranslate('LBL_QUICK_CREATE_FIELDS', $MODULE)}
                        </label>
                    </td>
                    <td class="fieldValue medium">
                        <div class="row-fluid">
                            <span class="span10">
                                <select id="{$MODULE}_editView_fieldName_module_quick_create_fields"
                                        name="module_quick_create_fields[]"
                                        class="select2" readonly="readonly" multiple="multiple">
                                    {foreach key=KEY item=ITEM from=$MODULE_QUICK_CREATE_FIELDS}
                                        <option value="{$KEY}" data-info='{Zend_Json::encode($ITEM)}'
                                                selected="selected">
                                            {vtranslate($ITEM['fieldlabel'], $MODULE)}
                                        </option>
                                    {/foreach}
                                </select>
                            </span>
                        </div>
                    </td>
                </tr>
                <tr>
                    <td class="fieldLabel medium">
                        <label class="muted pull-right marginRight10px">{vtranslate('LBL_LINKED_MODULES', $MODULE)}</label>
                    </td>
                    <td class="fieldValue medium">
                        <div class="row-fluid">
                            <span class="span10">
                                <select id="{$MODULE}_editView_fieldName_module_links" name="module_links[]"
                                        class="select2" readonly="readonly" multiple="multiple">
                                    {foreach key=KEY item=ITEM from=$MODULE_LINKS}
                                        <option value="{$ITEM['module_name']}" data-info='{Zend_Json::encode($ITEM)}'
                                                selected="selected">
                                            {vtranslate($ITEM['module_label'], $MODULE)}
                                        </option>
                                    {/foreach}
                                </select>
                            </span>
                        </div>
                    </td>
                </tr>
                </tbody>
            </table>
            <br>

            <div class="row-fluid">
                <div class="pull-right">
                    <button class="btn btn-success" type="submit" disabled="disabled">
                        <strong>{vtranslate('LBL_SAVE', $MODULE)}</strong>
                    </button>
                    <a class="cancelLink" type="reset" onclick="window.history.back();">{vtranslate('LBL_CANCEL', $MODULE)}</a>
                <div class="clearfix"></div>
            </div>
            <br>
        </form>
    </div>
{/strip}
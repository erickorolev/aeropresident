{*<!--
/*********************************************************************************
  ** The contents of this file are subject to the vtiger CRM Public License Version 1.0
   * ("License"); You may not use this file except in compliance with the License
   * The Original Code is:  vtiger CRM Open Source
   * The Initial Developer of the Original Code is vtiger.
   * Portions created by vtiger are Copyright (C) vtiger.
   * All Rights Reserved.
  *
 ********************************************************************************/
-->*}
{strip}
{if !empty($PAGE_INFO) AND $PAGE_INFO['total_record'] > 0}

    <div class="pull-right">
        <div  style="display: inline-block; margin-right: 5px; margin-top: 6px;vertical-align: top;">
                <span>
                    <span class="pageNumbersText" style="padding-right:5px">{if $PAGE_INFO['total_record'] > 0} {$PAGE_INFO['start_index']} {vtranslate('LBL_to', $MODULE)} {$PAGE_INFO['end_index']} {vtranslate('LBL_OF',$moduleName)} {$PAGE_INFO['total_record']} {else}<span>&nbsp;</span>{/if}</span>

                </span>
        </div>
        <div class="btn-group alignTop margin0px" style="display: inline-block">
                <button class="btn btn-default listViewPreviousPageButton"  {if $PAGE_INFO['page'] == 1} disabled {else}  data-page-number = "{$PAGE_INFO['page'] -1}" {/if} type="button"><i class="fa fa-caret-left"></i></button>
                {*<span class="dropdown" >*}
                <button class="btn btn-default dropdown-toggle listViewPageJump" type="button" data-page-number = "{$PAGE_INFO['page']}"  data-toggle="dropdown"  {if $PAGE_INFO['total_page'] eq 1} disabled {/if}>
                    <i class="vtGlyph vticon-pageJump fa fa-ellipsis-h icon" title="{vtranslate('LBL_LISTVIEW_PAGE_JUMP',$moduleName)}"></i>
                </button>
                <ul class="dropdown-menu" style="position: absolute;top: 25px;left: -90px;" >
                    <li>
                        <div class="listview-pagenum">
                            <span >{vtranslate('LBL_PAGE',$moduleName)}</span>&nbsp;
                            <strong><span>{$PAGE_INFO['page']}</span></strong>&nbsp;
                            <span >{vtranslate('LBL_OF',$moduleName)}</span>&nbsp;
                            <strong><span id="totalPageCount">{$PAGE_INFO['total_page']}</span></strong>
                        </div>
                        <div class="listview-pagejump">
                            <input type="text"  placeholder="Jump To" data-page-number="{$PAGE_INFO['page']}" class="listViewPagingInput text-center" value="{$PAGE_INFO['page']}"/>&nbsp;
                            <button type="button"  class="btn btn-success listViewPagingInputSubmit text-center" style="padding: 1px;margin-left: -5px;font-size: 10px;width: 50px;">{'GO'}</button>
                        </div>
                    </li>
                </ul>
                {*</span>*}
                <button class="btn btn-default listViewNextPageButton"  {if $PAGE_INFO['total_page'] == $PAGE_INFO['page']} disabled {else} data-page-number = "{$PAGE_INFO['page'] +1}" {/if} type="button"><i class="fa fa-caret-right"></i></button>
        </div>
    </div>
{/if}
{/strip}
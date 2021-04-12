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
                <button class="btn listViewPreviousPageButton"  {if $PAGE_INFO['page'] == 1} disabled {else}  data-page-number = "{$PAGE_INFO['page'] -1}" {/if} type="button"><span class="icon-chevron-left"></span></button>
                {*<span class="dropdown" >*}
                <button class="btn dropdown-toggle listViewPageJump" type="button" data-page-number = "{$PAGE_INFO['page']}"  data-toggle="dropdown"  {if $PAGE_INFO['total_page'] eq 1} disabled {/if}>
                    <i class="vtGlyph vticon-pageJump" title="{vtranslate('LBL_LISTVIEW_PAGE_JUMP',$moduleName)}"></i>
                </button>
                <ul class="dropdown-menu" style="position: absolute;top: 25px;left: -90px;" >
                    <li>
                        <span class="row-fluid">
                            <span class="span3 pushUpandDown2per"><span class="pull-right">{vtranslate('LBL_PAGE',$moduleName)}</span></span>
                            <span class="span4">
                                <input type="text"  class="listViewPagingInput" value="{$PAGE_INFO['page']}" style="max-width: 50px;max-height: 24px"/>
                            </span>
                            <span class="span2 textAlignCenter pushUpandDown2per">
                                &nbsp;&nbsp;{vtranslate('LBL_OF',$moduleName)}&nbsp;
                            </span>
                            <span class="span3 pushUpandDown2per totalPageCount" >{$PAGE_INFO['total_page']}</span>
                        </span>
                    </li>
                </ul>
                {*</span>*}
                <button class="btn listViewNextPageButton"  {if $PAGE_INFO['total_page'] == $PAGE_INFO['page']} disabled {else} data-page-number = "{$PAGE_INFO['page'] +1}" {/if} type="button"><span class="icon-chevron-right"></span></button>
        </div>
    </div>
{/if}
{/strip}
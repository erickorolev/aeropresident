{*+**********************************************************************************
* The contents of this file are subject to the vtiger CRM Public License Version 1.1
* ("License"); You may not use this file except in compliance with the License
* The Original Code is: vtiger CRM Open Source
* The Initial Developer of the Original Code is vtiger.
* Portions created by vtiger are Copyright (C) vtiger.
* All Rights Reserved.
************************************************************************************}
{strip}
    {assign var="FIELD_INFO" value=$FIELD_MODEL->getFieldInfo()}
    {assign var="SPECIAL_VALIDATOR" value=$FIELD_MODEL->getValidator()}
    {assign var=PICKLIST_VALUES value=$FIELD_INFO['picklistvalues']}
    {assign var=PICKLIST_COLORS value=$FIELD_INFO['picklistColors']}
<style>
    .vdProgressViewContainer {
        max-height: 32px!important;
        padding-right: 0px!important;
        margin-top: 13px;
    }
    .vdProgressViewContainer .vdProgressViewMiddleContainer {
        width: 95%;
        overflow-x: hidden;
    }
    .p-r-0 {
        padding-right: 0px!important;
    }
    .vdProgressViewContainer .vdProgressViewHeaderContainer {
        white-space: nowrap;
        display: flex;
        max-width: 100%!important;
        margin-top: -13px;
    }
    .list-inline {
        padding-left: 0;
        margin-left: -5px;
        list-style: none;
    }
    .vdProgressViewContainer .vdProgressViewHeaderContainer .vdProgressViewHeaderColumn {
        width: 128px!important;
        min-width: 128px;
        padding: 0;
        position: relative;
        padding-top: 16px;
    }
    .row ul li:first-child {
        margin-left: 0;
    }
    .vdProgressViewContainer .vdProgressViewHeaderContainer .vdProgressViewHeaderColumn .firstColumn {
        border-left: 0;
    }
    .vdProgressViewContainer .vdProgressViewHeaderContainer .vdProgressViewHeaderColumn .vdProgressViewHeaderEmpty {
        cursor: pointer;
        height: 28px;
        padding: 0 15px 0 0;
        display: inline-block;
        color: #e2e0e0;
        position: relative;
        width: 124px;
        max-width: 124px;
        border-top: 15px solid #e2e0e0;
        border-bottom: 15px solid #e2e0e0;
        border-left: 10px solid transparent;
        box-shadow: 0 -2px 0 #e2e0e0;
        margin-top: 2px;
    }
    .vdProgressViewContainer .vdProgressViewHeaderContainer .vdProgressViewHeaderColumn .vdProgressViewHeaderTitleContainer .vdProgressViewHeaderTitle {
        width: 100%;
        color: black;
        text-align: center;
    }
    .vdProgressViewContainer .vdProgressViewHeaderContainer .vdProgressViewHeaderColumn .vdProgressViewHeaderTitleContainer {
        margin-top: -10px;
        padding-left: 5px;
        width: 105px;
        max-width: 105px;
    }
    .vdProgressViewContainer .vdProgressViewHeaderContainer .vdProgressViewHeaderColumn .vdProgressViewHeaderEmpty:after {
        content: '\0000a0';
        width: 0;
        height: 0;
        border-left: 10px solid;
        border-top: 15px solid transparent;
        border-bottom: 15px solid transparent;
        display: inline-block;
        position: absolute;
        top: -15px;
        right: -10px;
    }
    .vdProgressView-Active:after {
        color: #21ba5c;
    }
    .vdProgressView-Active {
        border-top: 15px solid #21ba5c !important;
        border-bottom: 15px solid #21ba5c!important;
        border-left: 10px solid transparent!important;
        box-shadow: 0 -2px 0 #21ba5c!important;
    }
    .vdProgressView-Active .vdProgressViewHeaderTitle{
        color:#fff!important;
    }
    .vdProgressView-Finished:after {
        color: #547eba;
    }
    .vdProgressView-Finished {
        border-top: 15px solid #547eba !important;
        border-bottom: 15px solid #547eba!important;
        border-left: 10px solid transparent!important;
        box-shadow: 0 -2px 0 #547eba!important;
    }
    .vdProgressView-Finished .vdProgressViewHeaderTitle{
        color:#fff!important;
    }
    .vdProgressViewContainer .progressNavigator {
        width: 4%;
        margin-top: -8px;
    }
    .p-x-0 {
        padding-left: 0px!important;
        padding-right: 0px!important;
    }
    .vdProgressViewMainContainer .progressPrev {
        right: 32px;
        margin-top: 18px;
    }
    .cursorPointer {
        cursor: pointer;
        text-decoration: none;
    }
    .pull-left {
        float: left!important;
    }
    .f-20 {
        font-size: 20px!important;
    }
    .vdProgressViewMainContainer .progressNext {
        right: 0;
        margin-top: 18px;
    }
    .slider-wrap{
        position: relative;
        width: 100%;
        height: 150px;
        overflow-y:hidden;
        overflow-x:scroll;
    }
    .slider-wrap {
        position: relative;
        width: 100%;
        height: 230px;
        overflow-y:hidden;
        overflow-x:scroll;
        /*margin-top: 20px;*/
    }
    .slide-wrap {
        position: relative;
        width: 100%;
        top: 0;
        left: 0;
    }
    .slider-slide-wrap {
        position: absolute;
        width: 128px;
        height: 100%;
    }
    .vdProgressViewMainContainer .vdProgressViewContainer .vdProgressViewHeaderContainer .currentPicklistProgressionMarker {
        width: 0;
        height: 0;
        border-left: 8px solid transparent;
        border-right: 8px solid transparent;
        border-top: 8px solid #448aff;
        display: inline-block;
        position: relative;
        left: 43px;
        top: -34px;
    }
    #div_vdprogressview{
        display: block;
    }
    {if $LIST_COLOR}
        {if $PICKLIST_COLORS}
            {foreach item=PICKLIST_VALUE key=PICKLIST_NAME from=$PICKLIST_VALUES}
            {assign var=CLASS_NAME value="{$FIELD_MODEL->getFieldName()}_{$PICKLIST_NAME|replace:' ':'_'}"}
                    {* SalesPlatform.ru begin *}
            {* .picklistColor_{$CLASS_NAME} { *}
            .picklistColor_{Vtiger_Util_Helper::escapeCssSpecialCharacters($CLASS_NAME)} {
                    {* SalesPlatform.ru end *}
                 color: {$PICKLIST_COLORS[$PICKLIST_NAME]} !important;
                border-top: 15px solid {$PICKLIST_COLORS[$PICKLIST_NAME]} !important;
                border-bottom: 15px solid {$PICKLIST_COLORS[$PICKLIST_NAME]} !important;
                box-shadow: 0 -2px 0 {$PICKLIST_COLORS[$PICKLIST_NAME]} !important;
                padding-top: 0;
            }
            .picklistColor_{Vtiger_Util_Helper::escapeCssSpecialCharacters($CLASS_NAME)}:after {
                color: {$PICKLIST_COLORS[$PICKLIST_NAME]} !important;
            }
            {/foreach}
        {/if}
    {/if}
</style>
    <div class="vdProgressViewMainContainer" id="div_vdprogressview" >
        <div class="col-lg-12  col-md-12 col-sm-12 vdProgressViewContainer">
            <div class="vdProgressViewMiddleContainer  col-lg-11 col-md-11 col-sm-11 p-r-0 slider-wrap">
                <div class="vdProgressViewHeaderContainer list-inline slide-wrap" data-confmessage="{vtranslate('LBL_CONFIRM_MESSAGE', 'VDProgressView')}">
                    {assign var=FINISHED value=true}
                    {foreach key = key item=BAR from=$PROGRESSBARS}
                        {assign var=CLASS_NAME value="picklistColor_{$FIELD_MODEL->getFieldName()}_{$BAR|replace:' ':'_'}"}
                        <div class="vdProgressViewHeaderColumn slider-slide-wrap {if $CURRENT_STATUS eq {vtranslate($BAR,$MODULE_NAME)}}onView{/if}" data-no = "{$key}" data-value="{$BAR}" data-toggle="tooltip"
                            data-placement="bottom" data-field-name="{$FIELD_NAME}"  data-field-label="{$FIELD_LABEL}"   data-original-title="{vtranslate($BAR,$MODULE_NAME)}" data-confirm="{$CONFIRM}">
                            <div class="vdProgressViewHeaderEmpty {if $PICKLIST_COLORS[$BAR]}{$CLASS_NAME}{/if} {if $CURRENT_STATUS eq {vtranslate($BAR,$MODULE_NAME)}} vdProgressView-Active {assign var=FINISHED value=false}
                            {elseif $FINISHED and $CURRENT_STATUS}
                            vdProgressView-Finished {/if}">
                                <div class="vdProgressViewHeaderTitleContainer width100Per ">
                                    <div class="vdProgressViewHeaderTitle textOverflowEllipsis">{vtranslate($BAR,$MODULE_NAME)}</div>
                                </div>
                                {if $CURRENT_STATUS eq {vtranslate($BAR,$MODULE_NAME)}}<span class="currentPicklistProgressionMarker"></span>{/if}
                            </div>
                        </div>
                    {/foreach}
                </div>
            </div>
            <div class="progressNavigator pull-right col-lg-1 col-md-1 col-sm-1 p-x-0 m-t-8">
                <span class="progressPrev  pull-left cursorPointer">
                    <i  class="fa fa-chevron-circle-left f-20"></i>
                </span>
                        <span class="progressNext pull-right  cursorPointer">
                    <i class="fa fa-chevron-circle-right f-20"></i>
                </span>
            </div>
        </div>
    </div>
{/strip}
{*/* ********************************************************************************
* The content of this file is subject to the Related Blocks & Lists ("License");
* You may not use this file except in compliance with the License
* The Initial Developer of the Original Code is VTExperts.com
* Portions created by VTExperts.com. are Copyright(C) VTExperts.com.
* All Rights Reserved.
* ****************************************************************************** */*}

{strip}

    {assign var=WIDTHTYPE value=$USER_MODEL->get('rowheight')}
    {assign var=IS_MODULE_EDITABLE value=$RELMODULE_MODEL->isPermitted('EditView')}
    {assign var=IS_MODULE_DELETABLE value=$RELMODULE_MODEL->isPermitted('Delete')}
    {if $BLOCKTYPE eq 'block'}
        <table class="table detailview-table no-border">
            <tbody>
            {assign var=COUNTER value=0}
            <tr>
                {foreach item=FIELD_MODEL key=FIELD_NAME from=$FIELDS_LIST}
                {if $RELATED_RECORD_MODEL}
                    {assign var=FIELD_MODEL value=$FIELD_MODEL->set('fieldvalue',$RELATED_RECORD_MODEL->get($FIELD_MODEL->getFieldName()))}
                {/if}
                {if !$FIELD_MODEL->isViewableInDetailView()}
                    {continue}
                {/if}
                {if $FIELD_MODEL->get('uitype') eq "83"}
                {foreach item=tax key=count from=$TAXCLASS_DETAILS}
                {if $tax.check_value eq 1}
                {if $COUNTER eq 2}
            </tr><tr>
                {assign var="COUNTER" value=1}
                {else}
                {assign var="COUNTER" value=$COUNTER+1}
                {/if}
                <td class="fieldLabel {$WIDTHTYPE}">
                    <label class='muted'>{vtranslate($tax.taxlabel, $RELMODULE_NAME)}(%)</label>
                </td>
                <td class="fieldValue {$WIDTHTYPE}">
                             <span class="value">
                                 {$tax.percentage}
                             </span>
                </td>
                {/if}
                {/foreach}
                {else if $FIELD_MODEL->get('uitype') eq "69" || $FIELD_MODEL->get('uitype') eq "105"}
                {if $COUNTER neq 0}
                {if $COUNTER eq 2}
            </tr><tr>
                {assign var=COUNTER value=0}
                {/if}
                {/if}
                <td class="fieldLabel {$WIDTHTYPE}"><label class="muted">{vtranslate({$FIELD_MODEL->get('label')},{$RELMODULE_NAME})}</label></td>
                <td class="fieldValue {$WIDTHTYPE}">
                    <div id="imageContainer" width="300" height="200">
                        {foreach key=ITER item=IMAGE_INFO from=$IMAGE_DETAILS}
                            {if !empty($IMAGE_INFO.path) && !empty({$IMAGE_INFO.orgname})}
                                <img src="{$IMAGE_INFO.path}_{$IMAGE_INFO.orgname}" width="300" height="200">
                            {/if}
                        {/foreach}
                    </div>
                </td>
                {assign var=COUNTER value=$COUNTER+1}
                {else}
                {if $FIELD_MODEL->get('uitype') eq "20" or $FIELD_MODEL->get('uitype') eq "19"}
                {if $COUNTER eq '1'}
                <td class="{$WIDTHTYPE}"></td><td class="{$WIDTHTYPE}"></td></tr><tr>
                {assign var=COUNTER value=0}
                {/if}
                {/if}
                {if $COUNTER eq 2}
            </tr><tr>
                {assign var=COUNTER value=1}
                {else}
                {assign var=COUNTER value=$COUNTER+1}
                {/if}
                <td class="fieldLabel {$WIDTHTYPE}" id="{$RELMODULE_NAME}_detailView_fieldLabel_{$FIELD_MODEL->getName()}" {if $FIELD_MODEL->getName() eq 'description' or $FIELD_MODEL->get('uitype') eq '69'} style='width:8%'{/if}>
                    <label class="muted marginRight10px">
                        {vtranslate({$FIELD_MODEL->get('label')},{$RELMODULE_NAME})}
                        {if ($FIELD_MODEL->get('uitype') eq '72') && ($FIELD_MODEL->getName() eq 'unit_price')}
                            ({$BASE_CURRENCY_SYMBOL})
                        {/if}
                    </label>
                </td>
                <td class="fieldValue {$WIDTHTYPE}" id="{$RELMODULE_NAME}_detailView_fieldValue_{$FIELD_MODEL->getName()}" {if $FIELD_MODEL->get('uitype') eq '19' or $FIELD_MODEL->get('uitype') eq '20'} colspan="" {assign var=COUNTER value=$COUNTER+1} {/if}>
                    <span class="value" data-field-type="{$FIELD_MODEL->getFieldDataType()}" {if $FIELD_MODEL->get('uitype') eq '19' or $FIELD_MODEL->get('uitype') eq '20' or $FIELD_MODEL->get('uitype') eq '21'} style="white-space:normal;" {/if}>
                        {if ($RELMODULE_NAME eq 'Calendar' OR $RELMODULE_NAME eq 'Events')
                                AND ($FIELD_MODEL->getName() == 'date_start' OR $FIELD_MODEL->getName() == 'due_date')}
                            {assign var=DATE_FIELD value=$FIELD_MODEL}
                            {assign var=DATE_TIME_VALUE value=$FIELD_MODEL->get('fieldvalue')}
                            {* Set the date after converting with repsect to timezone *}
                            {assign var=DATE_TIME_CONVERTED_VALUE value=DateTimeField::convertToUserTimeZone($DATE_TIME_VALUE)->format('Y-m-d H:i:s')}
                            {assign var=DATE_TIME_COMPONENTS value=explode(' ' ,$DATE_TIME_CONVERTED_VALUE)}
                            {assign var=DATE_FIELD value=$DATE_FIELD->set('fieldvalue',$DATE_TIME_COMPONENTS[0])}
                            {$DATE_FIELD->getDisplayValue($DATE_FIELD->get('fieldvalue'), $RELATED_RECORD_MODEL->getId(), $RELATED_RECORD_MODEL)}
                            {assign var=DATE_FIELD value=$DATE_FIELD->set('fieldvalue',$DATE_TIME_VALUE)}
                        {else}
                            {include file=vtemplate_path($FIELD_MODEL->getUITypeModel()->getDetailViewTemplateName(),$RELMODULE_NAME) FIELD_MODEL=$FIELD_MODEL USER_MODEL=$USER_MODEL MODULE=$RELMODULE_NAME RECORD=$RELATED_RECORD_MODEL}
                        {/if}
                     </span>
                    {if $FIELD_MODEL->isEditable() eq 'true' && ($FIELD_MODEL->getFieldDataType()!=Vtiger_Field_Model::REFERENCE_TYPE)}
                        <span class="hide edit">
                            {if $RELATED_RECORD_MODEL->get('isEvent') eq 1}
                                {include file=vtemplate_path($FIELD_MODEL->getUITypeModel()->getTemplateName(),'Events') FIELD_MODEL=$FIELD_MODEL USER_MODEL=$USER_MODEL MODULE=$RELMODULE_NAME RECORD_STRUCTURE_MODEL = $RECORD_STRUCTURE_MODEL}
                            {else}
                                {include file=vtemplate_path($FIELD_MODEL->getUITypeModel()->getTemplateName(),$RELMODULE_NAME) FIELD_MODEL=$FIELD_MODEL USER_MODEL=$USER_MODEL MODULE=$RELMODULE_NAME RECORD_STRUCTURE_MODEL = $RECORD_STRUCTURE_MODEL}
                            {/if}

                            <br />
                            <a href="javascript:void(0);" data-field-name="{$FIELD_MODEL->getFieldName()}{if $FIELD_MODEL->get('uitype') eq '33'}[]{/if}" data-record-id="{$RELATED_RECORD_MODEL->getId()}" data-rel-module="{$RELMODULE_NAME}" class="hoverEditSave">{vtranslate('LBL_SAVE')}</a> |
                            <a href="javascript:void(0);" class="hoverEditCancel">{vtranslate('LBL_CANCEL')}</a>
                        </span>
                    {/if}
                </td>
                {/if}

                {if $FIELDS_LIST|@count eq 1 and $FIELD_MODEL->get('uitype') neq "19" and $FIELD_MODEL->get('uitype') neq "20" and $FIELD_MODEL->get('uitype') neq "30" and $FIELD_MODEL->get('name') neq "recurringtype" and $FIELD_MODEL->get('uitype') neq "69" and $FIELD_MODEL->get('uitype') neq "105"}
                    <td class="fieldLabel {$WIDTHTYPE}"></td><td class="{$WIDTHTYPE}"></td>
                {/if}
                {/foreach}
                {* adding additional column for odd number of fields in a block *}
                {if $FIELDS_LIST|@end eq true and $FIELDS_LIST|@count neq 1 and $COUNTER eq 1}
                    <td class="fieldLabel {$WIDTHTYPE}"></td><td class="{$WIDTHTYPE}"></td>
                {/if}
            </tr>
            </tbody>
        </table>
        {*{if $smarty.foreach.related_records_block.last}{else}<hr style="border: 10px solid #cccccc; margin: 0px;" />{/if}*}
        <div class="row relatedRecordActions" style="background-color: #cccccc;padding: 5px 0px; margin: 0">
            {if $IS_MODULE_DELETABLE}
                <a class="relatedBtnDelete pull-right" data-rel-module="{$RELMODULE_NAME}" data-record-id="{$RELATED_RECORD_MODEL->getId()}" style="margin-right:25px; color: #0088cc">{vtranslate('LBL_DELETE')}</a>
                &nbsp;&nbsp;
            {/if}
            {if $IS_MODULE_EDITABLE}
                <a class="relatedBtnEdit pull-right" href="index.php?module={$RELMODULE_NAME}&amp;view=Edit&amp;record={$RELATED_RECORD_MODEL->getId()}&sourceModule={$SOURCE_MODULE}&sourceRecord={$SOURCE_RECORD}&relationOperation=true" style="margin-right:40px; color: #0088cc">{vtranslate('LBL_EDIT')}</a>
                &nbsp;&nbsp;
            {/if}
            <a class="relatedBtnView pull-right" target="_blank" href="index.php?module={$RELMODULE_NAME}&amp;view=Detail&amp;record={$RELATED_RECORD_MODEL->getId()}&amp;mode=showDetailViewByMode&amp;requestMode=full" style="margin-right:50px; color: #0088cc">{vtranslate('LBL_VIEW','RelatedBlocksLists')}</a>
        </div>
    {else}
        <td style="width: 5%;" class="fieldValue">
            <div class="actions pull-left" style="width: 74px;">
                <span class="actionImages">
                    <a target="_blank" href="index.php?module={$RELMODULE_NAME}&amp;view=Detail&amp;record={$RELATED_RECORD_MODEL->getId()}&amp;mode=showDetailViewByMode&amp;requestMode=full">
                        <i class="fa fa-eye icon alignMiddle" title="Complete Details"></i>
                    </a>&nbsp;&nbsp;
                    {if $IS_MODULE_EDITABLE}
                        <a href="index.php?module={$RELMODULE_NAME}&amp;view=Edit&amp;record={$RELATED_RECORD_MODEL->getId()}&sourceModule={$SOURCE_MODULE}&sourceRecord={$SOURCE_RECORD}&relationOperation=true">
                            <i class="fa fa-pencil alignMiddle" title="Edit"></i>
                        </a>&nbsp;&nbsp;
                    {/if}
                    {if $IS_MODULE_DELETABLE}
                        <a class="relatedBtnDelete" data-rel-module="{$RELMODULE_NAME}" data-record-id="{$RELATED_RECORD_MODEL->getId()}"><i class="fa fa-trash alignMiddle" title="Delete"></i></a>
                    {/if}
                </span>
            </div>
        </td>
        {foreach item=FIELD_MODEL from=$FIELDS_LIST name=fields_list_data}
            {assign var=FIELD_MODEL value=$FIELD_MODEL->set('fieldvalue',$RELATED_RECORD_MODEL->get($FIELD_MODEL->getFieldName()))}
            {if $FIELD_MODEL->isEditable() eq 'true'}
                <td class="fieldValue {$WIDTHTYPE}" {if $FIELD_MODEL->get('uitype') eq '19' or $FIELD_MODEL->get('uitype') eq '20'} colspan="" {/if} style="white-space:nowrap;">
                    <div class="row-fluid">
                         <span class="value" data-field-type="{$FIELD_MODEL->getFieldDataType()}" {if $FIELD_MODEL->get('uitype') eq '19' or $FIELD_MODEL->get('uitype') eq '20' or $FIELD_MODEL->get('uitype') eq '21'} style="white-space:normal;" {/if}>
                             {if ($RELMODULE_NAME eq 'Calendar' OR $RELMODULE_NAME eq 'Events')
                                    AND ($FIELD_MODEL->getName() == 'date_start' OR $FIELD_MODEL->getName() == 'due_date')}
                                 {if $FIELD_MODEL->getName() == 'date_start'}
                                     {assign var=DATE_FIELD value=$FIELD_MODEL}
                                 {else if $FIELD_MODEL->getName() == 'due_date'}
                                     {assign var=DATE_FIELD value=$FIELD_MODEL}
                                 {/if}
                                 {assign var=DATE_TIME_VALUE value=$FIELD_MODEL->get('fieldvalue')}
                                 {* Set the date after converting with repsect to timezone *}
                                 {assign var=DATE_TIME_CONVERTED_VALUE value=DateTimeField::convertToUserTimeZone($DATE_TIME_VALUE)->format('Y-m-d H:i:s')}
                                 {assign var=DATE_TIME_COMPONENTS value=explode(' ' ,$DATE_TIME_CONVERTED_VALUE)}
                                 {assign var=DATE_FIELD value=$DATE_FIELD->set('fieldvalue',$DATE_TIME_COMPONENTS[0])}
                                 {$DATE_FIELD->getDisplayValue($DATE_FIELD->get('fieldvalue'), $RELATED_RECORD_MODEL->getId(), $RELATED_RECORD_MODEL)}
                                 {assign var=DATE_FIELD value=$DATE_FIELD->set('fieldvalue',$DATE_TIME_VALUE)}
                             {else}
                                 {include file=vtemplate_path($FIELD_MODEL->getUITypeModel()->getDetailViewTemplateName(),$RELMODULE_NAME) FIELD_MODEL=$FIELD_MODEL USER_MODEL=$USER_MODEL MODULE=$RELMODULE_NAME RECORD=$RELATED_RECORD_MODEL}
                             {/if}
                         </span>
                        {if $FIELD_MODEL->isEditable() eq 'true' && ($FIELD_MODEL->getFieldDataType()!=Vtiger_Field_Model::REFERENCE_TYPE)}
                            <span class="hide edit">
                                {if $RELATED_RECORD_MODEL->get('isEvent') eq 1}
                                    {include file=vtemplate_path($FIELD_MODEL->getUITypeModel()->getTemplateName(),'Events') FIELD_MODEL=$FIELD_MODEL USER_MODEL=$USER_MODEL MODULE=$RELMODULE_NAME RECORD_STRUCTURE_MODEL = $RECORD_STRUCTURE_MODEL}
                                {else}
                                    {*//TASKID: 1083447 - DEV: tiennguyen - DATE: 2018/11/2 - START*}
                                    {*//NOTE: support ACF date time field*}
                                    {if vtlib_isCustomModule($RELMODULE_NAME)}
                                        {if strpos($FIELD_MODEL->get('name'),'acf_dtf')!==false}
                                            {assign var=DATE_FIELD value=$FIELD_MODEL}
                                            {assign var=MODULE_MODEL value=$RECORD_STRUCTURE_MODEL->getModule()}
                                            {assign var=TIME_FIELD value=$MODULE_MODEL->getField($FIELD_MODEL->get('name')|cat:'_time')}

                                            {assign var=DATE_TIME_VALUE value= {$FIELD_MODEL->getDisplayValue($FIELD_MODEL->get('fieldvalue'), $RELATED_RECORD_MODEL->getId(), $RELATED_RECORD_MODEL)}}
                                            {assign var=DATE_TIME_COMPONENTS value=explode(' ' ,$DATE_TIME_VALUE)}
                                            {assign var=TIME_FIELD value=$TIME_FIELD->set('fieldvalue',$DATE_TIME_COMPONENTS[1])}

                                            {* Set the date after converting with repsect to timezone *}
                                            {assign var=DATE_TIME_CONVERTED_VALUE value=DateTimeField::convertToUserTimeZone($DATE_TIME_VALUE)->format('Y-m-d H:i:s')}
                                            {assign var=DATE_TIME_COMPONENTS value=explode(' ' ,$DATE_TIME_CONVERTED_VALUE)}
                                            {assign var=DATE_FIELD value=$DATE_FIELD->set('fieldvalue',$DATE_TIME_COMPONENTS[0])}
                                            <div>
                                                {include file=vtemplate_path('uitypes/Date.tpl',$MODULE) BLOCK_FIELDS=$BLOCK_FIELDS FIELD_MODEL=$DATE_FIELD}
                                            </div>
                                            <div>
                                                {include file=vtemplate_path('uitypes/Time.tpl',$MODULE) BLOCK_FIELDS=$BLOCK_FIELDS FIELD_MODEL=$TIME_FIELD FIELD_NAME=$TIME_FIELD->getFieldName()}
                                            </div>
                                        {else}
                                            {include file=vtemplate_path($FIELD_MODEL->getUITypeModel()->getTemplateName(),$RELMODULE_NAME) FIELD_MODEL=$FIELD_MODEL USER_MODEL=$USER_MODEL MODULE=$RELMODULE_NAME RECORD_STRUCTURE_MODEL = $RECORD_STRUCTURE_MODEL}
                                        {/if}
                                    {else}
                                        {include file=vtemplate_path($FIELD_MODEL->getUITypeModel()->getTemplateName(),$RELMODULE_NAME) FIELD_MODEL=$FIELD_MODEL USER_MODEL=$USER_MODEL MODULE=$RELMODULE_NAME RECORD_STRUCTURE_MODEL = $RECORD_STRUCTURE_MODEL}
                                    {/if}
                                    {*//TASKID: 1083447 - DEV: tiennguyen - DATE: 2018/11/2 - END*}
                                {/if}

                                <br />
                                <a href="javascript:void(0);" data-field-name="{$FIELD_MODEL->getFieldName()}{if $FIELD_MODEL->get('uitype') eq '33'}[]{/if}" data-record-id="{$RELATED_RECORD_MODEL->getId()}" data-rel-module="{$RELMODULE_NAME}" class="hoverEditSave">{vtranslate('LBL_SAVE')}</a> |
                                <a href="javascript:void(0);" class="hoverEditCancel">{vtranslate('LBL_CANCEL')}</a>
                            </span>
                        {/if}
                    </div>
                </td>
            {/if}
        {/foreach}

    {/if}
{/strip}
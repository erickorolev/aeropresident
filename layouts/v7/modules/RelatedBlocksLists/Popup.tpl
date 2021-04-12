{*<!--
/* ********************************************************************************
 * The content of this file is subject to the Related Blocks & Lists ("License");
 * You may not use this file except in compliance with the License
 * The Initial Developer of the Original Code is VTExperts.com
 * Portions created by VTExperts.com. are Copyright(C) VTExperts.com.
 * All Rights Reserved.
 * ****************************************************************************** */
-->*}
{strip}
	<div class="modal-dialog modal-lg">
		<div class="modal-content">
			{include file="ModalHeader.tpl"|vtemplate_path:$MODULE TITLE={vtranslate($MODULE,$MODULE)}}
			<div class="modal-body">
				<div id="popupPageContainer" class="contentsDiv col-sm-12">
					<input type="hidden" id="src_module" value="{$SOURCE_MODULE}"/>
					<input type="hidden" id="module" value="{$MODULE}"/>
					<input type="hidden" id="parent" value="{$PARENT_MODULE}"/>
					<input type="hidden" id="related_module" value="{$RELATED_MODULE}"/>
					<input type="hidden" id="src_record" value="{$SOURCE_RECORD}"/>
					<input type="hidden" id="sourceField" value="{$SOURCE_FIELD}"/>
					<input type="hidden" id="url" value="{$GETURL}" />
					<input type="hidden" id="multi_select" value="{$MULTI_SELECT}" />
					<input type="hidden" id="currencyId" value="{$CURRENCY_ID}" />
					<input type="hidden" id="relatedParentModule" value="{$RELATED_PARENT_MODULE}"/>
					<input type="hidden" id="relatedParentId" value="{$RELATED_PARENT_ID}"/>
					<input type="hidden" id="view" name="view" value="{$VIEW}"/>
					<input type="hidden" id="relationId" value="{$RELATION_ID}" />
					<input type="hidden" id="selectedIds" name="selectedIds" value="{$SELECTEDIDS}">
					{if !empty($POPUP_CLASS_NAME)}
						<input type="hidden" id="popUpClassName" value="{$POPUP_CLASS_NAME}"/>
					{/if}
					<div id="popupContents" class="">
						{include file='PopupContents.tpl'|vtemplate_path:$MODULE_NAME}
					</div>
					<input type="hidden" class="triggerEventName" value="{$smarty.request.triggerEventName}"/>
				</div>
			</div>
		</div>
	</div>
{/strip}
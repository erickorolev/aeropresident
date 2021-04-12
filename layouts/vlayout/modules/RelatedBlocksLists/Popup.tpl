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
<div id="popupPageContainer" class="contentsDiv">
	<div class="paddingLeftRight10px">{include file='PopupSearch.tpl'|vtemplate_path:$MODULE_NAME}</div>
	<div id="popupContents" class="paddingLeftRight10px">{include file='PopupContents.tpl'|vtemplate_path:$MODULE_NAME}</div>
	<input type="hidden" class="triggerEventName" value="{$smarty.request.triggerEventName}"/>
</div>
</div>
{/strip}
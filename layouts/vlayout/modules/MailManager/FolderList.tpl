{*<!--/************************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.1
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 ************************************************************************************/-->*}

{strip}
{if $FOLDERS}
    <div id="foldersList" class="row-fluid">
        <div class="span10">
            <ul class="nav nav-list">
                {foreach item=FOLDER from=$FOLDERS}
                    <li>
                        {* SalesPlatform.ru begin *}
                        <a class="mm_folder" id='_mailfolder_{$FOLDER->name()}' href='#{$FOLDER->name()}' onclick="MailManager.clearSearchString(); MailManager.folder_open('{$FOLDER->name()}'); ">{if $FOLDER->unreadCount()}<b>{vtranslate($FOLDER->name(), $MODULE)} ({$FOLDER->unreadCount()})</b>{else}{vtranslate($FOLDER->name(), $MODULE)}{/if}</a>
                        {*<a class="mm_folder" id='_mailfolder_{$FOLDER->name()}' href='#{$FOLDER->name()}' onclick="MailManager.clearSearchString(); MailManager.folder_open('{$FOLDER->name()}'); ">{if $FOLDER->unreadCount()}<b>{$FOLDER->name()} ({$FOLDER->unreadCount()})</b>{else}{$FOLDER->name()}{/if}</a>*}
                        {* SalesPlatform.ru end *}
                    </li>
                {/foreach}
            </ul>
        </div>
    </div>
{/if}
{/strip}
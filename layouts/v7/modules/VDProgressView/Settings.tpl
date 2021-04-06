<div class="container-fluid WidgetsManage">
    <div class="widget_header row">
        <div class="col-sm-6"><h4><label>{vtranslate('Progress View', 'VDProgressView')}</label>
        </div>
    </div>
    <hr>
    <div class="clearfix"></div>
    <div class = "row">
        <div class='col-md-5'>
            <div class="foldersContainer pull-left">
                <button type="button" class="btn addButton btn-default module-buttons"
                        onclick='window.location.href = "{$MODULE_MODEL->getCreateViewUrl()}"'>
                    <div class="fa fa-plus" ></div>
                    &nbsp;&nbsp;{vtranslate('New Progress' , $MODULE)}
                </button>
            </div>
        </div>
        <div class="col-md-4">
        </div>
        <div class="col-md-3">

        </div>
    </div>
    <div class="list-content row">
        <div class="col-sm-12 col-xs-12 ">
            <div id="table-content" class="table-container" style="padding-top:0px !important;">
                <table id="listview-table" class="table listview-table">
                    <thead>
                    <tr class="listViewContentHeader">
                        <th></th>
                        <th nowrap>{vtranslate('Module', $MODULE)}</th>
                        <th nowrap>{vtranslate('Field', $MODULE)}</th>
                        <th nowrap>{vtranslate('Actions', $MODULE)}</th>
                    </tr>
                    </thead>
                    <tbody>
                    {foreach item=LISTVIEW_ENTRY from=$LISTVIEW_ENTRIES}
                    <tr class="listViewEntries" data-url = {{$MODULE_MODEL->getCreateViewUrl($LISTVIEW_ENTRY['id'])}}>
                        <td>
                            <input style="opacity: 0;" {if $LISTVIEW_ENTRY['active'] == '1'} checked value="on" {else} value="off"{/if} data-on-color="success"  data-module="{$LISTVIEW_ENTRY['module']}" data-id="{$LISTVIEW_ENTRY['id']}" type="checkbox" name="progress_status" id="progress_status">
                        </td>
                        <td>
                            <span class="vicon-{strtolower($LISTVIEW_ENTRY['module'])} module-icon"></span><span style="vertical-align: 5px;">&nbsp;{vtranslate($LISTVIEW_ENTRY['module'],$LISTVIEW_ENTRY['module'])}</span>
                        </td>
                        <td>
                            <div class="header-div">
                                <div style="padding-top: 4px;">
                                        <span class="l-value"
                                             style="vertical-align: left; padding-left: 11px;">{$LISTVIEW_ENTRY['field_label']}</span>
                                </div>
                            </div>
                        </td>
                        <td>
                            <a href="{$MODULE_MODEL->getCreateViewUrl($LISTVIEW_ENTRY['id'])}"><i class="fa fa-pencil"></i> {vtranslate('LBL_EDIT', $MODULE)}</a>
                            <a href="javascript:void(0)" data-id="{$LISTVIEW_ENTRY['id']}" id="vdprogressview_delete" style="margin-left: 10px;"><i class="fa fa-trash"></i> {vtranslate('LBL_DELETE', $MODULE)}</a>
                        </td>
                    </tr>
                    {/foreach}
                    </tbody>
                </table>
            </div>
            <div id="scroller_wrapper" class="bottom-fixed-scroll">
                <div id="scroller" class="scroller-div"></div>
            </div>
        </div>
    </div>
</div>
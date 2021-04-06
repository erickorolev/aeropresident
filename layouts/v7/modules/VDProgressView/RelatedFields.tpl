
{strip}
    {* Required to display event fields also while adding conditions *}
    <option value="">{vtranslate('Select an Option', 'VDProgressView')}</option>
    {assign var=RELATED_FIELDS value = array('15','16','33')}
    {assign var=EXCLUDED_FIELDS value = array('hdnTaxType','region_id')}
    {foreach from=$SELECTED_MODULE_FIELDS key=FIELD_LBL item=BLOCK}
        {assign var=FIELD_ACTIVE value=$BLOCK -> get('presence')}
        {if $FIELD_ACTIVE != 1 && $BLOCK ->get('uitype')|in_array:$RELATED_FIELDS && !$FIELD_LBL|in_array:$EXCLUDED_FIELDS}
            <option value="{Vtiger_Util_Helper::toSafeHTML($FIELD_LBL)}"
                {if $FIELD_LBL = decode_html($RECORDENTRIES['field_name'])}
                    selected="selected"
                {/if}>
                {vtranslate($BLOCK ->get("label"),$SELECTED_MODULE_NAME)}
            </option>
        {/if}
    {/foreach}
{/strip}
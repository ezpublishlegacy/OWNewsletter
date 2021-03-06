{*?template charset=utf-8?*}

{if is_unset( $attribute_base )}
    {def $attribute_base='ContentObjectAttribute'}
{/if}

{def $datatype_name='ownewslettermailinglist'
     $mailing_list_object = $attribute.content}

{* siteaccess list *}
{def $available_siteaccess_list = $mailing_list_object.available_siteaccess_list
     $selected_sitaccess_list = $mailing_list_object.siteaccess_list
     $auto_approve_registered_user = $mailing_list_object.auto_approve_registered_user
}

<hr>
<label>{'List options'|i18n('newsletter/datatype/ownewslettermailinglist')}</label>
<table class="list" cellspacing="0">
    <tr>
        <th>{'Can subscribe'|i18n('newsletter/datatype/ownewslettermailinglist')}</th>
        <th>{'Siteaccess'|i18n('newsletter/datatype/ownewslettermailinglist')}</th>
    </tr>
    {foreach $available_siteaccess_list as $sitaccess_name => $siteaccess_info sequence array('bglight','bgdark') as $style}
        <tr class="{$style}">
            <td><input type="checkbox" name="{$attribute_base}_{$datatype_name}_SiteaccessList_{$attribute.id}[]" value="{$sitaccess_name}" {if $selected_sitaccess_list|contains( $sitaccess_name )}checked{/if}></td>
            <td>{$sitaccess_name|wash( )} ( {$siteaccess_info.locale|wash} - {$siteaccess_info.site_url|wash} )</td>
        </tr>
    {/foreach}
</table>

<hr>
{* auto_approve_registerd_user *}
<label>{'Automatically approve subscription after user registration?'|i18n('newsletter/datatype/ownewslettermailinglist')}</label>
<input type="radio" name="{$attribute_base}_{$datatype_name}_AutoApproveRegisterdUser_{$attribute.id}" value="0"{$auto_approve_registered_user|choose(' checked', '')}/> {'No'|i18n('newsletter/datatype/ownewslettermailinglist')}
<input type="radio" name="{$attribute_base}_{$datatype_name}_AutoApproveRegisterdUser_{$attribute.id}" value="1"{$auto_approve_registered_user|choose('', ' checked')}/> {'Yes'|i18n('newsletter/datatype/ownewslettermailinglist')}
{undef}

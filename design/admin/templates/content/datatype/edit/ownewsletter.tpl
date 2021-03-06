{*?template charset=utf-8?*}
{if is_unset( $attribute_base )}
    {def $attribute_base='ContentObjectAttribute'}
{/if}

{def $datatype_name='ownewsletter'
     $newsletter_object = $attribute.content}

{def $available_siteaccess_list = $newsletter_object.available_siteaccess_list
     $available_skin_list =  $newsletter_object.available_skin_list
     $default_mailing_lists_ids = $newsletter_object.default_mailing_lists_ids
     $main_siteaccess = $newsletter_object.main_siteaccess
     $sender_name = $newsletter_object.sender_name
     $sender_email = $newsletter_object.sender_email
     $test_receiver_email = $newsletter_object.test_receiver_email_list|implode(';')
     $skin_name = $newsletter_object.skin_name
     $mail_personalizations = $newsletter_object.mail_personalizations
	 $available_mailing_lists = $newsletter_object.available_mailing_lists
}
{* default value main_siteaccess *}
{if $main_siteaccess|eq('') }
    {set $main_siteaccess = $available_siteaccess_list[0]}
{/if}

{if $sender_email|eq('') }
    {set $sender_email = ezini('MailSettings','AdminEmail')}
{/if}

{if $test_receiver_email|eq('') }
    {set $test_receiver_email = ezini('MailSettings','AdminEmail')}
{/if}

<hr>


<table class="list" cellspacing="0">
    <tr>
        <th>{'Default mailing list selection'|i18n('newsletter/datatype/ownewsletter')}</th>
        <th>{'Mailing list'|i18n('newsletter/datatype/ownewsletter')}</th>
    </tr>
    {foreach $available_mailing_lists as $available_mailing_list sequence array('bglight','bgdark') as $style}
        <tr class="{$style}">
            <td><input type="checkbox" name="{$attribute_base}_{$datatype_name}_DefaultMailingListSelection_{$attribute.id}[]" value="{$available_mailing_list.contentobject_id}" {if $default_mailing_lists_ids|contains( $available_mailing_list.contentobject_id )}checked{/if}></td>
            <td>{$available_mailing_list.name|wash( )}</td>
        </tr>
    {/foreach}
</table>
<table class="list" cellspacing="0">
    <tr>
        <th>{'Main siteaccess'|i18n('newsletter/datatype/ownewsletter')} *</th>
        <th>{'Siteaccess'|i18n('newsletter/datatype/ownewsletter')}</th>
    </tr>
    {foreach $available_siteaccess_list as $sitaccess_name => $siteaccess_info sequence array('bglight','bgdark') as $style}
        <tr class="{$style}">
            <td><input type="radio" name="{$attribute_base}_{$datatype_name}_MainSiteaccess_{$attribute.id}" value="{$sitaccess_name}" {if $main_siteaccess|eq( $sitaccess_name )}checked{/if}></td>
            <td>{$sitaccess_name|wash( )} ( {$siteaccess_info.locale|wash} - {$siteaccess_info.site_url|wash} )</td>
        </tr>
    {/foreach}
</table>

<hr>
{* sender_email *}
<label>{'Newsletter sender e-mail'|i18n('newsletter/datatype/ownewsletter')} *</label>
<input type="text" class="halfbox" name="{$attribute_base}_{$datatype_name}_SenderEmail_{$attribute.id}" value="{$sender_email}" />

{* sender_name *}
<label>{'Newsletter sender name'|i18n('newsletter/datatype/ownewsletter')}</label>
<input type="text" class="halfbox" name="{$attribute_base}_{$datatype_name}_SenderName_{$attribute.id}" value="{$sender_name}" />

<hr>
{* test_receiver_email_string *}
<label>{'Newsletter default test receiver emails (separated by ;)'|i18n('newsletter/datatype/ownewsletter')} *</label>
<input type="text" class="halfbox" name="{$attribute_base}_{$datatype_name}_TestReceiverEmail_{$attribute.id}" value="{$test_receiver_email}" />

<hr>

{* skin_name *}
<label>{'Newsletter skin name'|i18n('newsletter/datatype/ownewsletter')}</label>
{foreach $available_skin_list as $skin_name_2}
    <input type="radio" name="{$attribute_base}_{$datatype_name}_SkinName_{$attribute.id}" value="{$skin_name_2}" {if or( eq( $skin_name, $skin_name_2), eq( $available_skin_list|count(), 1) ) }checked="checked"{/if} />{$skin_name_2|wash}
{/foreach}

<hr>
<label>{'Enable newsletter personalization if data are available'|i18n('newsletter/datatype/ownewsletter')} {*# {$mail_personalizations} #*}</label>
<select name="{$attribute_base}_{$datatype_name}_MailPersonalizations_{$attribute.id}[]" multiple="multiple">
    {foreach ezini('NewsletterMailPersonalizations','AvailableMailPersonalizations','newsletter.ini') as $personalization}
        <option value="{$personalization}" {if $mail_personalizations|contains($personalization)}selected="selected"{/if}>
            {ezini(concat($personalization,'-MailPersonalizationSettings'),'Name','newsletter.ini')}
        </option>
    {/foreach}
</select>
{undef}


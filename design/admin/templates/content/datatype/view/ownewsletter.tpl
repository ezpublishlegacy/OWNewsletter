{*?template charset=utf-8?*}

{def $newsletter_object = $attribute.content}

<div class="block float-break">
    <div class="element">
        <label>{'Default mailing list selection'|i18n('newsletter/datatype/ownewsletter')}</label>
        <ul>
            {def $default_mailing_list_object = null()}
            {foreach $newsletter_object.default_mailing_lists_ids as $default_mailing_list}
                {set $default_mailing_list_object = fetch( 'content', 'object', hash( 
							'object_id', $default_mailing_list
						) )}
                <li><a href={$default_mailing_list_object.main_node.url_alias|ezurl()} target="_blanck">{$default_mailing_list_object.name}</a></li>
                {/foreach}
        </ul>
    </div>
</div>

{def $main_siteaccess_info = $newsletter_object.available_siteaccess_list[$newsletter_object.main_siteaccess]}
<div class="block float-break">
    {* main_siteaccess *}
    <div class="element">
        <label>{'Main siteaccess'|i18n('newsletter/datatype/ownewsletter')}</label>
        {$newsletter_object.main_siteaccess|wash}
    </div>
    <div class="element">
        <label>{'Main siteaccess site url'|i18n('newsletter/datatype/ownewsletter')}</label>
        {$main_siteaccess_info.site_url|wash}
    </div>
    <div class="element">
        <label>{'Main siteaccess locale'|i18n('newsletter/datatype/ownewsletter')}</label>
        {$main_siteaccess_info.locale|wash}
    </div>
</div>




<div class="block float-break">

    {* e-mail data *}
    <div class="element">
        <label>{'Newsletter sender e-mail'|i18n('newsletter/datatype/ownewsletter')}:</label> {$newsletter_object.sender_email|wash}
    </div>
    <div class="element">
        <label>{'Newsletter sender name'|i18n('newsletter/datatype/ownewsletter')}:</label> {$newsletter_object.sender_name|wash}
    </div>
    <div class="element">
        <label>{'Newsletter default test receiver emails (separated by ;)'|i18n('newsletter/datatype/ownewsletter')}:</label> {$newsletter_object.test_receiver_email_list|implode(';')|wash}
    </div>
</div>

<div class="block float-break">
    <div class="element">
        <label>{'Personalizations enabled'|i18n('newsletter/datatype/ownewsletter')}:</label>
        <ul>
            {foreach $newsletter_object.mail_personalizations as $personalization}
                <li>{ezini(concat($personalization,'-MailPersonalizationSettings'),'Name','newsletter.ini')}</li>
                {/foreach}
        </ul>

    </div>
</div>

<div class="break"></div>

{undef $newsletter_object}

<h1>{'Hello %name'|i18n( 'newsletter/resendconfigurelink/mail',,hash('%name', $newsletter_user.name ))},</h1>

<p>
    {'To edit your subscription, please visit'|i18n( 'newsletter/resendconfigurelink/mail')}
    <a href="{concat('/newsletter/configure/', $newsletter_user.hash)|ezurl('no', 'full')}">{'this link'|i18n( 'newsletter/resendconfigurelink/mail')}.</a>
</p>

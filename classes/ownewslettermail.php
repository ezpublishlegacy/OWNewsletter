<?php

/**
 * Generate output and mail
 */
class OWNewsletterMail {

	/**
	 *
	 * @var string  CRLF - windows
	 */
	const HEADER_LINE_ENDING_CRLF = "\r\n";

	/**
	 *
	 * @var string  CR   - mac
	 */
	const HEADER_LINE_ENDING_CR = "\r";

	/**
	 *
	 * @var string  LF   - UNIX-MACOSX
	 */
	const HEADER_LINE_ENDING_LF = "\n";

	/**
	 *
	 * @var string
	 */
	protected $transportMethod = 'file';

	/**
	 *
	 * @var array assosiative array for additional email Header with some variables
	 *      for better bounce parsing
	 *      for example
	 *      array['X-OWNL-Edition']=lsjdfo13uru32s
	 */
	protected $ExtraEmailHeaderItemArray = array();

	/**
	 * which header line ending should be used for mail creation
	 * @var string
	 */
	protected $HeaderLineEnding = null;

	/**
	 * Mail info for sending
	 */
	protected $newsletterSending;
	protected $senderEmail;
	protected $senderName;
	protected $subject = '';
	protected $HTMLBody = '';
	protected $plainTextBody = '';
	protected $contentType = 'plain/text';

	/**
	 * Constructor
	 *
	 * @return void
	 */
	public function __construct() {
		$this->setHeaderLineEndingFromIni();
		$this->resetExtraMailHeaders();
	}

	/**
	 * Send test newsletter to tester email addresses
	 *
	 * @param OWNewsletterSending $editonContentObjectVersion
	 * @param array $emailReceivers
	 * @return array
	 */
	function sendNewsletterTestMail( OWNewsletterSending $newsletterSending, $emailReceivers ) {
		// generate all newsletter versions
		$this->newsletterSending = $newsletterSending;
		$output = $this->newsletterSending->attribute( 'output' );
		$this->senderEmail = trim( $this->newsletterSending->attribute( 'sender_email' ) );
		$this->senderName = $this->newsletterSending->attribute( 'sender_name' );
		if ( isset( $output['subject'] ) ) {
			$this->subject = $output['subject'];
		}
		if ( isset( $output['body'] ) && isset( $output['body']['html'] ) ) {
			$this->HTMLBody = $output['body']['html'];
		}
		if ( isset( $output['body'] ) && isset( $output['body']['text'] ) ) {
			$this->plainTextBody = $output['body']['text'];
		}
		if ( isset( $output['content_type'] ) ) {
			$this->contentType = $output['content_type'];
		}
		$this->setTransportMethodPreviewFromIni();
		$sendResult = array();
		foreach ( $emailReceivers as $emailReceiver ) {
			$sendResult[] = $this->sendEmail( $emailReceiver );
		}
		return $sendResult;
	}

	/**
	 * Send test newsletter to tester email addresses
	 *
	 * @param OWNewsletterSending $editonContentObjectVersion
	 * @param array $emailReceivers
	 * @return array
	 */
	function sendNewsletter( OWNewsletterSending $newsletterSending, $limit = false, $tracker = false ) {
		// generate all newsletter versions
		$this->newsletterSending = $newsletterSending;
		if ( $tracker ) {
			$tracker->setEditionContentObjectId( $this->newsletterSending->attribute( 'edition_contentobject_id' ) );
		}
		$output = $this->newsletterSending->attribute( 'output' );
		$this->senderEmail = trim( $this->newsletterSending->attribute( 'sender_email' ) );
		$this->senderName = $this->newsletterSending->attribute( 'sender_name' );
		$personalizeContent = (boolean) $this->newsletterSending->attribute( 'personalize_content' );
		if ( isset( $output['subject'] ) ) {
			$originalSubject = $output['subject'];
		}
		if ( isset( $output['body'] ) && isset( $output['body']['html'] ) ) {
			$originalHTMLBody = $output['body']['html'];
		}
		if ( isset( $output['body'] ) && isset( $output['body']['text'] ) ) {
			$originalPlainTextBody = $output['body']['text'];
		}
		if ( isset( $output['content_type'] ) ) {
			$this->contentType = $output['content_type'];
		}
		$this->setTransportMethodCronjobFromIni();
		$sendingItemList = OWNewsletterSendingItem::fetchList( array( 'edition_contentobject_id' => $this->newsletterSending->attribute( 'edition_contentobject_id' ) ), $limit );
		$sendResult = array();
		OWScriptLogger::logNotice( count( $sendingItemList ) . "items in the mailqueue", 'prepare_sending' );
		foreach ( $sendingItemList as $sendingIndex => $sendingItem ) {
			$sendingItem->sync();
			$newsletterUser = $sendingItem->attribute( 'newsletter_user' );
			$receiverEmail = $newsletterUser->attribute( 'email' );
			if ( $sendingItem->attribute( 'status' ) == OWNewsletterSendingItem::STATUS_NEW ) {
				// Assign newsletter user to tracking
				if ( $tracker ) {
					$tracker->setNewsletterUser( $newsletterUser );
				}
				$receiverName = $newsletterUser->attribute( 'email_name' );

				// ### configure hash
				$newsletterConfigureHash = $newsletterUser->attribute( 'hash' );
				$newsletterUnsubscribeHash = $newsletterUser->attribute( 'hash' );

				$searchArray = array(
					'#_hash_unsubscribe_#',
					'#_hash_configure_#'
				);

				$replaceArray = array(
					$newsletterUnsubscribeHash,
					$newsletterConfigureHash
				);

				$subject = $originalSubject;
				$HTMLBody = $originalHTMLBody;
				$plainTextBody = $originalPlainTextBody;

				if ( $personalizeContent === true ) {
					$searchArray = array_merge( $searchArray, array(
						'[[name]]',
						'[[salutation_name]]',
						'[[first_name]]',
						'[[last_name]]'
							) );
					$replaceArray = array_merge( $replaceArray, array(
						$newsletterUser->attribute( 'name' ),
						$newsletterUser->attribute( 'salutation_name' ),
						$newsletterUser->attribute( 'first_name' ),
						$newsletterUser->attribute( 'last_name' )
							) );
				}
				if ( $tracker ) {
					$HTMLBody = $tracker->insertMarkers( $HTMLBody );
					$plainTextBody = $tracker->insertMarkers( $plainTextBody );
				}

				$this->subject = str_replace( $searchArray, $replaceArray, $subject );
				$this->HTMLBody = str_replace( $searchArray, $replaceArray, $HTMLBody );
				$this->plainTextBody = str_replace( $searchArray, $replaceArray, $plainTextBody );

				$this->resetExtraMailHeaders();
				$this->setExtraMailHeadersByNewsletterSendItem( $sendingItem );

				$sendResult[$sendingIndex] = $this->sendEmail( $receiverEmail, $receiverName );
				if ( $sendResult[$sendingIndex]['send_result'] == false ) {
					$sendingItem->setAttribute( 'status', OWNewsletterSendingItem::STATUS_ABORT );
					OWScriptLogger::logError( "Sending the newsletter to $receiverEmail failed", 'process_sending' );
				} else {
					$sendingItem->setAttribute( 'status', OWNewsletterSendingItem::STATUS_SEND );
					OWScriptLogger::logNotice( "Sending the newsletter to $receiverEmail succeeded", 'process_sending' );
				}
				$sendingItem->store();
			} else {
				OWScriptLogger::logWarning( "Mailqueue item status change. The newsletter will not be sent to $receiverEmail", 'process_sending' );
			}
		}
		return $sendResult;
	}

	/**
	 * Mainfunction for mail send
	 *
	 * @param unknown_type $emailReceiver
	 * @param boolean $isPreview
	 * @param string $emailCharset
	 * @return array
	 */
	public function sendEmail( $emailReceiver, $emailReceiverName = 'NL Test Receiver', $emailCharset = 'utf-8' ) {
		$transportMethod = $this->transportMethod;
		if ( ezcMailTools::validateEmailAddress( $emailReceiver ) ) {
			//$mail = new ezcMailComposer();
			$mail = new OWNewsletterMailComposer();
			$mail->charset = $emailCharset;
			$mail->subjectCharset = $emailCharset;
			// from and to addresses, and subject
			$mail->from = new ezcMailAddress( $this->senderEmail, $this->senderName );
			// returnpath for email bounces
			$mail->returnPath = new ezcMailAddress( $this->senderEmail );

			$mail->addTo( new ezcMailAddress( trim( $emailReceiver ), $emailReceiverName ) );

			$mail->subject = $this->subject;
			if ( !empty( $this->HTMLBody ) ) {
				$mail->htmlText = $this->HTMLBody;
			}
			if ( !empty( $this->plainTextBody ) ) {
				$mail->plainText = $this->plainTextBody;
			}

			// http://ezcomponents.org/docs/api/latest/introduction_Mail.html#mta-qmail
			// HeaderLineEnding=auto
			// CRLF - windows - \r\n
			// CR - mac - \r
			// LF - UNIX-MACOSX - \n
			// default LF
			//ezcMailTools::setLineBreak( "\n" );
			ezcMailTools::setLineBreak( $this->HeaderLineEnding );

			// set 'x-ownl-' mailheader
			foreach ( $this->ExtraEmailHeaderItemArray as $key => $value ) {
				$mail->setHeader( $key, $value );
			}

			$mail->build();
			$transport = new OWNewsletterTransport( $transportMethod );
			$sendResult = $transport->send( $mail );
			$emailResult = array(
				'send_result' => $sendResult,
				'sender_email' => $this->senderEmail,
				'email_receiver' => $emailReceiver,
				'email_subject' => $this->subject,
				'email_charset' => $emailCharset,
				'transport_method' => $transportMethod );
		} else {
			$emailResult = array(
				'send_result' => false,
				'sender_email' => $this->senderEmail,
				'email_receiver' => $emailReceiver,
				'email_subject' => $this->subject,
				'email_charset' => $emailCharset,
				'transport_method' => $transportMethod );
		}
		return $emailResult;
	}

	/**
	 * Read ini and set transport
	 *
	 * @return unknown_type
	 */
	function setTransportMethodPreviewFromIni() {
		$newsletterINI = eZINI::instance( 'newsletter.ini' );
		$transportMethodPreview = $newsletterINI->variable( 'NewsletterMailSettings', 'TransportMethodPreview' );
		$this->transportMethod = $transportMethodPreview;

		return $this->transportMethod;
	}

	/**
	 * Read ini and set transport
	 *
	 * @return unknown_type
	 */
	function setTransportMethodDirectlyFromIni() {
		$newsletterINI = eZINI::instance( 'newsletter.ini' );
		$transportMethodDirectly = $newsletterINI->variable( 'NewsletterMailSettings', 'TransportMethodDirectly' );
		$this->transportMethod = $transportMethodDirectly;

		return $this->transportMethod;
	}

	/**
	 * Read ini and set transport
	 *
	 * @return unknown_type
	 */
	function setTransportMethodCronjobFromIni() {
		$newsletterINI = eZINI::instance( 'newsletter.ini' );
		$transportMethodCronjob = $newsletterINI->variable( 'NewsletterMailSettings', 'TransportMethodCronjob' );
		$this->transportMethod = $transportMethodCronjob;
		return $this->transportMethod;
	}

	/**
	 * read header line ending settings from newsletter.ini
	 *     *
	 * http://ezcomponents.org/docs/api/latest/introduction_Mail.html#mta-qmail
	 * @return string headerlineending
	 */
	private function setHeaderLineEndingFromIni() {
		$newsletterINI = eZINI::instance( 'newsletter.ini' );
		$headerLineEndingIni = $newsletterINI->variable( 'NewsletterMailSettings', 'HeaderLineEnding' );

		switch ( $headerLineEndingIni ) {
			case 'CRLF':
				$this->HeaderLineEnding = self::HEADER_LINE_ENDING_CRLF;
				break;

			case 'CR':
				$this->HeaderLineEnding = self::HEADER_LINE_ENDING_CR;
				break;

			case 'LF':
				$this->HeaderLineEnding = self::HEADER_LINE_ENDING_LF;
				break;

			// TODO choose automatically the right settings
			case 'auto':
				$this->HeaderLineEnding = self::HEADER_LINE_ENDING_LF;
				break;

			// default line ending \n
			default:
				$this->HeaderLineEnding = self::HEADER_LINE_ENDING_LF;
				break;
		}

		return $this->HeaderLineEnding;
	}

	/**
	 * reset  $this->extraEmailHeaderItemArray and set version number
	 */
	public function resetExtraMailHeaders() {
		$this->extraEmailHeaderItemArray = array();
		$this->setExtraMailHeader( 'version', ownewsletterInfo::SOFTWARE_VERSION );
	}

	/**
	 * used by newsletter cronjob process
	 *
	 * @param OWNewsletterUser $newsletterUser
	 * @return boolean
	 */
	public function setExtraMailHeadersByNewsletterSendItem( OWNewsletterSendingItem $newsletterSendingItem ) {
		$this->setExtraMailHeadersByNewsletterUser( $newsletterSendingItem->attribute( 'newsletter_user' ) );
		$this->setExtraMailHeadersByNewsletterSending( $newsletterSendingItem->attribute( 'newsletter_sending' ) );
		$this->setExtraMailHeader( 'senditem', $newsletterSendingItem->attribute( 'hash' ) );
	}

	/**
	 * used by Newletter edition preview and newsletter cronjob process
	 *
	 * @param OWNewsletterUser $newsletterUser
	 * @return boolean
	 */
	public function setExtraMailHeadersByNewsletterUser( OWNewsletterUser $newsletterUser ) {
		$this->setExtraMailHeader( 'receiver', $newsletterUser->attribute( 'email' ) );
		$this->setExtraMailHeader( 'user', $newsletterUser->attribute( 'hash' ) );
	}

	/**
	 * used by Newletter edition preview and newsletter cronjob process
	 *
	 * @param OWNewsletterUser $newsletterUser
	 * @return boolean
	 */
	public function setExtraMailHeadersByNewsletterSending( OWNewsletterSending $newsletterSending ) {
		$this->setExtraMailHeader( 'sending', $newsletterSending->attribute( 'hash' ) );
	}

	/**
	 * Set a new extra mailheader item
	 *
	 * setExtraMailHeader( 'Version', '1.0.0' ) will add the following mail header item
	 *
	 * X-OWNl-Version : 1.0.0
	 *
	 * @param string $name
	 * @param string $value
	 * @return boolean
	 */
	public function setExtraMailHeader( $name, $value ) {
		$this->ExtraEmailHeaderItemArray['x-ownl-' . $name] = (string) $value;
		return true;
	}

}

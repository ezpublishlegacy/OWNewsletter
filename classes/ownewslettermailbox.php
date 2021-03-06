<?php

class OWNewsletterMailbox extends eZPersistentObject {

    /**
     * Store ezc...Transport object global
     */
    var $TransportObject = null;

    /**
     * constructor
     *
     * @param mixed $row
     * @return void
     */
    function __construct( $row = array() ) {
        parent::__construct( $row );
    }

    /**
     * data fields...
     *
     * @return array
     */
    static function definition() {
        return array( 'fields' => array(
                'id' => array(
                    'name' => 'Id',
                    'datatype' => 'integer',
                    'default' => 0,
                    'required' => true ),
                'email' => array(
                    'name' => 'Email',
                    'datatype' => 'string',
                    'default' => '',
                    'required' => true ),
                'server' => array(
                    'name' => 'Server',
                    'datatype' => 'string',
                    'default' => '',
                    'required' => true ),
                'port' => array(
                    'name' => 'Port',
                    'datatype' => 'integer',
                    'default' => null,
                    'required' => false ),
                'username' => array(
                    'name' => 'Username',
                    'datatype' => 'string',
                    'default' => '',
                    'required' => true ),
                'password' => array(
                    'name' => 'Password',
                    'datatype' => 'string',
                    'default' => '',
                    'required' => true ),
                'type' => array(
                    'name' => 'Type',
                    'datatype' => 'string',
                    'default' => '',
                    'required' => true ),
                'is_activated' => array(
                    'name' => 'IsActivated',
                    'datatype' => 'integer',
                    'default' => 0,
                    'required' => true ),
                'is_ssl' => array(
                    'name' => 'IsSsl',
                    'datatype' => 'integer',
                    'default' => 0,
                    'required' => true ),
                'delete_mails_from_server' => array(
                    'name' => 'DeleteMailsFromServer',
                    'datatype' => 'integer',
                    'default' => 0,
                    'required' => true ),
                'last_server_connect' => array(
                    'name' => 'LastServerConnect',
                    'datatype' => 'integer',
                    'default' => 0,
                    'required' => true ) ),
            'keys' => array( 'id' ),
            'increment_key' => 'id',
            'class_name' => 'OWNewsletterMailbox',
            'name' => 'ownl_mailbox' );
    }

    /*     * **********************
     * FUNCTION ATTRIBUTES
     * ********************** */

    /*     * **********************
     * FETCH METHODS
     * ********************** */

    /**
     * Return object by id
     *
     * @param integer $id
     * @return object or boolean
     */
    static public function fetch( $id ) {
        $object = eZPersistentObject::fetchObject( self::definition(), null, array( 'id' => $id ), true );
        return $object;
    }

    /**
     * Search all objects with custom conditions
     *
     * @param array $conds
     * @param integer $limit
     * @param integer $offset
     * @param boolean $asObject
     * @return array
     */
    static function fetchList( $conds = array(), $limit = false, $offset = false, $asObject = true ) {
        $sortArr = array(
            'id' => 'asc' );
        $limitArr = null;

        if( (int) $limit != 0 ) {
            $limitArr = array(
                'limit' => $limit,
                'offset' => $offset );
        }
        $objectList = eZPersistentObject::fetchObjectList( self::definition(), null, $conds, $sortArr, $limitArr, $asObject, null, null, null, null );
        return $objectList;
    }

    /**
     * Count all subsciptions with custom conditions
     *
     * @param array $conds
     * @return interger
     */
    static function countList( $conds = array() ) {
        $objectList = eZPersistentObject::count( self::definition(), $conds );
        return $objectList;
    }

    /*     * **********************
     * OBJECT METHODS
     * ********************** */

    /*     * **********************
     * PERSISTENT METHODS
     * ********************** */

    static function createOrUpdate( $row ) {
        $object = new self( $row );
        $object->store();
        return $object;
    }

    /*     * **********************
     * OTHER METHODS
     * ********************** */

    /**
     * connect to all mailboxes which are activated and store all mails in our db
     *
     * @return array
     */
    public static function collectMailsFromActiveMailboxes() {
        $mailboxesProcessArray = array();
        $mailboxes = self::fetchList( array( 'is_activated' => true ) );

        if( is_array( $mailboxes ) ) {
            foreach( $mailboxes as $mailbox ) {
                if( $mailbox instanceof self ) {
                    try {
                        $connectResult = $mailbox->connect();
                        if( is_object( $connectResult ) ) {
                            try {
                                $mailboxesProcessArray[$mailbox->attribute( 'id' )] = $mailbox->fetchMails();
                                $mailbox->disconnect();
                            } catch( Exception $e ) {
                                return $e->getMessage();
                            }
                        } else {
                            $mailboxesProcessArray[$mailbox->attribute( 'id' )] = 'connection failed' . $connectResult;
                        }
                    } catch( Exception $e ) {
                        return $e->getMessage();
                    }
                }
            }

            return $mailboxesProcessArray;
        } else {
            return false;
        }
    }

    /*     * **********************
     * MAIL COLLECTION METHODS
     * ********************** */

    /**
     * connect with mailaccount
     *
     * @return object / boolean
     */
    public function connect() {
        // current mailbox id
        $mailboxId = $this->attribute( 'id' );

        // current mailbox type, for pop3-/imap switch
        $mailboxType = $this->attribute( 'type' );

        // login data
        $server = $this->attribute( 'server' );
        $userName = $this->attribute( 'username' );
        $password = $this->attribute( 'password' );
        $port = $this->attribute( 'port' );
        $ssl = (boolean) $this->attribute( 'is_ssl' );
        $deleteMailsFromServer = (boolean) $this->attribute( 'delete_mails_from_server' );

        $ezcTransportObject = null;

        $settingArray = array( 'mailbox_id' => $mailboxId,
            'type' => $mailboxType,
            'server' => $server,
            'username' => $userName,
            'password' => $password,
            'port' => $port,
            'is_ssl' => $ssl,
            'delete_mails_from_server' => $deleteMailsFromServer );
        try {
            // create transport object
            switch( $mailboxType ) {
                case 'imap':
                    $options = new ezcMailImapTransportOptions();
                    $options->ssl = $ssl;
                    $options->timeout = 3;
                    $ezcTransportObject = new ezcMailImapTransport( $server, $port, $options );
                    break;
                case 'pop3':
                    $options = new ezcMailPop3TransportOptions();
                    $options->ssl = $ssl;
                    $options->timeout = 3;
                    // $options->authenticationMethod = ezcMailPop3Transport::AUTH_APOP;
                    $ezcTransportObject = new ezcMailPop3Transport( $server, $port, $options );
                    break;
                default:
                    return $e->getMessage();
            }
        } catch( Exception $e ) {
            return $e->getMessage();
        }

        try {
            // authenticate twise is not allowed
            $ezcTransportObject->authenticate( $userName, $password );
        } catch( Exception $e ) {
            return $e->getMessage();
        }

        try {

            switch( $mailboxType ) {
                case 'imap':
                    $ezcTransportObject->selectMailbox( 'Inbox' );
                    break;
            }

            $this->TransportObject = $ezcTransportObject;

            // set connect time
            $this->setAttribute( 'last_server_connect', time() );
            $this->store();

            return $ezcTransportObject;
        } catch( Exception $e ) {
            return $e->getMessage();
        }
    }

    /**
     * disconnect connection
     * @return unknown_type
     */
    public function disconnect() {
        if( is_object( $this->TransportObject ) ) {
            $this->TransportObject->disconnect();
        }
    }

    /**
     * fetch mails to parse and/or store
     *
     * @return void
     */
    public function fetchMails() {
        $statusArray = array( 'added' => array(),
            'exists' => array(),
            'failed' => array() );
        $mailboxId = $this->attribute( 'id' );
        $mailboxDeleteMailsFromServer = (boolean) $this->attribute( 'delete_mails_from_server' );

        if( is_object( $this->TransportObject ) ) {
            $transport = $this->TransportObject;

            try {
                // it is possible that not all pop3 server understand this
                // array( message_num => unique_id );
                // array( 1 => '000001fc4420e93a', 2 => '000001fd4420e93a' );
                $uniqueIdentifierArray = $transport->listUniqueIdentifiers();
            } catch( Exception $e ) {
                
            }

            try {
                // array( message_id => message_size );
                // array( 2 => 1700, 5 => 1450 );
                $messageIdArray = $transport->listMessages();
            } catch( Exception $e ) {
                
            }

            // array( message_id => message_identifier )
            $messageNumberArray = array();

            // only fetch messages from server which are not in the db
            // use message_identifier for check
            $existingMessageIdentifierArray = $this->extractAllExistingIdentifiers( $uniqueIdentifierArray );

            foreach( $messageIdArray as $messageId => $messageSize ) {
                if( isset( $uniqueIdentifierArray[$messageId] ) ) {
                    $uniqueIdentifier = $uniqueIdentifierArray[$messageId];
                } else {
                    $uniqueIdentifier = false;
                }
                if( array_key_exists( $uniqueIdentifier, $existingMessageIdentifierArray ) ) {
                    $statusArray['exists'][$messageId] = $uniqueIdentifier;
                } else {
                    $messageNumberArray[$messageId] = $uniqueIdentifier;
                }
            }

            if( count( $messageNumberArray ) > 0 ) {
                // only fetch x item at once to avoid script timeout ... if call from admin frontend
                // the cronjob may be has other settings
                $fetchLimit = 50;
                $counter = 0;

                foreach( $messageNumberArray as $messageId => $messageIdentifier ) {
                    if( $counter >= $fetchLimit ) {
                        break;
                    } else {
                        // create mailobject from message id
                        // $mailboxDeleteMailsFromServer == true, set delete flag for current message
                        $mailObject = $transport->fetchByMessageNr( $messageId, $mailboxDeleteMailsFromServer );

                        // convert mailobject to string with own function
                        $messageString = $this->convertMailToString( $mailObject );

                        if( $messageIdentifier === false ) {
                            $messageIdentifier = 'cjwnl_' . md5( $messageString );
                        }

                        // if messageString has content
                        if( $messageString != null ) {
                            // add item to DB / Filesystem
                            $addResult = OWNewsletterBounce::addItem( $mailboxId, $messageIdentifier, $messageId, $messageString );
                            if( is_object( $addResult ) ) {
                                $statusArray['added'] [$messageId] = $messageIdentifier;
                            } else {
                                $statusArray['exists'] [$messageId] = $messageIdentifier;
                            }
                            unset( $addResult );
                        } else {
                            $statusArray['failed'] [$messageId] = $messageIdentifier;
                        }
                        unset( $messageString );
                        unset( $mailObject );
                    }
                    $counter++;
                }

                // delete messages with delete flag from mailbox
                switch( $this->attribute( 'type' ) ) {
                    case 'imap':
                        $transport->expunge();
                        break;
                }
            } else {
                return $statusArray;
            }
        }
        return $statusArray;
    }

    /**
     * check if  message_idendifier are in db if not return it otherwise ignore
     * it is used to avoid connections
     * array( message_id => message_identifer )
     * @param $array
     * @return unknown_type
     */
    protected function extractAllExistingIdentifiers( $messageIdentifierArray ) {
        $existingMessageIdentifierArray = array();
        $mailboxId = (int) $this->attribute( 'id' );
        $identifierImplodeString = '';

        foreach( $messageIdentifierArray as $identifier ) {
            $identifierImplodeString .= "'$identifier',";
        }

        $db = eZDB::instance();
        $sql = "SELECT id, message_identifier FROM ownl_bounce
                WHERE mailbox_id=$mailboxId
                AND message_identifier IN ( $identifierImplodeString -1 )";
        $rows = $db->arrayQuery( $sql );
        foreach( $rows as $row ) {
            $existingMessageIdentifierArray[$row['message_identifier']] = 'item_' . $row['id'];
        }
        return $existingMessageIdentifierArray;
    }

    /**
     * convert mailobject to string
     *
     * @param object $mailObjectSet
     * @return string / boolean
     */
    public function convertMailToString( $mailObjectSet ) {
        if( is_object( $mailObjectSet ) ) {
            $rawMail = '';
            while( ( $line = $mailObjectSet->getNextLine() ) !== null ) {
                $rawMail .= $line;
            }
            return $rawMail;
        } else {
            return false;
        }
    }

}

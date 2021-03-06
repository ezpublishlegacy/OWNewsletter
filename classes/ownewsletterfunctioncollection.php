<?php

/*
 * Fetch functions for newsletter module
 */

class OWNewsletterFunctionCollection {

    /**
     * Fetch all content classes in the newsletter edition class group
     * 
     * @return array of eZContentClass
     */
    static function fetchEditionClassList() {
        return array(
            'result' => self::getEditionClassList()
        );
    }

    /**
     * Fetch all content classes in the newsletter edition class group
     * 
     * @return array of content class identifier
     */
    static function fetchEditionClassIdentifierList() {
        $classList = self::getEditionClassList();
        if( !is_array( $classList ) ) {
            return array(
                'result' => false
            );
        }
        $result = array();
        foreach( $classList as $class ) {
            $result[] = $class->attribute( 'identifier' );
        }
        return array(
            'result' => $result
        );
    }

    /**
     * Prepare the content class list
     * 
     * @return array of eZContentClass
     */
    static protected function getEditionClassList() {
        $ini = eZINI::instance( 'newsletter.ini' );
        if( !$ini->hasVariable( 'NewsletterSettings', 'NewsletterEditionContentClassGroup' ) ) {
            eZDebug::writeError( "[NewsletterSettings]NewsletterEditionContentClassGroup is missing in newsletter.ini" );
            return false;
        }
        $classGroupName = $ini->variable( 'NewsletterSettings', 'NewsletterEditionContentClassGroup' );
        $classGroup = eZContentClassGroup::fetchByName( $classGroupName );
        if( !$classGroup instanceof eZContentClassGroup ) {
            eZDebug::writeError( "Class group $classGroupName not found." );
            return false;
        }
        $classGroupID = $classGroup->attribute( 'id' );
        return eZContentClassClassGroup::fetchClassList( 0, $classGroupID );
    }

    /**
     * Fetch all content classes in the newsletter edition class group
     * 
     * @return array of eZContentClass
     */
    static function fetchEditionClassGroupID() {
        $ini = eZINI::instance( 'newsletter.ini' );
        if( !$ini->hasVariable( 'NewsletterSettings', 'NewsletterEditionContentClassGroup' ) ) {
            eZDebug::writeError( "[NewsletterSettings]NewsletterEditionContentClassGroup is missing in newsletter.ini" );
            return false;
        }
        $classGroupName = $ini->variable( 'NewsletterSettings', 'NewsletterEditionContentClassGroup' );
        $classGroup = eZContentClassGroup::fetchByName( $classGroupName );
        if( !$classGroup instanceof eZContentClassGroup ) {
            eZDebug::writeError( "Class group $classGroupName not found." );
            $classGroupID = false;
        }
        $classGroupID = $classGroup->attribute( 'id' );
        return array(
            'result' => $classGroupID
        );
    }

    /**
     * Fetch users with custom parameter
     * 
     * @param integer $mailing_list_contentobject_id
     * @param string $user_status
     * @param string $subscription_status
     * @param integer $limit
     * @param integer $offset
     * @return array of OWNewsletterSubscription
     */
    static function fetchUsers( $mailing_list_contentobject_id, $user_status, $subscription_status, $email, $limit, $offset ) {
        $conds = array();
        if( $mailing_list_contentobject_id !== FALSE ) {
            $conds['subscription']['mailing_list_contentobject_id'] = (int) $mailing_list_contentobject_id;
        }
        if( is_string( $user_status ) ) {
            $user_status = self::getUserStatus( $user_status );
            $conds['status'] = is_array( $user_status ) ? array( $user_status ) : (int) $user_status;
        }
        if( is_string( $subscription_status ) ) {
            $subscription_status = self::getSubscriptionStatus( $subscription_status );
            $conds['subscription']['status'] = is_array( $subscription_status ) ? array( $subscription_status ) : (int) $subscription_status;
        }
        if( !empty( $email ) ) {
            $conds['email'] = array( 'like', "%$email%" );
        }
        return array( 
            'result' => OWNewsletterUser::fetchListWithSubscription( $conds, $limit, $offset ) 
        );
    }

    /**
     * Count subscriptions with custom parameter
     * 
     * @param integer $mailing_list_contentobject_id
     * @param string $user_status
     * @param string $subscription_status
     * @return integer
     */
    static function countUsers( $mailing_list_contentobject_id, $user_status, $subscription_status, $email ) {
        $conds = array();
        if( $mailing_list_contentobject_id !== FALSE ) {
            $conds['subscription']['mailing_list_contentobject_id'] = (int) $mailing_list_contentobject_id;
        }
        if( is_string( $user_status ) ) {
            $user_status = self::getUserStatus( $user_status );
            $conds['status'] = is_array( $user_status ) ? array( $user_status ) : (int) $user_status;
        }
        if( is_string( $subscription_status ) ) {
            $subscription_status = self::getSubscriptionStatus( $subscription_status );
            $conds['subscription']['status'] = is_array( $subscription_status ) ? array( $subscription_status ) : (int) $subscription_status;
        }
        if( !empty( $email ) ) {
            $conds['email'] = array( 'like', "%$email%" );
        }
        return array( 
            'result' => OWNewsletterUser::countListWithSubscription( $conds ) 
        );
    }

    static function fetchUserAdditionalFields() {
        $object = new OWNewsletterUser();
        return array( 
            'result' => $object->attribute( 'additional_fields' ) 
        );
    }

    /**
     * Fetch subscriptions with custom parameter
     * 
     * @param integer $mailing_list_contentobject_id
     * @param string $subscription_status
     * @param integer $limit
     * @param integer $offset
     * @return array of OWNewsletterSubscription
     */
    static function fetchSubscriptions( $mailing_list_contentobject_id, $filter_status, $user_status, $limit, $offset ) {
        $conds = array();
        if( $mailing_list_contentobject_id !== FALSE ) {
            $conds['mailing_list_contentobject_id'] = (int) $mailing_list_contentobject_id;
        }
        
        if( $user_status !== false ) {
            $conds['user']['status'] = self::getUserStatus( $user_status );
        }
        
        if( is_string( $filter_status ) ) {
            switch( $filter_status ) {
                case 'pending':
                    $conds['status'] = OWNewsletterSubscription::STATUS_PENDING;
                    break;
                case 'approved':
                    $conds['status'] = OWNewsletterSubscription::STATUS_APPROVED;
                    break;
                case 'inactived':
                    $conds['status'] = OWNewsletterSubscription::STATUS_INACTIVED;
                    break;
            }
        }
        return array( 
            'result' => OWNewsletterSubscription::fetchListWithUser( $conds, $limit, $offset ) 
        );
    }

    /**
     * Count subscriptions with custom parameter
     * 
     * @param integer $mailing_list_contentobject_id
     * @param string $status
     * @return integer
     */
    static function countSubscriptions( $mailing_list_contentobject_id, $filter_status, $user_status ) {
        $conds = array();
        if( $mailing_list_contentobject_id !== FALSE ) {
            $conds['subscription']['mailing_list_contentobject_id'] = (int) $mailing_list_contentobject_id;
        }
        if( $user_status !== false ) {
            $conds['ownl_user.status'] = self::getUserStatus( $user_status );
        }
        if( is_string( $filter_status ) ) {

            switch( $filter_status ) {
                case 'pending':
                    $conds['subscription']['status'] = OWNewsletterSubscription::STATUS_PENDING;
                    break;
                case 'approved':
                    $conds['subscription']['status'] = OWNewsletterSubscription::STATUS_APPROVED;
                    break;
                case 'inactived':
                    $conds['subscription']['status'] = OWNewsletterSubscription::STATUS_INACTIVED;
                    break;
            }
        }

        $result = OWNewsletterUser::countListWithSubscription( $conds );
        return array( 
            'result' => $result 
        );
    }

    /**
     * Transform subscription status string in system status value
     * @param string $status
     * @return integer
     */
    static function getUserStatus( $status ) {
        switch( $status ) {
            case 'pending':
                return OWNewsletterUser::STATUS_PENDING;
            case 'confirmed':
                return OWNewsletterUser::STATUS_CONFIRMED;
            case 'bounced':
                return array(
                    OWNewsletterUser::STATUS_BOUNCED_SOFT,
                    OWNewsletterUser::STATUS_BOUNCED_HARD );
            case 'bounced_soft':
                return OWNewsletterUser::STATUS_BOUNCED_SOFT;
            case 'bounced_hard':
                return OWNewsletterUser::STATUS_BOUNCED_HARD;
            case 'removed':
                return array(
                    OWNewsletterUser::STATUS_REMOVED_SELF,
                    OWNewsletterUser::STATUS_REMOVED_ADMIN );
            case 'removed_self':
                return OWNewsletterUser::STATUS_REMOVED_SELF;
            case 'removed_admin':
                return OWNewsletterUser::STATUS_REMOVED_ADMIN;
            case 'blacklisted':
                return OWNewsletterUser::STATUS_BLACKLISTED;
            default:
                return false;
        }
    }

    /**
     * Transform subscription status string in system status value
     * @param string $status
     * @return integer
     */
    static function getSubscriptionStatus( $status ) {
        switch( $status ) {
            case 'pending':
                return OWNewsletterSubscription::STATUS_PENDING;
            case 'approved':
                return OWNewsletterSubscription::STATUS_APPROVED;
            case 'inactived':
                return OWNewsletterSubscription::STATUS_INACTIVED;
            default:
                return false;
        }
    }

    /**
     * Fetch the list of all available subscription status
     */
    static function fetchAvailableSubscriptionStatus() {
        return array( 
            'result' => OWNewsletterSubscription::getAvailableStatus() 
        );
    }

    /**
     * Fetch the list of all available filter status
     */
    static function fetchAvailableFilterStatus() {

        return array( 'result' => array(
                'pending' => ezpI18n::tr( 'newsletter/subscription/status', 'Pending' ),
                'confirmed' => ezpI18n::tr( 'newsletter/user/status', 'Confirmed' ),
                'approved' => ezpI18n::tr( 'newsletter/subscription/status', 'Approved' ),
                'inactived' => ezpI18n::tr( 'newsletter/subscription/status', 'Inactived' ),
                'removed' => ezpI18n::tr( 'newsletter/user/status', 'Removed' ),
                'bounced' => ezpI18n::tr( 'newsletter/user/status', 'Bounced' ),
                'blacklisted' => ezpI18n::tr( 'newsletter/user/status', 'Blacklisted' ),
            ) );
    }

    static function filterStatusName( $user_status, $subscription_status ) {
        $filterStatusName = "";
        $userStatusArray = OWNewsletterUser::getAvailableStatus( 'name' );
        $subscriptionStatusArray = OWNewsletterSubscription::getAvailableStatus( 'name' );
        switch( $user_status ) {
            case OWNewsletterUser::STATUS_PENDING:
                $filterStatusName = $userStatusArray[OWNewsletterUser::STATUS_PENDING];
                break;
            case OWNewsletterUser::STATUS_CONFIRMED:
                switch( $subscription_status ) {
                    case OWNewsletterSubscription::STATUS_PENDING:
                        $filterStatusName = $subscriptionStatusArray[OWNewsletterSubscription::STATUS_PENDING];
                        break;
                    case OWNewsletterSubscription::STATUS_APPROVED:
                        $filterStatusName = $subscriptionStatusArray[OWNewsletterSubscription::STATUS_APPROVED];
                        break;
                    case OWNewsletterSubscription::STATUS_INACTIVED:
                        $filterStatusName = $subscriptionStatusArray[OWNewsletterSubscription::STATUS_INACTIVED];
                        break;
                }
                break;
            case OWNewsletterUser::STATUS_BOUNCED_SOFT:
                $filterStatusName = $userStatusArray[OWNewsletterUser::STATUS_BOUNCED_SOFT];
                break;
            case OWNewsletterUser::STATUS_BOUNCED_HARD:
                $filterStatusName = $userStatusArray[OWNewsletterUser::STATUS_BOUNCED_HARD];
                break;
            case OWNewsletterUser::STATUS_REMOVED_SELF:
                $filterStatusName = $userStatusArray[OWNewsletterUser::STATUS_REMOVED_SELF];
                break;
            case OWNewsletterUser::STATUS_REMOVED_ADMIN:
                $filterStatusName = $userStatusArray[OWNewsletterUser::STATUS_REMOVED_ADMIN];
                break;
            case OWNewsletterUser::STATUS_BLACKLISTED:
                $filterStatusName = $userStatusArray[OWNewsletterUser::STATUS_BLACKLISTED];
                break;
        }
        return $filterStatusName;
    }

}

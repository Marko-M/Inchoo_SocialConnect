<?php
/**
 * Inchoo is not affiliated with or in any way responsible for this code.
 *
 * Commercial support is available directly from the [extension author](http://www.techytalk.info/contact/).
 *
 * @category Marko-M
 * @package SocialConnect
 * @author Marko Martinović <marko@techytalk.info>
 * @copyright Copyright (c) Marko Martinović (http://www.techytalk.info)
 * @license http://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 */

class Inchoo_SocialConnect_AccountController extends Mage_Core_Controller_Front_Action
{

    public function preDispatch()
    {
        parent::preDispatch();

        if (!$this->getRequest()->isDispatched()) {
            return $this;
        }

        /*
         * Avoid situations where before_auth_url redirects when doing connect
         * and disconnect from account dashboard. Authenticate.
         */
        if (!Mage::getSingleton('customer/session')
                ->unsBeforeAuthUrl()
                ->unsAfterAuthUrl()
                ->authenticate($this)) {
            $this->setFlag('', 'no-dispatch', true);
        }

    }

    public function googleAction()
    {
        $userInfo = Mage::getSingleton('inchoo_socialconnect/google_info_user')
                ->load();

        Mage::register('inchoo_socialconnect_google_userinfo', $userInfo);

        $this->loadLayout();
        $this->renderLayout();
    }

    public function facebookAction()
    {
        $userInfo = Mage::getSingleton('inchoo_socialconnect/facebook_info_user')
            ->load();

        Mage::register('inchoo_socialconnect_facebook_userinfo', $userInfo);

        $this->loadLayout();
        $this->renderLayout();
    }

    public function twitterAction()
    {
        // Cache user info inside customer session due to Twitter window frame rate limits
        if(!($userInfo = Mage::getSingleton('customer/session')
                ->getInchooSocialconnectTwitterUserinfo()) || !$userInfo->hasData()) {
            
            $userInfo = Mage::getSingleton('inchoo_socialconnect/twitter_info_user')
                ->load();

            Mage::getSingleton('customer/session')
                ->setInchooSocialconnectTwitterUserinfo($userInfo);
        }

        Mage::register('inchoo_socialconnect_twitter_userinfo', $userInfo);

        $this->loadLayout();
        $this->renderLayout();
    }

    public function linkedinAction()
    {
        $userInfo = Mage::getSingleton('inchoo_socialconnect/linkedin_info_user')
            ->load();

        Mage::register('inchoo_socialconnect_linkedin_userinfo', $userInfo);

        $this->loadLayout();
        $this->renderLayout();
    }

}

<?php
/**
 * Inchoo is not affiliated with or in any way responsible for this code.
 *
 * Commercial support is available directly from the [extension author](http://www.techytalk.info/contact/).
 *
 * @category  Marko-M
 * @package   SocialConnect
 * @author    Marko Martinović <marko@techytalk.info>
 * @copyright Copyright (c) Marko Martinović (http://www.techytalk.info)
 * @license   http://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 */

class Inchoo_SocialConnect_Block_Facebook_Button extends Mage_Core_Block_Template
{
    /**
     * @var string
     */
    protected static $csrf;

    /**
     *
     * @var Inchoo_SocialConnect_Model_Facebook_Oauth2_Client
     */
    protected $client = null;

    /**
     *
     * @var Inchoo_SocialConnect_Model_Facebook_Info_User
     */
    protected $userInfo = null;

    protected function _construct() {
        parent::_construct();

        $this->client = Mage::getSingleton('inchoo_socialconnect/facebook_oauth2_client');
        if(!($this->client->isEnabled())) {
            return;
        }

        $this->userInfo = Mage::registry('inchoo_socialconnect_facebook_userinfo');

        Mage::getSingleton('customer/session')
            ->setSocialConnectRedirect(Mage::helper('core/url')->getCurrentUrl());

        $this->setTemplate('inchoo/socialconnect/facebook/button.phtml');
    }

    protected function _beforeToHtml()
    {
        if (!static::$csrf) {
            static::$csrf = Mage::getSingleton('core/session')->getFacebookCsrf();
        }
        // CSRF protection
        Mage::getSingleton('core/session')->setFacebookCsrf(static::$csrf);
        $this->client->setState(static::$csrf);

        return parent::_beforeToHtml();
    }

    protected function _getButtonUrl()
    {
        if(is_null($this->userInfo) || !$this->userInfo->hasData()) {
            return $this->client->createAuthUrl();
        } else {
            return $this->getUrl('socialconnect/facebook/disconnect');
        }
    }

    protected function _getButtonText()
    {
        if(is_null($this->userInfo) || !$this->userInfo->hasData()) {
            if(!($text = Mage::registry('inchoo_socialconnect_button_text'))){
                $text = $this->__('Connect');
            }
        } else {
            $text = $this->__('Disconnect');
        }

        return $text;
    }

}

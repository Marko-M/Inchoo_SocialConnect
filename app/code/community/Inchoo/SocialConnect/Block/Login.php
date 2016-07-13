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

class Inchoo_SocialConnect_Block_Login extends Mage_Core_Block_Template
{
    protected $clientGoogle = null;
    protected $clientFacebook = null;
    protected $clientTwitter = null;
    protected $clientLinkedin = null;

    protected $numEnabled = 0;
    protected $numDescShown = 0;
    protected $numButtShown = 0;

    protected function _construct() {
        parent::_construct();

        $this->clientGoogle = Mage::getSingleton('inchoo_socialconnect/google_oauth2_client');
        $this->clientFacebook = Mage::getSingleton('inchoo_socialconnect/facebook_oauth2_client');
        $this->clientTwitter = Mage::getSingleton('inchoo_socialconnect/twitter_oauth_client');
        $this->clientLinkedin = Mage::getSingleton('inchoo_socialconnect/linkedin_oauth2_client');

        if( !$this->_googleEnabled() &&
            !$this->_facebookEnabled() &&
            !$this->_twitterEnabled() &&
            !$this->_linkedinEnabled()) {
            return;
        }

        if($this->_googleEnabled()) {
            $this->numEnabled++;
        }

        if($this->_facebookEnabled()) {
            $this->numEnabled++;
        }

        if($this->_twitterEnabled()) {
            $this->numEnabled++;
        }

        if($this->_linkedinEnabled()) {
            $this->numEnabled++;
        }

        Mage::register('inchoo_socialconnect_button_text', $this->__('Login'), true);

        $this->setTemplate('inchoo/socialconnect/login.phtml');
    }

    protected function _getColSet()
    {
        return 'col'.$this->numEnabled.'-set';
    }

    protected function _getDescCol()
    {
        return 'col-'.++$this->numDescShown;
    }

    protected function _getButtCol()
    {
        return 'col-'.++$this->numButtShown;
    }

    protected function _googleEnabled()
    {
        return $this->clientGoogle->isEnabled();
    }

    protected function _facebookEnabled()
    {
        return $this->clientFacebook->isEnabled();
    }

    protected function _twitterEnabled()
    {
        return $this->clientTwitter->isEnabled();
    }
    
    protected function _linkedinEnabled()
    {
        return $this->clientLinkedin->isEnabled();
    }    

}

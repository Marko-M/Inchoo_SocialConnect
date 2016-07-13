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

class Inchoo_SocialConnect_Block_Linkedin_Account extends Mage_Core_Block_Template
{
    /**
     *
     * @var Inchoo_SocialConnect_Model_Linkedin_Oauth2_Client
     */
    protected $client = null;
    
    /**
     *
     * @var Inchoo_SocialConnect_Model_Linkedin_Info_User
     */
    protected $userInfo = null;

    protected function _construct() {
        parent::_construct();

        $this->client = Mage::getSingleton('inchoo_socialconnect/linkedin_oauth2_client');
        if(!($this->client->isEnabled())) {
            return;
        }

        $this->userInfo = Mage::registry('inchoo_socialconnect_linkedin_userinfo');

        $this->setTemplate('inchoo/socialconnect/linkedin/account.phtml');
    }

    protected function _hasData()
    {
        return $this->userInfo->hasData();
    }

    protected function _getLinkedinId()
    {
        return $this->userInfo->getId();
    }

    protected function _getStatus()
    {
        $siteStandardProfileRequest = $this->userInfo->getSiteStandardProfileRequest();
        if($siteStandardProfileRequest && !empty($siteStandardProfileRequest->url)) {
            $link = '<a href="'.$siteStandardProfileRequest->url.'" target="_blank">'.
                    $this->escapeHtml($this->_getName()).'</a>';
        } else {
            $link = $this->_getName();
        }

        return $link;
    }

    protected function _getPublicProfileUrl()
    {
        if($this->userInfo->getPublicProfileUrl()) {
            $link = '<a href="'.$this->userInfo->getPublicProfileUrl().'" target="_blank">'.
                    $this->escapeHtml($this->userInfo->getPublicProfileUrl()).'</a>';

            return $link;
        }

        return null;
    }

    protected function _getEmail()
    {
        return $this->userInfo->getEmailAddress();
    }

    protected function _getPicture()
    {
        if($this->userInfo->getPictureUrl()) {
            return Mage::helper('inchoo_socialconnect/linkedin')
                    ->getProperDimensionsPictureUrl($this->userInfo->getId(),
                            $this->userInfo->getPictureUrl());
        }

        return null;
    }

    protected function _getName()
    {
        return sprintf(
            '%s %s',
            $this->userInfo->getFirstName(),
            $this->userInfo->getLastName()
        );
    }

}
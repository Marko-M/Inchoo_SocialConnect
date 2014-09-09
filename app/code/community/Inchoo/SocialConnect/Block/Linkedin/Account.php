<?php
/**
* Inchoo
*
* NOTICE OF LICENSE
*
* This source file is subject to the Open Software License (OSL 3.0)
* that is bundled with this package in the file LICENSE.txt.
* It is also available through the world-wide-web at this URL:
* http://opensource.org/licenses/osl-3.0.php
* If you did not receive a copy of the license and are unable to
* obtain it through the world-wide-web, please send an email
* to license@magentocommerce.com so we can send you a copy immediately.
*
* DISCLAIMER
*
* Please do not edit or add to this file if you wish to upgrade
* Magento or this extension to newer versions in the future.
** Inchoo *give their best to conform to
* "non-obtrusive, best Magento practices" style of coding.
* However,* Inchoo *guarantee functional accuracy of
* specific extension behavior. Additionally we take no responsibility
* for any possible issue(s) resulting from extension usage.
* We reserve the full right not to provide any kind of support for our free extensions.
* Thank you for your understanding.
*
* @category Inchoo
* @package SocialConnect
* @author Marko Martinović <marko.martinovic@inchoo.net>
* @copyright Copyright (c) Inchoo (http://inchoo.net/)
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
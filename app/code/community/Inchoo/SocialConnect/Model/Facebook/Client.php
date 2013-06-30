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

require_once(Mage::getBaseDir('lib') . '/FacebookApiPhpClient/Facebook_Client.php');

class Inchoo_SocialConnect_Model_Facebook_Client
{
    const REDIRECT_URI_ROUTE = 'socialconnect/facebook/connect';

    const XML_PATH_ENABLED = 'customer/inchoo_socialconnect_facebook/enabled';
    const XML_PATH_CLIENT_ID = 'customer/inchoo_socialconnect_facebook/client_id';
    const XML_PATH_CLIENT_SECRET = 'customer/inchoo_socialconnect_facebook/client_secret';

    protected $client = null;
    
    public function __construct() {
        $enabled = $this->_isEnabled();
        $clientId = $this->_getClientId();
        $clientSecret = $this->_getClientSecret();

        if(!empty($enabled)) {
            $this->client = new Facebook_Client(array(
                'client_id' => $clientId,
                'client_secret' => $clientSecret,
                'redirect_uri' => Mage::getModel('core/url')->sessionUrlVar(
                    Mage::getUrl(self::REDIRECT_URI_ROUTE)
                )
            ));
        }
    }

    public function getClient()
    {
        return $this->client;
    }

    protected function _isEnabled()
    {
        return $this->_getStoreConfig(self::XML_PATH_ENABLED);
    }

    protected function _getClientId()
    {
        return $this->_getStoreConfig(self::XML_PATH_CLIENT_ID);
    }

    protected function _getClientSecret()
    {
        return $this->_getStoreConfig(self::XML_PATH_CLIENT_SECRET);
    }

    protected function _getStoreConfig($xmlPath)
    {
        return Mage::getStoreConfig($xmlPath, Mage::app()->getStore()->getId());
    }

}
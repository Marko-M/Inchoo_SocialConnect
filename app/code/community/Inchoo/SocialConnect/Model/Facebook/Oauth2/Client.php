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

class Inchoo_SocialConnect_Model_Facebook_Oauth2_Client
{
    const REDIRECT_URI_ROUTE = 'socialconnect/facebook/connect';

    const XML_PATH_ENABLED = 'customer/inchoo_socialconnect_facebook/enabled';
    const XML_PATH_CLIENT_ID = 'customer/inchoo_socialconnect_facebook/client_id';
    const XML_PATH_CLIENT_SECRET = 'customer/inchoo_socialconnect_facebook/client_secret';

    const OAUTH2_SERVICE_URI = 'https://graph.facebook.com';
    const OAUTH2_AUTH_URI = 'https://graph.facebook.com/oauth/authorize';
    const OAUTH2_TOKEN_URI = 'https://graph.facebook.com/oauth/access_token';

    protected $clientId = null;
    protected $clientSecret = null;
    protected $redirectUri = null;
    protected $state = '';
    protected $scope = array('public_profile', 'email', 'user_birthday');

    protected $token = null;

    public function __construct($params = array())
    {
        if(($this->isEnabled = $this->_isEnabled())) {
            $this->clientId = $this->_getClientId();
            $this->clientSecret = $this->_getClientSecret();
            $this->redirectUri = Mage::getModel('core/url')->sessionUrlVar(
                Mage::getUrl(self::REDIRECT_URI_ROUTE)
            );

            if(!empty($params['scope'])) {
                $this->scope = $params['scope'];
            }

            if(!empty($params['state'])) {
                $this->state = $params['state'];
            }
        }
    }

    public function isEnabled()
    {
        return (bool) $this->isEnabled;
    }

    public function getClientId()
    {
        return $this->clientId;
    }

    public function getClientSecret()
    {
        return $this->clientSecret;
    }

    public function getRedirectUri()
    {
        return $this->redirectUri;
    }

    public function getScope()
    {
        return $this->scope;
    }

    public function getState()
    {
        return $this->state;
    }

    public function setState($state)
    {
        $this->state = $state;
    }

    public function setAccessToken($token)
    {
        $this->token = $token;

        $this->extendAccessToken();
    }

    public function getAccessToken($code = null)
    {
        if(!empty($code)) {
            return $this->fetchAccessToken($code);
        } else if(!empty($this->token)) {
            return $this->token;
        } else {
            throw new Exception(
                Mage::helper('inchoo_socialconnect')
                    ->__('Unable to proceed without an access token.')
            );
        }
    }

    public function createAuthUrl()
    {
        $url =
            self::OAUTH2_AUTH_URI.'?'.
            http_build_query(
                array(
                    'client_id' => $this->clientId,
                    'redirect_uri' => $this->redirectUri,
                    'state' => $this->state,
                    'scope' => implode(',', $this->scope)
                )
            );
        return $url;
    }

    public function api($endpoint, $method = 'GET', $params = array())
    {
        if(empty($this->token)) {
            throw new Exception(
                Mage::helper('inchoo_socialconnect')
                    ->__('Unable to proceed without an access token.')
            );
        }

        $url = self::OAUTH2_SERVICE_URI.$endpoint;

        $method = strtoupper($method);

        $params = array_merge(array(
            'access_token' => $this->token->access_token
        ), $params);

        $response = $this->_httpRequest($url, $method, $params);

        return $response;
    }

    protected function fetchAccessToken($code)
    {
        $response = $this->_httpRequest(
            self::OAUTH2_TOKEN_URI,
            'POST',
            array(
                'code' => $code,
                'redirect_uri' => $this->redirectUri,
                'client_id' => $this->clientId,
                'client_secret' => $this->clientSecret,
                'grant_type' => 'authorization_code'
            )
        );

        $this->token = $response;

        $this->extendAccessToken();

        return $this->token;
    }

    public function extendAccessToken()
    {
        if(empty($this->token)) {
            throw new Exception(
                Mage::helper('inchoo_socialconnect')
                    ->__('No token set, nothing to extend.')
            );
        }

        // Expires not set or expires over two hours means long lived token
        if(!property_exists($this->token, 'expires') || $this->token->expires > 7200) {
            // Long lived token, no need to extend
            return;
        }

        $response = $this->_httpRequest(
            self::OAUTH2_TOKEN_URI,
            'GET',
            array(
                'client_id' => $this->clientId,
                'client_secret' => $this->clientSecret,
                'fb_exchange_token' => $this->token->access_token,
                'grant_type' => 'fb_exchange_token'
            )
        );

        return $this->token = $response;
    }

    protected function _httpRequest($url, $method = 'GET', $params = array())
    {
        $client = new Zend_Http_Client($url, array('timeout' => 60));

        switch ($method) {
            case 'GET':
                $client->setParameterGet($params);
                break;
            case 'POST':
                $client->setParameterPost($params);
                break;
            case 'DELETE':
                $client->setParameterGet($params);
                break;
            default:
                throw new Exception(
                    Mage::helper('inchoo_socialconnect')
                        ->__('Required HTTP method is not supported.')
                );
        }

        $response = $client->request($method);

        Inchoo_SocialConnect_Helper_Data::log($response->getStatus().' - '. $response->getBody());

        $decodedResponse = json_decode($response->getBody());

        /*
         * Per http://tools.ietf.org/html/draft-ietf-oauth-v2-27#section-5.1
         * Facebook should return data using the "application/json" media type.
         * Facebook violates OAuth2 specification and returns string. If this
         * ever gets fixed, following condition will not be used anymore.
         */
        if(empty($decodedResponse)) {
            $parsed_response = array();
            parse_str($response->getBody(), $parsed_response);

            $decodedResponse = json_decode(json_encode($parsed_response));
        }

        if($response->isError()) {
            $status = $response->getStatus();
            if(($status == 400 || $status == 401)) {
                if(isset($decodedResponse->error->message)) {
                    $message = $decodedResponse->error->message;
                } else {
                    $message = Mage::helper('inchoo_socialconnect')
                        ->__('Unspecified OAuth error occurred.');
                }

                throw new Inchoo_SocialConnect_Model_Facebook_Oauth2_Exception($message);
            } else {
                $message = sprintf(
                    Mage::helper('inchoo_socialconnect')
                        ->__('HTTP error %d occurred while issuing request.'),
                    $status
                );

                throw new Exception($message);
            }
        }

        return $decodedResponse;
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
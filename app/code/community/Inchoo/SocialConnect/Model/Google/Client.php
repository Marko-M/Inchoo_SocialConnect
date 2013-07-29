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

class Inchoo_SocialConnect_Model_Google_Client
{
    const REDIRECT_URI_ROUTE = 'socialconnect/google/connect';

    const XML_PATH_ENABLED = 'customer/inchoo_socialconnect_google/enabled';
    const XML_PATH_CLIENT_ID = 'customer/inchoo_socialconnect_google/client_id';
    const XML_PATH_CLIENT_SECRET = 'customer/inchoo_socialconnect_google/client_secret';

    const OAUTH2_REVOKE_URI = 'https://accounts.google.com/o/oauth2/revoke';
    const OAUTH2_TOKEN_URI = 'https://accounts.google.com/o/oauth2/token';
    const OAUTH2_AUTH_URI = 'https://accounts.google.com/o/oauth2/auth';
    const OAUTH2_SERVICE_URI = 'https://www.googleapis.com/oauth2/v2';

    protected $isEnabled = null;
    protected $clientId = null;
    protected $clientSecret = null;
    protected $redirectUri = null;
    protected $state = '';
    protected $scope = array(
        'https://www.googleapis.com/auth/userinfo.profile',
        'https://www.googleapis.com/auth/userinfo.email',
    );
    protected $access = 'offline';
    protected $prompt = 'auto';

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

            if(!empty($params['access'])) {
                $this->access = $params['access'];
            }

            if(!empty($params['prompt'])) {
                $this->prompt = $params['prompt'];
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

    public function getAccess()
    {
        return $this->access;
    }

    public function setAccess($access)
    {
        $this->access = $access;
    }

    public function getPrompt()
    {
        return $this->prompt;
    }

    public function setPrompt($prompt)
    {
        $this->access = $prompt;
    }

    public function setAccessToken($token)
    {
        $this->token = json_decode($token);
    }

    public function getAccessToken()
    {
        if(empty($this->token)) {
            $this->fetchAccessToken();
        } else if($this->isAccessTokenExpired()) {
            $this->refreshAccessToken();
        }

        return json_encode($this->token);
    }

    public function createAuthUrl()
    {
        $url =
        self::OAUTH2_AUTH_URI.'?'.
            http_build_query(
                array(
                    'response_type' => 'code',
                    'redirect_uri' => $this->redirectUri,
                    'client_id' => $this->clientId,
                    'scope' => implode(' ', $this->scope),
                    'state' => $this->state,
                    'access_type' => $this->access,
                    'approval_prompt' => $this->prompt
                    )
            );
        return $url;
    }

    public function api($endpoint, $method = 'GET', $params = array())
    {
        if(empty($this->token)) {
            $this->fetchAccessToken();
        } else if($this->isAccessTokenExpired()) {
            $this->refreshAccessToken();
        }

        $url = self::OAUTH2_SERVICE_URI.$endpoint;

        $method = strtoupper($method);

        $params = array_merge(array(
            'access_token' => $this->token->access_token
        ), $params);

        $response = $this->_httpRequest($url, $method, $params);

        return $response;
    }

    public function revokeToken()
    {
        if(empty($this->token)) {
            throw new Exception(
                Mage::helper('inchoo_socialconnect')
                    ->__('No access token available.')
            );
        }

        if(empty($this->token->refresh_token)) {
            throw new Exception(
                Mage::helper('inchoo_socialconnect')
                    ->__('No refresh token, nothing to revoke.')
            );
        }

        $this->_httpRequest(
            self::OAUTH2_REVOKE_URI,
            'POST',
           array(
               'token' => $this->token->refresh_token
           )
        );
    }

    protected function fetchAccessToken()
    {
        if(empty($_REQUEST['code'])) {
            throw new Exception(
                Mage::helper('inchoo_socialconnect')
                    ->__('Unable to retrieve access code.')
            );
        }

        $response = $this->_httpRequest(
            self::OAUTH2_TOKEN_URI,
            'POST',
            array(
                'code' => $_REQUEST['code'],
                'redirect_uri' => $this->redirectUri,
                'client_id' => $this->clientId,
                'client_secret' => $this->clientSecret,
                'grant_type' => 'authorization_code'
            )
        ); 

        $response->created = time();

        $this->token = $response;
    }

    protected function refreshAccessToken()
    {
        if(empty($this->token->refresh_token)) {
            throw new Exception(
                Mage::helper('inchoo_socialconnect')
                    ->__('No refresh token, unable to refresh access token.')
            );
        }

        $response = $this->_httpRequest(
            self::OAUTH2_TOKEN_URI,
            'POST',
            array(
                'client_id' => $this->clientId,
                'client_secret' => $this->clientSecret,
                'refresh_token' => $this->token->refresh_token,
                'grant_type' => 'refresh_token'
            )
        );

        $this->token->access_token = $response->access_token;
        $this->token->expires_in = $response->expires_in;
        $this->token->created = time();
    }

    protected function isAccessTokenExpired() {
        // If the token is set to expire in the next 30 seconds.
        $expired = ($this->token->created + ($this->token->expires_in - 30)) < time();

        return $expired;
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
                break;
            default:
                throw new Exception(
                    Mage::helper('inchoo_socialconnect')
                        ->__('Required HTTP method is not supported.')
                );
        }

        $response = $client->request($method);

        Mage::log($response->getStatus().' - '. $response->getBody());

        $decoded_response = json_decode($response->getBody());

        if($response->isError()) {
            $status = $response->getStatus();
            if(($status == 400 || $status == 401)) {
                if(isset($decoded_response->error->message)) {
                    $message = $decoded_response->error->message;
                } else {
                    $message = Mage::helper('inchoo_socialconnect')
                        ->__('Unspecified OAuth error occurred.');
                }

                throw new Inchoo_SocialConnect_GoogleOAuthException($message);
            } else {
                $message = sprintf(
                    Mage::helper('inchoo_socialconnect')
                        ->__('HTTP error %d occurred while issuing request.'),
                    $status
                );

                throw new Exception($message);
            }
        }

        return $decoded_response;
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

class Inchoo_SocialConnect_GoogleOAuthException extends Exception
{}

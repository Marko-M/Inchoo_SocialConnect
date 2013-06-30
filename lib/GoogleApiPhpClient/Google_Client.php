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

if (! function_exists('curl_init')) {
    throw new Exception('Google PHP API Client requires the CURL PHP extension');
}

if (! function_exists('json_decode')) {
  throw new Exception('Google PHP API Client requires the JSON PHP extension');
}

if (! function_exists('http_build_query')) {
    throw new Exception('Google PHP API Client requires http_build_query()');
}

class Google_Client
{

    const OAUTH2_REVOKE_URI = 'https://accounts.google.com/o/oauth2/revoke';
    const OAUTH2_TOKEN_URI = 'https://accounts.google.com/o/oauth2/token';
    const OAUTH2_AUTH_URI = 'https://accounts.google.com/o/oauth2/auth';
    const OAUTH2_SERVICE_URI = 'https://www.googleapis.com/oauth2/v2';

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
        if(
            empty($params['client_id']) ||
            empty($params['client_secret']) ||
            empty($params['redirect_uri'])) {
            throw new Exception(
                'client_id, client_secret, redirect_uri and scope are required'
            );
        }

        $this->clientId = $params['client_id'];
        $this->clientSecret = $params['client_secret'];
        $this->redirectUri = $params['redirect_uri'];

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

    public function setAccessToken($token)
    {
        $this->token = json_decode($token);
    }

    public function getAccessToken()
    {
        if(empty($this->token)) {
            $this->fetchAccessToken();
        }

        return json_encode($this->token);
    }

    public function api($endpoint, $method = 'get', $args = array(), $data = array())
    {
        if(empty($this->token)) {
            $this->fetchAccessToken();
        } else {
            $this->refreshAccessToken();
        }     
        
        $method = strtolower($method);
        
        $url =
        self::OAUTH2_SERVICE_URI.$endpoint.'?'.
        http_build_query(
            array_merge(array(
                'access_token' => $this->token->access_token
            ), $args)
        );
        
        switch ($method) {
            case 'get':
                $response = $this->curlGetRequest($url);
                break;
            case 'delete':
                $response = $this->curlDeleteRequest($url);
                break;
            case 'post':
                $response = $this->curlPostRequest($url, $data);
                break;
            default:
                throw new Exception('No HTTP method available');
        }
        
        $decoded_response = json_decode($response);

        if (isset($decoded_response->error)) {
            // Token expired, permissions revoked or password changed
            throw new Exception($decoded_response->error);
        }

        return $decoded_response;        
    }
    
    public function revokeToken()
    {
        if(empty($this->token)) {
            throw new Exception('No access token available');
        }

        if(empty($this->token->refresh_token)) {
            throw new Exception('No refresh token, nothing to revoke');
        }        

        $response = $this->curlPostRequest(
           self::OAUTH2_REVOKE_URI,
           array(
               'token' => $this->token->refresh_token
           )
        );
        
        $decoded_response = json_decode($response);
        
        if (isset($decoded_response->error)) {
            throw new GOAuthException($decoded_response->error);
        }
    }
    
    protected function fetchAccessToken()
    {
        if(empty($_REQUEST['code'])) {
            throw new Exception('Unable to retrieve access code');
        }

        $response = $this->curlPostRequest(
            self::OAUTH2_TOKEN_URI,
            array(
                'code' => $_REQUEST['code'],
                'redirect_uri' => $this->redirectUri,
                'client_id' => $this->clientId,
                'client_secret' => $this->clientSecret,
                'grant_type' => 'authorization_code'
            )
        );
        
        $decoded_response = json_decode($response);

        if (isset($decoded_response->error)) {
            throw new GOAuthException($decoded_response->error);
        }

        $this->token = $decoded_response;
    }
    
    protected function refreshAccessToken()
    {
        if(empty($this->token->refresh_token)) {
            throw new Exception('No refresh token, unable to refresh access token');
        }

        $response = $this->curlPostRequest(
            self::OAUTH2_TOKEN_URI,
            array(
                'client_id' => $this->clientId,
                'client_secret' => $this->clientSecret,
                'refresh_token' => $this->token->refresh_token,
                'grant_type' => 'refresh_token'        
            )
        );
        
        $decoded_response = json_decode($response);

        if(!isset($decoded_response->access_token) ||
                !isset($decoded_response->expires_in)) {
            throw new GOAuthException('Unable to refresh access token');
        }

        $this->token->access_token = $decoded_response->access_token;
        $this->token->expires_in = $decoded_response->expires_in;
    }    

    protected function curlGetRequest($url) {
        $c = curl_init();
        curl_setopt($c, CURLOPT_URL, $url);
        curl_setopt($c, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($c, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($c, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($c, CURLOPT_CONNECTTIMEOUT, 60);
        curl_setopt($c, CURLOPT_TIMEOUT, 120);
        $contents = curl_exec($c);

        curl_close($c);

        if ($contents)
            return $contents;
        else
            return false;
    }

    protected function curlPostRequest($url, $data = array()) {
        $c = curl_init();
        curl_setopt($c, CURLOPT_URL, $url);
        curl_setopt($c, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($c, CURLOPT_POST, true);
        curl_setopt($c, CURLOPT_POSTFIELDS, $data);
        curl_setopt($c, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($c, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($c, CURLOPT_CONNECTTIMEOUT, 60);
        curl_setopt($c, CURLOPT_TIMEOUT, 120);
        $contents = curl_exec($c);

        curl_close($c);

        if ($contents)
            return $contents;
        else
            return false;
    }

    protected function curlDeleteRequest($url) {
        $c = curl_init();
        curl_setopt($c, CURLOPT_URL, $url);
        curl_setopt($c, CURLOPT_CUSTOMREQUEST, 'DELETE');
        curl_setopt($c, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($c, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($c, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($c, CURLOPT_CONNECTTIMEOUT, 60);
        curl_setopt($c, CURLOPT_TIMEOUT, 120);
        $contents = curl_exec($c);

        curl_close($c);

        if ($contents)
            return $contents;
        else
            return false;
    }

}

class GOAuthException extends Exception
{}
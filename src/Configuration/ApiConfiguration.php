<?php
/**
 * Created by IntelliJ IDEA.
 * User: mduncan
 * Date: 9/29/15
 * Time: 12:16 PM
 */

namespace Fulfillment\Api\Configuration;

use FoxxMD\Utilities\ArrayUtil;
use \Fulfillment\Api\Contracts\ApiConfiguration as ConfigContract;
use Fulfillment\Api\Exceptions\MissingCredentialException;


class ApiConfiguration implements ConfigContract
{
    protected $username;
    protected $password;
    protected $clientId;
    protected $clientSecret;
    protected $scope;
    protected $accessToken;
    protected $endpoint;
    protected $authEndpoint;

    public function __construct($data = null)
    {
        $this->username     = ArrayUtil::get($data['username']);
        $this->password     = ArrayUtil::get($data['password']);
        $this->clientId     = ArrayUtil::get($data['clientId']);
        $this->clientSecret = ArrayUtil::get($data['clientSecret']);
        $this->accessToken  = ArrayUtil::get($data['accessToken']);
        $this->endpoint     = ArrayUtil::get($data['endpoint']);
        $this->scope        = ArrayUtil::get($data['scope']);
        $this->authEndpoint = ArrayUtil::get($data['authEndpoint']);

        if(is_null($this->authEndpoint) || !$this->authEndpoint){
            $this->authEndpoint = 'https://auth.fulfillment.com';
        }
    }

    public function getUsername()
    {
        return $this->username;
    }

    public function getPassword()
    {
        return $this->password;
    }

    public function getClientId()
    {
        return $this->clientId;
    }

    public function getClientSecret()
    {
        return $this->clientSecret;
    }

    public function getAccessToken()
    {
        return $this->accessToken;
    }

    public function setAccessToken($token)
    {
        $this->accessToken = $token;
    }

    public function getScope()
    {
        return $this->scope;
    }

    public function getEndpoint()
    {
        return $this->endpoint;
    }

    public function getAuthEndpoint()
    {
        return $this->authEndpoint;
    }
}
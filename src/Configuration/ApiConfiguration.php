<?php
/**
 * Created by IntelliJ IDEA.
 * User: mduncan
 * Date: 9/29/15
 * Time: 12:16 PM
 */

namespace Fulfillment\Api\Configuration;

use \Fulfillment\Api\Contracts\ApiConfiguration as ConfigContract;


class ApiConfiguration implements ConfigContract
{
    protected $username;
    protected $password;
    protected $clientId;
    protected $clientSecret;
    protected $accessToken;
    protected $endpoint;

    public function __construct($data = null){

    }

    public function getUsername()
    {
        return $this->username;
    }

    public function getPassword()
    {
        return $this->getPassword();
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

    public function setAccessToken($token){
        $this->accessToken = $token;
    }

    public function getEndpoint()
    {
        return $this->endpoint;
    }
}
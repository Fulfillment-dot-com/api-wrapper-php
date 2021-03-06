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


class ApiConfiguration implements ConfigContract {
	protected $username;
	protected $password;
	protected $clientId;
	protected $clientSecret;
	protected $scope;
	protected $accessToken;
	protected $endpoint;
	protected $authEndpoint;
	protected $storeToken;
	protected $loggerPrefix;
	protected $storageTokenPrefix;

	public function __construct($data = null)
	{
		$this->username           = ArrayUtil::get($data['username']);
		$this->password           = ArrayUtil::get($data['password']);
		$this->clientId           = ArrayUtil::get($data['clientId']);
		$this->clientSecret       = ArrayUtil::get($data['clientSecret']);
		$this->accessToken        = ArrayUtil::get($data['accessToken']);
		$this->endpoint           = ArrayUtil::get($data['endpoint']);
		$this->scope              = ArrayUtil::get($data['scope']);
		$this->authEndpoint       = ArrayUtil::get($data['authEndpoint'], 'https://auth.fulfillment.com');
		$this->storeToken         = ArrayUtil::get($data['storeToken'], true);
		$this->loggerPrefix       = ArrayUtil::get($data['loggerPrefix']);
		$this->storageTokenPrefix = ArrayUtil::get($data['storageTokenPrefix']);
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

	public function setShouldStoreToken($bool)
	{
		$this->storeToken = $bool;
	}

	public function shouldStoreToken()
	{
		return $this->storeToken;
	}

	public function setLoggerPrefix($prefix)
	{
		$this->loggerPrefix = $prefix;
	}

	public function getLoggerPrefix()
	{
		return (!is_null($this->loggerPrefix) ? ('[' . $this->loggerPrefix . '] ') : '');
	}

	public function setStorageTokenPrefix($prefix)
	{
		$this->storageTokenPrefix = $prefix;
	}

	public function getStorageTokenPrefix()
	{
		return $this->storageTokenPrefix;
	}

	public function getStorageTokenFilename()
	{
		return ((is_null($this->getStorageTokenPrefix()) ? '' : ($this->getStorageTokenPrefix() . '-')) . 'access_token.txt');
	}
}
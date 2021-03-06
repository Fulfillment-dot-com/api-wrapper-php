<?php

namespace Fulfillment\Api;


use FoxxMD\Utilities\ArrayUtil;
use Fulfillment\Api\Configuration\ApiConfiguration;
use Fulfillment\Api\Http\Request;
use Fulfillment\Api\Utilities\Helper;
use Fulfillment\Api\Utilities\RequestParser;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Psr7\Response;
use League\CLImate\CLImate;
use League\CLImate\Util\Writer\File;
use League\CLImate\Util\Writer\WriterInterface;
use Dotenv;

class Api {

	protected $config;
	protected $http;
	protected $lastResponse;

	/**
	 * @param $config array|string|\Fulfillment\Api\Contracts\ApiConfiguration|null
	 * @param $logger WriterInterface|null
	 * @param $guzzle Client|null
	 *
	 * @throws \Exception
	 */
	public function __construct($config = null, $logger = null, $guzzle = null)
	{
		//setup guzzle
		$this->guzzle = !is_null($guzzle) ? $guzzle : new Client();

		//setup climate
		$this->climate = new CLImate;

		if (!is_null($logger))
		{
			$this->climate->output->add('customLogger', $logger)->defaultTo('customLogger');
		}
		else
		{
			if (php_sapi_name() !== 'cli')
			{
				//if no custom logger and this isn't a CLI app then we need to write to a file
				$path = Helper::getStoragePath('logs/');
				$file = $path . 'Log--' . date("Y-m-d") . '.log';
				if (!file_exists($path) || !is_writable($path) || !$resource = fopen($file, 'a'))
				{
					$this->climate->output->defaultTo('buffer');
				}
				else
				{
					fclose($resource);
					$logFile = new File($file);
					$this->climate->output->add('file', $logFile)->defaultTo('file');
				}

				if (!getenv('NOANSI'))
				{
					//we want to logs to have ANSI encoding so we can tail the log remotely and get pretty colors
					$this->climate->forceAnsiOn();
				}
			}
		}

		//setup configuration
		if (is_string($config) || is_null($config))
		{
			if (!is_null($config))
			{
				if (!is_dir($config))
				{
					throw new \Exception('The provided directory location does not exist at ' . $config);
				}
				Dotenv::load($config);
			}
			$data         = [
				'username'           => getenv('USERNAME') ?: null,
				'password'           => getenv('PASSWORD') ?: null,
				'clientId'           => getenv('CLIENT_ID') ?: null,
				'clientSecret'       => getenv('CLIENT_SECRET') ?: null,
				'accessToken'        => getenv('ACCESS_TOKEN') ?: null,
				'endPoint'           => getenv('API_ENDPOINT') ?: null,
				'authEndpoint'       => getenv('AUTH_ENDPOINT') ?: null,
				'scope'              => getenv('SCOPE') ?: null,
				'storeToken'         => getenv('STORE_TOKEN') ?: null,
				'loggerPrefix'       => getenv('LOGGER_PREFIX') ?: null,
				'storageTokenPrefix' => getenv('STORAGE_TOKEN_PREFIX') ?: null,
			];
			$this->config = new ApiConfiguration($data);

		}
		else
		{
			if (is_array($config))
			{
				$data         = [
					'username'           => ArrayUtil::get($config['username']),
					'password'           => ArrayUtil::get($config['password']),
					'clientId'           => ArrayUtil::get($config['clientId']),
					'clientSecret'       => ArrayUtil::get($config['clientSecret']),
					'accessToken'        => ArrayUtil::get($config['accessToken']),
					'endpoint'           => ArrayUtil::get($config['endpoint']),
					'authEndpoint'       => ArrayUtil::get($config['authEndpoint']),
					'scope'              => ArrayUtil::get($config['scope']),
					'storeToken'         => ArrayUtil::get($config['storeToken']),
					'loggerPrefix'       => ArrayUtil::get($config['loggerPrefix']),
					'storageTokenPrefix' => ArrayUtil::get($config['storageTokenPrefix']),
				];
				$this->config = new ApiConfiguration($data);

			}
			else
			{
				if ($config instanceof \Fulfillment\Api\Contracts\ApiConfiguration)
				{
					$this->config = $config;
				}
			}
		}

		if ($this->config->shouldStoreToken() && is_null($this->config->getAccessToken()))
		{
			//try to get from file
			if (file_exists(Helper::getStoragePath($this->config->getStorageTokenFilename())))
			{
				$this->config->setAccessToken(file_get_contents(Helper::getStoragePath($this->config->getStorageTokenFilename())));
				$this->climate->info($this->config->getLoggerPrefix() . 'Got token ' . $this->config->getAccessToken() . ' from storage.');
			}
		}

		if (is_null($this->config->getAccessToken()) && (is_null($this->config->getClientId()) || is_null($this->config->getClientSecret()) || is_null($this->config->getUsername()) || is_null($this->config->getPassword())))
		{
			throw new \InvalidArgumentException($this->config->getLoggerPrefix() . 'No access token provided -- so client Id, client secret, username, and password must be provided');
		}
		if (is_null($this->config->getEndPoint()))
		{
			throw new \InvalidArgumentException($this->config->getLoggerPrefix() . 'Must provide an endpoint');
		}
		if (is_null($this->config->getScope()))
		{
			throw new \InvalidArgumentException($this->config->getLoggerPrefix() . 'Must provide scopes');
		}

		$this->http = new Request($this->guzzle, $this->config, $this->climate);
	}

	public function config()
	{
		return $this->config;
	}

	protected function tryRequest($method, $url, $payload = null, $queryString = null, $firstTry = true)
	{
		$this->lastResponse = null;
		try
		{
			$resultArr = $this->http->makeRequest($method, $url, $payload, $queryString);
			$this->lastResponse = $resultArr['response'];
			return $resultArr['result'];
		}
		catch (ConnectException $c)
		{
			$this->climate->error($this->config->getLoggerPrefix() . 'Error connecting to endpoint: ' . $c->getMessage());
			throw $c;
		}
		catch (RequestException $e)
		{
			if ($e->getResponse()->getStatusCode() == 401 || (isset(RequestParser::parseError($e)['error']) && RequestParser::parseError($e)['error'] == 'invalid_request') || (isset(RequestParser::parseError($e)['error_code']) && RequestParser::parseError($e)['error_code'] == '1100'))
			{ //check for oauth error, should try a refresh
				if ($firstTry)
				{
					$this->climate->info($this->config->getLoggerPrefix() . 'Possibly expired token, trying to refresh token...');
					$newToken = $this->http->requestAccessToken();
					if (!is_null($newToken))
					{
						$this->config->setAccessToken($newToken);
						$this->http = new Request($this->guzzle, $this->config, $this->climate);
						if ($this->config->shouldStoreToken())
						{
							file_put_contents(Helper::getStoragePath($this->config->getStorageTokenFilename()), $newToken);
						}
					}
					$this->climate->info($this->config->getLoggerPrefix() . 'Retrying request...');

					return $this->tryRequest($method, $url, $payload, $queryString, false);
				}
				else
				{
					//something else is wrong and requesting a new token isn't going to fix it
					throw new \Exception($this->config->getLoggerPrefix() . 'The request was unauthorized and could not be fixed by refreshing access token.', 401, $e);
				}
			}
			else
			{
				throw $e;
			}
		}
	}

	/**
	 * Get a new access token
	 *
	 * @return string|null
	 * @throws Exceptions\MissingCredentialException
	 * @throws Exceptions\UnauthorizedMerchantException
	 */
	public function refreshAccessToken()
	{
		$newToken = $this->http->requestAccessToken();
		if (!is_null($newToken))
		{
			$this->config->setAccessToken($newToken);
			$this->http = new Request($this->guzzle, $this->config, $this->climate);
			if ($this->config->shouldStoreToken())
			{
				file_put_contents(Helper::getStoragePath($this->config->getStorageTokenFilename()), $newToken);
			}
		}

		return $newToken;
	}

	/**
	 * Perform a GET request to the Api
	 *
	 * @param      $url string Relative URL from API base URL
	 * @param null $queryString
	 *
	 * @return mixed
	 */
	public function get($url, $queryString = null)
	{
		return $this->tryRequest('get', $url, null, $queryString);
	}

	/**
	 * Perform a POST request to the Api
	 *
	 * @param      $url     string Relative URL from API base URL
	 * @param      $payload array Request contents as json serializable array
	 * @param null $queryString
	 *
	 * @return mixed
	 */
	public function post($url, $payload, $queryString = null)
	{
		return $this->tryRequest('post', $url, $payload, $queryString);
	}

	/**
	 * Perform a PUT request to the Api
	 *
	 * @param      $url     string Relative URL from API base URL
	 * @param      $payload array Request contents as json serializable array
	 * @param null $queryString
	 *
	 * @return mixed
	 */
	public function put($url, $payload, $queryString = null)
	{
		return $this->tryRequest('put', $url, $payload, $queryString);
	}

	/**
	 * Perform a DELETE request to the Api
	 *
	 * @param      $url string Relative URL from API base URL
	 * @param null $queryString
	 *
	 * @return mixed
	 */
	public function delete($url, $queryString = null)
	{
		return $this->tryRequest('delete', $url, null, $queryString);
	}

	/**
	 * Get the Response object from the last successful request
	 *
	 * @return Response|null
	 */
	public function getLastResponse()
	{
		return $this->lastResponse;
	}
}
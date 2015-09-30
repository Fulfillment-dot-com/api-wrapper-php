<?php
/**
 * Created by IntelliJ IDEA.
 * User: mduncan
 * Date: 9/29/15
 * Time: 11:38 AM
 */

namespace Fulfillment\Api;


use FoxxMD\Utilities\ArrayUtil;
use Fulfillment\Api\Configuration\ApiConfiguration;
use Fulfillment\Api\Http\Request;
use Fulfillment\Api\Utilities\RequestParser;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use League\CLImate\CLImate;
use League\CLImate\Util\Writer\File;
use League\CLImate\Util\Writer\WriterInterface;
use Dotenv;

class Api
{

    protected $config;
    protected $http;

    /**
     * @param $config array|string|\Fulfillment\Api\Contracts\ApiConfiguration|null
     * @param $logger WriterInterface|null
     * @param $guzzle Client|null
     * @throws \Exception
     */
    public function __construct($config = null, $logger = null, $guzzle = null)
    {

        //setup guzzle
        $this->guzzle = !is_null($guzzle) ? $guzzle : new Client();

        //setup climate
        $this->climate = new CLImate;

        if (!is_null($logger)) {
            $this->climate->output->add('customLogger', $logger)->defaultTo('customLogger');
        } else if (php_sapi_name() !== 'cli') {
            //if no custom logger and this isn't a CLI app then we need to write to a file
            $path     = storage_path('logs/') . 'Log--' . date("Y-m-d") . '.log';
            $resource = fopen($path, 'a');;
            fclose($resource);
            $logFile = new File($path);
            $this->climate->output->add('file', $logFile)->defaultTo('file');

            if (!getenv('NOANSI')) {
                //we want to logs to have ANSI encoding so we can tail the log remotely and get pretty colors
                $this->climate->forceAnsiOn();
            }
        }

        //setup configuration
        if (is_string($config) || is_null($config)) {
            if (!is_null($config)) {
                if (!is_dir($config)) {
                    throw new \Exception('The provided directory location does not exist at ' . $config);
                }
                Dotenv::load($config);
            }
            $data         = [
                'username' => getenv('USERNAME'),
                'password' => getenv('PASSWORD'),
                'clientId' => getenv('CLIENT_ID'),
                'clientSecret' => getenv('CLIENT_SECRET'),
                'accessToken' => getenv('ACCESS_TOKEN'),
                'endPoint' => getenv('API_ENDPOINT')
            ];
            $this->config = new ApiConfiguration($data);

        } else if (is_array($config)) {
            $data         = [
                'username' => ArrayUtil::get($config['username']),
                'password' => ArrayUtil::get($config['password']),
                'clientId' => ArrayUtil::get($config['clientId']),
                'clientSecret' => ArrayUtil::get($config['clientSecret']),
                'accessToken' => ArrayUtil::get($config['accessToken']),
                'endpoint' => ArrayUtil::get($config['endpoint'])
            ];
            $this->config = new ApiConfiguration($data);

        } else if ($config instanceof \Fulfillment\Api\Contracts\ApiConfiguration) {
            $this->config = $config;
        }


        if (is_null($this->config->getAccessToken())) {
            //try to get from file
            if (file_exists(storage_path('auth_access_token.txt'))) {
                $this->config->setAccessToken(file_get_contents(storage_path('auth_access_token.txt')));
            }
        }

        if (is_null($this->config->getAccessToken()) && (is_null($this->config->getClientId()) || is_null($this->config->getClientSecret()) || is_null($this->config->getUsername()) || is_null($this->config->getPassword()))) {
            throw new \InvalidArgumentException('No access token provided -- so client Id, client secret, username, and password must be provided');
        }
        if (is_null($this->config->getEndPoint())) {
            throw new \InvalidArgumentException('Must provide an endpoint');
        }

        $this->http = new Request($this->guzzle, $this->config, $this->climate);

    }

    protected function tryRequest($method, $url, $payload = null, $queryString = null, $firstTry = true)
    {
        try {
            return $this->http->makeRequest($method, $url, $payload, $queryString);
        } catch (RequestException $e) {

            if ($e->getResponse()->getStatusCode() == 401 || (isset(RequestParser::parseError($e)->error) && RequestParser::parseError($e)->error == 'invalid_request')) {
                if ($firstTry) {
                    $this->climate->info('Possibly expired token, trying to refresh token...');
                    $newToken = $this->http->requestAccessToken();
                    if (!is_null($newToken)) {
                        $this->config->setAccessToken($newToken);
                        file_put_contents(storage_path('auth_access_token.txt'), $newToken);
                    }
                    $this->climate->info('Retrying request...');
                    return $this->http->makeRequest($method, $url, $payload, $queryString, $firstTry = false);
                } else {
                    //something else is wrong and requesting a new token isn't going to fix it
                    throw new \Exception('The request was unauthorized and could not be fixed by refreshing access token.', 0, $e);
                }
            } else {
                throw $e;
            }
        }
    }

    /**
     * Perform a GET request to the Api
     *
     * @param $url string Relative URL from API base URL
     * @param null $queryString
     * @return mixed
     */
    public function get($url, $queryString = null)
    {
        return $this->tryRequest('get', $url, null, $queryString);
    }

    /**
     * Perform a POST request to the Api
     *
     * @param $url string Relative URL from API base URL
     * @param $payload array Request contents as json serializable array
     * @param null $queryString
     * @return mixed
     */
    public function post($url, $payload, $queryString = null)
    {
        return $this->tryRequest('post', $url, $payload, $queryString);
    }

    /**
     * Perform a PATCH request to the Api
     *
     * @param $url string Relative URL from API base URL
     * @param $payload array Request contents as json serializable array
     * @param null $queryString
     * @return mixed
     */
    public function patch($url, $payload, $queryString = null)
    {
        return $this->tryRequest('patch', $url, $payload, $queryString);
    }

    /**
     * Perform a DELETE request to the Api
     *
     * @param $url string Relative URL from API base URL
     * @param null $queryString
     * @return mixed
     */
    public function delete($url, $queryString = null)
    {
        return $this->tryRequest('delete', $url, null, $queryString);
    }
}
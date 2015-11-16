<?php
/**
 * Created by IntelliJ IDEA.
 * User: mduncan
 * Date: 9/29/15
 * Time: 12:13 PM
 */

namespace Fulfillment\Api\Http;

use Fulfillment\Api\Configuration\ApiConfiguration;
use Fulfillment\Api\Exceptions\MissingCredentialException;
use Fulfillment\Api\Exceptions\UnauthorizedMerchantException;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Exception\RequestException;
use League\CLImate\CLImate;

class Request
{
    protected $guzzle;
    protected $config;
    protected $climate;

    /**
     * @param Client $guzzle
     * @param ApiConfiguration $config array
     * @param CLImate $climate
     */
    public function __construct(Client $guzzle, ApiConfiguration &$config, CLImate $climate)
    {
        $this->guzzle           = $guzzle;
        $this->config           = $config;
        $this->climate          = $climate;
    }

    function requestAccessToken()
    {

        try {
            $this->checkForCredentials();
        } catch(MissingCredentialException $e){
            $this->climate->error($e->getMessage());
            throw $e;
        }

        $this->climate->info($this->config->getLoggerPrefix() . 'Requesting new access token...');

        $authEndPoint = $this->config->getAuthEndpoint() . '/oauth/access_token';

        $this->climate->out($this->config->getLoggerPrefix() . 'URL: ' . $authEndPoint);

        try {
            $accessTokenResponse = $this->guzzle->post($authEndPoint, [
                'multipart' => [
                    [
                        'name' => 'client_id',
                        'contents' => $this->config->getClientId()
                    ],
                    [
                        'name' => 'client_secret',
                        'contents' => $this->config->getClientSecret()
                    ],
                    [
                        'name' => 'username',
                        'contents' => $this->config->getUsername()
                    ],
                    [
                        'name' => 'password',
                        'contents' => $this->config->getPassword()
                    ],
                    [
                        'name' => 'grant_type',
                        'contents' => 'password'
                    ],
                    [
                        'name' => 'scope',
                        'contents' => $this->config->getScope()
                    ]
                ]
            ],
                [
                    'http_errors' => false]
            );
        } catch (RequestException $e) {
            $this->climate->error($this->config->getLoggerPrefix() . 'Requesting access token has failed.');

            $this->printError($e);

            throw new UnauthorizedMerchantException();
        }
        $accessTokenJson = json_decode($accessTokenResponse->getBody());

        $this->climate->info($this->config->getLoggerPrefix() . 'Got new access token!');
        $this->climate->info($this->config->getLoggerPrefix() . 'Token: '  . $accessTokenJson->access_token);

        return $accessTokenJson->access_token;


    }

    /**
     * Make a request to the API using Guzzle
     *
     * @param $method string The HTTP VERB to use for this request
     * @param $url string The relative URL after the hostname
     * @param null $apiRequest array The contents of the api body
     * @param null $queryString array Data to add as a queryString to the url
     * @return mixed
     * @throws UnauthorizedMerchantException
     * @throws \Exception
     */
    function makeRequest($method, $url, $apiRequest = null, $queryString = null)
    {
        $urlEndPoint = $this->config->getEndpoint() . '/' . $url;

        //we want to see the url being called
        $this->climate->out($this->config->getLoggerPrefix() . 'URL: ' . $urlEndPoint);

        $data = [
            'headers' => [
                'Authorization' => 'Bearer ' . $this->config->getAccessToken()
            ],
            'json' => $apiRequest,
            'query' => $queryString
        ];


        try {
            switch ($method) {
                case 'post':
                    $response = $this->guzzle->post($urlEndPoint, $data);
                    break;
                case 'put':
                    $response = $this->guzzle->put($urlEndPoint, $data);
                    break;
                case 'delete':
                    $response = $this->guzzle->delete($urlEndPoint, $data);
                    break;
                case 'get':
                    $response = $this->guzzle->get($urlEndPoint, $data);
                    break;
                default:
                    throw new \Exception($this->config->getLoggerPrefix() . 'Missing request method!');

            }

            $this->climate->info($this->config->getLoggerPrefix() . 'Request successful.');
            $result = json_decode($response->getBody()); //for easier debugging

            return $result;

        } catch (ConnectException $c){
            $this->climate->error($this->config->getLoggerPrefix() . 'Error connecting to endpoint: ' . $c->getMessage());
            throw $c;
        } catch (RequestException $e) {
            $this->climate->error($this->config->getLoggerPrefix() . 'Request failed with status code ' . $e->getResponse()->getStatusCode());
            $this->printError($e);
            throw $e;
        }
    }

    private function printError(RequestException $requestException)
    {

        $error = $error = json_decode($requestException->getResponse()->getBody());

        if (!is_null($error) && isset($error->error)) {
            $this->climate->error($this->config->getLoggerPrefix() . '<bold>Error: </bold>' . $error->error);
            $this->climate->error($this->config->getLoggerPrefix() . '<bold>Description: </bold> ' . $error->error_description);
        } else if (!is_null($error) && isset($error->message)) {
            $this->climate->error($this->config->getLoggerPrefix() . '<bold>Error: </bold>' . $error->message);
            if (isset($error->validationErrors)) {
                if(is_array($error->validationErrors)){
                    foreach ($error->validationErrors as $prop => $message) {
                        $this->climate->error($this->config->getLoggerPrefix() . '-- ' . $prop . ': ' . $message);
                    }
                } else {
                    $this->climate->error($this->config->getLoggerPrefix() . '-- ' . $error->validationErrors);
                }
            }
        } else {
            $this->climate->error($this->config->getLoggerPrefix() . '<bold>Error: </bold>' . $requestException->getMessage());
        }
    }

    private function checkForCredentials()
    {
        if (empty($this->config->getUsername())) {
            throw new MissingCredentialException($this->config->getLoggerPrefix() . 'username');
        } elseif (empty($this->config->getPassword())) {
            throw new MissingCredentialException($this->config->getLoggerPrefix() . 'password');
        } elseif (empty($this->config->getClientId())) {
            throw new MissingCredentialException($this->config->getLoggerPrefix() . 'clientId');
        } elseif (empty($this->config->getClientSecret())) {
            throw new MissingCredentialException($this->config->getLoggerPrefix() . 'clientSecret');
        }
    }
}
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

        $this->climate->info('Requesting new access token...');

        $authEndPoint = $this->config->getAuthEndpoint() . '/oauth/access_token';

        $this->climate->out('URL: ' . $authEndPoint);

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
            $this->climate->error('Requesting access token has failed.');

            $this->printError($e);

            throw new UnauthorizedMerchantException();
        }
        $accessTokenJson = json_decode($accessTokenResponse->getBody());

        $this->climate->info('Got new access token!');
        $this->climate->info('Token: '  . $accessTokenJson->access_token);

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
        $this->climate->out('URL: ' . $urlEndPoint);

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
                    throw new \Exception('Missing request method!');

            }

            $this->climate->info('Request successful.');
            $result = json_decode($response->getBody()); //for easier debugging

            return $result;

        } catch (RequestException $e) {
            $this->climate->error('Request failed with status code ' . $e->getResponse()->getStatusCode());
            $this->printError($e);
            throw $e;
        }
    }

    private function printError(RequestException $requestException)
    {

        $error = $error = json_decode($requestException->getResponse()->getBody());

        if (!is_null($error) && isset($error->error)) {
            $this->climate->error('<bold>Error: </bold>' . $error->error);
            $this->climate->error('<bold>Description: </bold> ' . $error->error_description);
        } else if (!is_null($error) && isset($error->message)) {
            $this->climate->error('<bold>Error: </bold>' . $error->message);
            if (isset($error->validationErrors)) {
                foreach ($error->validationErrors as $prop => $message) {
                    $this->climate->error('-- ' . $prop . ': ' . $message);
                }
            }
        } else {
            $this->climate->error('<bold>Error: </bold>' . $requestException->getMessage());
        }
    }

    private function checkForCredentials()
    {
        if (empty($this->config->getUsername())) {
            throw new MissingCredentialException('username');
        } elseif (empty($this->config->getPassword())) {
            throw new MissingCredentialException('password');
        } elseif (empty($this->config->getClientId())) {
            throw new MissingCredentialException('clientId');
        } elseif (empty($this->config->getClientSecret())) {
            throw new MissingCredentialException('clientSecret');
        }
    }
}
# api-wrapper-php
A barebones wrapper to ease authentication and low-level communication with FDC API

## Example

```php
use Fulfillment\Api\Api;

$data = [
    'endpoint' => $endPoint,
    'clientId' => $clientId,
    'clientSecret' => $clientSecret,
    'accessToken' => $accessToken,
    'username' => $username,
    'password' => $password
];

$apiClient = new Api($data);

$newPostageClient = [
    'email' => 'some@email.com',
    'clientId' => 2
];

$returnedUser = $apiClient->post('users', $newPostageClient); //POST request with body as json

$apiClient->get('users', ['clientId' => '2']); //GET request with query string ?clientId=2
```
## Installation

```
composer require fulfillment/api-wrapper
```

## Configuration

### Authentication Configuration

Minimum requirements for auth -- you must have:

* client id
* client secret
* username
* password

**AND/OR**

* access token

Note that if the access token expires re-authentication cannot occur if credentials are not present.

Auth can be parsed using several options:

**Array**

```php
$data = [
    'endpoint' => $endPoint,
    'clientId' => $clientId,
    'clientSecret' => $clientSecret,
    'accessToken' => $accessToken,
    'username' => $username,
    'password' => $password
];

$apiClient = new Api($config)
```

**DotEnv**

Use a `.env` file compatible with [phpdotenv](https://github.com/vlucas/phpdotenv) library. Simply specify the absolute path to the folder containing your `.env` file as an argument in the constructor.

```php
$fileLocation = __DIR__ . DIRECTORY_SEPARATOR . '.env';

$apiClient = new Api($fileLocation);
```

**Environmental Variables**

Use environmental variables (available in `$_ENV`)

* **USERNAME**
* **PASSWORD**
* **CLIENT_ID**
* **CLIENT_SECRET**
* **ACCESS_TOKEN**
* **API_ENDPOINT**

```php
$apiClient = new Api();
```

**NOTE:** If an access token is generated the package will store a copy of the token either at `__DIR__ . /logs` or the location returned by `storage_path('logs/')`

### Logging Configuration

The package defaults to console output or file if STDOUT is not available. You may use your own logger by passing an object that implements [`League\CLImate\Util\Writer\WriterInterface`](http://climate.thephpleague.com/output/)

```php
$apiClient = new Api($config, $logger);
```

### Guzzle Configuration

You may use your own `guzzle` instance by passing it into the constructor

```php
$apiClient = new Api($config, $logger, $guzzle)
```

If no instance is passed a new one is created.


## Usage

If credentials are provided the client can(will) authenticate itself if an access token is not present or invalid. 

Basic requests are by using HTTP verbs methods:

* **get($url, $queryString = null)**
* **post($url, $body, $queryString = null)**
* **patch($url, $body, $queryString = null)**
* **delete($url, $queryString = null)**

```php
$apiClient->get($url, $queryString);
$apiClient->post($url, $body, $queryString);
$apiClient->patch($url, $body, $queryString);
$apiClient->delete($url, $queryString);
```

* **$url** is the relative url from the endpoint, it is concatenated before the request is sent -- `$fullURl = $endPoint . '/' . $url;`
* **$body** is an array or object that can be jsonified.
* **$queryString** is represented as a key/value array -- `$queryString = ['myKey' => 'myValue']` is equivalent to `http://endpoint/url?myKey=myValue`

### Helpers

Two functions are available to make response parsing more convenient:

**parseError**

This function will json decode `RequestException` thrown by Guzzle and return the error object used by FDC as a standard object.

```php
RequestParser::parseError($r)
```

**getErrorCode**

This will do the same as above will be only return an error code if one is present on the error.

```php
RequestParser::getErrorCode($r)
```


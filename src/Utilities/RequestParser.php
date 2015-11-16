<?php
/**
 * Created by IntelliJ IDEA.
 * User: mduncan
 * Date: 9/29/15
 * Time: 12:49 PM
 */

namespace Fulfillment\Api\Utilities;


use GuzzleHttp\Exception\RequestException;

class RequestParser
{
    public static function parseError(RequestException $requestException)
    {

        $error = $error = json_decode($requestException->getResponse()->getBody(), true);

        if (!is_null($error)) {
            return $error;
        } else {
            return $requestException->getMessage();
        }
    }

    public static function getErrorCode(RequestException $requestException)
    {
        $error = $error = json_decode($requestException->getResponse()->getBody());

        if (!is_null($error) && isset($error->error_code)) {
            return $error->error_code;
        } else {
            return null;
        }
    }
}
<?php
/**
 * Created by IntelliJ IDEA.
 * User: mduncan
 * Date: 9/29/15
 * Time: 12:49 PM
 */

namespace Fulfillment\Api\Utilities;


use GuzzleHttp\Exception\RequestException;

class RequestParser {
	/**
	 * Returns an object or array of the FDC error parsed from the Guzzle Request exception
	 *
	 * @param RequestException $requestException
	 * @param bool             $isAssoc
	 *
	 * @return string
	 */
	public static function parseError(RequestException $requestException, $isAssoc = true)
	{

		$error = $error = json_decode($requestException->getResponse()->getBody(), $isAssoc);

		if (!is_null($error))
		{
			return $error;
		}
		else
		{
			return $requestException->getMessage();
		}
	}

	public static function getErrorCode(RequestException $requestException)
	{
		$error = $error = json_decode($requestException->getResponse()->getBody());

		if (!is_null($error) && isset($error->error_code))
		{
			return $error->error_code;
		}
		else
		{
			return null;
		}
	}
}
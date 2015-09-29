<?php
/**
 * Created by IntelliJ IDEA.
 * User: mduncan
 * Date: 9/29/15
 * Time: 12:17 PM
 */

namespace Fulfillment\Api\Contracts;


interface ApiConfiguration
{
    public function getUsername();
    public function getPassword();
    public function getClientId();
    public function getClientSecret();
    public function getAccessToken();
    public function setAccessToken($token);
    public function getEndpoint();
}
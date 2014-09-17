<?php

/**
 *
 * Betfair Simple API (for API-NG)
 *
 * ------------------------------------------------------------------------
 *
 * Copyright (c) 2014 Dan Cotora
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>
 *
 * ------------------------------------------------------------------------
 *
 * This PHP class can be used to make request against Betfair API-NG endpoint
 *
 */

namespace Betfair;

class SimpleAPI
{

    /**
     * Define the login & the request API endpoints
     */
    const LOGIN_ENDPOINT   = "https://identitysso-api.betfair.com/api/certlogin";
    const REQUEST_ENDPOINT = "https://api.betfair.com/exchange/betting/json-rpc/v1";


    /**
     * The Betfair configuration options
     *
     * @var array
     */
    private $configuration;


    /**
     * The Betfair API session token
     *
     * @var string
     */
    private $sessionToken;


    /**
     * The class constructor
     * @param array $configuration The Betfair configuration options
     */
    public function __construct($configuration) {
        if (!function_exists('curl_version')) {
            throw new SimpleAPIException('The PHP curl extension is not installed. This extension is required to be able to use the API.');
        }

        if (!isset($configuration['username'])) {
            throw new SimpleAPIException('The API username is missing from the configuration options');
        }

        if (!isset($configuration['password'])) {
            throw new SimpleAPIException('The API password is missing from the configuration options');
        }

        if (!isset($configuration['appKey'])) {
            throw new SimpleAPIException('The API application key is missing from the configuration options');
        }

        if (!isset($configuration['cert'])) {
            throw new SimpleAPIException('The API certificate path is missing from the configuration options');
        }

        if (!is_readable($configuration['cert'])) {
            throw new SimpleAPIException('The API certificate file does not exist or is not readable');
        }

        $this->configuration = $configuration;
    }

    /**
     * Logs into Betfair and gets a session token
     *
     * @param  boolean $refresh TRUE if a new session token is required, FALSE if the cached one can be use
     * @return string           The session token
     */
    public function getSessionToken($refresh = FALSE)
    {
        // If we have a cached session token and a new one is not needed
        // return the cached one
        if (!$refresh && !empty($this->sessionToken)) {
            return $this->sessionToken;
        }

        // Get the app key
        $appKey = $this->configuration['appKey'];

        // Initialize the CURL request
        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, self::LOGIN_ENDPOINT);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

        // Set the client SSL certificate
        curl_setopt($ch, CURLOPT_SSLCERT, $this->configuration['cert']);

        // Set the headers
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'X-Application: ' . $appKey,
            'Accept: application/json'
        ));

        // Add the POST data
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query(array('username' => $this->configuration['username'], 'password' => $this->configuration['password']), '', '&'));

        // Get the response
        $response = json_decode(curl_exec($ch));

        // Close the connection
        curl_close($ch);

        // If no sessionToken was found, throw an exception
        if (empty($response->sessionToken)) {
            throw new SimpleAPIException('Could not get a valid session token from the login endpoint (check your configuration options)');
        }

        // Set the cached session token & return it
        $this->sessionToken = $response->sessionToken;

        // Return the session token
        return $this->sessionToken;
    }


    /**
     * Make an API request
     *
     * @param  string $operation The operation
     * @param  string $params    The parameters
     * @return string            The API response
     */
    public function request($operation, $params)
    {
        // Get the session token
        $sessionToken = $this->getSessionToken();

        // Get the app key
        $appKey = $this->configuration['appKey'];

        // Initialize the CURL request
        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, self::REQUEST_ENDPOINT);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'X-Application: ' . $appKey,
            'X-Authentication: ' . $sessionToken,
            'Accept: application/json',
            'Content-Type: application/json'
        ));

        // Add the POST data
        $postData = '[{"jsonrpc": "2.0", "method": "SportsAPING/v1.0/' . $operation . '", "params" :' . $params . ', "id": 1}]';
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);

        // Get the response
        $response = json_decode(curl_exec($ch));

        // Close the connection
        curl_close($ch);

        // If there was an error, clear the session token cache and throw an exception
        if (isset($response->error) || isset($response[0]->error)) {
            $this->sessionToken = null;
        }

        if (isset($response->error)) {
            throw new SimpleAPIException($response->error->message, $response->error->code);
        } elseif (isset($response[0]->error)) {
            throw new SimpleAPIException($response[0]->error->message, $response[0]->error->code);
        }

        // Return the response
        return $response;
    }

}
<?php

namespace Laravel\Socialite\One\Server;

use League\OAuth1\Client\Server\Server;
use League\OAuth1\Client\Credentials\CredentialsInterface;
use League\OAuth1\Client\Credentials\TokenCredentials;
use League\OAuth1\Client\Credentials\TemporaryCredentials;
use League\OAuth1\Client\Credentials\CredentialsException;
use League\OAuth1\Client\Credentials\ClientCredentials;
use League\OAuth1\Client\Server\User;
use Guzzle\Http\Exception\BadResponseException;

use Carbon\Carbon;


class GarminServer extends Server
{
    protected $test = false;
    public function test($bool = true) {
        $this->test = $bool;
    }
    /**
     * {@inheritDoc}
     */
    public function urlTemporaryCredentials()
    {
        return 'http://connectapitest.garmin.com/oauth-service-1.0/oauth/request_token';
    }
    /**
     * {@inheritDoc}
     */
    public function urlAuthorization()
    {
        return 'http://connecttest.garmin.com/oauthConfirm';
    }
    /**
     * {@inheritDoc}
     */
    public function urlTokenCredentials()
    {
        return 'http://connectapitest.garmin.com/oauth-service-1.0/oauth/access_token';
    }
    /**
     * {@inheritDoc}
     */
    public function urlUserDetails()
    {
        throw new \Exception("Garmin does not have a userDetails endpoint currently. Sorry...");
    }
    /**
     * {@inheritDoc}
     */
    public function userDetails($data, TokenCredentials $tokenCredentials)
    {
        $user = new User;
        return $user;
    }
    /**
     * {@inheritDoc}
     */
    public function userUid($data, TokenCredentials $tokenCredentials)
    {
        return;
    }
    /**
     * {@inheritDoc}
     */
    public function userEmail($data, TokenCredentials $tokenCredentials)
    {
        return;
    }
    /**
     * {@inheritDoc}
     */
    public function userScreenName($data, TokenCredentials $tokenCredentials)
    {
        return;
    }
    /**
     * Garmin doesn't have any public endpoints, so let's make sure we don't try this at home, kids
     */
    protected function fetchUserDetails(TokenCredentials $tokenCredentials, $force = true) {
        return [];
    }
    /**
     * Generate the OAuth protocol header for requests other than temporary
     * credentials, based on the URI, method, given credentials & body query
     * string.
     *
     * @param  string  $method
     * @param  string  $uri
     * @param  CredentialsInterface  $credentials
     * @param  array  $bodyCredentials
     * @return string
     */
    protected function protocolHeader($method, $uri, CredentialsInterface $credentials, array $bodyParameters = array())
    {
        $parameters = array_merge($this->baseProtocolParameters(), array(
            'oauth_token' => $credentials->getIdentifier(),
        ));
        $this->signature->setCredentials($credentials);
        $parameters['oauth_signature'] = $this->signature->sign($uri, array_merge($parameters, $bodyParameters), $method);
        if ( isset($bodyParameters['oauth_verifier']) ) {
            $parameters['oauth_verifier'] =  $bodyParameters['oauth_verifier'];
        }
        return $this->normalizeProtocolParameters($parameters);
    }

    /**
     * Handle a bad response coming back when getting token credentials.
     *
     * @param  BadResponseException
     * @return void
     * @throws CredentialsException
     */
    protected function handleTokenCredentialsBadResponse(BadResponseException $e)
    {
        $response = $e->getResponse();
        $body = $response->getBody();
        $statusCode = $response->getStatusCode();
        $body->uncompress();
        throw new CredentialsException("Received HTTP status code [$statusCode] with message \"$body\" when getting token credentials.");
    }
    
    /**
     * Get the authorization URL by passing in the temporary credentials
     * identifier or an object instance.
     *
     * @param  TemporaryCredentials|string  $temporaryIdentifier
     * @return string
     */
    public function getAuthorizationUrl($temporaryIdentifier)
    {
        // Somebody can pass through an instance of temporary
        // credentials and we'll extract the identifier from there.
        if ($temporaryIdentifier instanceof TemporaryCredentials) {
            $temporaryIdentifier = $temporaryIdentifier->getIdentifier();
        }
        $parameters = array(
            'oauth_token' => $temporaryIdentifier,
            'oauth_callback' => $this->clientCredentials->getCallbackUri(),
        );
        return $this->urlAuthorization().'?'.http_build_query($parameters);
    }

    /************************ added by tavo *******************************/

    /**
     * Creates a client credentials instance from an array of credentials.
     *
     * @param array $clientCredentials
     *
     * @return ClientCredentials
     */
    protected function createClientCredentials(array $clientCredentials)
    {
        $keys = array('identifier', 'secret');

        foreach ($keys as $key) {
            if (!isset($clientCredentials[$key])) {
                throw new \InvalidArgumentException("Missing client credentials key [$key] from options.");
            }
        }

        $_clientCredentials = new ClientCredentials();
        $_clientCredentials->setIdentifier($clientCredentials['identifier']);
        $_clientCredentials->setSecret($clientCredentials['secret']);

        if (isset($clientCredentials['callback_url'])) {
            $_clientCredentials->setCallbackUri($clientCredentials['callback_url']);
        }

        return $_clientCredentials;
    }

    /**
     * Gets temporary credentials by performing a request to
     * the server.
     *
     * @return TemporaryCredentials
     */
    public function getTemporaryCredentials()
    {
        $uri = $this->urlTemporaryCredentials();

        $client = $this->createHttpClient();

        $header = $this->temporaryCredentialsProtocolHeader($uri);
        //$authorizationHeader = array('Authorization' => $header);
        $requestHeaders =  array(
            'Authorization' => $header,
            'Content-Type' => 'application/octet-stream',
            'User-Agent' => 'Java/1.6.0_13'
        );


        $headers = $this->buildHttpClientHeaders($requestHeaders);

        try {
            $response = $client->post($uri, $headers)->send();
            //$response = $client->get($uri, $headers)->send();
        } catch (BadResponseException $e) {
            return $this->handleTemporaryCredentialsBadResponse($e);
        }

        return $this->createTemporaryCredentials($response->getBody());
    }


    /**
     * Generate the OAuth protocol header for a temporary credentials
     * request, based on the URI.
     *
     * @param string $uri
     *
     * @return string
     */
    protected function temporaryCredentialsProtocolHeader($uri)
    {
        $parameters = array_merge($this->baseProtocolParameters(), array(
            'oauth_callback' => $this->clientCredentials->getCallbackUri(),
        ));

        $parameters['oauth_signature'] = $this->signature->sign($uri, $parameters, 'POST');

        return $this->normalizeProtocolParameters($parameters);
    }

    /**
     * Retrieves token credentials by passing in the temporary credentials,
     * the temporary credentials identifier as passed back by the server
     * and finally the verifier code.
     *
     * @param TemporaryCredentials $temporaryCredentials
     * @param string               $temporaryIdentifier
     * @param string               $verifier
     *
     * @return TokenCredentials
     */
    public function getTokenCredentials(TemporaryCredentials $temporaryCredentials, $temporaryIdentifier, $verifier)
    {
        if ($temporaryIdentifier !== $temporaryCredentials->getIdentifier()) {
            throw new \InvalidArgumentException(
                'Temporary identifier passed back by server does not match that of stored temporary credentials.
                Potential man-in-the-middle.'
            );
        }

        $uri = $this->urlTokenCredentials();
        $bodyParameters = array('oauth_verifier' => $verifier);

        $client = $this->createHttpClient();

        $headers = $this->getHeaders($temporaryCredentials, 'POST', $uri, $bodyParameters);
        $headers['Content-Type'] = 'application/octet-stream';
        $headers['User-Agent'] = 'Java/1.6.0_13';

        try {
            $response = $client->post($uri, $headers, $bodyParameters)->send();
        } catch (BadResponseException $e) {
            return $this->handleTokenCredentialsBadResponse($e);
        }

        return $this->createTokenCredentials($response->getBody());
    }
}
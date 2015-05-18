<?php

namespace Laravel\Socialite\One\Server;

use League\OAuth1\Client\Server\Server;
use League\OAuth1\Client\Credentials\TokenCredentials;
use League\OAuth1\Client\Server\User;

use League\OAuth1\Client\Credentials\CredentialsInterface;
use League\OAuth1\Client\Signature\HmacSha1Signature;

use GuzzleHttp\Message\Request;

use Carbon\Carbon;


class GarminServer extends Server
{

    /**
     * {@inheritDoc}
     */
    public function urlRequestToken()
    {
        return 'http://gcsapitest.garmin.com/gcs-api/oauth/request_token';
    }

    /**
     * {@inheritDoc}
     */
    public function urlAccessToken()
    {
        return 'http://gcsapitest.garmin.com/gcs-api/oauth/access_token';
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
        return null;
    }

    public function urlUserActivities()
    {
        return 'http://gcsapitest.garmin.com/gcs-api/api/json';
    }


    protected function fetchUserDetails(TokenCredentials $tokenCredentials, $force = true)
    {
        return null;
    }

    /**
     * {@inheritDoc}
     */
    public function userDetails($data, TokenCredentials $tokenCredentials)
    {

        $user = new User;

        $uData = $data['user'];

        $user->uid = $uData['encodedId'];
        $user->nickname = isset($uData['displayName']) ? $uData['displayName'] : '';
        $user->name = isset($uData['fullName']) ? $uData['fullName'] : '';
        $user->imageUrl = isset($uData['avatar150']) ? $uData['avatar150'] : '';

        $used = array('encodedId', 'displayName', 'fullName', 'avatar150');


        if($data) {
            foreach ($data as $key => $value) {
                if (strpos($key, 'url') !== false) {

                    if (!in_array($key, $used)) {
                        $used[] = $key;
                    }

                    $user->urls[$key] = $value;
                }
            }

            // Save all extra data
            $user->extra = array_diff_key($data, array_flip($used));
        }

        return $user;
    }



    public function getUserActivities($tokenCredentials, $userId, $activityDate, $partnerCredentials)
    {

        $userAccessToken = $tokenCredentials->getIdentifier();
        $userSecret = $tokenCredentials->getSecret();
        echo 'userAccessToken = ' . $userAccessToken.'<br/>';
        echo 'userSecret = ' . $userSecret.'<br/>';

        $token = $partnerCredentials['token'];
        $secret = $partnerCredentials['token_secret'];



        //var_dump($tokenCredentials);
        /// THIS IS THE PARTNER KEYS that needs to be refreshed every week.
        //$tokenCredentials->setIdentifier($partnerCredentials['token']);
        //$tokenCredentials->setSecret($partnerCredentials['token_secret']);

        $partnerCredentials = $this->createClientCredentials(['identifier' => $partnerCredentials['token'],
                                                              'secret' => $partnerCredentials['token_secret'] ]);

        // oauth client creds
        $configCreds = $this->getClientCredentials();
        $clientToken = $configCreds->getIdentifier();


        $url = $this->urlUserActivities();
        $client = $this->createHttpClient();

        $parameters = array_merge($this->baseProtocolParameters(), array(
            'oauth_token' => $partnerCredentials->getIdentifier(),
        ));

        $clientToken = $parameters['oauth_consumer_key'];
        $userAccessToken = $userAccessToken; //$tokenCredentials->getIdentifier();

        $startTimeMS = Carbon::parse($activityDate)->timestamp * 1000;
        $endTimeMS = Carbon::parse($activityDate)->endOfDay()->timestamp * 1000;

        $ms = round(microtime(true) * 1000);
        $activitySummaryRequest = [
                                    'connectActivity' => true,
                                    'wellnessDaily' => true,
                                    //'wellnessMonitoring' => true,
                                    'connectConsumerKey' => $clientToken,  //'66fd9551-aab2-4c79-96db-78d4775e009a'
                                    'connectUserAccessToken' => $userAccessToken, //'962021c7-4a35-401c-8eaa-b0c67ab6d912'
                                    'acknowledgeFlag' => false,
                                    'beginStartTimeMillis' => $startTimeMS, //1431388839000,
                                    'endStartTimeMillis' =>   $endTimeMS //1431471639000 //1426827600000 
                                    ];


        $postSummary = array('serviceRequests' =>
                                array('WELLNESS' => 
                                    array('activityRequests' =>
                                        [array('GET_SUMMARIES' => [$activitySummaryRequest])] )));

        

  
        $postBodyJson = json_encode($postSummary);
        echo $postBodyJson;
        //$postBodyJson = '{"serviceRequests":{"WELLNESS":{"activityRequests":[{"GET_ACTIVITY_SUMMARY":[{"activitySummaryRequest":{"consumerToken":"66fd9551-aab2-4c79-96db-78d4775e009a","userAccessToken":"fc84e072-aac0-4394-b8fa-b364ef08a42a","unacknowledgedOnly":false,"beginTimeMillis":1426525200000, "endTimeMillis":1426532400000, "acknowledgeFlag":false},"app":{"appId":"ID_6","version":1.0}}]}]}}}';

        //$postBodyJson = '{"serviceRequests":{"WELLNESS":{"activityRequests":[{"GET_SUMMARIES":[{"connectActivity":true,"wellnessDaily":true,"wellnessMonitoring":true,"connectConsumerKey":"66fd9551-aab2-4c79-96db-78d4775e009a","connectUserAccessToken":"fc84e072-aac0-4394-b8fa-b364ef08a42a","acknowledgeFlag":false,"beginStartTimeMillis":1426532400000,"endStartTimeMillis":1426532400000}]}]}}}';
        //echo "<br/><br/>" . $postBodyJson2;

       //echo $postBodyJson;
       //exit;

        $request = $client->post($url, array(
                    'Authorization' => $this->protocolHeader('POST', $url, $partnerCredentials),
                ),
                $postBodyJson);

           $response = $client->post($url, array(
                    'Authorization' => $this->protocolHeader('POST', $url, $partnerCredentials),
                ),
                $postBodyJson)->send();


            try {
                $response = $client->post($url, array(
                    'Protocol' => 'JSON',
                    'Authorization' => $this->protocolHeader('POST', $url, $partnerCredentials),
                ),
                $postBodyJson)->send();




            } catch (BadResponseException $e) {



                $response = $e->getResponse();
                $body = $response->getBody();

                $statusCode = $response->getStatusCode();

                throw new \Exception(
                    "Received error [$body] with status code [$statusCode] when retrieving token credentials."
                );
            }


            $json = $response->getBody();
            echo '<br/><br/>'.$json.'<br/><br/>';
        return json_decode($response->getBody());

    }



    /**
     * @override for Garmin - needed the oauth_verifier in the headers params
     *
     * Generate the OAuth protocol header for requests other than temporary
     * credentials, based on the URI, method, given credentials & body query
     * string.
     *
     * @param  string  $method
     * @param  string  $uri
     * @param  CredentialsInterface  $credentials
     * @param  array  $bodyParameters
     * @return string
     */
    protected function protocolHeader($method, $uri, CredentialsInterface $credentials, array $bodyParameters = array())
    {

        $parameters = array_merge($this->baseProtocolParameters(), array(
            'oauth_token' => $credentials->getIdentifier(),
            //'oauth_token' => '3c84757b-c693-4c3f-8fb8-90a66adc59f8',
        ));

        $this->signature->setCredentials($credentials);

        $parameters['oauth_signature'] = $this->signature->sign(
            $uri,
            array_merge($parameters, $bodyParameters),
            $method
        );

        //@garmin
        $parameters = array_merge($parameters, $bodyParameters);



        $normalizedParams = $this->normalizeProtocolParameters($parameters);

        return $normalizedParams;
    }

    /**
     * Any additional required protocol parameters for an
     * OAuth request.
     *
     * @return array
     */
    protected function additionalProtocolParameters()
    {
        return array('oauth_verifier');
    }


    public function createCredentials(array $clientCredentials)
    {
        return $this->createClientCredentials($clientCredentials);
    }

    /**
     * {@inheritDoc}
     */
    public function userUid($data, TokenCredentials $tokenCredentials)
    {
        return $data['uid'];      
    }

    /**
     * {@inheritDoc}
     */
    public function userEmail($data, TokenCredentials $tokenCredentials)
    {
        return null;
    }

    /**
     * {@inheritDoc}
     */
    public function userScreenName($data, TokenCredentials $tokenCredentials)
    {
        return $data['nickname'];
    }


    /**
     * Gets temporary credentials by performing a request to
     * the server.
     *
     * @return  TemporaryCredentials
     */
    public function getPartnerRequestToken()
    {
        $uri = $this->urlRequestToken();

        $client = $this->createHttpClient();

        $header = $this->temporaryPartnerRequestTokenProtocolHeader($uri);
        $authorizationHeader = array('Authorization' => $header);
        $headers = $this->buildHttpClientHeaders($authorizationHeader);

        //echo '<br/>'.$uri;
        //var_dump($headers);

        try {
            $response = $client->post($uri, $headers)->send();
        } catch (BadResponseException $e) {
            return $this->handleTemporaryCredentialsBadResponse($e);
        }

        $body = $response->getBody();
        //$resp = $this->createTemporaryCredentials($response->getBody());
        parse_str($body, $data);
        //echo "dump data";
        //var_dump($data);

        $oauth_token = $data['oauth_token'];
        $oauth_token_secret = $data['oauth_token_secret'];
        //echo 'DECODE<br/>';
        //echo "oauth_token: " . $oauth_token.'<br/>';
        //echo "oauth_secret" . $oauth_token_secret.'<br/>';



        //////////////////////////////
        $uri = $this->urlAccessToken();

        $client = $this->createHttpClient();

        $credentials = $this->createClientCredentials(['identifier' => $oauth_token, 'secret' => $oauth_token_secret]);

        $header = $this->temporaryPartnerAccessTokenProtocolHeader($uri, $credentials);
        $authorizationHeader = array('Authorization' => $header);
        $headers = $this->buildHttpClientHeaders($authorizationHeader);

        //echo '<br/>'.$uri;
        //var_dump($headers);

        try {
            $response = $client->post($uri, $headers)->send();
        } catch (BadResponseException $e) {
            //var_dump($e);
            //exit;
            return $this->handleTemporaryCredentialsBadResponse($e);
        }

        $body = $response->getBody();
        //$resp = $this->createTemporaryCredentials($response->getBody());
        parse_str($body, $data);
        $oauth_token = $data['oauth_token'];
        $oauth_token_secret = $data['oauth_token_secret'];

        //echo "final:<br/>";
        //var_dump($data);
        $resp = $data;

        return $resp;
    }

    /**
     * Generate the OAuth protocol header for a temporary credentials
     * request, based on the URI.
     *
     * @param  string  $uri
     * @return string
     */
    protected function temporaryPartnerRequestTokenProtocolHeader($uri)
    {
        $parameters = array_merge($this->baseProtocolParameters(), array(
            'oauth_token' => null,
        ));

        $parameters['oauth_signature'] = $this->signature->sign($uri, $parameters, 'POST');

        return $this->normalizeProtocolParameters($parameters);
    }

    protected function temporaryPartnerAccessTokenProtocolHeader($uri, $clientCredentials)
    {
        $parameters = array_merge($this->baseProtocolParameters(), array(
            'oauth_token' => $clientCredentials->getIdentifier(),
        ));


        $signature = new HmacSha1Signature($clientCredentials);
        //var_dump($clientCredentials);
        //$parameters['oauth_signature'] = $signature->sign($uri, $parameters, 'POST');
        $this->signature->setCredentials($clientCredentials);
        $parameters['oauth_signature'] = $this->signature->sign($uri, $parameters, 'POST');

        //echo "oauth_signature = " . $parameters['oauth_signature'];
        //var_dump($parameters);

        return $this->normalizeProtocolParameters($parameters);
    }


}

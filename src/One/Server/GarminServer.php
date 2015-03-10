<?php

namespace Laravel\Socialite\One\Server;

use League\OAuth1\Client\Server\Server;
use League\OAuth1\Client\Credentials\TokenCredentials;
use League\OAuth1\Client\Server\User;


class Garmin extends Server
{
    /**
     * {@inheritDoc}
     */
    public function urlTemporaryCredentials()
    {
        //return 'http://connectapitest.garmin.com/oauth‐service‐1.0/oauth/request_token';
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
        return 'http://connectapitest.garmin.com/oauth‐service‐1.0/oauth/access_token';
    }

    /**
     * {@inheritDoc}
     */
    public function urlUserDetails()
    {
        return 'https://api.fitbit.com/1/user/-/profile.json';
    }

    public function urlUserActivities($userId, $dateStr)
    {
        return 'https://api.fitbit.com/1/user/'. $userId .'/activities/date/' . $dateStr.'.json';
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

        return $user;
    }

    public function getUserActivities($tokenCredentials, $userId, $activityDate)
    {

        $url = $this->urlUserActivities($userId, $activityDate);
        $client = $this->createHttpClient();

            try {
                $response = $client->get($url, array(
                    'Authorization' => $this->protocolHeader('GET', $url, $tokenCredentials),
                ))->send();
            } catch (BadResponseException $e) {
                $response = $e->getResponse();
                $body = $response->getBody();
                $statusCode = $response->getStatusCode();

                throw new \Exception(
                    "Received error [$body] with status code [$statusCode] when retrieving token credentials."
                );
            }

        return json_decode($response->getBody());

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
}

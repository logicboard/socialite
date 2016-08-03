<?php namespace Laravel\Socialite\Two;

use App\Data\Models\UserApp;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Log;

class FitbitProvider extends AbstractProvider implements ProviderInterface
{

    /**
     * The scopes being requested.
     *
     * updated 1
     *
     * @var array
     */

    protected $scopes = [
        'activity',
        'profile'
    ];

    /**
     * {@inheritdoc}
     */
    protected $scopeSeparator = ' ';

    /**
     * Format the given scopes.
     *
     * @param  array $scopes
     * @return string
     */
    protected function formatScopes(array $scopes)
    {
        return implode(' ', $scopes);
    }

    /**
     * {@inheritdoc}
     */
    protected function getAuthUrl($state)
    {
        return $this->buildAuthUrlFromBase('https://www.fitbit.com/oauth2/authorize', $state);
    }

    /**
     * {@inheritdoc}
     */
    protected function getTokenUrl()
    {
        return 'https://api.fitbit.com/oauth2/token';
    }

    protected function getRefreshTokenUrl()
    {
        return 'https://api.fitbit.com/oauth2/token';
    }

    protected function getRefreshTokenFields($code)
    {
        return [
            'grant_type' => 'refresh_token',
            'refresh_token' => $code,
        ];
    }

    public function refreshToken($code)
    {

        $response = $this->getHttpClient()->post($this->getRefreshTokenUrl(), [
            'headers' => ['Accept' => 'application/json', 'Authorization' => 'Basic ' . base64_encode($this->clientId . ':' . $this->clientSecret)],
            'body' => $this->getRefreshTokenFields($code),
        ]);

        return json_decode($response->getBody());

    }

    /**
     * Get the access token for the given code.
     *
     * @param  string $code
     * @return string
     */
    public function getAccessToken($code)
    {
        $response = $this->getHttpClient()->post($this->getTokenUrl(), [
            'headers' => ['Accept' => 'application/json', 'Authorization' => 'Basic ' . base64_encode($this->clientId . ':' . $this->clientSecret)],
            'body' => $this->getTokenFields($code),
        ]);

        return $this->parseAccessToken($response->getBody());
    }

    /**
     * {@inheritdoc}
     */
    protected function getUserByToken($token)
    {
        $response = $this->getHttpClient()->get('https://api.fitbit.com/1/user/-/profile.json', [
            'headers' => [
                'Authorization' => 'Bearer ' . $token,
            ],
        ]);
        return json_decode($response->getBody(), true);
    }

    public function getFitnessActivities($token, $options = array())
    {

        Log::info("getFitnessActivities() = " . $token);

        $paramsString = http_build_query($options);
        $endPoint = 'https://www.strava.com/api/v3/activities?' . $paramsString;
        $response = $this->getHttpClient()->get($endPoint, [
            'headers' => [
                'Accept' => 'application/json',
                'authorization' => 'Bearer ' . $token

            ],
        ]);

        return json_decode($response->getBody());

//        $url = $this->urlUserActivities($userId, $activityDate);
//        $client = $this->createHttpClient();
//
//        try {
//            $response = $client->get($url, array(
//                'Authorization' => $this->protocolHeader('GET', $url, $tokenCredentials),
//            ))->send();
//        } catch (BadResponseException $e) {
//            $response = $e->getResponse();
//            $body = $response->getBody();
//            $statusCode = $response->getStatusCode();
//
//            throw new \Exception(
//                "Received error [$body] with status code [$statusCode] when retrieving token credentials."
//            );
//        }
//
//        return json_decode($response->getBody());

    }

    public function getUserActivities($tokenCredentials, $userId, $activityDate, $userApp)
    {

        $url = $this->urlUserActivities($userId, $activityDate);
        $client = $this->getHttpClient();

        $token = "no-token";
        if ($userApp) {
            $token = $userApp->token;
        }

//        Log::info("Fitbit Token = " . $token);

        try {
            $response = $client->get($url, [
                'headers' => ['Accept' => 'application/json', 'Authorization' => 'Bearer ' . $token],
            ]);
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

    /**
     * {@inheritdoc}
     */
    public function getAccessTokenResponse($code)
    {

        $postKey = 'form_params';
        //(version_compare(ClientInterface::VERSION, '6') === 1) ? 'form_params' : 'body';
        $response = $this->getHttpClient()->post($this->getTokenUrl(), [
            'headers' => ['Accept' => 'application/json', 'Authorization' => 'Basic ' . base64_encode($this->clientId . ':' . $this->clientSecret)],
            $postKey => $this->getTokenFields($code),
        ]);
        $this->credentialsResponseBody = json_decode($response->getBody(), true);
        return json_decode($response->getBody(), true);
    }

    public function urlUserActivities($userId, $dateStr)
    {
        return 'https://api.fitbit.com/1/user/' . $userId . '/activities/date/' . $dateStr . '.json';
    }

    /**
     * Get the POST fields for the token request.
     *
     * @param  string $code
     * @return array
     */
    protected function getTokenFields($code)
    {
        
//        return array_add(
//            parent::getTokenFields($code), 'grant_type', 'authorization_code'
//        );
        return array_merge(parent::getTokenFields($code), [
            'grant_type' => 'authorization_code',
            'expires_in' => '31536000',
        ]);
    }

    /**
     * {@inheritdoc}
     */
    protected function mapUserToObject(array $user)
    {
        return (new User())->setRaw($user)->map([
            'id' => $user['user']['encodedId'],
            'nickname' => isset($user['user']['nickname']) ? $user['user']['nickname'] : '',
            'name' => $user['user']['fullName'],
            'email' => null,
            'avatar' => $user['user']['avatar150'],
        ]);
    }

}
<?php namespace Laravel\Socialite\Two;

use Symfony\Component\HttpFoundation\RedirectResponse;

class FitbitProvider extends AbstractProvider implements ProviderInterface {

	/**
	 * The scopes being requested.
	 *
	 * @var array
	 */

	protected $scopes = [
        'profile'
    ];

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

	/*
	 * {@inheritdoc}
	 */
	protected function getUserByToken($token)
	{
		$response = $this->getHttpClient()->get('https://api.fitbit.com/1/user/-/profile.json', [
			'headers' => [
				'Accept' => 'application/json',
				'authorization' => 'Bearer ' . $token

			],
		]);

		return json_decode($response->getBody(), true);
	}

	public function getFitnessActivities($token, $options=array())
	{

//		$paramsString = http_build_query($options);
//		$endPoint = 'https://www.strava.com/api/v3/activities?'.$paramsString;
//		$response = $this->getHttpClient()->get($endPoint, [
//			'headers' => [
//				'Accept' => 'application/json',
//				'authorization' => 'Bearer ' . $token
//
//			],
//		]);
//
//		return json_decode($response->getBody());

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

    public function urlUserActivities($userId, $dateStr)
    {
        return 'https://api.fitbit.com/1/user/'. $userId .'/activities/date/' . $dateStr.'.json';
    }

	/**
	 * Get the POST fields for the token request.
	 *
	 * @param  string  $code
	 * @return array
	 */
	protected function getTokenFields($code)
	{
		return array_add(
			parent::getTokenFields($code), 'grant_type', 'authorization_code'
  	);
	}

	/**
	 * {@inheritdoc}
	 */
	protected function mapUserToObject(array $user)
	{
		return (new User)->setRaw($user)->map([
			'id' => $user['encodedId'],
			'nickname' => isset($user['displayName']) ? $user['displayName'] : '',
			'name' => isset($user['fullName']) ? $user['fullName'] : '',
			'email' => isset($user['email']) ? $user['email'] : '',
			'avatar' => isset($user['avatar150']) ? $user['avatar150'] : '',

		]);
	}

}
<?php namespace Laravel\Socialite\Two;

use Symfony\Component\HttpFoundation\RedirectResponse;

class MapmyfitnessProvider extends AbstractProvider implements ProviderInterface {

	/**
	 * The scopes being requested.
	 *
	 * @var array
	 */
	protected $scopes = [];

	/**
	 * {@inheritdoc}
	 */
	protected function getAuthUrl($state)
	{
		return $this->buildAuthUrlFromBase('https://www.mapmyfitness.com/v7.0/oauth2/authorize', $state);
	}

	/**
	 * {@inheritdoc}
	 */
	protected function getTokenUrl()
	{
		return 'https://oauth2-api.mapmyapi.com/v7.0/oauth2/access_token';
	}

	/**
	 * Get the access token for the given code.
	 *
	 * @param  string  $code
	 * @return string
	 */
	public function getAccessToken($code)
	{

	  $headers = ['Api-Key' => $this->clientId, 'Accept' => 'application/json'];
	  $tokenData = $this->getTokenFields($code);

	  $response = $this->getHttpClient()->post($this->getTokenUrl(), [
	  	'headers' => $headers,
	  	'body' => $tokenData
	  	]);


		return $this->parseAccessToken($response->getBody());
	}

	public function refreshToken($code)
  {

	  $headers = ['Api-Key' => $this->clientId, 'Accept' => 'application/json'];
		$response = $this->getHttpClient()->post($this->getTokenUrl(), [
			'headers' => $headers,
			'body' => $this->getRefreshTokenFields($code),
		]);

		return json_decode($response->getBody());
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
	protected function getUserByToken($token)
	{

		$response = $this->getHttpClient()->get('https://oauth2-api.mapmyapi.com/v7.0/user/self/', [
			'headers' => [
				'Api-Key' => $this->clientId,
				'authorization' => 'Bearer ' . $token
			],
		]);


		return json_decode($response->getBody(), true);
	}


	public function getFitnessActivities($token, $options=array())
	{

		$headers = [
				'Api-Key' => $this->clientId,
				'authorization' => 'Bearer ' . $token,
				'Content-Type' => 'application/x-www-form-urlencoded'
			];
		$paramsString = http_build_query($options);
		$response = $this->getHttpClient()->get('https://oauth2-api.mapmyapi.com/v7.0/workout/?'.$paramsString, [
			'headers' => $headers
		]);

		return json_decode($response->getBody());

	}

	public function getActigraphy($token, $options=array())
	{

		$headers = [
				'Api-Key' => $this->clientId,
				'authorization' => 'Bearer ' . $token,
				'Content-Type' => 'application/x-www-form-urlencoded'
			];
		$paramsString = http_build_query($options);
		$response = $this->getHttpClient()->get('https://oauth2-api.mapmyapi.com/api/0.1/actigraphy/?'.$paramsString, [
			'headers' => $headers
		]);

		return json_decode($response->getBody());

	}


	public function getActivityType($token, $options=array())
	{
		$headers = [
				'Api-Key' => $this->clientId,
				'authorization' => 'Bearer ' . $token,
				'Content-Type' => 'application/x-www-form-urlencoded'
			];

		$paramsString = http_build_query($options);

		$activityId = isset($options['activityId']) ? $options['activityId'] : '';
		$response = $this->getHttpClient()->get('https://oauth2-api.mapmyapi.com/v7.0/activity_type/'.$activityId, [
			'headers' => $headers
		]);

		return json_decode($response->getBody());


	}

	/**
	 * {@inheritdoc}
	 */
	protected function mapUserToObject(array $user)
	{

		return (new User)->setRaw($user)->map([
			'id' => isset($user['id']) ? $user['id'] : null,
			'nickname' => isset($user['display_name']) ? $user['display_name'] : null,
			'name' => isset($user['first_name']) && isset($user['last_name']) ? $user['first_name'] . ' ' . $user['last_name'] : null,
			'email' => isset($user['email']) ? $user['email'] : null,
			'avatar' => ''

		]);
	}

}
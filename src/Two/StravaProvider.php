<?php namespace Laravel\Socialite\Two;

use Symfony\Component\HttpFoundation\RedirectResponse;

class StravaProvider extends AbstractProvider implements ProviderInterface {

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
		return $this->buildAuthUrlFromBase('https://www.strava.com/oauth/authorize', $state);
	}

	/**
	 * {@inheritdoc}
	 */
	protected function getTokenUrl()
	{
		return 'https://www.strava.com/oauth/token';
	}

	/*
	 * {@inheritdoc}
	 */
	protected function getUserByToken($token)
	{
		$response = $this->getHttpClient()->get('https://www.strava.com/api/v3/athlete', [
			'headers' => [
				'Accept' => 'application/json',
				'authorization' => 'Bearer ' . $token

			],
		]);

		return json_decode($response->getBody(), true);
	}

	public function getFitnessActivities($token, $options=array())
	{

		$paramsString = http_build_query($options);
		$endPoint = 'https://www.strava.com/api/v3/activities?'.$paramsString;
		$response = $this->getHttpClient()->get($endPoint, [
			'headers' => [
				'Accept' => 'application/json',
				'authorization' => 'Bearer ' . $token

			],
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
	protected function mapUserToObject(array $user)
	{
		return (new User)->setRaw($user)->map([
			'id' => $user['id'], 
			'nickname' => null, 
			'name' => $user['firstname'].' '.$user['lastname'],
			'email' => $user['email'], 
			'avatar' => $user['profile'],

		]);
	}

}
<?php namespace Laravel\Socialite\Two;

use Symfony\Component\HttpFoundation\RedirectResponse;

class GoogleProvider extends AbstractProvider implements ProviderInterface {

	/**
	 * The scopes being requested.
	 *
	 * @var array
	 */
	protected $scopes = [
		//'https://www.googleapis.com/auth/userinfo.email',
		'https://www.googleapis.com/auth/userinfo.profile',
	  'https://www.googleapis.com/auth/fitness.activity.read'
	];

	/**
	 * {@inheritdoc}
	 */
	protected function getAuthUrl($state)
	{
		return $this->buildAuthUrlFromBase('https://accounts.google.com/o/oauth2/auth', $state);
	}

	/**
	 * Format the given scopes.
	 *
	 * @param  array  $scopes
	 * @return string
	 */
	protected function formatScopes(array $scopes)
	{
		return implode(' ', $scopes);
	}

	/**
	 * {@inheritdoc}
	 */
	protected function getTokenUrl()
	{
		return 'https://accounts.google.com/o/oauth2/token';
	}

	/**
	 * Get the access token for the given code.
	 *
	 * @param  string  $code
	 * @return string
	 */
	public function getAccessToken($code)
	{
		$response = $this->getHttpClient()->post($this->getTokenUrl(), [
			'body' => $this->getTokenFields($code),
		]);

		return $this->parseAccessToken($response->getBody());
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
		$response = $this->getHttpClient()->get('https://www.googleapis.com/oauth2/v1/userinfo?alt=json&access_token='.$token, [
			'headers' => [
				'Accept' => 'application/json',
			],
		]);

		return json_decode($response->getBody(), true);
	}

	public function getFitnessDataSources($token)
	{
		$response = $this->getHttpClient()->get('https://www.googleapis.com/fitness/v1/users/me/dataSources?access_token='.$token, [
			'headers' => [
				'Accept' => 'application/json',
			],
		]);

		return json_decode($response->getBody());
	}

	public function getFitnessDatasets($token, $dataSourceId, $datasetId)
	{
		$response = $this->getHttpClient()->get('https://www.googleapis.com/fitness/v1/users/me/dataSources/'.$dataSourceId.'/datasets/'.$datasetId.'?access_token='.$token, [
			'headers' => [
				'Accept' => 'application/json',
			],
		]);

		return json_decode($response->getBody());
	}


	/**
	 * {@inheritdoc}
	 */
	protected function mapUserToObject(array $user)
	{
		return (new User)->setRaw($user)->map([
			'id' => $user['id'], 'nickname' => null, 'name' => $user['given_name'].' '.$user['family_name'],
			'email' => isset($user['email']) ? $user['email'] : '', 'avatar' => array_get($user, 'picture'),
		]);
	}

}
<?php namespace Laravel\Socialite\Two;

use Symfony\Component\HttpFoundation\RedirectResponse;

class StravaProvider extends AbstractProvider implements ProviderInterface {

	/**
	 * The scopes being requested.
	 *
	 * @var array
	 */
	protected $scopes = ['email'];

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

	/**
	 * Get the access token for the given code.
	 *
	 * @param  string  $code
	 * @return string
	 */
	public function getAccessToken($code)
	{
		$response = $this->getHttpClient()->get($this->getTokenUrl(), [
			'query' => $this->getTokenFields($code),
		]);

		return $this->parseAccessToken($response->getBody());
	}

	/**
	 * {@inheritdoc}
	 */
	protected function parseAccessToken($body)
	{
		parse_str($body);

		return $access_token;
	}

	/**
	 * {@inheritdoc}
	 */
	protected function getUserByToken($token)
	{
		$response = $this->getHttpClient()->get('https://www.strava.com/api/v3?access_token='.$token, [
			'headers' => [
				'Accept' => 'application/json',
			],
		]);

		return json_decode($response->getBody(), true);
	}

	/**
	 * {@inheritdoc}
	 */
	protected function mapUserToObject(array $user)
	{
		return (new User)->setRaw($user)->map([
			'id' => $user['id'], 'nickname' => null, 'name' => $user['first_name'].' '.$user['last_name'],
			'email' => $user['email'], 'avatar' => 'https://graph.facebook.com/'.$user['id'].'/picture?type=normal',
		]);
	}

}
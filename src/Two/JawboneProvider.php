<?php namespace Laravel\Socialite\Two;

use Symfony\Component\HttpFoundation\RedirectResponse;


class JawboneProvider extends AbstractProvider implements ProviderInterface {

	/**
	 * The scopes being requested.
	 *
	 * @var array
	 */
	protected $scopes = ['basic_read', 'extended_read', 'move_read'];

	/**
	 * {@inheritdoc}
	 */
	protected function getAuthUrl($state)
	{
		return $this->buildAuthUrlFromBase('https://jawbone.com/auth/oauth2/auth', $state);
	}

	/**
	 * {@inheritdoc}
	 */
	protected function getTokenUrl()
	{
		return 'https://jawbone.com/auth/oauth2/token';
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

		$response = $this->getHttpClient()->get('https://jawbone.com/nudge/api/v.1.1/users/@me', [
			'headers' => [
				'Accept' => 'application/json',
				'Authorization' => 'Bearer ' . $token
			],
		]);

		return json_decode($response->getBody(), true);
	}

	/**
	 * {@inheritdoc}
	 */
	protected function mapUserToObject(array $user)
	{

		$meta = $user['meta'];
		$data = $user['data'];

		return (new User)->setRaw($user)->map([
			'id' => $meta['user_xid'], 
			'name' => $data['first'] . ' ' . $data['last'],
			'first' => $data['first'],
			'last' => $data['last'],
			'avatar' => $data['image'],

		]);
	}

}
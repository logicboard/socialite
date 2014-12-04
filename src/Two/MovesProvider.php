<?php namespace Laravel\Socialite\Two;

use Symfony\Component\HttpFoundation\RedirectResponse;

class MovesProvider extends AbstractProvider implements ProviderInterface {

	/**
	 * The scopes being requested.
	 *
	 * @var array
	 */
	protected $scopes = ['activity','location'];

	/**
	 * {@inheritdoc}
	 */
	protected function getAuthUrl($state)
	{
		return $this->buildAuthUrlFromBase('https://api.moves-app.com/oauth/v1/authorize', $state);
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
		return 'https://api.moves-app.com/oauth/v1/access_token';
	}

	/**
	 * {@inheritdoc}
	 */
	protected function getUserByToken($token)
	{
		$response = $this->getHttpClient()->get('https://api.moves-app.com/api/1.1/user/profile', [
			'headers' => [
				'Accept' => 'application/json',
				'authorization' => 'Bearer ' . $token
			],
		]);

		return json_decode($response->getBody(), true);
	}


	public function getSummaries($token, $options=array())
	{

		//$headers = [
				//'Content-Type' => 'application/x-www-form-urlencoded'
			//];

		$date = $options['date'];
		$endPoint = 'https://api.moves-app.com/api/1.1/user/summary/daily/'.$date.'?access_token='.$token;
		$response = $this->getHttpClient()->get($endPoint, [
			//'headers' => $headers
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
			'id' => $user['userId'], 
			'nickname' => null, 
			'name' => null,
			'email' => null,
			'avatar' => null,

		]);
	}

}
<?php namespace Laravel\Socialite\Two;

use Symfony\Component\HttpFoundation\RedirectResponse;

class RunkeeperProvider extends AbstractProvider implements ProviderInterface {

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
		return $this->buildAuthUrlFromBase('https://runkeeper.com/apps/authorize', $state);
	}

	/**
	 * {@inheritdoc}
	 */
	protected function getTokenUrl()
	{
		return 'https://runkeeper.com/apps/token';
	}



	/**
	 * {@inheritdoc}
	 */
	protected function getUserByToken($token)
	{
		// Provides the UserID along with other user endpoints
		// http://developer.runkeeper.com/healthgraph/users
		$userResponse = $this->getHttpClient()->get('https://api.runkeeper.com/user?access_token='.$token);

		// Access the user profile data including name, photos, etc
		// http://developer.runkeeper.com/healthgraph/profile
		$profileResponse = $this->getHttpClient()->get('https://api.runkeeper.com/profile?access_token='.$token);

		$userData = $userResponse->json();
		$profileData = $profileResponse->json();

		$data = ['user' => $userData, 'profile' => $profileData];

		return $data;
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
	protected function mapUserToObject(array $data)
	{

		$user = $data['user'];
		$profile = $data['profile'];

		$userId = array_get($user, 'userID');
		$name = array_get($profile, 'name');
		$avatar = array_get($profile, 'normal_picture');


		return (new User)->setRaw($data)->map([
			'id' => $userId,
			'name' => $name,
			'avatar' => $avatar
		]);
	}

}
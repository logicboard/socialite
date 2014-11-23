<?php namespace Laravel\Socialite\Two;

use Laravel\Socialite\AbstractUser;

class User extends AbstractUser {

	/**
	 * The user's access token.
	 *
	 * @var string
	 */
	public $token;

	/**
	 * The user's refresh token.
	 *
	 * @var string
	 */
	public $refreshToken;

	/**
	 * The user's refresh token.
	 *
	 * @var integer
	 */
	public $expiresIn;


	/**
	 * The user's raw access token response.
	 *
	 * @var array
	 */
	public $accessTokenResponse;

	/**
	 * Set the access token on the user.
	 *
	 * @param  string  $token
	 * @return $this
	 */
	public function setToken($token)
	{
		$this->token = $token;

		return $this;
	}

	/**
	 * Set the refresh token on the user.
	 *
	 * @param  string  $token
	 * @return $this
	 */
	public function setRefreshToken($token)
	{
		$this->refreshToken = $token;

		return $this;
	}

	/**
	 * Set the refresh token on the user.
	 *
	 * @param  integer $time
	 * @return $this
	 */
	public function setExpiresIn($time)
	{
		$this->expiresIn = $time;

		return $this;
	}

	/**
	 * Set the raw access token response which in addition to access_token, may include:
	 * refresh_token, expires_in
	 *
	 * @param  integer $time
	 * @return $this
	 */
	public function setAccessTokenResponse(array $data)
	{

		$this->accessTokenResponse = $data;

		$accessToken = $data['access_token'];
		$refreshToken = isset($data['refresh_token']) ? $data['refresh_token'] : null;
		$expiresIn = isset($data['expires_in']) ? $data['expires_in'] : null;

		$this->setToken($accessToken);
		$this->setRefreshToken($refreshToken);
		$this->setExpiresIn($expiresIn);

		return $this;
	}

}
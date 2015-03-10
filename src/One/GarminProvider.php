<?php namespace Laravel\Socialite\One;

class GarminProvider extends AbstractProvider implements ProviderInterface {

    
		public function createClientCredentials(array $clientCredentials)
    {
    	return $this->server->createCredentials($clientCredentials);
    }

    public function getUserActivities($tokenCredentials, $userId, $date)
    {
    	return $this->server->getUserActivities($tokenCredentials, $userId, $date);
    }

}
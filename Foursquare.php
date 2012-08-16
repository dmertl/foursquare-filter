<?php

class Foursquare {

	protected $accessToken;
	public $authenticatedUser;

	const CACHE_TTL = 3600;

	public function __construct() {
		if(!$access_token = $this->getAccessTokenFromSession()) {
			if(!$access_token = $this->getAccessTokenFromCookie()) {
				if(!$access_token = $this->getAccessTokenFromOauth()) {
					//Not authorized
				}
			}
		}
		if($access_token) {
			$this->setAccessToken($access_token);

			//Get user data and test access token
			if($user = $this->apiCall('users/self')) {
				$this->authenticatedUser = $user;
			} else {
				$this->setAccessToken(null);
			}
		}
	}

	public function isAuthenticated() {
		return $this->accessToken ? true : false;
	}

	protected function getAccessTokenFromSession() {
		if(isset($_SESSION['Foursquare_accessToken'])) {
			return $_SESSION['Foursquare_accessToken'];
		}
		return false;
	}

	protected function getAccessTokenFromCookie() {
		if(isset($_COOKIE['Foursquare_accessToken'])) {
			return $_COOKIE['Foursquare_accessToken'];
		}
		return false;
	}

	protected function getAccessTokenFromOauth() {
		if(!empty($_GET['code'])) {
			$code = $_GET['code'];
			return $this->newAccessTokenFromFoursquare($code);
		}
		return false;
	}

	function newAccessTokenFromFoursquare($code) {
		//Replace with curl
		$access_token_url = 'https://foursquare.com/oauth2/access_token?client_id=' . urlencode(Configure::read('Foursquare.ClientId')) .
				'&client_secret=' . urlencode(Configure::read('Foursquare.ClientSecret')) .
				'&grant_type=authorization_code&redirect_uri=' . urlencode(Configure::read('Foursquare.RedirectUrl')) .
				'&code=' . urlencode($code);
		$response = file_get_contents($access_token_url);
		if(!empty($response)) {
			if($response = json_decode($response)) {
				return $response->access_token;
			}
		}
		return false;
	}

	protected function setAccessToken($access_token) {
		$this->accessToken = $access_token;
		$_SESSION['Foursquare_accessToken'] = $access_token;
		setcookie('Foursquare_accessToken', $access_token, time() + 60 * 60 * 24 * 30);
	}

	public function getAccessToken() {
		return $this->accessToken;
	}

	public function apiCall($url_piece, $query_params=array()) {
		$cache_key = $this->getCacheKey($url_piece, $this->getAccessToken(), $query_params);
		if(!($response = $this->getCachedApiResponse($cache_key))) {
			$query_params['oauth_token'] = $this->getAccessToken();
			$query_params['v'] = Configure::read('Foursquare.ApiVersionDate');
			$url = 'https://api.foursquare.com/v2/' . $url_piece . '?' . http_build_query($query_params);
			if($response = file_get_contents($url)) {
				$this->cacheApiResponse($cache_key, $response);
			} else {
				return false;
			}
		}
		if($response = json_decode($response)) {
			return $response;
		}
		return false;
	}

	public function filterResponse($items, $filter) {
		$filtered = array();
		foreach($items as $item) {
			if($this->itemMatchesFilter($item, $filter)) {
				$filtered[] = $item;
			}
		}
		return $filtered;
	}

	/**
	 * @param object $item
	 * @param array $filter
	 * @return bool
	 */
	public function itemMatchesFilter($item, $filter) {
		foreach($filter as $key => $f) {
			if(is_array($f)) {
				if(isset($item->{$key})) {
					$next = $item->{$key};
					if(is_array($next)) {
						//Array of items, at least one must match
						$pass = false;
						foreach($next as $n) {
							if($this->itemMatchesFilter($n, $f)) {
								$pass = true;
							}
						}
						if(!$pass) {
							return false;
						}
					} else {
						//Single item, if it fails return false
						if(!$this->itemMatchesFilter($next, $f)) {
							return false;
						}
					}
				} else {
					//Item not set
					return false;
				}
			} else {
				//Check if item is equal to filter
				if(!isset($item->{$key}) || $item->{$key} != $f) {
					return false;
				}
			}
		}
		return true;
	}

	/**
	 * Gets the API response from cache if it is set
	 * @param string $key The cache key for the API call
	 * @return bool|string The API response string or false on failure
	 */
	public function getCachedApiResponse($key) {
		if(function_exists('apc_fetch')) {
			return apc_fetch($key);
		}
		return false;
	}

	/**
	 * Caches an API response string
	 * @param string $key The cache key for the API call
	 * @param string $value The API response string
	 * @return bool
	 */
	public function cacheApiResponse($key, $value) {
		if(function_exists('apc_store')) {
			return apc_store($key, $value, self::CACHE_TTL);
		}
		return false;
	}

	/**
	 * Returns the cache key for an API call
	 * @param string $url_piece The URL segment provided to apiCall that identifies the endpoint
	 * @param string $access_token The access token for the currently authenticated user
	 * @param array $query_params An array of query params for the api call (excluding version and auth token)
	 * @return string
	 */
	public function getCacheKey($url_piece, $access_token, $query_params=array()) {
		return $url_piece . $access_token . json_encode($query_params);
	}
}

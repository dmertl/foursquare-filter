<?php
session_start();

require_once('config.php');
require_once('functions.php');
require_once('Foursquare.php');

$foursquare = new Foursquare();

if($foursquare->isAuthenticated()) {
	$limit = isset($_GET['limit']) ? $_GET['limit'] : 250;
	$offset = isset($_GET['offset']) ? $_GET['offset'] : 0;
	if(isset($_GET['filter'])) {
		$filter = $_GET['filter'];
		if(ini_get('magic_quotes_gpc')) {
			$filter = stripslashes($filter);
		}
		if(!$filter = json_decode($filter)) {
			throw new Exception('Unable to decode filter');
		}
	} else {
		$filter = array();
	}
	$params = array('limit' => $limit, 'offset' => $offset);
	$checkins = $foursquare->apiCall('users/self/checkins', $params);
	if($checkins && count($checkins) > 0) {
		$checkins = filterCheckins($checkins, $foursquare, $filter);

		outputCheckinsHtml($checkins);
	} else {
		//TODO: better way to determine no more checkins
		echo 'false';
	}
}
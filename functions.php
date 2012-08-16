<?php

/**
 * @param $checkins
 * @param Foursquare $foursquare
 * @param array $filter
 * @return array
 */
function filterCheckins($checkins, Foursquare $foursquare, $filter) {
	return $foursquare->filterResponse($checkins->response->checkins->items, $filter);
}

/**
 * @param array $checkins
 */
function outputCheckinsHtml($checkins) {
	foreach($checkins as $checkin) {
		echo '<a href="https://foursquare.com/v/'.$checkin->venue->id.'">';
		echo '<strong>' . $checkin->venue->name . '</strong></a> - ';
		echo '<em>' . date('M j Y', $checkin->createdAt) . '</em>';
		echo '<br />';
	}
}

/**
 * @param array $array
 * @return array
 */
function removeEmptyValues($array) {
	foreach($array as $key => $value) {
		if(is_array($value)) {
			if($clean = removeEmptyValues($value)) {
				$array[$key] = $clean;
			} else {
				unset($array[$key]);
			}
		} elseif(empty($value)) {
			unset($array[$key]);
		}
	}
	return $array;
}
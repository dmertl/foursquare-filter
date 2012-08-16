<?php
session_start();

require_once('config.php');
require_once('functions.php');
require_once('Foursquare.php');

$foursquare = new Foursquare();

?>
<html>
<head>
	<title>Foursquare Bar Checkins</title>
	<script type="text/javascript" src="https://ajax.googleapis.com/ajax/libs/jquery/1.7.2/jquery.min.js"></script>
	<script type="text/javascript" src="spin.min.js"></script>
	<script type="text/javascript">
		var spinner = new Spinner({
			"radius": 5,
			"length": 4,
			"width": 2
		});
		var offset = 250;
		function onAjaxLoadCheckinComplete(data, status, jqxhr) {
			spinner.stop();
			offset += 250;
			//DEBUG max for testing
			if(offset > 2000) {
				return;
			}
			if(data !== 'false') {
				$('#spinner').before(data);
				spinner.spin($('#spinner').get(0));
				$.ajax('ajax.php?offset=' + offset + '&filter=' + encodeURIComponent(window.filter), {
					"success":onAjaxLoadCheckinComplete
				});
			}
		}
	</script>
	<style type="text/css">
		body {
			font-family: 'Trebuchet MS', sans-serif;
			font-size: 12px;
		}
		#checkins {
			width: 400px;
			border:solid 1px #CCC;
			border-radius: 5px;
			padding: 5px;
		}
		#spinner {
			height: 20px;
		}
	</style>
</head>
<body>
<form method="post" action="">
	<label for="venue.name">Venue Name</label>
	<input type="text" name="venue[name]" id="venue.name" />
	<label for="venue.id">Venue ID</label>
	<input type="text" name="venue[id]" id="venue.id" />
	<label for="venue.category.id">Venue Cateogry ID</label>
	<input type="text" name="venue[category][id]" id="venue.category.id" />
	<input type="submit" name="submit" value="Submit" />
</form>
<?php
if($foursquare->isAuthenticated()) {
	if(!empty($_POST)) {
		unset($_POST['submit']);
		$filter = removeEmptyValues($_POST);
		?>
			<script type="text/javascript">
				window.filter = '<?php echo json_encode($filter); ?>';

				$(document).ready(function () {
					spinner.spin($('#spinner').get(0));

					$.ajax('ajax.php?offset=' + offset + '&filter=' + encodeURIComponent(window.filter), {
						"success":onAjaxLoadCheckinComplete
					});
				});
			</script>
		<?php
		$user = $foursquare->authenticatedUser;
		$user = $user->response->user;
		$params = array(
			'limit' => 250
		);
		$checkins = $foursquare->apiCall('users/self/checkins', $params);
		$checkins = filterCheckins($checkins, $foursquare, $filter);

		echo '<pre>';
		print_r($filter);
		echo '</pre>';
		echo 'for ' . $user->firstName . ' ' . $user->lastName . '<br />';
		echo '<br />';
		echo '<div id="checkins">';
		outputCheckinsHtml($checkins);
		echo '<div id="spinner"></div>';
		echo '</div>';
	}
} else {
	$url = 'https://foursquare.com/oauth2/authenticate?client_id=' . urlencode(Configure::read('Foursquare.ClientId')) . '&response_type=code&redirect_uri=' . urlencode(Configure::read('Foursquare.RedirectUrl'));
	echo '<a href="'.$url.'">Authorize Foursquare</a>';
}
?>
</body>
</html>

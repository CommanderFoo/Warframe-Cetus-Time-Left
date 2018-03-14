<?php

// Cetus time is the same for console and PC as far as I know.  But just in case, I am
// loading the world state for PS4.

$content = file_get_contents("http://content.ps4.warframe.com/dynamic/worldState.php");

// Used for switching between sun and moon image.
// 1 = moon, 2 = sun;

$img = "1";

// Wrote out to the page and sent as a response via AJAX.
// This is the time remaining of day or night, not the actual time on Cetus.

$cetus_time = "---";

// And here we go through the JSON to and work out the times.

if($content && strlen($content) > 1){
	$json = json_decode($content);
	
	if($json != null){
		$missions = $json -> SyndicateMissions;
		
		if($missions != null && count($missions) > 0){
			$cetus = null;
		
			foreach($missions as $mission){
				if($mission -> Tag == "CetusSyndicate"){
					$cetus = $mission;
					break;
				}
			}		
			
			if($cetus != null){
				//$activation = $cetus -> Activation -> {"\$date"} -> {"\$numberLong"} / 1000;
				$expiry = $cetus -> Expiry -> {"\$date"} -> {"\$numberLong"} / 1000;
				
				// No automatic timezone checking is done.
				// Not sure it really matters, as we are only concerned with
				// time left for day and night, not what the actual time is.
				// If you want to improve this, then send send the timestamps to
				// the client so JavaScript can handle it.
				
				$offset = (3600 * 4);
				
				$date1 = new DateTime();
				$date1 -> setTimestamp($expiry + $offset);
				
				$date2 = new DateTime();
				$date2 -> setTimestamp($expiry - (50 * 60) + $offset); // Night time lasts 50mins
				
				$date3 = new DateTime();
				$date3 -> setTimestamp(time() + $offset);
				
				$diff = $date1 -> diff($date3);
				
				if($date3 -> getTimestamp() <= $date2 -> getTimestamp()){
					$diff = $date2 -> diff($date3);
					$img = "2";
				}
				
				$hour = "";
				$mins = "";
				$seconds = "";
				
				if($diff -> h > 0){
					$hour = $diff -> h . "h ";
				}
				
				if($diff -> i > 0){
					$mins = (($diff -> i < 10)? "0" : "") . $diff -> i . "m ";
				}
				
				if($diff -> s > 0){
					$seconds = (($diff -> s < 10)? "0" : "") . $diff -> s . "s";
				}
				
				$cetus_time = $hour . $mins . $seconds;
			}
		}
	}
}

// Used if this is an AJAX request.

if(isset($_GET["c"])){
	echo json_encode(array(
	
		"time" => $cetus_time,
		"img" => $img
		
	));
		
	exit();
}

?>

<!doctype html>
<html>
	<head>
		<meta charset="utf-8">
		<title>Plains of Eidolon: <?php echo $cetus_time; ?></title>
		<style>
		
		body {
		
			background-color: #000000;
			color: white;
			margin: 0;
			background-image: url("<?php echo (($img == 1)? "moon" : "sun") . ".png"; ?>");
			background-repeat: no-repeat;
			background-position: center center;
			
		}
		
		.container {
		
			display: flex;
			justify-content: center;
			align-items: center;
			width: 100%;
			height: 100vh;
			font-size: 80px;
			box-sizing: border-box;
			opacity: .98;
			
		}
		
		#cetus-time {
		
			background-color: #333333;
			padding: 40px;
			border-radius: 25px;
			
		}
		
		</style>
	</head>
	<body>
		<div class="container">
			<div id="cetus-time">
				<?php
				
				echo $cetus_time;
				
				?>
				
			</div>	
		</div>
		
		<script>
		
		let ct = document.getElementById("cetus-time");
		let body = document.getElementsByTagName("body").item(0);
		let current_img = <?php echo $img; ?>;
		
		// Refreshes every 30 seconds.
		
		setInterval(() => {
			fetch(location.pathname + "?c").then((r) => {
				r.json().then(data => {
					ct.textContent = data.time;
					
					document.title = "Plains of Eidolon: " + data.time;
										
					if(current_img != data.img){
						body.style.backgroundImage = "url('" + ((data.img == 1)? "moon" : "sun") + ".png')";
						current_img = data.img;
					}
				});
			});
		}, 30000);
		
		</script>
	</body>
</html>
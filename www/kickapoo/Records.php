<html>
	<head>
		<title>XC Mile Database</title>
		
		<?php 
			include_once("analyticstracking.php");
			include_once("check_login.php");
		?>
		<link rel="icon" href="logo.png">
		<link rel="stylesheet" type="text/css" href="Baseline.css">
		<link rel="stylesheet" type="text/css" media='screen and (min-width: 1367px)'  href="NormalScreen.css">
		<link rel="stylesheet" type="text/css" media='screen and (max-width: 1366px) and (min-width: 641px)'  href="SmallScreen.css">
		<link rel="stylesheet" type="text/css" media='screen and (max-width: 640px)'  href="Mobile.css">
		<script src='styleJS.js'></script>
		<meta name="viewport" content="width=device-width,initial-scale=1.0">
	</head>
<body onload="myOnload();" onresize="myOnload();">
<div id='topBar'>
<a href="/"><img src="logo.png" id='home'></img></a>
<h1 id='title' >Records</h1>
<p class="topLinkHolder"><a class="Link" href="MileChart.php">View Miles</a></p>
<p class="bottomLinkHolder"><a class="Link" href="MileEntry.php">Enter Miles</a></p>
</div>
<?php
	ob_start(NULL, 0, PHP_OUTPUT_HANDLER_REMOVABLE);
?>
<div id='bigContainer'>
<div style="position:absolute; bottom: 0px; height: 10px; width: 10px;" onclick="document.getElementById('poll').style.display='table-row-group';"></div>
<?php
	if (session_status() !== PHP_SESSION_ACTIVE) {
		session_start();
	}
	date_default_timezone_set("America/Chicago");
	$link = mysqli_connect($_SERVER['RDS_HOSTNAME'], $_SERVER['RDS_USERNAME'], $_SERVER['RDS_PASSWORD'], $_SERVER['RDS_DB_NAME'], $_SERVER['RDS_PORT']);
	
	echo "<table class='recordsTable' id='table'><tbody><tr><th class='recordsTableCategory'>Category</th><th class='recordsTableRecord'>Record</th><th>Holder</th><th>Date</th></tr>";
	
	
	// Fastest 5k
	$races = ['5K', '3K', '3200M', '1600M', '800M'];
	foreach ($races as $distance) {
		$query="SHOW TABLES";
		$result = mysqli_query($link, $query);
		$max = INF;
		$name = "";
		$day = "";
		$race = "";
		while ($row = mysqli_fetch_array($result)) {
			if ($row[0] !== "users") {
				$query="SELECT * FROM " . $row[0];
				$result2 = mysqli_query($link, $query);
				if ($result2) {
					while ($row2 = mysqli_fetch_array($result2)) {
						for ($x = 1; $x < count($row2) / 2; $x++) {
							if ($row2[$x] !== "") {
								// Fix previous data
								if ($row2[$x] !== "") {
									if (count(explode('_', $row2[$x])) <= 4) {
										$fields = mysqli_fetch_fields($result2);
										mysqli_query($link, "UPDATE `$row[0]` SET " . $fields[$x]->name . "='$row2[$x]" . '___' . "' WHERE start_date='$row2[0]'");
									}
									if (count(explode('_', $row2[$x])) <= 6) {
										$fields = mysqli_fetch_fields($result2);
										mysqli_query($link, "UPDATE `$row[0]` SET " . $fields[$x]->name . "='$row2[$x]" . '_' . "' WHERE start_date='$row2[0]'");
									}
								}
								// End data correcter
								if (explode('_', $row2[$x])[5] === $distance) {
									if(strtotime(explode('_', $row2[$x])[6]) < $max && strtotime("0:0:00.00") < strtotime(explode('_', $row2[$x])[6])) {
										$name = $row[0];
										$time = explode('_', $row2[$x])[6];
										$max = strtotime(explode('_', $row2[$x])[6]);
										$day = ceil($x / 2);
										$race = explode('_', $row2[$x])[4];
										$day -= 1;
										$day = strtotime($row2[0]) + ($day * 24 * 60 * 60);
									}
								}
							}
						}
					}
				}
			}
		}
		if ($name !== "") {
			$query="SELECT Full_name FROM users WHERE Tbl_name = '" . $name . "'";
			$name = mysqli_fetch_object(mysqli_query($link, $query))->Full_name;
			$time = explode(':', $time);
			if ($time[0] == "0") {
				unset($time[0]);
			}
			echo "<tr><td>Fastest $distance</td><td>" . implode(":", $time) . " at " . $race . "</td><td>" . $name . "</td><td>" . date("m/d/Y", $day) . "</td></tr>";
		}
	}
	
	// Most miles logged
	$query="SHOW TABLES";
	$result = mysqli_query($link, $query);
	$max = -1;
	$name = "";
	$day = "";
	$dayOne = "";
	while ($row = mysqli_fetch_array($result)) {
		if ($row[0] !== "users") {
			$query="SELECT * FROM " . $row[0] . " ORDER BY start_date ASC";
			$result2 = mysqli_query($link, $query);
			$total = 0;
			$first = "";
			while ($row2 = mysqli_fetch_array($result2)) {
				for ($x = 1; $x < count($row2) / 2; $x++) {
					if ($row2[$x] !== "") {
						$total += explode('_', $row2[$x])[0];
						$today = ceil($x / 2);
						$today -= 1;
						$today = strtotime($row2[0]) + ($today * 24 * 60 * 60);
						if ($first === "") {
							$first = $today;
						}
					}
				}
			}
			if ($total > $max) {
				$name = $row[0];
				$max = $total;
				$day = $today;
				$dayOne = $first;
			}
		}
	}
	if ($name !== "") {
		$query="SELECT Full_name FROM users WHERE Tbl_name = '" . $name . "'";
		$name = mysqli_fetch_object(mysqli_query($link, $query))->Full_name;
		echo "<tr><td>Most miles logged</td><td>" . $max . " miles</td><td>" . $name . "</td><td>" . date("m/d/Y", $dayOne) . ' - ' . date("m/d/Y", $day) . "</td></tr>";
	}
	
	// Most miles in one week
	$query="SHOW TABLES";
	$result = mysqli_query($link, $query);
	$max = -1;
	$name = "";
	$day = "";
	$dayOne = "";
	while ($row = mysqli_fetch_array($result)) {
		if ($row[0] !== "users") {
			$query="SELECT * FROM " . $row[0] . " ORDER BY start_date ASC";
			$result2 = mysqli_query($link, $query);
			while ($row2 = mysqli_fetch_array($result2)) {
				$total = 0;
				$first = "";
				for ($x = 1; $x < count($row2) / 2; $x++) {
					if ($row2[$x] !== "") {
						$total += explode('_', $row2[$x])[0];
						$today = ceil($x / 2);
						$today -= 1;
						$today = strtotime($row2[0]) + ($today * 24 * 60 * 60);
						if ($first === "") {
							$first = $today;
						}
					}
				}
				if ($total > $max) {
					$name = $row[0];
					$max = $total;
					$day = $today;
					$dayOne = $first;
				}
			}
		}
	}
	if ($name !== "") {
		$query="SELECT Full_name FROM users WHERE Tbl_name = '" . $name . "'";
		$name = mysqli_fetch_object(mysqli_query($link, $query))->Full_name;
		echo "<tr><td>Most miles in one week</td><td>" . $max . " miles</td><td>" . $name . "</td><td>" . date("m/d/Y", $dayOne) . ' - ' . date("m/d/Y", $day) . "</td></tr>";
	}
	
	
	// Most miles on a pair of shoes
	$query="SHOW TABLES";
	$result = mysqli_query($link, $query);
	$max = -1;
	$shoe = "";
	$name = "";
	$day = "";
	$dayOne = "";
	while ($row = mysqli_fetch_array($result)) {
		if ($row[0] !== "users") {
			$shoes = [];
			$miles = [];
			$days = [];
			$first = [];
			$y = 0;
			$query="SELECT * FROM " . $row[0] . " ORDER BY start_date ASC";
			$result2 = mysqli_query($link, $query);
			while ($row2 = mysqli_fetch_array($result2)) {
				for ($x = 1; $x < count($row2) / 2; $x++) {
					if ($row2[$x] !== "") {
						$piece = explode('_', $row2[$x]);
						if (array_search(explode('-', $piece[2])[0], $shoes) === FALSE) {
							$shoes[] = explode('-', $piece[2])[0];
							$miles[] = $piece[0];
							$today = ceil($x / 2);
							$today -= 1;
							$today = strtotime($row2[0]) + ($today * 24 * 60 * 60);
							$days[] = $today;
							if (!isset($first[$y])) {
								$first[] = $today;
								$y += 1;
							}
						} else {
							$miles[array_search(explode('-', explode('_', $row2[$x])[2])[0], $shoes)] += explode('_', $row2[$x])[0];
							$today = ceil($x / 2);
							$today -= 1;
							$today = strtotime($row2[0]) + ($today * 24 * 60 * 60);
							$days[array_search(explode('-', explode('_', $row2[$x])[2])[0], $shoes)] = $today;
							if (!isset($first[$y])) {
								$first[] = $today;
								$y += 1;
							}
						}
					}
				}
			}
			if (!empty($miles)) {
				$key = array_search(max($miles), $miles);
				if ($miles[$key] > $max) {
					$name = $row[0];
					$shoe = $shoes[$key];
					$max = $miles[$key];
					$day = $days[$key];
					$dayOne = $first[$key];
				}
			}
		}
	}
	if ($name !== "") {
		$query="SELECT Full_name FROM users WHERE Tbl_name = '" . $name . "'";
		$name = mysqli_fetch_object(mysqli_query($link, $query))->Full_name;
		echo "<tr><td>Most miles on a pair of shoes</td><td>" . $shoe . " - " . $max . " miles</td><td>" . $name . "</td><td>" . date("m/d/Y", $dayOne) . ' - ' . date("m/d/Y", $day) . "</td></tr>";
	}
	
?>
</tbody>
<tbody id='poll' style='display:none'>
	<?php
	// Poll records
	global $polls;
	foreach ($polls as $poll) {
		$votes = [];
		$values = [];
		$query="SHOW TABLES";
		$result = mysqli_query($link, $query);
		while ($row = mysqli_fetch_array($result)) {
			if ($row[0] !== "users") {
				$query="SELECT * FROM `" . $row[0] . "` ORDER BY start_date ASC";
				$result2 = mysqli_query($link, $query);
				if (!$result2) {
					error_log($mysqli_error($link));
				}
				else {
					while ($row2 = mysqli_fetch_array($result2)) {
						for ($x = 1; $x < count($row2) / 2; $x++) {
							if ($row2[$x] !== "") {
								$piece = explode('_', $row2[$x]);
							
								if (isset($piece[7]) && $piece[7] === $poll) {
									if (array_search($piece[8], $votes) === FALSE) {
										$votes[] = $piece[8];
										$values[] = floor($piece[0]);
									} else {
										$values[array_search($piece[8], $votes)] += floor($piece[0]);
									}
								}
							}
						}
					}
				}
			}
		}
		if (!empty($values)) {
			$key = array_search(max($values), $values);
			echo "<tr><td>" . $poll . "</td><td>" . $values[$key] . " votes</td><td>" . $votes[$key] . "</td><td>" . date("m/d/Y") . "</td></tr>";
		}
	}
	?>
</tbody>
</table>
<?php
	if(!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] === FALSE) {
		ob_end_clean();
	} else {
		ob_end_flush();
	}
	mysqli_close($link);
?>
<span id="copyright">Copyright Josh Lawson 2017-<?php echo date('Y', time());?></span>
</div>
<?php
	include_once("ads.php");
?>
</body>
</html>
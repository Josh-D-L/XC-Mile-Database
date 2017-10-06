<html>
	<head>
		<title>XC Mile Database</title>
		<?php include_once("analyticstracking.php") ?>
		<?php include_once("check_login.php") ?>
		<link rel="icon" href="logo.png">
		<link rel="stylesheet" type="text/css" href="Baseline.css">
		<link rel="stylesheet" type="text/css" media='screen and (min-width: 1367px)'  href="NormalScreen.css">
		<link rel="stylesheet" type="text/css" media='screen and (max-width: 1366px) and (min-width: 641px)'  href="SmallScreen.css">
		<link rel="stylesheet" type="text/css" media='screen and (max-width: 640px)'  href="Mobile.css">
		<script src='styleJS.js'></script>
		<meta name="viewport" content="width=device-width,initial-scale=1.0">
		<script>
		function thisOnload() {
			myOnload();
			if (document.getElementById('table')) {
				if (document.getElementById('table').offsetHeight + 3 > window.innerHeight - home - 2) {
					document.getElementById('bigContainer').style.height = document.getElementById('table').offsetHeight + 3;
				}
			}
		}
		</script>
	</head>
<body onload="thisOnload();" onresize="thisOnload();">
</div>
<div id='topBar' class="mileTable">
<a href="/"><img src="logo.png" id='home'></img></a>
<h1 id='title' class="mileChart">
	<form action="MileChart.php" method="post"><select onchange="this.form.submit();" id="mileChartSelect" name="person">
	<?php
	$link = mysqli_connect($_SERVER['RDS_HOSTNAME'], $_SERVER['RDS_USERNAME'], $_SERVER['RDS_PASSWORD'], $_SERVER['RDS_DB_NAME'], $_SERVER['RDS_PORT']);
	
	if(isset($_SESSION['logged_in'])) {
		
		if (isset($_POST['person']) && $_POST['person'] !== "This Week") {
			$person = $_POST['person'];
			$query = "SELECT Full_name FROM `users` WHERE Tbl_name = '" . $_POST['person'] . "'";
			$name = mysqli_fetch_object(mysqli_query($link, $query))->Full_name;
			echo '<option selected="selected" value="' . $person . '">' . $name . '</option>';
			echo '<option value="This Week">This Week</option>';
		}
		else {
			//$person = $_SESSION['table'];
			//$name = $_SESSION['name'];
			$person = "This Week";
			$name = "This Week";
			echo '<option selected="selected" value="' . $person . '">' . $name . '</option>';
		}
		if ($name != $_SESSION['name']) {
			echo '<option value="' . $_SESSION['table'] . '">' . $_SESSION['name'] . '</option>';
		}
		$query="SELECT * FROM `users` ORDER BY Full_name ASC";
		$result = mysqli_query($link, $query);
		while ($row = mysqli_fetch_array($result)) {
			if ($row[0] != $name && $row[0] != $_SESSION['name']) {
				echo '<option value="' . $row[3] . '">' . $row[0] . '</option>';
			}
		}
		
	}
	?>
	</select></form></h1>
<p class="topLinkHolder"><a class="Link" href="Records.php">All-Time Records</a></p>
<p class="bottomLinkHolder"><a class="Link" href="MileEntry.php">Enter Miles</a></p>
</div>
<div id='bigContainer' class="mileTable">
<?php
	ob_start(NULL, 0, PHP_OUTPUT_HANDLER_REMOVABLE);
?>
<div>
<?php
	$link = mysqli_connect($_SERVER['RDS_HOSTNAME'], $_SERVER['RDS_USERNAME'], $_SERVER['RDS_PASSWORD'], $_SERVER['RDS_DB_NAME'], $_SERVER['RDS_PORT']);
	date_default_timezone_set("America/Chicago");
	
	if (isset($_POST['person']) && $_POST['person'] !== "This Week") {
		$person = $_POST['person'];
		$query = "SELECT Full_name FROM `users` WHERE Tbl_name = '" . $_POST['person'] . "'";
		$name = mysqli_fetch_object(mysqli_query($link, $query))->Full_name;
	}
	else {
		$person = "This Week";
		$name = "This Week";
	}
	
	if ($person === "This Week") {
		echo "<table id='table' class='mileTable'><tr><th id='weekFirstCol'>Name:</th><th>Mon.<br>AM</th><th>Mon.<br>PM</th><th>Tue.<br>AM</th><th>Tue.<br>PM</th>
			<th>Wed.<br>AM</th><th>Wed.<br>PM</th><th>Thu.<br>AM</th><th>Thu.<br>PM</th><th>Fri.<br>AM</th><th>Fri.<br>PM</th>
			<th>Sat.<br>AM</th><th>Sat.<br>PM</th><th>Sun.<br>AM</th><th>Sun.<br>PM</th><th>Total</th></tr>";
			
		date_default_timezone_set("America/Chicago");
		$day = date("l", time());
		$start = date("w", time());
		if ($start == 0) {
			$start = 7;
		}
		$start -= 1;
		$start = time() - ($start * 24 * 60 * 60);
		$start = date("Y-m-d", $start);	
			
		$query="SHOW TABLES";
		$result = mysqli_query($link, $query);
		while ($row = mysqli_fetch_array($result)) {
			if ($row[0] != 'users') {
				$query="SELECT * FROM `" . $row[0] . "` WHERE start_date = '" . $start . "'";
				$data = mysqli_query($link, $query);
				if (mysqli_num_rows ($data) !== 0) {
					$row2 = mysqli_fetch_array($data);
					$query="SELECT Full_name FROM `users` WHERE Tbl_name = '" . $row[0] . "'";
					$result2 = mysqli_fetch_object(mysqli_query($link, $query))->Full_name;
					echo "<tr><td>" . $result2 . "</td>";
					$total = 0;
					for ($x = 1; $x < count($row2) / 2; $x++) {
						// Fix previous data
						if ($row2[$x] !== "") {
							if (count(explode('_', $row2[$x])) <= 4) {
								$fields = mysqli_fetch_fields($data);
								mysqli_query($link, "UPDATE `$row[0]` SET " . $fields[$x]->name . "='$row2[$x]" . '___' . "' WHERE start_date='$start'");
							}
							if (count(explode('_', $row2[$x])) <= 6) {
								$fields = mysqli_fetch_fields($data);
								mysqli_query($link, "UPDATE `$row[0]` SET " . $fields[$x]->name . "='$row2[$x]" . '_' . "' WHERE start_date='$start'");
							}
							if (count(explode('_', $row2[$x])) <= 7) {
								$fields = mysqli_fetch_fields($data);
								mysqli_query($link, "UPDATE `$row[0]` SET " . $fields[$x]->name . "='$row2[$x]" . '__' . "' WHERE start_date='$start'");
							}
						}
						// End data correcter
						if ($row2[$x] !== "") {
							if (explode('_', $row2[$x])[4] !== "") {
								echo "<td class='toHover racecontainer'>" . (float)explode('_', $row2[$x])[0] . "<div class='hidden'>";
								$time = explode(':', explode('_', $row2[$x])[6]);
								if ((float)$time[0] === (float)0 || $time[0] === "") {
									array_shift($time);
								}
								echo "<span class='raceday'>Race Day!<br>" . explode('_', $row2[$x])[4] . "<br>" . explode('_', $row2[$x])[5] . "<br>" . implode(":", $time) . "<br></span>";
							}
							else {
								echo "<td class='toHover'>" . (float)explode('_', $row2[$x])[0] . "<div class='hidden'>";
								if (explode('_', $row2[$x])[1] !== "0:00") {
									echo explode('_', $row2[$x])[1] . " Pace<br>";
								}
							}
							if (explode('_', $row2[$x])[2] !== "") {
								echo explode('-', explode('_', $row2[$x])[2])[0] . "<br>";
							}
							if (explode('_', $row2[$x])[3] === "") {
								echo "No Comments</div></td>";
							}
							else {
								echo "Comments: " . explode('_', $row2[$x])[3] . "</div></td>";
							}
							$total += explode('_', $row2[$x])[0];
						} else {
							echo "<td></td>";
						}
					}
					echo "<td>" . $total . "</td>";
					echo "</tr>";
				}
			}
		}
		echo '<script>
			function sortTable() {
				var table, rows, switching, i, x, y, shouldSwitch;
				table = document.getElementById("table");
				switching = true;
				while (switching) {
					switching = false;
					rows = table.getElementsByTagName("TR");
					for (i=1; i < rows.length - 1; i++) {
						shouldSwitch = false;
						x = rows[i].getElementsByTagName("TD")[15];
						y = rows[i+1].getElementsByTagName("TD")[15];
						if (parseFloat(x.innerHTML) < parseFloat(y.innerHTML)) {
							shouldSwitch = true;
							break;
						}
					}
					if (shouldSwitch) {
						rows[i].parentNode.insertBefore(rows[i + 1], rows[i]);
						switching = true;
					}
				}
			}
			sortTable();
			</script>';
	}
	else {
		echo "<table id='table' class='mileTable'><tr><th id='mileFirstCol'>Week of:</th><th>Mon.<br>AM</th><th>Mon.<br>PM</th><th>Tue.<br>AM</th><th>Tue.<br>PM</th>
			<th>Wed.<br>AM</th><th>Wed.<br>PM</th><th>Thu.<br>AM</th><th>Thu.<br>PM</th><th>Fri.<br>AM</th><th>Fri.<br>PM</th>
			<th>Sat.<br>AM</th><th>Sat.<br>PM</th><th>Sun.<br>AM</th><th>Sun.<br>PM</th><th>Total</th></tr>";
		
		$query="SELECT * FROM `" . $person . "` ORDER BY start_date DESC";
		$result = mysqli_query($link, $query);
		
		while ($row = mysqli_fetch_array($result)) {
			$total = 0;
			echo "<tr>";
			echo "<td>" . $row[0] . "</td>";
			for ($x = 1; $x < count($row) / 2; $x++) {
				if ($row[$x] !== "") {
					if (explode('_', $row[$x])[4] !== "") {
						echo "<td class='toHover racecontainer'>" . (float)explode('_', $row[$x])[0] . "<div class='hidden'>";
						$time = explode(':', explode('_', $row[$x])[6]);
						if ((float)$time[0] === (float)0 || $time[0] === "") {
							array_shift($time);
						}
						echo "<span class='raceday'>Race Day!<br>" . explode('_', $row[$x])[4] . "<br>" . explode('_', $row[$x])[5] . "<br>" . implode(":", $time) . "<br></span>";
					}
					else {
						echo "<td class='toHover'>" . (float)explode('_', $row[$x])[0] . "<div class='hidden'>";
						if (explode('_', $row[$x])[1] !== "0:00") {
							echo explode('_', $row[$x])[1] . " Pace<br>";
						}
					}
					if (explode('_', $row[$x])[2] !== "") {
						echo explode('-', explode('_', $row[$x])[2])[0] . "<br>";
					}
					if (explode('_', $row[$x])[3] === "") {
						echo "No Comments</div></td>";
					}
					else {
						echo "Comments: " . explode('_', $row[$x])[3] . "</div></td>";
					}
					$total += explode('_', $row[$x])[0];
				} else {
					echo "<td></td>";
				}
			}
			echo "<td>" . $total . "</td>";
			
			echo "</tr>";
		}
	}
	
	mysqli_close($link);
?>
</table>
</div>
<?php
	if(!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] === FALSE) {
		ob_end_clean();
	} else {
		ob_end_flush();
	}
?>
</div>
<span id="copyright">Copyright Josh Lawson 2017-<?php echo date('Y', time());?></span>
<?php
	include_once("ads.php");
?>
</body>
</html>
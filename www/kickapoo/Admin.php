<html>
	<head>
		<title>XC Mile Database</title>
		<link rel="icon" href="logo.png">
		<link rel="stylesheet" type="text/css" href="Baseline.css">
		<link rel="stylesheet" type="text/css" media='screen and (min-width: 1367px)'  href="NormalScreen.css">
		<link rel="stylesheet" type="text/css" media='screen and (max-width: 1366px) and (min-width: 641px)'  href="SmallScreen.css">
		<link rel="stylesheet" type="text/css" media='screen and (max-width: 640px)'  href="Mobile.css">
		<style>
			.text {
				left: 35%;
				line-height: 20px;
			}
			.form {
				right: 45%;
			}
		</style>
		<script src='styleJS.js'></script>
	</head>
	<body style="overflow-x: scroll" onload="myOnload();" onresize="myOnload();">
		<div id='topBar'>
		<a href="/"><img src="logo.png" id='home'></img></a>
		<h1 id='title' >Admin Only</h1>
<p class="topLinkHolder"><a class="Link" href="Records.php">All-Time Records</a></p>
<p class="bottomLinkHolder"><a class="Link" href="MileEntry.php">Enter Miles</a></p>
		</div>
		<div id='bigContainer'>
			<div>
			<form method="post" action="Admin.php">
				<p class='text' style="top: -5px; font-size: 20px;"></p><p class='form' style="top: -5px;"><input style="width: 300px;" type="text" name="sql"></p>
				<p class='form' style="right: 40%; top: -5px;"><input type="submit" value="Execute"></p>
			</form>
			</div>
			<div style="position: absolute; top: 60px; width: 100%;">
			<?php
	if (session_status() !== PHP_SESSION_ACTIVE) {
		session_start();
	}
			if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] == false) {
				header("Location: /");
			}
			if (!isset($_SESSION['table']) || $_SESSION['table'] !== 'josh_lawson') {
				header("Location: /");
			}
			$link = mysqli_connect($_SERVER['RDS_HOSTNAME'], $_SERVER['RDS_USERNAME'], $_SERVER['RDS_PASSWORD'], $_SERVER['RDS_DB_NAME'], $_SERVER['RDS_PORT']);

			if (isset($_POST['sql'])) {
				
			if ($_POST['sql'] === "new week") {
			date_default_timezone_set("America/Chicago");

			$query="SHOW TABLES";
			$result = mysqli_query($link, $query);
			while ($row = $result->fetch_row() ) {
				if ($row[0] != 'users') {
					$query="INSERT INTO `" . $row[0] . "` (`start_date`, `mon_am`, `mon_pm`, `tue_am`, `tue_pm`, `wed_am`, `wed_pm`, `thu_am`, `thu_pm`, `fri_am`, `fri_pm`,
						`sat_am`, `sat_pm`, `sun_am`, `sun_pm`) VALUES ('" . date("Y-m-d") . "', '', '', '', '', '', '', '', '', '', '', '', '', '', '');";

					mysqli_query($link, $query);
				}
			}
			}
			else {
				$result = mysqli_query($link, $_POST['sql']);
				$error = mysqli_error($link);
				if ($error !== "") {
					echo $error;
				}
				$data = [];
				while($row = mysqli_fetch_assoc($result)) {
					$data[] = $row;
				}
				$colNames = array_keys(reset($data));
				echo '<table><tr>';
				foreach($colNames as $colName)
				{
					echo "<th>$colName</th>";
				}
				echo '</tr>';
				foreach($data as $row) {
					echo '<tr>';
					foreach($colNames as $colName) {
						echo "<td>".$row[$colName]."</td>";
					}
					echo "</tr>";
				}
			}
			}
			mysqli_close($link);

			?>
			</div>
		</div>
		<div id="leftAd"></div>
		<div id="rightAd"></div>
	</body>
</html>
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
		<style>
			table {
				background-color: transparent;
			}
			table, th, td {
				border: none;
			}
			.form {
				text-align: right;
			}
		</style>
		<script type="text/javascript">
		function showfield(name){
		  if(name=='Other')document.getElementById('div1').style.display='table-row-group';
		  else document.getElementById('div1').style.display='none';
		}
		
		function showfield2(name){
		  if(name=='Other')document.getElementById('div3').style.display='table-row';
		  else document.getElementById('div3').style.display='none';
		}

		function showRaceBoxes(name){
		  if(name=='race')document.getElementById('div2').style.display='table-row-group';
		  else document.getElementById('div2').style.display='none';
		}
		</script>
		<script src='styleJS.js'></script>
		<meta name="viewport" content="width=device-width,initial-scale=1.0">
	</head>
<body onload="myOnload();" onresize="myOnload();">
<div id='topBar'>
<a href="/"><img src="logo.png" id='home'></img></a>
<h1 id='title'>Enter a Run</h1>
<p class="topLinkHolder"><a class="Link" href="Records.php">All-Time Records</a></p>
<p class="bottomLinkHolder"><a class="Link" href="MileChart.php">View Miles</a></p>
</div>
<div id='bigContainer'>
<?php
	ob_start(NULL, 0, PHP_OUTPUT_HANDLER_REMOVABLE);
?>
<div style="position: absolute; bottom: 0px; height: 10px; width: 10px;" onclick="document.getElementById('poll').style.display='table-row-group';"></div>
<form action="process_MileEntry.php" id="mileForm" method="post">
<table class="mileEntry" id='littleContainer'>
	<tbody>
		<tr>
			<td class='text'>Run Type:</td><td class='form'><select class="inputBox" onchange="showRaceBoxes(this.options[this.selectedIndex].value)" name="runType">
					<option selected="selected" value="easy">Easy Run</option>
					<option value="easy">Workout</option>
					<option value="race">Race Day</option>
				</select>
			</td>
		</tr>
	</tbody>
	<tbody id="div2" style="display:none">
		<tr>
			<td>
				Race Name:
			</td>
			<td class="form">
				<input id="raceName" class="inputBox" type="text" name="raceName" />
			</td>
		</tr>
		<tr>
			<td>
				Race Distance:
			</td>
			<td class="form">
				<select class="inputBox" name="raceDistance">
					<option>5K</option>
					<option>3K</option>
					<option>3200M</option>
					<option>1600M</option>
				</select>
			</td>
		</tr>
		<tr>
			<td>
				Time: (h:mm:ss.millis)
			</td>
			<td class="form">
				<input id="raceTimeHours" type="number" name="raceHours" value='0' min=0>:<input id="raceTimeMins" type="number" name="raceMins" value='00' min=0 max=59>:<input id="raceTimeSeconds" type="number" value='00' name="raceSecs" min=0 max=59>.<input id="raceTimeMillis" type="number" value='00' name="raceMillis" min=0 max=99>
			</td>
		</tr>
	</tbody>
	<tbody>
<tr>
<td class='text'>Miles Ran:</td><td class='form'><input id="mileEntryMileCount" type="number" name="miles" step=.01 min=0></td></tr><tr>
<td class='text form2'>Average Pace:</td><td class='form form2'><input id="mileEntryMinutes" type="number" name="mins" value='0' min=0 max=59>:<input id="mileEntrySeconds" type="number" value='00' name="secs" min=0 max=59></td>
</tr><tr>


<td class='text form3'>Shoes Used:</td><td class='form form3'><select name="shoes" class="inputBox" id="mileEntryShoes" onchange="showfield(this.options[this.selectedIndex].value)">
<option value="" selected="selected">Please select ...</option>

<?php
$link = mysqli_connect($_SERVER['RDS_HOSTNAME'], $_SERVER['RDS_USERNAME'], $_SERVER['RDS_PASSWORD'], $_SERVER['RDS_DB_NAME'], $_SERVER['RDS_PORT']);
$query="SELECT * FROM `" . $_SESSION['table'] . "`";
$result2 = mysqli_query($link, $query);
$shoes = [];
$rows = [];
if ($result2) {
	while ($row2 = mysqli_fetch_array($result2)) {
		array_unshift($rows, $row2);
	}
	for ($i = 0; $i < count($rows); $i++) {
		for ($x = count($rows[$i]) / 2 - 1; $x >= 1; $x--) {
			if ($rows[$i][$x] !== "") {
				$piece = explode('_', $rows[$i][$x]);
				if (array_search($piece[2], $shoes) === FALSE) {
					if ($piece[2] != "") {
						$shoes[] = explode('-', $piece[2])[0];
					}
				}
			}
		}
	}
	for ($x = 0; $x < count($shoes); $x++) {
		echo '<option value="' . $shoes[$x] . '">' . $shoes[$x] . '</option>';
	}
}
?>

<option value="Other">Enter a new shoe</option>
</select>
</tr>
</tbody>
<tbody id="div1" style="display: none">
<tr><td>Name your shoe</td><td class="form"><input id="newShoeBox" class="inputBox" placeholder="" type="text" name="newShoeName" /></td></tr>
<tr><td>Shoe Model</td><td class="form"><select id="newShoeBox" class="inputBox" placeholder="" type="text" name="newShoeModel" onchange="showfield2(this.options[this.selectedIndex].value)">
<?php
	$query="SELECT * FROM `PAAPI`.`shoeTable` ORDER BY shoe ASC";
	$result = mysqli_query($link, $query);
	while ($row = mysqli_fetch_array($result)) {
		echo '<option value="' . $row[1] . '">' . $row[0] . '</option>';
	}
?>
<option value="Other">New Shoe Model</option>
</select>
</td></tr>
<tr id="div3" style="display: none"><td>Enter ASIN</td><td class="form"><input id="newShoeBox" placeholder="Found on Amazon" class="inputBox" type="text" name="newShoeModelName" /></td></tr>
<tr><td>Size</td><td class="form"><input id="newShoeBox" type="number" name="newShoeSize" step=.5 min=6 max=16></td></tr>
</tbody><tbody>
<td class='text form4'>Date:</td><td class='form form4'><input class="inputBox" type="date" name="date"></td></tr><tr>
<td class='text form5'>Time:</td><td class='form form5'><select class="inputBox" name="time">
	<option value="am">Morning</option>
	<option value="pm">Afternoon</option>
</select></td></tr><tr>
<td class='form form6' id="mileEntryCommentContainer" colspan=2><textarea placeholder='Comments' id="mileEntryComments" form="mileForm" name="comments"></textarea></td></tr><tr>
</tbody>
<tbody id='poll' style='display: none;'>
	<tr>
	<td class='text form3'>
	<select name="pollTopic" class="inputBox">
		<option>Pick a poll:</option>
		<?php
			global $polls;
			foreach ($polls as $poll) {
				echo "<option>$poll</option>";
			}
		?>
	</select>
	</td>
	<td class='form form3'><select name="pollVote" class="inputBox">
	<option>Pick your vote:</option>
	<?php
	$query="SELECT * FROM `users` ORDER BY Full_name ASC";
	$result = mysqli_query($link, $query);
	while ($row = mysqli_fetch_array($result)) {
		echo '<option>' . $row[0] . '</option>';
	}
	?>
	</select>
	</td>
	</tr>
	<tr style="font-size: .5em;"><td>Polls by Anna Weiner</td><td></td></tr>
</tbody>
<tbody>
<td class='text form8'></td><td class='form form8'><input class="inputBox" type="submit"></td></tr>
</tbody>
</table>
</form>
<?php
	if(!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] === FALSE) {
		ob_end_clean();
	} else {
		ob_end_flush();
	}
?>
<span id="copyright">Copyright Josh Lawson 2017-<?php echo date('Y', time());?></span>
</div>
<?php
	include_once("ads.php");
?>
</body>
</html>
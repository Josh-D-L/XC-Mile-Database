
<?php
	if (session_status() !== PHP_SESSION_ACTIVE) {
		session_start();
	}
	
	require_once('paapi.php');

$miles = check_input($_POST['miles']);
$mins = check_input($_POST['mins']);
$secs = check_input($_POST['secs']);
$shoes = check_input($_POST['shoes']);
$date = check_input($_POST['date']);
$time = check_input($_POST['time']);
$newShoeName = str_replace('-', '&#45;', check_input($_POST['newShoeName']));
$newShoeModel = str_replace('-', '&#45;', check_input($_POST['newShoeModel']));
$newShoeModelName = str_replace('-', '&#45;', check_input($_POST['newShoeModelName']));
$newShoeSize = str_replace('-', '&#45;', check_input($_POST['newShoeSize']));
$pollTopic = check_input($_POST['pollTopic']);
$pollVote = check_input($_POST['pollVote']);
$comments = check_input($_POST['comments']);
$raceName = check_input($_POST['raceName']);

$day = date("l", strtotime($date));
$start = date("w", strtotime($date));
if ($start == 0) {
	$start = 7;
}
$start -= 1;
$start = strtotime($date) - ($start * 24 * 60 * 60);
$start = date("Y-m-d", $start);

date_default_timezone_set("America/Chicago");

$link = mysqli_connect($_SERVER['RDS_HOSTNAME'], $_SERVER['RDS_USERNAME'], $_SERVER['RDS_PASSWORD'], $_SERVER['RDS_DB_NAME'], $_SERVER['RDS_PORT']);

if ($shoes === "Other") {
	if ($newShoeModel === "Other") {
		read_query_text("Operation = ItemLookup\nItemId = " . $newShoeModelName . "\nIdType = ASIN\nResponseGroup = ItemAttributes");
		$url = paapi::sign_query($parameters);
		$y = paapi::retrieve($url);
		$myXml = simplexml_load_string($y);
		$newShoeModel = (string)$myXml->Items->Item->ItemAttributes->Title;
		$query="INSERT INTO `PAAPI`.`shoes` (shoe, ASIN) VALUES (" . $newShoeModel . ", " . $newShoeModelName . ")";
		mysqli_query($link, $query);
	}
	$shoes = $newShoeName . '-' . $newShoeModel . '-' . $newShoeSize;
}

if ($date !== "") {
	$query="SELECT * FROM `" . $_SESSION['table'] . "` WHERE start_date = '" . $start . "'";
	$data = mysqli_query($link, $query);
	if (mysqli_num_rows ($data) === 0) {
		$query="INSERT INTO `" . $_SESSION['table'] . "` (`start_date`) VALUES ('" . $start . "');";
		mysqli_query($link, $query);
	}
					
	if ((float)$miles === (float)0 || $miles === "") { 
		$query="UPDATE `" . $_SESSION['table'] . "` SET " . substr($day, 0, 3) . "_" . $time . "='' WHERE start_date='" . $start . "';";
	} 
	else {
		if ($pollVote !== "Pick your vote:" && $pollTopic !== "Pick a poll:") {
			if ($_POST['runType'] === "race") {
				$query="UPDATE `" . $_SESSION['table'] . "` SET " . substr($day, 0, 3) . "_" . $time . "='" . $miles . "_" . $mins . ":" . $secs . "_" . $shoes . "_" . $comments . "_" . 
					$raceName . "_" . $_POST['raceDistance'] . "_" . $_POST['raceHours'] . ":" . $_POST['raceMins'] . ":" . $_POST['raceSecs'] . "." . $_POST['raceMillis'] . "_" . $pollTopic . "_" . $pollVote . "' WHERE start_date='" . $start . "'";
			}	
			else {
			$query="UPDATE `" . $_SESSION['table'] . "` SET " . substr($day, 0, 3) . "_" . $time . "='" . $miles . "_" . $mins . ":" . $secs . "_" . $shoes . "_" . $comments . "____" . $pollTopic . "_" . $pollVote . "' WHERE start_date='" . $start . "'";
			}
		}
		else {
			if ($_POST['runType'] === "race") {
				$query="UPDATE `" . $_SESSION['table'] . "` SET " . substr($day, 0, 3) . "_" . $time . "='" . $miles . "_" . $mins . ":" . $secs . "_" . $shoes . "_" . $comments . "_" . 
					$raceName . "_" . $_POST['raceDistance'] . "_" . $_POST['raceHours'] . ":" . $_POST['raceMins'] . ":" . $_POST['raceSecs'] . "." . $_POST['raceMillis'] . "__' WHERE start_date='" . $start . "'";
			}	
			else {
			$query="UPDATE `" . $_SESSION['table'] . "` SET " . substr($day, 0, 3) . "_" . $time . "='" . $miles . "_" . $mins . ":" . $secs . "_" . $shoes . "_" . $comments . "_____' WHERE start_date='" . $start . "'";
			}
		}
	}
	error_log($query);

	$result = mysqli_query($link, $query);

	$query="UPDATE `users` SET `lastRun`='" . date("Y-m-d H:i:s") . "' WHERE `Tbl_name`='" . $_SESSION['table'] . "';";
	mysqli_query($link, $query);
}

mysqli_close($link);

Header('Location: /MileChart.php');


function check_input($data) {
	$data = trim($data);
	$data = stripslashes($data);
	$data = htmlspecialchars($data, ENT_QUOTES);
	$data = str_replace('_', '&#95;', $data);
	return $data;
}
function read_query_text($txt) {
    //parse textarea submittal into:
    //   $parameters: an array for query signer
    //   $querytxt: a string to display in the textarea
    global $querytxt, $parameters;
    $lines = explode("\n", $txt);
    $querytxt = '';
    $parameters = array();
    foreach($lines as $line) {
        $q = explode('=', $line, 2);
        if(! $q) continue;
        $k = trim($q[0]);
        if(! $k) continue;
        if($q[1]) {
            $v = trim($q[1]);
            $parameters[$k] = $v;
            $querytxt .= "$k = $v\n";
        }
        else {
            $querytxt .= trim($k)."\n";
        }
    }
}
?>
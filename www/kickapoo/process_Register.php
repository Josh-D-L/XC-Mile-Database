<?php
date_default_timezone_set("America/Chicago");
$link = mysqli_connect($_SERVER['RDS_HOSTNAME'], $_SERVER['RDS_USERNAME'], $_SERVER['RDS_PASSWORD'], $_SERVER['RDS_DB_NAME'], $_SERVER['RDS_PORT']);
$user = check_input($_POST['username']);
$name = check_input($_POST['name']);
$pass = $_POST['password'];
$pass2 = $_POST['password2'];

if ($pass !== $pass2) {
	header("Location: Register.php?error=1");
}

$query = 'SELECT 1 FROM `users` WHERE Full_name = ' . $name;
$result = mysqli_query($link, $query);
if (!$result || $result->num_rows != 0) {
	header("Location: Register.php?error=2");
}

$query = 'SELECT 1 FROM `users` WHERE Username = ' . $user;
$result = mysqli_query($link, $query);
if (!$result || $result->num_rows != 0) {
	header("Location: Register.php?error=3");
}

$stored = password_hash(
	base64_encode(
		hash('sha256', $pass, true)
	), PASSWORD_DEFAULT);

$tbl_name = strtolower($name);
$tbl_name = str_replace(' ', '_', $tbl_name);

$query="INSERT INTO `users`(`Full_name`, `Username`, `Pass`, `Tbl_name`) VALUES ('" . $name . "','" . $user . "',
	'" . $stored . "','" . $tbl_name . "')";
$result = mysqli_query($link, $query);

$query="CREATE TABLE `" . $tbl_name . "` ( `start_date` DATE NOT NULL , `mon_am` TEXT NOT NULL , `mon_pm` TEXT NOT NULL , `tue_am` TEXT NOT NULL , `tue_pm` TEXT NOT NULL , `wed_am` TEXT NOT NULL , `wed_pm` TEXT NOT NULL , `thu_am` TEXT NOT NULL , `thu_pm` TEXT NOT NULL , `fri_am` TEXT NOT NULL , `fri_pm` TEXT NOT NULL , `sat_am` TEXT NOT NULL , `sat_pm` TEXT NOT NULL , `sun_am` TEXT NOT NULL , `sun_pm` TEXT NOT NULL ) ENGINE = MyISAM CHARSET=ascii COLLATE ascii_general_ci;";


$day = date("l", time());
$start = date("w", time());
if ($start == 0) {
	$start = 7;
}
$start -= 1;
$start = time() - ($start * 24 * 60 * 60);
$start = date("Y-m-d", $start);

$result = mysqli_query($link, $query);

mysqli_close($link);

header("Location: Login.php");

function check_input($data) {
	$data = trim($data);
	$data = stripslashes($data);
	$data = htmlspecialchars($data);
	return $data;
}
?>
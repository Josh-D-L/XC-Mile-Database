<?php
	$polls = ["Best Memer", "Best Dressed", "Biggest Ditz", "Fashion Icon", "Most Ballin'"];
	if (session_status() !== PHP_SESSION_ACTIVE) {
		session_start();
	}
	
	$link = mysqli_connect($_SERVER['RDS_HOSTNAME'], $_SERVER['RDS_USERNAME'], $_SERVER['RDS_PASSWORD'], $_SERVER['RDS_DB_NAME'], $_SERVER['RDS_PORT']);
	
	if(!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] === FALSE) {
		if (isset($_COOKIE['uname'])) {
			$uname = $_COOKIE['uname'];
			if (!empty($uname)) {
				$query = "SELECT * FROM `users` WHERE `login_cookie`='$uname'";
				$result = mysqli_query($link, $query);
				if (($result) && mysqli_num_rows($result) !== 0) {
					$row = mysqli_fetch_object($result);
					$_SESSION['logged_in'] = true;
					$_SESSION['table'] = $row->Tbl_name;
					$_SESSION['name'] = $row->Full_name;
					setcookie("uname", $uname, time()+3600*24*365, "/");
				}
			}
		}
	}
	if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] == false) {
		header("Location: /");
	}
	
	mysqli_close($link);
?>
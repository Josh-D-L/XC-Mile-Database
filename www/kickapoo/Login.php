
<?php
	if (session_status() !== PHP_SESSION_ACTIVE) {
		session_start();
	}
	
	$link = mysqli_connect($_SERVER['RDS_HOSTNAME'], $_SERVER['RDS_USERNAME'], $_SERVER['RDS_PASSWORD'], $_SERVER['RDS_DB_NAME'], $_SERVER['RDS_PORT']);
	
	if (!empty($_GET['error'])) {
		$msg = '';
		if ($_GET['error'] == 1) {
			$msg = "Incorrect username or password";
		}
		echo $msg;
	}
	
	
	if (isset($_SESSION['logged_in']) && $_SESSION['logged_in'] == true) {
		header("Location: /");
	}

	if (isset($_POST['username']) && isset($_POST['password'])) {
		$query = "SELECT pass FROM users WHERE Username = '" . $_POST['username'] . "'";
		$result = mysqli_fetch_object(mysqli_query($link, $query))->pass;
		if (password_verify(
			base64_encode(
				hash('sha256', $_POST['password'], true) 
			),
			$result))
		{
			$_SESSION['logged_in'] = true;
			$query = "SELECT Tbl_name FROM users WHERE Username = '" . $_POST['username'] . "'";
			$result = mysqli_fetch_object(mysqli_query($link, $query))->Tbl_name;
			$_SESSION['table'] = $result;
			$query = "SELECT Full_name FROM users WHERE Username = '" . $_POST['username'] . "'";
			$result = mysqli_fetch_object(mysqli_query($link, $query))->Full_name;
			$_SESSION['name'] = $result;
			
			if (isset($_POST['rememberMe']) ? $_POST['rememberMe'] : "0" === "1") {
				$cookiehash = mysqli_fetch_object(mysqli_query($link, "SELECT `login_cookie` FROM users WHERE `Tbl_name`='" . $_SESSION['table'] . "';"))->login_cookie;
				if ($cookiehash === "") {
					$cookiehash = password_hash($_POST['username'] . $_SERVER['REMOTE_ADDR'], PASSWORD_DEFAULT);
				}
				setcookie("uname", $cookiehash, time()+3600*24*365, "/");
				mysqli_query($link, "UPDATE `users` SET `login_cookie`='$cookiehash' WHERE `Tbl_name`='" . $_SESSION['table'] . "';");
				error_log(mysqli_error($link), 0);
				error_log("Cookie issued to " . $_SESSION['name'], 0);
			}
			
			mysqli_close($link);
			header("Location: /");
		}
		else {
			header("Location: Login.php?error=1");
			mysqli_close($link);
		}
	}
	
?>
<html>
	<head>
		<title>XC Mile Database</title>
		<?php include_once("analyticstracking.php") ?>
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
<h1 id='title'>Log In</h1>
</div>
<div id='bigContainer'>
<form method="post" action="Login.php">
	<p class='text' >Username:</p><p class='form'><input class="inputBox" type="text" name="username"></p>
	<p class='text form2'>Password:</p><p class='form form2'><input class="inputBox" type="password" name="password"></p>
	<p class='text form3'>Remember Me:</p><p class='form form3'><input class="inputBox" type="checkbox" name="rememberMe" value='1'></p>
	<p class='form form4'><input class="inputBox" type="submit" value="Login"></p>
</form>
<span id="copyright">Copyright Josh Lawson 2017-<?php echo date('Y', time());?></span>
</div>
<?php
	include_once("ads.php");
?>
</body>
</html>
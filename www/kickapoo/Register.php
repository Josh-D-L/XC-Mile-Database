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
<h1 id='title' >Register an account</h1>
</div>
<div id='bigContainer'>
<?php
	if (!empty($_GET['error'])) {
		$msg = '';
		if ($_GET['error'] == 1) {
			$msg = "Your passwords don't match.";
		}
		if ($_GET['error'] == 2) {
			$msg = "There's already someone with your name...";
		}
		if ($_GET['error'] == 3) {
			$msg = "There's already someone with that username.";
		}
		echo $msg;
	}
?>
<form method="post" action="process_Register.php">
	<p class='text' style="">Username:</p><p class='form'><input class="inputBox" type="text" name="username" required></p>
	<p class='text form2'>Password:</p><p class='form form2'><input class="inputBox" type="password" name="password" required></p>
	<p class='text form3'>Reenter password:</p><p class='form form3'><input class="inputBox" type="password" name="password2" required></p>
	<p class='text form4'>First and Last Name:</p><p class='form form4'><input class="inputBox" type="text" name="name" required></p>
	<p class='form form5'><input class="inputBox" type="submit" value="Register"></p>
</form>
<span id="copyright">Copyright Josh Lawson 2017-<?php echo date('Y', time());?></span>
</div>
<?php
	include_once("ads.php");
?>
</body>
</html>
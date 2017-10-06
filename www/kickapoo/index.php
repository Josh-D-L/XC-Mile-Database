<html>
	<head>
		<title>XC Mile Database</title>
		<?php include_once("analyticstracking.php") ?>
		<meta name="google-site-verification" content="Pdv3vUHteQw2msWLIKN9hoIlzvMbV6kVj23UuWBTq64" />
		<meta name='ir-site-verification-token' value='-306862340' />
		<meta name="description" content="Mile logger for Cross Country runners" />
		<meta name="keywords" content="xc mile database, cross country mile database, mile logger, xc mile logger" />
		<link rel="icon" href="logo.png">
		<link rel="stylesheet" type="text/css" href="Baseline.css">
		<link rel="stylesheet" type="text/css" media='screen and (min-width: 1367px)'  href="NormalScreen.css">
		<link rel="stylesheet" type="text/css" media='screen and (max-width: 1366px) and (min-width: 641px)'  href="SmallScreen.css">
		<link rel="stylesheet" type="text/css" media='screen and (max-width: 640px)'  href="Mobile.css">
		<meta name="viewport" content="width=device-width,initial-scale=1.0">
	</head>
	<body class="homepage">
		<h1 class="header homepage">Cross Country<br><span class="subtitle homepage">Mile Database</span></h1>
		<p class="homepage" id="greetingBox">
		<?php
	if (session_status() !== PHP_SESSION_ACTIVE) {
		session_start();
	}
			$link = mysqli_connect($_SERVER['RDS_HOSTNAME'], $_SERVER['RDS_USERNAME'], $_SERVER['RDS_PASSWORD'], $_SERVER['RDS_DB_NAME'], $_SERVER['RDS_PORT']);
			
			
			
			if(!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] === FALSE) {
				if (isset($_COOKIE['uname'])) {
					$uname = $_COOKIE['uname'];
					if (!empty($uname)) {
						$query = "SELECT * FROM `users` WHERE `login_cookie`='$uname';";
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
			
			mysqli_close($link);
			if (isset($_SESSION['name'])) {
				echo 'Hello, ' . explode(' ',$_SESSION['name'])[0];
				echo '<br><a class="homepage" href="Logout.php">Logout</a>';
			}
			else {
				echo 'Please <a class="homepage" href="Login.php">Log in</a> or <a class="homepage" href="Register.php">Register</a>';
			}
		?> 
		</p>
		<p class="homepage" id="linksBox">
		<?php
			if (isset($_SESSION['name'])) {
				echo '<a class="homepage" href="MileEntry.php">Enter Miles</a><br>';
				echo '<a class="homepage" href="MileChart.php">See your logged miles</a><br>';
				echo '<a class="homepage" href="Statistics.php">More data</a><br>';
				echo '<a target="_blank" style="color: Red" href="https://www.amazon.com/s/ref=sr_st_relevancerank?keywords=running+shoes&amp;fst=as%3Aoff&amp;rh=n%3A7141123011%2Ck%3Arunning+shoes%2Cp_89%3ASaucony%7CNew+Balance%7CBrooks%7CHoka+One%7CHoka&amp;qid=1504656078&amp;bbn=7141123011&amp;sort=relevancerank&_encoding=UTF8&tag=mil04f-20&linkCode=ur2&linkId=74d48b8d4a07b925969b3532438c9dfb&camp=1789&creative=9325">Buy new shoes</a><img src="//ir-na.amazon-adsystem.com/e/ir?t=mil04f-20&l=ur2&o=1" width="1" height="1" border="0" alt="" style="border:none !important; margin:0px !important;" />';
			}
		?> 
		</p>
		<span class="homepage" id="copyright">Copyright Josh Lawson 2017-<?php echo date('Y', time());?></span>
		<div class="AdSpot homepage"></div>
	</body>
</html>
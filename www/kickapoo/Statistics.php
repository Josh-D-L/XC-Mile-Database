<html>
	<head>
		<title>XC Mile Database</title>
		<?php include_once("analyticstracking.php") ?>
		<?php include_once("check_login.php"); 
		require_once('paapi.php');?>
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
<h1 id='title' >Statistics</h1>
<p class="topLinkHolder"><a class="Link" href="Records.php">All-Time Records</a></p>
<p class="bottomLinkHolder"><a class="Link" href="MileEntry.php">Enter Miles</a></p>
</div>
<div id='bigContainer'>
<?php
	ob_start(NULL, 0, PHP_OUTPUT_HANDLER_REMOVABLE);
?>
<div style="position: absolute; bottom: 0px; height: 10px; width: 10px;" onclick="document.getElementById('poll').style.display='table';"></div>
<?php
	if (session_status() !== PHP_SESSION_ACTIVE) {
		session_start();
	}
	$link = mysqli_connect($_SERVER['RDS_HOSTNAME'], $_SERVER['RDS_USERNAME'], $_SERVER['RDS_PASSWORD'], $_SERVER['RDS_DB_NAME'], $_SERVER['RDS_PORT']);
	echo "<table class='recordsTable' id='poll' style='display: none'><tbody><tr><th class='recordsTableCategory'>Category</th><th class='recordsTableRecord'>Votes</th></tr>";
	// Poll records
	global $polls;
	foreach ($polls as $poll) {
		$value = 0;
		$query="SHOW TABLES";
		$result = mysqli_query($link, $query);
		while ($row = mysqli_fetch_array($result)) {
			if ($row[0] !== "users") {
				$query="SELECT * FROM " . $row[0] . " ORDER BY start_date ASC";
				$result2 = mysqli_query($link, $query);
				while ($row2 = mysqli_fetch_array($result2)) {
					for ($x = 1; $x < count($row2) / 2; $x++) {
						if ($row2[$x] !== "") {
							$piece = explode('_', $row2[$x]);
						
							if (isset($piece[7]) && $piece[7] === $poll) {
								if ($piece[8] === $_SESSION['name']) {;
									$value += floor($piece[0]);
								}
							}
						}
					}
				}
			}
		}
		if ($value !== 0) {
			echo "<tr><td>" . $poll . "</td><td>" . $value . " votes</td></tr>";
		}
	}
	// End polls
	
	echo "<table id='statsShoeTable'><tr><th colspan='4'>Miles Per Shoe</th></tr><tr><th>Shoe</th><th>Total Miles</th><th>Model</th><th>Price</th></tr>";
		
	$query="SELECT * FROM `" . $_SESSION['table'] . "`";
	$result = mysqli_query($link, $query);
	
	$shoes = [];
	$miles = [];
	while ($row = mysqli_fetch_array($result)) {
		for ($x = 1; $x < count($row) / 2; $x++) {
			if ($row[$x] !== "") {
				$piece = explode('_', $row[$x]);
				if ($piece[2] !== "") {
					if (array_search($piece[2], $shoes) === FALSE) {
						$shoes[] = $piece[2];
						$miles[] = $piece[0];
					} else {
						$miles[array_search(explode('_', $row[$x])[2], $shoes)] += explode('_', $row[$x])[0];
					}
				}
			}
		}
	}
	for ($x = count($shoes) - 1; $x > -1; $x--) {
		if (count(explode("-", $shoes[$x])) > 1) {
			
			$query = "Select `shoe` FROM `PAAPI`.`shoeTable` WHERE ASIN = '" . explode("-", $shoes[$x])[1] . "'";
			$shoeModel = mysqli_fetch_object(mysqli_query($link, $query))->shoe;
			read_query_text("Operation = ItemLookup\nItemId = " . explode("-", $shoes[$x])[1] . "\nIdType = ASIN\nResponseGroup = Variations");
			
			
			// send parameters to query signer and get a URL
			$url = paapi::sign_query($parameters);
			
			
			// Query the API if the button was pressed
			$y = paapi::retrieve($url);
			$myXml = simplexml_load_string($y);
			$size = "";
			$price = -1;
			$formatPrice = "";
			$ASIN = "";
			foreach ($myXml->Items->Item->Variations->Item as $item) {
				if (strpos((string)$item->VariationAttributes->VariationAttribute[0]->Value, explode("-", $shoes[$x])[2]) > -1 && !(strpos((string)$item->VariationAttributes->VariationAttribute[0]->Value, "Wide") > -1)) {
					if ($price === -1 || (float)$item->ItemAttributes->ListPrice->Amount < $price) {		
						$size = (string)$item->VariationAttributes->VariationAttribute[0]->Value;
						$formatPrice = (string)$item->ItemAttributes->ListPrice->FormattedPrice;
						$price = (float)$item->ItemAttributes->ListPrice->Amount;
						$ASIN = (string)$item->ASIN;
					}					
				}
			}
			read_query_text("Operation = ItemLookup\nItemId = " . $ASIN . "\nIdType = ASIN\nResponseGroup = OfferSummary,ItemAttributes\n");
			if(count($parameters)>0) {
				// send parameters to query signer and get a URL
				$url = paapi::sign_query($parameters);
			 
				// Query the API if the button was pressed
				$y = paapi::retrieve($url);
				
				$myXml2 = simplexml_load_string($y);
			}
			echo "<tr><td>" . explode('-', $shoes[$x])[0] . "</td><td>$miles[$x]</td><td>" . $shoeModel . "</td><td><a href=" . (string)$myXml2->Items->Item->ItemLinks->ItemLink[6]->URL . " target='_blank'>" . (string)$myXml2->Items->Item->OfferSummary->LowestNewPrice->FormattedPrice . "</a></td></tr>";
		}
		else {
			echo "<tr><td>" . explode('-', $shoes[$x])[0] . "</td><td>$miles[$x]</td><td></td><td></td></tr>";
		}
	}
	
	$query="SELECT * FROM `" . $_SESSION['table'] . "`";
	$result = mysqli_query($link, $query);
	$total = 0;
	while ($row = mysqli_fetch_array($result)) {
		for ($x = 1; $x < count($row) / 2; $x++) {
			if ($row[$x] !== "") {
					$total += explode('_', $row[$x])[0];
			}
		}
	}
	echo "</table><p id='statsTotalMiles'>Total Miles: " . $total . "</p>";
	
	mysqli_close($link);
?>
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
<?php
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
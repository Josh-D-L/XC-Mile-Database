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
//ini_set('display_errors','Off');
//ini_set('display_errors','On');
//error_reporting(E_ALL ^ E_NOTICE);
 
require_once('paapi.php');
 
/* Typical API queries are saved in a cookie */
if($_COOKIE['queries'])
    $savedQueries = unserialize($_COOKIE['queries']);
else
    $savedQueries = array();
if(! is_array($savedQueries) || count($savedQueries)<1) {
    $savedQueries = array(
        'ItemSearch' => array( 'Operation'     => 'ItemSearch',
                               'SearchIndex'   => 'Electronics',
                               'Keywords'      => 'apple mac',
                               'ResponseGroup' => 'ItemAttributes' ),
        'ItemLookup' => array( 'Operation'     => 'ItemLookup',
                               'ItemId'        => 'B00008OE6I',
                               'IdType'        => 'ASIN',
                               'ResponseGroup' => 'ItemAttributes' ),
        'BrowseNodeLookup' => array('Operation'    => 'BrowseNodeLookup',
                                    'BrowseNodeId' => '1292115011',
                                    'ResponseGroup'=> 'BrowseNodeInfo' )
    );
}
 
/* Has the Submit Button been pressed? */
$query_submitted = false;
$xml_response = '';
if(count($_POST)>1 && $_POST['submitButton']=='SubmitQuery') {
    //set $querytxt to lines suitable for the textarea
    //set $parameters to an array suitable for the sign_query();
    read_query_text($_POST['qdeftxt']);
    $query_submitted = true;
}
 
/* If no query parameters have been sent, make up some to start */
if(! $querytxt) {
    //use the last one, saved in a cookie
    if($_COOKIE['lastop'] && $savedQueries[$_COOKIE['lastop']]) {
        set_query_text($savedQueries[$_COOKIE['lastop']]); 
    }
    else {
        set_query_text(reset($savedQueries));
    }
 
}
 
if(count($parameters)>0) {
    // send parameters to query signer and get a URL
    $url = paapi::sign_query($parameters);
 
    // Put it in a link for display
    $urlparts = str_replace('&', '<br />&', $url);
    $urlparts = str_replace('?', '<br />?', $urlparts);
    $link = '<a href="'.$url.'" target="paapiResult">'.$urlparts.'</a>';
 
    // Query the API if the button was pressed
    if($query_submitted) {
        $x = paapi::retrieve($url);
        $xml_response = '<p>Displayed '.date('Y-m-d H:i')." GMT.<br/>\n";
        if(paapi::$cache_obj) {
            $m = paapi::$cache_obj->expsecs / 60; //minutes
            $xml_response .= 'Retrieved from cache. Expires in ';
            if(90 > $m) { 
                $xml_response .= number_format($m,1).' minutes. ';
            }
            elseif( 27 > ($m=$m/60) ) { 
                $xml_response .= number_format($m,1).' hours. ';
            }
            else {
                $m = $m/24;
                $xml_response .= number_format($m,1).' days. ';
            }
            $xml_response .= '('.paapi::$cache_obj->expires." GMT)</p>\n";
        }
        else {
            $xml_response .= "Retrieved from PA API.</p>\n";
        }
        $xml_response .= format_XML_string($x);
    }
}
else {
    $url = '';
    $link = 'No URL defined.';
}
 
/* set the query in a cookie */
if($qop = $parameters['Operation']) {
    $savedQueries[$parameters['Operation']] = $parameters; //update current query op
    $sq = serialize($savedQueries);
    $expiry = time() + (86400 * 1000); //expires after 1000 days
    setcookie('queries', $sq, $expiry);
    setcookie('lastop', $qop, $expiry);
}
 
 
/* Display the page */
 
?><html xml:lang="en" lang="en" dir="ltr">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<!-- Copyright (c) Kenneth Lucius.
*
* This work is licensed under a Creative Commons Attribution-NonCommercial-ShareAlike 3.0 Unported License.
*
*    http://creativecommons.org/licenses/by-nc-sa/3.0/
*
-->
    <title>PA API: Define</title>
 
    <style type="text/css">
        #xmltext p {
            padding:0;
            margin:0;
        }
        #xmltext p.item {
            background-color:#def;
        }
        #xmltext p.error {
            background-color:#fdd;
        }
 
        span.xtag { 
            color: #66a;
        }
        span.xclose { 
            color: #aaa;
        }
        ul.linklist, ul.linklist li {
            display: inline;
            list-style: none ;
            padding: 0 0.2em;
            margin: 0;
        }
    </style>
    <script type="text/javascript">
    var queries = <?php  print( json_encode($savedQueries) ); ?>;
    function loadquery(op) {
        var s="";
        for(var param in queries[op]) {
            s += param + " = " + queries[op][param] + "\n";
        }
        document.getElementById("qdeftxt").value = s;
    }
    </script>
</head>
 
<body>
<p>Also: <a href="./bnBrowse.php">PA API: BrowseNode Info</a></p>
 
<h3>Locale: <?php  print( paapi::$amz_locale ); ?><br/>
Others: <?php  print( paapi::get_locale_list() ); ?></h3>
 
<h2>The Query Definition:</h2>
<?php  print( get_savedQueries_list() ); ?>
<form action="<?php print("{$_SERVER['SCRIPT_NAME']}"); ?>" id="querydef" method="post">
<textarea name="qdeftxt" id="qdeftxt" cols="80" rows="10"><?php print($querytxt); ?></textarea><br />
<input type="submit" name="submitButton" id="submitButton" value="SubmitQuery">
</form>
 
<h2>The Query URL:</h2>
<?php  print("<p>$link</p>"); ?>
 
<h2>The Response:</h2>
<div id="xmltext">
<?php print($xml_response); ?>
</div>
</body>
</html>
 
 
<?php
 
/* Functions used above */
 
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
 
function set_query_text($parm) {
    global $querytxt, $parameters;
    $parameters = $parm;
    $querytxt ='';
    foreach($parm as $k => $v) {
            $querytxt .= "$k = $v\n";
    }
}
 
function get_savedQueries_list() {
    global $savedQueries;
    $qlist = '<ul class="linklist">';
    foreach($savedQueries as $op => $q) {
        $qlist .= '<a href="javas'
                . 'cript:void(0);" onclick="loadquery('
                    ."'$op'"
                    .');">['.$op.']</a> ';
    }
    $qlist .= '</ul>';
    return $qlist;
}
 
/* This function accepts a string of valid XML, (presumably from "paapi::retrieve()") ,
   and returns a string of formatted HTML */
function format_XML_string($xml) {
    if(! $xml) return false;
    $dom = new DOMDocument('1.0');
    $dom->preserveWhiteSpace = false;
    $dom->formatOutput = true;
 
    //The API sometimes returns an HTML error page
    //so we have to check it manually
    if(substr($xml,0,5)=='<?xml') {
        $dom->loadXML($xml);
        $xmlstring = $dom->saveXML();
    }
    else {
        $dom->loadHTML($xml);
        $xmlstring = $dom->saveHTML();
    }
 
    //if something went wrong, just dump the xml string
    if(! $xmlstring) return htmlspecialchars($xml);
 
    $lines = explode("\n", $xmlstring);
    $ret = '';
    $itemcount=0;
    $isError=false;
    foreach($lines as $line) {
        $s = preg_replace("/<(.+?)>/", '<span class="xtag">&lt;$1></span>', $line);
        $css = ' style="padding-left:'.strspn($s, '     ').'em;"';
        if(strpos($s, '&lt;Errors>')) {
            $isError = true;
        }
        elseif($isError) {
            if(strpos($s, '&lt;/Errors>')) {
                $isError = false;
                $class = '';
            }
            else
                $class = ' class="Error"';
        }
        elseif(strpos($s, '&lt;Item>')) {
            $class = ' class="Item"';
            $s .= ' — [Item # '. ++$itemcount . '] —';
        }
        else
            $class = '';
        $ret .= '<p'.$class.$css.'>'.$s."</p>\n";
    }
    $ret = str_replace('<span class="xtag">&lt;/', '<span class="xclose">&lt;/', $ret);
    return $ret;
 
}
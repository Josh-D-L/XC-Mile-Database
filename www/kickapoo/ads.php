<?php

	include_once("user_agent.php");
	
	$ua = new UserAgent();
	
	ob_start(NULL, 0, PHP_OUTPUT_HANDLER_REMOVABLE);
	?>
	<div id="leftAd">
	<script type="text/javascript" src="//cdn.chitika.net/getads.js" async></script>
	<script type="text/javascript">
	  ( function() {
		if (window.CHITIKA === undefined) { window.CHITIKA = { 'units' : [] }; };
		var unit = {"calltype":"async[2]","publisher":"ZPIELORD","width":160,"height":600,"sid":"Chitika Default"};
		var placement_id = window.CHITIKA.units.length;
		window.CHITIKA.units.push(unit);
		document.write('<div id="chitikaAdBlock-' + placement_id + '"></div>');
	}());
	</script>
	</div>
	<div id="rightAd">
	<script type="text/javascript">
	  ( function() {
		if (window.CHITIKA === undefined) { window.CHITIKA = { 'units' : [] }; };
		var unit = {"calltype":"async[2]","publisher":"ZPIELORD","width":160,"height":600,"sid":"Chitika Default"};
		var placement_id = window.CHITIKA.units.length;
		window.CHITIKA.units.push(unit);
		document.write('<div id="chitikaAdBlock-' + placement_id + '"></div>');
	}());
	</script>
	</div>
	<?php
	if (!$ua->is_mobile()) {
		ob_end_flush();
	}
?>
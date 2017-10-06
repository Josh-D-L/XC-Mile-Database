<?php
	if (session_status() !== PHP_SESSION_ACTIVE) {
		session_start();
	}
	setcookie("uname", "", time()-3600, "/");
	session_destroy();
	header('Location: /');
	exit;
 ?> 
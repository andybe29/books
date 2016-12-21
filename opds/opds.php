<?php
	if (isset($_GET['name']) and file_exists($_GET['name'])) {
		header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
		header('Last-Modified: ' . gmdate( 'D, d M Y H:i:s') . ' GMT');
		header('Cache-Control: no-cache, must-revalidate');
		header('Pragma: no-cache');
		header('Content-Type: text/xml; charset=utf-8');
		echo file_get_contents($_GET['name']);
	} else {
		header('HTTP/1.1 404 Not Found');
	}
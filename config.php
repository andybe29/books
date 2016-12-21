<?php
	$config = new stdClass;

	$config->OPDS = 'http://example.com/opds/';

	$config->Path = new stdClass;
	$config->Path->OPDS   = pathinfo(__FILE__, PATHINFO_DIRNAME) . '/opds/';
	$config->Path->Upload = pathinfo(__FILE__, PATHINFO_DIRNAME) . '/upload/';

	$config->Client = new stdClass;
	$config->Client->ID     = 'foo.apps.googleusercontent.com';
	$config->Client->Secret = 'bar';
	$config->Client->URL    = 'http://example.com/';

	$config->Project = new stdClass;
	$config->Project->ID     = 'some-id';
	$config->Project->Number = 'some-number';
	$config->Project->Email  = 'some-number-compute@developer.gserviceaccount.com';
	$config->Project->Folder = 'folder-id';

	$config->db = [
		'username' => 'uname',
		'passwd'   => 'upass',
		'dbname'   => 'dbase',
		'host'     => 'localhost'
	];
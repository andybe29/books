<?php
	setlocale(LC_ALL, 'ru_RU.UTF-8');
	require_once('config.php');

	header('Content-Type: text/html; charset=utf-8');

	session_start();

	if (isset($_SESSION['access_token']) and $_SESSION['access_token']) {
		require_once 'google-api-php-client/vendor/autoload.php';

		$client = new Google_Client();

		$client->setAuthConfig('client.secret.json');
		$client->addScope('https://www.googleapis.com/auth/drive');
		$client->setAccessToken($_SESSION['access_token']);

		if ($client->isAccessTokenExpired()) {
			unset($_SESSION['access_token']);
			header('Location: ' . filter_var($config->Client->URL . '/oauth2callback.php', FILTER_SANITIZE_URL));
			exit;
		}
	} else {
		header('Location: ' . filter_var($config->Client->URL . '/oauth2callback.php', FILTER_SANITIZE_URL));
		exit;
	}
?>
<!doctype html>
<html lang="en">
	<head>
		<meta charset="utf-8">
		<meta http-equiv="X-UA-Compatible" content="IE=edge">
		<meta name="viewport" content="width=device-width, initial-scale=1">

		<title>Books 2.0</title>

		<link rel="stylesheet" href="css/spectre.min.css">
		<link rel="stylesheet" href="css/jquery.fileupload.css">
		<style>
			#run {
				position   : fixed;
				width      : 100%;
				z-index    : 1500;
			}
		</style>
	</head>
	<body>
		<div id="run"><img src="run.gif"></div>
		<div class="container grid-960">
			<div class="columns">
				<div class="column col-12">
					<h4 class="text-bold">Books 2.0</h4>
				</div>
			</div>
			<div class="columns">
				<div class="column col-2">
					<button class="btn btn-block tooltip tooltip-bottom" data-action="gdrive" data-tooltip="upload 2 google drive">gdrive</button>
				</div>
				<div class="column col-2">
					<button class="btn btn-block tooltip tooltip-bottom" data-action="gdrive.dbase" data-tooltip="sync google drive 2 database">gdrive2dbase</button>
				</div>
				<div class="column col-2">
					<button class="btn btn-block tooltip tooltip-bottom" data-action="dbase.gdrive" data-tooltip="sync database 2 google drive">dbase2gdrive</button>
				</div>
				<div class="column col-2">
					<button class="btn btn-block tooltip tooltip-bottom" data-action="opds" data-tooltip="generate opds">opds</button>
				</div>
				<div class="column col-2">
					<button class="btn btn-block tooltip tooltip-bottom" data-action="books" data-tooltip="get books">books</button>
				</div>
				<div class="column col-2">
					<div class="btn btn-block fileinput-button">
						upload
						<input id="files" type="file" name="data" multiple>
					</div>
				</div>
			</div>
			<div class="columns">
				<div id="progress" class="column col-12"></div>
			</div>
			<div class="columns">
				<div id="log" class="column col-12 text-bold toast"></div>
			</div>
			<ul id="letters" class="pagination text-center"></ul>
			<table class="table table-striped table-hover">
				<tbody id="books">
				</tbody>
			</table>
			<script src="https://ajax.googleapis.com/ajax/libs/jquery/1/jquery.min.js"></script>
			<script type="text/javascript" src="js/jquery.ui.widget.js"></script>
			<script type="text/javascript" src="js/jquery.fileupload.js"></script>
			<script type="text/javascript" src="js/books.js?<?php echo time() ?>"></script>
		</div>
	</body>
</html>
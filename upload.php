<?php
	setlocale(LC_ALL, 'ru_RU.UTF-8');
	require_once('config.php');

	if (isset($_SERVER['PHP_AUTH_USER']) and isset($_SERVER['PHP_AUTH_PW'])) {
		if ($_SERVER['PHP_AUTH_USER'] == $config->auth->user and $_SERVER['PHP_AUTH_PW'] == $config->auth->pw) {
			header('Content-Type: text/html; charset=utf-8');
		} else die();
	} else die();

	$ret = ['ok' => false, 'err' => ''];

	function post($val) {
		return (is_scalar($val)) ? trim(strip_tags($val)) : $val;
	}

	if ($do = isset($_FILES['data'])) {
		$post = array_merge(array_map('post', $_POST), array_map('post', $_FILES['data']));
	}
	if (!$do) goto foo;

	if (!empty($post['error'])) {
		$ret['err'] = $post['error'];
		goto foo;
	}

	$post['dst'] = $config->Path->Upload . $post['name'];

	if (move_uploaded_file($post['tmp_name'], $post['dst'])) {
		chmod($post['dst'], 0644);
		$ret['ok'] = true;
	}

	foo:
	$ret['fname'] = isset($post['name']) ? $post['name'] : '';
	$ret['type']  = $post['type'];
	if ($ret['ok']) unset($ret['err']);

	header('Content-Type: application/json');
	echo json_encode([$ret]);
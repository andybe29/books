<?php
	if (array_key_exists('HTTP_X_REQUESTED_WITH', $_SERVER) and strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
	} else die();

	if (ob_get_length()) ob_clean();

	setlocale(LC_ALL, 'ru_RU.UTF-8');
	require_once('config.php');

	header('Content-Type: text/html; charset=utf-8');

	$ret = ['ok' => false];

	session_start();

	if (isset($_SESSION['access_token']) and $_SESSION['access_token']) {
		require_once 'google-api-php-client/vendor/autoload.php';

		$client = new Google_Client();

		$client->setAuthConfig('client.secret.json');
		$client->addScope('https://www.googleapis.com/auth/drive');
		$client->setAccessToken($_SESSION['access_token']);

		if ($client->isAccessTokenExpired()) {
			unset($_SESSION['access_token']);
			$ret['err'] = 'access token expired';
			goto foo;
		}
	} else {
		$ret['err'] = 'access denied';
		goto foo;
	}

	$func = function($val) {
		return (is_scalar($val)) ? trim(strip_tags($val)) : $val;
	};
	$post = array_map($func, $_POST);

	require_once 'simpleMySQLi.class.php';
	require_once 'strUtils.class.php';

	$sql = new simpleMySQLi($config->db, pathinfo(__FILE__, PATHINFO_DIRNAME));

	if ($post['action'] == 'books') {
		$func = function($r) {
			foreach ($r as $key => $val) $r[$key] = in_array($key, ['count', 'id', 'number']) ? (int)$val : $val;
			return $r;
		};

		if (array_key_exists('author', $post)) {
			$do = (isset($post['author']) and preg_match('/^[a-z0-9]{32}$/', $post['author']));
			if (!$do) goto foo;

			$sql->str   = [];
			$sql->str[] = 'select id, file, title, size from books20';
			$sql->str[] = 'where md5(upper(author))=' . $sql->varchar($post['author']) . ' order by title';

			$u = $sql->execute() ? $sql->all() : false;
			$sql->free();

			if ($u === false) {
				log2file('an error occurred: ' . ($ret['err'] = $sql->err));
				goto foo;
			}

			$ret['books'] = array_map($func, $u);
		} else if (array_key_exists('letter', $post)) {
			$post = array_map('intval', $post);
			$do = (isset($post['letter']) and $post['letter'] >= 53392 and $post['letter'] <= 53423);
			if (!$do) goto foo;

			$sql->str   = [];
			$sql->str[] = 'select distinct author, md5(upper(author)) as hash, count(title) as count from books20';
			$sql->str[] = 'where ord(substr(upper(author), 1, 1))=' . $post['letter'] . ' group by author';

			$u = $sql->execute() ? $sql->all() : false;
			$sql->free();

			if ($u === false) {
				log2file('an error occurred: ' . ($ret['err'] = $sql->err));
				goto foo;
			}

			$ret['authors'] = array_map($func, $u);
		} else {
			# letters
			$s   = [];
			$s[] = 'substr(upper(author), 1, 1) as letter';
			$s[] = 'ord(substr(upper(author), 1, 1)) as number';
			$s[] = 'count(distinct author) as count';

			$sql->str = 'select ' . implode(', ', $s) . ' from books20 group by letter';
			$u = $sql->execute() ? $sql->all() : false;
			$sql->free();

			if ($u === false) {
				log2file('an error occurred: ' . ($ret['err'] = $sql->err));
				goto foo;
			}

			$ret['letters'] = array_map($func, $u);
		}

		$ret['ok'] = true;
	} else if ($post['action'] == 'dbase.gdrive' or $post['action'] == 'gdrive.dbase') {
		# dbase.gdrive: sync from database to Google Drive
		# gdrive.dbase: sync from Google Drive to database
		$service = new Google_Service_Drive($client);

		$w   = [];
		$w[] = $sql->varchar($config->Project->Folder) . ' in parents';
		$w[] = 'mimeType contains "zip" and trashed=false';

		$params = [];
		$params['fields']    = 'nextPageToken, files(id, name, size)';
		$params['orderBy']   = 'name';
		$params['pageSize']  = 999;
		$params['pageToken'] = null;
		$params['q']         = implode(' and ', $w);

		$books = [];

		do {
			try {
				$files = $service->files->listFiles($params);

				foreach ($files as $r) {
					$books[] = (object)['id' => $r->id, 'name' => $r->name, 'size' => strUtils::fsize($r->size)];
				}

				$params['pageToken'] = $files->nextPageToken;
			} catch (Exception $e) {
				log2file('an error occurred: ' . ($ret['err'] = $e->getMessage()));
				goto foo;
			}
		} while (!empty($params['pageToken']));

		$books = array_filter($books, function($r) { return substr($r->name, -8) === '.fb2.zip'; });
		$books = array_values($books);

		if (!$books) {
			log2file('an error occurred: ' . ($ret['err'] = 'no books found'));
			goto foo;
		}

		$func = function($r) {
			$x = array_map('trim', explode('. ', str_replace('.fb2.zip', '', $r->name)));

			$r->author = array_shift($x);
			$r->title  = implode('. ', $x);
			$r->hash   = md5($r->author . $r->title);
			return $r;
		};

		$books = array_map($func, $books);

		$ret['books'] = [];

		if ($post['action'] == 'dbase.gdrive') {
			$sql->str = 'select * from books20 order by id';
			$recs = $sql->execute() ? $sql->all() : false;
			$sql->free();

			if ($recs === false) {
				log2file('an error occurred: ' . ($ret['err'] = $sql->err));
				goto foo;
			} else if (!$recs) {
				log2file('an error occurred: ' . ($ret['err'] = 'no records found'));
				goto foo;
			}

			foreach ($recs as $rec) {
				if (array_filter($books, function($r) use ($rec) { return $r->id == $rec['file']; })) continue;

				$sql->str = 'delete from books20 where id=' . $rec['id'];
				$sql->execute();

				if ($sql->rows) {
					log2file('books20 deleting ' . implode(', ', strUtils::assoc2plain($r)) . ' : ok');
					$ret['books'][] = (int)$r['id'];
				} else {
					log2file('an error occurred: ' . ($ret['err'] = $sql->err));
					goto foo;
				}
			}

			$ret['ok'] = true;
		} else foreach ($books as $r) {
			$w   = [];
			$w[] = 'file=' . $sql->varchar($r->id);
			$w[] = 'hash=' . $sql->varchar($r->hash);

			$sql->str = 'select * from books20 where ' . $sql->_and($w);
			$sql->execute();
			$sql->free();

			if ($sql->err) {
				log2file('an error occurred: ' . ($ret['err'] = $sql->err));
				goto foo;
			} else if ($sql->rows) continue;

			# gdrive.book not found
			log2file($r->name . ' not found');

			$data = [];
			$data['file']   = $sql->varchar($r->id);
			$data['author'] = $sql->varchar($r->author);
			$data['title']  = $sql->varchar($r->title);
			$data['size']   = $sql->varchar($r->size);
			$data['hash']   = $sql->varchar($r->hash);
			$data['dtime']  = $sql->varchar($sql->now());

			if ($sql->insert('books20', $data)) {
				$ret['books'][] = $r->name;
				log2file('inserting file ' . $r->name . ': ok');
			} else if ($sql->err) {
				log2file('an error occurred: ' . ($ret['err'] = $sql->err));
				goto foo;
			}
		}

		$ret['ok'] = true;
	} else if ($post['action'] == 'gdrive.delete') {
		$do = (isset($post['id']) and ($post['id'] = (int)$post['id']) > 0);
		if (!$do) goto foo;

		$sql->str = 'select file, concat_ws(". ", author, title) as name from books20 where id=' . $post['id'];
		$rec = $sql->execute() ? $sql->assoc() : false;
		$sql->free();

		if ($rec === false) {
			log2file('an error occurred: ' . ($ret['err'] = $sql->err));
			goto foo;
		} else if ($sql->rows == 0) {
			log2file('an error occurred: ' . ($ret['err'] = 'books20.id=' . $post['id'] . ' not found'));
			goto foo;
		}

		# searching for books
		$books = [];

		$service = new Google_Service_Drive($client);

		$w   = [];
		$w[] = 'name contains ' . str_replace("\'", "'", $sql->varchar($rec['name']));
		$w[] = 'mimeType contains "zip" and trashed=false';

		$params = [];
		$params['fields']    = 'files(id, name)';
		$params['orderBy']   = 'name';
		$params['pageSize']  = 999;
		$params['q']         = implode(' and ', $w);

		try {
			$files = $service->files->listFiles($params);

			foreach ($files as $r) {
				$books[] = (object)['id' => $r->id, 'name' => $r->name];
			}
		} catch (Exception $e) {
			log2file('an error occurred: ' . ($ret['err'] = $e->getMessage()));
			goto foo;
		}

		$books = array_filter($books, function($obj) use($rec) { return $obj->id == $rec['file']; });

		if (!$books) {
			log2file('an error occurred: ' . ($ret['err'] = 'fileId ' . $sql->varchar($rec['file']) . ' not found'));
			goto foo;
		}

		try {
			$service->files->delete($rec['file']);
			log2file($rec['name'] . ' (' . $rec['file'] . ') deleted from Google Drive');
		} catch (Exception $e) {
			log2file('an error occurred: ' . ($ret['err'] = $e->getMessage()));
			goto foo;
		}

		$sql->str = 'delete from books20 where id=' . $post['id'];

		if (false !== ($ret['ok'] = $sql->execute())) {
			log2file('books20.id=' . $post['id'] . ' deleted');
		} else {
			log2file('an error occurred: ' . ($ret['err'] = $sql->err));
		}
	} else if ($post['action'] == 'gdrive.upload') {
		if (($ret['left'] = count($data = glob($config->Path->Upload . '*.fb2'))) == 0) {
			$ret['err'] = 'nothing 2 upload';
			goto foo;
		}

		$service = new Google_Service_Drive($client);

		$foo = new stdClass;

		$foo->fb2   = $data[0];
		$foo->fname = basename($foo->fb2);
		$foo->zip   = $foo->fb2 . '.zip';
		$foo->zname = basename($foo->zip);

		$zip = new ZipArchive;

		$zip->open($foo->zip, ZipArchive::CREATE | ZipArchive::OVERWRITE);
		$zip->addFile($foo->fb2, iconv('utf-8', 'cp866', $foo->fname));
		$zip->close();

		# find existsings
		$found = null;

		$w   = [];
		$w[] = 'name=' . str_replace("\'", "'", $sql->varchar($foo->zname));
		$w[] = $sql->varchar($config->Project->Folder) . ' in parents';
		$w[] = 'trashed=false';

		$params = [];
		$params['fields']    = 'files(id)';
		$params['q']         = implode(' and ', $w);

		try {
			$files = $service->files->listFiles(['q' => implode(' and ', $w)]);

			foreach ($files as $r) $found = $r->id;
		} catch (Exception $e) {
			log2file('an error occurred: ' . ($ret['err'] = $e->getMessage()));
			goto foo;
		}

		if ($found) {
			# update
			$meta = new Google_Service_Drive_DriveFile(['name' => $foo->zname]);

			$params = [];
			$params['data']       = file_get_contents($foo->zip);
			$params['mimeType']   = 'application/zip';
			$params['uploadType'] = 'media';
			$params['fields']     = 'id, name, size';

			try {
				$r = $service->files->update($found, $meta, $params);
				log2file('updating file ' . $foo->zname . ': ' . $r->id);
				$ret['ok'] = true;
			} catch (Exception $e) {
				log2file('an error occurred: ' . ($ret['err'] = $e->getMessage()));
				goto foo;
			}
		} else {
			# insert
			$params = [];
			$params['name']    = $foo->zname;
			$params['parents'] = [$config->Project->Folder];

			$meta = new Google_Service_Drive_DriveFile($params);

			$params = [];
			$params['data']       = file_get_contents($foo->zip);
			$params['mimeType']   = 'application/zip';
			$params['uploadType'] = 'media';
			$params['fields']     = 'id, name, size';

			try {
				$r = $service->files->create($meta, $params);
				log2file('inserting file ' . $foo->zname . ': ' . $r->id);
				$ret['ok'] = true;
			} catch (Exception $e) {
				log2file('an error occurred: ' . ($ret['err'] = $e->getMessage()));
				goto foo;
			}
		}

		if ($ret['ok']) {
			# anyone can view
			$service->getClient()->setUseBatch(true);

			$batch = $service->createBatch();
			$meta  = new Google_Service_Drive_Permission(['type' => 'anyone', 'role' => 'reader']);
			$req   = $service->permissions->create($r->id, $meta, ['fields' => 'id']);
			$batch->add($req, 'anyone');
			$results = $batch->execute();

			foreach ($results as $result) {
				if ($result instanceof Google_Service_Exception) {
					log2file('an error occurred: ' . $result);
				}
			}

			$service->getClient()->setUseBatch(false);

			$r->name = str_replace('.fb2.zip', '', $r->name);
			$r->name = array_map('trim', explode('. ', $r->name));

			$obj = new stdClass;
			$obj->file = $r->id;
			$obj->author = array_shift($r->name);
			$obj->author = trim($obj->author);
			$obj->title  = trim(implode('. ', $r->name));
			$obj->size   = strUtils::fsize($r->size);
			$obj->hash   = md5($obj->author . $obj->title);

			$sql->str = 'delete from books20 where file=' . $sql->varchar($obj->file);
			$sql->execute();

			$data = ['dtime' => $sql->varchar($sql->now())];
			foreach ($obj as $key => $val) {
				$data[$key] = $sql->varchar($val);
			}

			if ($ret['ok'] = $sql->insert('books20', $data) ? true : false) {
				unlink($foo->fb2); unlink($foo->zip);
				$ret['left'] --;
			} else {
				log2file('an error occurred: ' . $sql->err);
				unset($ret['left']);
			}
		}
	} else if ($post['action'] == 'opds') {
		foreach (glob($config->Path->OPDS . '*.xml') as $r) unlink($r);

		$s   = [];
		$s[] = 'substr(upper(author), 1, 1) as letter';
		$s[] = 'ord(substr(upper(author), 1, 1)) as number';
		$s[] = 'count(distinct author) as count';

		$sql->str = 'select ' . implode(', ', $s) . ' from books20 group by letter';

		$u = $sql->execute() ? $sql->all() : false;
		$sql->free();

		if ($u === false) {
			log2file('an error occurred: ' . ($ret['err'] = $sql->err));
			goto foo;
		} else if (!$u) {
			log2file('an error occurred: ' . ($ret['err'] = 'no records found'));
			goto foo;
		}

		$letters = [];

		$xml   = [];
		$xml[] = '<?xml version="1.0" encoding="utf-8"?>';
		$xml[] = '<feed xmlns="http://www.w3.org/2005/Atom" xmlns:dc="http://purl.org/dc/elements/1.1/">';
		$xml[] = '<title>Books 2.0</title>';
		$xml[] = '<updated>' . $sql->now() . '</updated>';
		foreach ($u as $r) {
			$letters[] = $r;
			$xml[] = '<entry>';
			$xml[] = '<title>' . $r['letter'] . '</title>';
			$xml[] = '<content type="text">' . $r['count'] . ' authors</content>';
			$xml[] = '<updated>' . $sql->now() . '</updated>';
			$xml[] = '<link type="application/atom+xml" href="' . $config->OPDS . $r['number'] . '.xml">' . $r['letter'] . '</link>';
			$xml[] = '</entry>';
		}
		$xml[] = '</feed>';

		if ($fd = fopen($config->Path->OPDS . 'index.xml', 'w')) {
			fwrite($fd, implode('', $xml));
			fclose($fd);
		} else {
			log2file('an error occured: ' . ($ret['err'] = 'index.xml saving failed'));
			goto foo;
		}

		foreach ($letters as $x) {
			$sql->str   = [];
			$sql->str[] = 'select distinct author, count(title) as count from books20';
			$sql->str[] = 'where ord(substr(upper(author), 1, 1))=' . $x['number'] . ' group by author';

			$u = $sql->execute() ? $sql->all() : false;
			$sql->free();

			if ($u === false) {
				log2file('an error occurred: ' . ($ret['err'] = $sql->err));
				goto foo;
			} else if (!$u) {
				log2file('an error occurred: ' . ($ret['err'] = 'no records for ' . $x['letter'] . ' found'));
				goto foo;
			}

			$authors = [];

			$xml   = [];
			$xml[] = '<?xml version="1.0" encoding="utf-8"?>';
			$xml[] = '<feed xmlns="http://www.w3.org/2005/Atom" xmlns:dc="http://purl.org/dc/elements/1.1/">';
			$xml[] = '<title>' . $x['letter'] . '</title>';
			foreach ($u as $r) {
				$r['hash'] = md5(strUtils::str2upper($r['author']));
				$authors[] = $r;

				$xml[] = '<entry>';
				$xml[] = '<title>' . $r['author'] . '</title>';
				$xml[] = '<content type="text">' . $r['count'] . ' books</content>';
				$xml[] = '<updated>' . $sql->now() . '</updated>';
				$xml[] = '<link type="application/atom+xml" href="' . $config->OPDS . $x['number'] . '.' . $r['hash'] . '.xml">' . $r['author'] . '</link>';
				$xml[] = '</entry>';
			}
			$xml[] = '</feed>';

			if ($fd = fopen($config->Path->OPDS . ($fname = $x['number'] . '.xml'), 'w')) {
				fwrite($fd, implode('', $xml));
				fclose($fd);
			} else {
				log2file('an error occurred: ' . ($ret['err'] = $fname . ' saving failed'));
				goto foo;
			}

			foreach ($authors as $a) {
				$sql->str   = [];
				$sql->str[] = 'select title, file, size from books20';
				$sql->str[] = 'where author=' . $sql->varchar($a['author']) . ' order by title';

				$u = $sql->execute() ? $sql->all() : false;
				$sql->free();

				if ($u === false) {
					log2file('an error occurred: ' . ($ret['err'] = $sql->err));
					goto foo;
				} else if (!$u) {
					log2file('an error occurred: ' . ($ret['err'] = 'no records for ' . $a['author'] . ' found'));
					goto foo;
				}

				$xml   = [];
				$xml[] = '<?xml version="1.0" encoding="utf-8"?>';
				$xml[] = '<feed xmlns="http://www.w3.org/2005/Atom" xmlns:dc="http://purl.org/dc/elements/1.1/">';
				$xml[] = '<title>' . $a['author'] . '</title>';
				foreach ($u as $r) {
					$xml[] = '<entry>';
					$xml[] = '<title>' . $r['title'] . '</title>';
					$xml[] = '<content type="text">' . $r['size'] . '</content>';
					$xml[] = '<author><name>' . $a['author'] . '</name></author>';
					$xml[] = '<link type="application/fb2+zip" href="https://drive.google.com/uc?export=download&amp;id=' . $r['file'] . '" title="fb2"/>';
					$xml[] = '<updated>' . $sql->now() . '</updated>';
					$xml[] = '</entry>';
				}
				$xml[] = '</feed>';

				if ($fd = fopen($config->Path->OPDS . ($fname = $x['number'] . '.' . $a['hash'] . '.xml'), 'w')) {
					fwrite($fd, implode('', $xml));
					fclose($fd);
				} else {
					log2file('an error occurred: ' . ($ret['err'] = $fname . ' saving failed'));
					goto foo;
				}
			}
		}

		$ret['ok'] = true;
	}

	foo:
	if ($ret['ok']) unset($ret['err']); else if (!isset($ret['err'])) {
		$ret['err'] = 'system error';
	}

	header('Content-Type: application/json');
	echo json_encode($ret);

	function log2file($what) {
		global $post;
		error_log(date('d.m.Y H:i:s') . ' : ' . $post['action'] . ' => ' . $what . PHP_EOL, 3, 'logs/log');
	}
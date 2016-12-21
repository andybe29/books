<?php
# simpleMySQLi
class simpleMySQLi {

	public $str = '';
	public $log = false;

	private $conn_id = 0;
	private $err     = '';
	private $id      = 0;
	private $path    = '';
	private $rows    = 0;
	private $res     = 0;

	public function __construct($db, $root = false) {
		if ($this->conn_id = mysqli_connect($db['host'], $db['username'], $db['passwd'], $db['dbname'])) {
			mysqli_set_charset($this->conn_id, 'utf8');
			$this->path = realpath($root ? $root : $_SERVER['DOCUMENT_ROOT']) . '/logs';
			return true;
		}
		return false;
	}

	public function __destruct() {
		mysqli_close($this->conn_id);
	}

	public function __get($key) {
		return in_array($key, ['err', 'id', 'path', 'rows']) ? $this->$key : null;
	}

	public function __debugInfo() {
		return [
			'str'  => $this->str,
			'err'  => $this->err,
			'id'   => $this->id,
			'path' => $this->path,
			'rows' => $this->rows
		];
	}

	public function all($num = false) {
		if (function_exists('mysqli_fetch_all')) {
			$u = mysqli_fetch_all($this->res, $num ? MYSQLI_NUM : MYSQLI_ASSOC);
			$u = array_map(['self', 'strip'], $u);
		} else {
			$u = [];
			while ($r = $num ? $this->fetch() : $this->assoc()) {
				$u[] = $r;
			}
		}

		return $u;
	}

	public function assoc() {
		$r = mysqli_fetch_assoc($this->res);
		return self::strip($r);
	}

	public function escape($str) {
		return mysqli_real_escape_string($this->conn_id, $str);
	}

	public function execute($log = false) {
		$this->err  = null;
		$this->rows = $this->id = 0;

		$this->str = trim(is_array($this->str) ? implode(' ', $this->str) : $this->str);
		$this->res = mysqli_query($this->conn_id, $this->str);

		if ($err_no = mysqli_errno($this->conn_id)) {

			$this->err = $err_no . ': ' . mysqli_error($this->conn_id);

		} else if (($pos = mb_stripos($this->str, 'delete')) === 0) {

			$this->rows = $this->get_rows();

		} else if (($pos = mb_stripos($this->str, 'insert')) === 0) {

			$this->id   = $this->last();
			$this->rows = $this->get_rows();

		} else if (($pos = mb_stripos($this->str, 'replace')) === 0) {

			$this->rows = $this->get_rows();

		} else if (($pos = mb_stripos($this->str, 'select')) === 0) {

			$this->rows = mysqli_num_rows($this->res);

		} else if (($pos = mb_stripos($this->str, 'update')) === 0) {

			$this->id   = $this->last();
			$this->rows = $this->get_rows();

		}

		if ($this->log or $log or $this->err) {
			$l   = array(PHP_EOL);
			$l[] = date('H:i:s');
			$l[] = $this->str;
			$l[] = $this->err;
			error_log(implode(PHP_EOL, $l), 3, $x = $this->path . '/mysql.' . date('Y.m.d') . '.log');
			chmod($x, 0644);
		}

		return $this->err ? false : true;
	}

	public function fetch() {
		$r = mysqli_fetch_array($this->res, MYSQLI_NUM);
		return self::strip($r);
	}

	public function free() {
		if (is_object($this->res) or is_array($this->res)) {
			mysqli_free_result($this->res);
		}
	}

	public function get_rows() {
		return mysqli_affected_rows($this->conn_id);
	}

	public function insert($table = '', $data = []) {
		if (!$data or !$table) return false;

		$this->str   = [];
		$this->str[] = 'insert into ' . $table;
		$this->str[] = '(' . implode(', ', array_keys($data)) . ')';
		$this->str[] = 'values (' . implode(', ', $data) . ')';
		return $this->execute() ? ($this->id ? $this->id : true) : false;
	}

	public function last() {
		return mysqli_insert_id($this->conn_id);
	}

	public function now($full = true) {
		return $full ? date('Y-m-d H:i:s') : date('Y-m-d');
	}

    public function update($table = '', $data = [], $where = [], $sep = 'and') {
        if (!$data or !$table) return false;

       	$this->str  = 'update ' . $table . ' set ' . implode(', ', self::_assoc2plain($data));
       	$this->str .= ($where) ? ' where ' . implode(' ' . $sep . ' ', $where) : '';

       	return $this->execute() ? $this->rows : false;
    }

	public function varchar($str = '') {
		return '"' . $this->escape($str) . '"';
	}

	static function strip($obj) {
		if (is_object($obj) or is_array($obj)) {
			foreach ($obj as $key => $val) {
				$obj[$key] = is_string($val) ? stripcslashes($val) : $val;
			}
		} else if (is_string($obj)) {
			$obj = stripcslashes($obj);
		}
		return $obj;
	}

	static function _assoc2plain($u = []) {
		$func = function($key, $val) { return $key . '=' . $val; };
		return array_map($func, array_keys($u), $u);
	}

	static function _and($u = []) {
		return implode(' and ', $u);
	}

	static function _or($u = []) {
		return implode(' or ', $u);
	}
}
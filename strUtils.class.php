<?php
class strUtils {
	public function assoc2plain($arr = []) {
		$func = function($key, $val) { return $key . '=' . $val; };
		return array_map($func, array_keys($arr), $arr);
	}

	public function chars($str) {
		return htmlspecialchars($str);
	}

	public function currency($x = 0, $decs = 2, $point = '.', $separator = ' ') {
		return ($x < 0 ? '- ' : '') . number_format(abs($x), $decs, $point, $separator);
	}

	public function date2str($dtime = 0) {
		$m  = [1 => ' января ', ' февраля ', ' марта ', ' апреля ', ' мая ', ' июня ', ' июля ', ' августа ', ' сентября ', ' октября ', ' ноября ', ' декабря '];
		$gd = getdate($dtime ? $dtime : time());

		return str_pad($gd['mday'], 2, '0', STR_PAD_LEFT)  . $m[$gd['mon']] . $gd['year'];
	}

	public function fsize($size) {
		$fsname = [' bytes', ' kb', ' mb', ' gb', ' tb', ' pb', ' eb', ' zb', ' yb'];
		$size = $size ? round($size / pow(1024, ($i = floor(log($size, 1024)))), 2) . $fsname[$i] : '0 bytes';
		return str_replace(',', '.', $size);
	}

	public function first($str) {
		return self::str2upper(mb_substr($str, 0, 1, 'utf-8'));
	}

	public function first2lower($str) {
		$len = mb_strlen($str, 'utf-8');
		$ret = self::str2lower(mb_substr($str, 0, 1, 'utf-8'));
		if ($len > 1) {
			$ret .= mb_substr($str, 1, $len - 1, 'utf-8');
		}
		return $ret;
	}

	public function first2upper($str) {
		$len = mb_strlen($str, 'utf-8');
		$ret = self::str2upper(mb_substr($str, 0, 1, 'utf-8'));
		if ($len > 1) {
			$ret .= mb_substr($str, 1, $len - 1, 'utf-8');
		}
		return $ret;
	}

	public function last($year = 0, $month = 0) {
		# возвращает unix timestamp последней секунды последнего дня заданного месяца и года
		# если месяц или год не заданы, берётся текущая дата
		if (!$year or !$month) {
			$d = getdate();
			$year  = $d['year'];
			$month = $d['mon'];
		}

		$udt = mktime(0, 0, 0, $month, 1, $year);

		return mktime(23, 59, 59, $month, date('t', $udt), $year);
	}

	public function monthes() {
		return [1 => 'январь', 'февраль', 'март', 'апрель', 'май', 'июнь', 'июль', 'август', 'сентябрь', 'октябрь', 'ноябрь', 'декабрь'];
	}

    public function plural($num, $arg0, $arg1, $arg2, $arg3, $prepend = false) {
		$result          = $prepend ? $num . ' ' . $arg0 : $arg0;
		$last_digit      = $num % 10;
		$last_two_digits = $num % 100;

		if ($last_digit == 1 and $last_two_digits != 11) {
			$result .= $arg1;
		} else if (($last_digit == 2 and $last_two_digits != 12) or ($last_digit == 3 and $last_two_digits != 13) or ($last_digit == 4 && $last_two_digits != 14)) {
			$result .= $arg2;
		} else {
			$result .= $arg3;
		}

		return $result;
	}

	public function str2arr($str) {
		return mb_strlen($str, 'utf-8') ? str_split($str) : [];
	}

	public function str2lat($str) {
		$iso = [' ' => '-', 'Є' => 'YE', 'І' => 'I', 'Ѓ' => 'G', 'і' => 'i', '№' => '#', 'є' => 'ye', 'ѓ' => 'g', 'А' => 'A', 'Б' => 'B', 'В' => 'V', 'Г' => 'G', 'Д' => 'D', 'Е' => 'E', 'Ё' => 'YO', 'Ж' => 'ZH', 'З' => 'Z', 'И' => 'I', 'Й' => 'J', 'К' => 'K', 'Л' => 'L', 'М' => 'M', 'Н' => 'N', 'О' => 'O', 'П' => 'P', 'Р' => 'R', 'С' => 'S', 'Т' => 'T', 'У' => 'U', 'Ф' => 'F', 'Х' => 'X', 'Ц' => 'C', 'Ч' => 'CH', 'Ш' => 'SH', 'Щ' => 'SHH', 'Ъ' => '', 'Ы' => 'Y', 'Ь' => '', 'Э' => 'E', 'Ю' => 'YU', 'Я' => 'YA', 'а' => 'a', 'б' => 'b', 'в' => 'v', 'г' => 'g', 'д' => 'd', 'е' => 'e', 'ё' => 'yo', 'ж' => 'zh', 'з' => 'z', 'и' => 'i', 'й' => 'j', 'к' => 'k', 'л' => 'l', 'м' => 'm', 'н' => 'n', 'о' => 'o', 'п' => 'p', 'р' => 'r', 'с' => 's', 'т' => 't', 'у' => 'u', 'ф' => 'f', 'х' => 'x', 'ц' => 'c', 'ч' => 'ch', 'ш' => 'sh', 'щ' => 'shh', 'ъ' => '', 'ы' => 'y', 'ь' => '', 'э' => 'e', 'ю' => 'yu', 'я' => 'ya', '«' => '', '»' => ''];
		return preg_replace('/[^a-z0-9-]/i', '', strtr(trim($str), $iso));
	}

	public function str2lower($str) {
		return mb_convert_case($str, MB_CASE_LOWER, 'utf-8');
	}

	public function str2title($str) {
		return mb_convert_case($str, MB_CASE_TITLE, 'utf-8');
	}

	public function str2upper($str) {
		return mb_convert_case($str, MB_CASE_UPPER, 'utf-8');
	}

	public function strip($str) {
        $str = htmlspecialchars($str, ENT_NOQUOTES);
        $str = str_replace("'", "&#039;", $str);
        $str = preg_replace("/&lt;([\/]{0,1})([a-zA-Z]{1})&gt;/", "<\\1\\2>", $str);
        return $str;
	}

	public function url2link($str) {
		return preg_replace('#http://([A-z0-9./-]+)#', '<a href="http://$1">$0</a>', $str);
	}

	public function utf2win($str) {
		return iconv('utf-8', 'windows-1251', $str);
	}

	public function win2utf($str) {
		return iconv('windows-1251', 'utf-8', $str);
	}
}
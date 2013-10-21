<?php

namespace Shell;

class Security {

	public static function rand($length = 12, $pool = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789") {
		$rand = "";
		for ($i = 0; $i < $length; $i++) {
			$rand .= $pool[rand(0, strlen($pool) - 1)];
		}
		return $rand;
	}

	public static function hash($password, $algorythm = "sha1") {
		$config = new Config();
		$salt = $config->get("salt");
		return hash($algorythm, $salt . $password);
	}

	public static function serialize($value) {
		$config = new Config();
		$salt = $config->get("salt");
		$q = Message::Query();
		$q = $q->SQL("SELECT AES_Encrypt('%s', '%s') as str;", $value, $salt);
		return base64_encode($q[0]->str);
	}

	public static function deserialize($value) {
		$value = base64_decode($value);
		$config = new Config();
		$salt = $config->get("salt");
		$q = Message::Query();
		$q = $q->SQL("SELECT AES_Decrypt('%s', '%s') as str;", $value, $salt);
		return $q[0]->str;
	}
}

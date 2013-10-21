<?php

namespace Shell;

class I18n {

	private	$local = null;
	private static $files = array();
	private static $lang = array();
	private static $instance = null;

	public function __construct() {
		$this->config = new Config;
		$this->local = $this->config->local;
		setlocale(LC_ALL, $this->local);
	}

	public static function getInstance() {
		if (self::$instance == null) {
			self::$instance = new I18n();
		}
		return self::$instance;
	}

	public function getLocal() {
		return $this->local;
	}

	public function addFile($file) {
		$xml = $this->realAddFile($file);
		if (is_array($xml)) {
			self::$lang = array_merge($xml, self::$lang);
			if (isset($xml["@attributes"])) {
				if (isset($xml["@attributes"]["extends"])) {
					$this->addFile($xml["@attributes"]["extends"]);
				}
			}
		}
	}

	private function realAddFile($file) {
		$config = new Config;
		if (is_numeric(strpos($file, "%LOCAL"))) {
			$file = str_replace("%LOCAL", $config->get("local"), $file);			
		}
		if (!is_numeric(strpos($file, "/")) && !is_numeric(strpos($file, ".xml"))) {
			$file = sprintf($config->get("i18nXmlSchema"), $config->get("path"), $config->get("local"), $file);
		}
		if (file_exists($file) == false) {
			$file = sprintf("%s/%s", $config->get("path"), $file);
		}

		if (in_array($file, self::$files)) {
			return;
		}
		if (!is_file($file)) {
			$i18n = I18n::getInstance();
			Log::write($i18n->getText("file_not_found", $file));
			return false;
		}
		self::$files[] = $file;

		$lang = array();
		$xml = simplexml_load_string(file_get_contents($file));
		if ($xml) {
			foreach ($xml as $tag => $value) {
				$lang[$tag] = (string) $this->removeTag($value, $tag);
			}
		}

		#return (array) simplexml_load_file($file);
		return $lang;
	}

	public function removeTag($xml, $tag) { // hack
		$search = array(sprintf("<%s>", $tag), sprintf("</%s>", $tag));
		return str_replace($search, "", $xml->asXML());
	}

	public function getText() {
		$i = func_num_args();
		if ($i < 1) return;
		$args = func_get_args();

		if (isset(self::$lang[$args[0]])) {
			if ($i >= 2) {
				$str = self::$lang[$args[0]];
				unset($args[0]);
				return (string) vsprintf($str, $args);
			} else {
				return (string) self::$lang[$args[0]];
			}
		}
	}

}

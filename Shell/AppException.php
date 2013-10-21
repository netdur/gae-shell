<?php

namespace Shell;

class AppException extends \Exception {
	
	public static function errorHandler($code, $string, $file, $line) {
		$config = new Config;
		$i18n 	= I18n::getInstance();
		$error	= $i18n->getText("generic_error_syntax", $line, $file, $string);

	    switch ($code) {
			case E_USER_ERROR:
				if ($config->development === true) {
					Log::write($error);
				}
			break;
			default:
	    		if ($config->development === true) {
	    			Log::write($error, Log::ERR);
				} else {
					if ($config->log != false) {
						error_log($error, 3, $config->log);
					}
				}
			break;
		}
	}

}
<?php

namespace Shell;

class Request {

	public static $_PUT = [];

	public function getPost() {
		$parameters = array();
		foreach ($_POST as $key => $value) {
			$parameters[$key] = addslashes($value);
		}
		return $parameters;
	}
	
	public function getArguments() {
		$config = new Config();
		$args = array();

		#console or http!?
		if (empty($_SERVER["HTTP_USER_AGENT"])) {
			$request = $this->getConsoleOptions();
			$config->development = true;
			$config->console = true;
		} else {
			$request = $this->getHttpOptions();
		}

		$uri = explode("?", $_SERVER["REQUEST_URI"]);
		$uri = $uri[0];
		foreach ($config->get("urls") as $pattern => $controller) {
			$pattern = sprintf("/%s/", str_replace("/", "\/", $pattern));
			if (preg_match($pattern, $uri, $matches)) {
				$config->set("mainClass", $controller);
				for ($i = 1; $i < count($matches); $i++) {
					$args[] = urldecode($matches[$i]);
				}
				break;
			}
		}
		$config->set("mainMethod", $_SERVER["REQUEST_METHOD"]);
		if ($_SERVER["REQUEST_METHOD"] == "PUT") {
			$input = file_get_contents("php://input");
			parse_str($input, $put);
			self::$_PUT = $put;
		}

		//$args = explode("?", $_SERVER["REQUEST_URI"]);
		//$args = explode("/", $args[0]);

		return array_filter($args);
	}
	
	private function getConsoleOptions() {
		return (array) array_slice($_SERVER["argv"], 1);
	}

	private function getHttpOptions() {
		$req = explode("?", $_SERVER["REQUEST_URI"]);
		$req = $req[0];

		$self = explode("index.php", $_SERVER["PHP_SELF"]);
		$self = $self[0];

		$req = str_replace($self, "", $req);

		return explode("/", $req);
	}

}

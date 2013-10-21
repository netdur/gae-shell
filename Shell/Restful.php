<?php

namespace Shell;

class Restful {

	public $lang = "en";
	public $key = null;
	public $model = null;
	public $models = [];
	public $data = [];
	public $results = [];
	public $methods = ["GET", "POST", "PUT", "DELETE", "OPTIONS"];
	public $disallowed = [];
	public $headers = ["X-CSRF-Token"];
	public $listing = true;
	
	public function api() {
		$this->results = [];
		$this->data = ["error" => 0, "code" => 200, "message" => "OK"];

		if (!method_exists($this, "authCheck")) {
			return true;
		}

		if (isset($_SERVER["key"])) {
			if ($this->authCheck($_SERVER["key"])) {
				return true;
			}
		} else {
			$this->data["error"] = 1;
			$this->data["code"] = 401;
			$this->data["message"] = "APIKeyError";
			return false;
		}
	}

	public function DELETE($id = false) {
		if ($this->api() == false) {
			$this->respond();
			return;
		}
		if ($id) {
			$model = new $this->model();
			$model->id = $id;
			$model->delete();
		}
		$this->respond();
	}

	public function POST($id = false) {
		if ($this->api() == false) {
			$this->respond();
			return;
		}
		if ($id) {
			$model = new $this->model();
			foreach ($_POST as $k => $v) {
				$model->{$k} = $v;
			}
			$model->save();
		}
		$this->respond();
	}

	public function PUT($id = false) {
		if ($this->api() == false) {
			$this->respond();
			return;
		}
		if ($id) {
			$model = new $this->model();
			$model->id = $id;
			$_PUT = Shell\Request::$_PUT;
			foreach ($_PUT as $k => $v) {
				$model->{$k} = $v;
			}
			$model->save();
		}
		$this->respond();
	}

	public function OPTIONS($id = false) {
		header("Access-Control-Allow-Methods: %s", implode(", ", $this->methods));
		header("Access-Control-Allow-Headers: %s", implode(", ", $this->headers));
	}

	public function _filter($array) {
		$result = [];
		foreach ($array as $k => $v) {
			if ($this->filter($k, $v)) {
				$result[$k] = $v;
			}
		}
		return $result;
	}

	public function filter($key, $value) {
		return true;
	}

	public function GET($id = false) {
		if ($this->api() == false) {
			$this->respond();
			return;
		}

		$fields = $_GET["-fields"];
		if ($fields == null) {
			$fields = "*";
		}

		$count = $_GET["-count"];
		if ($count == null) {
			$count = 20;
		}

		$q = false;

		if ($id) {
			if (strpos($id, ",") !== false) {
				$q = new Query($this->model);
				$q->find(["id" => explode(",", $id)]);
			} else {
				$model = new $this->model();
				$model->id = $id;
				$model->fetch($fields);
				if ($model->id) {
					$result = $model->getHash();
					$result = $this->_filter($result);
					$this->results[] = $result;
				}
			}
		} else { // listing
			if ($this->listing == true) {
				$q = new Query($this->model);
			} else {
				$this->data["error"] = 1;
				$this->data["code"] = 403;
				$this->data["message"] = "Forbidden";
			}
		}

		if ($q) {
			if (isset($_GET["-cursor"])) {
				$q->setCursor($_GET["-cursor"]);
			}
			$models = $q->fetch($fields, $count);
			foreach ($models as $model) {
				$result = $model->getHash();
				$result = $this->_filter($result);
				$this->results[] = $result;
			}
			// $this->data["sql"] = $q->getSQL();
			$this->data["next"] = $q->next;
			$this->data["cursor"] = $q->cursor;
		}
		
		$this->respond();
	}
	
	public function respond() {
		header("Content-type: application/json");
		$this->data["count"] = count($this->results);
		$this->data["results"] = $this->results;		
		echo json_encode($this->data);
	}
}
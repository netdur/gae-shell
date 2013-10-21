<?php

namespace Shell;

App::import("Debug");

class Form {

	protected static $id = 1001;

	private $rows = array();
	private $hash = array();
	private $form;

	public $method = "post";
	public $i18n = false;
	public $ucfirst = true;
	public $submit = null;
	public $stripHTML = true;
	public $action;

	public function __construct() {
		$config = Config::getInstance();
		if ($config->get("isHTML") === true) {
			$this->form = new Node("<form/>");
		}
		if ($config->get("mainMethod") == "POST") {
			$this->verify();
		}
	}

	public function getFormDOM() {
		return $this->form;
	}

	public function verify() {
		$config = Config::getInstance();
		foreach ($_FILES as $key => $value) {
			$type = sprintf("input_%s", $key);
			if (isset($_POST[$type])) {
				$this->hash[$key] = $value;
				if ((int) $_POST[$type] != 0) {
					if ($value["size"] > (int) $_POST[$type] * 1024) {
						return false;
					}
				}
				$file = sprintf("%supload/%s_%s",
					$config->get("cache"),
					str_replace("/tmp/", "", $value["tmp_name"]), $value["name"]);
				move_uploaded_file($value["tmp_name"], $file);
				$this->hash[$key]["path"] = $file;
			}
		}
		foreach ($_POST as $key => $value) {
			$type = sprintf("input_%s", $key);
			if (isset($_POST[$type])) {
				$type = $_POST[$type];
				switch ($type) {
					case "password":
					case "textarea":
					case "field":
					case "select":
					case "text":
						$this->hash[$key] =
							($this->stripHTML == true)
							? strip_tags($value) : $value;
						break;
					case "username":
						$this->hash[$key] =
							(Debug::assert($value, Debug::USERNAME))
							? $value : false; 
						break;
					case "email":
						$this->hash[$key] =
							(Debug::assert($value, Debug::EMAIL))
							? $value : false; 
						break;
					case "number":
						$this->hash[$key] =
							(Debug::assert($value, Debug::NUMBER))
							? $value : false; 
						break;
					case "url":
						$this->hash[$key] = (Debug::assert($value, Debug::URL)) ? $value : false; 
						break;
					case "checkbox":
						$this->hash[$key] = $value;
						break;
					case "radio":
						$this->hash[$key] = $value;
						break;
					case "hidden":
						$this->hash[$key] = $value;
						break;
				}
			}
		}
	}

	public function __set($key, $value) {
		$hidden = false;
		$row = $this->rows[$value];
		$input = $row["dd"]->query("input[type=text]");
		if (!$input) $input = $row["dd"]->query("input[type=password]");
		if (!$input) $input = $row["dd"]->query("input[type=file]");
		if (!$input) $input = $row["dd"]->query("select");
		if (!$input) $input = $row["dd"]->query("textarea");
		if (!$input) {
			$input = $row["dd"]->query("input[type=hide]");
			$hidden = ($input) ? true : false;
		}
		if ($input) {
			$input->set("name", $key);
		} else if ($row["dd"]->query("input[type=checkbox]")) {
			$checkboxs = $row["dd"]->queryAll("input[type=checkbox]");
			$name = sprintf("%s[]", $key);
			foreach ($checkboxs as $checkbox) {
				$checkbox->set("name", $name);
			}
		} else if ($row["dd"]->query("input[type=radio]")) {
			$radios = $row["dd"]->queryAll("input[type=radio]");
			foreach ($radios as $radio) {
				$radio->set("name", $key);
			}
		}
		$row["dd"]->query("input[type=hidden]")->set("name", sprintf("input_%s", $key));
		$label = $row["dt"]->query("label");
		if ($this->i18n == true) {
			$label->set("lang", $key);
		} else {
			$text = ($this->ucfirst == true) ? ucfirst($key) : $key;
			$label->setValue(str_replace("_", " ", $text));
		}
		$this->hash[$key] = $value;
		if ($hidden) {
			$label->remove();
			$input->set("type", "hidden");
		}
	}

	public function __get($key) {
		if (isset($this->hash[$key])) {
			return $this->hash[$key];
		} else {
			return null;
		}
	}

	public function username($value = null, $maxLength = null) {
		return $this->row("username", $value, $maxLength);
	}

	public function field($value = null, $maxLength = null) {
		return $this->row("text", $value, $maxLength);
	}

	public function password($value = null, $maxLength = null) {
		$id = $this->row("password", $value, $maxLength);
		$this->rows[$id]["dd"]->query("input[type=text]")->set("type", "password");
		return $id;
	}

	public function URL($value = null, $maxLength = null) {
		return $this->row("url", $value, $maxLength);
	}

	public function number($value = null, $maxLength = null) {
		return $this->row("number", $value, $maxLength);
	}

	public function email($value = null, $maxLength = null) {
		return $this->row("email", $value, $maxLength);
	}

	public function file($size = 0) {
		$this->form->set("enctype", "multipart/form-data");
		$id = $this->row($size);
		$this->rows[$id]["dd"]->query("input")->set("type", "file");
		return $id;
	}

	public function select($list, $selected = 1, $disableI18n = false) {
		$selected = (is_int($selected)) ? $selected : 1;
		$id = $this->row("select");
		$dd = $this->rows[$id]["dd"];
		$dd->query("input")->remove();
		$select = new Node("<select/>");
		foreach ($list as $key => $value) {
			$key = (is_string($key)) ? $key : $value;
			$option = new Node("<option/>");
			$option->set("value", $key);
			if ($this->i18n == false) {
				$text = ($this->ucfirst == true) ? ucfirst($value) : $value;
				$option->setValue($text);					
			} else if (is_numeric($value)) {
				$option->setValue($value);
			} else if ($disableI18n == true) {
				$option->setValue($value);
			} else {
				$option->set("lang", $value);
			}
			$select->append($option);
		}
		$select->query(sprintf("option:nth-child(%d)", $selected))->set("selected", "selected");
		$dd->query("input")->insertBefore($select);
		return $id;
	}

	public function checkboxs($list, $selected = array()) {
		$id = $this->row("checkbox");
		$dd = $this->rows[$id]["dd"];
		$dd->query("input")->remove();
		$ul = new Node("<ul/>");
		foreach ($list as $key => $value) {
			$uid = sprintf("input_%s", self::$id);
			self::$id++;
			$key = (is_string($key)) ? $key : $value;
			$li = new Node("<li/>");
			$label = new Node("<label/>");
			$label->set("value", $key);
			$label->set("for", $uid);
			if ($this->i18n == false) {
				$text = ($this->ucfirst == true) ? ucfirst($value) : $value;
				$label->setValue($text);					
			} else {
				$label->set("lang", $value);
			}
			$checkbox = new Node("<input/>");
			$checkbox->set("type", "checkbox");
			$checkbox->set("value", $key);
			$checkbox->set("id", $uid);
			$li->append($checkbox);
			$li->append($label);
			$ul->append($li);
		}
		$list = $ul->queryAll(sprintf("input[type=checkbox]")); 
		foreach ($selected as $index) {
			$list[($index - 1)]->set("checked", "checked");
		}
		$dd->query("input")->insertBefore($ul);
		return $id;
	}

	public function radios($list, $selected = 1) {
		$id = $this->row("radio");
		$dd = $this->rows[$id]["dd"];
		$dd->query("input")->remove();
		$ul = new Node("<ul/>");
		foreach ($list as $key => $value) {
			$uid = sprintf("input_%s", self::$id);
			self::$id++;
			$key = (is_string($key)) ? $key : $value;
			$li = new Node("<li/>");
			$label = new Node("<label/>");
			$label->set("value", $key);
			$label->set("for", $uid);
			if ($this->i18n == false) {
				$text = ($this->ucfirst == true) ? ucfirst($value) : $value;
				$label->setValue($text);					
			} else {
				$label->set("lang", $value);
			}
			$radio = new Node("<input/>");
			$radio->set("type", "radio");
			$radio->set("value", $key);
			$radio->set("id", $uid);
			$li->append($radio);
			$li->append($label);
			$ul->append($li);
		}
		if ($selected != 0) {
			$radio = $ul->queryAll("input");
			$radio[$selected - 1]->set("checked", "checked");
			// $ul->query(sprintf("input:nth-child(%s)", $selected))->set("checked", "checked");
		}
		$dd->query("input")->insertBefore($ul);
		return $id;
	}

	public function text($value = null) {
		$id = $this->row("text", $value);
		$dd = $this->rows[$id]["dd"];
		$dd->query("input")->remove();
		$textarea = new Node("<textarea/>");
		$textarea->set("id", $id);
		$textarea->setValue($value);
		$dd->query("input")->insertBefore($textarea);
		return $id;
	}

	public function hidden($value = null) {
		$id = $this->row("hidden", $value);
		$dd = $this->rows[$id]["dd"];
		$dd->query("input")->set("type", "hide");
		return $id;
	}

	public function getWidget() {
		return $this->getNode();
	}

	public function getNode() {

		$this->form->set("action", $this->action);
		$this->form->set("method", $this->method);

		$fieldset = new Node("<fieldset/>");
		$this->form->append($fieldset);

		$legend = new Node("<legend/>");
		$fieldset->append($legend);

		$dl = new Node("<dl/>");
		$fieldset->append($dl);

		foreach ($this->hash as $row) {
			$row = $this->rows[$row];
			$dl->append($row["dt"]);
			$dl->append($row["dd"]);
		}

		$dt = new Node("<dt/>");
		$dt->setValue("&nbsp;");
		$dl->append($dt);
		$dd = new Node("<dd/>");
		$dl->append($dd);

		$config = new Config();

		$hidden = new Node("<input/>");
		$hidden->set("type", "hidden");
		$hidden->set("name", "secret");
		$hidden->set("value", $config->secret);
		$dd->append($hidden);

		$submit = new Node("<input/>");
		$submit->set("type", "submit");
		if ($this->submit != null) $submit->set("value", $this->submit); 
		$dd->append($submit);

		return $this->form;

	}

	protected function row($type = null, $value = null, $maxLength = null) {

		$row = array();
		$id = sprintf("input_%d", self::$id);
		self::$id++;

		$row["dt"] = new Node("<dt/>");

		$label = new Node("<label/>");
		$label->set("for", $id);
		$row["dt"]->append($label);

		$row["dd"] = new Node("<dd/>");

		$input = new Node("<input/>");
		$input->set("type", "text");
		$input->set("id", $id);
		$input->set("value", $value);
		if ($maxLength != null) $input->set("maxlength", $maxLength);
		$row["dd"]->append($input);

		$hidden = new Node("<input/>");
		$hidden->set("type", "hidden");
		$hidden->set("value", $type);
		$row["dd"]->append($hidden);

		$this->rows[$id] = $row;

		return $id;

	}
}

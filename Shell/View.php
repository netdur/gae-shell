<?php

namespace Shell;

App::import("I18n");
App::import("Node");
App::import("Selector");

class View {

	private static $document = null;
	private static $instance = null;
	private static $template = null;

	public function getDocument() {
		return self::$document;
	}

	public static function getInstance() {
		if (self::$instance == null) {
			self::$instance = new View();
		}
		return self::$instance;
	}

	public function merge($node, $newNode) {
		if (is_string($node)) {
			$selector = new Selector();
			$node = $selector->query($node);
		}
		if (is_string($newNode)) {
			$newNode = new Node($newNode);
		}
		$attrs = $node->getAttributes();
		if ($node && $newNode) {
			$newNode = $node->replaceChild($newNode);
			foreach ($attrs as $key => $value) {
				if (!$newNode->hasAttribute($key)) {
					$newNode->setAttribute($key, $value);
				}
			}
		}
	}

	public function mergeFromFile($node, $file) {
		$config = new Config();
		if (!is_numeric(strpos($file, "/")) && !is_numeric(strpos($file, ".html"))) {
			$file = sprintf($config->get("webSchema"), $config->get("path"), $file);
		}
		if (file_exists($file) == false) {
			$file = sprintf("%s/%s", $config->path, $file);
		}
		$this->merge($node, file_get_contents($file));
	}

	public function setTemplateFromFile($template) {
		$config = new Config();
		if (!is_numeric(strpos($template, "/")) && !is_numeric(strpos($template, ".htm"))) {
			I18n::getInstance()->addFile($template);
			$template = sprintf($config->get("staticSchema"), $config->get("path"), $template);
		}
		if (file_exists($template) == false) {
			$template = sprintf("%s/%s", $config->get("path"), $template);
		}
		$this->setTemplate(file_get_contents($template));
	}

	public function setTemplate($template) {
		if (self::$template == null) {
			self::$template = true;
			$this->parse($template);
		}
	}

	public function flush($path = null) {
		if (self::$document === null) return;
		if ($path == null) {
			echo self::$document->saveHTML();
		} else {
			self::$document->saveHTMLFile($path);
		}
	}

	public function getContent() {
		if (self::$document === null) return;
		return self::$document->saveHTML();
	}

	private function parse($html) {
		$config = new Config();
		$config->set("isHTML", true);
		self::$document = new \DOMDocument;
		self::$document->loadHTML($html);
	}

	public function query($query, $context = null) {
		$selector = new Selector();
		return $selector->query($query, $context = null);
	}

	public function queryAll($query, $context = null) {
		$selector = new Selector();
		return $selector->queryAll($query, $context = null);
	}

	public function setLocal() {
		$i18n = I18n::getInstance();

		$nodes = new Selector();
		$nodes = $nodes->queryAll("[lang]");

		foreach ($nodes as $node) {
			$text = $i18n->getText($node->getAttribute("lang"));
			if ($text) {
				$node->setValue($text);
				$node->removeAttribute("lang");
			}
		}
	}
}

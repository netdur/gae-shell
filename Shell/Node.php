<?php

namespace Shell;

class Node {

	private	$item = null;
	private $document = null;

	const INVALID_PARAMETER	= "NODE ERROR: INVALID_PARAMETER";

	public function __toString() {
		return $this->textContent();
	}

	public function __construct($node, $document = null) {

		if ($document instanceof DOMDocument) {
			$this->document = $document;
		} else {
			$view = View::getInstance();
			$this->document = $view->getDocument();
		}

		if ($node instanceof \DOMNode) {
			$this->item = $node;
		} else if (strlen($node) < 2000 && file_exists($node)) {
			$this->createFromFile($node);
		} else if (is_string($node)) {
			$this->create($node);
		} else {
			Log::write(self::INVALID_PARAMETER);
		}
	}

	public function addClass($className) {
		if ($this->hasAttribute("class") === false) {
			$this->setAttribute("class", $className);
		} else {
			$this->removeClass($className);
			$class = $this->getAttribute("class");
			$class = explode(" ", $class);
			array_push($class, $className);
			$this->setAttribute("class", join(" ", $class));
		}
		return $this;
	}

	public function append($node) {
		return $this->appendChild($node);
	}

	public function appendChild($node) {
		$this->item->appendChild($node->item);
		return $this;
	}

	public function attributes() {
		$attributes 	= array();
		$attributesList = $this->item->attributes;
		foreach ($attributesList as $key => $value) {
			$attributes[$key] = $this->getAttribute($key);
		}
		return $attributes;
	}

	public function child() {
		$child = $this->item->childNodes;
		$nodes = array();
		foreach ($child as $node) {
			if ($node->nodeType == 3) continue;
			$nodes[] = new Node($node);
		}
		return $nodes;
	}

	public function cloneNode() {
		return new Node($this->item->cloneNode(true));
	}

	public function compareTo($node) {
		return $this->item->isSameNode($node->item);
	}

	public function contains($node) {
		#dom_node_compare_document_position
		#compareDocumentPosition
		$within = false;
		$all = $this->queryAll("*");
		foreach ($all as $element) {
			if ($element->compareTo($node)) {
				$within = true;
				break;
			}
		}
		return $within;
	}

	private function create($string) {
		$document = new \DOMDocument();
		if (!$document->loadXML($string)) {
			# this code is here to handle XML errors
			$document->loadHTML($string);
			$body = $document->getElementsByTagName("body")->item(0);
			$body = $document->saveXML($body);
			$body = preg_replace("/body/", "div", $body, 1);
			$body = str_replace("</body>", "</div>", $body);
			$document->loadXML($body);
		}
		$this->item = $this->document->importNode($document->firstChild, true);
	}

	public function createFromFile($path) {
		$this->create(file_get_contents($path));
	}

	public function get($key) {
		return $this->getAttribute($key);
	}

	public function getAttribute($key) {
		return $this->item->getAttribute($key);
	}

	public function getAttributes() {
		return $this->attributes();
	}

	public function getDOMNode() {
		return $this->item;
	}

	public function getElementsByTagName($tagName) {
		$tags = $this->item->getElementsByTagName($tagName);
		$list = array();
		foreach ($tags as $tag) {
			$list[] = new Node($tag);
		}
		return $list;
	}

	public function getStyle($key) {
		if ($this->hasAttribute("style") === false) {
			return;
		} else {
			$style	= $this->getAttribute("style");
			$rules	= explode(";", $style);
			$style	= array();
			foreach ($rules as $rule) {
				if ($rule) {
					$rule = explode(":", $rule);
					$style[$rule[0]] = $rule[1];
				}
			}
			return $style[$key];
		}
	}

	public function getValue() {
		return $this->document->saveXML($this->item, true);
	}

	public function firstChild() {
		$nodes = $this->child();
		return $nodes[0];
	}

	public function hasAttribute($key) {
		return $this->item->hasAttribute($key);
	}

	public function hasChild() {
		return $this->hasChildNodes();
	}

	public function hasChildNodes() { #text is child?
		return $this->item->hasChildNodes();
	}

	public function hasClass($className) {
		if ($this->hasAttribute("class") === false) {
			return false;
		} else {
			$class = $this->getAttribute("class");
			$class = explode(" ", $class);
			return in_array($className, $class);
		}
	}

	public function insertBefore($node) {
		$this->item->parentNode->insertBefore($node->item, $this->item);
		return $this;
	}

	public function lastChild() {
		$nodes = $this->child();
		return $nodes[count($nodes) - 1];
	}

	public static function loadFromView($view) {
		$config = new Config();
		return file_get_contents(sprintf($config->get("webSchema"), $config->get("path"), $view));
	}

	public function name() {
		return $this->item->nodeName;
	}

	public function next() {
		return new Node($this->item->nextSibling);
	}

	public function parent() {
		return new Node($this->item->parentNode);
	}

	public function parents() {
		$this->setAttribute("xpath", "startPoint");
		$xpath = new XPath();
		$elements = $xpath->queryAll('//*[@xpath="startPoint"]/ancestor::*');
		$this->removeAttribute("xpath");
		return $elements;
	}

	public function previous() {
		return new Node($this->item->previousSibling);
	}

	public function query($query) {
		$elements = $this->queryAll($query);
		if (isset($elements[0])) {
			return $elements[0];
		} else {
			return null;
		}
	}

	public function queryAll($query) {
		$selector = new Selector();
		$elements = $selector->queryAll($query, $this);
		return $elements;
	}

	public function remove() {
		return $this->removeChild();
	}

	public function removeAttribute($key) {
		$this->item->removeAttribute($key);
		return $this;
	}

	public function removeChild() {
		return $this->item->parentNode->removeChild($this->item);
	}

	public function removeClass($className) {
		if ($this->hasAttribute("class") === true) {
			$class = $this->getAttribute("class");
			$class = explode(" ", $class);
			$index = array_search($className, $class);
			if (is_numeric($index)) {
				unset($class[$index]);
			}
			$this->setAttribute("class", join(" ", $class));
		}
		return $this;
	}

	public function replace($node) {
		return $this->replaceChild($node);
	}

	public function replaceChild($node) {
		$this->item->parentNode->replaceChild($node->item, $this->item);
		return $node;
	}

	public function replaceClass($class, $newClass) {
		$this->removeClass($class);
		$this->addClass($newClass);
		return $this;
	}

	public function set($key, $value) {
		return $this->setAttribute($key, $value);
	}

	public function setAttribute($key, $value) {
		$this->item->setAttribute($key, $value);
		return $this;
	}

	public function setStyle($key, $value) {
		if ($this->hasAttribute("style") === false) {
			$this->setAttribute("style", sprintf("%s:%s;", $key, $value));
		} else {
			$style	= $this->getAttribute("style");
			$rules	= explode(";", $style);
			$style	= array();
			foreach ($rules as $rule) {
				if ($rule) {
					$rule = explode(":", $rule);
					$style[$rule[0]] = $rule[1];
				}
			}
			$style[$key]	= $value;
			$rules 			= array();
			foreach ($style as $key => $value) {
				$rules[]	= sprintf("%s:%s;", $key, $value);
			}
			$this->setAttribute("style", join("", $rules));
		}
		return $this;
	}

	public function setValue($value) {
		$this->item->nodeValue = $value;
		return $this;
	}

	public function setText($text) {
		$this->item->nodeValue = $text;
		return $this;
	}

	public function test($query) {
		$selector = new Selector($query);
		$selector = $selector->getElements();
		return $this->compareTo($selector[0]);
	}

	public function textContent() {
		return $this->item->textContent;
	}

	public function getText() {
		return $this->item->textContent;
	}

	public function toggleClass($className) {
		if ($this->hasClass($className)) {
			$this->removeClass($className);
		} else {
			$this->addClass($className);			
		}
		return $this;
	}

	public function type() {
		return $this->item->nodeType;
	}
}

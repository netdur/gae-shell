<?php

namespace Shell;

class XPath {

	private $XPath = null;
	private $elements = null;
	private $document = null;

	public function __construct($document = null) {
		if ($document instanceof DOMDocument) {
			$this->document = $document;
		} else {
			$view = View::getInstance();
			$this->document = $view->getDocument();
		}
	}

	public function query($query, $context = null) {

		$this->queryAll($query, $context);
		if (isset($this->elements[0])) {
			return $this->elements[0];
		} else {
			return false;
		}
	}

	public function queryAll($query, $context = null) {
		#Log::write($query);
		$this->XPath = $query;
		$xpath = new \DOMXPath($this->document);
		if ($context != null) {
			$elements = $xpath->query(sprintf(".%s", $query), $context->getDOMNode());
		} else {
			$elements = $xpath->query($query);
		}
		
		foreach ($elements as $element) {
			$this->elements[] = new Node($element);
		}

		if (is_array($this->elements)) {
			return $this->elements;
		} else {
			return array();
		}

	}

}

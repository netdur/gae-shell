<?php # http://alexei.417.ro/files/css-support.html

namespace Shell;

App::import("XPath");

class Selector {

	private $selector = null;

	public function query($query, $context = null) {
		$xpath = new XPath();
		return $xpath->query($this->parse($query), $context);
	}

	public function queryAll($query, $context = null) {
		$xpath = new XPath();
		return $xpath->queryAll($this->parse($query), $context);
	}

	private function parse($query) {
		$selectors = array();
		$quories = explode(",", trim($query));
		for ($i = 0; $i < count($quories); $i++) {
			array_push($selectors, "");
			$xpath = array();
			array_push($xpath, "//");
			$keywords = $this->getKeywords($quories[$i]);
			for ($y = 0; $y < count($keywords); $y++) {
				$keyword = trim($keywords[$y]);
				if ($keyword  == "") {
					continue;
				}
				$firstChar = $keyword{0};
				if ($firstChar == '[' || $firstChar == ':' || $firstChar == '#' || $firstChar == '.') {
					$keyword = sprintf("*%s", $keyword);
				}
				array_push($xpath, $this->cssToXPath($keyword));
			}
			$len = count($xpath);
			for ($y = 0; $y < $len; $y++) {
				$slash = "";
				if (isset($xpath[$y + 1])) {
					if ($xpath[$y + 1] != "/" && $xpath[$y] != "/" && $xpath[$y] != "//") {
						$slash = "//";
					}
				}
				$selectors[$i] = sprintf("%s%s%s", $selectors[$i], $xpath[$y], $slash);
			}
		}
		$query = "";
		$len = count($selectors);
		for ($i = 0; $i < $len; $i++) {
			$query = sprintf("%s %s %s", $query, ($query == "")  ? "": "|", $selectors[$i]);
		}
		return trim($query);
	}

	private function getKeywords($query) {
		$split = true;
		$keywords = "";
		$len = strlen($query);
		for ($i = 0; $i < $len; $i++) {
			$c = $query{$i};
			if ($c == '[') $split = false;
			if ($c == ']') $split = true;
			if ($c == " " && $split == true) {
				$keywords = sprintf("%s,", $keywords);
			} else {
				$keywords = sprintf("%s%s", $keywords, $query{$i});
			}
		}
		return explode(",", $keywords);
	}

	private function cssToXPath($css) {
		$xpath = "";
		$blocks = array();
		$block = 0;
		array_push($blocks, "");
		$len = strlen($css);
		$oc = null; 
		for ($i = 0; $i < $len; $i++) {
			$c = $css{$i};
			if (($c == '.' && $oc != '[') || $c == '#' || $c == '[' || $c == ':') {
				$block++;
				array_push($blocks, "");
				$oc = $c;
			}
			$blocks[$block] = sprintf("%s%s", $blocks[$block], $c);
		}
		$len = count($blocks);
		for ($i = 0; $i < $len; $i++) {
			$c = $blocks[$i]{0};
			if ($c == '.') {
				$xpath = sprintf("%s[contains(@class, '%s')]", $xpath, substr($blocks[$i], 1));
			} else if ($c == '#') {
				$xpath = sprintf("%s[@id='%s']", $xpath, substr($blocks[$i], 1));
			} else if ($c == ':') {
				$xpath = sprintf("%s%s", $xpath, $this->pseudoToXPath($blocks[$i], null));
			} else if ($c == '[') {
				$xpath = sprintf("%s%s", $xpath, $this->attributeToXPath($blocks[$i]));
			} else if ($c == '*') {
				$xpath = sprintf("%s*", $xpath);
			} else if ($c == '>') {
				$xpath = sprintf("%s/", $xpath);
			} else {
				$xpath = sprintf("%s%s", $xpath, $blocks[$i]);
			}
		}
		return $xpath;
	}

	private function attributeToXPath($css) {

		$attr = substr($css, 1, strlen($css) - 2); 
		$operation = null;

		if (is_numeric(strpos($attr, "|="))) {
			$operation = "|=";
		} else if (is_numeric(strpos($attr, "*="))) {
			$operation = "*=";
		} else if (is_numeric(strpos($attr, "$="))) {
			$operation = "$=";
		} else if (is_numeric(strpos($attr, "^="))) {
			$operation = "^=";
		} else if (is_numeric(strpos($attr, "~="))) {
			$operation = "~=";
		} else if (is_numeric(strpos($attr, "="))) {
			$operation = "=";
		}

		$attribute = "";
		$value = "";
		if ($operation != null) {
			$kv = explode($operation, $attr);
			$attribute = trim($kv[0]);
			$value = trim($kv[1]);
			$value = ($value{0} == "'" || $value{0} == '"') ? substr($value, 1, strlen($value) - 2) : $value;
		}

		$selector = "";
		if ($operation == "=") {
			$selector = sprintf("[@%s='%s']", $attribute, $value);
		} else if ($operation == "~=") {
			$selector = sprintf("[contains(concat(' ', normalize-space(@%s), ' '), ' %s ')]", $attribute, $value);
		} else if ($operation == "|=") {
			$selector = sprintf("[@%s = '%s' or starts-with(@%s, '%s-')]", $attribute, $value, $attribute, $value);
		} else if ($operation == "^=") {
			$selector = sprintf("[starts-with(@%s, '%s')]", $attribute, $value);
		} else if ($operation == "$=") {
			$selector = sprintf("[substring(@%s, string-length(@%s)-%s) = '%s']", $attribute, $attribute, strlen($value) - 1, $value);
		} else if ($operation == "*=") {
			$selector = sprintf("[contains(@%s, '%s')]", $attribute, $value);
		} else {
			$selector = sprintf("[@%s]", $attr);
		}
		return $selector;
	}

	private function pseudoToXPath($css, $tagName) {
		$selector = "";
		if ($css == ":root") {
			// !? W3C specs says :root always return <html>
			$selector = "ancestor::*[last()]";
		} else if (is_numeric(strpos($css, "nth-child"))) {
			$css = substr($css, 1);
			if (is_numeric(strpos($css, "odd"))) {
				$css = str_replace("odd", "2n+1", $css);
			}
			if (is_numeric(strpos($css, "even"))) {
				$css = str_replace("even", "2n", $css);
			}
			if ($css{11} == "n") {
				$n = explode("n+", substr($css, 10));
				$n[0] = (int) $n[0];
				$n[1] = (isset($n[1])) ? (int) $n[1] : 0;
				#$n[1] = sprintf("%s", substr($n[1], 1, strlen($n[1]) - 1));
				$selector = sprintf("[(position() + %s) mod - %s = %s]", $n[0], $n[0], $n[1]);
			} else {
				$selector = sprintf("[position() = %s]", substr($css, 9));
			}
		} else if (is_numeric(strpos($css, "nth-last-child"))) {
			$css = substr($css, 1);
			if (is_numeric(strpos($css, "+"))) {
				// lol wut
			} else {
				$selector = sprintf("[position() = last() - %s]", substr($css, 15));
			}
		} else if (is_numeric(strpos($css, "nth-of-type"))) {
			// ha ha you are funny
		} else if (is_numeric(strpos($css, "nth-last-of-type"))) {
			// are kidding me?
		} else if ($css == ":first-child") {
				$selector = "[position() = 1]";
		} else if ($css == ":last-child") {
				$selector = "[position() = last()]";
		} else if ($css == ":only-child") {
				$selector = "[count(../*) = 1]";
		} else if ($css == ":only-of-type") {
				$selector = sprintf("[count(../%s) = 1]", $tagName);
		} else if ($css == ":empty"){
				$selector = "[not(*) and not(normalize-space())]";
		} else if (is_numeric(strpos($css, "lang"))) {
			$css = substr($css, 1);
			$lang = substr(substr($css, 5), 0, count($css) - 1);
			$selector = sprintf("[@lang='%s']", $lang);
		} else if (is_numeric(strpos($css, ":enabled"))) {
			$selector = "[name() = 'input' and not(@disabled)]";
		} else if (is_numeric(strpos($css, ":disabled"))) {
			$selector = "[name() = 'input' and @disabled]";
		} else if (is_numeric(strpos($css, ":checked"))) {
			$selector = "[name() = 'input' and @checked]";
		}
		return $selector;
	}
}

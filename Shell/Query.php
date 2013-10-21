<?php

namespace Shell;

class Query {

	private $table = null;
	private $i18n = null;
	private $connection = null;
	private $hash = array();
	private $sql = null;
	private $find = false;
	private $className = null;

	private $where = null;
	private $order = null;
	private $offset = null;
	private $limit = null;

	private $tables = array();

	public function __construct($table, $local = null) {

		$this->className = $table;

		$config = Config::getInstance();

		if (in_array($table, $config->get("dbTables")) === false) {
			return;
		}

		foreach ($config->get("dbTables") as $k => $v) {
			if ($v == $table) {
				$table = $k;
				break;
			}
		}

		$this->table = sprintf("%s%s", $config->get("dbPrefix"), $table);
		$this->tables[] = $this->table;
		$this->i18n = I18n::getInstance();
		$this->i18n->addFile("i18n/%LOCAL/appshell/model.xml");

		$this->connection = new DBDriver(
			$config->get("dbHost"),
			$config->get("dbUser"),
			$config->get("dbPass"),
			$config->get("dbName")
		);
		if ($this->connection === false) {
			$error = $this->i18n->getText("connection_failed", $config->dbName, $config->dbHost);
			Log::write($error);
		}
	}

	public function addTable($table) {
		$table = $this->getTableRealName($table);
		if ($table != null) {
			$this->tables[] = $table;
		}
	}

	private function getTableRealName($table) {
		$config = Config::getInstance();

		if (in_array($table, $config->get("dbTables")) === false) {
			return null;
		}

		foreach ($config->get("dbTables") as $k => $v) {
			if ($v == $table) {
				return sprintf("%s%s", $config->get("dbPrefix"), $k);
			}
		}
	}

	public function getSQL() {
		return $this->sql;
	}

	public function __destruct() {
		$this->connection->dbFreeMemory();
		if ($this->connection) {
			$this->connection->dbClose();
		}
		$this->connection = null;
	}

	private function joinTable($string) {
		$parts = explode(".", $string);
		$table = $this->getTableRealName($parts[0]);
		if ($table == null) {
			return false;
		} else {
			return str_replace($parts[0], $table, $string);
		}
	}

	public function find($array = null) {

		$this->find = $array;
		$where = array();
		if ($array == null) return;
		foreach ($array as $key => $value) {
			if ($value === null) continue;
			// $sqlOperation = (is_numeric(strpos($value, "%"))) ? "LIKE" : "=";
			if (is_array($value)) {
				$sqlOperation = "IN";
			} else if (is_numeric(strpos($value, "%"))) {
				$sqlOperation = "LIKE";
			} else {
				$sqlOperation = "=";
			}
			if (is_numeric(strpos($value, ".")) OR is_numeric(strpos($key, "."))) {
				$key = $this->joinTable($key);
				$value = $this->joinTable($value);
				if ($key == false OR $value == false) {
					continue;
				}
				$where[] = sprintf("%s %s %s", $key, $sqlOperation, $value);
				continue;
			}
			if (is_array($value)) {
				$value = sprintf("(%s)", implode(", ", $value));
			} else {
				$value = $this->escapeString($value);
			}
			$where[] = sprintf("`%s` %s %s", $key, $sqlOperation, $value);
		}
		$this->where = $where;
		unset($where);
		return $this;
	}

	public function setOffset($offset) {
		$this->offset = (int) $offset;
		return $this;
	}

	public function setLimit($limit) {
		$this->limit = (int) $limit;
		return $this;
	}

	public function setCursor($cursor) {
		$this->_cursor = (int) $cursor;
		return $this;
	}

	public function setOrder($field, $type = "DESC") {
		$this->order[0] = $this->quoteHash($field);
		$this->order[1] = (strtolower($type) == "desc") ? "DESC" : "ASC";
		return $this;
	}

	public function fetch($fields = "*", $count = 20) {

		$select = null;
		$where = null;
		$local = null;
		$limit = null;
		$order = null;
		$sqlConditional = "WHERE";

		$this->table = implode(", ", $this->tables);
		$select = sprintf("SELECT %s FROM %s", $fields, $this->table);
	
		if ($this->find != false) {
			foreach ($this->where as $condition) {
				$where[] = sprintf("%s %s", $sqlConditional, $condition);
				$sqlConditional = "AND";
			}
			$where = (is_array($where)) ? implode(" ", $where) : null;	
		}

		if (is_int($this->limit)) {
			$count = $this->limit;
		}		

		if (is_int($this->offset)) {
			$limit = sprintf("LIMIT %d, %d", (int) $this->offset, $count + 1);
		} else {
			$limit = sprintf("LIMIT 0, %d", $count + 1);
		}
	
		if (is_array($this->order)) {
			$order = sprintf("ORDER BY %s %s", $this->order[0], $this->order[1]);
		} else {
			$order = "ORDER BY id DESC";
			$this->order[1] = "DESC";
		}

		if (isset($this->_cursor)) {
			// $this->order[1];
			$op = ($this->order[1] == "DESC") ? "<=" : ">=";
			if ($where == null) {
				$where = sprintf("%s WHERE id %s %s", $where, $op, $this->_cursor);
			} else {
				$where = sprintf("%s AND id %s %s", $where, $op, $this->_cursor);
			}
		}

		$this->sql = sprintf("%s %s %s %s %s", $select, $where, $local, $order, $limit);

		Log::write($this->sql);

		if ($this->connection->dbQuery($this->sql) === false) {
			return false;
		}

		$this->cursor = false;
		$this->next = false;
		$result = array();
		$i = 0;
		while ($row = $this->connection->dbObject()) {
			if ($i == $count) {
				$this->next = true;
				$this->cursor = $row->id;
				break;
			}
			$result[$i] = new $this->className;
			foreach ($row as $key => $value) {
				$result[$i]->{$key} = $value;
			}
			$i++;
		}

		unset($this);
		return $result;
	}

	public function SQL() {

		$args = func_get_args();
		$sql = $args[0];
		unset($args[0]);
		$dict = [];
		foreach ($args as $arg) {
			$dict[] = $this->connection->dbEscapeString($arg);
		}
		$sql = vsprintf($sql, $dict);

		$this->sql = $sql;
		// Log::write($sql);

		if ($this->connection->dbQuery($this->sql) === false) {
			return false;
		}

		$result = array();
		$i = 0;
		while ($row = $this->connection->dbObject()) {
			$result[$i] = new $this->className;
			foreach ($row as $key => $value) {
				$result[$i]->{$key} = $value;
			}
			$i++;
		}

		unset($this);
		return $result;
	}

	private function escapeString($value, $char = '"') {
		if (is_numeric($value)) {
			return sprintf('%s%s%s', $char, $this->connection->dbEscapeString($value), $char);
		} else {
			return sprintf('%s%s%s', $char, $this->connection->dbEscapeString($value), $char);
		}
	}

	public function quoteHash($value) {
		if (is_numeric(strpos($value, "."))) {
			$value = $this->joinTable($value);
			return sprintf("%s", $value);
		}
		return sprintf("`%s`", $value);
	}

}
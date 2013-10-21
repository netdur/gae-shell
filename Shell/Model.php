<?php

namespace Shell;

class Model {

	private $table = null;
	private $i18n = null;
	private $connection = null;
	private $hash = array();
	private $sql = null;
	private $local = false;
	private $mdb = false;

	public static function Query() {
		$model = get_called_class();
		return new Query($model);
	}

	public static function get($key, $fields = false) {
		$model = get_called_class();
		$model = new $model;
		$model->id = $key;
		$model->fetch($fields);
		return $model;
	}

	public function __get($key) {
		if (isset($this->hash[$key])) {
			return $this->hash[$key];
		} else {
			return null;
		}
	}

	public function __set($key, $value) {
		$this->hash[$key] = $value;
	}

	public function __construct($class, $table, $local = null) {

		$config	= new Config();

		$this->table = sprintf("%s%s", $config->get("dbPrefix"), $class);
		$this->i18n = new I18n;
		$this->i18n->addFile("i18n/%LOCAL/appshell/model.xml");

		$this->connection = new DBDriver(
			$config->get("dbHost"),
			$config->get("dbUser"),
			$config->get("dbPass"),
			$config->get("dbName")
		);
		if ($this->connection === false) {
			$error = $this->i18n->getText("connection_failed", $config->get("dbName"), $config->get("dbHost"));
			Log::write($error);
		}
	}

	public function __destruct() {
		if ($this->connection) {
			$this->connection->dbFreeMemory($this->connection);
			$this->connection->dbClose($this->connection);
		}
		$this->connection = null;
	}

	private function escapeString($value) {
		if (is_numeric($value)) {
			return sprintf("%s", $this->connection->dbEscapeString($value));
		} else {
			return sprintf('"%s"', $this->connection->dbEscapeString($value));
		}
	}

	public function getSql() {
		return $this->sql;
	}

	public function getHash() {
		return $this->hash;
	}

	private function insert() {

		foreach ($this->hash as $value) {
			$values[] = $this->escapeString($value);
		}

		$values	= implode(", ", $values);
		$hash	= array_map(array($this, "quoteHash"), array_keys($this->hash));
		$keys	= implode(", ", $hash);
		$sql	= sprintf("INSERT INTO %s (%s) VALUES (%s)", $this->table, $keys, $values);
		$this->connection->dbQuery($sql);
		$this->hash["id"] = $this->connection->dbLastId(); #lol wut!?
		$this->sql = $sql;
		Log::write($sql);

		$this->fetch();
	}

	public function quoteHash($value) {
		return sprintf("`%s`", $value);
	}

	public function delete() {
		$this->remove();
	}

	public function flush() {
		$memcache = new \Memcache();
		$memcache->delete($this->mdb);
	}

	public function remove() {
		$memcache = new \Memcache();
		$memcache->delete($this->mdb);
		if (isset($this->hash["id"])) {
			$syntax = "DELETE FROM %s WHERE `id` = %s";
			$sql	= sprintf($syntax, $this->table, $this->hash["id"]);
			$this->connection->dbQuery($sql);
			$this->sql = $sql;
			Log::write($sql);
		}
	}

	public function save() {
		$memcache = new \Memcache();
		$memcache->delete($this->mdb);
		if (isset($this->hash["id"])) {
			$this->update();
		} else {
			$this->insert();
		}
	}

	public function fetch($fields = false) {
		if ($fields) {
			if (is_array($fields)) {
				$fields = implode(", ", $fields);
			}
			$sql = sprintf("SELECT %s FROM %s WHERE `id` = %s", $fields, $this->table, $this->id);
		} else {
			$sql = sprintf("SELECT * FROM %s WHERE `id` = %s", $this->table, $this->id);
		}

		$memcache = new \Memcache();
		$this->mdb = md5($sql);
		$row = $memcache->get($this->mdb);
		$this->sql = $sql;
		
		$config = new Config();
		if ($config->get("dbMem") == 0) {
			$row = false;
			$memcache->delete($this->mdb);
		}

		if ($row === false) {
			Log::write(sprintf("MySQL:%s> %s", $this->mdb, $sql));
			if ($this->connection->dbQuery($sql) === false) {
				return false;
			}
			$this->id = null;
			$row = $this->connection->dbObject();
			if (is_object($row) === false) {
				return;
			}
			$memcache->set($this->mdb, $row);
			$this->updated = false;
		} else {
			Log::write(sprintf("Memcache:%s> %s", $this->mdb, $sql));
		}

		foreach($row as $key => $value) {
			$this->{$key} = $value;
		}
	}

	private function update() {
		foreach ($this->hash as $key => $value) {
			if ($key == "id") continue;
			$sql[]	= sprintf("`%s`=%s", $key, $this->escapeString($value));
		}

		$syntax = "UPDATE %s SET %s WHERE id = %s";
		$sql = sprintf($syntax, $this->table, implode(", ", $sql), $this->hash["id"]);

		Log::write($sql);
		$this->connection->dbQuery($sql);
		$this->sql = $sql;
	}
}
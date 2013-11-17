<?php

namespace Shell;

class DBDriver {
	
	public $connection = null;
	public $result = false;

	public function __construct($dbHost, $dbUser, $dbPass, $dbName) {
		$this->connection = new \mysqli($dbHost, $dbUser, $dbPass, $dbName);
	}

	public function dbQuery($sql) {
		$this->result = $this->connection->query($sql);
		return $this->result;
	}
	
	public function dbObject() {
		if ($this->result) {
			return $this->result->fetch_object();
		}
		return false;
	}
	
	public function dbFreeMemory() {
		if (is_resource($this->result)) {
			$this->result->close();
		}
	}
	
	public function dbEscapeString($string) {
		return $this->connection->escape_string($string);
	}

	public function dbLastId() {
		return $this->connection->insert_id;
	}
	
	public function dbClose() {
		$this->connection->close();
	}
}

<?php

class BaseModel{
	// property
	protected $connection;

	public function __construct(){
		// Credentials live in config.php (gitignored, never shared).
		// See config.example.php for the template.
		$config = require __DIR__ . '/../config.php';

		$this->connection = new mysqli(
			$config['host'],
			$config['user'],
			$config['password'],
			$config['dbname']
		);

		if ($this->connection->connect_error) {
			die("Database connection failed: " . $this->connection->connect_error);
		}
	}
}
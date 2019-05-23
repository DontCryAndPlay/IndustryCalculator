<?php
interface SQLiteController {
	public function open(string $filename) : bool;
	public function close() : bool;
	public function query(string $query) : array;
}
class SQLite implements SQLiteController{
	private $validExtensions = array("sqlite3", "pdo_sqlite");
	private $useExtension = null;
	private $handler = null;
	function __construct() {
		$ok = false;
		debug("Loaded %d SQLite extensions.", count($this->validExtensions));
		foreach($this->validExtensions as $extension) {
			if(extension_loaded($extension)) {
				$ok = true;
				$this->useExtension = $extension;
				debug("Chosen extension %s", $extension);
				break;
			}
		}
		// TODO: proper exception
		if(!$ok)
			throw new Exception("No SQLite extension detected", 1);

		$this->load();
	}
	private function load() {
		debug("Guessing handler...");
		switch($this->useExtension) {
			case "sqlite3":
				$this->handler = new SQLite3Controller();
				break;
			case "pdo_sqlite":
				$this->handler = new PDOSQLite();
				break;
			default:
				# TODO: proper exception handling
				throw new Exception("Undefined handler", 1);
				break;
		}
	}
	public function create(string $filename) : bool {
		if(file_exists($filename)) {
			throw new FileAlreadyExistsException;
			return false;
		}
		$f = @fopen($filename, "wb");
		if(!$f) {
			throw new FileNotWritableException;
			return false;
		}
		fclose($f);
		return $this->open($filename);
	}
	public function open(string $filename) : bool {
		# TODO: proper exception handling
		if($this->handler == null) {
			throw new Exception("Undefined handler", 1);
			return false;
		}
		if(!file_exists($filename)) {
			throw new FileNotFoundException;
			return false;
		}
		if(!is_readable($filename)) {
			throw new FileNotReadableException;
			return false;
		}

		$f = @fopen($filename, "rb");
		if(!$f) {
			throw new Exception("I/O Error", 1);
			return false;
		}
		
		fclose($f);

		return $this->handler->open($filename);	
	}
	public function close() : bool {
		if($this->handler == null) {
			throw new Exception("Undefined handler", 1);
			return false;
		}
		return $this->handler->close();
	}
	public function query(string $query) : array {
		if($this->handler == null) {
			throw new Exception("Undefined handler", 1);
			return [];
		}
		return $this->handler->query($query);
	}
}
class SQLite3Controller implements SQLiteController {
	private $db = null;
	function __construct() {
		debug("Handler loaded");
	}
	public function open(string $filename) : bool {
		debug("Opening " . $filename);
		$this->db = new SQLite3($filename);
		$this->db->enableExceptions(true);
		return true;
	}
	public function query(string $query) : array {
		debug("Querying: " . $query);
		try {
			$result = $this->db->query($query);
		} catch(Exception $e) {
			throw new SQLiteException($this->db->lastErrorMsg(), $this->db->lastErrorCode());
		}
		if(is_bool($result))
			return [];
		$data = $result->fetchArray(SQLITE3_ASSOC);
		if(is_bool($data))
			return [];
		return (array)$data;
	}
	public function close() : bool {
		debug("Closing database...");
		$this->db->close();
		return true;
	}
}
?>
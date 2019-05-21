<?php
//TODO: create interface for this
class SQLite {
	private $validExtensions = array("sqlite3", "pdo_sqlite")
	private $useExtension = null;
	function __construct() {
		$ok = false;
		foreach($validExtensions as $extension) {
			if(extension_loaded($extension)) {
				$ok = true;
				$this->useExtension = $extension;
				break;
			}
		}
		// TODO: proper exception
		if(!$ok)
			throw new Exception("No SQLite extension detected", 1);

		// TODO: instantiate new object depending of which extension has been found
		// 		 that object should manage its extension seamlessly:
		//       - interface?
	}
}
?>
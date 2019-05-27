<?php
class Downloader {
	private $dest, $uri;
	private $progressCallback;
	private $socket = null;
	private $file;
	private $wroteData = 0;
	public $force = false;
	function __construct(string $uri, string $dest = "") {
		if($dest == "")
			$dest = basename($uri);
		$this->uri = $uri;
		$this->dest = $dest;
		$this->socket = new Socket("GET", $uri);
	}
	function __destruct() {
		unset($this->socket);
	}
	private function createFile($path) : bool {
		if(strpos($path, "/") > 0) {
			#TODO: directory navigation
		}
		if(file_exists($path) && !$this->force) {
			throw new FileAlreadyExistsException;
			return false;
		}
		$f = @fopen($path, "wb");
		if(!$f) {
			throw new FileNotWritableException;
			return false;
		}
		$this->file = $f;
		return true;
	}
	public function execute() {
		$ok = $this->createFile($this->dest);
		if(!$ok)
			return false;
		$this->socket->set(CURLOPT_PROGRESSFUNCTION, array($this, 'progress'));
		$this->socket->set(CURLOPT_NOPROGRESS, false);
		$this->socket->set(CURLOPT_WRITEFUNCTION, array($this, 'write'));
		$this->socket->execute();
		debug("Wrote %d bytes", $this->wroteData);
		fclose($this->file);
	}
	public function onProgress(callable $callback) {
		$this->progressCallback = $callback;
	}
	public function progress(...$progressData) {
		if(is_callable($this->progressCallback))
			$this->progressCallback->call($this, $progressData[0], $progressData[1], $progressData[2]);
	}
	public function write($ch, $data) : int {
		$length = strlen($data);
		fwrite($this->file, $data, $length);
		$this->wroteData += $length;
		return $length;
	}
}
?>
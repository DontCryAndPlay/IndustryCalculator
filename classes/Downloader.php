<?php
class Downloader {
	private $dest, $uri;
	private $progressCallback;
	private $socket = null;
	private $file;
	private $wroteData = 0;
	function __construct(string $uri, string $dest = "") {
		if($dest == "")
			$dest = basename($uri);
		$this->uri = $uri;
		$this->dest = $dest;
		$this->socket = new Socket("GET", $uri);
	}
	private function createFile($path) : bool {
		if(strpos($path, "/") > 0) {
			#TODO: directory navigation
		}
		if(file_exists($path)) {
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
		$this->socket->set(CURLOPT_PROGRESSFUNCTION, array($this, 'onProgress'));
		$this->socket->set(CURLOPT_NOPROGRESS, false);
		$this->socket->set(CURLOPT_WRITEFUNCTION, array($this, 'onWrite'));
		$this->socket->execute();
		debug("Wrote %d bytes", $this->wroteData);
		fclose($this->file);
	}
	public function onProgress(...$progressData) {
		if(is_callable($this->progressCallback))
			$progressCallback($progressData);
	}
	public function onWrite($ch, $data) : int {
		$length = strlen($data);
		fwrite($this->file, $data, $length);
		$this->wroteData += $length;
		return $length;
	}
}
?>
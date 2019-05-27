<?php
interface iSocket {
	public function execute() : array;
	public function set(int $option, $value);
	public function setArray(array $options);
	public function enforceSSL(bool $status);
}
class Socket implements iSocket {
	private $validExtensions = array("curl");
	private $useExtension = null;
	private $handler = null;
	function __construct($method = false, $uri = false) {
		$ok = false;
		if(!defined("SOCKETEXTENSION")) {
			debug("Loaded %d Socket extensions.", count($this->validExtensions));
			foreach($this->validExtensions as $extension) {
				if(extension_loaded($extension)) {
					$ok = true;
					$this->useExtension = $extension;
					debug("Chosen extension %s", $extension);
					break;
				}
			}
			if(!$ok)
				$this->useExtension = "core";

			define("SOCKETEXTENSION", $this->useExtension);
		} else
			$this->useExtension = SOCKETEXTENSION;

		$this->load($method, $uri);
	}
	function __destruct() {
		$this->close();
	}
	private function load($method = false, $uri = false) {
		debug("Guessing handler...");
		switch($this->useExtension) {
			case "curl":
				$this->handler = new CurlController($method, $uri);
				break;
			case "core":
				$this->handler = new FSocketController($method, $uri);
				break;
			default:
				# TODO: proper exception handling
				throw new Exception("Unhandled extension", 1);
				break;
		}
	}
	public function execute() : array {
		if($this->handler === null) {
			throw new Exception("Undefined handler", 1);
			return [];
		}
		return $this->handler->execute();
	}
	public function close() {
		unset($this->handler);
	}
	public function set(int $option, $value) {
		$this->handler->set($option, $value);
	}
	public function setArray(array $options) {
		$this->handler->setArray($options);
	}
	public function enforceSSL(bool $status) {
		$this->handler->enforceSSL($status);
	}
}
class CurlController implements iSocket {
	private $supportedMethods = array("GET", "POST");
	private $method = false;
	private $uri = false;
	private $ch = null;
	private $curlDefaultOptions = array(
			CURLOPT_RETURNTRANSFER => true,     // return web page
			CURLOPT_HEADER         => false,    // don't return headers
			CURLOPT_FOLLOWLOCATION => true,     // follow redirects
			CURLOPT_ENCODING       => "",       // handle all encodings
			CURLOPT_USERAGENT      => "", // who am i
			CURLOPT_AUTOREFERER    => true,     // set referer on redirect
			CURLOPT_CONNECTTIMEOUT => 120,      // timeout on connect
			CURLOPT_TIMEOUT        => 120,      // timeout on response
			CURLOPT_MAXREDIRS      => 10       // stop after 10 redirects
		);
	private $options = array();
	function __construct($method = false, $uri = false) {
		if($method !== false) {
			debug("Got method: %s", $method);
			foreach($this->supportedMethods as $supportedMethod)
				if(strtoupper($method) == $supportedMethod)
					$this->method = $supportedMethod;
		}
		if($uri !== false) {
			debug("Got URI: %s", $uri);
			$this->uri = $uri;
		}
		$this->ch = curl_init();
	}
	function __destruct() {
		debug("Closing curl handler");
		curl_close($this->ch);
	}
	public function set(int $option, $value) {
		$this->options[$option] = $value;
	}
	public function setArray(array $options) {
		$this->options = array_merge($this->options, $options);
	}
	public function enforceSSL(bool $status) {
		$sslFlags = array(
			CURLOPT_SSL_VERIFYPEER => true,
			CURLOPT_SSL_VERIFYHOST => 2,
			);
		if($status) {
			debug("Enforcing SSL");
			$this->setArray($sslFlags);
		} else {
			debug("Disabling SSL flags");
			foreach($sslFlags as $flag=>$value) {
				foreach($this->options as $k=>$v) {
					if($k == $flag) {
						unset($this->options[$flag]);
						continue 2;
					}
				}
			}
		}
	}
	public function execute() : array {
		if($this->ch === null)
			return [];
		#TODO: handle POST
		curl_setopt($this->ch, CURLOPT_URL, $this->uri);
		curl_setopt_array($this->ch, $this->curlDefaultOptions);
		curl_setopt_array($this->ch, $this->options);
		$output = array();
		$output['data']    = curl_exec($this->ch);
		$output['headers'] = curl_getinfo($this->ch);
		$output['errno']   = curl_errno($this->ch);
		$output['errmsg']  = curl_error($this->ch);
		if($output['data'] === false)
			throw new SocketException("Error: %s" . $output['errmsg'], $output['errno']);

		return $output;
	}
}
?>
<?php
class ConfigManager {
	private $file;
	private $valid = false;
	private $config;
	public static $instance;
	public function __construct(string $configFile) {
		self::$instance = $this;
		debug("Loading configuration file...");
		$this->file = $configFile;
		if(!file_exists($configFile) || !is_readable($configFile))
			throw new ConfigException(sprintf("Can't open configuration file: %s", $configFile));

		$conf = @parse_ini_file($configFile, true);
		if(!$conf)
			throw new ConfigException("Invalid configuration file");
		$this->config = $conf;
	}
	public function getConfigurations() {
		return $this->config;
	}
	public function getConfiguration(string $key) {
		if(strpos($key, ".") > 0) {
			$keys = explode(".", $key);
			$val = $this->config;
			while(count($keys) > 0) {
				$key = array_shift($keys);
				if(!isset($val[$key]))
					return false;
				$val = $val[$key];
			}
			return $val;
		} else {
			return isset($this->config[$key])? $this->config[$key] : false;
		}
		return false;
	}
	public function setConfiguration(string $key, $value) : bool {
		if(strpos($key, ".") > 0) {
			$keys = explode(".", $key);
			$val = &$this->config;
			while(count($keys) > 0) {
				$key = array_shift($keys);
				if(!isset($val[$key]))
					$val[$key] = "";
				$val = &$val[$key];
			}
			$val = $value;
			return true;
		} else {
			$this->config[$key] = $value;
			return true;
		}
		return false;
	}
	public function save() : bool {
		# TODO: write to file
	}
}
?>
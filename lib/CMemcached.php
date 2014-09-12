<?php
namespace Pramod\Memcached;
class CMemcached {
	const DEFAULT_EXPIRATION = 3600;	//1 hour
	protected $_m;
	private $_c;
	
	public function __construct(array $config) {
		if(!extension_loaded('memcached')) {
			throw new \RuntimeException("Extension memcached not loaded.");
		}
		
		if(isset($config['expiration']) && (!ctype_digit($config['expiration']) || $config['expiration']<0)) {
			throw new \InvalidArgumentException("Default expiration time is not valid.");
		}
		
		$this->_c = $config;
		
		if(!isset($this->_c['expiration']) || $this->_c['expiration'] == '') {
			$this->_c['expiration'] = self::DEFAULT_EXPIRATION;
		}
		
		if(!isset($this->_c['namespace'])) {
			$this->_c['namespace'] = '';
		}
	
		if(!isset($this->_c['version'])) {
			$this->_c['version'] = '';
		}
		
		if(!isset($this->_c['logerror'])) {
			$this->_c['logerror'] = false;
		}

		$this->_m = new \Memcached();
		
		if(isset($config['server']) && is_array($config['server'])) {
			foreach($config['server'] as $server) {
				if(isset($server['host']) && isset($server['port']) && isset($server['weight'])) {
					$this->addServer($server['host'], $server['port'], $server['weight']);
				}
			}
		}
		
		//$this->_m->setOption(\Memcached::OPT_BINARY_PROTOCOL, true);
	}

	public function add($key, $value, $expiration=NULL) {
	
		if(trim($key)=='') {
			throw new \InvalidArgumentException("Key is empty."); 
		}
		
		if(is_null($expiration)) {
			$expiration = $this->_c['expiration'];
		}

		if(!$this->_m->add($this->createKey($key), $value, $expiration)) {
			$result_code = $this->_m->getResultCode();
			if($result_code === \Memcached::RES_NOTSTORED) {
				$this->logError(__METHOD__.":".__LINE__.":Unable to add key $key. Key already exists. Result code: $result_code. Result message: ".$this->_m->getResultMessage());
			}
			else {
				$this->logError(__METHOD__.":".__LINE__.":Unable to add key $key. Result code: $result_code. Result message: ".$this->_m->getResultMessage());
			}
			return false;
		}
		
		return true;
	}
	
	public function set($key, $value, $expiration=NULL) {
		if(is_null($expiration)) {
			$expiration = $this->_c['expiration'];
		}

		if(!$this->_m->set($this->createKey($key), $value, $expiration)) {
			$this->logError(__METHOD__.":".__LINE__.":Unable to set key $key. Result code: ".$this->_m->getResultCode().". Result message: ".$this->_m->getResultMessage());
			return false;
		}
		
		return true;
	}

	
	public function get($key, callable $cb=NULL, &$cas_token=NULL) {
		$value = $this->_m->get($this->createKey($key), $cb, $cas_token);
		
		$result_code = $this->_m->getResultCode();
		//check for result code since value may have been stored FALSE
		if($value === FALSE && $result_code === \Memcached::RES_NOTFOUND) {
			$this->logError(__METHOD__.":".__LINE__.":Key $key does not exists. Result code: $result_code. Result message: ".$this->_m->getResultMessage());
		}
		
		return $value;
	}
	
	/*
	--For PECL memcached 2.10--
delete used with second argument 'time' returns false and set the error code and message for invalid arguments unless used without Memcached::OPT_BINARY_PROTOCOL.

getResultCode() and getResultMessage() returns 38 and INVALID ARGUMENTS respectively.
	*/
	//public function delete($key, $time=0) {
	public function delete($key) {
		//if(!$this->_m->delete($this->createKey($key), $time)) {
		if(!$this->_m->delete($this->createKey($key))) {
			$result_code = $this->_m->getResultCode();
			if($result_code === \Memcached::RES_NOTFOUND) {
				$this->logError(__METHOD__.":".__LINE__.":Unable to delete key $key. Key doesn't exist. Result code: $result_code. Result message: ".$this->_m->getResultMessage());
			}
			else {
				$this->logError(__METHOD__.":".__LINE__.":Unable to delete key $key. Result code: $result_code. Result message: ".$this->_m->getResultMessage());
			}
			return false;
		}
		
		return true;
	}
	
	//*increment/decrement will not change TTL of a item
	//public function increment($key, $offset=1, $initial_value=0, $expiration=0) {
	public function increment($key, $offset=1) {
		/*if(is_null($expiration)) {
			$expiration = $this->_c['expiration'];
		}*/

		//PECL memcached >= 0.2.0
		//if(($val=$this->_m->increment($this->createKey($key), $offset, $initial_value, $expiration)) === FALSE) {
		
		if(($val=$this->_m->increment($this->createKey($key), $offset)) === FALSE) {
			$this->logError(__METHOD__.":".__LINE__.":Unable to increment key $key. Result code: ".$this->_m->getResultCode().". Result message: ".$this->_m->getResultMessage());
		}
		
		return $val;
	}
	
	//*increment/decrement will not change TTL of a item
	//public function decrement($key, $offset=1, $initial_value=0, $expiration=0) {
	public function decrement($key, $offset=1) {
		/*if(is_null($expiration)) {
			$expiration = $this->_c['expiration'];
		}*/

		//PECL memcached >= 0.2.0
		//if(($val=$this->_m->decrement($this->createKey($key), $offset, $initial_value, $expiration)) === FALSE) {

		if(($val=$this->_m->decrement($this->createKey($key), $offset)) === FALSE) {
			$this->logError(__METHOD__.":".__LINE__.":Unable to decrement key $key. Result code: ".$this->_m->getResultCode().". Result message: ".$this->_m->getResultMessage());
		}

		return $val;
	}
	
	public function addServer($host, $port, $weight=0) {
		if(!$this->_m->addServer($host, $port, $weight)) {
			$this->logError(__METHOD__.":".__LINE__.":Unable to add server. Result code: ".$this->_m->getResultCode().". Result message: ".$this->_m->getResultMessage());	//__METHOD__ gives "classname:methodname"
			return false;
		}
		
		return true;
	}
	
	//PECL memcached >= 2.0.0(only supported with binary protocol i.e. Memcached::OPT_BINARY_PROTOCOL set to true.)
	/*public function touch($key, $expiration) {
		if(!$this->_m->touch($this->createKey($key), $expiration)) {
			$this->logError(__METHOD__.":".__LINE__.":Setting new expiration failed. Result code: ".$this->_m->getResultCode().". Result message: ".$this->_m->getResultMessage());
			return false;
		}
		
		return true;
	}*/
	
	public function createKey($key) {
		return md5($this->_c['namespace'].$this->_c['version'].$key);
		//return $this->_c['namespace'].$this->_c['version'].$key;
	}
	
	protected function logError($msg) {
		if($this->_c['logerror']) {
			error_log($msg);
		}
	}
	
	public function getServerList() {
		return $this->_m->getServerList();
	}

}

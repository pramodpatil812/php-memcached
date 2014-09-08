<?php
namespace PHPMemcached;
class CMemcached {
	protected $_m;
	private $_c;
	
	public function __construct(array $config) {
		if(!extension_loaded('memcached')) {
			$this->logError(__METHOD__.":".__LINE__.":Extension memcached not loaded.");
			throw new MemcachedException("Extension memcached not loaded. Unable to create instance.");
		}
		
		$this->_m = new \Memcached();
		foreach($config['server'] as $server) {
			if(!$this->_m->addServer($server['host'], $server['port'], $server['weight'])) {
				$this->logError(__METHOD__.":".__LINE__.":Unable to add server.");	//__METHOD__ gives "classname:methodname"
			}
		}

		$this->_c = $config;
		//print_r($this->_c);
	}

	public function add($key, $value, $expiration=NULL) {
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
	
	public function multiAdd(array $data) {
		foreach($data as $d) {
			$this->add($d['key'], $d['value'], $d['expiration']);
		}
	}

	public function get($key) {
		$value = $this->_m->get($this->createKey($key));
		
		$result_code = $this->_m->getResultCode();
		//check for result code since value may have been stored FALSE
		if($value === FALSE && $result_code === \Memcached::RES_NOTFOUND) {
			$this->logError(__METHOD__.":".__LINE__.":Key $key does not exists. Result code: $result_code. Result message: ".$this->_m->getResultMessage());
		}
		
		return $value;
	}
	
	public function set($key, $value, $expiration=NULL) {
		if(is_null($expiration)) {
			$expiration = $this->_c['expiration'];
		}

		if(!$this->_m->set($this->createKey($key), $value, $expiration)) {
			$this->logError(__METHOD__.":".__LINE__.":Unable to set key $key. Result code: ".$m->getResultCode().". Result message: ".$this->_m->getResultMessage());
			return false;
		}
		
		return true;
	}
	
	public function createKey($key) {
		return md5($this->_c['namespace'].$this->_c['version'].$key);
	}
		
	//*increment/decrement will not change TTL of a item
	public function increment($key, $offset=1) {
		
	}
	
	//*increment/decrement will not change TTL of a item
	public function increment($key, $offset=1, $initial_value=0, $expiration=NULL) {
		if(is_null($expiration)) {
			$expiration = $this->_c['expiration'];
		}

		//PECL memcached >= 0.2.0
		if(($val=$this->_m->increment($this->createKey($key), $offset, $initial_value, $expiration)) === FALSE) {
			$this->logError(__METHOD__.":".__LINE__.":Unable to increment key $key. Result code: ".$m->getResultCode().". Result message: ".$this->_m->getResultMessage());
		}
		
		return $val;
	}
	
	//*increment/decrement will not change TTL of a item
	public function decrement($key, $offset=1, $initial_value=0, $expiration=NULL) {
		if(is_null($expiration)) {
			$expiration = $this->_c['expiration'];
		}

		//PECL memcached >= 0.2.0
		if(($val=$this->_m->decrement($this->createKey($key), $offset, $initial_value, $expiration)) === FALSE) {
			$this->logError(__METHOD__.":".__LINE__.":Unable to increment key $key. Result code: ".$m->getResultCode().". Result message: ".$this->_m->getResultMessage());
		}
		
		return $val;
	}
	
	public function delete($key, $time=0) {
		if(!$this->_m->delete($this->createKey($key), $time)) {
			$result_code = $this->_m->getResultCode();
			if($result_code === \Memcached::RES_NOTSTORED) {
				$this->logError(__METHOD__.":".__LINE__.":Unable to delete key $key. Key doesn't exist. Result code: $result_code. Result message: ".$this->_m->getResultMessage());
			}
			else {
				$this->logError(__METHOD__.":".__LINE__.":Unable to delete key $key. Result code: $result_code. Result message: ".$this->_m->getResultMessage());
			}
			return false;
		}
		
		return true;
	}
	
	protected function logError($msg) {
		if($this->_c['logerror']) {
			error_log($msg);
		}
	}

}

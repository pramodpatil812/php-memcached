<?php
namespace PHPMemcached;
class CMemcached {
	private $_m;
	private $_c;
	
	public function __construct(array $config) {
		$this->_m = new \Memcached();
		foreach($config['server'] as $server) {
			if(!$this->_m->addServer($server['host'], $server['port'], $server['weight'])) {
				error_log(__METHOD__.":".__LINE__.":Unable to add server.");	//__METHOD__ gives combination of classname and methodname
			}
		}

		$this->_c = $config;
		//print_r($this->_c);
	}

	public function add($key, $value, $expiration=NULL) {
		if(is_null($expiration)) {
			$expiration = $this->_c['expiration'];
		}

		$nKey = $this->createKey($key);
		if(!$this->_m->add($nKey, $value, $expiration)) {
			$result_code = $this->_m->getResultCode();
			if($result_code === \Memcached::RES_NOTSTORED) {
				error_log(__METHOD__.":".__LINE__.":Unable to add key $key. Key already exists. Result code: $result_code. Result message: ".$this->_m->getResultMessage());
			}
			else {
				error_log(__METHOD__.":".__LINE__.":Unable to add key $key. Result code: $result_code. Result message: ".$this->_m->getResultMessage());
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
		$nKey = $this->createKey($key);
		$value = $this->_m->get($nKey);
		
		$result_code = $this->_m->getResultCode();
		//check for result code since value may have been stored FALSE
		if($value === FALSE && $result_code === \Memcached::RES_NOTFOUND) {
			error_log(__METHOD__.":".__LINE__.":Key $key does not exists. Result code: $result_code. Result message: ".$this->_m->getResultMessage());
		}
		
		return $value;
	}
	
	public function set($key, $value, $expiration=NULL) {
		if(is_null($expiration)) {
			$expiration = $this->_c['expiration'];
		}

		$nKey = $this->createKey($key);
		if(!$this->_m->set($nKey, $value, $expiration)) {
			error_log(__METHOD__.":".__LINE__.":Unable to set key $key. Result code: ".$m->getResultCode().". Result message: ".$this->_m->getResultMessage());
			return false;
		}
		
		return true;
	}
	
	public function createKey($key) {
		return md5($this->_c['namespace'].$this->_c['version'].$key);
	}

}

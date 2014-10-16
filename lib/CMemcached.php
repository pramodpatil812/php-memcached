<?php
namespace Pramod\Memcached;

/**
 * Wrapper class for php memcached.
 * @author Pramod Patil
 * @link http://php.net/manual/en/class.memcached.php
 */
class CMemcached
{
	/** default TTL(in seconds) for objects when the same in not provided via constructor argument. */
    const DEFAULT_EXPIRATION = 3600;    //1 hour
    
    /** @var object $m holder for native memcached class object */
    protected $m;
    
    /** @var array $c holds an array of configurations */
    private $c;

	/**
	 @param array $config array of elements, each of which represents a configuration for memcached such as default expiration, namespace etc.
 	 @throws RuntimeException If the memcached php extension is not loaded.
	 @throws InvalidArgumentException If the 'expiration' key of the passed param is not integer or negative.
	 */
    public function __construct(array $config)
    {
        if (!extension_loaded('memcached')) {
            throw new \RuntimeException("Extension memcached not loaded.");
        }

        if (isset($config['expiration']) && (!ctype_digit($config['expiration']) || $config['expiration']<0)) {
            throw new \InvalidArgumentException("Default expiration time is not valid.");
        }

        $this->c = $config;

        if (!isset($this->c['expiration']) || $this->c['expiration'] == '') {
            $this->c['expiration'] = self::DEFAULT_EXPIRATION;
        }

        if (!isset($this->c['namespace'])) {
            $this->c['namespace'] = '';
        }

        if (!isset($this->c['version'])) {
            $this->c['version'] = '';
        }

        if (!isset($this->c['logerror'])) {
            $this->c['logerror'] = false;
        }

        $this->m = new \Memcached();

        if (isset($config['server']) && is_array($config['server'])) {
            foreach ($config['server'] as $server) {
                if (isset($server['host']) && isset($server['port']) && isset($server['weight'])) {
                    $this->addServer($server['host'], $server['port'], $server['weight']);
                }
            }
        }

        //$this->m->setOption(\Memcached::OPT_BINARY_PROTOCOL, true);
    }

	/**
	 Add atomically an object in the memcached.
	 @param string $key name of the object to be stored.
 	 @param mixed $value value to be stored.
 	 @param int $expiration TTL for the key.
	 @throws InvalidArgumentException If the $key is empty.
	 @return boolean returns true if $key is successfully added, false if the key is already present or some error occured.
	 */
    public function add($key, $value, $expiration = null)
    {
        if (trim($key)=='') {
            throw new \InvalidArgumentException("Key is empty.");
        }

        if (is_null($expiration)) {
            $expiration = $this->c['expiration'];
        }

        if (!$this->m->add($this->createKey($key), $value, $expiration)) {
            $result_code = $this->m->getResultCode();
            if ($result_code === \Memcached::RES_NOTSTORED) {
                $this->logError(__METHOD__.":".__LINE__.":Unable to add key $key. Key already exists. Result code: $result_code. Result message: ".$this->m->getResultMessage());
            } else {
                $this->logError(__METHOD__.":".__LINE__.":Unable to add key $key. Result code: $result_code. Result message: ".$this->m->getResultMessage());
            }

            return false;
        }

        return true;
    }

	/**
	 set an object in the memcached.
	 @param string $key name of the object to be stored.
 	 @param mixed $value value to be stored.
 	 @param int $expiration TTL for the key.
	 @return boolean returns true if $value is successfully stored, false otherwise.
	 */
    public function set($key, $value, $expiration = null)
    {
        if (is_null($expiration)) {
            $expiration = $this->c['expiration'];
        }

        if (!$this->m->set($this->createKey($key), $value, $expiration)) {
            $this->logError(__METHOD__.":".__LINE__.":Unable to set key $key. Result code: ".$this->m->getResultCode().". Result message: ".$this->m->getResultMessage());

            return false;
        }

        return true;
    }

    public function get($key, callable $cb = null, &$cas_token = null)
    {
        $value = $this->m->get($this->createKey($key), $cb, $cas_token);

        $result_code = $this->m->getResultCode();
        //check for result code since value may have been stored FALSE
        if ($value === false && $result_code === \Memcached::RES_NOTFOUND) {
            $this->logError(__METHOD__.":".__LINE__.":Key $key does not exists. Result code: $result_code. Result message: ".$this->m->getResultMessage());
        }

        return $value;
    }

    /*
    --For PECL memcached 2.10--
    delete used with second argument 'time' returns false and set the error code and message for invalid arguments unless used without Memcached::OPT_BINARY_PROTOCOL.

    getResultCode() and getResultMessage() returns 38 and INVALID ARGUMENTS respectively.
    */
    /**public function delete($key, $time=0) {*/
    public function delete($key)
    {
        //if (!$this->m->delete($this->createKey($key), $time)) {
        if (!$this->m->delete($this->createKey($key))) {
            $result_code = $this->m->getResultCode();
            if ($result_code === \Memcached::RES_NOTFOUND) {
                $this->logError(__METHOD__.":".__LINE__.":Unable to delete key $key. Key doesn't exist. Result code: $result_code. Result message: ".$this->m->getResultMessage());
            } else {
                $this->logError(__METHOD__.":".__LINE__.":Unable to delete key $key. Result code: $result_code. Result message: ".$this->m->getResultMessage());
            }

            return false;
        }

        return true;
    }

    //*increment/decrement will not change TTL of a item
    //public function increment($key, $offset=1, $initial_value=0, $expiration=0) {
    public function increment($key, $offset = 1)
    {
        /*if (is_null($expiration)) {
            $expiration = $this->c['expiration'];
        }*/

        //PECL memcached >= 0.2.0
        //if (($val=$this->m->increment($this->createKey($key), $offset, $initial_value, $expiration)) === FALSE) {

        if (($val=$this->m->increment($this->createKey($key), $offset)) === false) {
            $this->logError(__METHOD__.":".__LINE__.":Unable to increment key $key. Result code: ".$this->m->getResultCode().". Result message: ".$this->m->getResultMessage());
        }

        return $val;
    }

    //*increment/decrement will not change TTL of a item
    //public function decrement($key, $offset=1, $initial_value=0, $expiration=0) {
    public function decrement($key, $offset = 1)
    {
        /*if (is_null($expiration)) {
            $expiration = $this->c['expiration'];
        }*/

        //PECL memcached >= 0.2.0
        //if (($val=$this->m->decrement($this->createKey($key), $offset, $initial_value, $expiration)) === FALSE) {

        if (($val=$this->m->decrement($this->createKey($key), $offset)) === false) {
            $this->logError(__METHOD__.":".__LINE__.":Unable to decrement key $key. Result code: ".$this->m->getResultCode().". Result message: ".$this->m->getResultMessage());
        }

        return $val;
    }

    public function addServer($host, $port, $weight = 0)
    {
        if (!$this->m->addServer($host, $port, $weight)) {
            $this->logError(__METHOD__.":".__LINE__.":Unable to add server. Result code: ".$this->m->getResultCode().". Result message: ".$this->m->getResultMessage());    //__METHOD__ gives "classname:methodname"

            return false;
        }

        return true;
    }

    //PECL memcached >= 2.0.0(only supported with binary protocol i.e. Memcached::OPT_BINARY_PROTOCOL set to true.)
    /*public function touch($key, $expiration) {
        if (!$this->m->touch($this->createKey($key), $expiration)) {
            $this->logError(__METHOD__.":".__LINE__.":Setting new expiration failed. Result code: ".$this->m->getResultCode().". Result message: ".$this->m->getResultMessage());

            return false;
        }

        return true;
    }*/

    public function createKey($key)
    {
        return md5($this->c['namespace'].$this->c['version'].$key);
        //return $this->c['namespace'].$this->c['version'].$key;
    }

    protected function logError($msg)
    {
        if ($this->c['logerror']) {
            error_log($msg);
        }
    }

    public function getServerList()
    {
        return $this->m->getServerList();
    }
}

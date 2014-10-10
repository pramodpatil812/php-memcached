<?php
namespace Pramod\Memcached;

require_once(realpath(dirname(__FILE__) . '/../lib/CMemcached.php'));

class CMemcachedTest extends \PHPUnit_Framework_TestCase
{
    /*public function testIsExceptionRaisedForExtensionNotLoaded() {
    	try {
    		new CMemcached(require_once(realpath(dirname(__FILE__) . '/../config/config.php')));
    	} catch(\RuntimeException $e) {
    		return;
    	}
    	$this->fail('An expected exception has not been raised.');
    }*/

    public function testIsExceptionRaisedForInvalidDefaultExpirationDataType() {
    	try {
    		new CMemcached(array('expiration'=>'invaliddata'));
    	} catch(\InvalidArgumentException $e) {
    		return;
    	}
    	
    	$this->fail('An expected exception has not been raised.');
    }
    
    public function testIsExceptionRaisedForInvalidDefaultExpirationDataType2() {
    	try {
    		new CMemcached(array('expiration'=>'36a00'));
    	} catch(\InvalidArgumentException $e) {
    		return;
    	}
    	
    	$this->fail('An expected exception has not been raised.');
    }
    
    public function testIsExceptionRaisedForInvalidDefaultExpirationDataType3() {
       	try {
    		new CMemcached(array('expiration'=>'-3600'));
    	} catch(\InvalidArgumentException $e) {
    		return;
    	}
    	
    	$this->fail('An expected exception has not been raised.');
    }
    
    public function testIsExceptionRaisedForInvalidDefaultExpirationDataType4() {
       	try {
    		new CMemcached(array('expiration'=>''));
    	} catch(\InvalidArgumentException $e) {
    		return;
    	}
    	
    	$this->fail('An expected exception has not been raised.');
    }


    public function testIsExceptionRaisedForInvalidDefaultExpirationDataType5() {
    	new CMemcached(array('expiration'=>'3600'));
    }
    
    public function testIsExceptionRaisedForInvalidDefaultExpirationDataType6() {
    	new CMemcached(array('expiration'=>3600));
    }

    //PENDING TEST
    /*public function testMultipleServer() {
    	$config = array(
			'server'=>array(
				array('host'=>'127.0.0.1', 'port'=>11211, 'weight'=>50),
				array('host'=>'localhost', 'port'=>11211, 'weight'=>50)
			),
			'namespace' => 'test1',
			'version' => '1',
			'expiration' => 3600,	//1 hour
			'logerror' => true
		);
		
		//$this->assertEquals($expected, $actual);
		//var_dump((new CMemcached($config))->getServerList());
		$this->assertEquals($config['server'], (new CMemcached($config))->getServerList());
    }*/
    
    public function testAdd() {
    	$obj = new CMemcached(require(realpath(dirname(__FILE__) . '/../config/config.php')));
    	$this->assertEquals(true, $obj->add('testkey1', 'testvalue1'));
    	$this->assertEquals(true, $obj->add('testkey2', 'testvalue2', 600));
    	$this->assertEquals(false, $obj->add('testkey2', 'testvalue2', 600));
    	
    	$obj1 = new CMemcached(array());
    	$obj1->addServer('nonexistentserver', 11211);
    	$this->assertEquals(false, $obj1->set('testkey2', 'testvalue2', 600));
    }
    
    public function testIsExceptionRaisedFromAddForEmptyKey() {
    	try {
    		$obj = new CMemcached(require(realpath(dirname(__FILE__) . '/../config/config.php')));
    		$obj->add('', 'testvalue', 600);
    	} catch(\InvalidArgumentException $e) {
    		return;
    	}
    	
    	$this->fail('An expected exception has not been raised.');
    }
    
    public function testIsExceptionRaisedFromAddForEmptyKey2() {
    	try {
    		$obj = new CMemcached(require(realpath(dirname(__FILE__) . '/../config/config.php')));
    		$obj->add('   ', 'testvalue', 600);
    	} catch(\InvalidArgumentException $e) {
    		return;
    	}
    	
    	$this->fail('An expected exception has not been raised.');
    }
    
    public function testSetWithDefaultExpiry() {
    	$obj = new CMemcached(require(realpath(dirname(__FILE__) . '/../config/config.php')));
    	$this->assertEquals(true, $obj->set('testkey3', 'testvalue3'));
    }
    
    public function testSetWithExpiry() {
    	$obj = new CMemcached(require(realpath(dirname(__FILE__) . '/../config/config.php')));
    	$this->assertEquals(true, $obj->set('testkey4', 'testvalue4', 600));
    	$this->assertEquals(true, $obj->set('testkey5', 'testvalue5', 600));
    }
    
    public function testGet() {
    	$obj = new CMemcached(require(realpath(dirname(__FILE__) . '/../config/config.php')));
    	$this->assertEquals('testvalue4', $obj->get('testkey4'));
    	$this->assertEquals('testvalue2', $obj->get('testkey2'));
    	
    	//with callback(testkey6 does not exists)
    	$this->assertEquals('testvalue6', $obj->get('testkey6', array($this, 'myCallback'), $cas_token));
    	
    	$this->assertEquals(false, $obj->get('nonexistentkey'));
    }
    
    //Read-through cache callbacks
    public function myCallback(\Memcached $memObj, $key, &$value) {
    	$value = 'testvalue6';
    	return true;
    }
    
	public function testIncrementAndDecrement() {
		$obj = new CMemcached(require(realpath(dirname(__FILE__) . '/../config/config.php')));
    	$obj->set('testcounter', 1, 1800);
    	
    	//increment
    	$this->assertEquals(2, $obj->increment('testcounter'));
    	$this->assertEquals(7, $obj->increment('testcounter', 5));
    	$this->assertEquals(7, $obj->get('testcounter'));
    	//$this->assertEquals(false, $obj->increment('testcounter', 'a'));
    	//$this->assertEquals(7, $obj->get('testcounter'));
    	$this->assertEquals(false, $obj->increment('nonexistentcounter'));
    	
    	//decrement
    	$this->assertEquals(6, $obj->decrement('testcounter'));
    	$this->assertEquals(3, $obj->decrement('testcounter', 3));
    	$this->assertEquals(3, $obj->get('testcounter'));
    	$this->assertEquals(false, $obj->decrement('nonexistentcounter'));
	}
	
	public function testDelete() {
    	$obj = new CMemcached(require(realpath(dirname(__FILE__) . '/../config/config.php')));
    	$this->assertEquals(true, $obj->delete('testkey1'));
    	
    	//$this->assertEquals(true, $obj->delete('testkey2', 10));
    	$this->assertEquals(false, $obj->get('testkey1'));    	
    	$this->assertEquals(false, $obj->delete('nonexistentkey'));
    }
    
    /*public function testTouch() {
    	$obj = new CMemcached(require(realpath(dirname(__FILE__) . '/../config/config.php')));
    	$this->assertEquals(true, $obj->set('testkey1', 1800));
    	$this->assertEquals(true, $obj->touch('testkey1', 7200));
    	$this->assertEquals(false, $obj->touch('nonexistentkey', 3600));
    }*/

    
}

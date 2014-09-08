<?php
namespace PHPMemcached;
require_once(realpath(dirname(__FILE__) . '/lib/CMemcached.php'));

try{
$m = new CMemcached(require_once(realpath(dirname(__FILE__) . '/config/config.php')));
} catch(MemcachedException $e) {
}

$m->add("key1",2,60);
var_dump($m->get("key1"));

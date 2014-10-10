php-memcached
=============

##Description
This libray is a wrapper for <a href="http://php.net/manual/en/book.memcached.php">Memcached PHP Extension</a>. Define configuration in config/config.php such as default expiration time, servers, namespace and version number.

Found some facts and possible bugs. To check and see follow these links:

http://php.net/manual/en/memcached.decrement.php#115685
http://php.net/manual/en/memcached.decrement.php#115711
http://php.net/manual/en/memcached.delete.php#115712
http://php.net/manual/en/memcached.touch.php#115716

##Coding Style
Library follow <a href="http://www.php-fig.org/psr/psr-0/">PSR0</a>, <a href="http://www.php-fig.org/psr/psr-1/">PSR1</a> and <a href="http://www.php-fig.org/psr/psr-2/">PSR2</a> coding standards. Used <a href="http://pear.php.net/package/PHP_CodeSniffer/">PHP_CodeSniffer</a> and <a href="http://cs.sensiolabs.org/">PHP Coding Standards Fixer</a> for automating PSR standards checking and autofixing the sniff violations.

PSR standards have not been followed for unit tests.

##To do
Add a support for memcache php extension. User can switch between the two(memcached/memcache) via configuration. Memcached is improved form of Memcache php extension and hence usage of it is preferred.
php-memcached
=============

##Description
This libray is a wrapper for memcached php extension (http://php.net/manual/en/book.memcached.php). Define configuration in config/config.php such as default expiration time, servers, namespace and version number.

Found some facts and possible bugs. To check and see follow these links:

http://php.net/manual/en/memcached.decrement.php#115685
http://php.net/manual/en/memcached.decrement.php#115711
http://php.net/manual/en/memcached.delete.php#115712
http://php.net/manual/en/memcached.touch.php#115716


##To do
Add a support for memcache php extension. User can switch between the two(memcached/memcache) via configuration. Memcached is improved form of Memcache php extension and hence usage of it is preferred.

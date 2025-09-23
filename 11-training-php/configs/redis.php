<?php
$redis = new Redis();
// Kết nối qua service name trong docker-compose (không phải 127.0.0.1)
$redis->connect('web-redis', 6379);
return $redis;

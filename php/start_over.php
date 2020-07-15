<?php
require './databases/redis.php';
require './all_function.php';
require './config/redis_config.php';

/**
 * 建立redis链接
 */
$redis = RedisCache::getInstance(REDIS_HOST,REDIS_PORT);
$redis->flush_all();
echo '1';

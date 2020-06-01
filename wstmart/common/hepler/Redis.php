<?php
/**
 * Created by PhpStorm.
 * User: yangxiushan
 * Date: 2020-05-22
 * Time: 16:41
 */

namespace wstmart\common\helper;

class Redis
{
    public static function factory()
    {
        $redisConfig = config('account.redis');
        $redis = new \Redis();
        $redis->connect($redisConfig["host"], $redisConfig["port"], $redisConfig["timeout"]);

        if ($redisConfig["password"] != "") {
            $redis->auth($redisConfig["password"]);
        }
        return $redis;
    }
}
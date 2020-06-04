<?php
//缓存物流信息， 有效期5分钟
function cacheOrderExpress($orderId, $expressInfo, Redis $redis) {
    $key = "guangjiaohui:orderExpress:$orderId";
    $redis->setex($key, 3 * 86400, $expressInfo);
}

//获取物流信息
function getOrderExpress($orderId,  Redis $redis) {
    $key = "guangjiaohui:orderExpress:$orderId";
    $data = $redis->get($key);
    if ($data) {
        return $data;
    }
    return null;
}

// 缓存溯源token
function cacheVonetracerToken(array $data, Redis $redis)
{
    $key = "guangjiaohui:vonetracerToken";
    $time = $data["expires_in"];
    $cache = json_encode($data);
    $redis->setex($key, $time, $cache);
}

// 获取溯源token
function getVonetracerToken(Redis $redis)
{
    $key = "guangjiaohui:vonetracerToken";
    $data = $redis->get($key);
    if ($data) {
        return json_decode($data,true);
    }
    return null;
}
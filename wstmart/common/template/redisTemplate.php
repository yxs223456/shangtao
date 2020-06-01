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
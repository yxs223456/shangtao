<?php

namespace wstmart\common\helper;

use think\Exception;
use think\facade\Log;

/**
 * Created by PhpStorm.
 * User: lichaoyang
 * Date: 2020/5/18
 * Time: 下午5:45
 */
class Vonetracer
{
    private static $baseUrl = "https://www.vonetracer.com";
    private static $key = "";
    private static $private = "";
    private static $username = "";
    private static $password = "";

    /**
     * 初始化配置
     */
    private static function initConfig()
    {
        $config = config('account.vonetracer');
        self::$key = $config["key"] ?? "";
        self::$private = $config["private"] ?? "";
        self::$username = $config["username"] ?? "";
        self::$password = $config["password"] ?? "";
    }

    /**
     * 获取token
     */
    public static function getToken()
    {
        self::initConfig();
        $query = array(
            'scope' => 'server',
            'grant_type' => 'password',
            'username' => self::$username,
            'password' => self::$password
        );
        $url = trim(self::$baseUrl, "/") . "/auth/oauth/token?" . http_build_query($query);
        $header = array(
            "ClientType: 1",
            "Authorization: Basic " . base64_encode(self::$key . ":" . self::$private),
            "User-Agent:PostmanRuntime/7.24.1"
        );
        return self::curl($url, $header);
    }

    /**
     * 缓存token
     *
     * @return mixed
     */
    private static function getAccessToken()
    {
        $redis = Redis::factory();
        $data = getVonetracerToken($redis);
        if (empty($data)) {
            $data = self::getToken();
            cacheVonetracerToken($data, $redis);
        }

        return $data["access_token"];
    }

    /**
     * 数据上链
     *
     * @param $templateNo string 模板编号
     * @param $batchNo string 批次编号
     * @param $batchName string 批次名称
     * @param array $productCode 产品编号
     * @param string $parentBatchNo 父批次号
     * @return mixed
     * @throws Exception
     */
    public static function upload($templateNo, $batchNo, $batchName, array $productCode, $parentBatchNo = '')
    {
        if (empty($templateNo) || empty($batchName) || empty($batchNo) || empty($productCode)) {
            throw new Exception("参数错误");
        }
        $data = [
            "templateNo" => $templateNo,
            "batchNo" => $batchNo,
            "batchName" => $batchName,
            "parentBatchNo" => $parentBatchNo,
            "productCode" => $productCode
        ];
        $request = array_filter($data);

        $url = trim(self::$baseUrl, "/") . "/org/api/upload";
        $accessToken = self::getAccessToken();
        $header = array(
            "Authorization: Bearer " . $accessToken,
            "User-Agent:PostmanRuntime/7.24.1",
            "Content-Type:application/json",
        );
        return self::curl($url, $header, $request, "POST");
    }

    /**
     * 根据批次号查询二维码地址
     *
     * @param $batchNo string 批次号
     * @return mixed
     * @throws Exception
     */
    public static function productrecord($batchNo)
    {
        if (empty($batchNo)) {
            throw new Exception("参数异常");
        }
        $url = trim(self::$baseUrl, "/") . "/org/api/productrecord/" . $batchNo;
        $accessToken = self::getAccessToken();
        $header = array(
            "Authorization: Bearer " . $accessToken,
            "User-Agent:PostmanRuntime/7.24.1",
            "Content-Type:application/json",
        );
        return self::curl($url, $header);
    }

    /**
     * 根据批次号查询批次信息
     *
     * @param $batchNo
     * @return mixed
     * @throws Exception
     */
    public static function productbatch($batchNo)
    {
        if (empty($batchNo)) {
            throw new Exception("参数异常");
        }
        $url = trim(self::$baseUrl, "/") . "/org/api/productbatch/" . $batchNo;
        $accessToken = self::getAccessToken();
        $header = array(
            "Authorization: Bearer " . $accessToken,
            "User-Agent:PostmanRuntime/7.24.1",
            "Content-Type:application/json",
        );
        return self::curl($url, $header);
    }

    /**
     * 基础请求
     *
     * @param $url
     * @param array $header
     * @param array $data
     * @param string $type
     * @return mixed
     */
    private static function curl($url, array $header = array(), $data = array(), $type = 'GET')
    {
        //初始化 curl
        $ch = curl_init();
        //设置目标服务器
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        //超时时间
        curl_setopt($ch, CURLOPT_TIMEOUT, 60);
        //设置header
        if (!empty($header)) {
            curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
        }
        if ($type === 'POST') {
            // post数据
            curl_setopt($ch, CURLOPT_POST, 1);
            // post的变量
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data,JSON_UNESCAPED_UNICODE));
        }

        $output = curl_exec($ch);
        $err = curl_error($ch);

        if (!empty($err)) {
            Log::error('vonetracer curl 连接出错', [$url, $data, $err]);
        }
        curl_close($ch);

        return json_decode($output, true);
    }

}
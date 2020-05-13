<?php
/**
 * Created by PhpStorm.
 * User: yangxiushan
 * Date: 2020-05-13
 * Time: 17:52
 */
namespace wstmart\common\helper;

use OSS\OssClient;

class AliyunOss
{
    protected static $accessKeyId = "";
    protected static $accessKeySecret = "";
    protected static $endpoint = "";
    protected static $bucket = "";

    private static function getConfig()
    {
        $config = config("account.oss");
        self::$accessKeyId = $config["key_id"];
        self::$accessKeySecret = $config["key_secret"];
        self::$endpoint = $config["endpoint"];
        self::$bucket = $config["bucket"];
    }

    /**
     * @param $filename string 文件名称
     * @param string $content The content object
     * @return array
     * @throws \OSS\Core\OssException
     */
    public static function putObject($filename, $content)
    {
        self::getConfig();

        $ossClient = new OssClient(self::$accessKeyId, self::$accessKeySecret, self::$endpoint);
        $result = $ossClient->putObject(self::$bucket, $filename, $content);
        $rs = [
            'url' => $result['info']['url']
        ];
        return $rs;
    }
}
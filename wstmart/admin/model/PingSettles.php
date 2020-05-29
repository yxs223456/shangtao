<?php 
namespace wstmart\admin\model;

class PingSettles extends Base{
    protected $pk = 'id';

    const SETTLE_TYPE_BANK = 1;
    const SETTLE_TYPE_ALI = 2;
    const SETTLE_TYPE_WX = 3;
    const SETTLE_TYPE_WX_JSAPI = 4;
    const SETTLE_TYPE_WX_LITE = 5;

    const CHANNEL_LIST = array(
        self::SETTLE_TYPE_BANK => 'bank_account',
        self::SETTLE_TYPE_ALI => 'alipay',
        self::SETTLE_TYPE_WX => 'wx',
        self::SETTLE_TYPE_WX_JSAPI => 'wx_pub',
        self::SETTLE_TYPE_WX_LITE => 'wx_lite',
    );

    /**
     * 渠道请求参数转换
     *
     * @param $type
     * @return mixed|string
     */
    public static function getChannel($type)
    {
        return isset(self::CHANNEL_LIST[$type]) ? self::CHANNEL_LIST[$type] : "";
    }

    /**
     * 获取默认数据
     *
     * @param $shopId
     * @return array
     */
    public function getSettles($shopId)
    {
        $ret = array(
            'bankUserName' => '',
            'bankNo' => '',
            'transferType' => '',
            'openBankCode' => '',
            'aliAccount' => '',
            'aliName' => '',
            'aliTransferType' => '',
            'accountType' => '',
            'wxOpenId' => '',
            'wxJsapiOpenId' => '',
            'wxLiteOpenId' => '',
        );

        $data = $this->where("shopId", $shopId)
            ->select()->toArray();
        if (empty($data)) {
            return $ret;
        }

        foreach ($data as $value) {
            if ($value["settleType"] == self::SETTLE_TYPE_BANK) {
                $ret["bankUserName"] = $value["name"];
                $ret["bankNo"] = $value["account"];
                $ret["transferType"] = $value["type"];
                $ret["openBankCode"] = $value["openBankCode"];
            }

            if ($value["settleType"] == self::SETTLE_TYPE_ALI) {
                $ret["aliName"] = $value["name"];
                $ret["aliAccount"] = $value["account"];
                $ret["aliTransferType"] = $value["type"];
                $ret["accountType"] = $value["accountType"];
            }

            if ($value["settleType"] == self::SETTLE_TYPE_WX) {
                $ret["wxOpenId"] = $value["account"];
            }

            if ($value["settleType"] == self::SETTLE_TYPE_WX_JSAPI) {
                $ret["wxJsapiOpenId"] = $value["account"];
            }

            if ($value["settleType"] == self::SETTLE_TYPE_WX_LITE) {
                $ret["wxLiteOpenId"] = $value["account"];
            }
        }

        return $ret;
    }
}
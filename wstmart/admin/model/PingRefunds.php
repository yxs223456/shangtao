<?php 
namespace wstmart\admin\model;

use think\Db;
use think\Exception;
use think\facade\Log;
use wstmart\common\helper\Ping;

class PingRefunds extends Base{
    protected $pk = 'id';

    const STATUS_REFUNDING = 0;
    const STATUS_REFUND_SUCCESS = 1;

    /**
     * 退款
     */
    public function orderRefund($refund,$order){

        try {
            $tradeNo = empty($order['tradeNo']) ? "" : $order['tradeNo'];
            $backMoney = empty($refund["backMoney"]) ? 0 : (int)bcmul($refund["backMoney"], 100);
            $pingUserId = Db::name("shops")->where("shopId", $order["shopId"])->value("pingUserId");
            $pingOrderId = Db::name("ping_pays")->where("outTradeNo", $tradeNo)->value("pingOrderId");
            if (empty($tradeNo) || empty($backMoney) || empty($pingUserId) || empty($pingOrderId)) {
                Log::error("[refund error] : " . $order["orderId"] . "tradeNo,backMoney,pingOrderId or pingUserId not empty");
                return WSTReturn("退款失败", -1);
            }

            $royaltyUser = array(
                'user' => $pingUserId,
                'amount_refunded' => $backMoney
            );
            $metaData = array("orderId" => $order["orderId"]);
            $pingRefund = Ping::orderRefund($pingOrderId, "订单退款", array($royaltyUser), $metaData);

            $insert = array();
            $insert['shopId'] = $order["shopId"];
            $insert['orderId'] = $order["orderId"];
            $insert['pingOrderId'] = $pingOrderId;
            $insert['amount'] = $backMoney;
            $insert['royaltyUsers'] = json_encode($royaltyUser);
            $insert['responseData'] = json_encode($pingRefund->jsonSerialize());
            $insert['notifyData'] = "";
            $insert['createTime'] = date("Y-m-d H:i:s");
            $this->insertGetId($insert);

            // 更新订单退款成功
            $order->isRefund = 1;
            $order->save();

            //修改退款单信息
            $content = input('post.content');
            $refund->refundRemark = $content;
            $refund->refundTime = date('Y-m-d H:i:s');
            $refund->refundStatus = 2;
            $refund->save();

        } catch (\Throwable $e) {
            return WSTReturn($e->getMessage(), -1);
        }
        return WSTReturn("退款成功", 1);
    }
}
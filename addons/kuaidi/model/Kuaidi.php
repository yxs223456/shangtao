<?php
namespace addons\kuaidi\model;
use think\addons\BaseModel as Base;
use think\Db;
use think\facade\Env;
use wstmart\common\helper\Redis;

include_once Env::get('root_path') . 'wstmart/common/hepler/Redis.php';

/**
 * ============================================================================
 * WSTMart多用户商城
 * 版权所有 2016-2066 广州商淘信息科技有限公司，并保留所有权利。
 * 官网地址:http://www.wstmart.net
 * 交流社区:http://bbs.shangtao.net
 * 联系QQ:153289970
 * ----------------------------------------------------------------------------
 * 这不是一个自由软件！未经本公司授权您只能在不用于商业目的的前提下对程序代码进行修改和使用；
 * 不允许对程序代码以任何形式任何目的的再发布。
 * ============================================================================
 * 快递查询业务处理
 */
class Kuaidi extends Base{
	
	/**
	 * 绑定勾子
	 */
	public function install(){
		Db::startTrans();
		try{
			$hooks = array("adminDocumentOrderView","homeDocumentOrderView","afterQueryUserOrders","mobileDocumentOrderList","wechatDocumentOrderList");
			$this->bindHoods("Kuaidi", $hooks);
			
			Db::commit();
			return true;
		}catch (\Exception $e) {
			Db::rollback();
			return false;
		}
	}
	
	/**
	 * 解绑勾子
	 */
	public function uninstall(){
		Db::startTrans();
		try{
			$hooks = array("adminDocumentOrderView","homeDocumentOrderView","afterQueryUserOrders","mobileDocumentOrderList","wechatDocumentOrderList");
			$this->unbindHoods("Kuaidi", $hooks);
			
			Db::commit();
			return true;
		}catch (\Exception $e) {
			Db::rollback();
			return false;
		}
	}
	
	public function getExpress($orderId){
		$conf = $this->getConf("Kuaidi");
		$express = Db::name('orders')->where(["orderId"=>$orderId])->field(['expressId','expressNo'])->find();
		return $express;
	}
	
	public function getOrderExpress($orderId){
	    //先从缓存读取数据
        $redis = Redis::factory();
        $expressLogs = getOrderExpress($orderId, $redis);
        if ($expressLogs) {
            return $expressLogs;
        }

		$conf = config("account.kuaidi100");
		$express = Db::name('orders')->where(["orderId"=>$orderId])->field(['expressId','expressNo'])->find();
		
		if($express["expressId"]>0){
			$expressId = $express["expressId"];
			$row = Db::name('express')->where(["expressId"=>$expressId])->find();
			$typeCom =  strtolower($row["expressCode"]); //快递公司在快递100的编码
			$typeNu = $express["expressNo"]; //快递单号
			
			$appKey= $conf["key"];
			$customer = $conf["customer"];
			$postData["customer"] = $customer;$postData["param"] = json_encode([
			    "com" => $typeCom, //查询的快递公司的编码， 一律用小写字母
			    "num" => $typeNu, //查询的快递单号
            ]);

            $url='https://poll.kuaidi100.com/poll/query.do';
            $postData["sign"] = md5($postData["param"].$appKey.$postData["customer"]);
            $postData["sign"] = strtoupper($postData["sign"]);
            $expressLog = $this->curl($url, "post", $postData);

            $result = json_decode($expressLog, true);
            if (isset($result["data"])) {
                cacheOrderExpress($orderId, $expressLog, $redis);
            }
			return $expressLog;
		}
		
	}
	
	public function getOrderInfo(){
		$data = array();
		$orderId = input("orderId");
		$data["express"] = Db::name('orders o')->join('__EXPRESS__ e', 'o.expressId=e.expressId')->where(["orderId"=>$orderId])->field(['e.expressId','expressNo','expressName'])->find();
		$data["goodlist"] = Db::name('orders o')->join('__ORDER_GOODS__ og','o.orderId=og.orderId')->where(["o.orderId"=>$orderId])->field(["goodsId","goodsImg"])->limit(1)->select();
		return $data;
	}

    function curl($url, $method = 'get', $postData = null, $isPostDataJsonEncode = false, $isResponseJson = false, $cookie = null, $header = null, $isReturnHeader = false) {
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        if (stripos($url, 'https') !== false) {
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        }
        if ($isReturnHeader) {
            curl_setopt($ch, CURLOPT_HEADER, 1);
        }
        if (strtolower($method) == 'post') {
            curl_setopt($ch, CURLOPT_POST, 1);
            if ($isPostDataJsonEncode && is_array($postData)) {
                $postData = json_encode($postData, JSON_UNESCAPED_UNICODE);
            }
            curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
        }
        if (!empty($cookie)) {
            curl_setopt($ch, CURLOPT_COOKIE, $cookie);
        }
        if (!empty($header) && is_array($header) && count($header)) {
            curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
        }
        $data = curl_exec($ch);
        $err = curl_error($ch);
        if ($err) {
            $log = json_encode([
                "url" => $url,
                "data" => $postData,
                "header" => $header,
                "cookie" => $cookie,
                "reason" => $err,
            ], JSON_UNESCAPED_UNICODE);
            \think\facade\Log::write("curl exec error: $log");
            return false;
        }
        if ($isResponseJson) {
            $data = json_decode($data, true);
        }
        return $data;
    }
	

	public  function getOrderDeliver($orderId){
		$rs = Db::name('orders o')->where(["orderId"=>$orderId])->field("deliverType,orderStatus,expressNo")->find();
		return $rs;
	}
	
	
	
}

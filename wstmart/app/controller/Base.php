<?php
namespace wstmart\app\controller;
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
 * 默认控制器
 */
use think\Controller;
use think\Db;
class Base extends Controller{
    /**
     * 域名
     */
    public function domain(){
    	return url('/','','',true);
    }
    /**
	 * 获取验证码
	 */
	public function getVerify(){
		WSTVerify();
	}
    // 权限验证方法
    protected function checkAuth(){
        $tokenId = input('tokenId');
        if($tokenId==''){
            $rs = json_encode(WSTReturn('您还未登录',-999));
            die($rs);
        }
        $userId = Db::name('app_session')->where("tokenId='{$tokenId}'")->value('userId');
        if(empty($userId)){
            $rs = json_encode(WSTReturn('登录信息已过期,请重新登录',-999));
            die($rs);
        }
        return true;
    }
    /**
     * 上传图片
     */
    public function uploadPic(){
        return WSTUploadPic(0);
    }
    /**
    * 获取插件状态
    */
    public function getAddonStatus(){
        $addons = ['Auction','Kuaidi','Coupon','Reward','Integral','Groupon','Distribut','Wstim','Thirdlogin'];
        $rs = Db::name('addons')->where('dataFlag',1)->field('name,status')->select();
        $arr = [];
        foreach ($rs as $k=>$v) {
            if(in_array($v['name'], $addons)){
                $arr[$v['name']] = ($v['status']==1);
            }
        }
        if(isset($arr['Thirdlogin']) && $arr['Thirdlogin']==true ){
            $config = Db::name('addons')->where(['dataFlag'=>1,'name'=>'Thirdlogin'])->value('config');
            $config = json_decode($config,true);
            // 获取开启了哪些第三方登录
            $arr['ThirdloginCfg'] = $config['thirdTypes'];
        }
        return json_encode(WSTReturn('ok',1,$arr));
    }
}

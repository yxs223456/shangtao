<?php
/**
 * Created by PhpStorm.
 * User: yangxiushan
 * Date: 2020-04-22
 * Time: 17:18
 */

namespace wstmart\common\model;

use think\Db;
use think\facade\Env;

class ShopAdminModel extends Base
{
    protected $pk = "id";
    protected $table = "st_shop_admin";

    //查询用户手机是否存在
    public function checkUserPhone($userPhone){
        $dbo = $this->where(["userPhone"=>$userPhone]);

        $rs = $dbo->count();
        if($rs>0){
            return WSTReturn("手机号已存在!");
        }else{
            return WSTReturn("",1);
        }
    }

    /**
     * 商家注册
     */
    public function regist($loginSrc = 0){
        $data = array();
        $data['loginName'] = input("post.loginName");
        $data['loginPwd'] = input("post.loginPwd");
        $data['reUserPwd'] = input("post.reUserPwd");
        $startTime = (int)session('VerifyCode_userPhone_Time');
        if((time()-$startTime)>300){
//            return WSTReturn("验证码已超过有效期!");
        }
        $loginName = session('VerifyCode_userPhone');
        if($data['loginName']!=$loginName){
            return WSTReturn("注册手机号与验证手机号不一致!");
        }
        //检测账号是否存在

        $crs = WSTCheckShopAdminPhoneExists($loginName);
        if($crs['status']!=1)return $crs;
        $decrypt_data = WSTRSA($data['loginPwd']);
        $decrypt_data2 = WSTRSA($data['reUserPwd']);
        if($decrypt_data['status']==1 && $decrypt_data2['status']==1){
            $data['loginPwd'] = $decrypt_data['data'];
            $data['reUserPwd'] = $decrypt_data2['data'];
        }else{
            return WSTReturn('注册失败');
        }
        if($data['loginPwd']!=$data['reUserPwd']){
            return WSTReturn("两次输入密码不一致!");
        }
        foreach ($data as $v){
            if($v ==''){
                return WSTReturn("注册信息不完整!");
            }
        }
        $mobileCode = input("post.mobileCode");
        //请允许手机号码注册
        $data['userPhone'] = $loginName;
        $verify = session('VerifyCode_userPhone_Verify');
        if($mobileCode=="" || $verify != $mobileCode){
            return WSTReturn("短信验证码错误!");
        }

        $shopAdminInfo = [
            "userPhone" => $data["userPhone"],
            "pwd" => md5($data["loginPwd"]),
        ];

        Db::startTrans();
        try{
            $shopAdminId = $this->data($shopAdminInfo)->save();
            if(false !== $shopAdminId){
                Db::commit();
                return WSTReturn("注册成功",1);
            }
        }catch (\Exception $e) {
            Db::rollback();
        }
        return WSTReturn("注册失败!");
    }

    //商家登录
    public function checkLogin($loginSrc = 0){
        $loginName = input("post.loginName");
        $loginPwd = input("post.loginPwd");
        $code = input("post.verifyCode");
        $typ = (int)input("post.typ");
        $rememberPwd = input("post.rememberPwd",1);
        if(!WSTVerifyCheck($code) && strpos(WSTConf("CONF.captcha_model"),"4")>=0){
            return WSTReturn('验证码错误!');
        }
        $decrypt_data = WSTRSA($loginPwd);
        if($decrypt_data['status']==1){
            $loginPwd = $decrypt_data['data'];
        }else{
            return WSTReturn('登录失败');
        }
        $rs = $this->where("userPhone",$loginName)->find();
        if(!empty($rs)){
            session('WST_USER', $rs);
            if($rs['pwd']!=md5($loginPwd))return WSTReturn("密码错误");
            $shopAdminId = $rs['id'];
            $shop = Db::name("shops")->where("shopAdminId", $shopAdminId)->find();
            if(empty($shop)){
                return WSTReturn('您还没申请店铺!', -2);
            }
            $now = date("Y-m-d H:i:s");
            $this->where("id",$rs["id"])->update(["lastTime"=>$now]);
            session('WST_SHOP', $shop);
            $WST_SHOP_ADMIN = session('WST_USER');
            $WST_SHOP_ADMIN['tempShopId'] = $shop["shopId"];
            $WST_SHOP_ADMIN['lastTime'] = $now;
            session('WST_USER',$WST_SHOP_ADMIN);
            if($shop["shopStatus"] == -2){
                return WSTReturn("店铺已停用，不能登录!",-1);
            }
            if ($shop["applyStatus"] == 0) {
                return WSTReturn('请继续申请店铺', -3);
            }
            if ($shop["shopStatus"] == -1 || $shop["shopStatus"] == 0) {
                return WSTReturn('请继续申请店铺', -3);
            }

            //记住密码
            cookie("loginName", $loginName, time()+3600*24*90);
            if($rememberPwd == "on"){
                $datakey = md5($rs['userPhone'])."_".md5($rs['pwd']);
                $key = "";
                //加密
                require Env::get('root_path') . 'extend/org/Base64.php';
                $base64 = new \org\Base64();
                $loginKey = $base64->encrypt($datakey, $key);

                cookie("loginPwd", $loginKey, time()+3600*24*90);
            }else{
                cookie("loginPwd", null);
            }

            return WSTReturn("登录成功","1");

        }
        return WSTReturn("账号不存在");
    }
}
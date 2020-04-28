<?php
namespace wstmart\admin\model;

use think\Db;
use think\facade\Log;

/**
 * Class ShopAdmin
 * @package wstmart\admin\model
 *
 * 商店管理员表
 */
class ShopAdmin extends Base{
	protected $pk = 'id';

    /**
     * 检测登陆名称是否重复
     *
     * @param $loginName
     * @param int $shopId
     * @return bool
     */
    public function checkLoginName($loginName, $shopId = 0)
    {
        $dbo = $this->where(['userPhone' => $loginName]);
        if ($shopId > 0) {
            $data = Db::name("shops")->field("shopAdminId")->where("shopId", $shopId)->find();
            $adminId = $data["shopAdminId"];
            !empty($adminId) && $dbo->where('id', '<>', $adminId);
        }
        return boolval($dbo->Count());
    }

    /**
     * 更新用户信息
     *
     * @param $shopId
     * @param $id
     * @param $name
     * @param $pwd
     */
    public function updateAdmin($shopId,$id, $name, $pwd)
    {
        $data = $this->field("userPhone,pwd")->where(["id" => $id])->find();
        // 没有用户修复数据
        if (empty($data)) {
            $add = array(
                'userPhone' => $name,
                'pwd' => md5($pwd),
                'createTime' => date("Y-m-d H:i:s")
            );
            model("ShopAdmin")->save($add);
            $re = Db::name("shops")->where("shopId", $shopId)->update(array("shopAdminId" => model("ShopAdmin")->id));
            if (!$re) {
                Log::error("[{$shopId} 更新登陆用户shopAdminId失败]: shopAdminId = ".$re);
            }
            return;
        }

        $update = array();
        if ($data['userPhone'] != $name) {
            $update['userPhone'] = $name;
        }
        if ($pwd != $data['pwd'] && md5($pwd) != $data['pwd']) {
            $update['pwd'] = md5($pwd);
        }
        !empty($update) && $this->where("id", $id)->update($update);
    }
}

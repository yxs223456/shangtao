<?php
namespace wstmart\app\controller;
use wstmart\common\model\UserAddress as M;
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
 * 用户地址控制器
 */
class UserAddress extends Base{
	// 前置方法执行列表
    protected $beforeActionList = [
        'checkAuth'
    ];
	/**
	 * 地址管理
	 */
	public function index(){
		$m = new M();
		$userId = model('app/index')->getUserId();
		$addressList = $m->listQuery($userId);
		//获取省级地区信息
		$area = model('app/Areas')->listQuery(0);
		$data = [];
		// 地区信息
		$data['area'] = $area;
		// 已保存的用户地址列表
		$data['list'] = $addressList;

		// type为1时,代表由结算页面跳转过来,设置默认地址按钮,变为选择收货地址
		// type为0时,代表用户直接进入本页面、可直接设置默认地址
		$data['type'] = (int)input('type');

		// 结算页面所选中的地址Id,用于进入用户地址页面时,设置哪个地址为选中
		$data['addressId'] = (int)input('addressId');

		return json_encode(WSTReturn('请求成功', 1, $data));
	}
	/**
	 * 获取地址信息
	 */
	public function getById(){
		$m = new M();
		$userId = model('app/index')->getUserId();
		$rs = $m->getById(input('post.addressId/d'), $userId);
		// 查询到为空,即为新增收货地址,返回省级地址
		$areaM = model('app/Areas');
		if(empty($rs)){
			$rs = $areaM->listQuery(0);
		}else{
			// 获取地区数据
			$rs['area1'] = $areaM->listQuery(0);

			// $rs['area2'] = $areaM->listQuery($rs['areaId2']);
		}
		return json_encode(WSTReturn('请求成功',1,$rs));

	}
	/**
	 * 设置为默认地址
	 */
	public function setDefault(){
		$m = new M();
		$userId = model('app/index')->getUserId();
		$rs = $m->setDefault($userId);
		return json_encode($rs);
	}
	/**
     * 新增/编辑地址
     */
    public function edits(){
        $m = new M();
        $userId = (int)model('app/index')->getUserId();
        if(input('post.addressId/d')){
        	$rs = $m->edit($userId);
        }else{
        	$rs = $m->add($userId);
        } 
        return json_encode($rs);
    }
    /**
     * 删除地址
     */
    public function del(){
    	$m = new M();
    	$userId = (int)model('index')->getUserId();
    	return json_encode($m->del($userId));
    }
}

<?php
namespace wstmart\app\controller;
use wstmart\common\model\GoodsCats;
use wstmart\common\model\Attributes as AT;
use think\Cache;
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
 * 商品控制器
 */
class Goods extends Base{
    protected $beforeActionList = [
          'checkAuth' => ['only'=>'historyquery']
    ];
    // 获取猜你喜欢
    public function getGuess(){
        $catId = (int)input('catId');
        $goodsIds = explode(',',input('goodsIds'));
        if(!empty($goodsIds))$goodsIds = array_unique($goodsIds);
        // 猜你喜欢6件商品
        $like = model('Tags')->getGuessLike($catId,6,$goodsIds);
        foreach($like as $k=>$v){
            // 删除无用字段
            unset($like[$k]['shopName']);
            unset($like[$k]['shopId']);
            unset($like[$k]['goodsSn']);
            unset($like[$k]['goodsStock']);
            unset($like[$k]['marketPrice']);
            unset($like[$k]['isSpec']);
            unset($like[$k]['appraiseNum']);
            unset($like[$k]['visitNum']);
            // 替换商品图片
            $like[$k]['goodsImg'] = WSTImg($v['goodsImg'],3);
        }
        $rs = [
            'domain'=>$this->domain(),
            'goods'=>$like
        ];
        return json_encode(WSTReturn('ok',1,$rs));
    }
    // 获取商品主图及商品名称
    public function preloadGoods(){
        $m = model('app/goods');
        $rs = $m->preloadGoods();
        $rs['domain'] = $this->domain();
        return json_encode(WSTReturn('success',1,$rs));
    }
    /**
    * 商品咨询
    */
    public function getGoodsConsult(){
        $rs = model('GoodsConsult')->firstQuery(input('goodsId/d'));
        return json_encode(WSTReturn('ok',1,$rs));
    }
	/**
	 * 商品主页
	 */
	public function index(){
		$m = model('goods');
        $goods = $m->getBySale(input('goodsId/d'));
        // 找不到商品记录
        if(empty($goods))return json_encode(WSTReturn('未找到商品记录',-1));
        // 删除无用字段
        WSTUnset($goods,'goodsSn,productNo,isSale,isBest,isHot,isNew,isRecom,goodsCatIdPath,goodsCatId,shopCatId1,shopCatId2,brandId,goodsStatus,saleTime,goodsSeoKeywords,illegalRemarks,dataFlag,createTime,read');
        $goods['domain'] = $this->domain();
        if($goods['isFreeShipping'] == 1){
            $goods['isFreeShipping'] = '免运费';
        }elseif ($goods['isFreeShipping'] == 0) {
            $goods['isFreeShipping'] = sprintf("%.2f",$goods['shop']['freight']);
        }
        return json_encode(WSTReturn('请求成功',1,$goods));
	}
    // 获取商品详情
    public function goodsDetail(){
        $detail = model('goods')->getGoodsDetail((int)input('goodsId'));
        if(empty($detail))die('未找到该商品详情');
        $detail['goodsDesc'] = htmlspecialchars_decode($detail['goodsDesc']);
        $this->assign('goodsDesc',$detail);
        return $this->fetch('goods_desc');
    }



    /**
     * 获取商品列表
     */
    public function pageQuery(){
    	$m = model('goods');
    	$gc = new GoodsCats();
    	$catId = (int)input('catId');
    	if($catId>0){
    		$goodsCatIds = $gc->getParentIs($catId);
    	}else{
    		$goodsCatIds = [];
    	}

         //处理已选属性
        $vs = input('vs');
        $vs = ($vs!='')?explode(',',$vs):[];
        $at = new AT();
        $goodsFilter = $at->listQueryByFilter((int)input('catId/d'));
        $ngoodsFilter = [];
        if(!empty($vs)){
            // 存在筛选条件,取出符合该条件的商品id,根据商品id获取可选属性进行拼凑
            $goodsId = model('goods')->filterByAttributes();

            $attrs = model('Attributes')->getAttribute($goodsId);
            // 去除已选择属性
            foreach ($attrs as $key =>$v){
                if(!in_array($v['attrId'],$vs)){$ngoodsFilter[] = $v;}
            }
        }else{
            // 当前无筛选条件,取出分类下所有属性
            foreach ($goodsFilter as $key =>$v){
                if(!in_array($v['attrId'],$vs))$ngoodsFilter[] = $v;
            }
        }

    	$rs['goodsPage'] = $m->pageQuery($goodsCatIds);

        foreach ($ngoodsFilter as $k => $val) {
           $result = array_values(array_unique($ngoodsFilter[$k]['attrVal']));

           $ngoodsFilter[$k]['attrVal'] = $result;
        }
        $rs['goodsFilter'] = $ngoodsFilter;
        // `券`、`满送`标签  
        hook('afterQueryGoods',['page'=>&$rs['goodsPage'],'isApp'=>true]);
    	foreach ($rs['goodsPage']['data'] as $key =>$v){
    		$rs['goodsPage']['data'][$key]['goodsImg'] = WSTImg($v['goodsImg'],2);
            $rs['goodsPage']['data'][$key]['praiseRate'] = ($v['totalScore']>0)?(sprintf("%.2f",$v['totalScore']/($v['totalUsers']*15))*100).'%':'100%';
    	}
        $rs['domain'] = $this->domain();
    	return json_encode(WSTReturn('数据请求成功',1,$rs));
    }
    /**
    * 商品列表热卖推荐
    */
    public function getCatRecom(){
        $catId = (int)input('catId');
        $rs = model('Tags')->listGoods('recom',$catId,8);
        if(!empty($rs)){
            $_rs = [];
            foreach($rs as $k=>$v){
                $_rs[$k]['goodsId'] = $v['goodsId'];
                $_rs[$k]['goodsName'] = $v['goodsName'];
                $_rs[$k]['shopPrice'] = $v['shopPrice'];
                $_rs[$k]['goodsImg'] = $v['goodsImg'];
            }
            return json_encode(WSTReturn('数据请求成功',1,$_rs));
        }else{
            return json_encode(WSTReturn('暂无热卖推荐',-1));
        }

    }
    /**
    * 获取浏览历史
    */
    public function historyQuery(){
        $data['list'] = model('goods')->historyQuery();
        if(!empty($data['list'])){
	        foreach($data['list'] as $k=>$v){
	            $data['list'][$k]['goodsImg'] = WSTImg($v['goodsImg'],3);
	        }
        }
        // 域名
        $data['domain'] = $this->domain();
        return json_encode(WSTReturn('数据请求成功',1,$data));
    }
}

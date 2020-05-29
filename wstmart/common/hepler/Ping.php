<?php

namespace wstmart\common\helper;

include_once(\Env::get('root_path') . 'extend/pingplusplus/pingpp-php/init.php');

use Pingpp\Order;
use Pingpp\Pingpp;
use Pingpp\Charge;
use Pingpp\SettleAccount;
use Pingpp\User;
use think\Exception;

/**
 * Created by PhpStorm.
 * User: lichaoyang
 * Date: 2020/5/19
 * Time: 下午6:57
 */
class Ping
{
    // 配置数据
    private static $config;

    /**
     * 获取配置信息
     *
     * @param $key
     * @return string
     */
    private static function getConfig($key)
    {
        return isset(self::$config[$key]) ? self::$config[$key] : "";
    }

    /**
     * 初始化配置
     */
    private static function init()
    {
        $config = config("account.ping");
        $appKey = empty($config["appKey"]) ? "" : $config["appKey"];
        $privateKey = empty($config["privateKey"]) ? "" : $config["privateKey"];
        if (empty($appKey) || empty($privateKey)) {
            throw new Exception("ping配置错误");
        }
        Pingpp::setApiKey($appKey);
        Pingpp::setPrivateKey($privateKey);
        self::$config = $config;
    }

    /**
     * 创建支付数据
     *
     * @param $orderNo string 商户订单号
     * @param $channel string 支付使用的第三方支付渠道
     * @param $amount int 订单总金额（必须大于 0）单位分
     * @param $subject string 商品标题，该参数最长为 32
     * @param $body string 商品描述信息，该参数最长为 128
     * @param array $extra 特定渠道发起交易时需要的额外参数
     * @param string $timeExpire 订单失效时间的 Unix 时间戳
     * @param array $metadata 元数据
     * @param string $description 订单附加说明，最多 255
     * @param string $currency 3 位 ISO 货币代码
     * @return Charge
     * @throws Exception
     */
    public static function chargeCreate($orderNo, $channel, $amount, $subject, $body, $extra = array(), $timeExpire = "", $metadata = array(), $description = "", $currency = 'cny')
    {
        self::init();
        if (empty($orderNo) || empty($channel) || empty($amount) || empty($subject) || empty($body)) {
            throw new Exception("参数错误");
        }
        $app = self::getConfig("appId");
        if (empty($app)) {
            throw new Exception("appId 不存在");
        }

        $data = array(
            'order_no' => $orderNo,
            'channel' => $channel,
            'app' => array('id' => $app),
            'amount' => $amount,
            'client_ip' => $_SERVER['REMOTE_ADDR'],
            'currency' => $currency,
            'subject' => $subject,
            'body' => $body,
            'extra' => $extra,
            'time_expire' => $timeExpire,
            'metadata' => $metadata,
            'description' => $description
        );

        return Charge::create(array_filter($data));
    }

    /***************************************************用户相关*******************************************************/

    /**
     * 创建用户
     *
     * @param $id string 用户ID
     * @param string $address 用户地址
     * @param string $avatar 头像图片地址
     * @param string $email 邮箱地址
     * @param string $gender 性别。MALE：男，FEMALE：女
     * @param string $mobile 手机号码
     * @param string $name 用户昵称
     * @param array $metadata 元数据
     * @param string $parentUserId 父级用户 ID
     * @return User
     * @throws Exception
     */
    public static function createUser($id, $address = '', $avatar = '', $email = '', $gender = '', $mobile = '', $name = '', $metadata = array(), $parentUserId = "")
    {
        self::init();
        if (empty($id)) {
            throw new Exception("参数错误");
        }
        $app = self::getConfig("appId");
        if (empty($app)) {
            throw new Exception("appId 不存在");
        }

        Pingpp::setAppId($app);

        $data = array(
            'id' => $id,
            'address' => $address,
            'avatar' => $avatar,
            'email' => $email,
            'gender' => $gender,
            'mobile' => $mobile,
            'name' => $name,
            'metadata' => $metadata,
            'parent_user_id' => $parentUserId
        );

        return User::create(array_filter($data));
    }

    /**
     * 查询用户
     *
     * @param $id
     * @return User
     * @throws Exception
     */
    public static function user($id)
    {
        self::init();
        if (empty($id)) {
            throw new Exception("参数错误");
        }
        $app = self::getConfig("appId");
        if (empty($app)) {
            throw new Exception("appId 不存在");
        }

        Pingpp::setAppId($app);
        return User::retrieve($id);
    }

    /**
     * 编辑用户
     *
     * @param $id
     * @param string $address
     * @param string $avatar
     * @param string $email
     * @param string $gender
     * @param string $mobile
     * @param string $name
     * @param array $metadata
     * @param string $parentUserId
     * @return array|\Pingpp\PingppObject
     * @throws Exception
     */
    public static function updateUser($id, $address = '', $avatar = '', $email = '', $gender = '', $mobile = '', $name = '', $metadata = array(), $parentUserId = "")
    {
        self::init();
        if (empty($id)) {
            throw new Exception("参数错误");
        }

        $app = self::getConfig("appId");
        if (empty($app)) {
            throw new Exception("appId 不存在");
        }
        Pingpp::setAppId($app);

        $data = array(
            'address' => $address,
            'avatar' => $avatar,
            'email' => $email,
            'gender' => $gender,
            'mobile' => $mobile,
            'name' => $name,
            'metadata' => $metadata,
            'parent_user_id' => $parentUserId
        );

        return User::update($id, $data);
    }

    /**
     * 用户禁用启用
     *
     * @param $id
     * @param bool $disabled
     * @return array|\Pingpp\PingppObject
     * @throws Exception
     */
    public static function disable($id, $disabled = true)
    {
        self::init();
        if (empty($id)) {
            throw new Exception("参数错误");
        }

        $app = self::getConfig("appId");
        if (empty($app)) {
            throw new Exception("appId 不存在");
        }
        Pingpp::setAppId($app);

        $data = array(
            'disabled' => $disabled
        );

        return User::update($id, $data);
    }

    /**
     * 用户列表
     *
     * @param int $page 当前页数
     * @param int $perPage 每页数据
     * @param string $disabled 是否禁用 使用该参数时，不能同时使用其他参数
     * @param string $type 用户类型，取值范围：customer 为个人用户，business 为企业用户
     * @param string $identified 是否经过实名认证
     * @param string $created
     * @param string $relation
     * @return array
     * @throws Exception
     */
    public static function lists($page = 1, $perPage = 10, $disabled = '', $type = '', $identified = '', $created = '', $relation = '')
    {
        self::init();
        if (empty($id)) {
            throw new Exception("参数错误");
        }

        $app = self::getConfig("appId");
        if (empty($app)) {
            throw new Exception("appId 不存在");
        }
        Pingpp::setAppId($app);

        if (!empty($disabled)) {
            $data = array(
                'disabled' => $disabled
            );
            return User::all($data);
        }

        $data = array(
            'page' => $page,
            'per_page' => $perPage,
            'type' => $type,
            'identified' => $identified,
        );

        $data = array_filter($data);

        if (!empty($created) && !empty($relation)) {
            $name = 'created[' . $relation . ']';
            $data[$name] = $created;
        }

        return User::all($data);
    }

    /************************************************用户结算账户相关**************************************************/

    /**
     * 给用户添加结算账户
     *
     * @param $userId string 用户id
     * @param $channel string 支付渠道
     * @param $recipient
     * @return SettleAccount
     * @throws Exception
     */
    public static function settleAccountCreate($userId, $channel, $recipient)
    {
        self::init();
        if (empty($userId) || empty($channel) || empty($recipient)) {
            throw new Exception("参数错误");
        }

        $app = self::getConfig("appId");
        if (empty($app)) {
            throw new Exception("appId 不存在");
        }
        Pingpp::setAppId($app);

        $data = array(
            'channel' => $channel,
            'recipient' => array_filter($recipient)
        );

        return SettleAccount::create($userId, $data);

    }

    /*****************************************************订单相关****************************************************/

    /**
     * 创建支付数据
     *
     * @param $orderNo string 商户订单号 8-20位
     * @param $amount int 订单总金额（必须大于 0）单位分
     * @param $subject string 商品标题，该参数最长为 32
     * @param $body string 商品描述信息，该参数最长为 128
     * @param $royaltyUsersId string 分润用户id
     * @param string $currency 3 位 ISO 货币代码
     * @return Order
     * @throws Exception
     */
    public static function orderCreate($orderNo, $amount, $subject, $body, $royaltyUsersId, $currency = 'cny')
    {
        self::init();
        if (empty($orderNo) || empty($amount) || empty($subject) || empty($body)) {
            throw new Exception("参数错误");
        }
        $app = self::getConfig("appId");
        if (empty($app)) {
            throw new Exception("appId 不存在");
        }

        $royaltyUser = array(
            'user' => $royaltyUsersId,
            'amount' => $amount
        );

        $data = array(
            'app' => $app,
            'merchant_order_no' => $orderNo,
            'amount' => $amount,
            'client_ip' => isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : "127.0.0.1",
            'currency' => $currency,
            'subject' => $subject,
            'body' => $body,
            'royalty_users' => [$royaltyUser]
        );

        return Order::create(array_filter($data));
    }

    /**
     * 订单支付
     *
     * @param $orderId
     * @param $chargeAmount
     * @param $channel
     * @return Order
     * @throws Exception
     */
    public static function orderPay($orderId, $chargeAmount, $channel)
    {
        self::init();
        if (empty($orderId) || empty($channel)) {
            throw new Exception("参数错误");
        }
        $app = self::getConfig("appId");
        if (empty($app)) {
            throw new Exception("appId 不存在");
        }

        $data = array(
            'charge_amount' => $chargeAmount,
            'channel' => $channel
        );
        return Order::pay($orderId, $data);
    }

    /*****************************************************订单相关****************************************************/
}
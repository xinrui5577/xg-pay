<?php
/**
 * @author: helei
 * @createTime: 2016-07-20 16:21
 * @description: PxPay回调
 *
 * @link      https://github.com/helei112g/payment/tree/paymentv2
 * @link      https://helei112g.github.io/
 */

namespace Payment\Notify;

use Payment\Pxpay\PxpaySdk;
use Payment\Config;

class PxpayNotify extends NotifyStrategy
{
    private $sdk;
    /**
     * AliNotify constructor.
     * @param array $config
     * @throws PayException
     */
    public function __construct(array $config,$type)
    {
        parent::__construct($config);
        $this->sdk = new PxpaySdk($config, $type);
//        PxpaySdk
    }


    /**
     * 获取移除通知的数据  并进行简单处理（如：格式化为数组）
     *
     * 如果获取数据失败，返回false
     *
     * @return array|boolean
     * @author helei
     */
    public function getNotifyData()
    {
        $data = empty($_POST) ? $_GET : $_POST;
        if (empty($data) || ! is_array($data)) {
            return false;
        }
        return $data;
    }

    /**
     * 检查异步通知的数据是否合法
     *
     * 如果检查失败，返回false
     *
     * @param array $data  由 $this->getNotifyData() 返回的数据
     * @return boolean
     * @author helei
     */
    public function checkNotifyData(array $data)
    {
        // 检查签名
        $flag = $this->sdk->checkNotify($data);
        return $flag;
    }

    /**
     * 向客户端返回必要的数据
     * @param array $data 回调机构返回的回调通知数据
     * @return array|false
     * @author helei
     */
    protected function getRetData(array $data)
    {
        $retData = [
            'amount'   => $data['money'],
            'buyer_id'   => 0,
            'transaction_id'   => $data['order_id'],
            'order_no'   => $data['out_order_id'],
            'trade_state'   => 'true',
            'pay_time'   => date('Y-m-d H:i:s'),// 交易付款时间
            'pay_amount' => $data['money'],// 用户在交易中支付的金额
            'channel'   => Config::ALI_CHARGE,
        ];
        return $retData;
    }

    /**
     * 支付宝，成功返回 ‘success’   失败，返回 ‘fail’
     * @param boolean $flag 每次返回的bool值
     * @param string $msg 错误原因  后期考虑记录日志
     * @return string
     * @author helei
     */
    protected function replyNotify($flag, $msg = '')
    {
        if ($flag) {
            return 'SUCCESS';
        } else {
            return 'FAIL';
        }
    }
}

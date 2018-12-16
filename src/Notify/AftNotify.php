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

use Payment\Config;

class AftNotify extends NotifyStrategy {

    private $sdk;

    /**
     * AliNotify constructor.
     * @param array $config
     * @throws PayException
     */
    public function __construct(array $config) {
        parent::__construct($config);
        $this->sdk = new \Payment\Sdk\AftClient($config);
    }

    /**
     * 获取移除通知的数据  并进行简单处理（如：格式化为数组）
     *
     * 如果获取数据失败，返回false
     *
     * @return array|boolean
     * @author helei
     */
    public function getNotifyData() {
        $data = empty($_POST) ? $_GET : $_POST;
        if (empty($data) || !is_array($data)) {
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
    public function checkNotifyData(array $data) {
        return $this->sdk->requestSignVerify($data);
    }

    /**
     * 向客户端返回必要的数据
     * @param array $data 回调机构返回的回调通知数据
     * @return array|false
     * @author helei
     */
    protected function getRetData(array $data) {

        $retData = [
            'amount' => $data['total_amount'],
            'buyer_id' => 0,
            'transaction_id' => $data['trade_no'],
            'order_no' => $data['out_trade_no'],
            'trade_state' => 'true',
            'pay_time' =>$data['pay_time'],
            'pay_amount' => $data['total_amount'], // 用户在交易中支付的金额
            'channel' => Config::ALI_CHARGE,
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
    protected function replyNotify($flag, $msg = '') {
        if ($flag) {
            return 'SUCCESS';
        } else {
            return 'FAIL';
        }
    }

}

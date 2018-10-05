<?php
/**
 * Created by PhpStorm.
 * User: helei
 * Date: 17/3/6
 * Time: 上午8:49
 */

namespace Payment\Common\Ydt\Data\Charge;

use Payment\Common\PayException;
use Payment\Utils\ArrayUtil;

/**
 * Class WebChargeData
 *
 * @inheritdoc
 * @property string $auth_code  扫码支付授权码，设备读取用户微信中的条码或者二维码信息
 * @property string $sub_appid 微信分配的子商户公众账号ID
 * @property string $sub_mch_id 	微信支付分配的子商户号
 *
 * @package Payment\Common\Weixin\Data\Charge
 */
class BarChargeData extends ChargeBaseData
{

    /**
     * 生成下单的数据
     */
    protected function buildData()
    {
        $signData = [
            // 基本数据
            'appid' => trim($this->appId),
            'mch_id'    => trim($this->mchId),
            'nonce_str' => $this->nonceStr,
            'sign_type' => $this->signType,
            'fee_type'  => $this->feeType,

            // 业务数据
            'device_info'   => $this->terminal_id,
            'body'  => trim($this->subject),
            //'detail' => json_encode($this->body, JSON_UNESCAPED_UNICODE);
            'attach'    => trim($this->return_param),
            'out_trade_no'  => trim($this->order_no),
            'total_fee' => $this->amount,
            'spbill_create_ip'  => trim($this->client_ip),
            'auth_code'    => $this->auth_code,

            // 服务商
            'sub_appid' => $this->sub_appid,
            'sub_mch_id' => $this->sub_mch_id,
        ];

        // 移除数组中的空值
        $this->retData = ArrayUtil::paraFilter($signData);
    }
}

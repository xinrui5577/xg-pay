<?php
/**
 * @author: helei
 * @createTime: 2016-07-28 18:05
 * @description: 微信支付相关接口的数据基类
 */

namespace Payment\Common\Pxpay\Data;

use Payment\Common\BaseData;

/**
 * Class PxpayData
 *
 * @package Payment\Common\Weixin\Dataa
 */
class PxpayData extends BaseData
{
    public function setSign() {
        $this->buildData();
    }

    /**
     * 签名算法实现  便于后期扩展微信不同的加密方式
     * @param string $signStr
     * @return string
     */
    protected function makeSign($signStr)
    {
        $signStr .= '&key=' . $this->md5Key;
        $sign = md5($signStr);
        return  strtoupper($sign);
    }

    protected function buildData(): array {
        
    }

    protected function checkDataParam() {
        
    }

}

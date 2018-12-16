<?php

/**
 * @author: helei
 * @createTime: 2016-07-14 18:19
 * @description: PXPAY个人收款 接口
 * @link      https://github.com/helei112g/payment/tree/paymentv2
 * @link      https://helei112g.github.io/
 */

namespace Payment\Charge\Pxpay;

use Payment\Common\BaseStrategy;
use Payment\Pxpay\PxpaySdk;

class PxpayCharge implements BaseStrategy {

    private $sdk;

    /**
     * AliBaseStrategy constructor.
     * @param array $config
     * @throws PayException
     */
    public function __construct(array $config, $type) {
        $this->sdk = new PxpaySdk($config, $type);
    }

    public function handle(array $params) {
        $rs = $this->sdk->pay($params);
        if($rs){
            return $rs;
        }
    }

    public function getBuildDataClass() {
        return '';
    }
}

<?php


namespace Payment\Charge\Aft;

use Payment\Common\BaseStrategy;
use Payment\Sdk\AftClient;

class AftChargeWx implements BaseStrategy {

    private $sdk;

    /**
     * AliBaseStrategy constructor.
     * @param array $config
     * @throws PayException
     */
    public function __construct(array $config) {
        $this->sdk = new AftClient($config);
    }

    public function handle(array $params) {
        $rs = $this->sdk->pay_wx_h5($params);
        if($rs){
            return $rs;
        }
    }

    public function getBuildDataClass() {
        return '';
    }
}

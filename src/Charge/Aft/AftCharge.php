<?php


namespace Payment\Charge\Aft;

use Payment\Common\BaseStrategy;
use Payment\Sdk\AftClient;

class AftCharge implements BaseStrategy {

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
        $rs = $this->sdk->pay_ali_wap($params);
        if($rs){
            return $rs;
        }
    }

    public function getBuildDataClass() {
        return '';
    }
}

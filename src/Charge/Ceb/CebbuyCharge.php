<?php

namespace Payment\Charge\Ceb;

use Payment\Common\BaseStrategy;
use Payment\Sdk\CebbuySdk;

class CebbuyCharge implements BaseStrategy {

    private $sdk;

    /**
     * AliBaseStrategy constructor.
     * @param array $config
     * @throws PayException
     */
    public function __construct(array $config) {
        $this->sdk = new CebbuySdk($config);
    }

    public function handle(array $params) {
        $rs = $this->sdk->pay($params);
        if($rs){
            return $rs;
        }
        return false;
    }

    public function getBuildDataClass() {
        return '';
    }
}

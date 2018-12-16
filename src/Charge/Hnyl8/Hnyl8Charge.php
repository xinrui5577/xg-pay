<?php


namespace Payment\Charge\Hnyl8;

use Payment\Common\BaseStrategy;
use Payment\Sdk\Hnyl8Sdk;

class Hnyl8Charge implements BaseStrategy {

    private $sdk;

    /**
     * AliBaseStrategy constructor.
     * @param array $config
     * @throws PayException
     */
    public function __construct(array $config) {
        $this->sdk = new Hnyl8Sdk($config);
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

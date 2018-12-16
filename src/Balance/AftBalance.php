<?php

// +----------------------------------------------------------------------
// | UKAFU [ Aggregate payment ] Dalian Zhi Yi Technology Co., Ltd.
// | 支付/发卡/房卡/代理 系统
// +----------------------------------------------------------------------
// | Copyright (c) 2017~2018 http://www.ukafu.com All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: xinyu126 QQ 765858558
// +----------------------------------------------------------------------

namespace Payment\Balance;

use Payment\Balance\BaseBalance;

class AftBalance extends BaseBalance{
    
    public function query($cfg) {
       $client = new \Payment\Sdk\AftClient($cfg);
       $client->getBanlance();
    }

}
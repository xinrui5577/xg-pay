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

//查询余额
namespace Payment\Client;

use Payment\Config;
use Payment\Balance\AftBalance;

class Balance
{
    public function getInstance($paymode){
        $instance = false;
        switch ($paymode){
            case Config::ZY_Youxin:
            case Config::ZY_YouxinWx:
                $instance = new AftBalance();
                break;
        }
        return $instance;
    }
    
    public function query($paymode,$cfg){
        $inst = $this->getInstance($paymode);
        if($inst){
            $inst->query($cfg);
        }
    }
}
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

abstract class BaseBalance {

    abstract function query($cfg);
}
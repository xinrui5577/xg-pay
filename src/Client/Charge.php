<?php
/**
 * Created by PhpStorm.
 * User: helei
 * Date: 2017/3/4
 * Time: 下午5:40
 */

namespace Payment\Client;


use Payment\ChargeContext;
use Payment\Common\PayException;

class Charge
{
    /**
     * 异步通知类
     * @var ChargeContext
     */
    //protected static $instance;

    protected static function getInstance($channel, $config)
    {
        $instance = new ChargeContext();
        try {
            $instance->initCharge($channel, $config);
        } catch (PayException $e) {
            throw $e;
        }
        return $instance;
    }

    /**
     * @param string $channel
     * @param array $config
     * @param array $metadata
     *
     * @return mixed
     * @throws PayException
     */
    public static function run($channel, $config, $metadata)
    {
        try {
            $instance = self::getInstance($channel, $config);

            $ret = $instance->charge($metadata);
        } catch (PayException $e) {
            throw $e;
        }

        return $ret;
    }
}
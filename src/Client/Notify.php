<?php
/**
 * Created by PhpStorm.
 * User: helei
 * Date: 2017/3/7
 * Time: 下午6:29
 */

namespace Payment\Client;


use Payment\Common\PayException;
use Payment\NotifyContext;

class Notify
{

    /**
     * 异步通知类
     * @var NotifyContext
     */
    protected static $instance;

    protected static function getInstance($type, $config)
    {
        if (is_null(self::$instance)) {
            static::$instance = new NotifyContext();
            try {
                static::$instance->initNotify($type, $config);
            } catch (PayException $e) {
                throw $e;
            }
        }

        return static::$instance;
    }

    /**
     * 执行异步工作
     * @param string $type
     * @param array $config
     * @return array
     * @throws PayException
     */
    public static function run($type, $config)
    {
        try {
            $instance = self::getInstance($type, $config);
            $ret = $instance->notify();
        } catch (PayException $e) {
            throw $e;
        }

        return $ret;
    }
    

    /**
     * 返回异步通知的结果
     * @param $type
     * @param $config
     * @return array|false
     * @throws PayException
     */
    public static function getNotifyData($type, $config)
    {
        try {
            $instance = self::getInstance($type, $config);

            return $instance->getNotifyData();
        } catch (PayException $e) {
            throw $e;
        }
    }
}
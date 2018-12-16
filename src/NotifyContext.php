<?php
/**
 * @author: helei
 * @createTime: 2016-07-14 17:42
 * @description: 暴露给客户端调用的接口
 * @link      https://github.com/helei112g/payment/tree/paymentv2
 * @link      https://helei112g.github.io/
 */

namespace Payment;

use Payment\Notify\AliNotify;
use Payment\Notify\PxpayNotify;
use Payment\Notify\NotifyStrategy;
use Payment\Notify\WxNotify;
use Payment\Common\PayException;

class NotifyContext
{
    /**
     * 支付的渠道
     * @var NotifyStrategy
     */
    protected $notify;


    /**
     * 设置对应的通知渠道
     * @param string $channel 通知渠道
     *  - @see Config
     *
     * @param array $config 配置文件
     * @throws PayException
     * @author helei
     */
    public function initNotify($channel, array $config)
    {
        try {
            switch ($channel) {
                case Config::ZY_TongLian:
                    $this->notify = new Notify\CebbuyNotify($config);
                    break;
                case Config::ZY_Youxin:
                case Config::ZY_YouxinWx:
                    $this->notify = new Notify\AftNotify($config);
                    break;
                case Config::ZY_Kadiya:
                    $this->notify = new Notify\KadiyaNotify($config);
                    break;
                case Config::ZY_HNY:
                    $this->notify = new Notify\HnyNotify($config);
                    break;
                case Config::ZY_PXPAY_ALI:
                    $this->notify = new PxpayNotify($config,'ALIPAY');
                    break;
                case Config::ZY_PXPAY_WX:
                    $this->notify = new PxpayNotify($config,'WXPAY');
                    break;
                case Config::ALI_CHARGE:
                case Config::ALI_CHANNEL_WAP:
                    $this->notify = new AliNotify($config);
                    break;
                case Config::WX_CHARGE:
                case Config::WX_CHANNEL_WAP:
                    $this->notify = new WxNotify($config);
                    break;
                case Config::CMB_CHARGE:
                    $this->notify = new CmbNotify($config);
                    break;
                default:
                    throw new PayException('当前仅支持：ALI_CHARGE WX_CHARGE CMB_CHARGE 常量');
            }
        } catch (PayException $e) {
            throw $e;
        }
    }

    /**
     * 返回异步通知的数据
     * @return array|false
     */
    public function getNotifyData()
    {
        return $this->notify->getNotifyData();
    }

    /**
     * 通过环境类调用支付异步通知
     *
     * @return array
     * @throws PayException
     * @author helei
     */
    public function notify()
    {
        return $this->notify->handle();
    }
}

<?php

/**
 * @author: helei
 * @createTime: 2016-07-14 18:19
 * @description: PXPAY个人收款 接口
 * @link      https://github.com/helei112g/payment/tree/paymentv2
 * @link      https://helei112g.github.io/
 */

namespace Payment\Charge\Pxpay;

use Payment\Common\BaseStrategy;
use Payment\Common\Pxpay\Data\PxpayData;

class PxpayCharge implements BaseStrategy {

    private $merchantId = 17789023; //改成自己的ID
    private $merchantSecret = '6ba79edc29a36936ded33ddd39b83b90'; //改成自己的密钥
    private $gateway = 'https://pxpay.ukafu.com/';
    private $notifyUrl = '';
    private $returnUrl = '';
    private $errmsg;
    private $payType;

    /**
     * AliBaseStrategy constructor.
     * @param array $config
     * @throws PayException
     */
    public function __construct(array $config, $type) {
        if (empty($config['merchantid'])) {
            die('config 缺少参数 merchantid');
        }
        if (empty($config['secret'])) {
            die('config 缺少参数 secret');
        }
        $this->merchantId = $config['merchantid'];
        $this->merchantSecret = $config['secret'];
        $this->payType = $type;
        if (isset($config['notify_url'])) {
            $this->notifyUrl = $config['notify_url'];
        }
        if (isset($config['return_url'])) {
            $this->returnUrl = $config['return_url'];
        }
    }

    public function handle(array $params) {
        if (empty($params['amount'])) {
            die('缺少参数 订单金额:amount');
        }
        if (empty($params['order_no'])) {
            die('缺少参数 订单id:order_no');
        }
        $orderid =  $params['order_no'];
        $args = [
            'merchantid' => $this->merchantId,
            'paytype' => $this->payType,
            'amount' => $params['amount'],
            'orderid' => $params['order_no'],
            'client_ip' => $_SERVER['REMOTE_ADDR']
        ];
        if (isset($params['notify_url'])) {
            $args['notify_url'] = $params['notify_url'];
        } else {
            $args['notify_url'] = $this->notifyUrl.'/order/'.$orderid;
        }
        if (isset($params['return_url'])) {
            $args['return_url'] = $params['return_url'];
        } else {
            $args['return_url'] = $this->returnUrl.'/order/'.$orderid;
        }
        $sign = $this->sign($args);
        $args['sign'] = $sign;
        $url = $this->gateway . 'pxpayapi?' . http_build_query($args);
        $rs = $this->getCurl($url);
        if ($rs) {
            $json = json_decode($rs, true);
            if ($json && $json['code'] == 0) {
                //支付页面，获取订单信息
                $order = $json['data'];
                $orderid = $order['orderid'];
                $data = [
                    'code_url' => $order['qrcode'], //支付二维码
                    'pay_type' => $order['paytype'], //支付方式
                    'money' => $order['realprice'], //实际的支付金额
                    'orderid' => $orderid, //系统订单ID
                    'use_time' => time()//订单开始时间,倒计时5分钟
                ];
                return $data;
            } else {
                $this->errmsg = $json ? $json['msg'] : '未知错误:' . $rs;
            }
        }
        return false;
    }

    public function getBuildDataClass() {
        return PxpayData::class;
    }

    /**
     * 校验通知是否正确
     * * */
    public function checkNotify($params) {
        if (empty($params['sign'])) {
            return false;
        }
        $sign = $params['sign'];
        unset($params['sign']);
        $csign = $this->sign($params);
        if ($sign != $csign) {
            return false;
        }
        return true;
    }

    public function getErrorMsg() {
        return $this->errmsg;
    }

    /**
     * 检测订单,查看订单号是否完成
     */
    public function queryOrder($orderid) {
        $params = [
            'merchantid' => $this->merchantId,
            'orderid' => $orderid,
            'rndstr' => $this->randomStr(16),
        ];
        $sign = $this->sign($params);
        $params['sign'] = $sign;
        $url = $this->gateway . 'pxpayquery?' . http_build_query($params);
        $rs = $this->getCurl($url);
        if ($rs) {
            $json = json_decode($rs, true);
            if ($json && $json['code'] == 0) {
                $data = $json['data'];
                return $data['status'] == 1;
            }
        }
        return false;
    }

    function randomStr($length = 32) {
        $chars = "abcdefghijklmnopqrstuvwxyz0123456789";
        $str = "";
        for ($i = 0; $i < $length; $i++) {
            $str .= substr($chars, mt_rand(0, strlen($chars) - 1), 1);
        }
        return $str;
    }

    function json($code, $data) {
        $rs = [
            'code' => $code,
            'time' => time(),
            'data' => $data
        ];
        echo json_encode($rs);
        die();
    }

    /**
     * 请求网络数据
     */
    function getCurl($url, $timeout = 10) {
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_TIMEOUT, $timeout);
        curl_setopt($curl, CURLOPT_USERAGENT, $_SERVER['HTTP_USER_AGENT']);
        $resp = curl_exec($curl);
        curl_close($curl);
        return $resp;
    }

    /**
     * 获取本机的url，注意如果是内网，是无法被通知的。部署到外网服务器能获取支付完成通知
     */
    function getMyUrl() {
        $http_type = ((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on') || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] == 'https')) ? 'https://' : 'http://';
        return $http_type . $_SERVER['HTTP_HOST'];
    }

    /**
     * 签名，所有参数按字升序排列，去除空数据，然后拼接密钥。md5
     */
    function sign($data) {
        $data = $this->removeEmpty($data);
        ksort($data);
        $str = '';
        foreach ($data as $k => $v) {
            if ($str) {
                $str = $str . $k . $v;
            } else {
                $str = $k . $v;
            }
        }
        return md5($str . $this->merchantSecret);
    }

    /**
     * 移除空值的key
     * @param $para
     * @return array
     * @author helei
     */
    function removeEmpty($para) {
        $paraFilter = [];
        while (list($key, $val) = each($para)) {
            if ($val === '' || $val === null) {
                continue;
            } else {
                if (!is_array($para[$key])) {
                    $para[$key] = is_bool($para[$key]) ? $para[$key] : trim($para[$key]);
                }

                $paraFilter[$key] = $para[$key];
            }
        }

        return $paraFilter;
    }

}

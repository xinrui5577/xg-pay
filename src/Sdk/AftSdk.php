<?php

namespace Payment\Sdk;
/**
 * 个人支付的SDK
 *
 * @author Administrator
 */
class AftSdk {
    

    private $appid = '';
    private $merchantId = 1; //改成自己的ID
    private $merchantSecret = ''; //改成自己的密钥
    private $gateway = 'https://gateways.hnyl8.top/cnpPay/initPay';
    private $notifyUrl = '';
    private $returnUrl = '';
    private $errmsg;

    /**
     * AliBaseStrategy constructor.
     * @param array $config
     * @throws PayException
     */
    public function __construct(array $config) {
        if (empty($config['appid'])) {
            die('config 缺少参数 appid');
        }
        if (empty($config['merchantid'])) {
            die('config 缺少参数 merchantid');
        }
        if (empty($config['secret'])) {
            die('config 缺少参数 secret');
        }
        //2018102062111386,Gk4VwRjlH7OcMbJnrpZCtAXiBoyKN5WQ
        $this->appid = $config['appid'];
        $this->merchantId = $config['merchantid'];
        $this->merchantSecret = $config['secret'];
        if (isset($config['notify_url'])) {
            $this->notifyUrl = $config['notify_url'];
        }
        if (isset($config['return_url'])) {
            $this->returnUrl = $config['return_url'];
        }
    }

    public function pay(array $params) {
        if (empty($params['amount'])) {
            die('缺少参数 订单金额:amount');
        }
        if (empty($params['order_no'])) {
            die('缺少参数 订单id:order_no');
        }
//app_id string 是 32 移动支付平台分配给接入平台的服务商唯一的	ID,请 向	相应的对接负责人获取 
//method string 是 128 接口名称 
//sign string 是 256 请求参数的签名串 
//sign_type string 是 32 签名类型	"MD5"	或者	"RSA" 
//version string 是 3 调用的接口版本，默认且固定为：1.0
//content string 是 请求参数的集合，最大长度不限，除公共参数外所 有请求参数（业务参数）都必须放在这个参数中传 递

//merchant_no String 是 20 移动支付平台为商户分配的惟	一	ID,商户入驻后, 由平台返回 
//out_trade_no String 是 64 商户订单号,64	个字符以内、可	包	含字母、数 字、下划线;需保证	在	接入的商户系统中不重复 
//order_name String 是 64 商品描述	，传入公众号名称-实际商品名称，例 如：腾讯形象店-	image-QQ公仔 
//body String 是 - 订单描述 
//total_amount float 是 11 总金额	单位为元，精确到小数点后两位，取值 范围[0.01,100000000] 
//notify_url String 是 - 回调地址
//return_url String 特 殊 可 选  支付后跳转
//success_url String 可 选 - 同步跳转地址
        $data = [
            'app_id'=> $this->appid,
            'method'=> 'alipay.wap_pay',
            'version'=> '1.0',
        ];
        
        $orderid =  $params['order_no'];
        $args = [
            'merchant_no' => $this->merchantId,
            'out_trade_no' => $params['order_no'],
            'order_name' => '充值-'.time(),
            'body'=>'充值',
            'total_amount' => $params['amount'],
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
        $data['content'] = json_encode($args);
        $sign = $this->sign($data);
        
        $data['sign'] = $sign;
        $url = $this->gateway . '?' . http_build_query($args);
        $rs = $this->getCurl($url);
        if ($rs) {
            $json = json_decode($rs, true);
            if ($json && $json['pay_url']) {
                //支付页面，获取订单信息
                $data = [
                    'code_url' => $json['pay_url'], //支付二维码
                ];
                return $data;
            } else {
                die($rs);
            }
        }
        return false;
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
                $str = $str .'&'. $k .'='. $v;
            } else {
                $str = $k .'='. $v;
            }
        }
        return strtoupper(md5($str .'&key='. $this->merchantSecret));
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

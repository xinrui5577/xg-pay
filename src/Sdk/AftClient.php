<?php

namespace Payment\Sdk;

use Payment\Utils\StrUtil;
use Payment\Utils\NetUtil;

class AftClient {

    //应用ID
    public $appId;
    //秘钥
    public $secret;
    //网关
    public $gatewayUrl = "https://api.727pay.com/gateway";
    //返回数据格式
    public $format = "json";
    //api版本
    public $apiVersion = "1.0";
    //接口名称
    public $method = '';
    // 表单提交字符集编码
    public $postCharset = "UTF-8";
    private $fileCharset = "UTF-8";
    private $merchantId;
    private $config;
    private $notifyUrl;
    private $returnUrl;

    public function __construct($config) {
        $this->config = $config;
        date_default_timezone_set("Asia/Shanghai");
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
        $this->appId = $config['appid'];
        $this->merchantId = $config['merchantid'];
        $this->secret = $config['secret'];
        if (isset($config['notify_url'])) {
            $this->notifyUrl = $config['notify_url'];
        }
        if (isset($config['return_url'])) {
            $this->returnUrl = $config['return_url'];
        }
    }

    public function pay_ali_wap(array $params) {
        if (empty($params['amount'])) {
            die('缺少参数 订单金额:amount');
        }
        if (empty($params['order_no'])) {
            die('缺少参数 订单id:order_no');
        }
        $orderid = $params['order_no'];
        $args = [
            'merchant_no' => $this->merchantId,
            'out_trade_no' => $params['order_no'],
            'order_name' => '充值-' . time(),
            'body' => '充值',
            'total_amount' => $params['amount'],
        ];
        if (isset($params['notify_url'])) {
            $args['notify_url'] = $params['notify_url'];
        } else {
            $args['notify_url'] = $this->notifyUrl . '/order/' . $orderid;
        }
        if (isset($params['return_url'])) {
            $args['return_url'] = $params['return_url'];
        } else {
            $args['return_url'] = $this->returnUrl . '/order/' . $orderid;
        }

        $rs = $this->call('alipay.wap_pay', $args);
        if ($rs && $rs['error_code'] == 0) {
            $url = $rs['pay_url'];
            $i=0;
            do{
                $i++;
                if(!StrUtil::startsWith(strtolower($url),'https://qr.alipay.com')){
                    $data = NetUtil::getCurl($url);
                    if($data){
                        if($data['code']==302){
                            $url = $data['data'];
                        }else{
                            $content = $data['data'];
                            $preg = '/window.location.href."(http\S+)"/';
                            if(preg_match($preg, $content,$match)){
                                 $url =  $match[1];  
                            }
                            //https://qr.alipay.com/bax09056k2m7wxvlipdw20e0
                        }
                    }
                }else{
                    break;
                }
            }while($i<5);
            return ['code_url' =>$url];
        }
        var_dump($rs);
        return false;
    }

    public function pay_wx_h5(array $params) {
        if (empty($params['amount'])) {
            die('缺少参数 订单金额:amount');
        }
        if (empty($params['order_no'])) {
            die('缺少参数 订单id:order_no');
        }
        $orderid = $params['order_no'];
        if(empty($this->config['wap_url'])){
            die('缺少参数 网址 :wap_url');
        }
        if(empty($this->config['wap_name'])){
            die('缺少参数 网址名:wap_name');
        }
        $args = [
            'merchant_no' => $this->merchantId,
            'out_trade_no' => $params['order_no'],
            'order_name' => '充值-' . time(),
            'body' => '充值',
            'total_amount' => $params['amount'],
            
            'goods_tag' => '123123123',     //商品标记
            'spbill_create_ip' => '127.0.0.1',
            'sub_mch_id' => $this->merchantId,
            'sceneInfo' => json_encode([
            'h5_info' => [
                        'type' => 'Wap',
                        'wap_url' => $this->config['wap_url'],
                        'wap_name' => $this->config['wap_name'],
                    ]
            ]),
        ];
        if (isset($params['notify_url'])) {
            $args['notify_url'] = $params['notify_url'];
        } else {
            $args['notify_url'] = $this->notifyUrl . '/order/' . $orderid;
        }
        if (isset($params['return_url'])) {
            $args['return_url'] = $params['return_url'];
        } else {
            $args['return_url'] = $this->returnUrl . '/order/' . $orderid;
        }

        $rs = $this->call('weixin.h5_pay', $args);
        if ($rs && $rs['error_code'] == 0) {
            return ['code_url' => $rs['pay_url']];
        }
        var_dump($rs);
        return false;
    }

    public function md5Sign($params) {
        return md5(static::getSignContent($params) . '&key=' . $this->secret);
    }

    public function rsaSign($params, $rsaPrivateKey) {
        $data = static::getSignContent($params) . '&key=' . $this->secret;
        $res = openssl_get_privatekey($rsaPrivateKey);
        openssl_sign($data, $sign, $res);
        openssl_free_key($res);
        $sign = base64_encode($sign);
        return $sign;
    }

    private function rsaVerify($data, $sign, $rsaPublicKey, $signType) {
        $res = openssl_get_publickey($rsaPublicKey);
        ($res) or die('公钥错误。请检查公钥文件格式是否正确');

        //调用openssl内置方法验签，返回bool值

        if ("RSA2" == $signType) {
            $result = (bool) openssl_verify($data, base64_decode($sign), $res, OPENSSL_ALGO_SHA256);
        } else {
            $result = (bool) openssl_verify($data, base64_decode($sign), $res);
        }

        //释放资源
        openssl_free_key($res);

        return $result;
    }

    protected function getSignContent($params) {
        ksort($params);

        $stringToBeSigned = "";
        $i = 0;
        foreach ($params as $k => $v) {
            if (false === $this->checkEmpty($v) && "@" != substr($v, 0, 1)) {

                // 转换成目标字符集
                $v = $this->characet($v, $this->postCharset);

                if ($i == 0) {
                    $stringToBeSigned .= "$k" . "=" . "$v";
                } else {
                    $stringToBeSigned .= "&" . "$k" . "=" . "$v";
                }
                $i++;
            }
        }

        unset($k, $v);
        return $stringToBeSigned;
    }

    public function requestSignVerify($params) {
        $sign = $params['sign'];
        unset($params['sign']);
        unset($params['sign_type']);
        return $sign == $this->md5Sign($params);
    }

    protected function curl($url, $postFields = null) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_FAILONERROR, false);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

        $postBodyString = "";
        $encodeArray = Array();

        $postMultipart = false;

        if (is_array($postFields) && 0 < count($postFields)) {


            foreach ($postFields as $k => $v) {
                if ("@" != substr($v, 0, 1)) { //判断是不是文件上传
                    $postBodyString .= "$k=" . urlencode($this->characet($v, $this->postCharset)) . "&";
                    $encodeArray[$k] = $this->characet($v, $this->postCharset);
                } else { //文件上传用multipart/form-data，否则用www-form-urlencoded
                    $postMultipart = true;
                    $encodeArray[$k] = new \CURLFile(substr($v, 1));
                }
            }

            unset($k, $v);
            curl_setopt($ch, CURLOPT_POST, true);
            if ($postMultipart) {
                curl_setopt($ch, CURLOPT_SAFE_UPLOAD, false);
                curl_setopt($ch, CURLOPT_POSTFIELDS, $encodeArray);
            } else {
                curl_setopt($ch, CURLOPT_POSTFIELDS, substr($postBodyString, 0, -1));
            }
        }

        if ($postMultipart) {
            $headers = array('content-type: multipart/form-data;charset=' . $this->postCharset . ';boundary=' . $this->getMillisecond());
        } else {

            $headers = array('content-type: application/x-www-form-urlencoded;charset=' . $this->postCharset);
        }
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

        $reponse = curl_exec($ch);

//        if (curl_errno($ch)) {
//            throw new \Exception(curl_error($ch), 0);
//        } else {
//            $httpStatusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
//            if (200 !== $httpStatusCode) {
//                throw new \Exception($reponse, $httpStatusCode);
//            }
//        }
        curl_close($ch);
        return $reponse;
    }
    
    public function getBanlance(){
//        $args = [
//            'merchant_no' => $this->merchantId,
//        ];
//        $rs = $this->call('merchant.balance', $args);
//        var_dump($rs);
        $args = [
            'merchant_no' => 201810262251047282,
            'out_trade_no'=>time(),
            'amount'=>758,
            'bank_account_no'=>'6228481098927376072',
            'bank_account_name'=>'陈圣新',
            'bank_mobile'=>'17773119731',
            'id_card_no'=>'430181198601261055',
            'account_attr'=>0,
            'is_company'=>0,
            'province'=>'湖南',
            'city'=>'长沙',
            'bank_name'=>'中国农业银行',
            'remark'=>'tx',
            'bank_code'=>'HDL0026967'
        ];
        echo 'gg';
        $rs = $this->call('merchant.withdraw', $args);
        var_dump($rs);
    }
    
    public function transform(){
        $args = [
            'merchant_no' => $this->merchantId,
            'out_trade_no' => $params['order_no'],
            'order_name' => '充值-' . time(),
            'body' => '充值',
            'total_amount' => $params['amount'],
        ];
         $this->call('merchant.withdraw', $args);
    }

    protected function getMillisecond() {
        list($s1, $s2) = explode(' ', microtime());
        return (float) sprintf('%.0f', (floatval($s1) + floatval($s2)) * 1000);
    }

    public function call($method, $content) {
        $this->method = $method;
        $response = $this->execute(['content' => json_encode($content)]);
        $response = json_decode($response, true);
        return $response;
    }

    public function uploadAuthImg($externalId, $fileType, $img) {
        if ($this->checkEmpty($this->postCharset)) {
            $this->postCharset = "UTF-8";
        }

        $this->fileCharset = mb_detect_encoding($this->appId, "UTF-8,GBK");


        //		//  如果两者编码不一致，会出现签名验签或者乱码
        if (strcasecmp($this->fileCharset, $this->postCharset)) {

            // writeLog("本地文件字符集编码与表单提交编码不一致，请务必设置成一样，属性名分别为postCharset!");
            throw new \Exception("文件编码：[" . $this->fileCharset . "] 与表单提交编码：[" . $this->postCharset . "]两者不一致!");
        }

        $iv = $this->apiVersion;


        //组装系统参数
        $sysParams["app_id"] = $this->appId;
        $sysParams["version"] = $iv;

        //获取业务参数
        $apiParams = [
            'external_id' => $externalId,
            'file_type' => $fileType,
        ];

        //签名
        $sysParams = array_merge($apiParams, $sysParams);
        $sysParams["sign"] = $this->generateSign($sysParams);

        $sysParams['file'] = '@' . $img;

        //系统参数放入GET请求串
        $requestUrl = $this->uploadAuthImgUrl;

        //发起HTTP请求
        $resp = $this->curl($requestUrl, $sysParams);

        // 将返回结果转换本地文件编码
        $respObject = iconv($this->postCharset, $this->fileCharset . "//IGNORE", $resp);

        $response = json_decode($respObject, true);
        if ($response['success'] === true) {
            return array_get($response, 'return_value', true);
        }
        throw new \ErrorException(array_get($response, 'error_message'), array_get($response, 'error_code', 0));
    }

    public function execute($data) {

        if ($this->checkEmpty($this->postCharset)) {
            $this->postCharset = "UTF-8";
        }

        $this->fileCharset = mb_detect_encoding($this->appId, "UTF-8,GBK");

        //		//  如果两者编码不一致，会出现签名验签或者乱码
        if (strcasecmp($this->fileCharset, $this->postCharset)) {

            // writeLog("本地文件字符集编码与表单提交编码不一致，请务必设置成一样，属性名分别为postCharset!");
            throw new Exception("文件编码：[" . $this->fileCharset . "] 与表单提交编码：[" . $this->postCharset . "]两者不一致!");
        }

        $iv = $this->apiVersion;


        //组装系统参数
        $sysParams["app_id"] = $this->appId;
        $sysParams["version"] = $iv;
        $sysParams['method'] = $this->method;

        //获取业务参数
        $apiParams = $data;

        //签名
        $sysParams = array_merge($apiParams, $sysParams);
        $sysParams["sign"] = $this->md5Sign($sysParams);
        $sysParams["sign_type"] = "MD5";

//        $sysParams['sign'] = $this->rsaSign($sysParams, file_get_contents("../key/rsa_private_key.pem"));
//        $sysParams["sign_type"] = "RSA";
        //系统参数放入GET请求串
        $requestUrl = $this->gatewayUrl;

        //发起HTTP请求
        try {
            $resp = $this->curl($requestUrl, $sysParams);
        } catch (Exception $e) {
            die($e->getMessage());
            return false;
        }


        // 将返回结果转换本地文件编码
        $respObject = iconv($this->postCharset, $this->fileCharset . "//IGNORE", $resp);

        return $respObject;
    }

    /**
     * 转换字符集编码
     * @param $data
     * @param $targetCharset
     * @return string
     */
    function characet($data, $targetCharset) {


        if (!empty($data)) {
            $fileType = $this->fileCharset;
            if (strcasecmp($fileType, $targetCharset) != 0) {

                $data = mb_convert_encoding($data, $targetCharset);
            }
        }

        return $data;
    }

    /**
     * 校验$value是否非空
     *  if not set ,return true;
     *    if is null , return true;
     * */
    protected function checkEmpty($value) {
        if (!isset($value)) {
            return true;
        }
        if ($value === null) {
            return true;
        }
        if (trim($value) === "") {
            return true;
        }
        return false;
    }

}

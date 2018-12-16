<?php


namespace Payment\Charge\Kadiya;

use Payment\Common\BaseStrategy;

class KadiyaCharge implements BaseStrategy {

    private $cfg;
    private $notifyUrl;
    private $returnUrl;

    /**
     * AliBaseStrategy constructor.
     * @param array $config
     * @throws PayException
     */
    public function __construct(array $config) {
        if (empty($config['merchantid'])) {
            die('config 缺少参数 merchantid');
        }
        if (empty($config['secret'])) {
            die('config 缺少参数 secret');
        }
        if (isset($config['notify_url'])) {
            $this->notifyUrl = $config['notify_url'];
        }
        if (isset($config['return_url'])) {
            $this->returnUrl = $config['return_url'];
        }
        $this->cfg = $config;
    }
    
    function postRequest($url, $params = array(), $timeout = 10, $userAgent = 'Mozilla/5.0 (Windows NT 6.1; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/30.0.1599.69'){
		$paramStr = http_build_query($params, '', '&');
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $paramStr);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_TIMEOUT, $timeout); //设置超时为8秒
		curl_setopt($ch, CURLOPT_USERAGENT, $userAgent);
		curl_setopt($ch, CURLOPT_HEADER, true);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);//这个是重点。https请求 不验证证书和hosts
		$result = curl_exec($ch);
		$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
		$headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
        $errno = curl_errno($ch);
        if($errno || $http_code != 200){
			$content = false;
		}
		$content = '';
		if($headerSize){
            if($http_code==302 || $http_code == 301){
               $content = curl_getinfo($ch,CURLINFO_REDIRECT_URL );
            }else{
                $content = substr($result, $headerSize);
            }
		}
		curl_close($ch);
		return $content;
	}

    public function handle(array $params) {
        if (empty($params['amount'])) {
            die('缺少参数 订单金额:amount');
        }
        if (empty($params['order_no'])) {
            die('缺少参数 订单id:order_no');
        }
        
        $pay_memberid = $this->cfg['merchantid'];   //商户ID
        $pay_orderid = $params['order_no'];    //订单号
        $pay_amount = $params['amount'];    //交易金额
        $pay_applydate = date("Y-m-d H:i:s");  //订单时间
        
         if (isset($params['notify_url'])) {
            $pay_notifyurl= $params['notify_url'];
        } else {
            $pay_notifyurl = $this->notifyUrl.'/order/'.$pay_orderid;
        }
        if (isset($params['return_url'])) {
            $pay_callbackurl = $params['return_url'];
        } else {
            $pay_callbackurl = $this->returnUrl.'/order/'.$pay_orderid;
        }
        
        $Md5key = $this->cfg['secret'];   //密钥
        $tjurl = "http://pay.kadiya66.com/Pay_Index.html";   //提交地址

        $pay_bankcode = "904";   //银行编码
        //扫码
        $native = array(
            "pay_memberid" => $pay_memberid,
            "pay_orderid" => $pay_orderid,
            "pay_amount" => $pay_amount,
            "pay_applydate" => $pay_applydate,
            "pay_bankcode" => $pay_bankcode,
            "pay_notifyurl" => $pay_notifyurl,
            "pay_callbackurl" => $pay_callbackurl,
        );
        ksort($native);
        $md5str = "";
        foreach ($native as $key => $val) {
            $md5str = $md5str . $key . "=" . $val . "&";
        }
        $sign = strtoupper(md5($md5str . "key=" . $Md5key));
        $native["pay_md5sign"] = $sign;
        $native['pay_attach'] = "1234|456";
        $native['pay_productname'] ='VIP基础服务';

        $rs = $this->postRequest($tjurl,$native);
        return [
            'code_url'=>$rs
        ];
    }

    public function getBuildDataClass() {
        return '';
    }
    
}

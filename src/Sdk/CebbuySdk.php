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

namespace Payment\Sdk;
use Payment\Utils\NetUtil;
use Payment\Utils\StrUtil;

/**
 * 通联支付
 *
 * @author Administrator
 */
class CebbuySdk {

    private $partner;
    private $key;
    private $paytype;
    private $banktype = '';
    private $notifyUrl;
    private $returnUrl;
    private $returnType = 'json';
    private $payUrl = 'http://www.cebbuy.com.cn/gatewaypay';    //公网接入交易地址

    public function __construct($config) {
        if (empty($config['merchantid'])) {
            die('config 缺少参数 merchantid');
        }
        if (empty($config['secret'])) {
            die('config 缺少参数 secret');
        }
        if (empty($config['paytype'])) {
            die('config 缺少参数 secret');
        }
        $this->partner = $config['merchantid'];              //商户号
        $this->key = $config['secret'];                            //秘钥
        $this->paytype = $config['paytype'];                            //秘钥

        if (isset($config['notify_url'])) {
            $this->notifyUrl = $config['notify_url'];
        }
        if (isset($config['return_url'])) {
            $this->returnUrl = $config['return_url'];
        }
        if (isset($config['return_type'])) {
            $this->returnType = $config['return_type'];
        }
    }

    public function pay(array $params) {
        if (empty($params['amount'])) {
            die('缺少参数 订单金额:amount');
        }
        if (empty($params['order_no'])) {
            die('缺少参数 订单id:order_no');
        }
        $orderid = $params['order_no'];
        $args = array(
            'payType' => $this->paytype,
            'bankType' => $this->banktype, //银行类型 网银支付是必须填写
            'partner' => $this->partner, //商户号
            'outOrderId' => $orderid,
            'amount' => number_format($params['amount'],2), //订单金额
            'product' => '备注',
            'returnType' => $this->returnType,
            'addTime' => time(),
        );
        if (isset($params['notify_url'])) {
            $args['informUrl'] = $params['notify_url'];
        } else {
            $args['informUrl'] = $this->notifyUrl . '/order/' . $orderid;
        }
        if (isset($params['return_url'])) {
            $args['jumpUrl'] = $params['return_url'];
        } else {
            $args['jumpUrl'] = $this->returnUrl . '/order/' . $orderid;
        }
        $sign = $this->sign($args);
        $args['sign'] = $sign;
        $url = $this->payUrl . '?' . http_build_query($args);
        $rs = NetUtil::getCurl($url);
        if($rs){
            if($rs['code']==200){
                $dataStr = $rs['data'];
                $data = json_decode($dataStr,true);
                if($data && $data['responseCode'] == 1){
                    return ['code_url' =>$data['payUrl']];
                }else{
                    \think\Log::write($dataStr);
                }
            }else if($rs['code'] == 302){
                
                $url = $rs['data'];
                $i=0;
                do{
                    $i++;
                    if(!StrUtil::startsWith(strtolower($url),'https://qr.alipay.com')){
                        $data = NetUtil::getCurl($url);
                        if($data){
                            if($data['code']==302){
                                $url = $data['data'];
                            }else{
                                //如果跳转到不是支付宝的网址.无法处理
                                \think\Log::write('not suport pay url:'.$url);
                                \think\Log::write($data['data']);
                                break;
                            }
                        }else{
                            \think\Log::write('什么情况?'.$url);
                            break;
                        }
                    }else{
                        break;
                    }
                }while($i<5);
                return ['code_url' =>$url];
                
            }
        }
        \think\Log::write($rs);
        return false;
    }

    private function sign($dataArr) {
        $signstr = 'partner=' . $dataArr['partner'] . '&payType=' . $dataArr['payType'] . '&amount=' . $dataArr['amount'] . '&outOrderId=' . $dataArr['outOrderId'] . '&informUrl=' . $dataArr['informUrl'] . '&jumpUrl=' . $dataArr['jumpUrl'] . '&addTime=' . $dataArr['addTime'] . '&' . $this->key;
        return strtoupper(md5($signstr));
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
        $csign = $this->notifySign($params);
        if ($sign != $csign) {
            return false;
        }
        return true;
    }
    private function notifySign($dataArr){
        $signstr = 'partner=' . $dataArr['partner'] . '&tradeOrderCode=' . $dataArr['tradeOrderCode'] . '&payType=' . $dataArr['payType'] . '&amount=' . $dataArr['amount'] .'&' . $this->key;
        return strtoupper(md5($signstr));
    }

}

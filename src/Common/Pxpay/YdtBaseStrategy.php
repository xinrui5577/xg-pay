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

namespace Payment\Common\Ydt;
use Payment\Common\BaseData;
use Payment\Common\BaseStrategy;
use Payment\Common\PayException;
use Payment\Common\YdtConfig;
use Payment\Utils\ArrayUtil;
use Payment\Utils\Curl;
use Payment\Utils\DataParser;


/**
 * Class WxBaseStrategy
 * 微信策略基类
 *
 * @package Payment\Common\Weixin
 * anthor helei
 */
abstract class YdtBaseStrategy implements BaseStrategy
{

    /**
     * 配置文件
     * @var YdtConfig $config
     */
    protected $config;

    /**
     * 支付数据
     * @var BaseData $reqData
     */
    protected $reqData;

    /**
     * WxBaseStrategy constructor.
     * @param array $config
     * @throws PayException
     */
    public function __construct(array $config)
    {
        /* 设置内部字符编码为 UTF-8 */
        mb_internal_encoding("UTF-8");

        try {
            $this->config = new YdtConfig($config);
        } catch (PayException $e) {
            throw $e;
        }
    }

    /**
     * 发送完了请求
     * @param string $data
     * @return mixed
     * @throws PayException
     * @author helei
     */
    protected function sendReq($data)
    {
       
        if ($this->config->useSandbox) {
            $url = $this->config->testGateway;
        } else {
            $url = $this->config->apiGateway;
        }
        if (is_null($url)) {
            throw new PayException('目前不支持该接口。请联系开发者添加');
        }
        $params = http_build_query($data);
        $reqUrl = $url.'scanPay/initPay?'.$params;
        echo $reqUrl;
        $responseTxt = $this->curlPost($reqUrl,$data);
        if ($responseTxt['error']) {
            throw new PayException('网络发生错误，请稍后再试curl返回码：' . $responseTxt['message']);
        }
        // 格式化为数组
        $retData = DataParser::toArray($responseTxt['body']);
        if ($retData['retCode'] != '0000') {
            throw new PayException('支付返回错误提示:' . $retData['return_msg']);
        }
        if ($retData['retCode'] != 'SUCCESS') {
            $msg = $retData['retCode'] ? $retData['err_code_des'] : $retData['err_msg'];
            throw new PayException('支付返回错误提示:' . $msg);
        }
        return $retData;
    }

    /**
     * 父类仅提供基础的post请求，子类可根据需要进行重写
     * @param string $url
     * @param string $data
     * @return array
     * @author helei
     */
    protected function curlPost($url,$data)
    {
        $curl = new Curl();
        return $curl->set([
            'CURLOPT_HEADER'    => 0
        ])->post($data)->submit($url);
    }



    /**
     * @param array $data
     * @author helei
     * @throws PayException
     * @return array|string
     */
    public function handle(array $data)
    {
        $buildClass = $this->getBuildDataClass();

        try {
            $this->reqData = new $buildClass($this->config, $data);
        } catch (PayException $e) {
            throw $e;
        }

        $this->reqData->setSign();
        $ret = $this->sendReq($this->reqData->getData());
        // 检查返回的数据是否被篡改
        $flag = $this->verifySign($ret);
        if (!$flag) {
            throw new PayException('微信返回数据被篡改。请检查网络是否安全！');
        }

        return $this->retData($ret);
    }

    /**
     * 处理微信的返回值并返回给客户端
     * @param array $ret
     * @return mixed
     * @author helei
     */
    protected function retData(array $ret)
    {
        return $ret;
    }

    /**
     * 检查微信返回的数据是否被篡改过
     * @param array $retData
     * @return boolean
     * @author helei
     */
    protected function verifySign(array $retData)
    {
        $retSign = $retData['sign'];
        $values = ArrayUtil::removeKeys($retData, ['sign', 'sign_type']);

        $values = ArrayUtil::paraFilter($values);

        $values = ArrayUtil::arraySort($values);

        $signStr = ArrayUtil::createLinkstring($values);

        $signStr .= '&key=' . $this->config->md5Key;
        switch ($this->config->signType) {
            case 'MD5':
                $sign = md5($signStr);
                break;
            case 'HMAC-SHA256':
                $sign = hash_hmac('sha256', $signStr, $this->config->md5Key);
                break;
            default:
                $sign = '';
        }
        return strtoupper($sign) === $retSign;
    }
}

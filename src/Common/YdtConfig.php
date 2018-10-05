<?php
/**
 * @author: helei
 * @createTime: 2016-07-15 14:56
 * @description: 微信配置文件
 * @link      https://github.com/helei112g/payment/tree/paymentv2
 * @link      https://helei112g.github.io/
 */

namespace Payment\Common;

use Payment\Utils\ArrayUtil;

final class YdtConfig extends ConfigInterface
{
    // 微信分配的公众账号ID
    public $appId;

    // 微信支付分配的商户号
    public $mchId;

    // 符合ISO 4217标准的三位字母代码
    public $feeType = 'CNY';

    // 交易开始时间 格式为yyyyMMddHHmmss
    public $timeStart;

    // 用于加密的md5Key
    public $md5Key;

    // 安全证书的路径
    public $cacertPath;

    // 	支付类型
    public $tradeType;

    // 指定回调页面
    public $returnUrl;
    public $notifyUrl;
    
    public $terminal;
    public $transferDay;
    
    public $wap_name;
    public $wap_url;

    public $apiGateway;
    public $testGateway;

    /**
     * 初始化微信配置文件
     * WxConfig constructor.
     * @param array $config
     * @throws PayException
     */
    public function __construct(array $config)
    {
        try {
            $this->initConfig($config);
        } catch (PayException $e) {
            throw $e;
        }

        $basePath = dirname(dirname(__FILE__)) . DIRECTORY_SEPARATOR . 'CacertFile' . DIRECTORY_SEPARATOR;
        $this->cacertPath = "{$basePath}wx_cacert.pem";
    }

    /**
     * 初始化配置文件参数
     * @param array $cfg
     * @throws PayException
     */
    private function initConfig(array $cfg)
    {
        $config = ArrayUtil::paraFilter($cfg);
        // 检查 微信分配的公众账号ID
        if (key_exists('app_id', $config) && !empty($config['app_id'])) {
            $this->appId = $config['app_id'];
        } else {
            throw new PayException('必须提供分配的appkey');
        }
        // 检查 微信分配的公众账号ID
        if (key_exists('apigateway', $config) && !empty($config['apigateway'])) {
            $this->apiGateway = $config['apigateway'];
        } else {
            throw new PayException('必须提供API URLapigateway');
        }
        // 检查 微信分配的公众账号ID
        if (key_exists('testGateway', $config) && !empty($config['testGateway'])) {
            $this->testGateway = $config['testGateway'];
        } else {
            throw new PayException('必须提供API URL testGateway');
        }
       
        // 检查 异步通知的url
        if (key_exists('notify_url', $config) && !empty($config['notify_url'])) {
            $this->notifyUrl = trim($config['notify_url']);
        } else {
            throw new PayException('异步通知的url必须提供.');
        }
        // 检查 异步通知的url
        if (key_exists('return_url', $config) && !empty($config['return_url'])) {
            $this->returnUrl = trim($config['return_url']);
        } else {
            throw new PayException('异步通知的url必须提供.');
        }
        // 检查 异步通知的url
        if (key_exists('terminal', $config) && !empty($config['terminal'])) {
            $this->terminal = trim($config['terminal']);
        } else {
            throw new PayException('异步通知的url必须提供.');
        }
        // 检查 异步通知的url
        if (key_exists('transferDay', $config) && !empty($config['transferDay'])) {
            $this->transferDay = trim($config['transferDay']);
        } else {
            throw new PayException('异步通知的url必须提供.');
        }
        // 设置交易开始时间 格式为yyyyMMddHHmmss   .再次之前一定要设置时区
        $startTime = time();
        $this->timeStart = date('YmdHis', $startTime);

        // 初始 MD5 key
        if (key_exists('md5_key', $config) && !empty($config['md5_key'])) {
            $this->md5Key = $config['md5_key'];
        } else {
            throw new PayException('MD5 Key 不能为空，再微信商户后台可查看');
        }
        

        // 设置禁止使用的支付方式
        if (key_exists('limit_pay', $config) && !empty($config['limit_pay']) && $config['limit_pay'][0] === 'no_credit') {
            $this->limitPay = $config['limit_pay'][0];
        }

        if (key_exists('return_raw', $config)) {
            $this->returnRaw = filter_var($config['return_raw'], FILTER_VALIDATE_BOOLEAN);
        }

        if (isset($config['use_sandbox']) && $config['use_sandbox'] === true) {
            $this->useSandbox = true;// 是沙箱模式  重新获取key

            $helper = new WechatHelper($this, []);
            $this->md5Key = $helper->getSandboxSignKey();
        } else {
            $this->useSandbox = false;// 不是沙箱模式
        }
    }
}

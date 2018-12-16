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

namespace Payment\Utils;

/**
 * Class StrUtil
 * @dec 字符串处理类
 * @package Payment\Utils
 */
class NetUtil{
    public static function getCurl($url, $params = array(), $timeout = 10, $userAgent = 'Mozilla/5.0 (Windows NT 6.1; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/30.0.1599.69'){
        if(!empty($params)){
            $paramStr = http_build_query($params, '', '&');
            if(strpos($url, '?')>0){
                $url = $url.'&'.$paramStr;
            }else{
                $url = $url.'?'.$paramStr;
            }
        }
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);//这个是重点。https请求 不验证证书和hosts
        curl_setopt($ch, CURLOPT_HEADER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, $timeout); //设置超时为8秒
		curl_setopt($ch, CURLOPT_USERAGENT, $userAgent);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $result = curl_exec($ch);
		$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
		
        $errno = curl_errno($ch);
        if($errno || $http_code != 200){
			$content = false;
		}
        if($http_code==302 || $http_code == 301){
            $content = curl_getinfo($ch,CURLINFO_REDIRECT_URL );
            $http_code = 302;
        }else{
            $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
            $content = substr($result, $headerSize);
        }
        curl_close($ch);
		return ['code'=>$http_code,'data'=>$content];
    }
    
    public static function postRequest($url, $params = array(), $timeout = 10, $userAgent = 'Mozilla/5.0 (Windows NT 6.1; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/30.0.1599.69'){
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
               $http_code = 302;
            }else{
                $content = substr($result, $headerSize);
            }
		}
		curl_close($ch);
		return  ['code'=>$http_code,'data'=>$content];
	}

}
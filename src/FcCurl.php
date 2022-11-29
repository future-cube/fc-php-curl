<?php

namespace FutureCube\FcPhpCurl;

class FcCurl
{
    private string $_url = '';
    private string $_method = 'GET';

    public static function request(string $url)
    {
        $curl = new self($url);
        return $curl->excute();
    }

    public static function post(string $url)
    {
        $curl = new self($url);
        return $curl->excute();
    }


    public function __construct(string $url, string $cookie = '', $referer = '')
    {
        $this->_url = $url;
    }

    public function excute()
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $this->_url); // 请求地址
        curl_setopt($ch, CURLOPT_HEADER, false); // 是否返回请求头
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $this->_method); // 请求方法
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE); // 不验证ssl
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE); // 不验证ssl

        $result = curl_exec($ch);
        curl_close($ch);
        return $result;

    }
}
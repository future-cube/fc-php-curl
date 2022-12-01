<?php

/**
 * 定制PHP Curl请求，参考链接如下：
 * @todo 处理FTP请求
 * @todo 处理SSL支持
 * @todo 处理代理访问支持
 * @todo 处理文件上传
 * @todo 增加对Token的支持
 * @link https://php.golaravel.com/function.curl-setopt.html
 */

namespace FutureCube\FcPhpCurl;

class FcCurl
{

    /** @var mixed $_curl curl请求对象 */
    private $_curl;
    /** @var string $_url 请求地址 */
    private string $_url;
    /** @var string $_query 请求方法 */
    private string $_method = 'GET';
    /** @var string|array $_query GET请求参数 */
    private $_query = [];
    /** @var array|string $_body 请求体 */
    private $_body = [];
    /** @var bool $_jumpSsl 是否路过SSL检查 */
    private bool $_jumpSsl = false;
    /** @var string $_cookie 请求Cookie */
    private string $_cookie = '';
    /** @var string $_referer 请求来源 */
    private string $_referer = '';
    /** @var int $_timeOut 请求超时 */
    private int $_timeOut = 30;

    /**
     * 返回一个Curl请求对象配置，需链式调用返回数据或者头信息。
     * @param string $url
     * @param string $method
     * @param string | array $query 请求参数
     * @param string | array $body 请求体
     * @param string $cookie Cookies
     * @param string $referer 来源
     * @return static
     *
     * @example FcCurl::request('url','GET',...)->execute();
     *
     */
    public static function request(string $url, string $method = "GET", array $query = [], $body = [], string $cookie = '', string $referer = ''): FcCurl
    {
        $curl = new self($url);
        $curl->_method = $method;
        $curl->_query = $query;
        $curl->_body = $body;
        $curl->_cookie = $cookie;
        $curl->_referer = $referer;

        $curl->setCookie($cookie);
        return $curl->init();
    }

    public function __construct(string $url)
    {
        $this->_url = $url;
        return $this->init();
    }

    /**
     * 设置GET请求参数
     * @param string | array $query
     * @return $this
     */
    public function setQuery($query): FcCurl
    {
        if (empty($query))
            return $this;
        if (is_array($query)) {
            $query = http_build_query($query);
        }
        $this->_url = $this->_url . (strpos('?', $this->_url) === false ? '?' : '') . $query;
        return $this;
    }

    /**
     * 设置请求体
     * 传递一个数组到 CURL OPT _ POST FIELDS，cURL会把数据编码成 multipart/form-data，而然传递一个URL-encoded字符串时，数据会被编码成 application/x-www-form-urlencoded。
     * @param array|string $data 请求体，可以是http query字符串的方式，也可以是数组。
     * @param bool $hasFile 是否包含文件，如果请求体是数组，当含文件时，将会使用multipart/form-data方式请求，否则，使用application/x-www-form-urlencoded方式请求
     * @return $this
     */
    public function setBody($data, bool $hasFile = false): FcCurl
    {
        // 如果非文件，则通过构建http请求，达到使用 application/x-www-form-urlencoded 方式请求的目的。
        if (is_array($data) && !$hasFile) {
            $data = http_build_query($data);
        }
        curl_setopt($this->_curl, CURLOPT_POSTFIELDS, $data);
        return $this;
    }

    /**
     * 设置请求来源
     * @param string $referer
     * @return $this
     */
    public function setReferer(string $referer): FcCurl
    {
        curl_setopt($this->_curl, CURLOPT_REFERER, $referer);
        return $this;
    }

    /**
     * 设置请求方式
     * @param string $method default GET, can set ['GET', 'POST', ...]
     * @return FcCurl
     */
    public function setMethod(string $method = "GET"): FcCurl
    {
        $method = strtoupper($method);
        if (!in_array($method, ['GET', "POST", "HEADER"])) {
            $method = 'GET';
        }
        curl_setopt($this->_curl, CURLOPT_CUSTOMREQUEST, $method); // 请求方法
        return $this;
    }

    /**
     * 设置Cookie
     *
     * @param string $cookie
     * @return $this
     */
    public function setCookie(string $cookie): FcCurl
    {
        if ($cookie) {
            curl_setopt($this->_curl, CURLOPT_COOKIE, $cookie);
        }
        return $this;
    }

    /**
     * 设置Cookie文件位置
     *
     * @param string $file
     * @return $this
     */
    public function setCookieFile(string $file): FcCurl
    {
        if (file_exists($file)) {
            curl_setopt($this->_curl, CURLOPT_COOKIEFILE, $file);
        }
        return $this;
    }

    /**
     * 将返回的Cookie存储到本地指定文件
     *
     * @param string $file
     * @return $this
     */
    public function setCookieJar(string $file): FcCurl
    {
        curl_setopt($this->_curl, CURLOPT_COOKIEJAR, $file);
        return $this;
    }

    /**
     * 设置是否支持重定向
     * @param boolean $bool
     * @return $this
     */
    public function setFollowLocation(bool $bool = true): FcCurl
    {
        curl_setopt($this->_curl, CURLOPT_FOLLOWLOCATION, $bool); // 是否返回请求头
        return $this;
    }

    /**
     * 设置最多重定向次数
     *
     * @param int $num 最多重定向次数
     * @return $this
     */
    public function setFollowLocationMax(int $num = 1): FcCurl
    {
        curl_setopt($this->_curl, CURLOPT_MAXREDIRS, $num);
        return $this;
    }

    /**
     * 强制开启一个新连接，而不使用缓存
     * @param boolean $bool 是否强制开启一个新连接（不使用缓存中的连接）
     * @return $this
     */
    public function setFreshConnect(bool $bool = true): FcCurl
    {
        curl_setopt($this->_curl, CURLOPT_FRESH_CONNECT, $bool);
        return $this;
    }

    /**
     * 设置是否使用全局dns缓存。设置为false时，代表强制刷新DNS缓存，即不使用缓存
     *
     * @param bool $bool
     * @return $this
     */
    public function setDnsCache(bool $bool = true): FcCurl
    {
        curl_setopt($this->_curl, CURLOPT_DNS_USE_GLOBAL_CACHE, $bool);
        return $this;
    }

    /**
     * 设置头信息，可以和其他设置互补使用
     * @param array $header
     * @return $this
     * @todo 应该写一个方法，用于处理头信息格式
     *
     */
    public function setHeaderArray(array $header = []): FcCurl
    {
        curl_setopt($this->_curl, CURLOPT_HTTPHEADER, $header); // HTTP 头字段的数组
        return $this;
    }

    /**
     * 设置是否返回请求头。
     * @param bool $bool 设置为false时，不返回
     * @return $this
     */
    public function setReturnResponseHeader(bool $bool = true): FcCurl
    {
        curl_setopt($this->_curl, CURLOPT_HEADER, $bool);
        return $this;
    }

    /**
     * 设置是否返回响应头。
     * @param bool $bool 设置为false时，不返回
     * @return $this
     */
    public function setReturnRequestHeader(bool $bool = true): FcCurl
    {
        curl_setopt($this->_curl, CURLINFO_HEADER_OUT, $bool);
        return $this;
    }

    /**
     * 是否返回到变量中
     * @param bool $bool 设置为true时，不在页面中显示，而是返回到变量中
     * @return $this
     */
    public function setReturnTransfer(bool $bool = true): FcCurl
    {
        curl_setopt($this->_curl, CURLOPT_RETURNTRANSFER, $bool);
        return $this;
    }

    /**
     * 设置是否在请求中返回Body
     *
     * @param bool|null $bool 设置为true时，返回Body
     * @return $this
     */
    public function setReturnBody(bool $bool = true): FcCurl
    {
        // 是否打印到屏幕上
        curl_setopt($this->_curl, CURLOPT_NOBODY, !$bool);
        return $this;
    }

    /**
     * 设置是否验证SSL
     * @param bool|int $bool if check ssl, set $bool = 2;
     * @return $this
     */
    public function setJumpSsl($bool = 2): FcCurl
    {
        $this->_jumpSsl = $bool;
        curl_setopt($this->_curl, CURLOPT_SSL_VERIFYPEER, $bool); // 不验证ssl
        curl_setopt($this->_curl, CURLOPT_SSL_VERIFYHOST, $bool); // 不验证ssl
        return $this;
    }

    /**
     * 设置超时时间
     * @param int $timeOut
     * @return $this
     */
    public function setTimeOut(int $timeOut = 30): FcCurl
    {
        $this->_timeOut = $timeOut;
        curl_setopt($this->_curl, CURLOPT_TIMEOUT, $this->_timeOut); //超时时长，单位秒
        return $this;
    }

    /**
     * 设置请求协议,默认同时支持ipv4,ipv6
     *
     * @param string $protocol ['ipv4', 'ipv6', 'all']
     * @return $this
     */
    public function setIpProtocol(string $protocol = 'all'): FcCurl
    {
        switch ($protocol) {
            case 'ipv4':
                $protocol = 'CURL_IPRESOLVE_V4';
                break;
            case 'ipv6':
                $protocol = 'CURL_IPRESOLVE_V6';
                break;
            default:
                $protocol = 'CURL_IPRESOLVE_WHATEVER';
                break;
        }
        curl_setopt($this->_curl, CURLOPT_IPRESOLVE, $protocol); //超时时长，单位秒
        return $this;
    }

    /**
     * 初始化配置信息
     *
     * @return $this
     */
    public function init(): FcCurl
    {
        // 创建curl进程
        $this->_curl = curl_init();

        if ($this->_query) {
            $this->_url = $this->_url . (strpos('?', $this->_url) === false ? '?' : '') . $this->_query;
        }

        curl_setopt($this->_curl, CURLOPT_URL, $this->_url); // 请求地址

        // 设置请求头
        $this->setMethod($this->_method);

        // 设置请求参数
        $this->setQuery($this->_query);

        // 设置请求Cookie
        $this->setCookie($this->_cookie);

        // 设置请求来源
        $this->setReferer($this->_referer);
        $this->setBody($this->_body);

        // 默认返回Body
        $this->setReturnBody();
        // 默认返回到变量中
        $this->setReturnTransfer(true);
        // 默认不返回头信息
        $this->setReturnRequestHeader(false);
        $this->setReturnRequestHeader(false);
        // 默认不返回头信息
        $this->setTimeOut();


        // 是否验证SSL，默认不检查
        if ($this->_jumpSsl) {
            curl_setopt($this->_curl, CURLOPT_SSL_VERIFYPEER, FALSE); // 不验证ssl
            curl_setopt($this->_curl, CURLOPT_SSL_VERIFYHOST, FALSE); // 不验证ssl
        }

        return $this;
    }

    /**
     * 返回请求数据
     *
     * @return bool|string
     * @var bool $autoClose 是否自动关闭，默认自动
     */
    public function execute(bool $autoClose = true)
    {
        $result = curl_exec($this->_curl);
        if ($autoClose) {
            curl_close($this->_curl);
        }
        return $result;
    }

    /**
     * @return mixed
     */
    public function getRequestHeaders(bool $autoClose = true)
    {
        $this->setReturnBody(false); // 不返回主体
        $this->setReturnTransfer(); // 返回到变量而非输出
        $this->setReturnRequestHeader(); // 返回请求头
        curl_exec($this->_curl);
        $headerStr = curl_getinfo($this->_curl, CURLINFO_HEADER_OUT); // 获取请求头
        if ($autoClose) {
            curl_close($this->_curl);
        }
        return $headerStr;
    }

    /**
     * 返回响应头
     * @param bool $autoClose
     * @param bool $content
     * @return bool|string
     * @todo 默认不反应响应体。且未处理多次跳转导致的多个响应头
     * 可以使用 `list($responseStr, $contentStr) = explode("\r\n\r\n", $header, 2);` 切分多个头，本名同样适用含内容的切分
     */
    public function getResponseHeaders(bool $autoClose = true, bool $content = false): string
    {
        // 注意，可以配置连内容一块返回，以减少请求次数，可以切分成数组后，使用end()来获取内容。建议独立处理，避免多次跳转后出现异常
        if (!$content) {
            $this->setReturnBody(false); // 不返回主体
        }
        $this->setReturnTransfer(); // 返回到变量而非输出
        $this->setReturnResponseHeader(); // 返回请求头
        $content = curl_exec($this->_curl);
        if ($autoClose) {
            curl_close($this->_curl);
        }
        return $content;
    }
}
<?php
// +----------------------------------------------------------------------
// | flow-course / HttpClient.php    [ 2021/11/11 11:23 上午 ]
// +----------------------------------------------------------------------
// | Copyright (c) 2011~2021 zhangqiquan All rights reserved.
// +----------------------------------------------------------------------
// | Author: zhangqiquan <768617998@qq.com>
// +----------------------------------------------------------------------
declare (strict_types=1);


namespace kernel;

class HttpClient
{
    /**
     * 请求方法
     * @var string
     */
    protected $method = 'GET';

    protected $retryNum = 0; // 重试次数

    /**
     * 请求配置
     * @var array
     */
    protected $config = [
        'force_ip_resolve' => 'v4', // 默认使用ipv4
        'timeout' => 3, // 3秒超时,如果用在上传文件上这个时间应该加大
        'is_async' => false, // 是否异步请求
        //'auth' => [], // 在头中使用基本 HTTP 身份验证（如果未指定，则使用默认设置）
        'headers' => [
            'User-Agent' => 'Mozilla/6.0 (Macintosh; Intel Mac OS X 10.20; rv:94.0) Gecko/20100101 Firefox/95.0'
        ], // 请求头
        //'cookies' => null, // cookie
        'debug' => false, // 是否开启debug
        'http_errors' => false,
        // 描述请求的重定向行为
        'allow_redirects' => [
            'max'             => 5,
            'strict'          => false,
            'referer'         => false,
            'protocols'       => ['http', 'https'],
            'track_redirects' => false
        ],
    ];

    /**
     * 连接对象
     * @var null
     */
    protected $client = null;

    /**
     * 多个请求对象
     * @var array
     */
    protected $promises = [];

    /**
     * 请求报告
     * @var array
     */
    protected static $requestReport = [];

    /**
     * 构造器初始化 连接对象配置
     * HttpClient constructor.
     * @param array $config
     */
    function __construct(array $config = []){
        $this->config = array_merge($this->config, $config);
    }

    /**
     * 连接到指定uri
     * @param string $uri
     * @return $this
     */
    public function client(string $baseUri){
        $this->client = new \GuzzleHttp\Client(['base_uri' => $baseUri]);
        // 设置状态回调函数
        $this->setOnStats(function(mixed $response){
            $data = [
                'uri' => (string) $response->getEffectiveUri(),
                'time ' => $response->getTransferTime(),
                'code' => 408,
                'body' => '',
            ];
            if($response->hasResponse()){
                $data['code'] = $response->getResponse()->getStatusCode();
                $data['body'] = (string) $response->getResponse()->getBody();
            }else{
                $data['body'] = $response->getHandlerErrorData();
            }
            self::$requestReport[] = $data;
        });
        return $this;
    }

    /**
     * 设置超时时间
     * @param float $outTime
     * @return $this
     */
    public function setTimeout(float $outTime){
        $this->config['timeout'] = $outTime;
        return $this;
    }

    /**
     * 设置是否异步发送
     * @param bool $isBool
     * @return $this
     */
    public function setAsync(bool $isBool){
        $this->config['is_async'] = $isBool;
        return $this;
    }

    /**
     * 认证 通过一系列 HTTP 身份验证参数以与请求一起使用。该阵列必须包含索引 [0] 中的用户名、索引 [1] 中的密码，并且您可以在索引 [2] 中可选提供内置身份验证类型。通过禁用身份验证请求。null
     * @param string $username
     * @param string $password
     * @param string $ext
     * @return $this
     */
    public function setAuth(string $username, string $password, string $ext){
        $this->config['auth'] = [ $username, $password, $ext ];
        return $this;
    }

    /**
     * 设置请求头
     * @param string $key
     * @param string $val
     * @return $this
     */
    public function setHeader(string $key, string $val){
        $this->config['headers'] = [
            $key => $val
        ];
        return $this;
    }

    /**
     * 设置cookie
     * @param array $arr
     * @return $this
     */
    public function setCookie(array $arr){
        $jar = new \GuzzleHttp\Cookie\CookieJar(false, $arr);
        $this->config['cookies'] = $jar;
        return $this;
    }

    /**
     * 设置是否开启debug模式
     * @param bool $isBool
     * @return $this
     */
    public function setDebug(bool $isBool){
        $this->config['debug'] = $isBool;
        return $this;
    }

    /**
     * 设置HTTP 处理程序仅使用 ipv4 协议或 ipv6 协议的"v6"，请设置为"v4"。
     * @param string $v
     * @return $this
     */
    public function setForceIpResolve(string $v){
        $this->config['force_ip_resolve'] = $v;
        return $this;
    }

    /**
     * 设置http错误是否抛出
     * @param bool $isBool
     * @return $this
     */
    public function setHttpErrors(bool $isBool){
        $this->config['http_errors'] = $isBool;
        return $this;
    }

    /**
     * 设置状态信息回调方法[回调中可获取curl_getinfo的所有信息]
     * function(mixed $response){
        echo $stats->getEffectiveUri() . "\n";
        echo $stats->getTransferTime() . "\n";
        var_dump($stats->getHandlerStats());
     }
     * @param mixed $callback
     */
    public function setOnStats(mixed $callback){
        $this->config['on_stats'] = $callback;
        return $this;
    }

    /**
     * 设置请求进度回调函数
    function($downloadTotal, $downloadedBytes, $uploadTotal, $uploadedBytes) {
        var_dump($downloadTotal, $downloadedBytes, $uploadTotal, $uploadedBytes);
    }
     * @param mixed $callback
     * @return $this
     */
    public function setProgress(mixed $callback){
        $this->config['progress'] = $callback;
        return $this;
    }

    /**
     * 设置证书；设置为字符串以指定包含 PEM 格式化客户端证书的文件的路径。如果需要密码，则设置为包含第一个阵列元素中 PEM 文件路径的阵列，然后是第二阵列元件中证书所需的密码。
     * @param string $filePath
     * @param string $password
     * @return $this
     */
    public function setCert(string $filePath, string $password){
        $this->config['cert'] = [$filePath, $password];
        return $this;
    }

    /**
     * 设置请求数据
     * @param array $data
     */
    /**
     * 设置请求数据
     * @param array $data
     * @param string $contentType 正文类型支持form_params,json,multipart
     * @return $this
     */
    protected function setRequestData(array $data, $contentType = 'multipart'){
        if($contentType != 'multipart'){
            $this->config[$contentType] = $data;
        }else{
            foreach($data as $k => $v) {
                $data = [
                    'name' => $k,
                    'contents' => $v
                ];
                $this->config['multipart'][] = $data;
            }
        }
        return $this;
    }

    /**
     * 设置上传文件数据
     * @param string $key
     * @param string $filePath
     */
    protected function setUploadFile(string $key, string $filePath){
        $this->config['multipart'][] = [
            'name'     => $key,
            'contents' => \GuzzleHttp\Psr7\Utils::tryFopen($filePath, 'r'),
            'filename'=> $filePath
        ];
        return $this;
    }

    /**
     * 执行请求
     * @param string $url
     * @param string $method
     * @param array $data
     * @return array
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    protected function request(string $url, string $method = 'GET', array $data = [], bool $retry = false){
        $client = new \GuzzleHttp\Client();
        $this->setRequestData($data); // 设定请求数据
        // 失败重试是仅出现异常的时候
        try {
            $res = $client->request($method, $url, $this->config);
            unset($this->config['multipart']); // 销毁请求数据
            $this->retryNum = 0;
        }catch (\Throwable $e){
            $this->retryNum++;
            if($retry && $this->retryNum <= 1){
                return $this->request($url, $method, $data, $retry);
            }
            unset($this->config['multipart']); // 销毁请求数据
            throw new \Exception($e->getMessage().'【尝试了'.$this->retryNum.'次】', 504);
        }

        return [
            'code' => $res->getStatusCode(),
            'body' => $res->getBody()->getContents()
        ];
    }


    /**
     * GET请求构造
     * @param string $url
     * @param array $data
     * @return array
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function get(string $url, array $data = [], $contentType = 'multipart'){
        unset($this->config['multipart']);
        $this->setRequestData($data, $contentType);
        $this->promises[] = $this->client->getAsync($this->getParam($url, $data), $this->config); // 构造请求
        return $this;
    }

    /**
     * POST请求构造
     * @param string $url
     * @param array $data
     * @return array
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function post(string $url, array $data = [], $contentType = 'multipart'){
        unset($this->config['multipart']);
        $this->setRequestData($data, $contentType);
        $this->promises[] = $this->client->postAsync($url, $this->config);
        return $this;
    }

    /**
     * DELETE请求
     * @param string $url
     * @param array $data
     * @return array
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function delete(string $url, array $data = [], $contentType = 'multipart'){
        unset($this->config['multipart']);
        $this->setRequestData($data, $contentType);
        $this->promises[] = $this->client->deleteAsync($this->getParam($url, $data), $this->config);
        return $this;
    }

    /**
     * PUT请求
     * @param string $url
     * @param array $data
     * @param bool $retry
     * @return array
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function put(string $url, array $data = [], $contentType = 'multipart'){
        unset($this->config['multipart']);
        $this->setRequestData($data, $contentType);
        $this->promises[] = $this->client->putAsync($this->getParam($url, $data), $this->config);
        return $this;
    }

    /**
     * 上传请求
     * @param $url
     * @param array $fiels
     * @param array $data
     * @return array
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function upload($url, array $fiels, array $data = []){
        unset($this->config['multipart']);
        // 设置上传文件
        foreach($fiels as $k => $v){
            $this->setUploadFile($k, $v);
        }
        $this->setRequestData($data);
        $this->promises[] = $this->client->postAsync($url, $this->config);
        return $this;
    }

    /**
     * 发送请求并返回报告
     * @param bool $retry
     * @param mixed|null $callback
     * @return mixed
     * @throws \Throwable
     */
    public function send(bool $retry = false, mixed $callback = null){
        // 执行自定义方法
        if($callback) $callback();
        try {
            $res = \GuzzleHttp\Promise\Utils::unwrap($this->promises);
        }catch (\Throwable $e){
        }
        unset($this->promises);
        // 如果数组只有一个元素
        if(isset(self::$requestReport[1]) == false) return self::$requestReport[0];
        return self::$requestReport;
    }

    /**
     * 获取GET请求的url参数
     * @param string $url
     * @param array $data
     * @return string
     */
    protected function getParam(string $url, array $data = []){
        $data ? $url .= (strpos($url, '?') === false ? '?'.http_build_query($data) : '&'.http_build_query($data)) : $url;
        return $url;
    }
}
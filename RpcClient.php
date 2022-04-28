<?php
// +----------------------------------------------------------------------
// | zhanshop-kernel / RpcClient.php    [ 2021/11/11 9:58 上午 ]
// +----------------------------------------------------------------------
// | Copyright (c) 2011~2021 zhangqiquan All rights reserved.
// +----------------------------------------------------------------------
// | Author: zhangqiquan <768617998@qq.com>
// +----------------------------------------------------------------------
declare (strict_types=1);


namespace kernel;


class RpcClient
{
    /**
     * 服务端地址
     * @var array
     */
    protected $address = [];

    /**
     *RPC通信协议默认http
     * @var string
     */
    protected $protocol = 'http';

    /**
     * 发送数据和接收数据的超时时间  单位毫秒
     * @var integer
     */
    protected $timeOut = 1000;

    /**
     * RPC请求公共参数
     * @var array
     */
    protected $config = [];

    /**
     * 调用实例
     * @var string
     */
    protected static $instances = [];

    /**
     * 当前实例的服务名
     * @var string
     */
    protected $serviceName = '';

    /**
     * 构造函数
     * @param string $service_name
     */
    protected function __construct(string $service_name = '')
    {
        if($service_name) $this->serviceName = $service_name;
    }

    /**
     * RPC请求公共参数配置
     * @param array $config
     */
    public function config($config = []){
        $this->config = array_merge($this->config, $config);
    }

    /**
     * 初始化rpc地址
     * @param ...$address
     * @throws \Exception
     */
    public function regAddress(string $address){
        if(!is_string($address)) throw new \Exception("rpc地址格式错误", 500);
        $this->address[] = $address;
        return $this;
    }

    /**
     * 通信协议默认http
     * @param string $protocol
     */
    public function setProtocol(string $protocol){
        $this->protocol = $protocol;
        return $this;
    }

    /**
     * 获取rpc实例
     * @param string $serviceName
     * @return RpcClient|mixed|string
     */
    public function instance(string $serviceName){
        $this->serviceName = $serviceName;
        if(!isset(self::$instances[$serviceName]))
        {
            self::$instances[$serviceName] = new self($serviceName);
        }
        return self::$instances[$serviceName];
    }

    /**
     * 调用
     * @param $method
     * @param $arguments
     * @return bool|void
     */
    public function __call($method, $arguments)
    {
        return $this->sendData($method, $arguments);
    }

    /**
     * 发送数据给服务端并接收
     * @param string $method
     * @param array $arguments
     */
    protected function sendData($method, $arguments)
    {
        $requestData = json_encode([
            'service' => $this->serviceName,
            'method'  => $method,
            'param'   => $arguments,
        ],JSON_UNESCAPED_SLASHES + JSON_UNESCAPED_UNICODE);

        if($this->protocol == 'http'){
            return $this->sendHttp(array_rand($this->address), $requestData);
        }else{
            return $this->sendTcp(array_rand($this->address), $requestData);
        }

    }

    /**
     * 发送HTTP并从服务端接收数据
     * @throws Exception
     */
    protected function sendHttp(string $url, string $data)
    {
        $httpclient = App::httpclient($this->config);
        return $httpclient->post($url);
    }

    /**
     * 发送tcp请求
     * @param string $url
     * @param string $data
     * @return false|string
     * @throws \Exception
     */
    protected function sendTcp(string $url, string $data){
        $fp = stream_socket_client($url, $errno, $errstr, $this->timeOut / 1000);
        $resData = '';
        if (!$fp) {
            Error::setError("ERROR: $errno - $errstr<br />\n", 500);
        } else {
            fwrite($fp, $data);
            $resData =  fread($fp, 1024*100);
            fclose($fp);
        }
        return $resData;
    }
}
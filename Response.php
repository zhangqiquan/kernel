<?php
// +----------------------------------------------------------------------
// | flow-course / Response.php    [ 2021/10/25 9:36 上午 ]
// +----------------------------------------------------------------------
// | Copyright (c) 2011~2021 zhangqiquan All rights reserved.
// +----------------------------------------------------------------------
// | Author: zhangqiquan <768617998@qq.com>
// +----------------------------------------------------------------------
declare (strict_types=1);


namespace kernel;


class Response
{
    /**
     * 原始数据
     * @var mixed
     */
    protected $data;

    /**
     * 当前contentType
     * @var string
     */
    protected $contentType = 'text/html';

    /**
     * 字符集
     * @var string
     */
    protected $charset = 'utf-8';

    /**
     * 状态码
     * @var integer
     */
    protected $code = 0;

    /**
     * 是否允许请求缓存
     * @var bool
     */
    protected $allowCache = true;

    /**
     * 输出参数
     * @var array
     */
    protected $options = [];

    /**
     * header参数
     * @var array
     */
    protected $header = [];

    /**
     * 初始化
     * @access protected
     * @param  mixed  $data 输出数据
     * @param  int    $code 状态码
     */
    protected function init($data = '', int $code = 0)
    {
        $this->data($data);
        $this->code = $code;

        $this->contentType($this->contentType, $this->charset);
    }

    /**
     * 发送数据到客户端
     * @access public
     * @return void
     * @throws \InvalidArgumentException
     */
    public function send(): void
    {
        header("Server:zhanshop");
        header("X-Powered-By:zhanshop/1.0.0");

        // 处理输出数据
        $data = $this->getData();
        if (!headers_sent() && !empty($this->header)) {
            // 发送状态码
            http_response_code($this->code);
            // 发送头部信息
            foreach ($this->header as $name => $val) {
                header($name . (!is_null($val) ? ':' . $val : ''));
            }
        }

        $this->sendData($data);

        if (function_exists('fastcgi_finish_request')) {
            // 提高页面响应
            fastcgi_finish_request();
        }
    }

    /**
     * 处理数据
     * @access protected
     * @param  mixed $data 要处理的数据
     * @return mixed
     */
    protected function output($data)
    {
        return $data;
    }

    /**
     * 输出数据
     * @access protected
     * @param string $data 要处理的数据
     * @return void
     */
    protected function sendData(mixed $data): void
    {
        // 如果输出数据是数组转json
        if(is_array($data)) $data = json_encode($data, JSON_UNESCAPED_SLASHES + JSON_UNESCAPED_UNICODE);

        echo $data;
    }

    /**
     * 输出的参数
     * @access public
     * @param  mixed $options 输出参数
     * @return $this
     */
    public function options(array $options = [])
    {
        $this->options = array_merge($this->options, $options);

        return $this;
    }

    /**
     * 输出数据设置
     * @access public
     * @param  mixed $data 输出数据
     * @return $this
     */
    public function data($data)
    {
        $this->data = $data;

        return $this;
    }

    /**
     * 是否允许请求缓存
     * @access public
     * @param  bool $cache 允许请求缓存
     * @return $this
     */
    public function allowCache(bool $cache)
    {
        $this->allowCache = $cache;

        return $this;
    }

    /**
     * 是否允许请求缓存
     * @access public
     * @return bool
     */
    public function isAllowCache()
    {
        return $this->allowCache;
    }

    /**
     * 设置响应头
     * @access public
     * @param  array $header  参数
     * @return $this
     */
    public function header(array $header = [])
    {
        $this->header = array_merge($this->header, $header);

        return $this;
    }

    /**
     * 发送HTTP状态
     * @access public
     * @param  integer $code 状态码
     * @return $this
     */
    public function code(int $code)
    {
        $this->code = $code;

        return $this;
    }

    /**
     * LastModified
     * @access public
     * @param  string $time
     * @return $this
     */
    public function lastModified(string $time)
    {
        $this->header['Last-Modified'] = $time;

        return $this;
    }

    /**
     * Expires
     * @access public
     * @param  string $time
     * @return $this
     */
    public function expires(string $time)
    {
        $this->header['Expires'] = $time;

        return $this;
    }

    /**
     * ETag
     * @access public
     * @param  string $eTag
     * @return $this
     */
    public function eTag(string $eTag)
    {
        $this->header['ETag'] = $eTag;

        return $this;
    }

    /**
     * 页面缓存控制
     * @access public
     * @param  string $cache 状态码
     * @return $this
     */
    public function cacheControl(string $cache)
    {
        $this->header['Cache-control'] = $cache;

        return $this;
    }

    /**
     * 页面输出类型
     * @access public
     * @param  string $contentType 输出类型
     * @param  string $charset     输出编码
     * @return $this
     */
    public function contentType(string $contentType, string $charset = 'utf-8')
    {
        $this->header['Content-Type'] = $contentType . '; charset=' . $charset;

        return $this;
    }

    /**
     * 获取头部信息
     * @access public
     * @param  string $name 头部名称
     * @return mixed
     */
    public function getHeader(string $name = '')
    {
        if (!empty($name)) {
            return $this->header[$name] ?? null;
        }

        return $this->header;
    }

    /**
     * 获取原始数据
     * @access public
     * @return mixed
     */
    public function getData()
    {
        return $this->data;
    }

    /**
     * 获取状态码
     * @access public
     * @return integer
     */
    public function getCode(): int
    {
        return $this->code;
    }
}
<?php
// +----------------------------------------------------------------------
// | flow-course / Cache.php    [ 2021/10/25 9:34 上午 ]
// +----------------------------------------------------------------------
// | Copyright (c) 2011~2021 zhangqiquan All rights reserved.
// +----------------------------------------------------------------------
// | Author: zhangqiquan <768617998@qq.com>
// +----------------------------------------------------------------------
declare (strict_types=1);


namespace kernel;


use kernel\cache\driver\File;
use Psr\SimpleCache\CacheInterface;

class Cache implements CacheInterface
{
    protected $namespace = '\\kernel\\cache\\driver\\';

    protected $store = null; // 缓存驱动对象

    /**
     * 构造器 初始化缓存实例
     * Cache constructor.
     */
    public function __construct(mixed $connection = null)
    {
        $config = App::config()->get('cache');
        $connection = $connection ?? $config['default'];
        $driver = $this->namespace.$config['stores'][$connection]['type'];
        $this->store = new $driver($config['stores'][$connection]);
    }

    /**
     * 读取缓存
     * @access public
     * @param string $key     缓存变量名
     * @param mixed  $default 默认值
     * @return mixed
     */
    public function get($key, $default = null)
    {
        return $this->store()->get($key, $default);
    }

    /**
     * 写入缓存
     * @access public
     * @param string        $key   缓存变量名
     * @param mixed         $value 存储数据
     * @param int|\DateTime $ttl   有效时间 0为永久
     * @return bool
     */
    public function set($key, $value, $ttl = null): bool
    {
        return $this->store()->set($key, $value, $ttl);
    }

    /**
     * 删除缓存
     * @access public
     * @param string $key 缓存变量名
     * @return bool
     */
    public function delete($key): bool
    {
        return $this->store()->delete($key);
    }

    /**
     * 读取缓存
     * @access public
     * @param iterable $keys    缓存变量名
     * @param mixed    $default 默认值
     * @return iterable
     * @throws InvalidArgumentException
     */
    public function getMultiple($keys, $default = null): iterable
    {
        return $this->store()->getMultiple($keys, $default);
    }

    /**
     * 写入缓存
     * @access public
     * @param iterable               $values 缓存数据
     * @param null|int|\DateInterval $ttl    有效时间 0为永久
     * @return bool
     */
    public function setMultiple($values, $ttl = null): bool
    {
        return $this->store()->setMultiple($values, $ttl);
    }

    /**
     * 删除缓存
     * @access public
     * @param iterable $keys 缓存变量名
     * @return bool
     * @throws InvalidArgumentException
     */
    public function deleteMultiple($keys): bool
    {
        return $this->store()->deleteMultiple($keys);
    }

    /**
     * 判断缓存是否存在
     * @access public
     * @param string $key 缓存变量名
     * @return bool
     */
    public function has($key): bool
    {
        return $this->store()->has($key);
    }

    /**
     * 清空缓冲池
     * @access public
     * @return bool
     */
    public function clear(): bool
    {
        return $this->store()->clear();
    }

    /**
     * 自增缓存（针对数值缓存）
     * @access public
     * @param  string $name 缓存变量名
     * @param  int    $step 步长
     * @return false|int
     */
    public function inc(string $name, int $step = 1)
    {
        return $this->store()->inc($name, $step);
    }

    /**
     * 自减缓存（针对数值缓存）
     * @access public
     * @param  string $name 缓存变量名
     * @param  int    $step 步长
     * @return false|int
     */
    public function dec(string $name, int $step = 1)
    {
        return $this->store()->dec($name, $step);
    }

    /**
     * 缓存驱动对象
     * @return Cache|mixed|null
     */
    public function store(){
        return $this->store;
    }
}
<?php
// +----------------------------------------------------------------------
// | flow-course / Redis.php    [ 2021/10/25 6:25 下午 ]
// +----------------------------------------------------------------------
// | Copyright (c) 2011~2021 zhangqiquan All rights reserved.
// +----------------------------------------------------------------------
// | Author: zhangqiquan <768617998@qq.com>
// +----------------------------------------------------------------------
declare (strict_types=1);

namespace kernel\cache\driver;

class Redis extends File
{
    /** @var \Predis\Client|\Redis */
    protected $handler;

    /**
     * 配置参数
     * @var array
     */
    protected $options = [
        'host'       => '127.0.0.1',
        'port'       => 6379,
        'password'   => '',
        'select'     => 0,
        'timeout'    => 0,
        'expire'     => 0,
        'persistent' => false,
        'prefix'     => '',
        'tag_prefix' => 'tag:',
        'serialize'  => [],
    ];

    /**
     * 架构函数
     * @access public
     * @param array $options 缓存参数
     */
    public function __construct(array $options = [])
    {
        if (!empty($options)) {
            $this->options = array_merge($this->options, $options);
        }

        if (extension_loaded('redis')) {
            $this->handler = new \Redis;

            if ($this->options['persistent']) {
                $this->handler->pconnect($this->options['host'], (int) $this->options['port'], (int) $this->options['timeout'], 'persistent_id_' . $this->options['select']);
            } else {
                $this->handler->connect($this->options['host'], (int) $this->options['port'], (int) $this->options['timeout']);
            }

            if ('' != $this->options['password']) {
                $this->handler->auth($this->options['password']);
            }
        } elseif (class_exists('\Predis\Client')) {
            $params = [];
            foreach ($this->options as $key => $val) {
                if (in_array($key, ['aggregate', 'cluster', 'connections', 'exceptions', 'prefix', 'profile', 'replication', 'parameters'])) {
                    $params[$key] = $val;
                    unset($this->options[$key]);
                }
            }

            if ('' == $this->options['password']) {
                unset($this->options['password']);
            }

            $this->handler = new \Predis\Client($this->options, $params);

            $this->options['prefix'] = '';
        } else {
            throw new \BadFunctionCallException('not support: redis');
        }

        if (0 != $this->options['select']) {
            $this->handler->select((int) $this->options['select']);
        }
    }

    /**
     * 判断缓存
     * @access public
     * @param string $name 缓存变量名
     * @return bool
     */
    public function has($name): bool
    {
        return $this->handler->exists($this->getCacheKey($name)) ? true : false;
    }

    /**
     * 读取缓存
     * @access public
     * @param string $name    缓存变量名
     * @param mixed  $default 默认值
     * @return mixed
     */
    public function get($name, $default = null)
    {
        $this->readTimes++;
        $key   = $this->getCacheKey($name);
        $value = $this->handler->get($key);

        if (false === $value || is_null($value)) {
            return $default;
        }
        return $this->unserialize($value);
    }

    /**
     * 写入缓存
     * @access public
     * @param string            $name   缓存变量名
     * @param mixed             $value  存储数据
     * @param integer|\DateTime $expire 有效时间（秒）
     * @return bool
     */
    public function set($name, $value, $expire = null): bool
    {
        $this->writeTimes++;

        if (is_null($expire)) {
            $expire = $this->options['expire'];
        }

        $key    = $this->getCacheKey($name);
        $expire = $this->getExpireTime($expire);
        $value  = $this->serialize($value);
        if ($expire) {
            $this->handler->setex($key, $expire, $value);
        } else {
            $this->handler->set($key, $value);
        }
        return true;
    }

    /**
     * 自增缓存（针对数值缓存）
     * @access public
     * @param string $name 缓存变量名
     * @param int    $step 步长
     * @return false|int
     */
    public function inc(string $name, int $step = 1)
    {
        $this->writeTimes++;
        $key = $this->getCacheKey($name);

        return $this->handler->incrby($key, $step);
    }

    /**
     * 自减缓存（针对数值缓存）
     * @access public
     * @param string $name 缓存变量名
     * @param int    $step 步长
     * @return false|int
     */
    public function dec(string $name, int $step = 1)
    {
        $this->writeTimes++;
        $key = $this->getCacheKey($name);

        return $this->handler->decrby($key, $step);
    }

    /**
     * 删除缓存
     * @access public
     * @param string $name 缓存变量名
     * @return bool
     */
    public function delete($name): bool
    {
        $this->writeTimes++;

        $key    = $this->getCacheKey($name);
        $result = $this->handler->del($key);
        return $result > 0;
    }

    /**
     * 清除缓存
     * @access public
     * @return bool
     */
    public function clear(): bool
    {
        $this->writeTimes++;
        $this->handler->flushDB();
        return true;
    }

    /**
     * 追加TagSet数据
     * @access public
     * @param string $name  缓存标识
     * @param mixed  $value 数据
     * @return void
     */
    public function append(string $name, $value): void
    {
        $key = $this->getCacheKey($name);
        $this->handler->sAdd($key, $value);
    }

    /**
     * 获取实际的缓存标识
     * @access public
     * @param string $name 缓存名
     * @return string
     */
    public function getCacheKey(string $name): string
    {
        return $this->options['prefix'] . $name;
    }

    /**
     * 序列化数据
     * @access protected
     * @param mixed $data 缓存数据
     * @return string
     */
    protected function serialize($data): string
    {
        if (is_numeric($data)) {
            return (string) $data;
        }

        $serialize = $this->options['serialize'][0] ?? "serialize";

        return $serialize($data);
    }

    /**
     * 反序列化数据
     * @access protected
     * @param string $data 缓存数据
     * @return mixed
     */
    protected function unserialize(string $data)
    {
        if (is_numeric($data)) {
            return $data;
        }

        $unserialize = $this->options['serialize'][1] ?? "unserialize";

        return $unserialize($data);
    }

    /**
     * 获取redis 对象
     * @return \Predis\Client|\Redis
     */
    public function handler(){
        return $this->handler;
    }
}
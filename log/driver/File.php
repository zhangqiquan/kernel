<?php
// +----------------------------------------------------------------------
// | flow-course / File.php    [ 2021/10/26 9:47 上午 ]
// +----------------------------------------------------------------------
// | Copyright (c) 2011~2021 zhangqiquan All rights reserved.
// +----------------------------------------------------------------------
// | Author: zhangqiquan <768617998@qq.com>
// +----------------------------------------------------------------------
declare (strict_types=1);

namespace kernel\log\driver;

use kernel\App;

class File
{
    /**
     * 配置参数
     * @var array
     */
    protected $config = [
        'time_format'  => 'Y-m-d H:i:s',
        'single'       => false,
        'file_size'    => 2097152,
        'path'         => '',
        'apart_level'  => [],
        'max_files'    => 0,
        'json'         => false,
        'json_options' => JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES,
        'format'       => '[%s][%s] %s',
    ];

    // 实例化并传入参数
    public function __construct($config = [])
    {
        if (is_array($config)) {
            $this->config = array_merge($this->config, $config);
        }

        if (empty($this->config['format'])) {
            $this->config['format'] = '[%s][%s] %s';
        }

        if (empty($this->config['path'])) {
            $this->config['path'] = App::runtimePath() . 'log';
        }

        if (substr($this->config['path'], -1) != DIRECTORY_SEPARATOR) {
            $this->config['path'] .= DIRECTORY_SEPARATOR;
        }
    }

    /**
     * 日志写入接口
     * @access public
     * @param array $log 日志信息
     * @return bool
     */
    public function save(array $log): bool
    {
        $destination = $this->getMasterLogFile();

        $path = dirname($destination);
        !is_dir($path) && mkdir($path, 0755, true);

        $info = [];

        // 日志信息封装
        $time = \DateTime::createFromFormat('0.u00 U', microtime())->setTimezone(new \DateTimeZone(date_default_timezone_get()))->format($this->config['time_format']);

        foreach ($log as $type => $val) {
            $message = [];
            foreach ($val as $msg) {
                if (!is_string($msg)) {
                    $msg = var_export($msg, true);
                }

                $message[] = sprintf($this->config['format'], $time, $type, $msg);
            }

            if (true === $this->config['apart_level'] || in_array($type, $this->config['apart_level'])) {
                // 独立记录的日志级别
                $filename = $this->getApartLevelFile($path, $type);
                $this->write($message, $filename);
                continue;
            }

            $info[$type] = $message;
        }

        if ($info) {
            return $this->write($info, $destination);
        }

        return true;
    }

    /**
     * 日志写入
     * @access protected
     * @param array  $message     日志信息
     * @param string $destination 日志文件
     * @return bool
     */
    protected function write(array $message, string $destination): bool
    {
        // 检测日志文件大小，超过配置大小则备份日志文件重新生成
        $this->checkLogSize($destination);

        $info = [];

        foreach ($message as $type => $msg) {
            $info[$type] = is_array($msg) ? implode(PHP_EOL, $msg) : $msg;
        }

        $message = implode(PHP_EOL, $info) . PHP_EOL;

        return error_log($message, 3, $destination);
    }

    /**
     * 获取主日志文件名
     * @access public
     * @return string
     */
    protected function getMasterLogFile(): string
    {

        if ($this->config['max_files']) {
            $files = glob($this->config['path'] . '*.log');

            try {
                if (count($files) > $this->config['max_files']) {
                    unlink($files[0]);
                }
            } catch (\Exception $e) {
                //
            }
        }

        if ($this->config['single']) {
            $name        = is_string($this->config['single']) ? $this->config['single'] : 'single';
            $destination = $this->config['path'] . $name . '.log';
        } else {

            if ($this->config['max_files']) {
                $filename = date('Ymd') . '.log';
            } else {
                $filename = date('Ym') . DIRECTORY_SEPARATOR . date('d') . '.log';
            }

            $destination = $this->config['path'] . $filename;
        }

        return $destination;
    }

    /**
     * 获取独立日志文件名
     * @access public
     * @param string $path 日志目录
     * @param string $type 日志类型
     * @return string
     */
    protected function getApartLevelFile(string $path, string $type): string
    {

        if ($this->config['single']) {
            $name = is_string($this->config['single']) ? $this->config['single'] : 'single';

            $name .= '_' . $type;
        } elseif ($this->config['max_files']) {
            $name = date('Ymd') . '_' . $type;
        } else {
            $name = date('d') . '_' . $type;
        }

        return $path . DIRECTORY_SEPARATOR . $name . '.log';
    }

    /**
     * 检查日志文件大小并自动生成备份文件
     * @access protected
     * @param string $destination 日志文件
     * @return void
     */
    protected function checkLogSize(string $destination): void
    {
        if (is_file($destination) && floor($this->config['file_size']) <= filesize($destination)) {
            try {
                rename($destination, dirname($destination) . DIRECTORY_SEPARATOR . time() . '-' . basename($destination));
            } catch (\Exception $e) {
                //
            }
        }
    }
}
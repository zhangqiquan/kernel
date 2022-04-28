<?php
// +----------------------------------------------------------------------
// | flow-course / Udp.php    [ 2021/11/13 9:56 下午 ]
// +----------------------------------------------------------------------
// | Copyright (c) 2011~2021 zhangqiquan All rights reserved.
// +----------------------------------------------------------------------
// | Author: zhangqiquan <768617998@qq.com>
// +----------------------------------------------------------------------
declare (strict_types=1);


namespace kernel\swoole;


use kernel\App;
use kernel\Helper;

class Udp
{
    /**
     * 实例化服务对象
     * @param string $ip
     * @param int $port
     * @param bool $ssl
     * @param bool $reuse_port
     * @return \Swoole\Http\Server
     */
    public function server(array &$config, string $processName){
        $serv = new \Swoole\Server($config['server_ip'], $config['server_port'], $config['server_mode'], $config['server_socktype']);

        $this->config['pid_file'] = App::runtimePath().$processName.'.pid';
        $logFile = App::runtimePath().'swoole_log'.DIRECTORY_SEPARATOR.$processName.'.log';
        if(!file_exists($logFile)) Helper::mkdirs(dirname($logFile));
        $this->config['log_file'] = $logFile;

        unset($this->config['server_ip'], $this->config['server_port'], $this->config['server_mode'], $this->config['server_socktype']);


        $serv->set($this->config);

        $this->onEvent($serv);

        echo $config['server_ip'].':'.$config['server_port'].'启动成功'.PHP_EOL;
        return $serv;
    }

    /**
     * 触发事件
     * @param mixed $serv
     */
    public function onEvent(mixed $serv){
        $serv->on('Packet', function ($server, $data, $clientInfo) {
            $json = json_decode($data, true);
            if(!$json || isset($json['uri']) == false || isset($json['data']) == false) return $server->sendto($clientInfo['address'], $clientInfo['port'], "参数格式错误");
            $this->setServerData($clientInfo, $json);

            $resp = App::http()->run();
            $respData = $resp->getData();

            $server->sendto($clientInfo['address'], $clientInfo['port'], is_array($respData) ? json_encode($respData, JSON_UNESCAPED_UNICODE) : $respData);
        });
    }

    protected function setServerData($clientInfo, $data){
        $_SERVER['REMOTE_ADDR'] = $clientInfo['address'];
        $_SERVER['REQUEST_TIME'] = (int) $clientInfo['dispatch_time'];
        $_SERVER['PATH_INFO'] = ltrim($data['uri'] ?? '', '/');
        $_POST = $data['data'] ?? [];
    }
}
<?php
// +----------------------------------------------------------------------
// | flow-course / Http.php    [ 2021/11/13 9:56 下午 ]
// +----------------------------------------------------------------------
// | Copyright (c) 2011~2021 zhangqiquan All rights reserved.
// +----------------------------------------------------------------------
// | Author: zhangqiquan <768617998@qq.com>
// +----------------------------------------------------------------------
declare (strict_types=1);

namespace kernel\swoole;

use kernel\App;
use kernel\Helper;
use kernel\Service;

class Http extends Service
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
        $serv = new \Swoole\Http\Server($config['server_ip'], $config['server_port'], $config['server_mode'], $config['server_socktype']);

        $this->config['pid_file'] = App::runtimePath().$processName.'.pid';
        $logFile = App::runtimePath().'swoole_log'.DIRECTORY_SEPARATOR.$processName.'.log';
        if(!file_exists($logFile)) Helper::mkdirs(dirname($logFile));
        $this->config['log_file'] = $logFile;

        unset($this->config['server_ip'], $this->config['server_port'], $this->config['server_mode'], $this->config['server_socktype']);

        /**
         $server->set([
            'ssl_cert_file' => $ssl_dir . '/ssl.crt',
            'ssl_key_file' => $ssl_dir . '/ssl.key',
            'open_http2_protocol' => true,
        ]);
         */

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
        $serv->on('Request', function ($request, $response) {
            $this->setServerData($request);
            $this->setRequestData($request);

            // 运行HTTP层逻辑
            $resp = App::http()->run();
            $respData = $resp->getData();
            $response->header('Content-Type', $resp->getHeader('Content-Type'));
            $response->status($resp->getCode() ?? 200);
            $response->end(is_array($respData) ? json_encode($respData, JSON_UNESCAPED_UNICODE) : $respData);
        });
    }

    protected function setServerData(mixed $request){
        foreach($request->header as $k => $v){
            $_SERVER['HTTP_'.strtoupper($k)] = $v;
        }
        foreach($request->server as $k => $v){
            $_SERVER[strtoupper($k)] = $v;
        }
        $_SERVER['PATH_INFO'] = ltrim($_SERVER['PATH_INFO'], '/');
    }

    public function setRequestData(mixed $request){
        $_GET = $request->get ?? [];
        $_POST = $request->post ?? [];
        $_REQUEST = array_merge($_GET, $_POST);
        $_FILES = $request->files ?? [];
    }
}
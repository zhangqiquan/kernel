<?php
// +----------------------------------------------------------------------
// | flow-course / Swoole.php    [ 2021/11/13 8:47 下午 ]
// +----------------------------------------------------------------------
// | Copyright (c) 2011~2021 zhangqiquan All rights reserved.
// +----------------------------------------------------------------------
// | Author: zhangqiquan <768617998@qq.com>
// +----------------------------------------------------------------------
declare (strict_types=1);


namespace kernel;


class Swoole
{
    /**
     * 当前指令【start|restart|stop|status|reload|help|connections】
     * @var string
     */
    protected $command = 'help';

    /**
     * 进程名称
     * @var bool
     */
    protected $processName = null;

    /**
     * swooleServer配置参数
     * @var array
     */
    protected $config = [
        'server_ip' => '0.0.0.0',
        'server_port' => 9100,
        'server_mode' => SWOOLE_BASE,
        'server_protocol' => 'http',
        'server_socktype' => SWOOLE_SOCK_TCP,
        'server_ssl' => false,
        'server_reuse_port' => false,
        'reactor_num'   => 1,
        'worker_num'    => 1,
        'daemonize' => false,
        'max_request'   => 10000,
        'heartbeat_idle_time' => 10,
        'heartbeat_check_interval' => 5,
    ];

    /**
     * 构造器
     * Swoole constructor.
     * @param array $config
     */
    function __construct(array $config = []){
        $this->config = array_merge($this->config, $config);
    }

    /**
     * 运行入口
     * @param array $param
     */
    public function run(array $param){
        $this->processName = explode('.', $param[0])[0];
        isset($param[1]) ? $this->command = $param[1]  : '';
        isset($param[2]) ? $this->config['daemonize'] = (bool) $param[2] : '';
        $command = $this->command;
        $this->$command();
    }

    /**
     * 启动
     */
    public function start(){
        // 走不通的协议
        $protocol = ucfirst($this->config['server_protocol']);
        $service = '\\kernel\\swoole\\'.$protocol;
        $serv = App::service()->get('\\kernel\\swoole\\'.$protocol)->server($this->config, $this->processName);

        $serv->start(); // 启动
    }

    public function reload(){

    }

    public function restart(){

    }

    public function stop(){

    }

    public function status(){

    }

    public function connections(){

    }

    public function help(){

    }

}
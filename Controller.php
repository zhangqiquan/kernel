<?php
// +----------------------------------------------------------------------
// | flow-course / Controller.php    [ 2021/10/25 3:55 下午 ]
// +----------------------------------------------------------------------
// | Copyright (c) 2011~2021 zhangqiquan All rights reserved.
// +----------------------------------------------------------------------
// | Author: zhangqiquan <768617998@qq.com>
// +----------------------------------------------------------------------
declare (strict_types=1);


namespace kernel;


class Controller
{
    /**
     * 前置中间件
     * @var array
     */
    protected $beforeMiddleware = [];

    /**
     * 后置中间件
     * @var array
     */
    protected $afterMiddleware = [];

    /**
     * 异步中间件【异步执行】
     * @var array
     */
    protected $asyMiddleware = [];

    /**
     * 统一返回
     * @param array $data
     * @param string $msg
     * @param int $code
     * @return array
     */
    public function result(mixed $data = [], $msg = '成功', $code = 0){
        App::response()->contentType('application/json');
        return [
            'errorcode' => $code,
            'errormsg' => $msg,
            'data' => $data,
            'time' => App::request()->time(true), // 请求时间
            'runtime' => microtime(true) - App::beginTime(),
            'use_memory' => round((memory_get_usage() - App::beginMem()) / 1024, 3).'kb', // 用户内存
            'peak_memory' => round(memory_get_peak_usage() / 1024, 3).'kb', // 内存峰值
        ];
    }

    /**
     * 获取前置控制中间件
     */
    public function getBeforeMiddleware(){
        return $this->beforeMiddleware;
    }

    /**
     * 获取后置控制中间件
     */
    public function getAfterMiddleware(){
        return $this->afterMiddleware;
    }

    /**
     * 获取异步控制中间件
     * @return array
     */
    public function getAsyMiddleware(){
        return $this->asyMiddleware;
    }
}
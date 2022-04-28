<?php
// +----------------------------------------------------------------------
// | flow-course / RESTFul.php    [ 2021/10/26 5:51 下午 ]
// +----------------------------------------------------------------------
// | Copyright (c) 2011~2021 zhangqiquan All rights reserved.
// +----------------------------------------------------------------------
// | Author: zhangqiquan <768617998@qq.com>
// +----------------------------------------------------------------------
declare (strict_types=1);


namespace kernel;


class RESTFul
{

    /**
     * 请求对象
     * @var null
     */
    protected $request = null;

    /**
     * service类
     * @var null
     */
    protected $service = null;

    /**
     * service调用方法
     * @var null
     */
    protected $method = null;

    /**
     * 构造器
     * RESTFul constructor.
     * @param Request $request
     * @param mixed|null $service
     */
    public function __construct(Request $request, mixed $service = null)
    {
        $this->request = $request;
        $service = $service ?? str_replace('controller', 'service', $request->controller()).'Service';
        $this->service = $service;
        $this->method = strtolower($request->method());
    }

    /**
     * 请求service
     * @return mixed
     */
    public function requert(){
        $method = $this->method.ucfirst($this->request->action());
        // 如果service 被重写
        if(isset($this->request->menu['service'])){
            if(method_exists($this->request->menu['service'], $method)) return $this->request->menu['service']->$method($this->request);
        }
        // 根据注释验证
        return App::service()->get($this->service)->$method($this->request);
    }
}
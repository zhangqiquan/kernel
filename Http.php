<?php
// +----------------------------------------------------------------------
// | flow-course / Http.php    [ 2021/10/25 9:44 上午 ]
// +----------------------------------------------------------------------
// | Copyright (c) 2011~2021 zhangqiquan All rights reserved.
// +----------------------------------------------------------------------
// | Author: zhangqiquan <768617998@qq.com>
// +----------------------------------------------------------------------
declare (strict_types=1);


namespace kernel;


class Http
{
    /**
     * 当前请求对象
     * @var null
     */
    protected $request = null;

    /**
     * 当前请求的控制器
     * @var null
     */
    protected $controller = null;

    /**
     * 异常类
     * @var array|mixed|string
     */
    protected $handler = Error::class;

    /**
     * 构造器
     * Http constructor.
     */
    public function __construct(){
        if($handler = App::config()->get('app.handler')) $this->handler = $handler;
        set_error_handler([$this->handler, 'render'], E_ALL);// 警告错误
        set_exception_handler([$this->handler, 'exception']); //异常处理函数

        $this->init(); // 初始化操作
    }

    /**
     * 初始化操作
     */
    protected function init(){
        $inis = App::config()->get('ini');
        foreach ($inis as $k => $v){
            ini_set($k, $v);
        }

    }

    /**
     * 执行应用程序
     * @access public
     * @param Request|null $request
     * @return Response
     */
    public function run(): Response
    {

        try {
            $this->request = App::request();
            $response = $this->runWithRequest($this->request);
        } catch (\Throwable $e) {
            // 触发用户错误
            $response = Error::userError($e);
        }
        return $response;
    }

    /**
     * 执行应用逻辑
     * @param Request $request
     * @return Response
     * @throws \Exception
     */
    public function runWithRequest(Request $request){
        App::route()->check(); // 检查路由

        $method     = App::request()->action();
        $this->controller = App::service()->get(App::request()->controller());
        $response = App::response();

        App::middleware()->runBefore($request, $this->controller->getBeforeMiddleware()); // 运行前置中间件

        $controllerData = $this->controller->$method($request); // 执行控制器方法

        // 设定响应
        $response->code(0);
        $response->data($controllerData);

        App::middleware()->runAfter($request, $this->controller->getAfterMiddleware()); // 运行后置中间件【后置可以改变response的值】

        return $response;
    }



    /**
     * HttpEnd
     * @param Response $response
     * @return void
     */
    public function end(Response $response): void
    {
        try {
            $asyMiddleware = $this->controller ? $this->controller->getAsyMiddleware() : [];
            App::middleware()->runAsy($this->request, $asyMiddleware); // 执行异步中间件
            App::log()->write();
        }catch(\Throwable $e){
            $errmsg = '【'.date('Y-m-d H:i:s').'】'.$e->getMessage().PHP_EOL;
            $errmsg .= $e->getFile().' 第'.$e->getLine().'行'.PHP_EOL;
            $errmsg .= $e->getTraceAsString().PHP_EOL.PHP_EOL;
            echo $errmsg;
            echo error_log($errmsg, 3, App::runtimePath().'log'.DIRECTORY_SEPARATOR.'error.log'); // 记录错误日志
        }
        App::log()->clear();
    }


}
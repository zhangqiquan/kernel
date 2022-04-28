<?php
// +----------------------------------------------------------------------
// | flow-course / Route.php    [ 2021/10/25 9:36 上午 ]
// +----------------------------------------------------------------------
// | Copyright (c) 2011~2021 zhangqiquan All rights reserved.
// +----------------------------------------------------------------------
// | Author: zhangqiquan <768617998@qq.com>
// +----------------------------------------------------------------------
declare (strict_types=1);


namespace kernel;


class Route
{
    /**
     * 注册的路由列表（请求类型,uri,方法,中间件）
     * @var array
     */
    protected $registers = [];

    /**
     * REST定义
     * @var array
     */
    protected $rest = [
        'index'  => ['get', '', 'index'],
        'create' => ['get', '/create', 'create'],
        'edit'   => ['get', '/<id>/edit', 'edit'],
        'read'   => ['get', '/<id>', 'read'],
        'save'   => ['post', '', 'save'],
        'update' => ['put', '/<id>', 'update'],
        'delete' => ['delete', '/<id>', 'delete'],
    ];

    /**
     * 注册路由
     * @param array $methods
     * @param string $uri
     * @param string $action
     * @param array $middleware
     */
    public function match(array $methods, string $uri, string $action = null){
        // 带$ 代表结束
        $this->registers[$uri] = [$methods, $uri, $action];
    }

    /**
     * 解析路由 分成成版本号和地址+参数
     * @return array
     */
    protected function parse(){
        $data = [];
        $url = App::request()->pathinfo();
        $version = strstr($url, '/', true);//路由文件版本
        $routeFile = App::routePath() . $version . '.php';
        if(file_exists($routeFile)) require_once $routeFile;
        $data['version'] = $version;

        $urlParam = explode('$', $url);
        $data['uri']     = strstr($urlParam[0], '/');
        if(isset($urlParam[1])){
            $urlParam[1] = explode('/', $urlParam[1]);
            foreach($urlParam[1] as $k => $v){
                if($k % 2 == 0){
                    $_REQUEST[$v] = $urlParam[1][$k+1] ?? null; // 追加全局请求参数
                }
            }
        }
        return $data;
    }

    /**
     * 检查路由追加额外参数
     * @return array
     */
    public function check(){
        $parse = $this->parse(); // 解析url参数
        $route = $this->registers[$parse['uri']] ?? Error::setError('您访问的接口不存在', 404, 404);
        $request = App::request();
        $method = $request->method();
        if(!in_array($method, $route[0])) Error::setError('当前接口不支持'.$method.'请求', 403, 403);
        $actions = explode('@', $route[2]);
        $version = str_replace('.', '_', $parse['version']);
        $controller = '\app\api\\'.$version.'\controller\\'.$actions[0];
        $request->setVersion($version); // 设置请求版本
        $request->setController($controller); // 设置控制器名称
        $request->setAction($actions[1]); // 设置控制器方法
    }

    /**
     * 获取路由
     * @param string $uri
     * @return array|mixed
     */
    public function get(string $uri){
        return $this->registers[$uri] ?? [];
    }

    /**
     * 获取全部路由
     * @return array
     */
    public function getAll(){
        return $this->registers;
    }

    /**
     * 清空路由
     */
    public function clean(){
        $this->registers = [];
    }

}
<?php
// +----------------------------------------------------------------------
// | flow-course / Controller.php    [ 2021/10/29 2:45 下午 ]
// +----------------------------------------------------------------------
// | Copyright (c) 2011~2021 zhangqiquan All rights reserved.
// +----------------------------------------------------------------------
// | Author: zhangqiquan <768617998@qq.com>
// +----------------------------------------------------------------------
declare (strict_types=1);

namespace kernel\console\command\apicreate;

use kernel\App;
use kernel\console\Input;
use kernel\Helper;

class Controller extends Common
{
    protected static $input = null;
    protected static $version = null;

    /**
     * 创建控制器
     * @param Input $input
     */
    public static function create(Input $input){

        self::$input = $input;

        $version = $input->param('version');
        $class = $input->param('class');
        $method = $input->param('method');

        $version = str_replace('.', '_', $version);
        self::$version = $version;
        $classFile = App::appPath().'api'.DIRECTORY_SEPARATOR.$version.DIRECTORY_SEPARATOR.'controller'.DIRECTORY_SEPARATOR.$class.'.php';

        self::check($classFile); // 检查控制器是否存在不存在初始化

        self::createClassMethod($classFile, $input->param('title'));
    }

    /**
     * 获取初始化控制器代码
     * @return string
     */
    protected static function getClassInitCode()
    {
        $class = self::$input->param('class');
        $code = Helper::headComment($class.'控制器');
        $version = self::$version;
        $code .= "
namespace app\api\\{$version}\controller;

use kernel\App;
use kernel\Controller;
use kernel\Request;
use app\middleware\VisitLog;

class $class extends Controller
{
    /**
     * 前置中间件
     * @var array
     */
    protected \$beforeMiddleware = [];

    /**
     * 后置中间件
     * @var array
     */
    protected \$afterMiddleware = [];

    /**
     * 异步中间件
     * @var array
     */
    protected \$asyMiddleware = [
        RequestLog::class
    ];
    
}";
        return $code;
    }

    /**
     * 获取初始化控制器方法代码
     * @return string
     */
    protected static function getClassMethodInitCode(string $title, string $method)
    {
        $group = self::$input->param('groupname');
        $code = "
     /**
     * $title
     * @apiGroup $group
     * @param Request \$request
     * @return array
     */
    public function $method(Request \$request){
        \$data = App::restful(\$request)->requert();
        return \$this->result(\$data);
    }";
        return $code;
    }

    /**
     * 获取需要创建的方法名列表
     * @return array
     */
    protected static function getClassMethods(string $classFile): array
    {
        $method = self::$input->param('method');
        $class = '\\app\\api\\'.self::$version.'\\controller\\'.self::$input->param('class');
        $ref = new \ReflectionClass(new $class());

        $methods = json_decode(json_encode($ref->getMethods()), true);
        if(is_array($methods)) $methods = array_column($methods, 'name');

        $res = [];
        if(!in_array($method, $methods)){
            $res[] = $method;
        }
        return $res;
    }
}
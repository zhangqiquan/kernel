<?php
// +----------------------------------------------------------------------
// | flow-course / Service.php    [ 2021/10/29 2:46 下午 ]
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

class Service extends Common
{
    protected static $input = null;
    protected static $version = null;

    public static function create(Input $input){
        self::$input = $input;

        self::$version = str_replace('.', '_', $input->param('version'));
        $classFile = App::appPath().'api'.DIRECTORY_SEPARATOR.self::$version.DIRECTORY_SEPARATOR.'service'.DIRECTORY_SEPARATOR.$input->param('class').'Service'.'.php';

        self::check($classFile); // 检查控制器是否存在不存在初始化

        self::createClassMethod($classFile, $input->param('title'));
    }

    /**
     * 获取控制器初始化代码
     */
    protected static function getInitClassCode(string $version){
        $class = self::$input->param('class');
        $code = Helper::headComment($class.'Service');
        $code .= "
namespace app\\api\\$version\\service;

use kernel\\Request;
use kernel\\App;

class {$class}Service
{
    
}";
        return $code;
    }

    /**
     * 获取需要创建的方法名列表
     * @param string $classFile
     * @return array
     * @throws \ReflectionException
     */
    protected static function getClassMethods(string $classFile): array
    {
        $class = '\\app\\api\\'.self::$version.'\\service\\'.self::$input->param('class').'Service';
        $ref = new \ReflectionClass(new $class());

        $methods = json_decode(json_encode($ref->getMethods()), true);
        if(is_array($methods)) $methods = array_column($methods, 'name');

        $reqTypes =  self::$input->param('reqtype'); // 请求方法

        $res = [];
        foreach($reqTypes as $v){
            $reqType = $v.ucwords(self::$input->param('method')); // 驼峰命名
            if(!in_array($reqType, $methods)){
                // 创建Service对象RestFul 方法
                $res[] = $reqType;
            }
        }
        return $res;
    }

    /**
     * 获取serviceClass初始化代码
     * @return string
     */
    protected static function getClassInitCode()
    {
        $class = self::$input->param('class');
        $version = self::$version;
        $code = Helper::headComment($class.'Service');
        $code .= "
namespace app\\api\\$version\service;

use kernel\\Request;
use kernel\\App;

class {$class}Service
{
    
}";
        return $code;
    }

    /**
     * 获取需要创建的方法初始化代码
     * @param string $title
     * @param string $method
     * @return mixed|void
     */
    protected static function getClassMethodInitCode(string $title, string $method)
    {
        $curd = '[];';
        if(self::$input->param('table')){
            $curd = 'App::database()->model("'.self::$input->param('table').'")->select(); // 数据模型测试代码';
        }
        $code = "
     /**
     * $title
     * @apiParam {String} Required param 示例参数
     * @apiExplain {json} {\"错误码\":\"错误描述\"}
     * @return array
     */
    public function $method(Request \$request){
        \$data = $curd
        return \$data;
    }";
        return $code;
    }
}
<?php
// +----------------------------------------------------------------------
// | flow-course / Database.php    [ 2021/10/25 9:35 上午 ]
// +----------------------------------------------------------------------
// | Copyright (c) 2011~2021 zhangqiquan All rights reserved.
// +----------------------------------------------------------------------
// | Author: zhangqiquan <768617998@qq.com>
// +----------------------------------------------------------------------
declare (strict_types=1);


namespace kernel;


use think\db\BaseQuery;
use think\facade\Db;

class Database
{
    /**
     * 模型对象实例列表
     * @var Container|Closure
     */
    protected static $models;

    public function __construct(array $config = [])
    {
        $config = array_merge(App::config()->get('database'), $config);
        Db::setConfig($config);
        Db::setLog(App::log());
        Db::setCache(App::cache());
    }

    /**
     * 使用表达式设置数据
     * @param string $value
     * @return mixed
     */
    public static function raw(string $value){
        return Db::raw($value);
    }

    /**
     * 获得查询日志（没有设置日志对象使用）设置了日志对象这里会收不到
     * @access public
     * @param bool $clear 是否清空
     * @return array
     */
    public function getDbLog(bool $clear = false): array
    {
        return App::log()->getLog()['sql'] ?? [];
    }

    /**
     * 获取model
     * @param mixed $name
     * @return Model
     */
    public function model(mixed $name){
        $class = '\\app\\model\\'.ucfirst(Helper::camelize($name)); // 转驼峰命名
        // 如果model存在返回model,否则z
        $model = $this->getModels($class);
        if(!$model){
            $model = Db::name($name);
        }
        return $model;
    }

    /**
     * 原生查询
     * @param string $sql
     * @return mixed
     */
    public function query(string $sql){
        return Db::query($sql);
    }

    /**
     * 执行sql
     * @param string $sql
     * @return mixed
     */
    public function execute(string $sql){
        return Db::execute($sql);
    }

    /**
     * 容器获取注册app类
     * @param string $name
     * @param mixed $value
     */
    private function getModels(mixed $model){
        if (isset(self::$models[$model])) {
            return self::$models[$model];
        }

        if($this->checkFile($model)){
            $obj = new $model();
            return self::$models[$model] = $obj;
        }
        return false;
    }

    /**
     * 检查model文件是否存在
     * @param mixed $model
     * @return bool
     */
    private function checkFile(mixed $model){
        $modelFile = App::rootPath().str_replace('\\', '/', $model).'.php';
        if(!file_exists($modelFile)) return false;
        return true;
    }

    /**
     * 启动一个分布式事务执行【 要确保你的数据表引擎为InnoDB，并且开启XA事务支持。】
     * @param callable $callback
     * @param array $dbs
     * @return void
     */
    public function transactionXa(callable $callback, array $dbs = []){
        return Db::transactionXa($callback, $dbs);
    }

    /**
     * 启动一个事务【当闭包中的代码发生异常会自动回滚】
     * @param callable $callback
     * @return mixed
     */
    public function transaction(callable $callback){
        return Db::transaction($callback);
    }
}
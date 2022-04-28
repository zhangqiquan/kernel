<?php
// +----------------------------------------------------------------------
// | flow-course / Model.php    [ 2021/10/26 11:22 上午 ]
// +----------------------------------------------------------------------
// | Copyright (c) 2011~2021 zhangqiquan All rights reserved.
// +----------------------------------------------------------------------
// | Author: zhangqiquan <768617998@qq.com>
// +----------------------------------------------------------------------
declare (strict_types=1);


namespace kernel;


use kernel\database\SoftDelete;

abstract class Model extends \think\Model
{
    use SoftDelete;
    // 设置当前模型对应的完整数据表名称
    protected $table = '【请在model上配置$table属性对应的表名】';

    // 设置当前模型的数据库连接
    protected $connection = null;

    // 设置当前模型软删除字段
    protected $deleteTime = null;

    // 设置软删除字段的默认值
    protected $defaultSoftDelete = 0;

    /**
     * 构造方法 如果需要动态修改table只能放到这里
     * Model constructor.
     * @param array $data
     */
    public function __construct(array $data = [])
    {
        parent::__construct($data);
    }

    // 模型初始化
    protected static function init()
    {
        //TODO:初始化内容
    }

    // 关联模型自动完成数据获取

}
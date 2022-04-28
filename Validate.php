<?php
// +----------------------------------------------------------------------
// | flow-course / Validate.php    [ 2021/10/25 9:37 上午 ]
// +----------------------------------------------------------------------
// | Copyright (c) 2011~2021 zhangqiquan All rights reserved.
// +----------------------------------------------------------------------
// | Author: zhangqiquan <768617998@qq.com>
// +----------------------------------------------------------------------
declare (strict_types=1);


namespace kernel;


class Validate
{
    /**
     * 验证失败错误信息
     * @var string|array
     */
    protected $error = [];

    /**
     * 是否批量验证
     * @var bool
     */
    protected $batch = false;


    /**
     * 设置批量验证
     * @access public
     * @param bool $batch 是否批量验证
     *
     * @return $this
     */
    public function batch(bool $batch = true)
    {
        $this->batch = $batch;

        return $this;
    }

    /**
     * 数据自动验证
     * @access public
     * @param array $data  数据
     * @param array $rules 验证规则
     * @return mixed
     */
    public function check(array $data, array $rules = []): mixed
    {
        foreach($rules as $k => $v){
            if(isset($data[$k]) == false || $data[$k] == "") Error::setError($k.'不能为空', 403);
        }
        return $data;
    }

}
<?php
// +----------------------------------------------------------------------
// | flow-course / Doc.php    [ 2021/10/29 2:47 下午 ]
// +----------------------------------------------------------------------
// | Copyright (c) 2011~2021 zhangqiquan All rights reserved.
// +----------------------------------------------------------------------
// | Author: zhangqiquan <768617998@qq.com>
// +----------------------------------------------------------------------
declare (strict_types=1);


namespace kernel\console\command\apicreate;


use kernel\App;
use kernel\console\Input;
use kernel\Service\ApiDocService;

class Doc
{
    /**
     * 创建api文档
     * @param Input $input
     */
    public static function create(Input $input){
        $action = $input->param('class').'@'.$input->param('method');
        $service = App::service()->get(ApiDocService::class);
        $apiDoc = $service->get($input->param('version'), $input->param('uri'));
        // 如果apiDoc 不存在就创建
        if(!$apiDoc){
            $service->create(
                $input->param('version'),
                $input->param('uri'),
                $input->param('reqtype'),
                $action,
                $input->param('title'),
                $input->param('groupname'),
                [],
                '',
                '[]',
                '[]',
                '[]',
            );
            echo PHP_EOL.'apiDoc构造成功'.PHP_EOL;
        }
    }

}
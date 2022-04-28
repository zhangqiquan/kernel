<?php
// +----------------------------------------------------------------------
// | flow-course / ApiManager.php    [ 2021/10/28 6:16 下午 ]
// +----------------------------------------------------------------------
// | Copyright (c) 2011~2021 zhangqiquan All rights reserved.
// +----------------------------------------------------------------------
// | Author: zhangqiquan <768617998@qq.com>
// +----------------------------------------------------------------------
declare (strict_types=1);


namespace kernel\console\command;


use kernel\App;
use kernel\console\Command;
use kernel\console\Input;
use kernel\console\Output;
use kernel\Error;
use kernel\service\ApiDoc;
use kernel\Service\ApiDocService;

class ApiDocManager extends Command
{
    protected $service = null;
    protected $output = null;

    public function configure()
    {
        $this->setTitle('apidoc管理')->setDescription('支持对现有的文档数据进行(修改/删除/清空/回滚/生成等功能)');
        // TODO: Implement configure() method.
    }

    public function execute(Input $input, Output $output)
    {
        $this->output = $output;
        $this->service = App::service()->get(ApiDocService::class);
        $method = $input->input('method', '[get,edit,delete,clean,rollback,generate]', 'get');
        $this->$method($input, $output);
    }

    /**
     * 查询并输出
     * @param Input $input
     * @param Output $output
     */
    protected function get(Input $input, Output $output){
        $version = $input->input('version', 'api版本', '');
        $uri = $input->input('uri', 'api地址', '');
        $data = $this->service->get($version, $uri);
        var_export($data);

        echo PHP_EOL.PHP_EOL;
    }

    /**
     * 清空文档
     * @param Input $input
     * @param Output $output
     */
    protected function clean(Input $input, Output $output){
        $data = $this->service->clearAll();
        $this->service->setSeq(0);
        $output->output('清空成功', 'success');
    }

    /**
     * 修改
     * @param Input $input
     * @param Output $output
     */
    protected function edit(Input $input, Output $output){
        $data = $input->all();

        $version = $input->input('version', '请输入version');
        $uri = $input->input('uri', '请输入uri');

        $version ?? Error::setError('--version 不能为空');
        $uri ?? Error::setError('--uri 不能为空');

        $data = $input->input('data', '更新数据【仅支持json格式】');

        if($this->service->edit($version, $uri, json_decode($data, true))){
            return $output->output('修改成功', 'success');
        }
        $output->output('修改失败', 'error');
    }

    /**
     * 删除
     * @param Input $input
     * @param Output $output
     */
    protected function delete(Input $input, Output $output){
        $version = $input->input('version', '删除的版本');
        $uri = $input->input('uri', '删除的uri');

        $version ?? Error::setError('--version 不能为空');
        $uri ?? Error::setError('--uri 不能为空');

        $this->service->delete($version, $uri);

        $lastId = 0;
        $data = $this->service->get();
        if($data) $lastId = $data[array_key_last($data)]['id'];

        $this->service->setSeq((int)$lastId);
        $output->output('删除成功', 'success');
    }

    /**
     * 仅回滚apidDoc,提示请手动删除 控制器和 service的方法
     * @param Input $input
     * @param Output $output
     */
    protected function rollback(Input $input, Output $output){
        $data = $this->service->get();
        if($data){
            $data = $data[array_key_last($data)];
            $this->service->delete($data['version'], $data['uri']);
            $lastId = 0;
            $apidata = $this->service->get();
            if($apidata) $lastId = $apidata[array_key_last($apidata)]['id'];
            $this->service->setSeq((int)$lastId);
            $output->output('apiDoc回滚成功,请手动清理'.$data['version'].$data['action'].'和对应service '.$data['method'].'的代码', 'success');
            $output->output('请手动清理'.$data['version'].'下'.$data['action'].'以及service '.$data['method'].'的代码', 'info');
        }
    }

    /**
     * 根据路由生成文档
     * @param Input $input
     * @param Output $output
     */
    public function generate(Input $input, Output $output){
        $all = scandir(App::routePath());
        foreach($all as $v){
            $infos = pathinfo($v);
            if($infos['extension'] == 'php'){
                include App::routePath().$v;
                $this->updateAllApiDoc($infos['filename'], App::route()->getAll());
                App::route()->clean(); // 清理掉
            }
        }
    }

    protected $runNun = 1;

    protected function updateAllApiDoc(string $version, array $data){
        $version_ = str_replace('.', '_' , $version);
        foreach($data as $v){
            echo '###'.$this->runNun++;
            $apiDocData = [];
            $requestTypes = $v[0];
            $action = $v[2];
            $uri = $v[1];
            try {
                $apiDocData['version'] = $version;
                $apiDocData['uri'] = $uri;
                $apiDocData['action'] = $action;
                $apiDocData['method'] = json_encode($requestTypes, JSON_UNESCAPED_SLASHES + JSON_UNESCAPED_UNICODE);
                $apiDocData['title'] = $this->service->getApiDocTitle($version_, $action); // 拿到apiDoc标题
                $apiDocData['groupname'] = $this->service->getApiDocGroup($version_, $action); // 拿到apiDoc分组
                // 去除所有空格
                if($apiDocData['groupname'] == false) Error::setError('@apiGroup 分组名称未定义'.$version.'/'.$action);
                foreach($requestTypes as $vv){
                    $apiDocData['param'][$vv] = $this->service->getApiDocParam($version_, $action, strtolower($vv)); // 拿到文档参数
                    $apiDocData['explain'][$vv] = $this->service->getApiDocExplain($version_, $action, strtolower($vv)); // 拿到错误代码解析
                }
                $apiDocData['param'] = json_encode($apiDocData['param'], JSON_UNESCAPED_SLASHES + JSON_UNESCAPED_UNICODE);
                $apiDocData['explain'] = json_encode($apiDocData['explain'], JSON_UNESCAPED_SLASHES + JSON_UNESCAPED_UNICODE);
                //print_r($apiDocData);
                $this->service->maintain($apiDocData); // 更新维护文档
                $this->output->output(PHP_EOL.'【成功】'.$version_.'/'.$uri, 'success');
            }catch (\Throwable $e){
                echo "【错误】";
                $this->output->exception($e);
            }
            echo PHP_EOL.'======================================================================================'.PHP_EOL;
        }
    }

    protected function test(Input $input, Output $output){
        $str = 'Test'.time();
        $this->service->create('vt1.0.0', $str, ['GET','POST'], $str.'@index', $str, '测试文档', [], $str.'详情', '{}', '{}', '{}');
    }
}
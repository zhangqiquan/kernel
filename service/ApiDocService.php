<?php
// +----------------------------------------------------------------------
// | flow-course / ApiDocService.php    [ 2021/10/29 10:24 上午 ]
// +----------------------------------------------------------------------
// | Copyright (c) 2011~2021 zhangqiquan All rights reserved.
// +----------------------------------------------------------------------
// | Author: zhangqiquan <768617998@qq.com>
// +----------------------------------------------------------------------
declare (strict_types=1);

namespace kernel\service;

use kernel\App;
use kernel\Error;
use think\facade\Db;

class ApiDocService
{
    protected $docName = 'apidoc';
    /**
     * ApiDoc constructor.
     * @param array $data
     */
    public function __construct(array $data = [])
    {
        Db::setConfig($this->config());

        $this->tableExist();// 检查表是否存在
    }

    /**
     * 获取数据库实例
     * @return Db
     */
    public function db(){
        return Db::name($this->docName);
    }

    /**
     * 添加文档
     * @param string $version
     * @param string $uri
     * @param array $method
     * @param string $action
     * @param string $title
     * @param string $groupname
     * @param array $param
     * @param string $detail
     * @param string $success
     * @param string $failure
     * @param string $explain
     * @return int|string
     */
    public function create(string $version, string $uri, array $method, string $action, string $title, string $groupname, array $param, string $detail, string $success, string $failure, string $explain){
        return $this->db()->insert(['version' => $version, 'uri' => $uri, 'method' => json_encode($method), 'action' => $action,  'title' => $title, 'groupname' => $groupname, 'param' => json_encode($param, JSON_UNESCAPED_SLASHES + JSON_UNESCAPED_UNICODE), 'detail' => $detail, 'success' => $success, 'failure' => $failure, 'explain' => $explain]);
    }

    /**
     * 修改文档
     * @param string $version
     * @param string $uri
     * @param array $data
     * @return int
     * @throws \think\db\exception\DbException
     */
    public function edit(string $version, string $uri, array $data){
        return $this->db()->where(['version' => $version, 'uri' => $uri])->update($data);
    }

    /**
     * 查询
     * @param string|null $version
     * @param string|null $uri
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function get(?string $version = null, ?string $uri = null){
        $where = [];
        if($version) $where['version'] = $version;
        if($uri) $where['uri'] = $uri;

        return $this->db()->where($where)->order('id', 'asc')->select()->toArray();
    }

    /**
     * 删除文档
     * @param string $version
     * @param string $uri
     */
    public function delete(string $version, string $uri){
        return $this->db()->where(['version' => $version, 'uri' => $uri])->delete();
    }


    /**
     * 获取数据库配置
     * @return array
     */
    private function config(){
        return [
            'default'         => 'apidoc',
            'connections'     => [
                'apidoc' => [
                    'type'        => 'sqlite',
                    'database'       => App::runtimePath() .'/apidoc.db', // api文档数据文件保存地址
                    'prefix'         => '',
                    'debug'          => true
                ]
            ]
        ];
    }


    /**
     * 表检查 不存在就创建
     */
    protected function tableExist(){
        try {
            $this->db()->limit(1)->select();
        }catch (\Throwable $e){
            if($e->getCode() == 10501) $this->createTable(); // 如果数据库不存在
        }
    }

    /**
     * 创建文档表
     */
    public function createTable(){
        $sql = 'CREATE TABLE IF NOT EXISTS "'.$this->docName.'" ( "id" INTEGER NOT NULL PRIMARY KEY AUTOINCREMENT, "version" TEXT, "action" TEXT, "uri" TEXT,  "method" TEXT,
    "title" TEXT, "detail" TEXT,  "groupname" TEXT, "param" TEXT, "success" TEXT, "failure" TEXT, "explain" TEXT );
    UPDATE "main"."sqlite_sequence" SET seq = 1 WHERE name = \''.$this->docName.'\';
    PRAGMA foreign_keys = true;';
        Db::execute($sql);
    }

    /**
     * 重新设置步值
     * @param int $seq
     */
    public function setSeq(int $seq){
        Db::execute("update sqlite_sequence SET seq = {$seq} where name ='{$this->docName}';");
    }

    /**
     * 清空所有文档数据
     */
    public function clearAll(){
        Db::execute("delete from {$this->docName};update sqlite_sequence SET seq = 1 where name ='{$this->docName}';");
    }

    /**
     * apiDoc运行入口
     * @return \kernel\Response
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public static function run(){
        $response = App::response();
        $response->contentType('application/json');
        try {
            $method = App::request()->param('method', 'get');
            $service = new self();
            $request = App::request();
            switch ($method){
                case 'getDetail':
                    $service->login(App::aes()->decrypt($_COOKIE['auth'] ?? ''));
                    $data =  $service->getDetail($request->param('version'), $request->param('uri'));
                    break;
                case 'login':
                    $data =  $service->login($request->param('pwd'));
                    break;
                case 'updateDoc':
                    $service->login(App::aes()->decrypt($_COOKIE['auth'] ?? ''));
                    $data = $service->updateDoc($request->param('version'), $request->param('uri'), $request->param('request_method'), $request->param('http_code'), $request->param('result'));
                    break;
                default :
                    $service->login(App::aes()->decrypt($_COOKIE['auth'] ?? ''));
                    $data =  $service->getTab();
            }
            $response->code(0);
            $response->data($data);
        }catch (\Throwable $e){
            // 触发用户错误
            $response = Error::userError($e);
        }
        return $response;
    }

    /**
     * 登录appiDoc
     * @param $pwd
     */
    public function login(?string $pwd){
        if($pwd == 'zhangqiquan'){
            // 使用对称加密 有效期24小时
            setcookie('auth', App::aes()->encrypt($pwd), time()+3600*24*7, '/');
        }else{
            Error::setError('请授权访问',403);
        }
        return [];
    }
    /**
     * 获取文档列表
     * @return array|\think\Collection|Db[]
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getTab(){
        $subQuery = $this->db()->field('id,version,uri,title,groupname')->order('id', 'desc')->buildSql();
        $data = Db::table($subQuery . ' a')->order('id', 'asc')->select()->toArray();
        //print_r($data);die;
        $uniqueData = [];
        foreach($data as $k => $v){
            $uniqueData[$v['uri']] = $v;
        }
        $data = array_values($uniqueData);
        $groups = array_values(array_unique(array_column($data, 'groupname')));

        $result = [];

        foreach($groups as $k => $v){
            $result[$k]['title'] = $v;
            foreach($data as $kk => $vv){
                if($vv['groupname'] == $v){
                    $result[$k]['apis'][] = [
                        'uri' => $vv['uri'],
                        'title' => $vv['title'],
                        'version' => $vv['version'],
                    ];
                    unset($data[$kk]);
                }
            }
        }
        return $result;
    }

    /**
     * 获取文档明细
     * @param string $version
     * @param string $uri
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getDetail(string $version, string $uri){
        $data = $this->get($version, $uri);
        if($data){
            $data = $data[0];
            $data['method'] = json_decode(strtoupper($data['method'] ?? '[]'), true);
            $data['param'] = json_decode($data['param'] ?? '[]', true);
            $data['success'] = json_decode($data['success'] ?? '[]', true);
            $data['failure'] = json_decode($data['failure'] ?? '[]', true);
            $data['explain'] = json_decode($data['explain'] ?? '[]', true);
        }

        return [
            'data' => $data,
            'versions' => $this->db()->where(['uri' => $uri])->field('version')->order('id', 'desc')->column('version'),
        ];
    }

    /**
     * 更新文档
     * @param string $version
     * @param string $uri
     * @param string $httpCode
     * @param string $result
     * @throws \think\db\exception\DbException
     */
    public function updateDoc(string $version, string $uri, string $method, string $httpCode, string $result){
        $resJson = json_decode($result, true);
        if($resJson) $result = $resJson;
        $data = [];
        if(App::request()->server('SERVER_ADDR') == '127.0.0.1'){
            $row = $this->get($version, $uri);
            if($row){
                $row = $row[0];
                if($httpCode > 399){
                    $failure = json_decode($row['failure'] ?? '[]', true);
                    $failure = is_array($failure) ?  $failure : [];
                    $failure[$method] = $result;
                    $data = ['failure' => json_encode($failure, JSON_UNESCAPED_SLASHES + JSON_UNESCAPED_UNICODE)];
                }else{
                    $success = json_decode($row['success'] ?? '[]', true);
                    $success = is_array($success) ? $success : [];
                    $success[$method] = $result;
                    $data = ['success' => json_encode($success, JSON_UNESCAPED_SLASHES + JSON_UNESCAPED_UNICODE)];
                }
                $ok = $this->db()->where(['version' => $version, 'uri' => $uri])->update($data);
                if($ok == false) Error::setError('文档更新失败');
            }
        }
        return $data;
    }

    /**
     * 维护文档 不存在就创建 否则更新
     * @param $data
     */
    public function maintain(array $data){
        if($this->get($data['version'], $data['uri'])){
            // 更新
            $this->db()->where(['version' => $data['version'], 'uri' => $data['uri']])->update($data);
        }else{
            // 添加
            $this->db()->insert($data);
        }
    }

    /**
     * 获取ApiDoc标题
     * @param $class
     * @param $method
     * @return array|string|string[]|null
     * @throws \ReflectionException
     */
    public function getApiDocTitle(string $version, string $action){
        echo PHP_EOL.'#获取'.$version.'/'.$action.'的apiDoc标题#';
        $action = explode('@', $action);
        $class = '\\app\\api\\'.$version.'\\controller\\'.$action[0];
        $rc = new \ReflectionClass($class);
        $rc = $rc->getMethod($action[1]);
        $comment = $rc->getDocComment();
        unset($rc);
        unset($rc);
        if($comment){
            $arr = explode("\n", $comment);
            if(isset($arr[1])){
                return str_replace('*', '', preg_replace('/\s+/',  '', $arr[1]));
            }
        }
    }

    /**
     * 获取ApiDoc分组
     * @param $class
     * @param $method
     * @return array|string|string[]|null
     * @throws \ReflectionException
     */
    public function getApiDocGroup(string $version, string $action){
        echo PHP_EOL.'#获取'.$version.'/'.$action.'的apiDoc分组#';
        $action = explode('@', $action);
        $class = '\\app\\api\\'.$version.'\\controller\\'.$action[0];
        $rc = new \ReflectionClass($class);

        $rc = $rc->getMethod($action[1]);
        $comment = $rc->getDocComment();
        unset($rc);
        if($comment){
            $arr = explode("\n", $comment);
            foreach ($arr as $k => $v){
                if(strpos($v, '@apiGroup')){
                    $rows = array_values(array_filter(explode(' ', str_replace(["\n", "\t", "\r"], '', $v))));
                    unset($rows[0],$rows[1]);
                    return str_replace(' ', '', implode(' ', $rows));
                }
            }
        }
    }

    /**
     * 获取ApiDoc参数
     * @param string $version
     * @param string $action
     * @param string $requestType
     * @return array
     * @throws \ReflectionException
     */
    public function getApiDocParam(string $version, string $action, string $requestType){
        echo PHP_EOL.'#获取'.$version.'/'.$action.'的service的'.$requestType.'参数#';
        $action = explode('@', $action);
        $class = '\\app\\api\\'.$version.'\\service\\'.$action[0].'Service';
        $rc = new \ReflectionClass($class);

        $rc = $rc->getMethod($requestType.ucwords($action[1]));
        $comment = $rc->getDocComment();
        unset($rc);
        $data = [];
        if($comment){
            $arr = explode("\n", $comment);
            foreach ($arr as $k => $v){
                if(strpos($v, 'apiParam')){
                    $rows = array_values(array_filter(explode(' ', str_replace(["\n", "\t", "\r"], '', $v))));
                    $param = [
                        'field' => $rows[4],
                        'type' => $rows[2],
                        'verify' => $rows[3],
                        'description' => ''
                    ];
                    unset($rows[0],$rows[1],$rows[2],$rows[3],$rows[4]);
                    $param['description'] = implode(' ', $rows);
                    $data[] = $param;
                }
            }
        }
        return $data;
    }

    /**
     * 获取apiDoc错误解释
     * @param string $version
     * @param string $action
     * @param string $requestType
     * @return array|string
     * @throws \ReflectionException
     */
    public function getApiDocExplain(string $version, string $action, string $requestType){
        echo PHP_EOL.'#获取'.$version.'/'.$action.'的service的'.$requestType.'异常解释#';
        $action = explode('@', $action);
        $class = '\\app\\api\\'.$version.'\\service\\'.$action[0].'Service';
        $rc = new \ReflectionClass($class);
        $rc = $rc->getMethod($requestType.ucwords($action[1]));
        $comment = $rc->getDocComment();
        unset($rc);
        if($comment){
            $arr = explode("\n", $comment);
            foreach ($arr as $k => $v){
                if(strpos($v, 'apiExplain')){
                    $rows = array_values(array_filter(explode(' ', str_replace(["\n", "\t", "\r"], '', $v))));
                    unset($rows[0],$rows[1],$rows[2]);
                    return implode(' ', $rows);
                }
            }
        }
    }

    public static function __callStatic($method, $arg){
        $msg = __CLASS__.'@'.$method.'()  method does not exist ';
        throw new \Exception($msg, 404);
    }

    public function __call($method, $arg){
        $msg = __CLASS__.'@'.$method.'()  method does not exist ';
        throw new \Exception($msg, 404);
    }


}
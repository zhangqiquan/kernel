# zhanshop框架核心

本系统采用RESTFUL应用程序的设计风格开发，返回结构为JSON格式， 所有资源都共享统一的接口，以便在客户端和服务器之间传输状态。使用的是标准的 HTTP 方法，比如 GET、PUT、POST 和 DELETE。
注意: POST请求参数中添加 _method = 方法名 可以伪造请求方法。

## 主要新特性

* 采用`PHP8`,php版本必须大于8.0
* 支持更多的`PSR`规范
* 系统服务注入支持
* 数据库操作底层使用think-orm
* 内部功能中间件化
* 更强大的控制台
* 对IDE更加友好

## 安装

~~~
composer require zhanshop/kernel

~~~

## 目录结构
~~~
├─app 应用目录
│  ├─api api目录
│  │  ├─v1_0_0 api版本
│  │  │  ├─controller api控制器
│  │  │  │  ├─Index.php 
│  │  │  ├─service api服务
│  │  │  │  ├─IndexService.php
│  │  ├─v2_0_0
│  ├─console 控制台目录
│  ├─library 封装library
│  ├─middleware 控制器中间件
│  ├─model 数据库模型
│  ├─provide 服务提供者
├─cmd.php 控制台程序入口
├─composer.json
├─config 配置目录
├─public 网站目录
├─route 路由
├─runtime 运行时
├─vendor 框架目录及composer包

~~~


#使用方法

###1.生成路由
~~~
php cmd.php api:create // 使用命令生成路由
php cmd.php apidoc:manager // 当代码和注释发生变更使用该命令进行 修改/删除/清空/回滚/生成
对框架生成的service进行业务开发即可
~~~

###2.数据模型

模型定义

namespace app\model;

~~~
<?php
namespace app\model;
use kernel\Model;

class Abc extends Model
{
    // 设置当前模型的对应数据表
    protected $table = '对应的表名';

    // 设置当前模型的数据库连接
    protected $connection = '连接配置名称';

    protected $pk = '主键id';

}
~~~
~~~
    使用案例
    App::database()->model('表名')->where(['id' => 123])->find();  // 查询单个数据
    App::database()->model('表名')->where(['a' => 123])->finder(); // 分页查询
    App::database()->model('表名')->save([...]);  // 新增数据
    App::database()->model('表名')->where(['id' => 123])->delete(); // 删除
    
    App::database()->model('user_play_log')->db()->getConnection()->query(...) // 使用当前模型连接执行原生sql查询
    
    事务 当闭包中的代码发生异常会自动回滚
    App::database()->transaction(function(){
        ....
    });
    分布式事务
    App::database()->transactionXa(function(){
        ....
    });
    
    模型调用使用App::database()->model('表名')，更多数据模型操作可详见 https://www.kancloud.cn/manual/think-orm/1258003
~~~

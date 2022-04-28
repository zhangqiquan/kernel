<?php
// +----------------------------------------------------------------------
// | flow-course / App类    [ 2021/10/22 4:45 下午 ]
// +----------------------------------------------------------------------
// | Copyright (c) 2011~2021 zhangqiquan All rights reserved.
// +----------------------------------------------------------------------
// | Author: zhangqiquan <768617998@qq.com>
// +----------------------------------------------------------------------
declare (strict_types=1);

namespace kernel;
/**
 * @method static Http http()
 * @method static Request request()
 * @method static Response response()
 * @method static Controller controller()
 * @method static Config config()
 * @method static Cache cache()
 * @method static Log log()
 * @method static Error error()
 * @method static Validate validate()
 * @method static Route route()
 * @method static Env env()
 * @method static RESTFul restful()
 * @method static Service service()
 * @method static Database database()
 * @method static Aes aes()
 * @method static Middleware middleware()
 * @method static Console console()
 * @method static HttpClient httpclient()
 * @method static Swoole swoole()
 * @method static Curl curl()
 */
class App extends Container
{
    /**
     * 应用调试模式
     * @var bool
     */
    protected static $appDebug = false;

    /**
     * 应用开始时间
     * @var float
     */
    protected static $beginTime;

    /**
     * 应用内存初始占用
     * @var integer
     */
    protected static $beginMem;
    /**
     * 应用根目录
     * @var string
     */
    protected static $rootPath = '';

    /**
     * 框架目录
     * @var string
     */
    protected static $kernelPath = '';

    /**
     * 应用目录
     * @var string
     */
    protected static $appPath = '';

    /**
     * Runtime目录
     * @var string
     */
    protected static $runtimePath = '';

    /**
     * 路由定义目录
     * @var string
     */
    protected static $routePath = '';
    /**
     * app注册,其他的也需要注册,service,lib都可以搞一套注册 使用的时候注册
     * 应用初始化器(从app容器调用的类全部是单例)
     * @var array
     */
    protected $registers = [
        'error'           => Error::class,
        'env'             => Env::class,
        'config'          => Config::class,
        'cache'           => Cache::class,
        'event'           => Event::class,
        'log'             => Log::class,
        'request'         => Request::class,
        'response'        => Response::class,
        'route'           => Route::class,
        'validate'        => Validate::class,
        'database'        => Database::class,
        'http'            => Http::class,
        'console'         => Console::class,
        'controller'      => Controller::class,
        'service'         => Service::class,
        'restful'         => RESTFul::class,
        'middleware'      => Middleware::class,
        'swoole'          => Swoole::class,
        'aes'             => Aes::class,
        'httpclient'      => HttpClient::class,
        'curl'            => Curl::class
    ];

    /**
     * 架构方法
     * App constructor.
     * @param $rootPath 项目根目录
     */
    public function __construct(string $rootPath){

        self::$beginTime = microtime(true); // 应用开始时间
        self::$beginMem  = memory_get_usage(); // 应用开始内存

        self::$kernelPath   = __DIR__. DIRECTORY_SEPARATOR;
        self::$rootPath     = $rootPath . DIRECTORY_SEPARATOR;
        self::$appPath      = self::$rootPath . 'app' . DIRECTORY_SEPARATOR;
        self::$runtimePath  = self::$rootPath . 'runtime' . DIRECTORY_SEPARATOR;
        self::$routePath    = self::$rootPath . 'route' . DIRECTORY_SEPARATOR;
        self::$instance     = $this; // 容器自身
    }

    /**
     * 获取框架目录地址
     * @return string
     */
    public static function kernelPath(){
        return self::$kernelPath;
    }

    /**
     * 获取项目跟目录地址
     * @return string
     */
    public static function rootPath(){
        return self::$rootPath;
    }

    /**
     * 获取app跟目录地址
     * @return string
     */
    public static function appPath(){
        return self::$appPath;
    }

    /**
     * 获取运行目录地址
     * @return string
     */
    public static function runtimePath(){
        return self::$runtimePath;
    }

    /**
     * 获取路由目录地址
     * @return string
     */
    public static function routePath(){
        return self::$routePath;
    }

    /**
     * 获取app调试状态
     * @return string
     */
    public static function appDebug(){
        return self::$appDebug;
    }

    /**
     * 获取框架开始时间
     * @return string
     */
    public static function beginTime(){
        return self::$beginTime;
    }

    /**
     * 获取框架开始内存
     * @return string
     */
    public static function beginMem(){
        return self::$beginMem;
    }

}
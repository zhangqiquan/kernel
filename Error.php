<?php
// +----------------------------------------------------------------------
// | flow-course / Error类    [ 2021/10/22 5:54 下午 ]
// +-------------------------------------------------------------------------------------------------------
// | Copyright (c) 2011~2021 zhangqiquan All rights reserved.
// +----------------------------------------------------------------------
// | Author: zhangqiquan <768617998@qq.com>
// +----------------------------------------------------------------------
declare (strict_types=1);


namespace kernel;


class Error
{
    // http错误码
    public static $httpCode = 500;

    /**
     * 设置错误信息
     * @param int $code
     * @param string $msg
     */
    public static function setError(string $msg, int $code = 500, int $httpCode = 419){
        self::$httpCode = $httpCode;
        throw new \Exception($msg, $code);
    }

    /**
     * 系统异常处理
     * @param int $errno
     * @param string $errstr
     * @param string $errfile
     * @param string $errline
     */
    public static function render(int $errno,  $errstr,  $errfile,  $errline){
        self::setError($errstr.'; errfile:'.$errfile.'; errline:'.$errline, 500, 500);
    }

    /**
     * 用户自定义的异常处理
     * @param \Throwable $exception
     */
    public static function exception(\Throwable $exception){
        App::response()->contentType('application/json');
        $data = App::controller()->result(self::resultException(self::$httpCode, $exception), $exception->getMessage(), $exception->getCode());
        // 设定响应code
        $response = App::response();
        $response->code(self::$httpCode);
        // 设定响应数据
        $response->data($data);
        return $response;
    }
    /**
     * 触发用户错误 返回响应对象
     * @param \Throwable $exception
     * @return Response
     */
    public static function userError(\Throwable $exception) :Response{
        $HandlerClass = Error::class;
        if($handler = App::config()->get('app.handler')) $HandlerClass = $handler;
        return $HandlerClass::exception($exception);
    }

    /**
     *
     * @param $code
     * @param \Throwable $exception
     * @return array
     */
    public static function resultException(int $httpCode, \Throwable $exception){
        $result = [];
        if($httpCode > 419){
            $result['errfile'] = $exception->getFile();
            $result['errline'] = $exception->getLine();
            $result['trace']   = $exception->getTrace();
        }
        return $result;
    }
}
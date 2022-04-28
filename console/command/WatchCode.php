<?php

namespace kernel\console\command;

use kernel\console\Command;
use kernel\console\Input;
use kernel\console\Output;

class WatchCode extends Command
{
    /**
     * 排除目录
     * @var string[]
     */
    public static $excludeDir = ['vendor', 'runtime', 'public'];

    protected $hashes = [];

    /**
     * 监视的文件后缀
     * @var string
     */
    public static $watchExt = 'php,env';

    public function configure()
    {
        $this->setTitle('监视代码变化')->setDescription('监视指定目录的代码文件变化');
    }

    public function execute(Input $input, Output $output)
    {
        $path = $input->param('path', App::rootPath());
        $hashes = $this->initState($path); // 初始化文件状态

        while (true){
            $isChange = $this->onChange($path);
            if($isChange){
                $output->output('重新载入', 'info');
                $this->execute($input, $output);
                break;
            }
            sleep(2);
        }
    }

    public function onChange($path) : bool{
        $files = $this->getFiles($path);
        $newHashes = array_combine($files, array_map([$this, 'fileHash'], $files));
        foreach($newHashes as $k => $v){
            if(isset($this->hashes[$k]) == false){
                echo '文件新增:'.$k.PHP_EOL;
                // 有新增文件
                return true;
            }elseif (isset($this->hashes[$k]) && $this->hashes[$k] != $v){
                echo '文件发生变化:'.$k.PHP_EOL;
                // 文件发生变化
                return true;
            }
        }

        foreach($this->hashes as $k => $v){
            // 如果老的文件不在新的中被删除了
            if(isset($newHashes[$k]) == false){
                echo '文件删除:'.$k.PHP_EOL;
                return true;
            }
        }
        // 如果新增 或者 变化
        return false;
    }

    /**
     * 初始化目录文件状态
     * @param string $path
     * @return void
     */
    protected function initState(string $path){
        $files = $this->getFiles($path);
        $this->hashes = array_combine($files, array_map([$this, 'fileHash'], $files));
        $count = count($this->hashes);
        echo  "监视 $count 个文件..." . PHP_EOL;
        return $this->hashes;
    }

    /**
     * 获取所有文件
     * @param string $path
     * @return array
     */
    protected function getFiles(string $path){
        $directory = new \RecursiveDirectoryIterator($path);
        $filter = new Filter($directory);
        $iterator = new \RecursiveIteratorIterator($filter);
        return array_map(function ($fileInfo) {
            return $fileInfo->getPathname();
        }, iterator_to_array($iterator));

    }

    /**
     * 获取文件hash值
     * @param string $pathname
     * @return string
     */
    protected function fileHash(string $pathname): string
    {
        $contents = file_get_contents($pathname);
        if (false === $contents) {
            return 'deleted';
        }
        return md5($contents);
    }
}

class Filter extends \RecursiveFilterIterator
{
    public function accept() :bool
    {
        if ($this->current()->isDir()) {
            if (preg_match('/^\./', $this->current()->getFilename())) {
                return false;
            }
            return !in_array($this->current()->getFilename(), WatchCode::$excludeDir);
        }
        $list = array_map(function (string $item): string {
            return "\.$item";
        }, explode(',', WatchCode::$watchExt));
        $list = implode('|', $list);
        $int = preg_match("/($list)$/", $this->current()->getFilename());
        return boolval($int);
    }
}
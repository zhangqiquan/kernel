<?php
declare (strict_types = 1);
namespace kernel\database\connector;

use PDO;
use think\db\BaseQuery;
use think\db\PDOConnection;

class Mysql extends PDOConnection
{

    /**
     * 获取当前连接器类对应的Builder类
     * @access public
     * @return string
     */
    public function getBuilderClass(): string
    {
        return '\\think\\db\\builder\\Mysql';
        //return $this->getConfig('builder') ?: '\\think\\db\\builder\\' . 'Mysql');
    }
    
    /**
     * 解析pdo连接的dsn信息
     * @access protected
     * @param  array $config 连接信息
     * @return string
     */
    protected function parseDsn(array $config): string
    {
        if (!empty($config['socket'])) {
            $dsn = 'mysql:unix_socket=' . $config['socket'];
        } elseif (!empty($config['hostport'])) {
            $dsn = 'mysql:host=' . $config['hostname'] . ';port=' . $config['hostport'];
        } else {
            $dsn = 'mysql:host=' . $config['hostname'];
        }
        $dsn .= ';dbname=' . $config['database'];
        
        if (!empty($config['charset'])) {
            $dsn .= ';charset=' . $config['charset'];
        }
        
        return $dsn;
    }
    
    /**
     * 取得数据表的字段信息
     * @access public
     * @param  string $tableName
     * @return array
     */
    public function getFields(string $tableName): array
    {
        [$tableName] = explode(' ', $tableName);
        
        if (false === strpos($tableName, '`')) {
            if (strpos($tableName, '.')) {
                $tableName = str_replace('.', '`.`', $tableName);
            }
            $tableName = '`' . $tableName . '`';
        }
        
        $sql    = 'SHOW FULL COLUMNS FROM ' . $tableName;
        $pdo    = $this->getPDOStatement($sql);
        $result = $pdo->fetchAll(PDO::FETCH_ASSOC);
        $info   = [];
        
        if (!empty($result)) {
            foreach ($result as $key => $val) {
                $val = array_change_key_case($val);
                $types = explode('(', $val['type']);
                $length = isset($types[1]) ? str_replace(')', '', $types[1]) : null;
                if($length) $length = explode(' ' ,$length)[0];
                $info[$val['field']] = [
                    'name'    => $val['field'],
                    'type'    => $this->getFieldType($val['type']),//拿到的不再是数据库上的类型转换了
                    'length'  => $length,//字段长度
                    'notnull' => 'NO' == $val['null'],
                    'default' => $val['default'],
                    'primary' => strtolower($val['key']) == 'pri',
                    'autoinc' => strtolower($val['extra']) == 'auto_increment',
                    'comment' => $val['comment'],
                ];
            }
        }
        return $this->fieldCase($info);
    }
    
    /**
     * 取得数据库的表信息
     * @access public
     * @param  string $dbName
     * @return array
     */
    public function getTables(string $dbName = ''): array
    {
        $sql    = !empty($dbName) ? 'SHOW TABLES FROM ' . $dbName : 'SHOW TABLES ';
        $pdo    = $this->getPDOStatement($sql);
        $result = $pdo->fetchAll(PDO::FETCH_ASSOC);
        $info   = [];
        foreach ($result as $key => $val) {
            $info[$key] = current($val);
        }
        return $info;
    }
    
    protected function supportSavepoint(): bool
    {
        return true;
    }
    
    /**
     * .获取Schema字段的类型
     * @param array $fields
     * @return array
     */
    public function getSchemaType(array $fields): array{
        $info   = [];
        
        foreach ($fields as $key => $val) {
            // 记录字段类型
            $info[$key] = $val['type'];
            
            if (!empty($val['primary'])) {
                $pk[] = $key;
            }
            
            if (!empty($val['autoinc'])) {
                $autoinc = $key;
            }
        }
        
        if (isset($pk)) {
            // 设置主键
            $pk          = count($pk) > 1 ? $pk : $pk[0];
            $info['_pk'] = $pk;
        }
        
        if (isset($autoinc)) {
            $info['_autoinc'] = $autoinc;
        }
        
        return $info;
    }
    
    /**
     * 获取数据表的字段信息
     * @access public
     * @param string $tableName 数据表名
     * @return array
     */
    public function getTableFieldsInfo(string $tableName): array
    {
        return $this->getFields($tableName);
    }
    
    /**
     * 获取数据表信息缓存key
     * @access protected
     * @param string $schema 数据表名称
     * @return string
     */
    public function getSchemaCacheKey(string $schema): string
    {
        return $this->getConfig('hostname') . ':' . $this->getConfig('hostport') . '@' . $schema;
    }
    /**
     * @param string $tableName 数据表名称
     * @param bool $force 强制从数据库获取
     * @return array
     */
    public function getSchemaInfo(string $tableName, $force = false)
    {
        if (!strpos($tableName, '.')) {
            $schema = $this->getConfig('database') . '.' . $tableName;
        } else {
            $schema = $tableName;
        }
        
        if (!isset($this->info[$schema]) || $force) {
            // 读取字段缓存
            $cacheKey   = $this->getSchemaCacheKey($schema);
            $cacheField = $this->config['fields_cache'] && !empty($this->cache);
            
            if ($cacheField && !$force) {
                $info = $this->cache->get($cacheKey);
            }
            
            if (empty($info)) {
                $info = $this->getTableFieldsInfo($tableName);
                if ($cacheField) {
                    $this->cache->set($cacheKey, $info);
                }
            }
            
            $info = $this->getSchemaType($info);
            
            $pk      = $info['_pk'] ?? null;
            $autoinc = $info['_autoinc'] ?? null;
            unset($info['_pk'], $info['_autoinc']);
            
            $this->info[$schema] = [
                'fields'  => array_keys($info),
                'type'    => $info,
                'bind'    => $info,
                'pk'      => $pk,
                'autoinc' => $autoinc,
            ];
        }
        return $this->info[$schema];
    }
    

    /**
     * 分析缓存Key
     * @access protected
     * @param BaseQuery $query 查询对象
     * @param string    $method 查询方法
     * @return string
     */
    protected function getCacheKey(BaseQuery $query, string $method = ''): string
    {
        if (!empty($query->getOptions('key')) && empty($method)) {
            $key = 'db:' . $this->getConfig('database') . ':' . $query->getTable() . ':' . $query->getOptions('key');
        } else {
            $key = 'db:' . $this->getConfig('database') . ':' . $query->getTable().':'.$query->getQueryGuid();
        }
        return $key;
    }
}


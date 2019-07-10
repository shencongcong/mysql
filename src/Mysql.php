<?php
/**
 * Created by PhpStorm.
 * User: danielshen
 * Date: 2019/7/10
 * Time: 15:16
 */

namespace Shencongcong\Mysql;

use Shencongcong\Mysql\Exceptions\Exception;
use Shencongcong\Mysql\Exceptions\GatewayErrorException;
use Shencongcong\Mysql\Exceptions\HttpException;
use Shencongcong\Mysql\Exceptions\InvalidArgumentException;
use Shencongcong\Mysql\Exceptions\PdoErrorException;

class Mysql
{

    private static $instance;

    protected $_table = '';

    protected $_field = '*';

    protected $_order = '';

    protected $_where = '';

    protected $_limit = '';

    protected $_join = '';

    protected $_debug = false;

    protected $_pdo;

    private function __construct($config)
    {
        // 实例化数据库
        try {
            $this->_pdo = new \PDO('mysql:host=' . $config['host'] . ';port=' . $config['port'] . ';dbname=' . $config['database'], $config['user'], $config['password'], [\PDO::ATTR_PERSISTENT => true]);

            $this->_pdo->exec("set names 'utf8'");
        } catch (\PDOException $e) {
            throw  new \PDOException($e->getMessage(), $e->getCode());
        }
    }

    public static function getDbInstance($config)
    {
        // 获取单例
        if ( !(self::$instance instanceof self) ) {
            self::$instance = new Mysql($config);
        }

        return self::$instance;
    }

    public function table($table)
    {
        $this->_table = $table;

        return $this;
    }

    public function field($field)
    {
        $this->_field = $field;

        return $this;
    }

    public function order($order)
    {
        $this->_order = $order;

        return $this;
    }

    public function limit($limit)
    {
        $this->_limit = 'limit ' . $limit;

        return $this;
    }

    public function where($where)
    {
        $real_where = '';
        foreach ($where as $k => $item) {
            $real_where .= " $k= '" . $item . "' and";
        }
        $real_where = 'where ' . substr($real_where, 0, -3);

        $this->_where = $real_where;

        return $this;
    }

    public function page($page = 1, $num = 10)
    {
        $page = intval($page);
        $num = intval($num);
        $start = ($page - 1) * $num;
        $this->_limit = "limit $start,$num";

        return $this;
    }

    public function join($join)
    {
        $this->_join = $join;

        return $this;
    }

    public function debug()
    {
        $this->_debug = true;

        return $this;
    }

    public function select()
    {
        return $this->_query();
    }

    public function find()
    {
        return $this->_query()[0];
    }

    public function insert($data)
    {
        $update = '';
        foreach ($data as $k => $v) {
            $update .= $k . '="' . $v . '",';
        }
        $update = trim($update, ',');
        $sql = "insert into {$this->_table} set $update;";
        $this->_exec($sql);

        return $this->_pdo->lastInsertId();
    }

    public function update($data)
    {
        if ( $this->_where ) {
            $update = '';
            foreach ($data as $k => $v) {
                $update .= '`' . $k . '`="' . $v . '",';
            }
            $update = trim($update, ',');
            $sql = "update {$this->_table} set $update {$this->_where};";

            return $this->_exec($sql);
        }
        else {
            // todo 抛出异常
        }
    }

    public function delete()
    {
        if ( $this->_where ) {
            $sql = "delete  from {$this->_table} {$this->_where};";

            return $this->_exec($sql);
        }
        else {
            throw new InvalidArgumentException('where condition cannot be empty', 2002);
        }
    }

    public function query($sql, $param = [])
    {
        //禁用prepared statements的仿真效果 确保SQL语句和相应的值在传递到mysql服务器之前是不会被PHP解析
        $this->_pdo->setAttribute(\PDO::ATTR_EMULATE_PREPARES, false);

        $pre = $this->_pdo->prepare($sql);
        $pre->execute($param);
        if ( $this->_error() ) {
            return $pre->fetchAll(\PDO::FETCH_ASSOC);
        }
    }

    protected function _exec($sql)
    {
        if ( $this->_debug ) {
            echo $sql;
            exit();
        }
        else {
            $res = $this->_pdo->exec($sql);
            if ( $this->_error() ) {
                return $res;
            }
        }
    }

    protected function _query()
    {
        $sql = "select {$this->_field} from {$this->_table} {$this->_where} {$this->_join} {$this->_order} {$this->_limit}";
        if ( $this->_debug ) {
            echo $sql;
            exit();
        }
        else {
            $res = $this->_pdo->query($sql);
            if ( $this->_error() ) {
                return $res->fetchAll(\PDO::FETCH_ASSOC);
            }
        }
    }

    protected function _error()
    {
        if ( $this->_pdo->errorCode() == 00000 ) {
            return true;
        }
        else {
            throw new PdoErrorException($this->_pdo->errorInfo()[2], $this->_pdo->errorCode());
        }
    }

    private function __clone()
    {
        // TODO: Implement __clone() method.
    }

    private function __wakeup()
    {
        // TODO: Implement __wakeup() method.
    }

}
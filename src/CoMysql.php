<?php
/**
 * Created by PhpStorm.
 * User: danielshen
 * Date: 2019/7/10
 * Time: 15:16
 */

namespace Shencongcong\Mysql;

use Shencongcong\Mysql\Exceptions\GatewayErrorException;
use Shencongcong\Mysql\Exceptions\InvalidArgumentException;
use Shencongcong\Mysql\Exceptions\PdoErrorException;

class CoMysql
{

    private static $instance;

    private $_min=10;

    private $_max=100;

    private $_count;

    private $_connections;

    private $_db_config=[];

    private $_spare_time=10 * 3600; //用于空闲连接回收判断

    private $_inited = false;

    protected $_table = '';

    protected $_field = '*';

    protected $_order = '';

    protected $_where = '';

    protected $_limit = '';

    protected $_join = '';

    protected $_debug = false;

    protected $_swoole_db;

    public function __construct($dbConfig,$config)
    {
        // 实例化数据库
        try {
            $this->_db_config = $dbConfig;
            $this->_min = $config['min'];
            $this->_max = $config['max'];
            $this->_spare_time = $config['spareTime'];
            $this->_connections = new \Swoole\Coroutine\Channel($this->_max + 1);
        } catch (GatewayErrorException $e) {
            throw  new GatewayErrorException($e->getMessage(), $e->getCode());
        }
    }

    public static function getDbInstance($dbConfig,$config)
    {
        // 获取单例
        if ( !(self::$instance instanceof self) ) {
            self::$instance = new CoMysql($dbConfig,$config);
        }

        return self::$instance;
    }

    public function getConnection($timeOut = 3)
    {
        $obj = null;
        if ($this->_connections->isEmpty()) {
            if ($this->_count < $this->_max) {//连接数没达到最大，新建连接入池
                $this->_count++;
                $obj = $this->createObject();
            } else {
                $obj = $this->_connections->pop($timeOut);//timeout为出队的最大的等待时间
            }
        } else {
            $obj = $this->_connections->pop($timeOut);
        }
        return $obj;
    }

    protected function createObject()
    {
        $obj = null;
        $db = $this->createDb();
        if($db){
            $obj = [
                'last_used_time' => time(),
                'db' => $db,
            ];
        }

        return $obj;
    }

    protected function createDb()
    {
        $db = new \Swoole\Coroutine\Mysql();
        $db->connect(
            $this->_db_config
        );

        return $db;
    }

    public function free($obj)
    {
        if ($obj) {
            $this->_connections->push($obj);
        }
    }

    public function init()
    {
        if($this->_inited){
            return null;
        }
        for ($i=0; $i<$this->_min;$i++){
            $obj = $this->createObject();
            $this->_count++;
            $this->_connections->push($obj);
        }

        return $this;
    }

    /**
     * 处理空闲连接
     *
     * @author danielshen
     * @datetime   2019-07-12 16:30
     */
    public function gcSpareObject()
    {
        //大约2分钟检测一次连接
        \Swoole\Timer::tick(120000, function () {
            $list = [];
            /*echo "开始检测回收空闲链接" . $this->connections->length() . PHP_EOL;*/
            if ($this->_connections->length() < intval($this->_max * 0.5)) {
                echo "请求连接数还比较多，暂不回收空闲连接\n";
            }
            while (true) {
                if (!$this->_connections->isEmpty()) {
                    $obj = $this->_connections->pop(0.001);
                    $last_used_time = $obj['last_used_time'];
                    if ($this->_count > $this->_min && (time() - $last_used_time > $this->_spare_time)) {//回收
                        $this->_count--;
                    } else {
                        array_push($list, $obj);
                    }
                } else {
                    break;
                }
            }
            foreach ($list as $item) {
                $this->_connections->push($item);
            }
            unset($list);
        });
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
        $res = $this->_exec($sql);

        return $res;
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
            throw new InvalidArgumentException('where condition cannot be empty', 2002);
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

    protected function _exec($sql)
    {
        if ( $this->_debug ) {
            echo $sql;
            exit();
        }
        else {
            $res = $this->_swoole_db->query($sql);

            return $res;
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
            $res = $this->_swoole_db->query($sql);

            return $res;
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
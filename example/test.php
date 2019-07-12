<?php
/**
 * Created by PhpStorm.
 * User: danielshen
 * Date: 2019/7/10
 * Time: 16:15
 */

require(dirname(__DIR__) . '/vendor/autoload.php');

$db_config = [
    'host' => '10.0.150.78',
    'port' => 3356,
    'user' => 'root',
    'password' => '123u123U!@#',
    'database' => 'test',
];

$config = [
  'min'=>'1',
  'max'=>'2',
  'spareTime'=>'3'
];

$db = \Shencongcong\Mysql\CoMysql::getDbInstance($db_config,$config)->init();
var_dump($db);exit;

// 查询
//$res = $db->table('url_contents')->select();

// 更新
//$res = $db->table('url_contents')->where(['id'=>1])->update(['url'=>'update_www.baidu.comm']);

// 插入

//$res = $db->table('url_contents')->insert(['url'=>'insert_www.baidu.com','contents'=>'www.biadu.com/successs']);
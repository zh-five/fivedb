<?php
/**
 *
 *
 * @author        肖武 <five@v5ip.com>
 * @datetime      2017/12/19 下午11:01
 */

namespace Five\DB;


interface DBInterface {

    /**
     * 初始化一个mysql操作对象
     *
     * @param string $write_host    主库host(写数据的库)
     * @param array  $arr_read_host 从库host列表, 可以多个
     * @param string $database      数据库名
     * @param string $username      用户名
     * @param string $password      密码
     * @param string $charset       字符集
     * @param int    $port          端口号
     *
     * @return mixed
     */
    public static function initMysql($write_host = '127.0.0.1', $arr_read_host = [], $database = 'test', $username = 'root', $password = '', $charset = 'utf8', $port = 3306);

    /**
     * 初始化一个sqlite操作对象
     *
     * @param string $dsn pdo连接需要的dsn. 如:'sqlite:/opt/databases/db.sq3', 'sqlite::memory:',
     *                    'sqlite2:/opt/databases/db.sq2','sqlite2::memory:'
     *
     * @return mixed
     */
    public static function initSqlite($dsn);


    /**
     * 获取原生的PDO对象
     *
     * @param string $wr_type 'w'or'r', w:主库,r:从库. 无从库配置时返回主库pdo, 有则随机返回
     *
     * @return \PDO
     */
    public function getPDO($wr_type);

    /**
     * 执行一条sql语句,返回影响行数
     *
     * @param string $sql   sql, 其中参数用问号占位
     * @param array  $param 替换问号的数据, 数组必须是从0开始的数字索引
     *
     * @return mixed
     */
    public function exec($sql, $param);

    /**
     * 执行一条sql语句,返回一行数据
     *
     * @param string $sql       sql, 其中参数用问号占位
     * @param array  $param     替换问号的数据, 数组必须是从0开始的数字索引
     * @param bool   $is_master 是否强制查主库
     *
     * @return array 一维数组
     */
    public function fetchRow($sql, $param, $is_master = false);

    /**
     * 执行一条sql语句,返回多行数据
     *
     * @param string $sql   sql, 其中参数用问号占位
     * @param array  $param 替换问号的数据, 数组必须是从0开始的数字索引
     * @param bool   $is_master 是否强制查主库
     *
     * @return array  二维数组
     */
    public function fetchAll($sql, $param, $is_master = false);

    /**
     * 执行一条sql语句,返回一列数据.只返回第一列
     *
     * @param string $sql   sql, 其中参数用问号占位
     * @param array  $param 替换问号的数据, 数组必须是从0开始的数字索引
     * @param bool   $is_master 是否强制查主库
     *
     * @return array 一维数组
     */
    public function fetchCol($sql, $param, $is_master = false);

    /**
     * 执行一条sql语句,返回一个字段的数据(表格里的一个单元格)
     *
     * @param string $sql   sql, 其中参数用问号占位
     * @param array  $param 替换问号的数据, 数组必须是从0开始的数字索引
     * @param bool   $is_master 是否强制查主库
     *
     * @return mixed
     */
    public function fetchOne($sql, $param, $is_master = false);

    /**
     * 插入数据
     *
     * @param string $table    表名
     * @param array  $arr_data 一维数组时插入一行, 二维是插入多行
     * @param bool   $ignore   是否忽略插入
     *
     * @return int
     */
    public function insert($table, $arr_data, $ignore = false);

    public function lastInsertId();

    public function delete($table, $arr_where);

    public function update($table, $arr_where, $arr_set);

    /**
     * 根据where条件获取一行数据
     *
     * @param string $table      表名
     * @param array  $arr_where  条件数组
     * @param bool   $is_master 是否强制查主库
     * @param string $select_sql select 和 from 两个关键字中间的sql字符串
     *
     * @return array
     */
    public function getTableWhereRow($table, $arr_where, $is_master = false, $select_sql = '*');

    /**
     * 根据条件组件计算结果数
     *
     * @param string $table     表名
     * @param array  $arr_where 条件数组
     * @param bool   $is_master 是否强制查主库
     *
     * @return int
     */
    public function getTableWhereCount($table, $arr_where, $is_master = false);

    /**
     * @param string $table        表名
     * @param string $select_sql   select 和 from 两个关键字中间的sql字符串
     * @param array  $arr_where    条件数组
     * @param array  $arr_order_by 排序数组[field1, 'asc' or 'desc', field2, 'asc' or 'desc', ...]
     * @param array  $arr_limit    limit限制. [offset, num], 为空则不限制
     * @param bool   $is_master    是否强制查主库
     *
     * @return array
     */
    public function getTableWhereList($table, $select_sql, $arr_where, $arr_order_by, $arr_limit, $is_master = false);

    /**
     * 启动一个事务
     * 在callback里执行数据库操作, 同一个库的操作都会包含在事务里. 若要中断事务, 抛出异常即可
     *
     * @param callable $callback
     *
     * @return \Exception | null 失败时返回异常对象, 成功时返回 null
     */
    public function beginTransaction($callback);
}
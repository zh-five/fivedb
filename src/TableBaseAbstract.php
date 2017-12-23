<?php
/**
 * 数据表操作类的基类
 *
 * @author        肖武 <five@v5ip.com>
 * @datetime      2017/12/22 下午11:05
 */

namespace Five\DB;


abstract class TableBaseAbstract {
    /**
     * db对象
     * @var DB
     */
    protected $_db = null;

    /**
     * 表名
     * @var string
     */
    protected $_table = '';


    /**
     * 子类里实现此方法. 调用项目里的 DBFactory
     *
     * @param string $db_flag 数据库标识
     * @param string $tb_name 表名
     *
     * @throws DBException
     */
    abstract public function __construct($db_flag, $tb_name);


    /**
     * 插入数据
     *
     * @param array $arr_data 一维数组时插入一行, 二维是插入多行
     * @param bool  $ignore   是否忽略插入
     *
     * @return mixed
     */
    public function insert($arr_data, $ignore = false) {
        return $this->_db->insert($this->_table, $arr_data, $ignore);
    }

    /**
     * 根据条件数组删除数据
     *
     * @param $arr_where
     *
     * @return int 影响条数
     * @throws DBException
     */
    public function delete($arr_where) {
        return $this->_db->delete($this->_table, $arr_where);
    }

    /**
     * 修改数据
     *
     * @param array $arr_where 条件数组
     * @param array $arr_set   需修改的数据
     *
     * @return mixed
     * @throws DBException
     */
    public function update($arr_where, $arr_set) {
        return $this->_db->update($this->_table, $arr_where, $arr_set);
    }

    /**
     * 根据where条件获取一行数据
     *
     * @param array  $arr_where  条件数组
     * @param bool   $is_master  是否强制查主库
     * @param string $str_fields select 和 from 两个关键字中间的sql字符串
     *
     * @return mixed
     * @throws DBException
     */
    public function getWhereRow($arr_where, $is_master = false, $str_fields = '*') {
        return $this->_db->getTableWhereRow($this->_table, $arr_where, $is_master, $str_fields);
    }

    /**
     * 根据条件组件计算结果数
     *
     * @param array $arr_where 条件数组
     * @param bool  $is_master 是否强制查主库
     *
     * @return int
     * @throws DBException
     */
    public function getWhereCount($arr_where, $is_master = false) {
        return $this->_db->getTableWhereCount($this->_table, $arr_where, $is_master);
    }

    /**
     * 查询多条记录
     *
     * @param string $str_fields   select 和 from 两个关键字中间的sql字符串
     * @param array  $arr_where    条件数组
     * @param array  $arr_order_by 排序数组[field1, 'asc' or 'desc', field2, 'asc' or 'desc', ...]
     * @param array  $arr_limit    limit限制. [offset, num], 为空则不限制
     * @param bool   $is_master    是否强制查主库
     *
     * @return array 有命中返回二维数组, 无数组则返回空数组
     * @throws DBException
     */
    public function getWhereList($str_fields, $arr_where, $arr_order_by, $arr_limit, $is_master = false) {
        return $this->_db->getTableWhereList($this->_table, $str_fields, $arr_where, $arr_order_by, $arr_limit, $is_master);
    }
}
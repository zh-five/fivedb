<?php
/**
 * 
 *
 * @author        肖武 <five@v5ip.com>
 * @datetime      2017/12/22 下午11:05
 */

namespace Five\DB;


class TableBase {
    /**
     * db对象
     * @var DB
     */
    protected $_db = null;

    /**
     * 表名
     * @var string 
     */
    protected $_tb = '';


    /**
     * 子类里覆盖此方法. 调用项目里的DBFactory
     *
     * @param string $db_flag 数据库标识
     * @param string $tb_name 表名
     *
     * @throws DBException
     */
    public function __construct($db_flag, $tb_name) {
        $this->_db = DBFactory::getDB($db_flag);
        $this->_tb = $tb_name;
    }
    
    
}
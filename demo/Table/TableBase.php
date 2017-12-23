<?php
/**
 * 
 *
 * @author        肖武 <five@v5ip.com>
 * @datetime      2017/12/22 下午11:05
 */

namespace Five\DB\Demo\Table;


class TableBase {
    /**
     * db对象
     * @var \Five\DB\DB
     */
    protected $_db = null;

    /**
     * 表名
     * @var string 
     */
    protected $_tb = '';


    /**
     * 
     *
     * @param string $db_flag 数据库标识
     * @param string $tb_name 表名
     *
     * @throws \Five\DB\DBException
     */
    public function __construct($db_flag, $tb_name) {
        $this->_db = DBFactory::getDB($db_flag);
        $this->_tb = $tb_name;
    }
    
    
}
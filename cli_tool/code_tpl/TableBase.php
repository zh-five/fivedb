{php_start}
/**
 * 数据表操作的基类
 *
 * @author        自动生成
 * @datetime      {date_time}
 */

namespace {namespace};


class TableBase extends \Five\DB\TableBaseAbstract {

    /**
     *
     * @param string $db_flag 数据库标识 DBFactory::DB_*
     * @param string $tb_name 表名
     *
     * @throws \Five\DB\DBException
     */
    public function __construct($db_flag, $tb_name) {
        $this->_db    = DBFactory::getDB($db_flag);
        $this->_table = $tb_name;
    }
}
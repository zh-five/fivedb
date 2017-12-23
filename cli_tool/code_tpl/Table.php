{php_start}
/**
 * 数据表操作类, {db_flag}.{table_name}
 *
 * @author        自动生成
 * @datetime      {date_time}
 */
namespace {namespace}\{db_format};

use {namespace}\DBFactory;
use {namespace}\TableBase;


class {tb_format} extends TableBase {

    /**
     * User constructor.
     * @throws \Five\DB\DBException
     */
    public function __construct() {
        parent::__construct(DBFactory::DB_{db_upper}, '{table_name}');
    }
}
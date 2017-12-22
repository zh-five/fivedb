<?php
namespace Five\DB\Demo\Table\Test;

use Five\DB\Demo\Table\DBFactory;
use Five\DB\Demo\Table\TableBase;

/**
 * 程序自动生成
 */

class User extends TableBase {

    /**
     * User constructor.
     * @throws \Five\DB\DBException
     */
    public function __construct() {
        parent::__construct(DBFactory::DB_TEST, 'user');
    }
}
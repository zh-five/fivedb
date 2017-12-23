<?php
/**
 *
 *
 * @author        肖武 <five@v5ip.com>
 * @datetime      2017/12/22 下午9:48
 */

namespace Five\DB\Demo\Table;

class DBFactory extends \Five\DB\DBFactoryAbstract {
    const DB_TEST = 'test';


    /**
     * 覆盖父类方法返回正确的配置文件路径
     * @return string
     */
    protected static function getConfFile() {
        return __DIR__.'/../conf/db.conf.php';
    }
}
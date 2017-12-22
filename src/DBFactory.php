<?php
/**
 *
 *
 * @author        肖武 <five@v5ip.com>
 * @datetime      2017/12/22 下午9:48
 */

namespace Five\DB;


abstract class DBFactory {
    //const DB_TEST = 'test';

    /**
     *
     * @var DB[]
     */
    protected static $_arr_obj = [];

    /**
     * 所有数据库配置信息
     * @var array
     */
    protected static $_arr_conf = [];

    protected function __construct() {
    }

    /**
     * 获得一个db对象
     *
     * @param string $db_flag
     *
     * @return DB
     * @throws DBException
     */
    public static function getDB($db_flag) {
        if (!isset(self::$_arr_obj[$db_flag])) {
            self::$_arr_obj[$db_flag] = self::_init($db_flag);
        }

        return self::$_arr_obj[$db_flag];
    }

    /**
     * 初始化一个db
     *
     * @param string $db_flag
     *
     * @return DB
     * @throws DBException
     */
    protected static function _init($db_flag) {
        if (!self::$_arr_conf) {
            $conf_file = self::getConfFile();
            self::$_arr_conf = include $conf_file;
        }

        if (!isset(self::$_arr_conf[$db_flag])) {
            throw new DBException('未找到DB配置项:' . $db_flag);
        }
        $conf = self::$_arr_conf[$db_flag];

        return DB::initMysql($conf['write_host'], $conf['arr_read_host'], $conf['username'], $conf['password'], $conf['charset'], $conf['port']);
    }

    /**
     * 获取配置文件路径
     * @return string
     */
    protected static function getConfFile(){
        //在子类里覆盖此方法,返回正确的路径
        return '';
    }
}
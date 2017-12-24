<?php
/**
 *
 *
 * @author        肖武 <five@v5ip.com>
 * @datetime      2017/12/24 下午11:11
 */

namespace Five\DB;


class Util {

    /**
     * 构建查询条件数组
     * 
     * v2：回调函数返回null时当将忽略这个参数
     *
     * @param array $arg         前端提交参数, $_GET或$_POST
     * @param array $where_conf  转换为条件数组的配置
     *
     * @return array [条件数组, 格式化后arg]
     */
    static function arg2SqlWhere($arg, $where_conf) {
        $arr_where  = [];
        $format_arg = [];
        foreach ($where_conf as $k => $v) {
            if (!isset($arg[$k])) { //key不存在, 忽略
                $format_arg[$k] = '';
                continue;
            }
            $format_arg[$k] = $val = $arg[$k];
            
            if (!is_array($val)) {
                $val = trim($val);
            }
            if ($val === '') {  //忽略空字符串
                continue;
            }
            if (!empty($v[1])) {
                $func = $v[1];
                $val  = call_user_func($func, $val);
                if (is_null($val)) {
                    continue;
                }
            }

            $where       = $v[0]; // ['id', '=']
            $where[]     = $val;
            $arr_where[] = $where;
        }

        return [$arr_where, $format_arg];
    }
}

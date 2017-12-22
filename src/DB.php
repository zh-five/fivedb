<?php
/**
 *
 *
 * @author        肖武 <five@v5ip.com>
 * @datetime      2017/12/20 下午7:12
 */

namespace Five\DB;


class DB implements DBInterface {
    protected $_driver_type = 1; //数据库类型. 1:mysql, 2:sqlite,
    
    protected $_conf = [
        'arr_dsn'        => '', //连接主从库的dsn, 主库的key为'w', 从库的是从0开始的数字
        'username'       => '', //用户名
        'password'       => '', //密码
        'driver_options' => '', //具体驱动的连接选项的键=>值数组
        'init_sql'       => '', //连接后初始化执行的sql
    ];

    /**
     * 从库数量
     * @var int 
     */
    protected $_read_dsn_num = 0;

    /**
     * \PDO对象缓存
     * @var \PDO[] 
     */
    protected $_pdo_cache = [];


    /**
     * FiveDB constructor.
     *
     * @param int    $driver_type    数据库类型. 1:mysql, 2:sqlite,
     * @param string $write_dsn      连接主库的dsn
     * @param array  $arr_read_dsn   连接从库的dsn列表
     * @param string $username       用户名
     * @param string $password       密码
     * @param array  $driver_options 具体驱动的连接选项的键
     * @param string $init_sql       连接后初始化执行的sql
     */
    protected function __construct($driver_type, $write_dsn, $arr_read_dsn = [], $username = null, $password = null, $driver_options=null, $init_sql = '') {
        $this->_driver_type = $driver_type;
        $this->_conf = [
            'username'       => $username, //用户名
            'password'       => $password, //密码
            'driver_options' => $driver_options, //具体驱动的连接选项的键=>值数组
            'init_sql'       => $init_sql, //连接后初始化执行的sql
        ];
        
        //dsn
        $this->_conf['arr_dsn'] = $arr_read_dsn;  //从库key为数字
        $this->_conf['arr_dsn']['w'] = $write_dsn; //主库的key是 'w'
        
        //从库数量
        $this->_read_dsn_num = count($arr_read_dsn);
    }

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
     * @return self
     */
    public static function initMysql($write_host = '127.0.0.1', $arr_read_host = [], $database = 'test', $username = 'root', $password = '', $charset = 'utf8', $port = 3306) {
        $dsn = "mysql:dbname={$database};port={$port};host=";
        $write_dsn = $dsn.$write_host;
        $arr_read_dsn = [];
        foreach ($arr_read_host as $host) {
            $arr_read_dsn[] = $dsn.$host;
        }
        $init_sql = "set names '{$charset}'";
        $driver_options = [\PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION]; //出错抛异常
        
        return new self(1, $write_dsn, $arr_read_dsn, $username, $password, $driver_options, $init_sql);
    }

    /**
     * 初始化一个sqlite操作对象
     *
     * @param string $dsn pdo连接需要的dsn. 如:'sqlite:/tmp/sqlite-db.sq3', 'sqlite::memory:',
     *                    'sqlite2:/tmp/sqlite-db.sq2','sqlite2::memory:'
     *
     * @return self
     */
    public static function initSqlite($dsn) {
        return new self(2, $dsn, [], '', '', [\PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION]);
    }

    /**
     * 获取原生的PDO对象
     *
     * @param string $wr_type 'w'or'r', w:主库,r:从库. 无从库配置时返回主库pdo, 有则随机返回
     *
     * @return \PDO
     */
    public function getPDO($wr_type) {
        //无从库, 读写都为主库
        if ($this->_read_dsn_num == 0) { 
            $wr_type = 'w';
        }
        
        $key = $wr_type == 'r' ? mt_rand(0, $this->_read_dsn_num-1) : 'w';
        if (empty($this->_pdo_cache[$key])) {
            $this->_pdo_cache[$key] = new \PDO($this->_conf['arr_dsn'][$key], $this->_conf['username'], $this->_conf['password'], $this->_conf['driver_options']);
            if ($this->_conf['init_sql']) {
                $this->_pdo_cache[$key]->exec($this->_conf['init_sql']);
            }
        }
        
        return $this->_pdo_cache[$key];
    }

    /**
     * 查询一句语句，反回查询句柄
     *
     * @param string  $sql       SQL语句
     * @param array   $param     变量参数
     * @param boolean $is_master 是否强制使用主库
     *
     * @return \PDOStatement|false
     */
    protected function query($sql, array $param = [], $is_master = false) {
        $wr_type = $is_master ? 'w' : $this->getWRType($sql);
        $pdo     = $this->getPdo($wr_type);
        if (!$pdo instanceof \PDO) {
            return false;
        }
        
        $st = $pdo->prepare($sql);
        $ret       = $st->execute($param); // 支持null的写入
        
        //@todo 超时断开后重连处理

        return $st;
    }

    /**
     * 根据sql判断读写类型
     *
     * @param $sql
     *
     * @return string
     */
    protected function getWRType($sql) {
        return substr(strtolower(ltrim($sql)), 0, 6) == 'select' ? 'r' : 'w';
    }
    

    /**
     * 执行一条sql语句,返回影响行数
     *
     * @param string $sql   sql, 其中参数用问号占位
     * @param array  $param 替换问号的数据, 数组必须是从0开始的数字索引
     *
     * @return mixed
     */
    public function exec($sql, $param) {
        $st = $this->query($sql, $param, true);

        return $st->rowCount();
    }


    /**
     * 执行一条sql语句,返回一个字段的数据(表格里的一个单元格)
     *
     * @param string $sql       sql, 其中参数用问号占位
     * @param array  $param     替换问号的数据, 数组必须是从0开始的数字索引
     * @param bool   $is_master 是否强制查主库
     *
     * @return mixed
     */
    public function fetchOne($sql, $param, $is_master = false) {
        $st = $this->query($sql, $param, $is_master);

        return $st ? $st->fetchColumn(0) : $st;
    }

    /**
     * 执行一条sql语句,返回一行数据
     *
     * @param string $sql       sql, 其中参数用问号占位
     * @param array  $param     替换问号的数据, 数组必须是从0开始的数字索引
     * @param bool   $is_master 是否强制查主库
     *
     * @return array 一维数组
     */
    public function fetchRow($sql, $param, $is_master = false) {
        $st = $this->query($sql, $param, $is_master);
        if ($st instanceof \PDOStatement) {
            $row = $st->fetch(\PDO::FETCH_ASSOC);

            return $row ? $row : [];
        } else {
            return [];
        }
    }

    /**
     * 执行一条sql语句,返回一列数据.只返回第一列
     *
     * @param string $sql       sql, 其中参数用问号占位
     * @param array  $param     替换问号的数据, 数组必须是从0开始的数字索引
     * @param bool   $is_master 是否强制查主库
     *
     * @return array 一维数组
     */
    public function fetchCol($sql, $param, $is_master = false) {
        $st = $this->query($sql, $param, $is_master);

        return $st ? $st->fetchAll(\PDO::FETCH_COLUMN, 0) : [];
    }

    /**
     * 执行一条sql语句,返回多行数据
     *
     * @param string $sql       sql, 其中参数用问号占位
     * @param array  $param     替换问号的数据, 数组必须是从0开始的数字索引
     * @param bool   $is_master 是否强制查主库
     *
     * @return array  二维数组
     */
    public function fetchAll($sql, $param, $is_master = false) {
        $st = $this->query($sql, $param, $is_master);

        return $st ? $st->fetchAll(\PDO::FETCH_ASSOC) : [];
    }

    /**
     * 构造逗号分隔的'?'. 如 '?,?,?'
     *
     * @param int $num 问号个数
     *
     * @return string
     */
    protected function mkAskStr($num) {
        return rtrim(str_repeat('?,', $num), ',');
    }

    /**
     * 插入数据
     *
     * @param string $table    表名
     * @param array  $arr_data 一维数组时插入一行, 二维是插入多行
     * @param bool   $ignore   是否忽略插入(sqlite, 此参数无效)
     *
     * @return mixed
     */
    public function insert($table, $arr_data, $ignore = false) {
        $action = $ignore && $this->_driver_type == 1 ? 'INSERT IGNORE INTO' : 'INSERT INTO';

        if (isset($arr_data[0]) && is_array($arr_data[0])) { //当做二维数组处理, 批量插入
            $fields  = '(`' . implode('`,`', array_keys($arr_data[0])) . '`)';
            $row_ask = '(' . $this->mkAskStr(count($arr_data[0])) . ')';

            $arr_values = [];
            $param      = [];
            foreach ($arr_data as $row) {
                $arr_values[] = $row_ask;
                $param        = array_merge($param, array_values($row));
            }
            $values = implode(',', $arr_values);
        } else { //一维数组,插入一行
            $fields = '(`' . implode('`,`', array_keys($arr_data)) . '`)';
            $values = '(' . $this->mkAskStr(count($arr_data)) . ')';
            $param  = array_values($arr_data);
        }

        $sql = "$action $table $fields values $values";

        return $this->exec($sql, $param);
    }

    /**
     * 获取最后插入记录的主键
     * 
     * @return int
     */
    public function lastInsertId() {
        return $this->getPDO('w')->lastInsertId();
    }

    /**
     * 根据条件数组删除数据
     * 
     * @param string $table
     * @param array  $arr_where
     *
     * @return int 影响条数
     * @throws DBException
     */
    public function delete($table, $arr_where) {
        list($where_sql, $paraam) = $this->mkWhere($arr_where);

        $sql = "delete from {$table} {$where_sql}";

        return $this->exec($sql, $paraam);
    }

    /**
     * @param $table
     * @param $arr_where
     * @param $arr_set
     *
     * @return mixed
     * @throws DBException
     */
    public function update($table, $arr_where, $arr_set) {
        list($set_sql, $paraam1)   = $this->mkUpdateSet($arr_set);
        list($where_sql, $paraam2) = $this->mkWhere($arr_where);

        $sql = "update $table $set_sql $where_sql";

        return $this->exec($sql, array_merge($paraam1, $paraam2));
    }

    /**
     * 根据where条件获取一行数据
     *
     * @param string $table      表名
     * @param array  $arr_where  条件数组
     * @param bool   $is_master  是否强制查主库
     * @param string $select_sql select 和 from 两个关键字中间的sql字符串
     *
     * @return mixed
     * @throws DBException
     */
    public function getTableWhereRow($table, $arr_where, $is_master = false, $select_sql = '*') {
        list($where_sql, $paraam) = $this->mkWhere($arr_where);
        $sql = "select {$select_sql} from `$table` {$where_sql}";

        return $this->fetchRow($sql, $paraam, $is_master);
    }

    /**
     * 根据条件组件计算结果数
     *
     * @param string $table     表名
     * @param array  $arr_where 条件数组
     * @param bool   $is_master 是否强制查主库
     *
     * @return int
     * @throws DBException
     */
    public function getTableWhereCount($table, $arr_where, $is_master = false) {
        list($where_sql, $paraam) = $this->mkWhere($arr_where);
        $sql = "select count(1) from `$table` {$where_sql}";

        return $this->fetchOne($sql, $paraam, $is_master);
    }

    /**
     * @param string $table        表名
     * @param string $select_sql   select 和 from 两个关键字中间的sql字符串
     * @param array  $arr_where    条件数组
     * @param array  $arr_order_by 排序数组[field1, 'asc' or 'desc', field2, 'asc' or 'desc', ...]
     * @param array  $arr_limit    limit限制. [offset, num], 为空则不限制
     * @param bool   $is_master    是否强制查主库
     *
     * @return array
     * @throws DBException
     */
    public function getTableWhereList($table, $select_sql, $arr_where, $arr_order_by, $arr_limit, $is_master = false) {
        list($where_sql, $paraam) = $this->mkWhere($arr_where);
        $order_by_sql = $this->mkOrderBy($arr_order_by);
        $str_limit = $arr_limit ? sprintf('limit %d, %d',  $arr_limit[0], $arr_limit[1]) : '';
        $sql = "select {$select_sql} from `$table` {$where_sql} $order_by_sql $str_limit";

        return $this->fetchAll($sql, $paraam, $is_master);
    }

    /**
     * 启动一个事务
     * 在callback里执行数据库操作, 同一个库的操作都会包含在事务里. 若要中断事务, 抛出异常即可
     *
     * @param callable $callback
     *
     * @return \Exception | null 失败时返回异常对象, 成功时返回 null
     */
    public function beginTransaction($callback) {
        $pdo = $this->getPDO('w');
        
        //已在事务里
        if ($pdo->inTransaction()) {
            $callback();
            return null;
        }
        
        //启动事务
        $ret = $pdo->beginTransaction();
        if (!$ret) {
            return new DBException('启动事务失败');
        }

        //执行事务
        try{
            $callback(); //执行回调. 里面应该为各个表的操作

            $pdo->commit(); //提交
        } catch (\Exception $e) {
            $pdo->rollBack(); //回滚
            return $e;
        }
        
        return null;
    }


    /**
     * 构造update的set语句(不含set关键词)
     *
     * @param array $arr_set  [f1=>v1,f2=>v2] --> 'f1=>?,f2=>?', [v1,v2];    [f1=>['f1+?',v1], f2=>['CONCAT(?,f2,?)', [v1,v2]]] ---> 'f1=?,f2=CONCAT(?,f2,?)', [v1,v1,v2]
     *
     * @return array
     */
    protected function mkUpdateSet($arr_set) {
        $tmp = [];
        $param = [];
        foreach ($arr_set as $field => $val) {
            if (is_array($val)) {
                $tmp[] = "`$field`={$val[0]}";
                if (is_array($val[1])) {
                    $param = array_merge($param, $val[1]);
                } else {
                    $param[] = $val[1];
                }
            } else {
                $tmp[] = "`$field`=?";
                $param[] = $val;
            }
        }

        return ['set '.implode(',', $tmp), $param];
    }

    /**
     * 组装where语句
     *
     * @param array  $arr_where 一个单元为一个逻辑条件，多个条件（单元）是and关系. 条件单元有两种模式:
     *                          1.field_name => val  表示 'field_name = val'
     *                          2.field_name => array(op_sign, op_val) 表示 'field_name op_sign op_val'
     *                          举例： array('state' = 1, 'ctime' => array('>=', '1426348800'))
     * @param bool   $is_where  是否带where关键字
     * @param string $glue
     *
     * @return array
     * @throws DBException
     */
    protected function mkWhere($arr_where, $is_where = true, $glue = 'and') {
        $op_list = array();
        $param = array();
        foreach ($arr_where as $f => $v) {
            if (is_string($f) && is_scalar($v)) {
                $op_list[] = "`$f`=?";
                $param[] = $v;
            } elseif (is_int($f) && is_array($v)){
                list($str_op, $op_params) = $this->mkWhereUnit($v);
                if (!$str_op) { //逻辑组合单元的结果可能为空
                    continue;
                }
                $op_list[] = $str_op;
                foreach ($op_params as $op_param) {
                    $param[] = $op_param;
                }
            } else {
                throw new DBException('arr_where格式错误.arr_where查询数组支持两种单元: str=>str|int, int=>arr_op; 其中arr_op有两种格式: array(and|or, arr_where), array(字段名,运算符,数据):'.json_encode($arr_where));
            }
        }
        $sql_where = $op_list ? implode(" $glue ", $op_list) : '';
        $is_where && $sql_where && $sql_where = ' where '.$sql_where;

        return array($sql_where, $param);
    }

    /**
     * 构造where的一个复杂条件单元
     *
     * @param array $arr_op 操作项：array(字段名，操作符，操作数)， 如：array('ctime', '<', 100), array('status', '!=', 0)
     *
     * @return array
     * @throws DBException
     */
    private function mkWhereUnit($arr_op) {
        //逻辑组合单元: array( and|or, arr_where)
        if (isset($arr_op[1]) && in_array($arr_op[0], array('and', 'or')) && is_array($arr_op[1])) {
            if (empty($arr_op[1])) {
                return array('', array());
            }
            list($str_op, $op_params) = $this->mkWhere($arr_op[1], false, $arr_op[0]);

            return array("($str_op)", $op_params);
        }

        //纯条件单元: array(字段名, 运算符, 数据);

        //允许操作符 => 对应处理方法
        $allow_sign = array (
            '='      => 'mkCommOP',
            '<'      => 'mkCommOP',
            '>'      => 'mkCommOP',
            '<='     => 'mkCommOP',
            '>='     => 'mkCommOP',
            '!='     => 'mkCommOP',
            'like'   => 'mkCommOP',
            'in'     => 'mkInOP',
            'not in' => 'mkInOP',
        );
        if (!is_scalar($arr_op[0])) {
            throw new DBException('arr_op格式错误.arr_where查询数组支持两种单元: str=>str|int, int=>arr_op; 其中arr_op有两种格式: array(and|or, arr_where), array(字段名,运算符,数据):'.json_encode($arr_op));
        }
        if (!isset($allow_sign[$arr_op[1]])) {
            throw new DBException('允许运算符:('.implode(',', array_keys($allow_sign)).");实际为:({$arr_op[1]}):".json_encode($arr_op));
        }

        return $this->{$allow_sign[$arr_op[1]]}($arr_op);
    }

    /**
     * 普通操作条件组合
     *
     * @param array $arr_op 如:array('time', '>', 1234567)
     *
     * @return  array
     * @throws DBException
     */
    private function mkCommOP($arr_op) {
        if (!isset($arr_op[2])) {
            throw new DBException('没有操作数:'.json_encode($arr_op));
        }

        if (!is_scalar($arr_op[2]) // ['id', '=', 3]
            && !(
                is_array($arr_op[2]) 
                && count($arr_op[2]) == 2 
                && is_string($arr_op[2][0]) 
                && is_array($arr_op[2][1]) 
            ) // ['total_cash', '>', ['refund_cash + ?', 100] ] 
        ) {
            throw new DBException('操作数类型不对:'. json_encode($arr_op));
        }

        if (is_scalar($arr_op[2])) {
            $val   = '?';
            $param = [$arr_op[2]];
        } else {
            $val   = $arr_op[2][0];
            $param = $arr_op[2][1];
        }

        return array("`{$arr_op[0]}` {$arr_op[1]} $val", $param);
    }

    /**
     * in 和 not in操作条件组合
     *
     * @param array $arr_op 如:array('id', 'in', array(123, 122, 234))
     *
     * @return array
     * @throws DBException
     */
    public function mkInOP($arr_op) {
        if (!is_array($arr_op[2])) {
            throw new DBException('in和not in 查询, 操作数必须为数组', $arr_op);
        }

        $str_op = sprintf('`%s` %s (%s)', $arr_op[0], $arr_op[1], $this->mkAskStr(count($arr_op[2])));

        return array($str_op, $arr_op[2]);
    }


    /**
     * 组装排序sql语句
     *
     * @param array $arr_order 格式: array([field_name1, is_desc[, field_name2, is_desc][, ...]]])
     *
     * @return string
     * @throws DBException
     */
    protected function mkOrderBy($arr_order) {
        if (!is_array($arr_order)) {
            throw new DBException('sql查询的$arr_order参数必须为数组');
        }

        if (empty($arr_order)) {
            return ' ';
        }

        $num = count($arr_order);
        if ($num % 2 != 0) {
            throw new DBException('sql查询的$arr_order参数数组单元数须为偶数');
        }

        $out = array();
        for ( $i=0; $i<$num; $i+=2){
            if (is_scalar($arr_order[$i+0]) && is_scalar($arr_order[$i+1])) {
                $out[] = sprintf('`%s` %s', $arr_order[$i+0], $arr_order[$i+1] ? 'desc' : 'asc');
                continue;
            }
            throw new DBException('sql查询的$arr_order参数格式错误');
        }

        return $out ? ' order by '.implode(',', $out) : '';
    }
}
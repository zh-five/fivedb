<?php
/**
 *
 *
 * @author        肖武 <five@v5ip.com>
 * @datetime      2017/12/20 下午9:51
 */

namespace Five\Test\DB;

include __DIR__.'/../src/DBInterface.php';
include __DIR__.'/../src/DB.php';

try{
    $obj = new TestMysql();
    $obj->run();
} catch (\Exception $e) {
    print_r($e);
}

class TestMysql{
    /**
     * @var DB
     */
    protected $db = null;
    
    function run() {
        $this->init();
        $this->connect();
        $this->create();
        $this->showTables();
        //$this->insertNull();return;
        $this->insert();
        $this->select();
        $this->update();
        $this->transaction();
        $this->delete();
        
    }
    
    function init() {
        error_reporting(E_ALL); //显示所有错误报告
        ini_set("display_errors", "On"); 
    }
    
    function connect() {
        $this->db = DB::initMysql('127.0.0.1', [], 'test', 'test', 'test123');
    }
    
    function showTables() {
        $arr = $this->db->fetchAll('show tables', []);
        echo "show tables:\n";
        print_r($arr);
    }
    
    function create() {
        $sql = 'CREATE TABLE IF NOT EXISTS `aa_test` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT \'主键\',
  `k_host_conf` varchar(32) NOT NULL COMMENT \'主机名-配置名\',
  `test` int(11) NOT NULL,
  `update_time` int(11) NOT NULL COMMENT \'更新时间\',
  `str_time` datetime NOT NULL COMMENT \'字符串时间\',
  PRIMARY KEY (`id`),
  UNIQUE KEY `k_host_conf` (`k_host_conf`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COMMENT=\'测试db服务是否正常的表\' AUTO_INCREMENT=1 ;';
        
        echo "创建表:\n";
        $ret = $this->db->exec($sql, []);
        var_dump($ret);
    }
    
    function insertNull() {
        $tb = 'aa_test';
        $row = [
            'k_host_conf' => '', //uniqid(),
            'test' => 0,
            'update_time' => time(),
            'str_time' => date('Y-m-d H:i:s'),
        ];
        print_r($row);
        $n = $this->db->insert($tb, $row);
        echo $n;
    }
    
    function insert() {
        $tb = 'aa_test';
        $row = [
            'k_host_conf' => uniqid(),
            'test' => 234,
            'update_time' => time(),
            'str_time' => date('Y-m-d H:i:s'),
        ];
        
        echo "-- 插入一行\n";
        $n = $this->db->insert($tb, $row);
        printf("影响行数:%d, id:%d\n", $n, $this->db->lastInsertId());
        
        echo "-- 正常重复插入报错\n";
        try{
            $this->db->insert($tb, $row);
        } catch (\Exception $e) {
            print_r($e->getMessage());
            echo "\n";
        }
        
        echo "-- 忽略模式重复插入:\n";
        $n = $this->db->insert($tb, $row, true);
        printf("影响行数:%d, id:%d\n", $n, $this->db->lastInsertId());
        
        echo "-- 批量插入\n";
        $list = [];
        for ($i=0; $i<10;$i++) {
            $list[] = [
                'k_host_conf' => uniqid(),
                'test' => 234,
                'update_time' => time(),
                'str_time' => date('Y-m-d H:i:s'),
            ];
        }
        $n = $this->db->insert($tb, $list);
        printf("影响行数:%d, id:%d\n", $n, $this->db->lastInsertId());

        echo "-- 正常重复批量插入报错\n";
        try{
            $this->db->insert($tb, $list);
        } catch (\Exception $e) {
            print_r($e->getMessage());
            echo "\n";
        }

        echo "-- 忽略模式重复插入:\n";
        $n = $this->db->insert($tb, $list, true);
        printf("影响行数:%d, id:%d\n", $n, $this->db->lastInsertId());
    }

    /**
     * @throws DBException
     */
    function select() {
        echo "---- test select\n";
        $table = 'aa_test';
        printf("-- 总记录数:%d\n", $this->db->getTableWhereCount($table, []));
        
        echo "-- 后两条记录:\n";
        print_r($this->db->getTableWhereList($table,'*', [], ['id', 1], [0, 2]));
        
        echo "-- 10秒内更新的记录\n";
        $arr_where = [
            ['update_time', '>', time() - 10],
        ];
        $list = $this->db->getTableWhereList($table,'*', $arr_where, ['id', 1], []);
        print_r($list);

        echo "-- 10秒内更新的复杂条件查询\n";
        $arr_where = [
            ['update_time', '>', time() - 10],
            ['or', [
                ['id', '=', $list[0]['id']],
                ['k_host_conf', 'like', $list[2]['k_host_conf']],
            ]],
        ];
        print_r($this->db->getTableWhereList($table,'*', $arr_where, ['id', 1], []));
        
        echo "-- 查一列:\n";
        print_r($this->db->fetchCol('select id from aa_test limit 3', []));
        
        
        echo "-- wehre f > f1+?\n";
        $this->db->insert($table, [
            'k_host_conf' => uniqid(),
            'test' => 1,
            'update_time' => 4,
        ]);
        $arr = $this->db->getTableWhereRow($table,  [
            ['update_time', '=', ['test + ?', [3]]]
        ]);
        print_r($arr);

    }
    
    function update() {
        echo "---- update\n";
        $tb = 'aa_test';
        $row = $this->db->getTableWhereRow($tb, []);
        print_r($row);
        $id = $row['id'];
        $arr_where = ['id' => $id];
        print_r($arr_where);
        
        echo "-- 修改=100\n";
        $this->db->update($tb, $arr_where, ['test' => 100]);
        print_r($this->db->getTableWhereRow($tb,  $arr_where));

        echo "-- 修改=f+100\n";
        $this->db->update($tb, $arr_where, ['test' => ['update_time+?', 100]]);
        print_r($this->db->getTableWhereRow($tb,  $arr_where));

        echo "-- 修改加括号\n";
        $this->db->update($tb, $arr_where, ['k_host_conf' => ['CONCAT(?,k_host_conf, ?)', ['(', ')']]]);
        print_r($this->db->getTableWhereRow($tb, $arr_where));
        
        echo "-- 删除\n";
        $this->db->delete($tb, $arr_where);
        print_r($this->db->getTableWhereRow($tb,  $arr_where));
        
    }
    
    //事务
    function transaction() {
        $table = 'aa_test';
        echo "---- 事务测试\n";
        echo "-- 正常执行\n";
        printf("-- 事务前,总记录数:%d\n", $this->db->getTableWhereCount($table, []));
        $this->db->beginTransaction(function(){
            $this->insert();
            $this->update();
        });
        printf("-- 事务后,总记录数:%d\n", $this->db->getTableWhereCount($table, []));

        echo "-- 执行时抛异常\n";
        printf("-- 事务前,总记录数:%d\n", $this->db->getTableWhereCount($table, []));
        $e = $this->db->beginTransaction(function(){
            $this->insert();
            $this->update();
            throw new \Exception('出错啦!');
        });
        echo "error:", $e->getMessage(),"\n";
        printf("-- 事务后,总记录数:%d\n", $this->db->getTableWhereCount($table, []));

        echo "-- 事务嵌套\n";
        printf("-- 事务前,总记录数:%d\n", $this->db->getTableWhereCount($table, []));
         $this->db->beginTransaction(function(){
            $this->insert();
            $this->transaction2();
        });
        printf("-- 事务后,总记录数:%d\n", $this->db->getTableWhereCount($table, []));
    }
    
    function transaction2() {
        $this->db->beginTransaction(function(){
            $tb = 'aa_test';
            $arr_where = $this->db->getTableWhereRow($tb,  []);
            printf("count:%d\n", $this->db->getTableWhereCount($tb, []));
            $this->db->delete($tb, $arr_where);
            printf("count:%d\n", $this->db->getTableWhereCount($tb, []));
        });
    }
    
    function delete() {
        $tb = 'aa_test';
        echo "---- delete\n";
        
        echo "-- 删除1条\n";
        $arr_where = $this->db->getTableWhereRow($tb, [], false, 'id');
        printf("count:%d\n", $this->db->getTableWhereCount($tb, []));
        $this->db->delete($tb, $arr_where);
        printf("count:%d\n", $this->db->getTableWhereCount($tb, []));

        echo "-- 删除1秒前的数据\n";
        printf("count:%d\n", $this->db->getTableWhereCount($tb, []));
        $this->db->delete($tb, [['update_time', '<', time()-1]]);
        printf("count:%d\n", $this->db->getTableWhereCount($tb, []));


        echo "-- 清空表\n";
        printf("count:%d\n", $this->db->getTableWhereCount($tb, []));
        $this->db->exec('truncate aa_test', []);
        printf("count:%d\n", $this->db->getTableWhereCount($tb, []));
    }
}

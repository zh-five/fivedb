<?php
/**
 * 命令行工具
 *
 * @author        肖武 <five@v5ip.com>
 * @datetime      2017/12/23 下午8:58
 */

if (count($argv) != 4) {
    echo "功能: 根据数据库配置文件自动生成data层代码, 一表一类一文件\n";
    echo '用法: php ',$argv[0], " <conf_file> <sub_namespace> <code_dir>\n";
    echo "参数说明:\n";
    echo "\tconf_file: 数据库配置文件路径\n";
    echo "\tsub_namespace: data层子命名空间.如:'Five\\DB\\Data'\n";
    echo "\tcode_dir: data层代码存储目录.'./src/Data'\n";
    echo "如: php cli_tool/create_code.php cli_tool/db.example.conf.php 'Five\Test' ./tmp\n";
    exit;
}

try{
    $obj = new \DBTool($argv[1], $argv[2], $argv[3]);
    $obj->run();
}catch (Throwable $e){
    print_r($e);
}

class DBTool{
    protected $_conf_file = '';
    protected $_sub_namespace = '';
    protected $_code_dir = '';
    
    protected $_arr_conf = [];
    
    function __construct($conf_file, $sub_namespace, $code_dir) {
        $this->_conf_file = realpath($conf_file);
        $this->_sub_namespace = trim($sub_namespace, '\\');
        $this->_code_dir = realpath($code_dir);
    }
    
    function run() {
        $this->check();
        $this->mkFactory();
        $this->mkDataBase();
        $this->mkTable();
    }
    
    protected function check() {
        
        
        if (!is_file($this->_conf_file)) {
            echo "配置文件不存在:".$this->_conf_file, "\n";
            exit;
        }  
        
        if (!is_dir($this->_code_dir)) {
            echo '代码目录不存在:', $this->_code_dir, "\n";
            exit;
        }
        
        $this->_arr_conf = require $this->_conf_file;
        if (!$this->_arr_conf) {
            echo "读取配置信息失败.请检查配置文件\n";
            var_dump($this->_arr_conf);
            exit;
                
        }
    }
    
    protected function mkFactory() {
        $tpl_file = __DIR__.'/code_tpl/DBFactory.php';
        $to_file  = $this->_code_dir.'/DBFactory.php';

        $arr_replace = [
            '{php_start}'  => '<?php',
            '{date_time}'  => date('Y-m-d H:i:s'),
            '{namespace}'  => $this->_sub_namespace,
            '{const_list}' => $this->mkConstList(),
            '{conf_file}'  => $this->relativePath($this->_conf_file, $this->_code_dir),
        ];
        
        return $this->replaceMoveFile($tpl_file, $to_file, $arr_replace);
    }
    
    protected function mkDataBase() {
        $tpl_file = __DIR__.'/code_tpl/TableBase.php';
        $to_file  = $this->_code_dir.'/TableBase.php';

        $arr_replace = [
            '{php_start}'  => '<?php',
            '{date_time}'  => date('Y-m-d H:i:s'),
            '{namespace}'  => $this->_sub_namespace,
        ];

        return $this->replaceMoveFile($tpl_file, $to_file, $arr_replace);
    }
    
    protected function mkTable() {
        include __DIR__.'/../src/DBInterface.php';
        include __DIR__.'/../src/DB.php';
        include __DIR__.'/../src/DBFactoryAbstract.php';
        include $this->_code_dir.'/DBFactory.php';

        $tpl_file = __DIR__.'/code_tpl/Table.php';
        foreach ($this->_arr_conf as $db_flag => $v) {
            //echo $db_flag,"\n";
            $db_format = $this->formatName($db_flag);
            
            //目录
            $db_dir = $this->_code_dir.'/'.$db_format;
            if (!file_exists($db_dir)) {
                mkdir($db_dir);
            }
            
            //表列表
            $class = $this->_sub_namespace.'\\DBFactory';
            $db = call_user_func([$class, 'getDB'], $db_flag);
            $arr_tb = $db->fetchCol('show tables', []);
            
            $date_time = date('Y-m-d H:i:s');
            foreach ($arr_tb as $table) {
                $tb_format = $this->formatName($table);
                $to_file = "$db_dir/$tb_format.php";
                $arr_replace = [
                    '{php_start}'  => '<?php',
                    '{date_time}'  => $date_time,
                    '{namespace}'  => $this->_sub_namespace,
                    '{table_name}' => $table,
                    '{tb_format}'  => $tb_format,
                    '{db_flag}'    => $db_flag,
                    '{db_format}'  => $db_format,
                    '{db_upper}'   => strtoupper($db_flag),
                ];
                
                $n = $this->replaceMoveFile($tpl_file, $to_file, $arr_replace);
                $n && printf("%s.%s\n", $db_flag, $table);
            }
        }
        
        //测试
        echo "\n\n生成文件结束\n";
        include __DIR__.'/../src/TableBaseAbstract.php';
        include $this->_code_dir.'/TableBase.php';
        include $to_file;
        $class = "{$this->_sub_namespace}\\{$db_format}\\{$tb_format}";
        echo '测试 ',$class, " 读取一行得:\n";
        $obj = new $class();
        
        $arr = $obj->getWhereRow([]);
        print_r($arr);
    }
    
    //生成 DBFactory 的类常量列表
    protected function mkConstList() {
        $str = '';
        foreach ($this->_arr_conf as $db_flag => $v) {
            $str .= sprintf("    const DB_%s = '%s';\n"
                , strtoupper($db_flag)
                , $db_flag
            );
        }
        
        return $str;
    }

    /**
     * 格式化为驼峰命名
     * admin_user -> AdminUser
     * adminUser  -> AdminUser
     * AdminUser  -> AdminUser
     * Admin_User -> AdminUser
     * 
     * @param $name
     *
     * @return string
     */
    protected function formatName($name) {
        $arr_tmp = explode('_', $name);
        array_walk($arr_tmp, function(&$v) {
            $v{0} = strtoupper($v{0});
        });
        
        return implode('', $arr_tmp);
    }

    /**
     * 计算path相对于base_dir的相对路径
     * 
     * @param string $path
     * @param string $base_dir
     *
     * @return string
     */
    protected function relativePath($path, $base_dir) {
        $arr_path = explode('/', $path);
        $arr_dir  = explode('/', $base_dir);
        
        $k = 0;
        foreach ($arr_path as $k => $v) {
            if (!isset($arr_dir[$k]) || $arr_dir[$k] != $arr_path[$k]) {
                break;
            }
        }
        
        $str = str_repeat('../', count($arr_dir)-$k);
        $str.= implode('/', array_slice($arr_path, $k));
        
        return $str;
    }

    protected function replaceMoveFile($from_file, $to_file, $arr_replace) {
        if (file_exists($to_file)) {
            return 0;
        }

        $code = file_get_contents($from_file);
        $code = str_replace(array_keys($arr_replace), array_values($arr_replace), $code);

        return file_put_contents($to_file, $code);
    }
}
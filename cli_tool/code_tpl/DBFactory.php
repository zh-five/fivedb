{php_start}
/**
 * DB工厂类
 *
 * @author        自动生成
 * @datetime      {date_time}
 */

namespace {namespace};

class DBFactory extends \Five\DB\DBFactoryAbstract {
{const_list}


    /**
     * 覆盖父类方法返回正确的配置文件路径
     * @return string
     */
    protected static function getConfFile() {
        return __DIR__.'/{conf_file}';
    }
}
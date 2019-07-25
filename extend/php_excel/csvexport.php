<?php

namespace php_excel;

use think\Controller;
use think\Request;
use app\common\model\ExcelImportTemporary;
use think\Db;

/**
 * PHPCsv 工具类
 * 使用说明
 * use php_excel\csvexport;;//引入
 * 
 * $csvexport = new csvexport();
 * 
 */
class csvexport extends Controller {

    // uploads 路径
    private $runtime_url = ROOT_PATH . 'public' . DS . 'uploads' . DS . 'export_csv'; //config('uploads_url');
    public function __construct() {
        parent::_initialize();
    }

    function putCsvOne($data,$filename) {
        $str = '';
        for ($i = 0; $i < count($data); $i++) {
            for ($c = 0; $c < count($data[$i]); $c++) {
                $value = mb_convert_encoding($data[$i][$c], 'GBK', 'UTF-8');
                if ($value) {
                    if ($c == count($data[$i]) - 1) {
                        $str .= $value;
                    } else {
                        $str .= $value . ",";
                    }
                } else {
                    $str .= $value . ",";
                }
            }
            $str .= "\r\n";
        }
        //设置heard
        header('Content-Type: application/download');
        header("Content-type:text/csv");
        $filename .= '.csv';
        header("Content-Disposition:attachment;filename=" . $filename);
        header('Cache-Control:must-revalidate,post-check=0,pre-check=0');
        header('Expires:0');
        header('Pragma:public');
        echo $str;
    }
}

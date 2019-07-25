<?php

namespace log;

class Log {
    /**
     * 日志文件大小限制
     * @var int 字节数
     */
    private static $log_size = 1048576; // 1024 * 1024 * 5 = 5MB 

    /**
     * 设置单个日志文件大小限制
     * 
     * @param int $size 字节数
     */

    public static function set_size($size) {
        if (is_numeric($size)) {
            self::$log_size = $size;
        }
    }

    /**
     * 写日志
     *
     * @param string $log_message 日志信息
     * @param string $log_type    日志类型
     */
    public static function writer($log_message, $log_type, $rname) {
        empty($log_type)&&$log_type = 'log';
        empty($rname)&&$rname = 'public';
        $log_type = strtolower($log_type);
        $rname = strtolower($rname);
        // 检查日志目录是否可写 
        $path =  RUNTIME_PATH."writelog/"."$log_type/$rname/";
        if (!file_exists($path)) {
            @mkdir($path, 0777, true);
        }
        if (!is_writable($path))
            exit(' is not writeable !');
        $s_now_time = date('[Y-m-d H:i:s]');
        $log_now_day = date('Y_m_d');
        // 根据类型设置日志目标位置 
        $log_path = $path;
        switch ($log_type) {
            case 'debug':
                $log_path .= 'out_' . $log_now_day . '.xml';
                break;
            case 'error':
                $log_path .= 'err_' . $log_now_day . '.xml';
                break;
            case 'log':
                $log_path .= 'log_' . $log_now_day . '.xml';
                break;
            case 'every_page':
                $log_path .= 'every_page_' . $log_now_day . '.xml';
                break;
            case 'quality':
                $log_path .= 'quality_' . $log_now_day . '.xml';
                break;
            case 'pay_ok':
                $log_path .= 'pay_ok_' . $log_now_day . '.xml';
                break;
            case 'pay_error':
                $log_path .= 'pay_error' . $log_now_day . '.xml';
                break;
            default:
                $log_path .= 'log_' . $log_now_day . '.xml';
                break;
        }

        //检测日志文件大小, 超过配置大小则重命名 
        if (file_exists($log_path) && self::$log_size <= filesize($log_path)) {
            $s_file_name = substr(basename($log_path), 0, strrpos(basename($log_path), '.log')) . '_' . time() . '.log';
            rename($log_path, dirname($log_path) . DS . $s_file_name);
        }
        clearstatcache();
        // 写日志, 返回成功与否
        $flag = "'字符串'";
        if (is_object($log_message)) {
            
            $log_message = json_decode(json_encode( $log_message ), true );
            $flag = "对象";
        }else if(is_array($log_message)){
            $flag ="数组";
        }
//        $log_message = is_array($log_message) ? self::ToXml($log_message) : $log_message;
        $log_message = is_array($log_message) ? json_encode($log_message) : $log_message;
        return error_log("$s_now_time  $flag\n $log_message\n\n", 3, $log_path);
    }

    public static function ToXml($data, $root = true, $kg = "  ") {
        if (empty($data)) {
            return "";
        } else if (is_string($data)) {
            return "<xml>\n$kg<string><![CDATA[$data]]></string>\n</xml>\n\n";
        } else {
            $str = "";
            if ($root)
                $str .= "<xml>\n";
            foreach ($data as $key => $val) {
                if (is_array($val)) {
                    $child = self::ToXml($val, false, $kg . "   ");
                    $str .= "$kg<$key>$kg\n$child$kg</$key>\n";
                } else {
                    $str.= "$kg<$key><![CDATA[$val]]></$key>\n";
                }
                if ($kg == "  " && is_array($val)) {
                    $str.="\n";
                }
            }
            if ($root)
                $str .= "</xml>\n\n";
            return $str;
        }
    }

}

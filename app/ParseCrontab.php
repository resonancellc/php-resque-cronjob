<?php namespace App;

class ParseCrontab
{
    static public $error;

    /**
     *  解析crontab的定时格式，linux只支持到分钟/，这个类支持到秒
     * @param string $crontab_string :
     *
     *  3    4    5
     *  *    *    *
     *  -    -    -
     *  |    |    |
     *  |    |    +----- day of week (0 - 6) (Sunday=0)
     *  |    +----- month (1 - 12)
     *  +------- day of month (1 - 31)

     * @param int $start_time timestamp [default=current timestamp]
     * @return int unix timestamp - 下一分钟内执行是否需要执行任务，如果需要，则把需要在那几秒执行返回
     * @throws InvalidArgumentException 错误信息
     */
    static public function parse($crontab_string, $start_time = null)
    {
        if (!preg_match('/^((\*(\/[0-9]+)?)|[0-9\-\,\/]+)\s+((\*(\/[0-9]+)?)|[0-9\-\,\/]+)\s+((\*(\/[0-9]+)?)|[0-9\-\,\/]+)$/i', trim($crontab_string))) {
                self::$error = "Invalid cron string: " . $crontab_string;
                return false;
        }

        if ($start_time && !is_numeric($start_time)) {
            self::$error = "\$start_time must be a valid unix timestamp ($start_time given)";
            return false;
        }

        $cron  = preg_split("/[\s]+/i", trim($crontab_string));
        $start = empty($start_time) ? time() : $start_time;
        // for test
//        $cron  = array(
//            0 => '*',
//            1 => '10',
//            2 => '0,1,2,3'
//        );
        $date = array(
            'day'     => self::_parse_cron_number($cron[0], 1, 31),
            'month'   => self::_parse_cron_number($cron[1], 1, 12),
            'week'    => self::_parse_cron_number($cron[2], 0, 6),
        );
        if(
            in_array(intval(date('j', $start)), $date['day']) &&
            in_array(intval(date('w', $start)), $date['week']) &&
            in_array(intval(date('n', $start)), $date['month'])

        ){
            return true;
        }
        return false;
    }

    /**
     * 解析单个配置的含义
     * @param $s
     * @param $min
     * @param $max
     * @return array
     */
    static protected function _parse_cron_number($s, $min, $max)
    {
        $result = array();
        $v1 = explode(",", $s);
        foreach ($v1 as $v2) {
            $v3 = explode("/", $v2);
            $step = empty($v3[1]) ? 1 : $v3[1];
            $v4 = explode("-", $v3[0]);
            $_min = count($v4) == 2 ? $v4[0] : ($v3[0] == "*" ? $min : $v3[0]);
            $_max = count($v4) == 2 ? $v4[1] : ($v3[0] == "*" ? $max : $v3[0]);
            for ($i = $_min; $i <= $_max; $i += $step) {
                $result[$i] = intval($i);
            }
        }
        ksort($result);
        return $result;
    }
}
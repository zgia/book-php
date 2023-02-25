<?php

namespace App\Helper;

use Carbon\CarbonImmutable;

/**
 * Class DateTimeHelper
 */
class DateTimeHelper
{
    /**
     * 计算两个时间相差几个月
     *
     * @param int $date1 UNIX 时间戳
     * @param int $date2 UNIX 时间戳
     *
     * @return int
     */
    public static function monthDiff(int $date1, int $date2)
    {
        return CarbonImmutable::createFromTimestampUTC($date1)->diffInMonths(CarbonImmutable::createFromTimestampUTC($date2));
    }

    /**
     * 某个时间往过去、将来 $num 个月
     *
     * @param int $timestamp UNIX 时间戳
     * @param int $num       <0，表示往过去；>0，表示往将来
     *
     * @return int UNIX 时间戳
     */
    public static function addMonths(int $timestamp, int $num)
    {
        return CarbonImmutable::createFromTimestampUTC($timestamp)->addMonths($num)->timestamp;
    }

    /**
     * 获取某一天的增减月份后的日期
     *
     * @param string $day 某天，比如2022-10-20
     * @param int    $num <0，表示往过去；>0，表示往将来
     *
     * @return string yyyy-mm-dd
     */
    public static function dayAddMonths(string $day, int $num)
    {
        return CarbonImmutable::now()->setDateFrom($day)->addMonths($num)->toDateString();
    }
}

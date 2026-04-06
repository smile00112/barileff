<?php

namespace Webkul\Core\Support;

use Illuminate\Database\Query\Expression;
use Illuminate\Support\Facades\DB;

class DbHelper
{
    /**
     * MySQL: FIELD(column, 1,2,3)
     * PG:    array_position(ARRAY[1,2,3], column)
     */
    public static function fieldOrderBy(string $column, array $ids): Expression
    {
        if (empty($ids)) {
            return DB::raw('0');
        }

        $idsList = implode(',', array_map('intval', $ids));

        if (DB::getDriverName() === 'pgsql') {
            return DB::raw("array_position(ARRAY[{$idsList}], {$column})");
        }

        return DB::raw("FIELD({$column}, {$idsList})");
    }

    /**
     * MySQL: GROUP_CONCAT(column SEPARATOR ',')
     * PG:    STRING_AGG(column::text, ',')
     */
    public static function groupConcat(string $column, string $separator = ','): string
    {
        $separator = addslashes($separator);

        if (DB::getDriverName() === 'pgsql') {
            return "STRING_AGG({$column}::text, '{$separator}')";
        }

        return "GROUP_CONCAT({$column} SEPARATOR \"{$separator}\")";
    }

    /**
     * MySQL: FIND_IN_SET(?, column)
     * PG:    ? = ANY(string_to_array(column, ','))
     */
    public static function findInSet(string $value, string $column): string
    {
        if (DB::getDriverName() === 'pgsql') {
            return "{$value} = ANY(string_to_array({$column}, ','))";
        }

        return "FIND_IN_SET({$value}, {$column})";
    }

    /**
     * MySQL: DATE_FORMAT(column, '%Y-%m-%d')
     * PG:    TO_CHAR(column, 'YYYY-MM-DD')
     */
    public static function dateFormat(string $column, string $format): string
    {
        if (DB::getDriverName() === 'pgsql') {
            return "TO_CHAR({$column}, '".self::mysqlFormatToPostgres($format)."')";
        }

        return "DATE_FORMAT({$column}, \"{$format}\")";
    }

    /**
     * MySQL: IF(condition, then, else)
     * PG:    CASE WHEN condition THEN then ELSE else END
     */
    public static function ifExpression(string $condition, string $then, string $else): string
    {
        if (DB::getDriverName() === 'pgsql') {
            return "CASE WHEN {$condition} THEN {$then} ELSE {$else} END";
        }

        return "IF({$condition}, {$then}, {$else})";
    }

    /**
     * MySQL: json_unquote(json_extract(column, '$."key"'))
     * PG:    (column->>'key')::integer
     */
    public static function jsonExtractInt(string $column, string $key): string
    {
        if (DB::getDriverName() === 'pgsql') {
            return "({$column}->>'{$key}')::integer";
        }

        return "json_unquote(json_extract({$column}, '$.\"".$key."\"'))";
    }

    /**
     * CONCAT_WS works on both, but PG requires explicit CAST for non-text columns.
     */
    public static function concatWs(string $separator, array $columns): string
    {
        $separator = addslashes($separator);
        $columnsList = implode(', ', $columns);

        return "CONCAT_WS('{$separator}', {$columnsList})";
    }

    /**
     * Reporting: group by calendar month (1–12).
     *
     * MySQL: MONTH(column)
     * PG:    EXTRACT(MONTH FROM column)
     */
    public static function reportingGroupByMonth(string $column): string
    {
        if (DB::getDriverName() === 'pgsql') {
            return "EXTRACT(MONTH FROM {$column})";
        }

        return "MONTH({$column})";
    }

    /**
     * Reporting: group by ISO 8601 week number (aligned with Carbon week / getWeeksInterval filters).
     *
     * MySQL: WEEK(column, 3) — mode 3 is ISO 8601
     * PG:    EXTRACT(WEEK FROM column)
     */
    public static function reportingGroupByIsoWeek(string $column): string
    {
        if (DB::getDriverName() === 'pgsql') {
            return "EXTRACT(WEEK FROM {$column})";
        }

        return "WEEK({$column}, 3)";
    }

    /**
     * Reporting: group by day-of-year (1–366).
     *
     * MySQL: DAYOFYEAR(column)
     * PG:    EXTRACT(DOY FROM column)
     */
    public static function reportingGroupByDayOfYear(string $column): string
    {
        if (DB::getDriverName() === 'pgsql') {
            return "EXTRACT(DOY FROM {$column})";
        }

        return "DAYOFYEAR({$column})";
    }

    /**
     * Reporting: English weekday name (Sunday … Saturday), for grouping / display.
     *
     * MySQL: DAYNAME(column)
     * PG:    CASE EXTRACT(DOW FROM column) — DOW: 0 = Sunday … 6 = Saturday
     */
    public static function reportingDayNameEnglish(string $column): string
    {
        if (DB::getDriverName() === 'pgsql') {
            return 'CASE EXTRACT(DOW FROM '.$column.')::integer '
                ."WHEN 0 THEN 'Sunday' WHEN 1 THEN 'Monday' WHEN 2 THEN 'Tuesday' "
                ."WHEN 3 THEN 'Wednesday' WHEN 4 THEN 'Thursday' WHEN 5 THEN 'Friday' WHEN 6 THEN 'Saturday' END";
        }

        return "DAYNAME({$column})";
    }

    /**
     * Convert MySQL date format specifiers to PostgreSQL TO_CHAR format.
     */
    protected static function mysqlFormatToPostgres(string $format): string
    {
        $replacements = [
            '%Y' => 'YYYY',
            '%y' => 'YY',
            '%m' => 'MM',
            '%d' => 'DD',
            '%H' => 'HH24',
            '%i' => 'MI',
            '%s' => 'SS',
            '%M' => 'Month',
            '%b' => 'Mon',
            '%D' => 'DDth',
            '%e' => 'FMDD',
            '%W' => 'Day',
            '%a' => 'Dy',
            '%j' => 'DDD',
        ];

        return str_replace(array_keys($replacements), array_values($replacements), $format);
    }
}

<?php

use Illuminate\Database\Query\Expression;
use Illuminate\Support\Facades\DB;
use Webkul\Core\Support\DbHelper;

it('generates fieldOrderBy expression for current driver', function () {
    $result = DbHelper::fieldOrderBy('id', [3, 1, 2]);

    expect($result)->toBeInstanceOf(Expression::class);

    $sql = $result->getValue(DB::connection()->getQueryGrammar());

    if (DB::getDriverName() === 'pgsql') {
        expect($sql)->toContain('array_position(ARRAY[3,1,2], id)');
    } else {
        expect($sql)->toContain('FIELD(id, 3,1,2)');
    }
});

it('generates fieldOrderBy with empty ids', function () {
    $result = DbHelper::fieldOrderBy('id', []);

    expect($result)->toBeInstanceOf(Expression::class);

    $sql = $result->getValue(DB::connection()->getQueryGrammar());

    expect($sql)->toBe('0');
});

it('generates groupConcat for current driver', function () {
    $result = DbHelper::groupConcat('name', ',');

    expect($result)->toBeString();

    if (DB::getDriverName() === 'pgsql') {
        expect($result)->toContain('STRING_AGG(name::text');
    } else {
        expect($result)->toContain('GROUP_CONCAT(name SEPARATOR');
    }
});

it('generates groupConcat with custom separator', function () {
    $result = DbHelper::groupConcat('method', '|');

    expect($result)->toBeString();

    if (DB::getDriverName() === 'pgsql') {
        expect($result)->toContain("STRING_AGG(method::text, '|')");
    } else {
        expect($result)->toContain('GROUP_CONCAT(method SEPARATOR "|")');
    }
});

it('generates findInSet for current driver', function () {
    $result = DbHelper::findInSet('?', 'terms');

    expect($result)->toBeString();

    if (DB::getDriverName() === 'pgsql') {
        expect($result)->toBe("? = ANY(string_to_array(terms, ','))");
    } else {
        expect($result)->toBe('FIND_IN_SET(?, terms)');
    }
});

it('generates dateFormat for current driver', function () {
    $result = DbHelper::dateFormat('created_at', '%Y-%m-%d');

    expect($result)->toBeString();

    if (DB::getDriverName() === 'pgsql') {
        expect($result)->toBe("TO_CHAR(created_at, 'YYYY-MM-DD')");
    } else {
        expect($result)->toBe('DATE_FORMAT(created_at, "%Y-%m-%d")');
    }
});

it('generates dateFormat with month-day format', function () {
    $result = DbHelper::dateFormat('date_of_birth', '%m-%d');

    expect($result)->toBeString();

    if (DB::getDriverName() === 'pgsql') {
        expect($result)->toBe("TO_CHAR(date_of_birth, 'MM-DD')");
    } else {
        expect($result)->toBe('DATE_FORMAT(date_of_birth, "%m-%d")');
    }
});

it('generates ifExpression for current driver', function () {
    $result = DbHelper::ifExpression('age > 18', "'adult'", "'minor'");

    expect($result)->toBeString();

    if (DB::getDriverName() === 'pgsql') {
        expect($result)->toBe("CASE WHEN age > 18 THEN 'adult' ELSE 'minor' END");
    } else {
        expect($result)->toBe("IF(age > 18, 'adult', 'minor')");
    }
});

it('generates jsonExtractInt for current driver', function () {
    $result = DbHelper::jsonExtractInt('summary', 'created');

    expect($result)->toBeString();

    if (DB::getDriverName() === 'pgsql') {
        expect($result)->toBe("(summary->>'created')::integer");
    } else {
        expect($result)->toBe('json_unquote(json_extract(summary, \'$."created"\'))');
    }
});

it('generates concatWs for both drivers', function () {
    $result = DbHelper::concatWs(' ', ['first_name', 'last_name']);

    expect($result)->toBeString();
    expect($result)->toBe("CONCAT_WS(' ', first_name, last_name)");
});

it('generates reportingGroupByMonth for current driver', function () {
    $result = DbHelper::reportingGroupByMonth('created_at');

    expect($result)->toBeString();

    if (DB::getDriverName() === 'pgsql') {
        expect($result)->toBe('EXTRACT(MONTH FROM created_at)');
    } else {
        expect($result)->toBe('MONTH(created_at)');
    }
});

it('generates reportingGroupByIsoWeek for current driver', function () {
    $result = DbHelper::reportingGroupByIsoWeek('created_at');

    expect($result)->toBeString();

    if (DB::getDriverName() === 'pgsql') {
        expect($result)->toBe('EXTRACT(WEEK FROM created_at)');
    } else {
        expect($result)->toBe('WEEK(created_at, 3)');
    }
});

it('generates reportingGroupByDayOfYear for current driver', function () {
    $result = DbHelper::reportingGroupByDayOfYear('created_at');

    expect($result)->toBeString();

    if (DB::getDriverName() === 'pgsql') {
        expect($result)->toBe('EXTRACT(DOY FROM created_at)');
    } else {
        expect($result)->toBe('DAYOFYEAR(created_at)');
    }
});

it('generates reportingDayNameEnglish for current driver', function () {
    $result = DbHelper::reportingDayNameEnglish('created_at');

    expect($result)->toBeString();

    if (DB::getDriverName() === 'pgsql') {
        expect($result)->toContain('EXTRACT(DOW FROM created_at)');
        expect($result)->toContain("WHEN 0 THEN 'Sunday'");
        expect($result)->toContain("WHEN 6 THEN 'Saturday'");
    } else {
        expect($result)->toBe('DAYNAME(created_at)');
    }
});

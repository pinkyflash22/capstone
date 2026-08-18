<?php

declare(strict_types=1);

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\DB;

require dirname(__DIR__).'/vendor/autoload.php';
$app = require dirname(__DIR__).'/bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

$columns = DB::select(<<<'SQL'
    select
        c.table_name,
        c.column_name,
        c.data_type,
        c.udt_name,
        c.character_maximum_length,
        c.numeric_precision,
        c.numeric_scale,
        c.datetime_precision,
        c.is_nullable,
        c.column_default,
        c.ordinal_position,
        exists (
            select 1
            from information_schema.table_constraints tc
            join information_schema.key_column_usage kcu
              on tc.constraint_name = kcu.constraint_name
             and tc.constraint_schema = kcu.constraint_schema
            where tc.table_schema = 'public'
              and tc.table_name = c.table_name
              and tc.constraint_type = 'PRIMARY KEY'
              and kcu.column_name = c.column_name
        ) as is_primary
        , exists (
            select 1
            from information_schema.table_constraints utc
            join information_schema.key_column_usage ukcu
              on utc.constraint_name = ukcu.constraint_name
             and utc.constraint_schema = ukcu.constraint_schema
            where utc.table_schema = 'public'
              and utc.table_name = c.table_name
              and utc.constraint_type = 'UNIQUE'
              and ukcu.column_name = c.column_name
              and (
                  select count(*)
                  from information_schema.key_column_usage ukcu_count
                  where ukcu_count.constraint_schema = utc.constraint_schema
                    and ukcu_count.constraint_name = utc.constraint_name
              ) = 1
        ) as is_unique
    from information_schema.columns c
    join information_schema.tables t
      on t.table_schema = c.table_schema
     and t.table_name = c.table_name
     and t.table_type = 'BASE TABLE'
    where c.table_schema = 'public'
    order by c.table_name, c.ordinal_position
SQL);

$foreignKeys = DB::select(<<<'SQL'
    select
        tc.table_name,
        kcu.column_name,
        ccu.table_name as target_table,
        ccu.column_name as target_column,
        tc.constraint_name
    from information_schema.table_constraints tc
    join information_schema.key_column_usage kcu
      on tc.constraint_name = kcu.constraint_name
     and tc.constraint_schema = kcu.constraint_schema
    join information_schema.constraint_column_usage ccu
      on tc.constraint_name = ccu.constraint_name
     and tc.constraint_schema = ccu.constraint_schema
    where tc.constraint_schema = 'public'
      and tc.constraint_type = 'FOREIGN KEY'
    order by tc.table_name, kcu.ordinal_position
SQL);

echo json_encode([
    'columns' => $columns,
    'foreign_keys' => $foreignKeys,
], JSON_THROW_ON_ERROR);

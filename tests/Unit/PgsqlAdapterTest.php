<?php

declare(strict_types=1);

namespace ArtemYurov\DbSync\Tests\Unit;

use ArtemYurov\DbSync\Adapters\PgsqlAdapter;
use PHPUnit\Framework\TestCase;

class PgsqlAdapterTest extends TestCase
{
    protected PgsqlAdapter $adapter;

    protected function setUp(): void
    {
        parent::setUp();
        $this->adapter = new PgsqlAdapter();
    }

    public function test_parse_sql_statements_basic(): void
    {
        $sql = "CREATE TABLE users (id serial PRIMARY KEY);\nCREATE TABLE orders (id serial PRIMARY KEY);";
        $statements = $this->adapter->parseSqlStatements($sql);

        $this->assertCount(2, $statements);
        $this->assertStringContainsString('CREATE TABLE users', $statements[0]);
        $this->assertStringContainsString('CREATE TABLE orders', $statements[1]);
    }

    public function test_parse_sql_statements_skips_comments(): void
    {
        $sql = "-- This is a comment\nCREATE TABLE users (id serial PRIMARY KEY);\n-- Another comment";
        $statements = $this->adapter->parseSqlStatements($sql);

        $this->assertCount(1, $statements);
        $this->assertStringContainsString('CREATE TABLE users', $statements[0]);
    }

    public function test_parse_sql_statements_skips_set_commands(): void
    {
        $sql = "SET statement_timeout = 0;\nSET lock_timeout = 0;\nCREATE TABLE users (id serial PRIMARY KEY);";
        $statements = $this->adapter->parseSqlStatements($sql);

        $this->assertCount(1, $statements);
        $this->assertStringContainsString('CREATE TABLE users', $statements[0]);
    }

    public function test_parse_sql_statements_handles_multiline(): void
    {
        $sql = "CREATE TABLE users (\n    id serial PRIMARY KEY,\n    name varchar(255)\n);";
        $statements = $this->adapter->parseSqlStatements($sql);

        $this->assertCount(1, $statements);
        $this->assertStringContainsString('id serial PRIMARY KEY', $statements[0]);
        $this->assertStringContainsString('name varchar(255)', $statements[0]);
    }

    public function test_parse_sql_statements_handles_empty_input(): void
    {
        $statements = $this->adapter->parseSqlStatements('');
        $this->assertEmpty($statements);
    }

    public function test_parse_sql_statements_skips_pg_catalog(): void
    {
        $sql = "SELECT pg_catalog.set_config('search_path', '', false);\nCREATE TABLE t (id int);";
        $statements = $this->adapter->parseSqlStatements($sql);

        $this->assertCount(1, $statements);
        $this->assertStringContainsString('CREATE TABLE t', $statements[0]);
    }
}

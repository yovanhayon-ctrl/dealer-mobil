<?php

namespace App\Support;

use Illuminate\Database\Connection;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Ekspor seluruh database ke satu file SQL (struktur + isi) tanpa mysqldump, agar jalan di shared hosting
 * dan tidak perlu menulis kata sandi database di perintah. Hasilnya bisa diimpor lewat phpMyAdmin
 * atau `mysql -u user -p nama_db < database.sql`. Mendukung MySQL/MariaDB (production) dan SQLite (test).
 */
class DatabaseDumper
{
    /** Baris per perintah INSERT. */
    private const CHUNK = 200;

    /**
     * @return int jumlah tabel yang diekspor
     */
    public function dump(string $file, ?string $connection = null): int
    {
        $db = DB::connection($connection);
        $handle = fopen($file, 'wb');

        if ($handle === false) {
            throw new RuntimeException("File {$file} tidak bisa ditulis.");
        }

        try {
            $mysql = in_array($db->getDriverName(), ['mysql', 'mariadb'], true);
            $tables = $this->tables($db, $mysql);

            fwrite($handle, '-- Cadangan database '.config('app.name').' '.now()->format('Y-m-d H:i:s')."\n");
            if ($mysql) {
                fwrite($handle, "SET NAMES utf8mb4;\nSET FOREIGN_KEY_CHECKS=0;\n");
            }

            foreach ($tables as $table => $create) {
                fwrite($handle, "\n".'DROP TABLE IF EXISTS '.$this->wrap($table, $mysql).";\n{$create};\n");
                $this->writeRows($handle, $db, $table, $mysql);
            }

            if ($mysql) {
                fwrite($handle, "\nSET FOREIGN_KEY_CHECKS=1;\n");
            }

            return count($tables);
        } finally {
            fclose($handle);
        }
    }

    /**
     * @return array<string, string> nama tabel => perintah CREATE TABLE
     */
    private function tables(Connection $db, bool $mysql): array
    {
        if ($mysql) {
            $tables = [];

            foreach ($db->select("SHOW FULL TABLES WHERE Table_type = 'BASE TABLE'") as $row) {
                $name = array_values((array) $row)[0];
                $tables[$name] = array_values((array) $db->selectOne("SHOW CREATE TABLE `{$name}`"))[1];
            }

            return $tables;
        }

        return collect($db->select("SELECT name, sql FROM sqlite_master WHERE type = 'table' AND name NOT LIKE 'sqlite_%' ORDER BY name"))
            ->mapWithKeys(fn ($row) => [$row->name => $row->sql])
            ->all();
    }

    /**
     * @param  resource  $handle
     */
    private function writeRows($handle, Connection $db, string $table, bool $mysql): void
    {
        $pdo = $db->getPdo();
        $quoted = $this->wrap($table, $mysql);
        $rows = [];
        $columns = null;

        // Urutan stabil tanpa bergantung pada kolom id (tabel pivot / settings tidak punya id).
        foreach ($db->table($table)->cursor() as $row) {
            $row = (array) $row;
            $columns ??= implode(', ', array_map(fn ($column) => $this->wrap($column, $mysql), array_keys($row)));
            $rows[] = '('.implode(', ', array_map(fn ($value) => match (true) {
                $value === null => 'NULL',
                is_bool($value) => $value ? '1' : '0',
                is_int($value), is_float($value) => (string) $value,
                default => $pdo->quote((string) $value),
            }, $row)).')';

            if (count($rows) === self::CHUNK) {
                fwrite($handle, "INSERT INTO {$quoted} ({$columns}) VALUES\n".implode(",\n", $rows).";\n");
                $rows = [];
            }
        }

        if ($rows !== []) {
            fwrite($handle, "INSERT INTO {$quoted} ({$columns}) VALUES\n".implode(",\n", $rows).";\n");
        }
    }

    private function wrap(string $name, bool $mysql): string
    {
        return $mysql ? '`'.str_replace('`', '``', $name).'`' : '"'.str_replace('"', '""', $name).'"';
    }
}

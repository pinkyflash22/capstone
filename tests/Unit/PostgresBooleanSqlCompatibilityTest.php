<?php

namespace Tests\Unit;

use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use Tests\TestCase;

class PostgresBooleanSqlCompatibilityTest extends TestCase
{
    public function test_boolean_columns_are_not_compared_to_integer_literals_in_application_sql(): void
    {
        $booleanColumns = [];

        foreach ($this->phpFiles(database_path('migrations')) as $file) {
            preg_match_all("/->boolean\(['\"]([^'\"]+)['\"]\)/", file_get_contents($file), $matches);
            $booleanColumns = array_merge($booleanColumns, $matches[1]);
        }

        $booleanColumns = array_values(array_unique($booleanColumns));
        sort($booleanColumns);
        $this->assertNotEmpty($booleanColumns, 'No boolean migration columns were discovered.');

        $columnPattern = implode('|', array_map(
            static fn (string $column): string => preg_quote($column, '/'),
            $booleanColumns
        ));
        $numericComparison = '/\b(?:'.$columnPattern.')\b\s*(?:=|<>|!=)\s*[01]\b/i';
        $violations = [];

        foreach ([app_path(), base_path('routes')] as $directory) {
            foreach ($this->phpFiles($directory) as $file) {
                foreach (file($file) as $lineNumber => $line) {
                    if (preg_match($numericComparison, $line)) {
                        $violations[] = str_replace(base_path().DIRECTORY_SEPARATOR, '', $file).':'.($lineNumber + 1);
                    }
                }
            }
        }

        $this->assertSame([], $violations, 'PostgreSQL boolean columns must use TRUE/FALSE, not 1/0: '.implode(', ', $violations));
    }

    /** @return list<string> */
    private function phpFiles(string $directory): array
    {
        $files = [];
        $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($directory));

        foreach ($iterator as $file) {
            if ($file->isFile() && $file->getExtension() === 'php') {
                $files[] = $file->getPathname();
            }
        }

        return $files;
    }
}

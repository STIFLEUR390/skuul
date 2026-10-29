<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

/**
 * Keep the campus scope reachable by name.
 *
 * `SchoolScope::class` in a file that does not import it silently becomes
 * `App\Models\SchoolScope`, and `withoutGlobalScope()` then removes nothing:
 * the query stays narrowed and the bug only shows up as a missing row.
 */
class SchoolScopeImportsTest extends TestCase
{
    public function test_every_file_that_names_the_campus_scope_imports_it(): void
    {
        $missing = [];

        $root = dirname(__DIR__, 2);

        foreach ($this->phpFilesIn($root.'/app', $root.'/database', $root.'/tests') as $file) {
            $source = (string) file_get_contents($file);

            if ($file === __FILE__ || $file === $root.'/app/Models/Scopes/SchoolScope.php' || !str_contains($source, 'SchoolScope::class')) {
                continue;
            }

            if (!str_contains($source, 'use App\\Models\\Scopes\\SchoolScope;')) {
                $missing[] = str_replace($root.'/', '', $file);
            }
        }

        $this->assertSame([], $missing, 'These files name SchoolScope but do not import it.');
    }

    /**
     * @return list<string>
     */
    private function phpFilesIn(string ...$directories): array
    {
        $files = [];

        foreach ($directories as $directory) {
            $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($directory));

            foreach ($iterator as $entry) {
                if ($entry->isFile() && $entry->getExtension() === 'php') {
                    $files[] = $entry->getPathname();
                }
            }
        }

        return $files;
    }
}

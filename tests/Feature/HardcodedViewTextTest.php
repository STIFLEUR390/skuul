<?php

namespace Tests\Feature;

use Tests\TestCase;

class HardcodedViewTextTest extends TestCase
{
    /**
     * Names that stay the same in every language.
     *
     * @var array<int, string>
     */
    private const ALLOWED = [
        'Kola',
        'WhatsApp',
    ];

    public function test_views_hold_no_hardcoded_sentences(): void
    {
        $offenders = [];

        $files = new \RegexIterator(
            new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator(resource_path('views'))),
            '/\.blade\.php$/',
        );

        foreach ($files as $file) {
            /** @var \SplFileInfo $file */
            $path = str_replace(resource_path('views').'/', '', $file->getPathname());

            foreach ($this->hardcodedTexts($file->getPathname()) as $line => $text) {
                if (in_array($text, self::ALLOWED, true)) {
                    continue;
                }

                $offenders[] = "{$path}:{$line} → {$text}";
            }
        }

        $this->assertSame([], $offenders, "Wrap these strings in __():\n".implode("\n", $offenders));
    }

    /**
     * Sentences left as plain text between two HTML tags.
     *
     * @return array<int, string>
     */
    private function hardcodedTexts(string $path): array
    {
        $html = (string) file_get_contents($path);
        $pad = static fn (string $text): string => str_repeat("\n", substr_count($text, "\n"));

        $masked = preg_replace_callback('/\{\{--.*?--\}\}/s', fn (array $match): string => $pad($match[0]), $html);
        $masked = preg_replace_callback('/@php.*?@endphp/s', fn (array $match): string => $pad($match[0]), (string) $masked);
        $masked = preg_replace_callback('/<(script|style|pre|code|textarea)\b.*?<\/\1>/is', fn (array $match): string => $pad($match[0]), (string) $masked);

        $found = [];
        $lines = explode("\n", (string) $masked);

        foreach ($lines as $index => $line) {
            if (!preg_match_all('/>([^<>]+)</', $line, $matches)) {
                continue;
            }

            foreach ($matches[1] as $text) {
                $text = trim((string) preg_replace('/\s+/', ' ', $text));

                if (!$this->isPlainSentence($text)) {
                    continue;
                }

                $found[$index + 1] = $text;
            }
        }

        return $found;
    }

    /**
     * Text that reads as a sentence, rather than an expression or a lone word.
     */
    private function isPlainSentence(string $text): bool
    {
        if ($text === '' || !preg_match('/[a-zA-Z]/', $text)) {
            return false;
        }

        // Blade expressions, PHP operators and entities are code, not copy.
        if (preg_match('/[{}$@=|_\[\]\\\\]|->|::|&#/', $text)) {
            return false;
        }

        $lettered = array_filter(preg_split('/\s+/', $text) ?: [], fn (string $word): bool => (bool) preg_match('/[a-zA-Z]{2,}/', $word));

        return count($lettered) >= 2;
    }
}

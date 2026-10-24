<?php

namespace Tests\Feature;

use App\Traits\FeatureTestTrait;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TranslationCatalogTest extends TestCase
{
    use FeatureTestTrait;
    use RefreshDatabase;

    public function test_the_json_catalogues_hold_the_same_keys(): void
    {
        $this->assertSame([], $this->missingKeys($this->jsonKeys('en'), $this->jsonKeys('fr')), 'Keys missing from lang/fr.json.');
        $this->assertSame([], $this->missingKeys($this->jsonKeys('fr'), $this->jsonKeys('en')), 'Keys missing from lang/en.json.');
    }

    public function test_every_php_catalogue_holds_the_same_keys(): void
    {
        foreach (glob(lang_path('en/*.php')) ?: [] as $englishFile) {
            $group = basename($englishFile, '.php');
            $frenchFile = lang_path("fr/{$group}.php");

            $this->assertFileExists($frenchFile);

            $english = $this->flatten(require $englishFile);
            $french = $this->flatten(require $frenchFile);

            $this->assertSame([], $this->missingKeys($english, $french), "Keys missing from lang/fr/{$group}.php.");
            $this->assertSame([], $this->missingKeys($french, $english), "Keys missing from lang/en/{$group}.php.");
        }
    }

    public function test_a_page_renders_in_french_for_a_person_who_chose_french(): void
    {
        $this->authorized_user([]);
        auth()->user()->forceFill(['locale' => 'fr'])->save();

        $this->get(route('profile.show'))
            ->assertOk()
            ->assertSee(__('Language'))
            ->assertSee('Langue');
    }

    /**
     * @return array<string, string>
     */
    private function jsonKeys(string $locale): array
    {
        /** @var array<string, string> $catalogue */
        $catalogue = json_decode((string) file_get_contents(lang_path("{$locale}.json")), true, flags: JSON_THROW_ON_ERROR);

        return $catalogue;
    }

    /**
     * @param  array<string, mixed>  $catalogue
     * @return array<string, mixed>
     */
    private function flatten(array $catalogue, string $prefix = ''): array
    {
        $flat = [];

        foreach ($catalogue as $key => $value) {
            $path = $prefix === '' ? (string) $key : $prefix.'.'.$key;

            if (is_array($value)) {
                $flat += $this->flatten($value, $path);

                continue;
            }

            $flat[$path] = $value;
        }

        return $flat;
    }

    /**
     * @param  array<string, mixed>  $source
     * @param  array<string, mixed>  $target
     * @return array<int, string>
     */
    private function missingKeys(array $source, array $target): array
    {
        return array_values(array_diff(array_keys($source), array_keys($target)));
    }
}

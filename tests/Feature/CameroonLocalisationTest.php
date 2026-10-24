<?php

namespace Tests\Feature;

use Tests\TestCase;

/**
 * The school lives in Cameroon: its money is the franc and its day is Douala's.
 */
class CameroonLocalisationTest extends TestCase
{
    public function test_the_school_keeps_its_books_in_francs(): void
    {
        $this->assertSame('XAF', config('app.currency'));

        app()->setLocale('fr');
        $this->assertSame('250 000 FCFA', preg_replace('/\s+/u', ' ', money_text(250_000)));

        app()->setLocale('en');
        $this->assertSame('FCFA 250,000', preg_replace('/\s+/u', ' ', money_text(250_000)));
    }

    public function test_the_school_day_starts_in_douala(): void
    {
        $this->assertSame('Africa/Douala', config('app.timezone'));
        $this->assertSame('Africa/Douala', date_default_timezone_get());
    }
}

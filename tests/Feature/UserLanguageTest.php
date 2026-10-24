<?php

namespace Tests\Feature;

use App\Models\Installation;
use App\Models\User;
use App\Traits\FeatureTestTrait;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class UserLanguageTest extends TestCase
{
    use FeatureTestTrait;
    use RefreshDatabase;

    public function test_a_person_with_a_french_preference_reads_french(): void
    {
        $this->readsTheInterfaceIn('fr');
    }

    public function test_a_person_with_an_english_preference_reads_english(): void
    {
        $this->readsTheInterfaceIn('en');
    }

    private function readsTheInterfaceIn(string $locale): void
    {
        $school = $this->workingSchool();
        $user = $this->memberOf($school, User::factory()->create(['locale' => $locale]));

        $this->actingAsMemberOf($school, $user)->get(route('profile.show'))->assertOk();

        $this->assertSame($locale, app()->getLocale());
    }

    public function test_a_person_can_switch_their_language_without_touching_the_installation(): void
    {
        $this->authorized_user([]);
        $user = auth()->user();

        $this->get(route('profile.show'))->assertOk();
        $installationLocale = Installation::withoutGlobalScopes()->value('locale');

        $this->from(route('profile.show'))
            ->post(route('user.language.update'), ['locale' => 'fr'])
            ->assertRedirect(route('profile.show'));

        $this->assertSame('fr', $user->fresh()->locale);
        $this->assertSame($installationLocale, Installation::withoutGlobalScopes()->value('locale'));

        $this->get(route('profile.show'))->assertOk()->assertSee('name="locale"', false);
        $this->assertSame('fr', app()->getLocale());
    }

    public function test_an_unsupported_language_is_rejected(): void
    {
        $this->authorized_user([]);
        $user = auth()->user();

        $this->from(route('profile.show'))
            ->post(route('user.language.update'), ['locale' => 'xx'])
            ->assertRedirect(route('profile.show'))
            ->assertSessionHasErrors('locale');

        $this->assertNull($user->fresh()->locale);
    }

    public function test_the_language_survives_signing_out_and_back_in(): void
    {
        $user = $this->memberOf($this->workingSchool(), User::factory()->create([
            'password' => Hash::make('secret-password'),
            'locale' => 'fr',
        ]));

        $this->post(route('logout'))->assertRedirect();

        $this->post(route('login'), [
            'email' => $user->email,
            'password' => 'secret-password',
        ]);

        $this->get(route('dashboard'))->assertOk();

        $this->assertAuthenticatedAs($user);
        $this->assertSame('fr', app()->getLocale());
    }
}

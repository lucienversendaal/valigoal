<?php

namespace Tests\Feature\Auth;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Laravel\Fortify\Features;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->skipUnlessFortifyHas(Features::registration());
    }

    public function test_registration_screen_can_be_rendered(): void
    {
        $this->get(route('register'))->assertOk();
    }

    public function test_new_valicare_users_can_register_as_deelnemer(): void
    {
        $response = $this->post(route('register.store'), [
            'name' => 'John Doe',
            'email' => 'john.doe@valicare.nl',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $response->assertSessionHasNoErrors();
        $this->assertAuthenticated();

        $user = User::firstWhere('email', 'john.doe@valicare.nl');
        $this->assertSame(UserRole::Deelnemer, $user->role);
        $this->assertNull($user->email_verified_at, 'New users must verify their email.');
    }

    public function test_registration_requires_a_valicare_email_address(): void
    {
        $response = $this->post(route('register.store'), [
            'name' => 'Jane Doe',
            'email' => 'jane@gmail.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $response->assertSessionHasErrors('email');
        $this->assertGuest();
        $this->assertDatabaseMissing('users', ['email' => 'jane@gmail.com']);
    }

    public function test_registration_requires_a_full_name(): void
    {
        $response = $this->post(route('register.store'), [
            'name' => 'Madonna',
            'email' => 'madonna@valicare.nl',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $response->assertSessionHasErrors('name');
        $this->assertGuest();
    }

    public function test_a_verification_email_is_sent_on_registration(): void
    {
        Notification::fake();

        $this->post(route('register.store'), [
            'name' => 'Sven Sample',
            'email' => 'sven@valicare.nl',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $user = User::firstWhere('email', 'sven@valicare.nl');
        Notification::assertSentTo($user, VerifyEmail::class);
    }
}

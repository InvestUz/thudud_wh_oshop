<?php

namespace Tests\Feature;

use Illuminate\Session\TokenMismatchException;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class ExpiredSessionTest extends TestCase
{
    public function test_expired_web_form_redirects_to_login_with_a_message(): void
    {
        Route::middleware('web')->post('/expired-session-test', function (): never {
            throw new TokenMismatchException('CSRF token mismatch.');
        });

        $response = $this->post('/expired-session-test');

        $response->assertRedirect(route('login'));
        $response->assertSessionHasErrors('session');
    }

    public function test_expired_json_request_keeps_the_419_status(): void
    {
        Route::middleware('web')->post('/expired-session-json-test', function (): never {
            throw new TokenMismatchException('CSRF token mismatch.');
        });

        $response = $this->postJson('/expired-session-json-test');

        $response->assertStatus(419);
        $response->assertJsonStructure(['message']);
    }
}

<?php

namespace Tests\Feature;

use Illuminate\Http\Exceptions\PostTooLargeException;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class UploadLimitTest extends TestCase
{
    public function test_oversized_web_upload_redirects_with_a_friendly_flag(): void
    {
        Route::middleware('web')->post('/oversized-upload-test', function (): never {
            throw new PostTooLargeException;
        });

        $response = $this->from('/applications/4')->post('/oversized-upload-test');

        $response->assertRedirect('/applications/4?upload_error=1');
    }

    public function test_oversized_json_upload_returns_a_clear_413_response(): void
    {
        Route::post('/oversized-upload-json-test', function (): never {
            throw new PostTooLargeException;
        });

        $response = $this->postJson('/oversized-upload-json-test');

        $response->assertStatus(413);
        $response->assertJsonStructure(['message']);
    }
}

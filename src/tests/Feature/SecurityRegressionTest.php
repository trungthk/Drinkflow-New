<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\RoomStatus;
use App\Models\Room;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class SecurityRegressionTest extends TestCase
{
    use RefreshDatabase;

    /** External Referer values must never become open redirects. */
    public function test_guest_room_redirect_rejects_external_referer(): void
    {
        $room = Room::create(['name' => 'Security Room', 'slug' => 'security-room', 'status' => RoomStatus::Active]);

        $response = $this->withHeader('Referer', 'https://attacker.example/phishing')
            ->get(route('user.dashboard', $room->slug));

        $response->assertRedirect('/');
        $this->assertStringNotContainsString('attacker.example', (string) $response->headers->get('Location'));
    }
}

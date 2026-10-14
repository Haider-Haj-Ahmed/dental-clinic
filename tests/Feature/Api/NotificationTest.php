<?php

namespace Tests\Feature\Api;

use App\Models\User;
use App\Notifications\InAppNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NotificationTest extends TestCase
{
    use RefreshDatabase;

    private function actingAsOwner(): array
    {
        $user  = User::factory()->owner()->create();
        $token = $user->createToken('test', ['*'])->plainTextToken;
        return [$user, $token];
    }

    public function test_list_notifications_returns_paginated_results(): void
    {
        [$user, $token] = $this->actingAsOwner();

        $user->notify(new InAppNotification('test.event', 'Title 1', 'Body 1'));
        $user->notify(new InAppNotification('test.event', 'Title 2', 'Body 2'));

        $response = $this->withToken($token)
             ->getJson('/api/v1/notifications')
             ->assertOk()
             ->assertJsonStructure(['data', 'unread_count', 'total']);

        $this->assertEquals(2, $response->json('unread_count'));
        $this->assertCount(2, $response->json('data'));
    }

    public function test_mark_notification_as_read(): void
    {
        [$user, $token] = $this->actingAsOwner();
        $user->notify(new InAppNotification('test.event', 'Title', 'Body'));

        $notificationId = $user->fresh()->notifications->first()->id;

        $this->withToken($token)
             ->postJson("/api/v1/notifications/{$notificationId}/read")
             ->assertOk()
             ->assertJsonPath('unread_count', 0);

        $this->assertNotNull($user->fresh()->notifications->first()->read_at);
    }

    public function test_mark_all_notifications_as_read(): void
    {
        [$user, $token] = $this->actingAsOwner();

        $user->notify(new InAppNotification('test.event', 'Title 1', 'Body 1'));
        $user->notify(new InAppNotification('test.event', 'Title 2', 'Body 2'));

        $this->withToken($token)
             ->postJson('/api/v1/notifications/read-all')
             ->assertOk()
             ->assertJsonPath('unread_count', 0);

        $this->assertEquals(0, $user->fresh()->unreadNotifications()->count());
    }

    public function test_delete_notification(): void
    {
        [$user, $token] = $this->actingAsOwner();
        $user->notify(new InAppNotification('test.event', 'Title', 'Body'));

        $notificationId = $user->fresh()->notifications->first()->id;

        $this->withToken($token)
             ->deleteJson("/api/v1/notifications/{$notificationId}")
             ->assertOk();

        $this->assertEquals(0, $user->fresh()->notifications()->count());
    }

    public function test_delete_read_notifications(): void
    {
        [$user, $token] = $this->actingAsOwner();

        $user->notify(new InAppNotification('test.event', 'Unread', 'Body'));
        $user->notify(new InAppNotification('test.event', 'Read', 'Body'));
        $user->notifications->last()->markAsRead();

        $this->withToken($token)
             ->deleteJson('/api/v1/notifications/read')
             ->assertOk();

        $this->assertEquals(1, $user->fresh()->notifications()->count());
    }
}

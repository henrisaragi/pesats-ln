<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\ConversationStatus;
use App\Enums\NotificationCategory;
use App\Events\ChatMessageSent;
use App\Models\Conversation;
use App\Models\Entitas\Entitas;
use App\Models\User;
use App\Notifications\AppNotification;
use Database\Seeders\MasterDataPermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class ChatTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);
        $this->seed(MasterDataPermissionSeeder::class);
    }

    private function makeUser(?string $entityType = null, ?string $role = null): User
    {
        $entityId = null;

        if ($entityType !== null) {
            $entityId = Entitas::create([
                'entity_type' => $entityType,
                'name' => 'Entitas Test',
                'address' => 'Jl. Test',
            ])->id;
        }

        $user = User::create([
            'name' => 'User Test',
            'email' => uniqid('user', true).'@example.test',
            'username' => uniqid('user', true),
            'password' => bcrypt('password'),
            'entity_id' => $entityId,
            'is_active' => true,
            'onboarding_completed' => true,
        ]);

        if ($role !== null) {
            $user->assignRole($role);
        } elseif ($entityType !== null) {
            $user->assignRole($entityType);
        }

        return $user;
    }

    public function test_entity_user_can_start_a_conversation_and_notifies_the_respond_pool(): void
    {
        Notification::fake();
        Event::fake([ChatMessageSent::class]);

        $initiator = $this->makeUser('perusahaan');
        $staff = $this->makeUser(null, 'upt-ksda');
        $staff->givePermissionTo('chat.respond');

        $response = $this->actingAs($initiator)->postJson(route('chat.store'), ['body' => 'Halo, saya butuh bantuan.']);

        $response->assertCreated();

        $this->assertDatabaseHas('conversations', [
            'initiator_id' => $initiator->id,
            'status' => ConversationStatus::Waiting->value,
        ]);

        Notification::assertSentTo($staff, AppNotification::class);
        Event::assertDispatched(ChatMessageSent::class);
    }

    public function test_upt_ksda_user_cannot_start_a_conversation(): void
    {
        $user = $this->makeUser(null, 'upt-ksda');
        $user->givePermissionTo('chat.respond');

        $this->actingAs($user)->postJson(route('chat.store'), ['body' => 'Halo'])->assertForbidden();
    }

    public function test_user_without_entity_cannot_start_a_conversation(): void
    {
        $user = $this->makeUser();
        $user->givePermissionTo('chat.start');

        $this->actingAs($user)->postJson(route('chat.store'), ['body' => 'Halo'])->assertForbidden();
    }

    public function test_first_reply_claims_the_conversation_and_locks_out_other_staff(): void
    {
        Notification::fake();

        $initiator = $this->makeUser('perusahaan');
        $staffA = $this->makeUser(null, 'upt-ksda');
        $staffA->givePermissionTo('chat.respond');
        $staffB = $this->makeUser(null, 'upt-ksda');
        $staffB->givePermissionTo('chat.respond');

        $conversation = Conversation::create(['initiator_id' => $initiator->id, 'status' => ConversationStatus::Waiting]);
        $conversation->messages()->create(['sender_id' => $initiator->id, 'body' => 'Halo']);

        // Both staff can see the waiting conversation before anyone claims it.
        $this->actingAs($staffA)->getJson(route('chat.messages', $conversation))->assertOk();
        $this->actingAs($staffB)->getJson(route('chat.messages', $conversation))->assertOk();

        $this->actingAs($staffA)
            ->postJson(route('chat.send', $conversation), ['body' => 'Halo, ada yang bisa saya bantu?'])
            ->assertOk();

        $conversation->refresh();
        $this->assertSame(ConversationStatus::Active, $conversation->status);
        $this->assertSame($staffA->id, $conversation->claimed_by);

        // Staff B lost visibility now that it's claimed by staff A.
        $this->actingAs($staffB)->getJson(route('chat.messages', $conversation))->assertForbidden();
        $this->actingAs($staffB)->postJson(route('chat.send', $conversation), ['body' => 'Halo juga'])->assertForbidden();

        // Initiator still sees it and can reply.
        $this->actingAs($initiator)->getJson(route('chat.messages', $conversation))->assertOk();

        Notification::assertSentTo($initiator, AppNotification::class);
    }

    public function test_only_participants_can_end_the_conversation_and_closed_conversations_reject_new_messages(): void
    {
        $initiator = $this->makeUser('perusahaan');
        $staff = $this->makeUser(null, 'upt-ksda');
        $staff->givePermissionTo('chat.respond');
        $bystander = $this->makeUser(null, 'upt-ksda');
        $bystander->givePermissionTo('chat.respond');

        $conversation = Conversation::create([
            'initiator_id' => $initiator->id,
            'claimed_by' => $staff->id,
            'status' => ConversationStatus::Active,
        ]);
        $conversation->messages()->create(['sender_id' => $initiator->id, 'body' => 'Halo']);

        $this->actingAs($bystander)->postJson(route('chat.end', $conversation))->assertForbidden();

        $this->actingAs($initiator)->postJson(route('chat.end', $conversation))->assertOk();

        $conversation->refresh();
        $this->assertSame(ConversationStatus::Closed, $conversation->status);
        $this->assertSame($initiator->id, $conversation->ended_by);
        $this->assertNotNull($conversation->ended_at);

        $this->actingAs($initiator)
            ->postJson(route('chat.send', $conversation), ['body' => 'Masih ada?'])
            ->assertForbidden();
    }

    public function test_starting_a_new_conversation_after_closing_creates_a_separate_session(): void
    {
        $initiator = $this->makeUser('perusahaan');

        $first = Conversation::create(['initiator_id' => $initiator->id, 'status' => ConversationStatus::Closed, 'ended_at' => now(), 'ended_by' => $initiator->id]);

        $this->actingAs($initiator)
            ->postJson(route('chat.store'), ['body' => 'Percakapan baru'])
            ->assertCreated();

        $this->assertSame(2, Conversation::where('initiator_id', $initiator->id)->count());
        $this->assertDatabaseHas('conversations', ['id' => $first->id, 'status' => ConversationStatus::Closed->value]);
    }

    public function test_list_buckets_are_scoped_correctly(): void
    {
        $initiatorA = $this->makeUser('perusahaan');
        $initiatorB = $this->makeUser('perorangan');
        $staff = $this->makeUser(null, 'upt-ksda');
        $staff->givePermissionTo('chat.respond');

        $waitingA = Conversation::create(['initiator_id' => $initiatorA->id, 'status' => ConversationStatus::Waiting]);
        Conversation::create(['initiator_id' => $initiatorB->id, 'status' => ConversationStatus::Waiting]);
        $activeA = Conversation::create(['initiator_id' => $initiatorA->id, 'claimed_by' => $staff->id, 'status' => ConversationStatus::Active]);

        $asInitiator = $this->actingAs($initiatorA)->getJson(route('chat.list'))->json();
        $this->assertEmpty($asInitiator['queue']);
        $this->assertCount(2, $asInitiator['mine']);

        $asStaff = $this->actingAs($staff)->getJson(route('chat.list'))->json();
        $this->assertCount(2, $asStaff['queue']);
        $this->assertContains($waitingA->id, array_column($asStaff['queue'], 'id'));
        $this->assertCount(1, $asStaff['mine']);
        $this->assertSame($activeA->id, $asStaff['mine'][0]['id']);
    }

    public function test_opening_a_conversation_marks_incoming_messages_as_read(): void
    {
        $initiator = $this->makeUser('perusahaan');
        $staff = $this->makeUser(null, 'upt-ksda');
        $staff->givePermissionTo('chat.respond');

        $conversation = Conversation::create([
            'initiator_id' => $initiator->id,
            'claimed_by' => $staff->id,
            'status' => ConversationStatus::Active,
        ]);
        $fromInitiator = $conversation->messages()->create(['sender_id' => $initiator->id, 'body' => 'Halo']);
        $fromStaff = $conversation->messages()->create(['sender_id' => $staff->id, 'body' => 'Halo juga']);

        // Staff hasn't opened it yet - the initiator's message is unread from staff's side.
        $listBefore = $this->actingAs($staff)->getJson(route('chat.list'))->json();
        $this->assertSame(1, $listBefore['mine'][0]['unread_count']);

        // Opening it marks the initiator's message as read (but not staff's own message).
        $messagesPayload = $this->actingAs($staff)->getJson(route('chat.messages', $conversation))->json();
        $this->assertTrue(collect($messagesPayload['messages'])->firstWhere('id', $fromInitiator->id)['read']);

        $fromInitiator->refresh();
        $fromStaff->refresh();
        $this->assertNotNull($fromInitiator->read_at);
        $this->assertNull($fromStaff->read_at);

        $listAfter = $this->actingAs($staff)->getJson(route('chat.list'))->json();
        $this->assertSame(0, $listAfter['mine'][0]['unread_count']);

        // From the initiator's side, staff's message is still unread until they open it too.
        $initiatorList = $this->actingAs($initiator)->getJson(route('chat.list'))->json();
        $this->assertSame(1, $initiatorList['mine'][0]['unread_count']);
    }

    public function test_general_notifications_no_longer_broadcast(): void
    {
        $notification = new AppNotification(NotificationCategory::InformasiLain, 'Judul', 'Pesan');

        $this->assertSame(['database'], $notification->via($this->makeUser('perusahaan')));
    }
}

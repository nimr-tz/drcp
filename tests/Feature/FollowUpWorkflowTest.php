<?php

namespace Tests\Feature;

use App\Enums\ItemStatus;
use App\Enums\UserRole;
use App\Models\FollowUpItem;
use App\Models\ItemDocument;
use App\Models\Section;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class FollowUpWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_secretary_can_create_a_follow_up_item(): void
    {
        $section = Section::create(['name' => 'Research Section']);
        $secretary = User::factory()->create([
            'role' => 'secretary',
            'section_id' => null,
        ]);
        $head = User::factory()->create([
            'role' => 'head_of_section',
            'section_id' => $section->id,
        ]);

        $response = $this->actingAs($secretary)->post(route('items.store'), [
            'item_type' => 'letter',
            'title' => 'Quarterly letter',
            'description' => 'Need a response.',
            'eoffice_reference' => 'EO-100',
            'source' => 'HQ',
            'received_at' => now()->toDateString(),
            'priority' => 'high',
            'status' => 'assigned',
            'current_owner_id' => $head->id,
            'section_id' => $section->id,
            'next_action' => 'Prepare the response.',
            'next_follow_up_date' => now()->addDay()->toDateString(),
            'due_date' => now()->addDays(2)->toDateString(),
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('follow_up_items', [
            'title' => 'Quarterly letter',
            'current_owner_id' => $head->id,
            'eoffice_reference' => 'EO-100',
        ]);
        $this->assertDatabaseHas('item_histories', [
            'event_type' => 'created',
            'action_summary' => 'Item created',
        ]);
    }

    public function test_head_of_section_can_only_update_items_currently_assigned_to_them(): void
    {
        $section = Section::create(['name' => 'Coordination Section']);
        $head = User::factory()->create([
            'role' => 'head_of_section',
            'section_id' => $section->id,
        ]);
        $otherHead = User::factory()->create([
            'role' => 'head_of_section',
            'section_id' => $section->id,
        ]);

        $ownedItem = FollowUpItem::create([
            'reference_code' => 'DRCP-2026-0001',
            'item_type' => 'call',
            'title' => 'Owned item',
            'received_at' => now()->toDateString(),
            'priority' => 'medium',
            'status' => 'in_progress',
            'current_owner_id' => $head->id,
            'section_id' => $section->id,
            'next_action' => 'Call back.',
            'next_follow_up_date' => now()->addDay()->toDateString(),
            'last_updated_at' => now(),
        ]);

        $otherItem = FollowUpItem::create([
            'reference_code' => 'DRCP-2026-0002',
            'item_type' => 'meeting',
            'title' => 'Other item',
            'received_at' => now()->toDateString(),
            'priority' => 'medium',
            'status' => 'assigned',
            'current_owner_id' => $otherHead->id,
            'section_id' => $section->id,
            'next_action' => 'Prepare notes.',
            'next_follow_up_date' => now()->addDay()->toDateString(),
            'last_updated_at' => now(),
        ]);

        $this->actingAs($head)->put(route('items.update', $ownedItem), [
            'status' => 'waiting_external_response',
            'section_id' => $section->id,
            'next_action' => 'Wait for finance feedback.',
            'next_follow_up_date' => now()->addDays(2)->toDateString(),
            'due_date' => now()->addDays(3)->toDateString(),
        ])->assertRedirect();

        $this->assertDatabaseHas('follow_up_items', [
            'id' => $ownedItem->id,
            'status' => 'waiting_external_response',
        ]);

        $this->actingAs($head)->put(route('items.update', $otherItem), [
            'status' => 'in_progress',
            'section_id' => $section->id,
            'next_action' => 'Try update.',
            'next_follow_up_date' => now()->addDay()->toDateString(),
        ])->assertForbidden();
    }

    public function test_secretary_can_transfer_item_to_director_desk_and_close_it(): void
    {
        $section = Section::create(['name' => 'Promotion Section']);
        $secretary = User::factory()->create(['role' => 'secretary']);
        $head = User::factory()->create([
            'role' => 'head_of_section',
            'section_id' => $section->id,
        ]);

        $item = FollowUpItem::create([
            'reference_code' => 'DRCP-2026-0003',
            'item_type' => 'visit',
            'title' => 'Site visit follow-up',
            'received_at' => now()->toDateString(),
            'priority' => 'high',
            'status' => 'in_progress',
            'current_owner_id' => $head->id,
            'section_id' => $section->id,
            'next_action' => 'Share visit brief.',
            'next_follow_up_date' => now()->addDay()->toDateString(),
            'last_updated_at' => now(),
        ]);

        $this->actingAs($secretary)->post(route('items.transfer', $item), [
            'current_owner_id' => $secretary->id,
            'status' => 'at_director',
            'next_action' => 'Update the director.',
            'next_follow_up_date' => now()->addDay()->toDateString(),
            'notes' => 'Taken to director desk.',
        ])->assertRedirect();

        $item->refresh();

        $this->assertSame(ItemStatus::AtDirector, $item->status);
        $this->assertSame($secretary->id, $item->current_owner_id);

        $this->actingAs($secretary)->post(route('items.close', $item), [
            'status' => 'closed',
            'closure_note' => 'Director approved and matter is complete.',
        ])->assertRedirect();

        $this->assertDatabaseHas('follow_up_items', [
            'id' => $item->id,
            'status' => 'closed',
        ]);
        $this->assertDatabaseHas('item_histories', [
            'follow_up_item_id' => $item->id,
            'event_type' => 'transfer',
        ]);
        $this->assertDatabaseHas('item_histories', [
            'follow_up_item_id' => $item->id,
            'event_type' => 'closed',
        ]);
    }

    public function test_current_owner_can_upload_and_download_documents_for_an_item(): void
    {
        Storage::fake('local');

        $section = Section::create(['name' => 'Research Section']);
        $head = User::factory()->create([
            'role' => 'head_of_section',
            'section_id' => $section->id,
        ]);

        $item = FollowUpItem::create([
            'reference_code' => 'DRCP-2026-0004',
            'item_type' => 'letter',
            'title' => 'Document test item',
            'received_at' => now()->toDateString(),
            'priority' => 'medium',
            'status' => 'assigned',
            'current_owner_id' => $head->id,
            'section_id' => $section->id,
            'next_action' => 'Attach the supporting document.',
            'next_follow_up_date' => now()->addDay()->toDateString(),
            'last_updated_at' => now(),
        ]);

        $upload = $this->actingAs($head)->post(route('items.documents.store', $item), [
            'document' => UploadedFile::fake()->create('brief.pdf', 256, 'application/pdf'),
            'category' => 'draft_response',
            'document_label' => 'Draft response',
            'is_primary' => '1',
            'description' => 'Brief for review',
        ]);

        $upload->assertRedirect();
        $this->assertDatabaseHas('item_documents', [
            'follow_up_item_id' => $item->id,
            'original_name' => 'brief.pdf',
            'category' => 'draft_response',
            'document_label' => 'Draft response',
            'version_number' => 1,
            'is_primary' => true,
        ]);

        $document = $item->documents()->first();

        Storage::disk('local')->assertExists($document->storage_path);

        $this->actingAs($head)
            ->get(route('items.documents.download', [$item, $document]))
            ->assertOk();

        $this->actingAs($head)
            ->get(route('items.documents.preview', [$item, $document]))
            ->assertOk()
            ->assertHeader('Content-Type', 'application/pdf');
    }

    public function test_document_versions_increment_and_main_document_can_change(): void
    {
        Storage::fake('local');

        $section = Section::create(['name' => 'Promotion Section']);
        $secretary = User::factory()->create(['role' => 'secretary']);

        $item = FollowUpItem::create([
            'reference_code' => 'DRCP-2026-0005',
            'item_type' => 'letter',
            'title' => 'Versioned document item',
            'received_at' => now()->toDateString(),
            'priority' => 'high',
            'status' => 'assigned',
            'current_owner_id' => $secretary->id,
            'section_id' => $section->id,
            'next_action' => 'Prepare response versions.',
            'next_follow_up_date' => now()->addDay()->toDateString(),
            'last_updated_at' => now(),
        ]);

        $this->actingAs($secretary)->post(route('items.documents.store', $item), [
            'document' => UploadedFile::fake()->create('draft-v1.pdf', 100, 'application/pdf'),
            'category' => 'draft_response',
            'document_label' => 'Draft response',
            'is_primary' => '1',
        ])->assertRedirect();

        $this->actingAs($secretary)->post(route('items.documents.store', $item), [
            'document' => UploadedFile::fake()->create('draft-v2.pdf', 120, 'application/pdf'),
            'category' => 'draft_response',
            'document_label' => 'Draft response',
        ])->assertRedirect();

        $this->assertDatabaseHas('item_documents', [
            'follow_up_item_id' => $item->id,
            'document_label' => 'Draft response',
            'version_number' => 2,
        ]);

        $secondVersion = ItemDocument::query()
            ->where('follow_up_item_id', $item->id)
            ->where('version_number', 2)
            ->firstOrFail();

        $this->actingAs($secretary)
            ->post(route('items.documents.primary', [$item, $secondVersion]))
            ->assertRedirect();

        $this->assertDatabaseHas('item_documents', [
            'id' => $secondVersion->id,
            'is_primary' => true,
        ]);

        $this->assertSame(1, ItemDocument::query()
            ->where('follow_up_item_id', $item->id)
            ->where('is_primary', true)
            ->count());
    }

    public function test_non_secretary_cannot_create_an_item(): void
    {
        $section = Section::create(['name' => 'Operations Section']);
        $head = User::factory()->create([
            'role' => UserRole::HeadOfSection->value,
            'section_id' => $section->id,
        ]);

        $this->actingAs($head)->get(route('items.create'))->assertForbidden();

        $this->actingAs($head)->post(route('items.store'), [
            'item_type' => 'letter',
            'title' => 'Should be rejected',
            'received_at' => now()->toDateString(),
            'priority' => 'medium',
            'status' => 'assigned',
            'current_owner_id' => $head->id,
            'next_action' => 'Noop',
            'next_follow_up_date' => now()->addDay()->toDateString(),
        ])->assertForbidden();
    }

    public function test_invalid_status_transition_is_rejected(): void
    {
        $section = Section::create(['name' => 'Coordination Section']);
        $secretary = User::factory()->create(['role' => UserRole::Secretary->value]);
        $head = User::factory()->create([
            'role' => UserRole::HeadOfSection->value,
            'section_id' => $section->id,
        ]);

        $item = FollowUpItem::create([
            'reference_code' => 'DRCP-2026-0010',
            'item_type' => 'letter',
            'title' => 'Transition test',
            'received_at' => now()->toDateString(),
            'priority' => 'medium',
            'status' => ItemStatus::New->value,
            'current_owner_id' => $secretary->id,
            'section_id' => $section->id,
            'next_action' => 'Act.',
            'next_follow_up_date' => now()->addDay()->toDateString(),
            'last_updated_at' => now(),
        ]);

        // New → completed is not allowed
        $this->actingAs($secretary)->put(route('items.update', $item), [
            'status' => ItemStatus::Completed->value,
            'next_action' => 'Done',
            'next_follow_up_date' => now()->addDay()->toDateString(),
        ])->assertSessionHasErrors('status');

        $this->assertDatabaseHas('follow_up_items', [
            'id' => $item->id,
            'status' => ItemStatus::New->value,
        ]);
    }

    public function test_closed_item_cannot_be_updated_or_transferred(): void
    {
        $section = Section::create(['name' => 'Promotion Section']);
        $secretary = User::factory()->create(['role' => UserRole::Secretary->value]);
        $head = User::factory()->create([
            'role' => UserRole::HeadOfSection->value,
            'section_id' => $section->id,
        ]);

        $item = FollowUpItem::create([
            'reference_code' => 'DRCP-2026-0011',
            'item_type' => 'internal_task',
            'title' => 'Closed item',
            'received_at' => now()->toDateString(),
            'priority' => 'low',
            'status' => ItemStatus::Closed->value,
            'current_owner_id' => $secretary->id,
            'section_id' => $section->id,
            'next_action' => 'N/A',
            'next_follow_up_date' => now()->toDateString(),
            'last_updated_at' => now(),
            'closed_at' => now(),
            'closure_note' => 'Done.',
        ]);

        $this->actingAs($secretary)->put(route('items.update', $item), [
            'status' => ItemStatus::InProgress->value,
            'next_action' => 'Reopen',
            'next_follow_up_date' => now()->addDay()->toDateString(),
        ])->assertSessionHasErrors('status');

        $this->actingAs($secretary)->post(route('items.transfer', $item), [
            'current_owner_id' => $head->id,
            'status' => ItemStatus::Assigned->value,
            'next_action' => 'Act',
            'next_follow_up_date' => now()->addDay()->toDateString(),
        ])->assertSessionHasErrors('status');
    }

    public function test_head_of_section_cannot_route_item_to_director_desk(): void
    {
        $section = Section::create(['name' => 'Research Section']);
        $head = User::factory()->create([
            'role' => UserRole::HeadOfSection->value,
            'section_id' => $section->id,
        ]);
        $secretary = User::factory()->create(['role' => UserRole::Secretary->value]);

        $item = FollowUpItem::create([
            'reference_code' => 'DRCP-2026-0012',
            'item_type' => 'letter',
            'title' => 'Director routing test',
            'received_at' => now()->toDateString(),
            'priority' => 'high',
            'status' => ItemStatus::InProgress->value,
            'current_owner_id' => $head->id,
            'section_id' => $section->id,
            'next_action' => 'Draft response.',
            'next_follow_up_date' => now()->addDay()->toDateString(),
            'last_updated_at' => now(),
        ]);

        $this->actingAs($head)->post(route('items.transfer', $item), [
            'current_owner_id' => $secretary->id,
            'status' => ItemStatus::AtDirector->value,
            'next_action' => 'For director',
            'next_follow_up_date' => now()->addDay()->toDateString(),
        ])->assertSessionHasErrors('current_owner_id');
    }

    public function test_director_can_view_items_at_director_status(): void
    {
        $section = Section::create(['name' => 'Operations Section']);
        $secretary = User::factory()->create(['role' => UserRole::Secretary->value]);
        $director = User::factory()->create(['role' => UserRole::Director->value]);

        $item = FollowUpItem::create([
            'reference_code' => 'DRCP-2026-0013',
            'item_type' => 'call',
            'title' => 'Director visibility test',
            'received_at' => now()->toDateString(),
            'priority' => 'high',
            'status' => ItemStatus::AtDirector->value,
            'current_owner_id' => $secretary->id,
            'section_id' => $section->id,
            'next_action' => 'Awaiting decision.',
            'next_follow_up_date' => now()->addDay()->toDateString(),
            'last_updated_at' => now(),
        ]);

        $this->actingAs($director)->get(route('items.show', $item))->assertOk();
    }
}

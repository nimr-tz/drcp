<?php

namespace Database\Seeders;

use App\Enums\ItemStatus;
use App\Enums\UserRole;
use App\Models\FollowUpItem;
use App\Models\ItemHistory;
use App\Models\Section;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DemoDataSeeder extends Seeder
{
    public function run(): void
    {
        // Sections
        $coordination = Section::create([
            'name' => 'Coordination Section',
            'description' => 'Coordinates inter-office actions and deadlines.',
        ]);

        $promotion = Section::create([
            'name' => 'Promotion Section',
            'description' => 'Oversees communication, visibility, and outreach follow-ups.',
        ]);

        // Users
        $nyanda = User::create([
            'name' => 'Nyanda',
            'email' => 'nyanda@drcp.test',
            'role' => UserRole::Director->value,
            'password' => Hash::make('Nyanda2026!'),
        ]);

        $jackline = User::create([
            'name' => 'Jackline',
            'email' => 'jackline@drcp.test',
            'role' => UserRole::Secretary->value,
            'password' => Hash::make('Jackline2026!'),
        ]);

        $mazigo = User::create([
            'name' => 'Mazigo',
            'email' => 'mazigo@drcp.test',
            'role' => UserRole::HeadOfSection->value,
            'section_id' => $coordination->id,
            'password' => Hash::make('Mazigo2026!'),
        ]);

        $maryWinnie = User::create([
            'name' => 'Mary-Winnie',
            'email' => 'marywinnie@drcp.test',
            'role' => UserRole::HeadOfSection->value,
            'section_id' => $promotion->id,
            'password' => Hash::make('MaryWinnie2026!'),
        ]);

        // Demo items

        $letter = FollowUpItem::create([
            'reference_code' => 'DRCP-2026-0001',
            'item_type' => 'letter',
            'title' => 'Letter requesting quarterly coordination brief',
            'description' => 'Incoming correspondence requiring a response and director review.',
            'eoffice_reference' => 'EO-DRCP-44321',
            'source' => 'Ministry HQ',
            'received_at' => now()->subDays(4)->toDateString(),
            'priority' => 'high',
            'status' => ItemStatus::Assigned->value,
            'current_owner_id' => $mazigo->id,
            'section_id' => $coordination->id,
            'next_action' => 'Prepare the draft brief and return to Jackline for director review.',
            'next_follow_up_date' => now()->addDay()->toDateString(),
            'due_date' => now()->addDays(3)->toDateString(),
            'last_updated_at' => now()->subDay(),
        ]);

        ItemHistory::create([
            'follow_up_item_id' => $letter->id,
            'user_id' => $jackline->id,
            'event_type' => 'created',
            'action_summary' => 'Matter logged by secretary',
            'notes' => 'Registered from incoming correspondence.',
            'created_at' => now()->subDays(4),
            'updated_at' => now()->subDays(4),
        ]);

        ItemHistory::create([
            'follow_up_item_id' => $letter->id,
            'user_id' => $jackline->id,
            'event_type' => 'transfer',
            'from_user_id' => $jackline->id,
            'to_user_id' => $mazigo->id,
            'old_status' => 'new',
            'new_status' => 'assigned',
            'action_summary' => 'Assigned to Coordination Section',
            'notes' => 'Mazigo to prepare the initial response.',
            'created_at' => now()->subDays(3),
            'updated_at' => now()->subDays(3),
        ]);

        $call = FollowUpItem::create([
            'reference_code' => 'DRCP-2026-0002',
            'item_type' => 'call',
            'title' => 'Follow up with Finance Office on workshop clearance',
            'description' => 'Phone follow-up for pending clearance — director to decide on escalation.',
            'source' => 'Internal coordination',
            'received_at' => now()->subDays(2)->toDateString(),
            'priority' => 'medium',
            'status' => ItemStatus::AtDirector->value,
            'current_owner_id' => $jackline->id,
            'section_id' => $coordination->id,
            'next_action' => 'Update the director after receiving feedback from Finance.',
            'next_follow_up_date' => now()->toDateString(),
            'due_date' => now()->addDay()->toDateString(),
            'last_updated_at' => now()->subHours(10),
        ]);

        ItemHistory::create([
            'follow_up_item_id' => $call->id,
            'user_id' => $mazigo->id,
            'event_type' => 'created',
            'action_summary' => 'Follow-up task created',
            'notes' => 'Call needed to unblock workshop planning.',
            'created_at' => now()->subDays(2),
            'updated_at' => now()->subDays(2),
        ]);

        ItemHistory::create([
            'follow_up_item_id' => $call->id,
            'user_id' => $mazigo->id,
            'event_type' => 'transfer',
            'from_user_id' => $mazigo->id,
            'to_user_id' => $jackline->id,
            'old_status' => 'in_progress',
            'new_status' => 'at_director',
            'action_summary' => 'Moved to director desk through Jackline',
            'notes' => 'Director Nyanda to decide whether to escalate.',
            'created_at' => now()->subDay(),
            'updated_at' => now()->subDay(),
        ]);

        FollowUpItem::create([
            'reference_code' => 'DRCP-2026-0003',
            'item_type' => 'meeting',
            'title' => 'Confirm attendance list for outreach meeting',
            'description' => 'Promotion Section to confirm external participants.',
            'source' => 'Promotion calendar',
            'received_at' => now()->subDays(6)->toDateString(),
            'priority' => 'low',
            'status' => ItemStatus::InProgress->value,
            'current_owner_id' => $maryWinnie->id,
            'section_id' => $promotion->id,
            'next_action' => 'Share final attendance list with Jackline.',
            'next_follow_up_date' => now()->subDay()->toDateString(),
            'due_date' => now()->subDay()->toDateString(),
            'last_updated_at' => now()->subDays(4),
        ]);

        FollowUpItem::create([
            'reference_code' => 'DRCP-2026-0004',
            'item_type' => 'internal_task',
            'title' => 'Prepare outreach materials for upcoming event',
            'description' => 'Promotion Section to finalise printed materials and digital assets.',
            'source' => 'Internal planning',
            'received_at' => now()->subDay()->toDateString(),
            'priority' => 'medium',
            'status' => ItemStatus::Assigned->value,
            'current_owner_id' => $maryWinnie->id,
            'section_id' => $promotion->id,
            'next_action' => 'Send draft materials to Jackline for review.',
            'next_follow_up_date' => now()->addDay()->toDateString(),
            'due_date' => now()->addDays(2)->toDateString(),
            'last_updated_at' => now()->subHours(6),
        ]);
    }
}

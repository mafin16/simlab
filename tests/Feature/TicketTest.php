<?php

namespace Tests\Feature;

use App\Models\Asset;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TicketTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['role' => User::ROLE_SUPER_ADMIN]);
    }

    private function teknisi(): User
    {
        return User::factory()->create(['role' => User::ROLE_TEKNISI]);
    }

    private function validPayload(array $overrides = []): array
    {
        return array_merge([
            'asset_id' => Asset::factory()->create(['category' => 'PC Desktop', 'status' => 'Ready'])->id,
            'component_issue' => 'Mouse / Keyboard',
            'priority' => 'Medium',
            'description' => 'Klik kiri mouse macet sejak pagi.',
            'reporter_name' => 'Bu Rina',
        ], $overrides);
    }

    public function test_guest_is_redirected_from_tickets_index(): void
    {
        $this->get(route('tickets.index'))->assertRedirect('/login');
    }

    public function test_siswa_cannot_view_tickets_index(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_SISWA]);

        $this->actingAs($user)
            ->get(route('tickets.index'))
            ->assertForbidden();
    }

    public function test_instruktur_can_view_tickets_index(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_INSTRUKTUR]);

        $this->actingAs($user)
            ->get(route('tickets.index'))
            ->assertOk()
            ->assertSee('Helpdesk & Tiket Perbaikan Lab', false);
    }

    public function test_resolve_modal_form_targets_resolve_endpoint(): void
    {
        $this->actingAs($this->admin())
            ->get(route('tickets.index'))
            ->assertOk()
            ->assertSee("+ '/resolve'", false);
    }

    public function test_resolved_column_shows_only_five_newest(): void
    {
        Ticket::factory()->resolved()->create([
            'ticket_code' => 'TKT-9001',
            'resolved_at' => now()->subDays(30),
        ]);

        foreach (range(1, 11) as $i) {
            Ticket::factory()->resolved()->create([
                'ticket_code' => 'TKT-7'.str_pad((string) $i, 3, '0', STR_PAD_LEFT),
                'resolved_at' => now()->subHours($i),
            ]);
        }

        $this->actingAs($this->admin())
            ->get(route('tickets.index'))
            ->assertOk()
            ->assertSee('Menampilkan 5 terbaru dari 12 tiket selesai', false)
            ->assertSee('TKT-7001', false)
            ->assertSee('TKT-7005', false)
            ->assertDontSee('TKT-7006', false)
            ->assertDontSee('TKT-9001', false);
    }

    public function test_super_admin_can_store_ticket_and_asset_becomes_degraded(): void
    {
        $payload = $this->validPayload();

        $this->actingAs($this->admin())
            ->post(route('tickets.store'), $payload)
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertDatabaseHas('tickets', [
            'status' => 'Open',
            'priority' => 'Medium',
            'reporter_name' => 'Bu Rina',
        ]);

        $this->assertDatabaseHas('assets', ['id' => $payload['asset_id'], 'status' => 'Degraded']);
    }

    public function test_store_ticket_with_booting_issue_sets_asset_maintenance(): void
    {
        $payload = $this->validPayload([
            'component_issue' => 'PC Tidak Bisa Booting / OS Error',
            'priority' => 'High',
        ]);

        $this->actingAs($this->teknisi())
            ->post(route('tickets.store'), $payload)
            ->assertSessionHas('success');

        $this->assertDatabaseHas('assets', ['id' => $payload['asset_id'], 'status' => 'Maintenance']);
    }

    public function test_escalation_does_not_downgrade_maintenance_asset(): void
    {
        $payload = $this->validPayload([
            'component_issue' => 'Mouse / Keyboard',
        ]);

        Asset::whereKey($payload['asset_id'])->update(['status' => 'Maintenance']);

        $this->actingAs($this->teknisi())
            ->post(route('tickets.store'), $payload)
            ->assertSessionHas('success');

        $this->assertDatabaseHas('assets', ['id' => $payload['asset_id'], 'status' => 'Maintenance']);
    }

    public function test_store_ticket_validation_fails_on_invalid_priority(): void
    {
        $this->actingAs($this->admin())
            ->post(route('tickets.store'), $this->validPayload(['priority' => 'Urgent']))
            ->assertSessionHasErrors('priority');

        $this->assertDatabaseCount('tickets', 0);
    }

    public function test_ticket_code_is_generated_sequentially(): void
    {
        $first = $this->validPayload();
        $second = $this->validPayload();

        $this->actingAs($this->admin())->post(route('tickets.store'), $first);
        $this->actingAs($this->admin())->post(route('tickets.store'), $second);

        $codes = Ticket::orderBy('id')->pluck('ticket_code')->all();

        $this->assertSame(['TKT-0001', 'TKT-0002'], $codes);
    }

    public function test_teknisi_can_start_ticket(): void
    {
        $ticket = Ticket::factory()->create();
        $user = $this->teknisi();

        $this->actingAs($user)
            ->post(route('tickets.start', $ticket))
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertDatabaseHas('tickets', [
            'id' => $ticket->id,
            'status' => 'In Progress',
            'technician_name' => $user->name,
        ]);
    }

    public function test_start_fails_for_non_open_ticket(): void
    {
        $ticket = Ticket::factory()->inProgress()->create();

        $this->actingAs($this->teknisi())
            ->post(route('tickets.start', $ticket))
            ->assertSessionHas('error');

        $this->assertDatabaseHas('tickets', ['id' => $ticket->id, 'status' => 'In Progress']);
    }

    public function test_instruktur_cannot_start_ticket(): void
    {
        $ticket = Ticket::factory()->create();
        $user = User::factory()->create(['role' => User::ROLE_INSTRUKTUR]);

        $this->actingAs($user)
            ->post(route('tickets.start', $ticket))
            ->assertForbidden();

        $this->assertDatabaseHas('tickets', ['id' => $ticket->id, 'status' => 'Open']);
    }

    public function test_resolve_requires_resolution_notes(): void
    {
        $ticket = Ticket::factory()->inProgress()->create();

        $this->actingAs($this->teknisi())
            ->post(route('tickets.resolve', $ticket), ['resolution_notes' => ''])
            ->assertSessionHasErrors('resolution_notes');

        $this->assertDatabaseHas('tickets', ['id' => $ticket->id, 'status' => 'In Progress']);
    }

    public function test_resolve_requires_in_progress_status(): void
    {
        $ticket = Ticket::factory()->create();

        $this->actingAs($this->teknisi())
            ->post(route('tickets.resolve', $ticket), ['resolution_notes' => 'Sudah diganti unit baru.'])
            ->assertSessionHas('error');

        $this->assertDatabaseHas('tickets', ['id' => $ticket->id, 'status' => 'Open']);
    }

    public function test_teknisi_can_resolve_ticket_and_asset_back_to_ready(): void
    {
        $asset = Asset::factory()->create(['status' => 'Degraded']);
        $ticket = Ticket::factory()->inProgress()->create(['asset_id' => $asset->id]);

        $this->actingAs($this->teknisi())
            ->post(route('tickets.resolve', $ticket), ['resolution_notes' => 'Mouse diganti unit cadangan gudang.'])
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertDatabaseHas('tickets', [
            'id' => $ticket->id,
            'status' => 'Resolved',
            'technician_name' => 'Teknisi Lab',
        ]);

        $this->assertNotNull($ticket->fresh()->resolved_at);
        $this->assertDatabaseHas('assets', ['id' => $asset->id, 'status' => 'Ready']);
    }

    public function test_resolve_does_not_override_scrapped_asset(): void
    {
        $asset = Asset::factory()->create(['status' => 'Scrapped']);
        $ticket = Ticket::factory()->inProgress()->create(['asset_id' => $asset->id]);

        $this->actingAs($this->teknisi())
            ->post(route('tickets.resolve', $ticket), ['resolution_notes' => 'Unit tidak dapat diperbaiki.'])
            ->assertSessionHas('success');

        $this->assertDatabaseHas('assets', ['id' => $asset->id, 'status' => 'Scrapped']);
    }

    public function test_siswa_cannot_store_ticket(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_SISWA]);

        $this->actingAs($user)
            ->post(route('tickets.store'), $this->validPayload())
            ->assertForbidden();

        $this->assertDatabaseCount('tickets', 0);
    }

    public function test_teknisi_can_destroy_ticket(): void
    {
        $ticket = Ticket::factory()->create();

        $this->actingAs($this->teknisi())
            ->delete(route('tickets.destroy', $ticket))
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertDatabaseCount('tickets', 0);
    }

    public function test_instruktur_cannot_destroy_ticket(): void
    {
        $ticket = Ticket::factory()->create();
        $user = User::factory()->create(['role' => User::ROLE_INSTRUKTUR]);

        $this->actingAs($user)
            ->delete(route('tickets.destroy', $ticket))
            ->assertForbidden();

        $this->assertDatabaseCount('tickets', 1);
    }

    public function test_destroy_open_ticket_reverts_asset_to_ready(): void
    {
        $asset = Asset::factory()->create(['category' => 'PC Desktop', 'status' => 'Degraded']);
        $ticket = Ticket::factory()->create(['asset_id' => $asset->id, 'component_issue' => 'Mouse / Keyboard']);

        $this->actingAs($this->admin())
            ->delete(route('tickets.destroy', $ticket))
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertDatabaseHas('assets', ['id' => $asset->id, 'status' => 'Ready']);
    }

    public function test_destroy_heaviest_ticket_downgrades_asset_to_remaining_severity(): void
    {
        $asset = Asset::factory()->create(['category' => 'PC Desktop', 'status' => 'Maintenance']);
        $monitor = Ticket::factory()->create(['asset_id' => $asset->id, 'component_issue' => 'Monitor Blank / Flashing']);
        Ticket::factory()->create(['asset_id' => $asset->id, 'component_issue' => 'Mouse / Keyboard']);

        $this->actingAs($this->admin())
            ->delete(route('tickets.destroy', $monitor))
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertDatabaseHas('assets', ['id' => $asset->id, 'status' => 'Degraded']);
    }

    public function test_resolve_one_of_two_active_tickets_keeps_remaining_severity(): void
    {
        $asset = Asset::factory()->create(['category' => 'PC Desktop', 'status' => 'Maintenance']);
        $monitor = Ticket::factory()->inProgress()->create(['asset_id' => $asset->id, 'component_issue' => 'Monitor Blank / Flashing']);
        Ticket::factory()->inProgress()->create(['asset_id' => $asset->id, 'component_issue' => 'Mouse / Keyboard']);

        $this->actingAs($this->teknisi())
            ->post(route('tickets.resolve', $monitor), ['resolution_notes' => 'Panel monitor diganti unit cadangan.'])
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertDatabaseHas('assets', ['id' => $asset->id, 'status' => 'Degraded']);
    }

    public function test_destroy_ticket_does_not_override_scrapped_asset(): void
    {
        $asset = Asset::factory()->create(['category' => 'PC Desktop', 'status' => 'Scrapped']);
        $ticket = Ticket::factory()->create(['asset_id' => $asset->id, 'component_issue' => 'Monitor Blank / Flashing']);

        $this->actingAs($this->admin())
            ->delete(route('tickets.destroy', $ticket))
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertDatabaseHas('assets', ['id' => $asset->id, 'status' => 'Scrapped']);
    }

    public function test_start_ticket_does_not_change_asset_status(): void
    {
        $asset = Asset::factory()->create(['category' => 'PC Desktop', 'status' => 'Degraded']);
        $ticket = Ticket::factory()->create(['asset_id' => $asset->id, 'component_issue' => 'Mouse / Keyboard']);

        $this->actingAs($this->teknisi())
            ->post(route('tickets.start', $ticket))
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertDatabaseHas('tickets', ['id' => $ticket->id, 'status' => 'In Progress']);
        $this->assertDatabaseHas('assets', ['id' => $asset->id, 'status' => 'Degraded']);
    }
}

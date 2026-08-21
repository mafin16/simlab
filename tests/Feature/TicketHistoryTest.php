<?php

namespace Tests\Feature;

use App\Models\Asset;
use App\Models\Lab;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use ZipArchive;

class TicketHistoryTest extends TestCase
{
    use RefreshDatabase;

    private function makeTicket(Lab $lab, array $attributes = []): Ticket
    {
        $asset = Asset::factory()->create(['lab_id' => $lab->id]);

        return Ticket::factory()->create(array_merge(['asset_id' => $asset->id], $attributes));
    }

    public function test_guest_is_redirected_from_history(): void
    {
        $this->get(route('tickets.history'))->assertRedirect('/login');
        $this->get(route('tickets.history.excel'))->assertRedirect('/login');
    }

    public function test_siswa_is_forbidden_from_history(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_SISWA]);

        $this->actingAs($user)->get(route('tickets.history'))->assertForbidden();
        $this->actingAs($user)->get(route('tickets.history.excel'))->assertForbidden();
    }

    public function test_staff_sees_all_tickets_including_resolved(): void
    {
        $lab = Lab::factory()->create();

        $this->makeTicket($lab, ['ticket_code' => 'TKT-OPEN-0001']);

        $asset = Asset::factory()->create(['lab_id' => $lab->id]);
        Ticket::factory()->resolved()->create(['asset_id' => $asset->id, 'ticket_code' => 'TKT-DONE-0002']);

        $user = User::factory()->create(['role' => User::ROLE_SUPER_ADMIN]);

        $this->actingAs($user)
            ->get(route('tickets.history'))
            ->assertOk()
            ->assertSee('TKT-OPEN-0001')
            ->assertSee('TKT-DONE-0002')
            ->assertSee('Arsip Tiket');
    }

    public function test_status_filter_shows_only_matching_tickets(): void
    {
        $lab = Lab::factory()->create();
        $this->makeTicket($lab, ['ticket_code' => 'TKT-OPEN-0001', 'status' => 'Open']);

        $asset = Asset::factory()->create(['lab_id' => $lab->id]);
        Ticket::factory()->resolved()->create(['asset_id' => $asset->id, 'ticket_code' => 'TKT-DONE-0002']);

        $user = User::factory()->create(['role' => User::ROLE_TEKNISI]);

        $this->actingAs($user)
            ->get(route('tickets.history', ['status' => 'Resolved']))
            ->assertOk()
            ->assertSee('TKT-DONE-0002')
            ->assertDontSee('TKT-OPEN-0001');
    }

    public function test_search_finds_by_ticket_code_and_reporter(): void
    {
        $lab = Lab::factory()->create();
        $this->makeTicket($lab, ['ticket_code' => 'TKT-7777-AAAA', 'reporter_name' => 'Budi Santoso']);
        $this->makeTicket($lab, ['ticket_code' => 'TKT-1111-BBBB', 'reporter_name' => 'Sari Dewi']);

        $user = User::factory()->create(['role' => User::ROLE_INSTRUKTUR]);

        $this->actingAs($user)
            ->get(route('tickets.history', ['search' => 'TKT-7777']))
            ->assertOk()
            ->assertSee('TKT-7777-AAAA')
            ->assertDontSee('TKT-1111-BBBB');

        $this->actingAs($user)
            ->get(route('tickets.history', ['search' => 'Sari']))
            ->assertOk()
            ->assertSee('TKT-1111-BBBB')
            ->assertDontSee('TKT-7777-AAAA');
    }

    public function test_date_range_filter_excludes_out_of_range_tickets(): void
    {
        $lab = Lab::factory()->create();
        $this->makeTicket($lab, ['ticket_code' => 'TKT-OLD-0001', 'reported_at' => now()->subDays(10)]);
        $this->makeTicket($lab, ['ticket_code' => 'TKT-NEW-0002', 'reported_at' => now()]);

        $user = User::factory()->create(['role' => User::ROLE_SUPER_ADMIN]);

        $this->actingAs($user)
            ->get(route('tickets.history', [
                'date_from' => now()->subDay()->format('Y-m-d'),
                'date_to' => now()->format('Y-m-d'),
            ]))
            ->assertOk()
            ->assertSee('TKT-NEW-0002')
            ->assertDontSee('TKT-OLD-0001');
    }

    public function test_lab_filter_scopes_tickets_to_selected_lab(): void
    {
        $labA = Lab::factory()->create();
        $labB = Lab::factory()->create();

        $this->makeTicket($labA, ['ticket_code' => 'TKT-LABA-001']);
        $this->makeTicket($labB, ['ticket_code' => 'TKT-LABB-002']);

        $user = User::factory()->create(['role' => User::ROLE_TEKNISI]);

        $this->actingAs($user)
            ->get(route('tickets.history', ['lab_id' => $labA->id]))
            ->assertOk()
            ->assertSee('TKT-LABA-001')
            ->assertDontSee('TKT-LABB-002');
    }

    public function test_history_is_paginated_fifteen_per_page(): void
    {
        $lab = Lab::factory()->create();

        foreach (range(1, 16) as $i) {
            $this->makeTicket($lab, [
                'ticket_code' => sprintf('TKT-PAGE-%04d', $i),
                'reported_at' => now()->subMinutes(17 - $i),
            ]);
        }

        $user = User::factory()->create(['role' => User::ROLE_SUPER_ADMIN]);

        $pageOne = $this->actingAs($user)->get(route('tickets.history'));
        $pageOne->assertOk()->assertDontSee('TKT-PAGE-0001');

        $pageTwo = $this->get(route('tickets.history', ['page' => 2]));
        $pageTwo->assertOk()->assertSee('TKT-PAGE-0001');
    }

    public function test_history_excel_contains_filtered_tickets(): void
    {
        $lab = Lab::factory()->create();

        $asset = Asset::factory()->create(['lab_id' => $lab->id]);
        Ticket::factory()->resolved()->create(['asset_id' => $asset->id, 'ticket_code' => 'TKT-KEEP-0001']);

        $this->makeTicket($lab, ['ticket_code' => 'TKT-SKIP-0002', 'status' => 'Open']);

        $user = User::factory()->create(['role' => User::ROLE_INSTRUKTUR]);
        $response = $this->actingAs($user)
            ->get(route('tickets.history.excel', ['status' => 'Resolved']));

        $response->assertOk();

        $resource = tmpfile();
        fwrite($resource, $response->streamedContent());
        $path = stream_get_meta_data($resource)['uri'];

        $zip = new ZipArchive;
        $zip->open($path);
        $content = ($zip->getFromName('xl/sharedStrings.xml') ?: '').'|'.($zip->getFromName('xl/worksheets/sheet1.xml') ?: '');
        $zip->close();
        fclose($resource);

        $this->assertStringContainsString('TKT-KEEP-0001', $content);
        $this->assertStringNotContainsString('TKT-SKIP-0002', $content);
    }

    public function test_history_rejects_invalid_date_range(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_SUPER_ADMIN]);

        $this->actingAs($user)
            ->from(route('reports.index'))
            ->get(route('tickets.history', [
                'date_from' => '2026-05-10',
                'date_to' => '2026-05-01',
            ]))
            ->assertRedirect(route('reports.index'))
            ->assertSessionHasErrors('date_to');
    }
}

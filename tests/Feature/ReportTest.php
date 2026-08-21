<?php

namespace Tests\Feature;

use App\Models\Asset;
use App\Models\Lab;
use App\Models\Presence;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use ZipArchive;

class ReportTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_from_all_report_routes(): void
    {
        $routes = [
            route('reports.index'),
            route('reports.maintenance.pdf'),
            route('reports.presence.pdf'),
            route('reports.presence.excel'),
            route('tickets.history'),
            route('tickets.history.excel'),
        ];

        foreach ($routes as $route) {
            $this->get($route)->assertRedirect('/login');
        }
    }

    public function test_siswa_is_forbidden_from_reports(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_SISWA]);

        $this->actingAs($user)
            ->get(route('reports.index'))
            ->assertForbidden();

        $this->actingAs($user)
            ->get(route('tickets.history'))
            ->assertForbidden();

        $this->actingAs($user)
            ->get(route('reports.presence.excel'))
            ->assertForbidden();
    }

    public function test_staff_can_view_report_hub(): void
    {
        $roles = [User::ROLE_SUPER_ADMIN, User::ROLE_TEKNISI, User::ROLE_INSTRUKTUR];

        foreach ($roles as $role) {
            $user = User::factory()->create(['role' => $role]);

            $this->actingAs($user)
                ->get(route('reports.index'))
                ->assertOk()
                ->assertSee('Laporan Pemeliharaan')
                ->assertSee('Log Presensi');
        }
    }

    public function test_maintenance_pdf_downloads_with_period_filename(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_SUPER_ADMIN]);
        Ticket::factory()->resolved()->create();

        $response = $this->actingAs($admin)
            ->get(route('reports.maintenance.pdf', [
                'date_from' => now()->startOfMonth()->format('Y-m-d'),
                'date_to' => now()->format('Y-m-d'),
            ]));

        $response->assertOk();
        $this->assertStringContainsString('application/pdf', (string) $response->headers->get('Content-Type'));
        $this->assertStringContainsString(
            'laporan-pemeliharaan-'.now()->startOfMonth()->format('Ymd').'-'.now()->format('Ymd'),
            (string) $response->headers->get('Content-Disposition')
        );
    }

    public function test_maintenance_pdf_rejects_inverted_date_range(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_SUPER_ADMIN]);

        $this->actingAs($admin)
            ->from(route('reports.index'))
            ->get(route('reports.maintenance.pdf', [
                'date_from' => '2026-05-10',
                'date_to' => '2026-05-01',
            ]))
            ->assertRedirect(route('reports.index'))
            ->assertSessionHasErrors('date_to');
    }

    public function test_presence_pdf_downloads(): void
    {
        $teknisi = User::factory()->create(['role' => User::ROLE_TEKNISI]);
        Presence::factory()->checkedOut()->create();

        $response = $this->actingAs($teknisi)->get(route('reports.presence.pdf'));

        $response->assertOk();
        $this->assertStringContainsString('application/pdf', (string) $response->headers->get('Content-Type'));
    }

    public function test_presence_excel_contains_only_data_in_range_and_lab(): void
    {
        $labA = Lab::factory()->create();
        $labB = Lab::factory()->create();

        $assetA = Asset::factory()->create(['lab_id' => $labA->id]);
        $assetB = Asset::factory()->create(['lab_id' => $labB->id]);

        Presence::factory()->checkedOut()->create([
            'asset_id' => $assetA->id,
            'user_identifier' => '11111',
            'session_date' => today(),
        ]);

        Presence::factory()->checkedOut()->create([
            'asset_id' => $assetB->id,
            'user_identifier' => '22222',
            'session_date' => today(),
        ]);

        Presence::factory()->yesterday()->checkedOut()->create([
            'asset_id' => $assetA->id,
            'user_identifier' => '33333',
        ]);

        $instruktur = User::factory()->create(['role' => User::ROLE_INSTRUKTUR]);
        $response = $this->actingAs($instruktur)->get(route('reports.presence.excel', [
            'date_from' => today()->format('Y-m-d'),
            'date_to' => today()->format('Y-m-d'),
            'lab_id' => $labA->id,
        ]));

        $response->assertOk();

        $sharedStrings = $this->xlsxSharedStrings($response->streamedContent());

        $this->assertStringContainsString('11111', $sharedStrings);
        $this->assertStringNotContainsString('22222', $sharedStrings);
        $this->assertStringNotContainsString('33333', $sharedStrings);
    }

    public function test_presence_excel_defaults_to_current_month_without_params(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_SUPER_ADMIN]);

        $response = $this->actingAs($user)->get(route('reports.presence.excel'));

        $response->assertOk();
        $this->assertStringContainsString('log-presensi-', (string) $response->headers->get('Content-Disposition'));
    }

    private function xlsxSharedStrings(string $content): string
    {
        $resource = tmpfile();
        fwrite($resource, $content);
        $path = stream_get_meta_data($resource)['uri'];

        $zip = new ZipArchive;
        $zip->open($path);

        $shared = $zip->getFromName('xl/sharedStrings.xml') ?: '';
        $sheet = $zip->getFromName('xl/worksheets/sheet1.xml') ?: '';
        $zip->close();
        fclose($resource);

        return $shared.'|'.$sheet;
    }
}

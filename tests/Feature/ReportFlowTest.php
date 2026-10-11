<?php

namespace Tests\Feature;

use App\Models\Report;
use App\Models\ServiceType;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReportFlowTest extends TestCase
{
    use RefreshDatabase;

    private function payload(): array
    {
        return [
            'service_type_id' => ServiceType::where('short_name', '7DG')->value('id'),
            'service_date' => '2026-10-04',
            'start_time' => '08:00',
            'end_time' => '10:35',
            'tasks' => [['number' => '1', 'title' => 'Moderation', 'handled_by' => 'Sis Ada', 'remarks' => '']],
            'viewership' => [['platform' => 'YouTube', 'viewers' => 100], ['platform' => 'Facebook', 'viewers' => 50]],
            'attendees' => [['name' => 'Bro Godwin', 'role' => 'HOD']],
            'workspace_clean' => true,
            'incidents' => [],
        ];
    }

    public function test_roles_are_separated(): void
    {
        $this->seed(DatabaseSeeder::class);
        $coord = User::where('role', 'coordinator')->first();
        $admin = User::where('role', 'admin')->first();

        $this->actingAs($coord)->get('/admin')->assertForbidden();
        $this->actingAs($admin)->get('/coordinator/reports')->assertForbidden();
        $this->actingAs($admin)->get('/admin')->assertOk();
    }

    public function test_coordinator_creates_submits_and_admin_approves(): void
    {
        $this->seed(DatabaseSeeder::class);
        $coord = User::where('role', 'coordinator')->first();
        $admin = User::where('role', 'admin')->first();

        $this->actingAs($coord)->post('/coordinator/reports', $this->payload())->assertRedirect();
        $report = Report::firstOrFail();
        $this->assertSame(150, $report->totalViewers());
        $this->assertSame('2 hrs 35 mins', $report->durationLabel());

        $this->post("/coordinator/reports/{$report->id}/submit")->assertRedirect();
        // submitted reports are locked for the coordinator
        $this->put("/coordinator/reports/{$report->id}", $this->payload())->assertForbidden();

        $this->actingAs($admin)->post("/admin/reports/{$report->id}/review", ['decision' => 'approve'])->assertRedirect();
        $this->assertSame('approved', $report->fresh()->status);
    }

    public function test_docx_download_and_other_coordinators_are_blocked(): void
    {
        $this->seed(DatabaseSeeder::class);
        $coord = User::where('role', 'coordinator')->first();
        $other = User::factory()->create(['role' => 'coordinator']);

        $this->actingAs($coord)->post('/coordinator/reports', $this->payload());
        $report = Report::firstOrFail();

        $res = $this->get("/coordinator/reports/{$report->id}/download")->assertOk();
        $this->assertStringContainsString('Witty_Inventions_Service_Report_-_7DG_Oct_04_2026.docx', $res->headers->get('content-disposition'));

        $this->actingAs($other)->get("/coordinator/reports/{$report->id}/download")->assertForbidden();
    }

    public function test_admin_can_add_service_type(): void
    {
        $this->seed(DatabaseSeeder::class);
        $this->actingAs(User::where('role', 'admin')->first())
            ->post('/admin/catalog/service-types', ['name' => 'Special Service', 'short_name' => 'SPECIAL', 'is_active' => true])
            ->assertRedirect();
        $this->assertDatabaseHas('service_types', ['short_name' => 'SPECIAL']);
    }

    public function test_admin_changes_password_and_disables_users_and_workers(): void
    {
        $this->seed(DatabaseSeeder::class);
        $admin = User::where('role', 'admin')->first();
        $coord = User::where('role', 'coordinator')->first();

        $this->actingAs($admin)->put("/admin/users/{$coord->id}/password", [
            'password' => 'brand-new-pass', 'password_confirmation' => 'brand-new-pass',
        ])->assertRedirect();
        $this->assertTrue(\Illuminate\Support\Facades\Hash::check('brand-new-pass', $coord->fresh()->password));

        $this->patch("/admin/users/{$coord->id}/toggle")->assertRedirect();
        $this->assertFalse($coord->fresh()->is_active);
        $this->patch("/admin/users/{$admin->id}/toggle")->assertStatus(422);

        $worker = \App\Models\Worker::first();
        $this->patch("/admin/catalog/workers/{$worker->id}/toggle")->assertRedirect();
        $this->assertFalse($worker->fresh()->is_active);
    }
}

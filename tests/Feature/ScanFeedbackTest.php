<?php

namespace Tests\Feature;

use App\Livewire\Admin\MasterData\ScanFeedbackComponent;
use App\Livewire\ScanComponent;
use App\Models\Barcode;
use App\Models\ScanFeedback;
use App\Models\Shift;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ScanFeedbackTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Seed default scan feedbacks
        ScanFeedback::create([
            'category' => 'super_early',
            'title' => 'Luar Biasa!',
            'message' => 'Gokill {name}! Awal banget.',
            'icon' => 'fire',
            'badge_color' => 'green',
            'is_active' => true,
        ]);

        ScanFeedback::create([
            'category' => 'early',
            'title' => 'Hebat!',
            'message' => 'Hebat {name}! Datang lebih awal nih.',
            'icon' => 'sparkles',
            'badge_color' => 'green',
            'is_active' => true,
        ]);

        ScanFeedback::create([
            'category' => 'out',
            'title' => 'Terima Kasih!',
            'message' => 'Terima kasih atas kerja kerasmu hari ini, {name}!',
            'icon' => 'heart',
            'badge_color' => 'purple',
            'is_active' => true,
        ]);
    }

    public function test_random_feedback_replaces_name_placeholder_correctly()
    {
        $feedback = ScanFeedback::getRandomFeedback('early', 'Zaenal');

        $this->assertEquals('early', $feedback['type']);
        $this->assertEquals('Hebat!', $feedback['title']);
        $this->assertStringContainsString('Zaenal', $feedback['message']);
        $this->assertEquals('sparkles', $feedback['icon']);
        $this->assertEquals('green', $feedback['badge_color']);
    }

    public function test_superadmin_can_access_and_manage_scan_feedbacks()
    {
        $superadmin = User::factory()->create(['group' => 'superadmin']);

        $this->actingAs($superadmin);

        Livewire::test(ScanFeedbackComponent::class)
            ->set('category', 'super_early')
            ->set('title', 'Super Early Special!')
            ->set('message', 'Gokill {name}! Panutan.')
            ->set('icon', 'fire')
            ->set('badge_color', 'green')
            ->set('is_active', true)
            ->call('store')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('scan_feedbacks', [
            'title' => 'Super Early Special!',
            'category' => 'super_early',
        ]);

        $created = ScanFeedback::where('title', 'Super Early Special!')->first();

        // Edit
        Livewire::test(ScanFeedbackComponent::class)
            ->call('edit', $created->id)
            ->set('title', 'Super Early Special Updated')
            ->call('update')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('scan_feedbacks', [
            'id' => $created->id,
            'title' => 'Super Early Special Updated',
        ]);

        // Toggle Active
        Livewire::test(ScanFeedbackComponent::class)
            ->call('toggleActive', $created->id);

        $this->assertDatabaseHas('scan_feedbacks', [
            'id' => $created->id,
            'is_active' => false,
        ]);

        // Delete
        Livewire::test(ScanFeedbackComponent::class)
            ->call('confirmDelete', $created->id)
            ->call('delete');

        $this->assertDatabaseMissing('scan_feedbacks', [
            'id' => $created->id,
        ]);
    }

    public function test_non_superadmin_user_cannot_access_scan_feedback_component()
    {
        $user = User::factory()->create(['group' => 'user']);

        $this->actingAs($user);

        $this->get(route('hr.masters.scan-feedback'))
            ->assertStatus(403);
    }

    public function test_scanning_triggers_motivation_modal_with_correct_feedback()
    {
        $user = User::factory()->create([
            'name' => 'Zaenal Alfian',
            'group' => 'user',
            'status' => 'active',
        ]);

        $barcode = Barcode::create([
            'name' => 'Barcode Kantor Utama',
            'value' => 'TEST_BARCODE_123',
            'latitude' => -6.200000,
            'longitude' => 106.816666,
            'radius' => 1000,
        ]);

        $shift = Shift::create([
            'name' => 'Shift Pagi',
            'start_time' => '23:59:59',
            'end_time' => '23:59:59',
        ]);

        $this->actingAs($user);

        Livewire::test(ScanComponent::class)
            ->set('currentLiveCoords', [-6.200000, 106.816666])
            ->set('shift_id', $shift->id)
            ->call('scan', 'TEST_BARCODE_123')
            ->assertSet('showMotivationModal', true)
            ->assertSet('motivationTitle', 'Luar Biasa!')
            ->assertSee('Zaenal');
    }
}

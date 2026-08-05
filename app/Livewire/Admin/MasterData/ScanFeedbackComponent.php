<?php

namespace App\Livewire\Admin\MasterData;

use App\Models\ScanFeedback;
use Illuminate\Support\Facades\Auth;
use Laravel\Jetstream\InteractsWithBanner;
use Livewire\Component;
use Livewire\WithPagination;

class ScanFeedbackComponent extends Component
{
    use WithPagination;
    use InteractsWithBanner;

    public string $search = '';
    public string $categoryFilter = '';

    // Modal state
    public bool $showCreateModal = false;
    public bool $showEditModal = false;
    public bool $showDeleteModal = false;

    // Form fields
    public ?string $editingId = null;
    public string $category = 'early';
    public string $title = '';
    public string $message = '';
    public string $icon = 'sparkles';
    public string $badge_color = 'green';
    public bool $is_active = true;
    public ?string $deletingId = null;

    protected array $rules = [
        'category' => 'required|in:super_early,early,on_time,late_mild,late_severe,out',
        'title' => 'required|string|max:150',
        'message' => 'required|string',
        'icon' => 'required|string|max:50',
        'badge_color' => 'required|string|max:50',
        'is_active' => 'boolean',
    ];

    public function mount()
    {
        if (!Auth::user()?->isSuperadmin) {
            abort(403, 'Akses Ditolak: Hanya SuperAdmin yang dapat mengelola Master Scan Feedback.');
        }
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingCategoryFilter(): void
    {
        $this->resetPage();
    }

    public function resetForm(): void
    {
        $this->reset(['editingId', 'category', 'title', 'message', 'icon', 'badge_color', 'is_active']);
        $this->category = 'early';
        $this->icon = 'sparkles';
        $this->badge_color = 'green';
        $this->is_active = true;
        $this->resetErrorBag();
    }

    public function create(): void
    {
        if (!Auth::user()?->isSuperadmin) {
            abort(403);
        }

        $this->resetForm();
        $this->showCreateModal = true;
    }

    public function store(): void
    {
        if (!Auth::user()?->isSuperadmin) {
            abort(403);
        }

        $validated = $this->validate();

        ScanFeedback::create($validated);

        $this->banner(__('Scan feedback ucapan berhasil ditambahkan.'));
        $this->showCreateModal = false;
        $this->resetForm();
    }

    public function edit(string $id): void
    {
        if (!Auth::user()?->isSuperadmin) {
            abort(403);
        }

        /** @var ScanFeedback $feedback */
        $feedback = ScanFeedback::findOrFail($id);

        $this->editingId = $feedback->id;
        $this->category = $feedback->category;
        $this->title = $feedback->title;
        $this->message = $feedback->message;
        $this->icon = $feedback->icon;
        $this->badge_color = $feedback->badge_color;
        $this->is_active = $feedback->is_active;

        $this->resetErrorBag();
        $this->showEditModal = true;
    }

    public function update(): void
    {
        if (!Auth::user()?->isSuperadmin) {
            abort(403);
        }

        $validated = $this->validate();

        /** @var ScanFeedback $feedback */
        $feedback = ScanFeedback::findOrFail($this->editingId);
        $feedback->update($validated);

        $this->banner(__('Scan feedback ucapan berhasil diperbarui.'));
        $this->showEditModal = false;
        $this->resetForm();
    }

    public function toggleActive(string $id): void
    {
        if (!Auth::user()?->isSuperadmin) {
            abort(403);
        }

        /** @var ScanFeedback $feedback */
        $feedback = ScanFeedback::findOrFail($id);
        $feedback->update(['is_active' => !$feedback->is_active]);

        $this->banner(__('Status keaktifan ucapan berhasil diperbarui.'));
    }

    public function confirmDelete(string $id): void
    {
        if (!Auth::user()?->isSuperadmin) {
            abort(403);
        }

        $this->deletingId = $id;
        $this->showDeleteModal = true;
    }

    public function delete(): void
    {
        if (!Auth::user()?->isSuperadmin) {
            abort(403);
        }

        if ($this->deletingId) {
            ScanFeedback::where('id', $this->deletingId)->delete();
            $this->banner(__('Scan feedback ucapan berhasil dihapus.'));
        }

        $this->showDeleteModal = false;
        $this->deletingId = null;
    }

    public function render()
    {
        $feedbacks = ScanFeedback::when($this->categoryFilter, fn ($q) => $q->where('category', $this->categoryFilter))
            ->when($this->search, function ($q) {
                $q->where('title', 'like', '%' . $this->search . '%')
                    ->orWhere('message', 'like', '%' . $this->search . '%');
            })
            ->latest()
            ->paginate(12);

        return view('livewire.admin.master-data.scan-feedback', [
            'feedbacks' => $feedbacks,
        ]);
    }
}

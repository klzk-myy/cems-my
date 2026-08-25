<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class TransactionBatchControllerTest extends TestCase
{
    use RefreshDatabase;

    private User $manager;

    protected function setUp(): void
    {
        parent::setUp();
        $this->manager = User::factory()->create(['role' => UserRole::Manager]);
    }

    #[Test]
    public function batch_upload_requires_csv_file(): void
    {
        $this->actingAs($this->manager)
            ->post('/transactions/batch-upload', [])
            ->assertSessionHasErrors('csv_file');
    }

    #[Test]
    public function batch_upload_rejects_invalid_file_type(): void
    {
        $file = UploadedFile::fake()->create('test.pdf', 1024, 'application/pdf');

        $this->actingAs($this->manager)
            ->post('/transactions/batch-upload', ['csv_file' => $file])
            ->assertSessionHasErrors('csv_file');
    }

    #[Test]
    public function batch_upload_requires_manager_role(): void
    {
        $teller = User::factory()->create(['role' => UserRole::Teller]);
        $file = UploadedFile::fake()->create('test.csv', 1024, 'text/csv');

        $this->actingAs($teller)
            ->post('/transactions/batch-upload', ['csv_file' => $file])
            ->assertStatus(403);
    }
}

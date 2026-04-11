<?php

namespace Tests\Unit;

use App\Models\Compliance\ComplianceCase;
use App\Models\Compliance\ComplianceCaseLink;
use App\Models\User;
use App\Services\AlertTriageService;
use App\Services\CaseManagementService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CaseManagementDocumentLinkTest extends TestCase
{
    use RefreshDatabase;

    protected CaseManagementService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new CaseManagementService(app(AlertTriageService::class));
    }

    public function test_add_link_creates_compliance_case_link(): void
    {
        $case = ComplianceCase::factory()->open()->create();

        $link = $this->service->addLink($case->id, 'Transaction', 123);

        $this->assertInstanceOf(ComplianceCaseLink::class, $link);
        $this->assertEquals($case->id, $link->case_id);
        $this->assertEquals('Transaction', $link->linked_type);
        $this->assertEquals(123, $link->linked_id);
    }

    public function test_remove_link_deletes_compliance_case_link(): void
    {
        $case = ComplianceCase::factory()->open()->create();

        $link = $case->links()->create([
            'linked_type' => 'Transaction',
            'linked_id' => 456,
        ]);

        $this->service->removeLink($link->id);

        $this->assertDatabaseMissing('compliance_case_links', ['id' => $link->id]);
    }

    public function test_verify_document_sets_verified_fields(): void
    {
        $case = ComplianceCase::factory()->open()->create();
        $uploader = User::factory()->create();
        $verifier = User::factory()->create();

        $doc = $case->documents()->create([
            'file_name' => 'test.pdf',
            'file_path' => 'compliance_cases/1/documents/test.pdf',
            'file_type' => 'application/pdf',
            'uploaded_by' => $uploader->id,
            'uploaded_at' => now(),
        ]);

        $verified = $this->service->verifyDocument($doc->id, $verifier->id);

        $this->assertNotNull($verified->verified_at);
        $this->assertEquals($verifier->id, $verified->verified_by);
    }

    public function test_get_case_documents_returns_documents(): void
    {
        $case = ComplianceCase::factory()->open()->create();
        $uploader = User::factory()->create();

        $case->documents()->create([
            'file_name' => 'doc1.pdf',
            'file_path' => 'compliance_cases/1/documents/doc1.pdf',
            'file_type' => 'application/pdf',
            'uploaded_by' => $uploader->id,
            'uploaded_at' => now(),
        ]);

        $docs = $this->service->getCaseDocuments($case->id);

        $this->assertCount(1, $docs);
        $this->assertEquals('doc1.pdf', $docs->first()->file_name);
    }

    public function test_get_case_links_returns_links(): void
    {
        $case = ComplianceCase::factory()->open()->create();

        $case->links()->create([
            'linked_type' => 'Transaction',
            'linked_id' => 789,
        ]);

        $links = $this->service->getCaseLinks($case->id);

        $this->assertCount(1, $links);
        $this->assertEquals('Transaction', $links->first()->linked_type);
    }
}

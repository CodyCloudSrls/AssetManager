<?php

namespace Tests\Feature\Tickets\Ui;

use App\Models\Company;
use App\Models\ComplianceDomain;
use App\Models\Document;
use App\Models\DocumentFramework;
use App\Models\DocumentType;
use App\Models\Ticket;
use App\Models\User;
use Tests\TestCase;

class TicketDocumentAccessScopeTest extends TestCase
{
    public function test_ticket_form_lists_only_visible_documents(): void
    {
        $company = Company::factory()->create();
        $type = DocumentType::factory()->create();
        $actor = $this->restrictedTicketUser($company, [
            'tickets.create' => '1',
            'documents.view' => '1',
            'documents.area.cybersecurity.view' => '1',
        ]);

        $nis2Framework = $this->framework($company, 'nis2', 'nis2-ticket-form-scope');
        $gdprFramework = $this->framework($company, 'gdpr', 'gdpr-ticket-form-scope');

        $this->document($company, $type, $nis2Framework, 'Visible cyber ticket document', 'cybersecurity');
        $this->document($company, $type, $nis2Framework, 'Hidden admin ticket document', 'administration');
        $this->document($company, $type, $gdprFramework, 'Hidden gdpr ticket document', 'cybersecurity');

        $this->actingAs($actor)
            ->get(route('tickets.create'))
            ->assertOk()
            ->assertSeeText('Visible cyber ticket document')
            ->assertDontSeeText('Hidden admin ticket document')
            ->assertDontSeeText('Hidden gdpr ticket document');
    }

    public function test_ticket_store_rejects_documents_hidden_by_document_scopes(): void
    {
        $company = Company::factory()->create();
        $type = DocumentType::factory()->create();
        $actor = $this->restrictedTicketUser($company, [
            'tickets.create' => '1',
            'documents.view' => '1',
            'documents.area.cybersecurity.view' => '1',
        ]);

        $framework = $this->framework($company, 'nis2', 'nis2-ticket-store-scope');
        $hidden = $this->document($company, $type, $framework, 'Hidden ticket store document', 'administration');

        $this->actingAs($actor)
            ->post(route('tickets.store'), [
                'company_id' => $company->id,
                'source' => Ticket::SOURCE_INTERNAL,
                'subject' => 'Scoped ticket document rejection',
                'description' => 'Trying to link a document hidden by document area scope.',
                'document_id' => $hidden->id,
            ])
            ->assertSessionHasErrors('document_id');

        $this->assertDatabaseMissing('tickets', [
            'subject' => 'Scoped ticket document rejection',
            'document_id' => $hidden->id,
        ]);
    }

    public function test_ticket_api_hides_linked_documents_hidden_by_document_scopes(): void
    {
        $company = Company::factory()->create();
        $type = DocumentType::factory()->create();
        $actor = $this->restrictedTicketUser($company, [
            'tickets.view' => '1',
            'documents.view' => '1',
            'documents.area.cybersecurity.view' => '1',
        ]);

        $framework = $this->framework($company, 'nis2', 'nis2-ticket-api-scope');
        $hidden = $this->document($company, $type, $framework, 'Hidden ticket API document', 'administration');

        $ticket = Ticket::query()->create([
            'company_id' => $company->id,
            'source' => Ticket::SOURCE_INTERNAL,
            'subject' => 'Ticket with hidden document',
            'description' => 'The linked document must not be exposed through the API transformer.',
            'document_id' => $hidden->id,
            'created_by' => $actor->id,
        ]);

        $this->actingAsForApi($actor)
            ->getJson(route('api.tickets.show', $ticket))
            ->assertOk()
            ->assertJsonPath('document', null);
    }

    private function restrictedTicketUser(Company $company, array $permissions): User
    {
        $actor = User::factory()->create([
            'company_id' => $company->id,
            'permissions' => json_encode($permissions),
            'compliance_scope_restricted' => true,
        ]);

        $actor->complianceDomains()->sync([
            $this->complianceDomain('nis2')->id,
        ]);

        return $actor->refresh()->load('complianceDomains');
    }

    private function complianceDomain(string $key): ComplianceDomain
    {
        return ComplianceDomain::query()->firstOrCreate(
            ['key' => $key],
            [
                'name' => strtoupper($key),
                'is_active' => true,
                'is_system' => true,
            ]
        );
    }

    private function framework(Company $company, string $domain, string $slug): DocumentFramework
    {
        $this->complianceDomain($domain);

        return DocumentFramework::factory()->create([
            'company_id' => $company->id,
            'name' => strtoupper($slug),
            'slug' => $slug,
            'compliance_domain' => $domain,
            'status' => 'active',
            'is_active' => true,
            'is_system_template' => false,
        ]);
    }

    private function document(
        Company $company,
        DocumentType $type,
        ?DocumentFramework $framework,
        string $name,
        ?string $area
    ): Document {
        return Document::query()->create([
            'name' => $name,
            'company_id' => $company->id,
            'status' => Document::STATUS_ACTIVE,
            'document_area' => $area,
            'document_type_id' => $type->id,
            'document_framework_id' => $framework?->id,
        ]);
    }
}

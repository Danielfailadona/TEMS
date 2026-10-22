<?php

namespace Tests\Feature;

use App\Enums\CitationStatus;
use App\Models\Citation;
use App\Models\Payment;
use App\Models\User;
use App\Models\ViolationType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CitizenPaymentAutofillTest extends TestCase
{
    use RefreshDatabase;

    private function violationType(): ViolationType
    {
        return ViolationType::firstOrCreate(
            ['code' => 'TEST-VIO'],
            ['name' => 'Test Violation', 'penalty_amount' => 500, 'is_active' => true]
        );
    }

    private function citation(array $overrides = []): Citation
    {
        $enforcer = User::factory()->create(['role' => 'enforcer']);

        return Citation::create(array_merge([
            'citation_number' => 'CIT-PAY-0001',
            'violation_type_id' => $this->violationType()->id,
            'issued_by' => $enforcer->id,
            'penalty_amount' => 750,
            'status' => CitationStatus::Issued->value,
            'location' => 'Rizal Park',
            'vehicle_plate' => 'ABC-1234',
            'driver_name' => 'Juan Dela Cruz',
            'issued_at' => now(),
            'due_date' => now()->addDays(7),
        ], $overrides));
    }

    private function payUrl(Citation $citation): string
    {
        return "/cite/{$citation->id}/{$citation->getValidationToken()}/pay";
    }

    public function test_pay_form_autofills_from_citation(): void
    {
        $citation = $this->citation();

        $response = $this->get($this->payUrl($citation));

        $response->assertOk();
        $response->assertSee('Pay Citation');
        $response->assertSee('CIT-PAY-0001');
        $response->assertSee('ABC-1234');
        $response->assertSee('Test Violation');
        // Autofilled payer name comes from the citation driver.
        $response->assertSee('value="Juan Dela Cruz"', false);
        // Amount due is pre-filled.
        $response->assertSee('750.00');
        // Form posts to the token-validated checkout endpoint.
        $response->assertSee(route('public.citation.checkout', [
            'id' => $citation->id,
            'token' => $citation->getValidationToken(),
        ]), false);
    }

    public function test_pay_form_rejects_invalid_token(): void
    {
        $citation = $this->citation();

        $this->get("/cite/{$citation->id}/invalid-token/pay")->assertNotFound();
    }

    public function test_pay_form_shows_already_paid_state(): void
    {
        $citation = $this->citation(['status' => CitationStatus::Paid->value]);
        Payment::create([
            'receipt_number' => 'RCPT-PAID-0001',
            'citation_id' => $citation->id,
            'cashier_id' => User::factory()->create(['role' => 'cashier'])->id,
            'amount' => 750,
            'payment_method' => 'cash',
            'paid_at' => now(),
        ]);

        $response = $this->get($this->payUrl($citation));

        $response->assertOk();
        $response->assertSee('already been paid');
        $response->assertSee('RCPT-PAID-0001');
        $response->assertDontSee('Proceed to Pay');
    }

    public function test_lookup_result_offers_online_payment_for_unpaid_citation(): void
    {
        $citation = $this->citation();

        $response = $this->get('/citizen/citation/search?search=CIT-PAY-0001');

        $response->assertOk();
        $response->assertSee('Pay Online');
        $response->assertSee(route('public.citation.pay', [
            'id' => $citation->id,
            'token' => $citation->getValidationToken(),
        ]), false);
    }
}

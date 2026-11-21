<?php

namespace Tests\Feature;

use App\Models\Payment;
use App\Models\Sale;
use App\Models\User;
use App\Services\PaymentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class PaymentTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_redirected_to_login_when_viewing_payments(): void
    {
        $this->get(route('payments.index'))
            ->assertRedirect(route('login'));
    }

    public function test_staff_can_view_payments_but_cannot_record_them(): void
    {
        $staff = User::factory()->create();
        $data = $this->validPaymentData();

        $this->actingAs($staff)
            ->get(route('payments.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Payments/Index')
                ->where('can.create', false)
                ->etc()
            );

        $this->actingAs($staff)
            ->post(route('payments.store'), $data)
            ->assertForbidden();

        $this->assertDatabaseCount('payments', 0);
    }

    public function test_admin_can_view_filtered_payments_with_real_stats(): void
    {
        $admin = User::factory()->admin()->create();
        $data = $this->validPaymentData([
            'receipt_number' => 'PAY-REPORT-001',
            'amount' => '1500.00',
        ]);
        app(PaymentService::class)->create($data, $admin);

        $response = $this->actingAs($admin)->get(route('payments.index', [
            'search' => 'PAY-REPORT',
            'method' => Payment::METHOD_CASH,
        ]));

        $response->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Payments/Index')
                ->where('filters.search', 'PAY-REPORT')
                ->where('filters.method', Payment::METHOD_CASH)
                ->has('payments.data', 1)
                ->where('payments.data.0.receipt_number', 'PAY-REPORT-001')
                ->where('stats.total_collected', '1500.00')
                ->where('stats.payment_count', 1)
                ->where('can.create', true)
                ->etc()
            );
    }

    public function test_receipt_number_is_normalized_when_recorded_over_http(): void
    {
        $admin = User::factory()->admin()->create();
        $data = $this->validPaymentData([
            'receipt_number' => ' pay-lowercase-001 ',
        ]);

        $this->actingAs($admin)
            ->post(route('payments.store'), $data)
            ->assertRedirect(route('payments.index'));

        $this->assertDatabaseHas('payments', [
            'receipt_number' => 'PAY-LOWERCASE-001',
            'created_by' => $admin->id,
        ]);
    }

    public function test_admin_can_record_a_payment_and_sale_balances_are_updated(): void
    {
        $admin = User::factory()->admin()->create();
        $data = $this->validPaymentData([
            'receipt_number' => 'pay-http-001',
            'amount' => '1500.00',
        ]);

        $response = $this->actingAs($admin)->post(route('payments.store'), $data);

        $sale = Sale::query()->findOrFail($data['sale_id']);

        $response->assertRedirect(route('payments.index'))
            ->assertSessionHas('success', 'Payment recorded successfully.');

        $this->assertDatabaseHas('payments', [
            'sale_id' => $sale->id,
            'receipt_number' => 'PAY-HTTP-001',
            'amount' => 1500.00,
            'method' => Payment::METHOD_CASH,
            'created_by' => $admin->id,
        ]);
        $this->assertSame('1500.00', $sale->fresh()->paid_amount);
        $this->assertSame('3500.00', $sale->fresh()->due_amount);
        $this->assertSame(Sale::PAYMENT_STATUS_PARTIAL, $sale->fresh()->payment_status);
    }

    public function test_final_payment_marks_the_sale_as_paid(): void
    {
        $admin = User::factory()->admin()->create();
        $data = $this->validPaymentData(['amount' => '5000.00']);

        $this->actingAs($admin)->post(route('payments.store'), $data)
            ->assertRedirect(route('payments.index'));

        $sale = Sale::query()->findOrFail($data['sale_id']);

        $this->assertSame('5000.00', $sale->fresh()->paid_amount);
        $this->assertSame('0.00', $sale->fresh()->due_amount);
        $this->assertSame(Sale::PAYMENT_STATUS_PAID, $sale->fresh()->payment_status);
    }

    public function test_overpayment_is_rejected_and_no_payment_is_recorded(): void
    {
        $admin = User::factory()->admin()->create();
        $data = $this->validPaymentData(['amount' => '5001.00']);

        $response = $this->actingAs($admin)->post(route('payments.store'), $data);

        $response->assertRedirect(route('payments.index'))
            ->assertSessionHas('error');

        $this->assertStringContainsString('exceeds the outstanding balance', session('error'));
        $this->assertDatabaseCount('payments', 0);
        $this->assertSame('0.00', Sale::query()->findOrFail($data['sale_id'])->fresh()->paid_amount);
    }

    public function test_receipt_number_must_be_unique(): void
    {
        $admin = User::factory()->admin()->create();
        $existing = $this->validPaymentData(['receipt_number' => 'PAY-DUPLICATE-001']);
        app(PaymentService::class)->create($existing, $admin);

        $this->actingAs($admin)
            ->post(route('payments.store'), $this->validPaymentData([
                'receipt_number' => 'pay-duplicate-001',
            ]))
            ->assertSessionHasErrors(['receipt_number']);

        $this->assertDatabaseCount('payments', 1);
    }

    public function test_payment_validation_rules_are_enforced(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->post(route('payments.store'), $this->validPaymentData(['method' => 'crypto']))
            ->assertSessionHasErrors(['method']);

        $this->actingAs($admin)
            ->post(route('payments.store'), $this->validPaymentData([
                'payment_date' => now()->addDay()->toDateString(),
            ]))
            ->assertSessionHasErrors(['payment_date']);

        $this->actingAs($admin)
            ->post(route('payments.store'), $this->validPaymentData(['amount' => '0.00']))
            ->assertSessionHasErrors(['amount']);
    }

    public function test_payment_options_only_include_sales_with_an_outstanding_balance(): void
    {
        $admin = User::factory()->admin()->create();
        $paidSale = Sale::factory()->create(['invoice_number' => 'SAL-PAID-001', 'total_amount' => '1000.00']);
        $openSale = Sale::factory()->create(['invoice_number' => 'SAL-OPEN-001', 'total_amount' => '2000.00']);

        app(PaymentService::class)->create($this->validPaymentData([
            'sale_id' => $paidSale->id,
            'receipt_number' => 'PAY-FULL-001',
            'amount' => '1000.00',
        ]), $admin);

        $this->actingAs($admin)
            ->get(route('payments.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->has('saleOptions', 1)
                ->where('saleOptions.0.value', $openSale->id)
                ->where('saleOptions.0.due', '2000.00')
                ->etc()
            );
    }

    public function test_payment_policy_allows_both_roles_to_view_but_only_admin_to_record(): void
    {
        $payment = Payment::factory()->create();
        $admin = User::factory()->admin()->create();
        $staff = User::factory()->create();

        $this->assertTrue(Gate::forUser($admin)->allows('viewAny', Payment::class));
        $this->assertTrue(Gate::forUser($admin)->allows('create', Payment::class));
        $this->assertTrue(Gate::forUser($admin)->allows('view', $payment));
        $this->assertTrue(Gate::forUser($staff)->allows('viewAny', Payment::class));
        $this->assertTrue(Gate::forUser($staff)->allows('view', $payment));
        $this->assertFalse(Gate::forUser($staff)->allows('create', Payment::class));
        $this->assertFalse(Gate::forUser($admin)->allows('update', $payment));
        $this->assertFalse(Gate::forUser($admin)->allows('delete', $payment));
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function validPaymentData(array $overrides = []): array
    {
        $defaults = [
            'receipt_number' => 'PAY-TEST-001',
            'payment_date' => now()->toDateString(),
            'amount' => '1500.00',
            'method' => Payment::METHOD_CASH,
            'reference' => null,
            'notes' => 'Test payment.',
        ];

        if (! array_key_exists('sale_id', $overrides)) {
            $defaults['sale_id'] = Sale::factory()->create(['total_amount' => '5000.00'])->id;
        }

        return array_merge($defaults, $overrides);
    }
}

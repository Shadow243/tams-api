<?php

namespace Tests\Feature;

use App\Models\CustomerAccount;
use App\Models\Customer;
use App\Models\Currency;
use App\Models\Branch;
use App\Models\User;
use App\Models\AccountInterestSetting;
use App\Services\CustomerAccountService;
use App\Services\InterestCalculationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CustomerAccountInterestTest extends TestCase
{
    use RefreshDatabase;

    private CustomerAccountService $accountService;
    private InterestCalculationService $interestService;
    private User $user;
    private Customer $customer;
    private Currency $currency;
    private Branch $branch;

    protected function setUp(): void
    {
        parent::setUp();

        $this->accountService = app(CustomerAccountService::class);
        $this->interestService = new InterestCalculationService($this->accountService);

        // Create test data
        $this->user = User::factory()->create();
        $this->customer = Customer::factory()->create();
        $this->currency = Currency::factory()->create(['code' => 'XAF']);
        $this->branch = Branch::factory()->create();
    }

    /**
     * Test que les intérêts sont calculés sur la DETTE UNIQUEMENT, pas sur le montant retiré
     */
    public function test_interest_calculated_on_debt_amount_not_withdrawal_amount(): void
    {
        // Créer un compte avec un solde initial de 500 XAF
        $account = CustomerAccount::create([
            'customer_id' => $this->customer->id,
            'currency_id' => $this->currency->id,
            'branch_id' => $this->branch->id,
            'balance' => 500,
            'credit_limit' => 10000,
            'status' => 'active',
            'is_vip' => true,
        ]);

        // Configurer les intérêts à 5% mensuel sur solde négatif
        AccountInterestSetting::create([
            'customer_account_id' => $account->id,
            'interest_type' => 'percentage',
            'interest_rate' => 5.0,
            'application_period' => 'monthly',
            'apply_on_negative_balance' => true,
            'apply_on_positive_balance' => false,
            'is_active' => true,
            'next_application_date' => now(),
        ]);

        // Retirer 1000 XAF (plus que le solde disponible)
        $this->accountService->withdraw($account, 1000, $this->user, 'Test withdrawal');

        $account->refresh();

        // Vérifier que le solde est maintenant négatif (dette de 500 XAF)
        $this->assertEquals(-500, $account->balance);

        // Appliquer les intérêts
        $interestTransaction = $this->accountService->applyInterest($account, $this->user);

        $account->refresh();

        // Les intérêts doivent être calculés sur 500 XAF (la dette), pas sur 1000 XAF (le retrait)
        // Intérêts = 500 * 5% = 25 XAF
        $this->assertEquals(25, $interestTransaction->amount);

        // Le nouveau solde doit être -525 XAF (dette + intérêts)
        $this->assertEquals(-525, $account->balance);
    }

    /**
     * Test avec plusieurs retraits successifs
     */
    public function test_interest_on_cumulative_debt(): void
    {
        $account = CustomerAccount::create([
            'customer_id' => $this->customer->id,
            'currency_id' => $this->currency->id,
            'branch_id' => $this->branch->id,
            'balance' => 800,
            'credit_limit' => 5000,
            'status' => 'active',
            'is_vip' => true,
        ]);

        AccountInterestSetting::create([
            'customer_account_id' => $account->id,
            'interest_type' => 'percentage',
            'interest_rate' => 3.0,
            'application_period' => 'monthly',
            'apply_on_negative_balance' => true,
            'apply_on_positive_balance' => false,
            'is_active' => true,
            'next_application_date' => now(),
        ]);

        // Premier retrait: 1000 XAF
        $this->accountService->withdraw($account, 1000, $this->user);
        $account->refresh();
        $this->assertEquals(-200, $account->balance);

        // Deuxième retrait: 500 XAF
        $this->accountService->withdraw($account, 500, $this->user);
        $account->refresh();
        $this->assertEquals(-700, $account->balance);

        // Appliquer les intérêts
        $interestTransaction = $this->accountService->applyInterest($account, $this->user);
        $account->refresh();

        // Les intérêts doivent être calculés sur 700 XAF (dette cumulée)
        // Intérêts = 700 * 3% = 21 XAF
        $this->assertEquals(21, $interestTransaction->amount);
        $this->assertEquals(-721, $account->balance);
    }

    /**
     * Test avec dépôt après retrait
     */
    public function test_interest_after_partial_repayment(): void
    {
        $account = CustomerAccount::create([
            'customer_id' => $this->customer->id,
            'currency_id' => $this->currency->id,
            'branch_id' => $this->branch->id,
            'balance' => 300,
            'credit_limit' => 2000,
            'status' => 'active',
            'is_vip' => true,
        ]);

        AccountInterestSetting::create([
            'customer_account_id' => $account->id,
            'interest_type' => 'percentage',
            'interest_rate' => 4.0,
            'application_period' => 'monthly',
            'apply_on_negative_balance' => true,
            'apply_on_positive_balance' => false,
            'is_active' => true,
            'next_application_date' => now(),
        ]);

        // Retrait: 1000 XAF → Solde: -700 XAF
        $this->accountService->withdraw($account, 1000, $this->user);
        $account->refresh();
        $this->assertEquals(-700, $account->balance);

        // Dépôt: 400 XAF → Solde: -300 XAF
        $this->accountService->deposit($account, 400, $this->user);
        $account->refresh();
        $this->assertEquals(-300, $account->balance);

        // Appliquer les intérêts
        $interestTransaction = $this->accountService->applyInterest($account, $this->user);
        $account->refresh();

        // Les intérêts doivent être calculés sur 300 XAF (dette restante après dépôt)
        // Intérêts = 300 * 4% = 12 XAF
        $this->assertEquals(12, $interestTransaction->amount);
        $this->assertEquals(-312, $account->balance);
    }

    /**
     * Test avec montant fixe au lieu de pourcentage
     */
    public function test_fixed_amount_interest_on_debt(): void
    {
        $account = CustomerAccount::create([
            'customer_id' => $this->customer->id,
            'currency_id' => $this->currency->id,
            'branch_id' => $this->branch->id,
            'balance' => 200,
            'credit_limit' => 3000,
            'status' => 'active',
            'is_vip' => true,
        ]);

        // Intérêts fixes de 50 XAF par mois
        AccountInterestSetting::create([
            'customer_account_id' => $account->id,
            'interest_type' => 'fixed',
            'fixed_amount' => 50,
            'application_period' => 'monthly',
            'apply_on_negative_balance' => true,
            'apply_on_positive_balance' => false,
            'is_active' => true,
            'next_application_date' => now(),
        ]);

        // Retrait: 1000 XAF → Solde: -800 XAF
        $this->accountService->withdraw($account, 1000, $this->user);
        $account->refresh();
        $this->assertEquals(-800, $account->balance);

        // Appliquer les intérêts
        $interestTransaction = $this->accountService->applyInterest($account, $this->user);
        $account->refresh();

        // Les intérêts sont fixes: 50 XAF (indépendamment du montant de la dette)
        $this->assertEquals(50, $interestTransaction->amount);
        $this->assertEquals(-850, $account->balance);
    }

    /**
     * Test de simulation sans appliquer les intérêts
     */
    public function test_interest_simulation(): void
    {
        $account = CustomerAccount::create([
            'customer_id' => $this->customer->id,
            'currency_id' => $this->currency->id,
            'branch_id' => $this->branch->id,
            'balance' => 400,
            'credit_limit' => 5000,
            'status' => 'active',
            'is_vip' => true,
        ]);

        AccountInterestSetting::create([
            'customer_account_id' => $account->id,
            'interest_type' => 'percentage',
            'interest_rate' => 6.0,
            'application_period' => 'monthly',
            'apply_on_negative_balance' => true,
            'apply_on_positive_balance' => false,
            'is_active' => true,
            'next_application_date' => now(),
        ]);

        // Retrait pour créer une dette de 600 XAF
        $this->accountService->withdraw($account, 1000, $this->user);
        $account->refresh();
        $this->assertEquals(-600, $account->balance);

        // Simuler les intérêts SANS les appliquer
        $simulation = $this->interestService->simulateInterest($account);

        $this->assertTrue($simulation['applicable']);
        $this->assertEquals(-600, $simulation['current_balance']);
        
        // Intérêts simulés = 600 * 6% = 36 XAF
        $this->assertEquals(36, $simulation['interest_amount']);
        $this->assertEquals(-636, $simulation['balance_after']);
        $this->assertTrue($simulation['is_debt_interest']);

        // Vérifier que le solde n'a PAS changé (simulation uniquement)
        $account->refresh();
        $this->assertEquals(-600, $account->balance);
    }

    /**
     * Test que les intérêts NE sont PAS appliqués si le compte a un solde positif
     * et que apply_on_positive_balance est false
     */
    public function test_no_interest_on_positive_balance_when_disabled(): void
    {
        $account = CustomerAccount::create([
            'customer_id' => $this->customer->id,
            'currency_id' => $this->currency->id,
            'branch_id' => $this->branch->id,
            'balance' => 1000,
            'credit_limit' => 5000,
            'status' => 'active',
            'is_vip' => true,
        ]);

        AccountInterestSetting::create([
            'customer_account_id' => $account->id,
            'interest_type' => 'percentage',
            'interest_rate' => 5.0,
            'application_period' => 'monthly',
            'apply_on_negative_balance' => true,
            'apply_on_positive_balance' => false, // Désactivé pour solde positif
            'is_active' => true,
            'next_application_date' => now(),
        ]);

        // Tenter d'appliquer les intérêts sur un solde positif
        $interestTransaction = $this->accountService->applyInterest($account, $this->user);

        // Aucun intérêt ne doit être appliqué
        $this->assertNull($interestTransaction);

        // Le solde ne doit pas changer
        $account->refresh();
        $this->assertEquals(1000, $account->balance);
    }
}

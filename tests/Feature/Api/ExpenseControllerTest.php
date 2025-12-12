<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

use App\Models\Access;
use App\Models\Expense;
use App\Models\Expensetype;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class ExpenseControllerTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Expense $expense;

    private Product $product;

    private Expensetype $expensetype;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->product = Product::factory()->create(['ProductID' => 12345]);
        $this->expensetype = Expensetype::factory()->create(['ExpenseTypeID' => 1]);

        $this->expense = Expense::factory()->create([
            'ProductID' => $this->product->ProductID,
            'ExpenseID' => $this->expensetype->ExpenseTypeID,
            'ExpenseDate' => '2022-07-02',
            'Expense' => 100.50,
        ]);

        // Create access for user to product
        Access::factory()->create([
            'user_id' => $this->user->id,
            'accessible_id' => $this->product->id,
            'accessible_type' => 'product',
        ]);
    }

    public function test_can_list_expenses(): void
    {
        Expense::factory()->count(5)->create([
            'ProductID' => $this->product->ProductID,
            'ExpenseID' => $this->expensetype->ExpenseTypeID,
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/v1/expenses');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => ['id', 'ProductID', 'ExpenseID', 'ExpenseDate', 'Expense', 'created_at'],
                ],
                'meta',
                'links',
            ]);
    }

    public function test_can_show_expense(): void
    {
        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson("/api/v1/expenses/{$this->expense->id}");

        $response->assertStatus(200)
            ->assertJsonPath('data.id', $this->expense->id)
            ->assertJsonPath('data.Expense', (float) $this->expense->Expense);
    }

    public function test_can_create_expense(): void
    {
        $expenseData = [
            'ProductID' => $this->product->ProductID,
            'ExpenseID' => $this->expensetype->ExpenseTypeID,
            'ExpenseDate' => '2022-07-03',
            'Expense' => 200.75,
        ];

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/v1/expenses', $expenseData);

        $response->assertStatus(201)
            ->assertJsonPath('data.Expense', 200.75);

        $this->assertDatabaseHas('expenses', [
            'ProductID' => $this->product->ProductID,
            'ExpenseID' => $this->expensetype->ExpenseTypeID,
            'Expense' => 200.75,
        ]);
    }

    public function test_can_update_expense_with_access(): void
    {
        $updateData = [
            'Expense' => 150.25,
        ];

        $response = $this->actingAs($this->user, 'sanctum')
            ->putJson("/api/v1/expenses/{$this->expense->id}", $updateData);

        $response->assertStatus(200)
            ->assertJsonPath('data.Expense', 150.25);

        $this->assertDatabaseHas('expenses', [
            'id' => $this->expense->id,
            'Expense' => 150.25,
        ]);
    }

    public function test_cannot_update_expense_without_access(): void
    {
        $otherProduct = Product::factory()->create();
        $otherExpense = Expense::factory()->create([
            'ProductID' => $otherProduct->ProductID,
            'ExpenseID' => $this->expensetype->ExpenseTypeID,
        ]);
        $otherUser = User::factory()->create();

        // Видаляємо доступ до компанії для користувача
        $otherUser->accesses()->where('accessible_type', 'company')->delete();

        $response = $this->actingAs($otherUser, 'sanctum')
            ->putJson("/api/v1/expenses/{$otherExpense->id}", [
                'Expense' => 999.99,
            ]);

        $response->assertStatus(403);
    }

    public function test_can_delete_expense_with_access(): void
    {
        $response = $this->actingAs($this->user, 'sanctum')
            ->deleteJson("/api/v1/expenses/{$this->expense->id}");

        $response->assertStatus(204);

        $this->assertSoftDeleted('expenses', [
            'id' => $this->expense->id,
        ]);
    }

    public function test_validation_fails_for_missing_required_fields(): void
    {
        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/v1/expenses', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['ProductID', 'ExpenseID', 'ExpenseDate', 'Expense']);
    }

    public function test_guest_cannot_access_expenses_endpoint(): void
    {
        $response = $this->getJson('/api/v1/expenses');

        $response->assertStatus(401);
    }

    public function test_can_get_expense_statistics(): void
    {
        Expense::factory()->count(5)->create([
            'ProductID' => $this->product->ProductID,
            'ExpenseID' => $this->expensetype->ExpenseTypeID,
        ]);
        $deleted = Expense::factory()->create([
            'ProductID' => $this->product->ProductID,
            'ExpenseID' => $this->expensetype->ExpenseTypeID,
        ]);
        $deleted->delete();

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/v1/expenses/statistics');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'total',
                'deleted',
                'total_amount',
                'created_today',
                'created_this_week',
                'created_this_month',
            ]);
    }

    public function test_can_list_expenses_for_product(): void
    {
        Expense::factory()->count(3)->create([
            'ProductID' => $this->product->ProductID,
            'ExpenseID' => $this->expensetype->ExpenseTypeID,
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson("/api/v1/products/{$this->product->slug}/expenses");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => ['id', 'ProductID', 'ExpenseID', 'ExpenseDate', 'Expense'],
                ],
            ]);
    }

    public function test_can_filter_expenses_by_date_range(): void
    {
        Expense::factory()->create([
            'ProductID' => $this->product->ProductID,
            'ExpenseID' => $this->expensetype->ExpenseTypeID,
            'ExpenseDate' => '2022-07-01',
        ]);
        Expense::factory()->create([
            'ProductID' => $this->product->ProductID,
            'ExpenseID' => $this->expensetype->ExpenseTypeID,
            'ExpenseDate' => '2022-07-02',
        ]);
        Expense::factory()->create([
            'ProductID' => $this->product->ProductID,
            'ExpenseID' => $this->expensetype->ExpenseTypeID,
            'ExpenseDate' => '2022-07-03',
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/v1/expenses?start_date=2022-07-02&end_date=2022-07-02');

        $response->assertStatus(200);
    }

    public function test_can_create_expense_for_product_without_product_id(): void
    {
        $expenseData = [
            'ExpenseID' => $this->expensetype->ExpenseTypeID,
            'ExpenseDate' => '2022-07-04',
            'Expense' => 300.00,
        ];

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson("/api/v1/products/{$this->product->slug}/expenses", $expenseData);

        $response->assertStatus(201)
            ->assertJsonPath('data.ProductID', $this->product->ProductID);

        $this->assertEquals(300.00, $response->json('data.Expense'));

        $this->assertDatabaseHas('expenses', [
            'ProductID' => $this->product->ProductID,
            'ExpenseID' => $this->expensetype->ExpenseTypeID,
            'Expense' => 300.00,
        ]);
    }

    public function test_cannot_set_product_id_when_creating_through_product_route(): void
    {
        $otherProduct = Product::factory()->create();

        $expenseData = [
            'ProductID' => $otherProduct->ProductID, // Should be prohibited
            'ExpenseID' => $this->expensetype->ExpenseTypeID,
            'ExpenseDate' => '2022-07-05',
            'Expense' => 400.00,
        ];

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson("/api/v1/products/{$this->product->slug}/expenses", $expenseData);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['ProductID']);
    }

    public function test_can_show_expense_for_product(): void
    {
        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson("/api/v1/products/{$this->product->slug}/expenses/{$this->expense->id}");

        $response->assertStatus(200)
            ->assertJsonPath('data.id', $this->expense->id)
            ->assertJsonPath('data.ProductID', $this->product->ProductID);
    }

    public function test_returns_404_if_expense_not_belongs_to_product(): void
    {
        $otherProduct = Product::factory()->create();
        $otherExpense = Expense::factory()->create([
            'ProductID' => $otherProduct->ProductID,
            'ExpenseID' => $this->expensetype->ExpenseTypeID,
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson("/api/v1/products/{$this->product->slug}/expenses/{$otherExpense->id}");

        $response->assertStatus(404);
    }

    public function test_can_update_expense_for_product(): void
    {
        $updateData = [
            'Expense' => 250.50,
        ];

        $response = $this->actingAs($this->user, 'sanctum')
            ->putJson("/api/v1/products/{$this->product->slug}/expenses/{$this->expense->id}", $updateData);

        $response->assertStatus(200)
            ->assertJsonPath('data.Expense', 250.50)
            ->assertJsonPath('data.ProductID', $this->product->ProductID);
    }

    public function test_cannot_change_product_id_when_updating_through_product_route(): void
    {
        $otherProduct = Product::factory()->create();

        $updateData = [
            'ProductID' => $otherProduct->ProductID, // Should be prohibited
            'Expense' => 500.00,
        ];

        $response = $this->actingAs($this->user, 'sanctum')
            ->putJson("/api/v1/products/{$this->product->slug}/expenses/{$this->expense->id}", $updateData);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['ProductID']);
    }

    public function test_can_delete_expense_for_product(): void
    {
        $response = $this->actingAs($this->user, 'sanctum')
            ->deleteJson("/api/v1/products/{$this->product->slug}/expenses/{$this->expense->id}");

        $response->assertStatus(204);

        $this->assertSoftDeleted('expenses', [
            'id' => $this->expense->id,
        ]);
    }

    public function test_can_restore_expense(): void
    {
        $this->expense->delete();

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson("/api/v1/expenses/{$this->expense->id}/restore");

        $response->assertStatus(200)
            ->assertJsonPath('data.id', $this->expense->id);

        $this->assertDatabaseHas('expenses', [
            'id' => $this->expense->id,
            'deleted_at' => null,
        ]);
    }

    public function test_can_force_delete_expense(): void
    {
        $this->expense->delete();

        $response = $this->actingAs($this->user, 'sanctum')
            ->deleteJson("/api/v1/expenses/{$this->expense->id}/force");

        $response->assertStatus(204);

        $this->assertDatabaseMissing('expenses', [
            'id' => $this->expense->id,
        ]);
    }
}

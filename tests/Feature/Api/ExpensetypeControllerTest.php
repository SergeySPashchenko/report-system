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

final class ExpensetypeControllerTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Expensetype $expensetype;

    private Product $product;

    private Expense $expense;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->expensetype = Expensetype::factory()->create(['ExpenseTypeID' => 1, 'Name' => 'Test ExpenseType']);
        $this->product = Product::factory()->create(['ProductID' => 12345]);

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

    public function test_can_list_expensetypes(): void
    {
        Expensetype::factory()->count(5)->create();

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/v1/expensetypes');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => ['id', 'Name', 'slug', 'created_at'],
                ],
                'meta',
                'links',
            ]);
    }

    public function test_can_search_expensetypes(): void
    {
        Expensetype::factory()->create(['Name' => 'Marketing']);
        Expensetype::factory()->create(['Name' => 'Sales']);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/v1/expensetypes?search=Marketing');

        $response->assertStatus(200);
    }

    public function test_can_show_expensetype(): void
    {
        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson("/api/v1/expensetypes/{$this->expensetype->slug}");

        $response->assertStatus(200)
            ->assertJsonPath('data.id', $this->expensetype->id)
            ->assertJsonPath('data.Name', $this->expensetype->Name);
    }

    public function test_can_create_expensetype(): void
    {
        $expensetypeData = [
            'Name' => 'New ExpenseType',
        ];

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/v1/expensetypes', $expensetypeData);

        $response->assertStatus(201)
            ->assertJsonPath('data.Name', 'New ExpenseType');

        $this->assertDatabaseHas('expensetypes', [
            'Name' => 'New ExpenseType',
        ]);
    }

    public function test_can_update_expensetype(): void
    {
        $updateData = [
            'Name' => 'Updated ExpenseType Name',
        ];

        $response = $this->actingAs($this->user, 'sanctum')
            ->putJson("/api/v1/expensetypes/{$this->expensetype->slug}", $updateData);

        $response->assertStatus(200)
            ->assertJsonPath('data.Name', 'Updated ExpenseType Name');

        $this->assertDatabaseHas('expensetypes', [
            'id' => $this->expensetype->id,
            'Name' => 'Updated ExpenseType Name',
        ]);
    }

    public function test_can_delete_expensetype(): void
    {
        $response = $this->actingAs($this->user, 'sanctum')
            ->deleteJson("/api/v1/expensetypes/{$this->expensetype->slug}");

        $response->assertStatus(204);

        $this->assertSoftDeleted('expensetypes', [
            'id' => $this->expensetype->id,
        ]);
    }

    public function test_validation_fails_for_missing_name(): void
    {
        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/v1/expensetypes', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['Name']);
    }

    public function test_guest_cannot_access_expensetypes_endpoint(): void
    {
        $response = $this->getJson('/api/v1/expensetypes');

        $response->assertStatus(401);
    }

    public function test_can_get_expensetype_statistics(): void
    {
        Expensetype::factory()->count(5)->create();
        $deleted = Expensetype::factory()->create();
        $deleted->delete();

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/v1/expensetypes/statistics');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'total',
                'deleted',
                'created_today',
                'created_this_week',
                'created_this_month',
            ]);
    }

    public function test_can_restore_expensetype(): void
    {
        $this->expensetype->delete();

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson("/api/v1/expensetypes/{$this->expensetype->id}/restore");

        $response->assertStatus(200)
            ->assertJsonPath('data.id', $this->expensetype->id);

        $this->assertDatabaseHas('expensetypes', [
            'id' => $this->expensetype->id,
            'deleted_at' => null,
        ]);
    }

    public function test_can_force_delete_expensetype(): void
    {
        $this->expensetype->delete();

        $response = $this->actingAs($this->user, 'sanctum')
            ->deleteJson("/api/v1/expensetypes/{$this->expensetype->id}/force");

        $response->assertStatus(204);

        $this->assertDatabaseMissing('expensetypes', [
            'id' => $this->expensetype->id,
        ]);
    }

    public function test_can_list_expenses_for_expensetype(): void
    {
        Expense::factory()->count(3)->create([
            'ProductID' => $this->product->ProductID,
            'ExpenseID' => $this->expensetype->ExpenseTypeID,
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson("/api/v1/expensetypes/{$this->expensetype->slug}/expenses");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => ['id', 'ProductID', 'ExpenseID', 'ExpenseDate', 'Expense'],
                ],
            ]);
    }

    public function test_can_create_expense_for_expensetype_without_expensetype_id(): void
    {
        $expenseData = [
            'ProductID' => $this->product->ProductID,
            'ExpenseDate' => '2022-07-06',
            'Expense' => 500.00,
        ];

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson("/api/v1/expensetypes/{$this->expensetype->slug}/expenses", $expenseData);

        $response->assertStatus(201)
            ->assertJsonPath('data.ExpenseID', $this->expensetype->ExpenseTypeID);

        $this->assertEquals(500.00, $response->json('data.Expense'));

        $this->assertDatabaseHas('expenses', [
            'ProductID' => $this->product->ProductID,
            'ExpenseID' => $this->expensetype->ExpenseTypeID,
            'Expense' => 500.00,
        ]);
    }

    public function test_cannot_set_expensetype_id_when_creating_through_expensetype_route(): void
    {
        $otherExpensetype = Expensetype::factory()->create();

        $expenseData = [
            'ProductID' => $this->product->ProductID,
            'ExpenseID' => $otherExpensetype->ExpenseTypeID, // Should be prohibited
            'ExpenseDate' => '2022-07-07',
            'Expense' => 600.00,
        ];

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson("/api/v1/expensetypes/{$this->expensetype->slug}/expenses", $expenseData);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['ExpenseID']);
    }

    public function test_can_show_expense_for_expensetype(): void
    {
        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson("/api/v1/expensetypes/{$this->expensetype->slug}/expenses/{$this->expense->id}");

        $response->assertStatus(200)
            ->assertJsonPath('data.id', $this->expense->id)
            ->assertJsonPath('data.ExpenseID', $this->expensetype->ExpenseTypeID);
    }

    public function test_returns_404_if_expense_not_belongs_to_expensetype(): void
    {
        $otherExpensetype = Expensetype::factory()->create();
        $otherExpense = Expense::factory()->create([
            'ProductID' => $this->product->ProductID,
            'ExpenseID' => $otherExpensetype->ExpenseTypeID,
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson("/api/v1/expensetypes/{$this->expensetype->slug}/expenses/{$otherExpense->id}");

        $response->assertStatus(404);
    }

    public function test_can_update_expense_for_expensetype(): void
    {
        $updateData = [
            'Expense' => 350.75,
        ];

        $response = $this->actingAs($this->user, 'sanctum')
            ->putJson("/api/v1/expensetypes/{$this->expensetype->slug}/expenses/{$this->expense->id}", $updateData);

        $response->assertStatus(200)
            ->assertJsonPath('data.Expense', 350.75)
            ->assertJsonPath('data.ExpenseID', $this->expensetype->ExpenseTypeID);
    }

    public function test_cannot_change_expensetype_id_when_updating_through_expensetype_route(): void
    {
        $otherExpensetype = Expensetype::factory()->create();

        $updateData = [
            'ExpenseID' => $otherExpensetype->ExpenseTypeID, // Should be prohibited
            'Expense' => 700.00,
        ];

        $response = $this->actingAs($this->user, 'sanctum')
            ->putJson("/api/v1/expensetypes/{$this->expensetype->slug}/expenses/{$this->expense->id}", $updateData);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['ExpenseID']);
    }

    public function test_can_delete_expense_for_expensetype(): void
    {
        $response = $this->actingAs($this->user, 'sanctum')
            ->deleteJson("/api/v1/expensetypes/{$this->expensetype->slug}/expenses/{$this->expense->id}");

        $response->assertStatus(204);

        $this->assertSoftDeleted('expenses', [
            'id' => $this->expense->id,
        ]);
    }
}

# Company API Architecture

## 📁 Project Structure

```
app/
├── Http/
│   ├── Controllers/Api/
│   │   └── CompanyController.php      # Company CRUD endpoints
│   │
│   ├── Requests/
│   │   ├── StoreCompanyRequest.php   # Create company validation
│   │   └── UpdateCompanyRequest.php  # Update company validation
│   │
│   └── Resources/
│       ├── CompanyResource.php       # Single company transformation
│       └── CompanyCollection.php      # Company collection transformation
│
├── Models/
│   ├── Company.php                   # Company model with traits
│   └── Access.php                    # Polymorphic access model
│
├── Policies/
│   └── CompanyPolicy.php             # Company authorization rules
│
├── Queries/
│   └── CompanyQuery.php              # Reusable query builder for companies
│
└── Services/
    └── CompanyService.php            # Business logic layer
```

---

## 🏗️ Architecture Layers

### 1. **Controller Layer** (Thin)
- Handles HTTP requests/responses
- Validates input (via Form Requests)
- Authorizes actions (via Policies)
- Delegates to Service layer

### 2. **Service Layer** (Business Logic)
- Contains business rules
- Orchestrates multiple operations
- Uses Query objects for data retrieval
- Returns domain objects

### 3. **Query Layer** (Data Access)
- Encapsulates complex queries
- Reusable query builders
- Improves testability

### 4. **Access System** (Polymorphic)
- Users access companies through `Access` model
- Polymorphic relationship allows future expansion
- First user creates "Main" company automatically

---

## 🔄 Request Flow

```
HTTP Request
    ↓
Middleware (Rate Limit, Auth, EnsureUserIsActive)
    ↓
Controller (Validation, Authorization)
    ↓
Service (Business Logic)
    ↓
Query (Database)
    ↓
Response (Resource Transformation)
```

---

## 📝 Code Examples

### Using CompanyService

```php
use App\Services\CompanyService;

class CompanyController extends Controller
{
    public function __construct(
        private readonly CompanyService $companyService
    ) {}
    
    public function index(Request $request)
    {
        $companies = $this->companyService->getPaginatedCompanies(
            search: $request->search,
            sortBy: $request->sort_by,
            perPage: 15
        );
        
        return new CompanyCollection($companies);
    }
}
```

### Using CompanyQuery

```php
use App\Queries\CompanyQuery;

$query = new CompanyQuery();

// Search and paginate
$companies = $query
    ->search('tech')
    ->sort('name')
    ->paginate(20);

// Find by slug
$company = $query->findBySlug('main');
```

### Automatic Company Assignment

When a user is created, the `AssignCompanyToUser` listener automatically:
1. Checks if "Main" company exists
2. Creates "Main" company if it's the first user
3. Assigns access to the company for the new user

```php
// This happens automatically via UserCreated event
$user = User::create($data);
// → UserObserver::created()
// → UserCreated event
// → AssignCompanyToUser listener
// → Creates "Main" company (if first user)
// → Creates Access record
```

---

## 🛡️ Authorization

### CompanyPolicy Rules

- **viewAny**: All authenticated users can view company list
- **view**: All authenticated users can view companies
- **create**: All authenticated users can create companies
- **update**: Users can only update companies they have access to
- **delete**: Users can only delete companies they have access to (except "Main")
- **restore**: Users can restore companies they have access to
- **forceDelete**: Users can permanently delete companies they have access to (except "Main")

**Important**: The "Main" company cannot be deleted or force deleted.

---

## 🎯 Service Methods

### CompanyService API

```php
// Retrieval
$companyService->getPaginatedCompanies(?string $search, ?string $sortBy, ...);
$companyService->findBySlug(string $slug): ?Company;
$companyService->getStatistics(): array;

// Mutations
$companyService->create(array $data): Company;
$companyService->update(Company $company, array $data): Company;
$companyService->delete(Company $company): bool;
$companyService->restore(string $id): Company;
$companyService->forceDelete(string $id): bool;
```

---

## 📊 Company Statistics Endpoint

**Route**: `GET /api/v1/companies/statistics`

**Response**:

```json
{
  "total": 25,
  "deleted": 2,
  "created_today": 1,
  "created_this_week": 5,
  "created_this_month": 12
}
```

---

## 🔗 Relationships

### User ↔ Company (Many-to-Many via Access)

```php
// Get user's companies
$user->companies; // Collection of Company models

// Get user's main company
$user->company(); // Returns Company or null

// Get company's users
$company->users; // Collection of User models

// Check if user has access to company
$user->companies()->where('companies.id', $company->id)->exists();
```

### Access Model

The `Access` model provides polymorphic access:
- `user_id`: The user who has access
- `accessible_id`: The ID of the accessible resource
- `accessible_type`: The type of resource (Company::class)

This allows future expansion to other resource types.

---

## 🚀 API Endpoints

### List Companies
```
GET /api/v1/companies
Query Parameters:
  - search: string (optional)
  - sort_by: string (optional)
  - sort_direction: asc|desc (optional, default: asc)
  - per_page: integer (optional, default: 15)
```

### Get Company
```
GET /api/v1/companies/{slug}
```

### Create Company
```
POST /api/v1/companies
Body:
{
  "name": "Company Name"
}
```

### Update Company
```
PUT/PATCH /api/v1/companies/{slug}
Body:
{
  "name": "Updated Name"
}
```

### Delete Company
```
DELETE /api/v1/companies/{slug}
```

### Restore Company
```
POST /api/v1/companies/{id}/restore
```

### Force Delete Company
```
DELETE /api/v1/companies/{id}/force
```

### Get Statistics
```
GET /api/v1/companies/statistics
```

---

## 🔔 Event System

### UserCreated Event → AssignCompanyToUser Listener

When a user is created:
1. `UserObserver::created()` fires `UserCreated` event
2. `AssignCompanyToUser` listener handles the event:
   - Checks if "Main" company exists
   - Creates "Main" company if it's the first user
   - Creates `Access` record linking user to company

**Note**: This ensures every user has access to at least one company.

---

## 🧪 Testing

### Service Layer Tests

```php
use App\Services\CompanyService;

test('service creates company correctly', function () {
    $service = app(CompanyService::class);
    
    $company = $service->create([
        'name' => 'Test Company',
    ]);
    
    expect($company->name)->toBe('Test Company');
    expect($company->slug)->toBe('test-company');
});
```

### Policy Tests

```php
test('user can only update companies they have access to', function () {
    $user = User::factory()->create();
    $company = Company::factory()->create();
    
    // User doesn't have access
    $this->actingAs($user)
        ->putJson("/api/v1/companies/{$company->slug}", [
            'name' => 'Updated'
        ])
        ->assertForbidden();
    
    // Create access
    Access::factory()->create([
        'user_id' => $user->id,
        'accessible_id' => $company->id,
        'accessible_type' => Company::class,
    ]);
    
    // Now user can update
    $this->actingAs($user)
        ->putJson("/api/v1/companies/{$company->slug}", [
            'name' => 'Updated'
        ])
        ->assertSuccessful();
});
```

---

## 📚 Best Practices

### 1. **Keep Controllers Thin**
✅ Do:
```php
public function store(StoreCompanyRequest $request): CompanyResource
{
    $company = $this->companyService->create($request->validated());
    return new CompanyResource($company);
}
```

❌ Don't:
```php
public function store(Request $request)
{
    // 50 lines of validation, business logic, queries...
}
```

### 2. **Use Service Layer for Business Logic**
✅ Do:
```php
class CompanyService
{
    public function delete(Company $company): bool
    {
        // Business logic here
        return $company->delete();
    }
}
```

### 3. **Use Query Objects for Complex Queries**
✅ Do:
```php
$companies = $this->companyQuery
    ->search($search)
    ->sort('name')
    ->paginate(20);
```

❌ Don't:
```php
$companies = Company::where('name', 'like', "%{$search}%")
    ->orderBy('name')
    ->paginate(20);
```

### 4. **Check Access Before Operations**
✅ Do:
```php
public function update(User $user, Company $company): bool
{
    return $user->companies()->where('companies.id', $company->id)->exists();
}
```

---

## 🔍 Debugging

### View Registered Routes

```bash
php artisan route:list --path=api/v1/companies
```

### Test Company Creation

```bash
php artisan tinker
```

```php
$company = Company::create(['name' => 'Test Company']);
$company->slug; // 'test-company'
```

### Check User's Companies

```php
$user = User::first();
$user->companies; // Collection of Company models
$user->company(); // Main company or null
```

---

## 🎁 Special Features

### Automatic "Main" Company

- First user in the system automatically creates "Main" company
- All subsequent users get access to the same "Main" company
- "Main" company cannot be deleted or force deleted
- This ensures every user has at least one company

### Slug Generation

- Company slugs are automatically generated from the name
- Uses `HasSlug` trait from Spatie
- Slugs are unique and URL-friendly
- Slugs don't change when name is updated

### Soft Deletes

- Companies use soft deletes
- Deleted companies can be restored
- Access records are also soft deleted when company is deleted

---

## 📋 Summary

The Company API provides a complete CRUD interface for managing companies with:

- ✅ Full CRUD operations
- ✅ Search and pagination
- ✅ Authorization via policies
- ✅ Automatic company assignment for users
- ✅ Statistics endpoint
- ✅ Soft deletes with restore
- ✅ Polymorphic access system
- ✅ Slug-based routing
- ✅ Comprehensive validation

All following the same architectural patterns as the User API for consistency.

# Brand API Architecture

## 📁 Project Structure

```
app/
├── Http/
│   ├── Controllers/Api/
│   │   └── BrandController.php      # Brand CRUD endpoints
│   │
│   ├── Requests/
│   │   ├── StoreBrandRequest.php    # Create brand validation
│   │   └── UpdateBrandRequest.php   # Update brand validation
│   │
│   └── Resources/
│       ├── BrandResource.php        # Single brand transformation
│       └── BrandCollection.php      # Brand collection transformation
│
├── Models/
│   ├── Brand.php                    # Brand model with traits
│   └── Access.php                   # Polymorphic access model
│
├── Policies/
│   └── BrandPolicy.php              # Brand authorization rules
│
├── Queries/
│   └── BrandQuery.php                # Reusable query builder for brands
│
└── Services/
    └── BrandService.php              # Business logic layer
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
- Users access brands through `Access` model
- Polymorphic relationship allows future expansion
- **Brands are NOT automatically created** (unlike Company)

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

### Using BrandService

```php
use App\Services\BrandService;

class BrandController extends Controller
{
    public function __construct(
        private readonly BrandService $brandService
    ) {}
    
    public function index(Request $request)
    {
        $brands = $this->brandService->getPaginatedBrands(
            search: $request->search,
            sortBy: $request->sort_by,
            perPage: 15
        );
        
        return new BrandCollection($brands);
    }
}
```

### Using BrandQuery

```php
use App\Queries\BrandQuery;

$query = new BrandQuery();

// Search and paginate
$brands = $query
    ->search('tech')
    ->sort('name')
    ->paginate(20);

// Find by slug
$brand = $query->findBySlug('nike');
```

### Manual Access Assignment

Unlike Company, brands are NOT automatically assigned. You need to create Access records manually:

```php
use App\Models\Access;
use App\Models\Brand;
use App\Models\User;

// Create brand
$brand = Brand::create(['name' => 'Nike']);

// Assign access to user
Access::create([
    'user_id' => $user->id,
    'accessible_id' => $brand->id,
    'accessible_type' => 'brand',
]);
```

---

## 🛡️ Authorization

### BrandPolicy Rules

- **viewAny**: All authenticated users can view brand list
- **view**: All authenticated users can view brands
- **create**: All authenticated users can create brands
- **update**: Users can only update brands they have access to
- **delete**: Users can only delete brands they have access to
- **restore**: Users can restore brands they have access to
- **forceDelete**: Users can permanently delete brands they have access to

**Note**: Unlike Company, there's no protected "Main" brand - all brands can be deleted if user has access.

---

## 🎯 Service Methods

### BrandService API

```php
// Retrieval
$brandService->getPaginatedBrands(?string $search, ?string $sortBy, ...);
$brandService->findBySlug(string $slug): ?Brand;
$brandService->getStatistics(): array;

// Mutations
$brandService->create(array $data): Brand;
$brandService->update(Brand $brand, array $data): Brand;
$brandService->delete(Brand $brand): bool;
$brandService->restore(string $id): Brand;
$brandService->forceDelete(string $id): bool;
```

---

## 📊 Brand Statistics Endpoint

**Route**: `GET /api/v1/brands/statistics`

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

### User ↔ Brand (Many-to-Many via Access)

```php
// Get user's brands
$user->brands; // Collection of Brand models

// Get brand's users
$brand->users; // Collection of User models

// Check if user has access to brand
$user->brands()->where('brands.id', $brand->id)->exists();
```

### Access Model

The `Access` model provides polymorphic access:
- `user_id`: The user who has access
- `accessible_id`: The ID of the accessible resource
- `accessible_type`: The type of resource (`'brand'` for brands)

---

## 🚀 API Endpoints

### List Brands
```
GET /api/v1/brands
Query Parameters:
  - search: string (optional)
  - sort_by: string (optional)
  - sort_direction: asc|desc (optional, default: asc)
  - per_page: integer (optional, default: 15)
```

### Get Brand
```
GET /api/v1/brands/{slug}
```

### Create Brand
```
POST /api/v1/brands
Body:
{
  "name": "Brand Name"
}
```

### Update Brand
```
PUT/PATCH /api/v1/brands/{slug}
Body:
{
  "name": "Updated Name"
}
```

### Delete Brand
```
DELETE /api/v1/brands/{slug}
```

### Restore Brand
```
POST /api/v1/brands/{id}/restore
```

### Force Delete Brand
```
DELETE /api/v1/brands/{id}/force
```

### Get Statistics
```
GET /api/v1/brands/statistics
```

---

## 🔔 Key Differences from Company

### No Automatic Creation
- **Company**: Automatically creates "Main" company for first user
- **Brand**: No automatic creation - brands must be created manually

### No Protected Brands
- **Company**: "Main" company cannot be deleted
- **Brand**: All brands can be deleted if user has access

### Manual Access Assignment
- **Company**: Access is automatically assigned via `AssignCompanyToUser` listener
- **Brand**: Access must be assigned manually through Access model

---

## 🧪 Testing

### Service Layer Tests

```php
use App\Services\BrandService;

test('service creates brand correctly', function () {
    $service = app(BrandService::class);
    
    $brand = $service->create([
        'name' => 'Test Brand',
    ]);
    
    expect($brand->name)->toBe('Test Brand');
    expect($brand->slug)->toBe('test-brand');
});
```

### Policy Tests

```php
test('user can only update brands they have access to', function () {
    $user = User::factory()->create();
    $brand = Brand::factory()->create();
    
    // User doesn't have access
    $this->actingAs($user)
        ->putJson("/api/v1/brands/{$brand->slug}", [
            'name' => 'Updated'
        ])
        ->assertForbidden();
    
    // Create access
    Access::factory()->create([
        'user_id' => $user->id,
        'accessible_id' => $brand->id,
        'accessible_type' => 'brand',
    ]);
    
    // Now user can update
    $this->actingAs($user)
        ->putJson("/api/v1/brands/{$brand->slug}", [
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
public function store(StoreBrandRequest $request): BrandResource
{
    $brand = $this->brandService->create($request->validated());
    return new BrandResource($brand);
}
```

### 2. **Use Service Layer for Business Logic**
✅ Do:
```php
class BrandService
{
    public function delete(Brand $brand): bool
    {
        // Business logic here
        return $brand->delete();
    }
}
```

### 3. **Use Query Objects for Complex Queries**
✅ Do:
```php
$brands = $this->brandQuery
    ->search($search)
    ->sort('name')
    ->paginate(20);
```

### 4. **Check Access Before Operations**
✅ Do:
```php
public function update(User $user, Brand $brand): bool
{
    return $user->brands()->where('brands.id', $brand->id)->exists();
}
```

---

## 🔍 Debugging

### View Registered Routes

```bash
php artisan route:list --path=api/v1/brands
```

### Test Brand Creation

```bash
php artisan tinker
```

```php
$brand = Brand::create(['name' => 'Test Brand']);
$brand->slug; // 'test-brand'
```

### Check User's Brands

```php
$user = User::first();
$user->brands; // Collection of Brand models
```

### Assign Access to Brand

```php
use App\Models\Access;

Access::create([
    'user_id' => $user->id,
    'accessible_id' => $brand->id,
    'accessible_type' => 'brand',
]);
```

---

## 🎁 Special Features

### Slug Generation

- Brand slugs are automatically generated from the name
- Uses `HasSlug` trait from Spatie
- Slugs are unique and URL-friendly
- Slugs can be duplicated (unlike Company) - uses `allowDuplicateSlugs()`

### Soft Deletes

- Brands use soft deletes
- Deleted brands can be restored
- Access records are also soft deleted when brand is deleted

### No Automatic Assignment

- Unlike Company, brands are NOT automatically assigned to users
- Access must be granted manually through Access model
- This allows fine-grained control over brand access

---

## 📋 Summary

The Brand API provides a complete CRUD interface for managing brands with:

- ✅ Full CRUD operations
- ✅ Search and pagination
- ✅ Authorization via policies
- ✅ Manual access assignment (no automatic creation)
- ✅ Statistics endpoint
- ✅ Soft deletes with restore
- ✅ Polymorphic access system
- ✅ Slug-based routing
- ✅ Comprehensive validation

All following the same architectural patterns as the Company API for consistency, but without automatic brand creation.

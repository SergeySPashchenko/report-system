# Product API Architecture

## 📁 Project Structure

```
app/
├── Http/
│   ├── Controllers/Api/
│   │   └── ProductController.php      # Product CRUD endpoints
│   │
│   ├── Requests/
│   │   ├── StoreProductRequest.php    # Create product validation
│   │   └── UpdateProductRequest.php   # Update product validation
│   │
│   └── Resources/
│       ├── ProductResource.php        # Single product transformation
│       └── ProductCollection.php      # Product collection transformation
│
├── Models/
│   ├── Product.php                    # Product model with relationships
│   └── Access.php                    # Polymorphic access model
│
├── Policies/
│   ├── ProductPolicy.php              # Product authorization rules
│   └── Concerns/
│       └── HasAccessCheck.php         # Shared access check logic
│
├── Queries/
│   └── ProductQuery.php               # Reusable query builder for products
│
└── Services/
    └── ProductService.php             # Business logic layer
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
- Filters products based on user access
- Returns domain objects

### 3. **Query Layer** (Data Access)
- Encapsulates complex queries
- Filters by user access automatically
- Reusable query builders
- Improves testability

### 4. **Access System** (Polymorphic)
- Users access products through `Access` model
- Polymorphic relationship allows future expansion
- **Access Control Logic:**
  - Company users have access to everything
  - Brand users have access to all products of their brands
  - Product users have access only to their assigned products

---

## 🔄 Request Flow

```
HTTP Request
    ↓
Middleware (Rate Limit, Auth, EnsureUserIsActive)
    ↓
Controller (Validation, Authorization)
    ↓
Service (Business Logic + Access Filtering)
    ↓
Query (Database + Access Filtering)
    ↓
Response (Resource Transformation)
```

---

## 🔐 Access Control System

### Access Levels

1. **Company Access** (Full Access)
   - User has access to company → sees all products
   - No filtering applied

2. **Brand Access** (Brand-scoped Access)
   - User has access to specific brands → sees all products of those brands
   - Products filtered by `brand_id` matching user's accessible brands

3. **Product Access** (Product-scoped Access)
   - User has access to specific products → sees only those products
   - Products filtered by `id` matching user's accessible products

### Access Check Logic

```php
// In ProductPolicy
public function view(User $user, Product $product): bool
{
    // Company users see everything
    if ($user->company() !== null) {
        return true;
    }
    
    // Product users see their products
    if ($user->products()->exists()) {
        return $user->products()->where('id', $product->id)->exists();
    }
    
    // Brand users see products of their brands
    if ($user->brands()->exists() && $product->brand_id) {
        return $user->brands()->where('id', $product->brand_id)->exists();
    }
    
    return false;
}
```

---

## 📝 Code Examples

### Using ProductService

```php
use App\Services\ProductService;

class ProductController extends Controller
{
    public function __construct(
        private readonly ProductService $productService
    ) {}
    
    public function index(Request $request)
    {
        $products = $this->productService->getPaginatedProducts(
            user: $request->user(),
            search: $request->search,
            sortBy: $request->sort_by,
            perPage: 15
        );
        
        return new ProductCollection($products);
    }
}
```

### Using ProductQuery

```php
use App\Queries\ProductQuery;

$query = new ProductQuery();

// Automatically filters by user access
$products = $query
    ->reset()
    ->forUser($user)
    ->search('hemorrhoid')
    ->byBrand($brandId)
    ->sort('Product', 'asc')
    ->paginate(15);
```

---

## 🚀 API Endpoints

### Products
- `GET /api/v1/products` - List products (filtered by access)
- `POST /api/v1/products` - Create product
- `GET /api/v1/products/{slug}` - Get product
- `PUT/PATCH /api/v1/products/{slug}` - Update product
- `DELETE /api/v1/products/{slug}` - Delete product (soft delete)
- `POST /api/v1/products/{id}/restore` - Restore deleted product
- `DELETE /api/v1/products/{id}/force` - Permanently delete product
- `GET /api/v1/products/statistics` - Get product statistics (filtered by access)

### Query Parameters

#### List Products (`GET /api/v1/products`)
- `search` - Search by product name or slug
- `sort_by` - Column to sort by (default: `created_at`)
- `sort_direction` - Sort direction: `asc` or `desc` (default: `asc`)
- `per_page` - Items per page (default: 15)

---

## 📊 Product Model

### Fields
- `id` - ULID primary key
- `ProductID` - Unique integer ID from external system
- `Product` - Product name
- `slug` - URL-friendly identifier (auto-generated)
- `newSystem` - Boolean flag
- `Visible` - Boolean visibility flag
- `flyer` - Boolean flyer flag
- `brand_id` - Foreign key to brands table
- `main_category_id` - Foreign key to categories table
- `marketing_category_id` - Foreign key to categories table
- `gender_id` - Foreign key to genders table
- `created_at` - Creation timestamp
- `updated_at` - Update timestamp
- `deleted_at` - Soft delete timestamp

### Relationships
- `brand()` - BelongsTo Brand
- `main_category()` - BelongsTo Category
- `marketing_category()` - BelongsTo Category
- `gender()` - BelongsTo Gender
- `accesses()` - MorphMany Access
- `users()` - BelongsToMany User (through Access)

---

## 🛡️ Authorization

### Product Policy

- **viewAny**: User must have access to at least one product/brand/company
- **view**: User must have access to the specific product
- **create**: User must have access to at least one product/brand/company
- **update**: User must have access to the specific product
- **delete**: User must have access to the specific product
- **restore**: User must have had access to the product (checked via Access model)
- **forceDelete**: User must have access to the specific product

---

## 🔄 Synchronization

Products can be synchronized from external database using:

```bash
php artisan products:sync
```

This command:
1. Syncs categories and genders from external database
2. Syncs products with their relationships
3. Finds or creates brands by name
4. Maps old IDs to new ULIDs

---

## 📋 Validation Rules

### Store Product
- `ProductID` - Required, integer, unique
- `Product` - Required, string, max 255
- `newSystem` - Optional, boolean
- `Visible` - Optional, boolean
- `flyer` - Optional, boolean
- `brand_id` - Optional, exists in brands
- `main_category_id` - Optional, exists in categories
- `marketing_category_id` - Optional, exists in categories
- `gender_id` - Optional, exists in genders

### Update Product
- All fields optional
- `ProductID` must be unique if provided (excluding current product)

---

## 🧪 Testing

Products have comprehensive test coverage:
- Feature tests for API endpoints
- Policy tests for authorization
- Service tests for business logic
- Query tests for filtering

---

## 📝 Notes

1. **Access Filtering**: All product queries automatically filter by user access level
2. **Slug Generation**: Products use slug for URL (auto-generated from Product name)
3. **Soft Deletes**: Products support soft deletes with restore capability
4. **External Sync**: Products can be synced from external database maintaining old IDs
5. **Relationships**: Products are linked to brands, categories, and genders

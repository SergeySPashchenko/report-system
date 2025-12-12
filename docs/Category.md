# Category API Architecture

## 📁 Project Structure

```
app/
├── Http/
│   ├── Controllers/Api/
│   │   └── CategoryController.php      # Category CRUD endpoints
│   │
│   ├── Requests/
│   │   ├── StoreCategoryRequest.php   # Create category validation
│   │   └── UpdateCategoryRequest.php   # Update category validation
│   │
│   └── Resources/
│       ├── CategoryResource.php       # Single category transformation
│       └── CategoryCollection.php     # Category collection transformation
│
├── Models/
│   └── Category.php                   # Category model
│
├── Policies/
│   └── CategoryPolicy.php             # Category authorization rules
│
├── Queries/
│   └── CategoryQuery.php               # Reusable query builder for categories
│
└── Services/
    └── CategoryService.php             # Business logic layer
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

### 4. **Access System**
- **Categories are accessible to ALL authenticated users**
- No access filtering required
- Categories are reference data used by products

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

## 🔐 Access Control

**Categories are public to all authenticated users** - no access restrictions apply. This is because categories are reference data that products use, and all users need to see the full list of available categories.

---

## 📝 Code Examples

### Using CategoryService

```php
use App\Services\CategoryService;

class CategoryController extends Controller
{
    public function __construct(
        private readonly CategoryService $categoryService
    ) {}
    
    public function index(Request $request)
    {
        $categories = $this->categoryService->getPaginatedCategories(
            search: $request->search,
            sortBy: $request->sort_by,
            perPage: 15
        );
        
        return new CategoryCollection($categories);
    }
}
```

### Using CategoryQuery

```php
use App\Queries\CategoryQuery;

$query = new CategoryQuery();

$categories = $query
    ->reset()
    ->search('non-diet')
    ->sort('category_name', 'asc')
    ->paginate(15);
```

---

## 🚀 API Endpoints

### Categories
- `GET /api/v1/categories` - List categories
- `POST /api/v1/categories` - Create category
- `GET /api/v1/categories/{slug}` - Get category
- `PUT/PATCH /api/v1/categories/{slug}` - Update category
- `DELETE /api/v1/categories/{slug}` - Delete category (soft delete)
- `POST /api/v1/categories/{id}/restore` - Restore deleted category
- `DELETE /api/v1/categories/{id}/force` - Permanently delete category
- `GET /api/v1/categories/statistics` - Get category statistics

### Query Parameters

#### List Categories (`GET /api/v1/categories`)
- `search` - Search by category name or slug
- `sort_by` - Column to sort by (default: `created_at`)
- `sort_direction` - Sort direction: `asc` or `desc` (default: `asc`)
- `per_page` - Items per page (default: 15)

---

## 📊 Category Model

### Fields
- `id` - ULID primary key
- `category_id` - Integer ID from external system (unique)
- `category_name` - Category name
- `slug` - URL-friendly identifier (auto-generated)
- `created_at` - Creation timestamp
- `updated_at` - Update timestamp
- `deleted_at` - Soft delete timestamp

### Relationships
- `main_products()` - HasMany Product (as main_category)
- `marketing_products()` - HasMany Product (as marketing_category)

---

## 🛡️ Authorization

### Category Policy

**All methods return `true`** - categories are accessible to all authenticated users:
- `viewAny()` - All authenticated users can view list
- `view()` - All authenticated users can view category
- `create()` - All authenticated users can create categories
- `update()` - All authenticated users can update categories
- `delete()` - All authenticated users can delete categories
- `restore()` - All authenticated users can restore categories
- `forceDelete()` - All authenticated users can permanently delete categories

---

## 🔄 Synchronization

Categories can be synchronized from external database during product synchronization:

```bash
php artisan products:sync
```

This command:
1. Syncs categories from external database
2. Preserves old `category_id` for mapping
3. Creates categories if they don't exist
4. Updates category names if they changed

---

## 📋 Validation Rules

### Store Category
- `category_name` - Required, string, max 255
- `category_id` - Optional, integer, unique

### Update Category
- `category_name` - Optional, string, max 255
- `category_id` - Optional, integer, unique (excluding current category)

---

## 🧪 Testing

Categories have comprehensive test coverage:
- Feature tests for API endpoints
- Policy tests for authorization
- Service tests for business logic
- Query tests for filtering

---

## 📝 Notes

1. **Public Access**: Categories are accessible to all authenticated users
2. **Slug Generation**: Categories use slug for URL (auto-generated from category_name)
3. **Soft Deletes**: Categories support soft deletes with restore capability
4. **External Sync**: Categories can be synced from external database maintaining old IDs
5. **Reference Data**: Categories are used as reference data by products

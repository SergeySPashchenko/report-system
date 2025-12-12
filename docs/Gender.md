# Gender API Architecture

## 📁 Project Structure

```
app/
├── Http/
│   ├── Controllers/Api/
│   │   └── GenderController.php       # Gender CRUD endpoints
│   │
│   ├── Requests/
│   │   ├── StoreGenderRequest.php     # Create gender validation
│   │   └── UpdateGenderRequest.php    # Update gender validation
│   │
│   └── Resources/
│       ├── GenderResource.php         # Single gender transformation
│       └── GenderCollection.php      # Gender collection transformation
│
├── Models/
│   └── Gender.php                     # Gender model
│
├── Policies/
│   └── GenderPolicy.php               # Gender authorization rules
│
├── Queries/
│   └── GenderQuery.php                # Reusable query builder for genders
│
└── Services/
    └── GenderService.php              # Business logic layer
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
- **Genders are accessible to ALL authenticated users**
- No access filtering required
- Genders are reference data used by products

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

**Genders are public to all authenticated users** - no access restrictions apply. This is because genders are reference data that products use, and all users need to see the full list of available genders.

---

## 📝 Code Examples

### Using GenderService

```php
use App\Services\GenderService;

class GenderController extends Controller
{
    public function __construct(
        private readonly GenderService $genderService
    ) {}
    
    public function index(Request $request)
    {
        $genders = $this->genderService->getPaginatedGenders(
            search: $request->search,
            sortBy: $request->sort_by,
            perPage: 15
        );
        
        return new GenderCollection($genders);
    }
}
```

### Using GenderQuery

```php
use App\Queries\GenderQuery;

$query = new GenderQuery();

$genders = $query
    ->reset()
    ->search('both')
    ->sort('gender_name', 'asc')
    ->paginate(15);
```

---

## 🚀 API Endpoints

### Genders
- `GET /api/v1/genders` - List genders
- `POST /api/v1/genders` - Create gender
- `GET /api/v1/genders/{slug}` - Get gender
- `PUT/PATCH /api/v1/genders/{slug}` - Update gender
- `DELETE /api/v1/genders/{slug}` - Delete gender (soft delete)
- `POST /api/v1/genders/{id}/restore` - Restore deleted gender
- `DELETE /api/v1/genders/{id}/force` - Permanently delete gender
- `GET /api/v1/genders/statistics` - Get gender statistics

### Query Parameters

#### List Genders (`GET /api/v1/genders`)
- `search` - Search by gender name or slug
- `sort_by` - Column to sort by (default: `created_at`)
- `sort_direction` - Sort direction: `asc` or `desc` (default: `asc`)
- `per_page` - Items per page (default: 15)

---

## 📊 Gender Model

### Fields
- `id` - ULID primary key
- `gender_id` - Integer ID from external system (unique)
- `gender_name` - Gender name
- `slug` - URL-friendly identifier (auto-generated)
- `created_at` - Creation timestamp
- `updated_at` - Update timestamp
- `deleted_at` - Soft delete timestamp

### Relationships
- `products()` - HasMany Product

---

## 🛡️ Authorization

### Gender Policy

**All methods return `true`** - genders are accessible to all authenticated users:
- `viewAny()` - All authenticated users can view list
- `view()` - All authenticated users can view gender
- `create()` - All authenticated users can create genders
- `update()` - All authenticated users can update genders
- `delete()` - All authenticated users can delete genders
- `restore()` - All authenticated users can restore genders
- `forceDelete()` - All authenticated users can permanently delete genders

---

## 🔄 Synchronization

Genders can be synchronized from external database during product synchronization:

```bash
php artisan products:sync
```

This command:
1. Syncs genders from external database
2. Preserves old `gender_id` for mapping
3. Creates genders if they don't exist
4. Updates gender names if they changed

---

## 📋 Validation Rules

### Store Gender
- `gender_name` - Required, string, max 255
- `gender_id` - Optional, integer, unique

### Update Gender
- `gender_name` - Optional, string, max 255
- `gender_id` - Optional, integer, unique (excluding current gender)

---

## 🧪 Testing

Genders have comprehensive test coverage:
- Feature tests for API endpoints
- Policy tests for authorization
- Service tests for business logic
- Query tests for filtering

---

## 📝 Notes

1. **Public Access**: Genders are accessible to all authenticated users
2. **Slug Generation**: Genders use slug for URL (auto-generated from gender_name)
3. **Soft Deletes**: Genders support soft deletes with restore capability
4. **External Sync**: Genders can be synced from external database maintaining old IDs
5. **Reference Data**: Genders are used as reference data by products

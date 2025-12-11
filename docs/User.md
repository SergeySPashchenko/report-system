# Complete Laravel API Architecture

## 📁 Project Structure

```
app/
├── Events/
│   ├── UserRegistered.php          # Fired when user registers
│   ├── UserLoggedIn.php            # Fired when user logs in
│   ├── UserLoggedOut.php           # Fired when user logs out
│   ├── UserTokenRefreshed.php      # Fired when token is refreshed
│   ├── UserCreated.php             # Fired when user is created
│   ├── UserUpdated.php             # Fired when user is updated
│   ├── UserDeleted.php             # Fired when user is deleted
│   └── UserRestored.php            # Fired when user is restored
│
├── Listeners/
│   ├── SendWelcomeEmail.php        # Sends welcome email on registration
│   ├── NotifyAdminOfNewUser.php    # Notifies admin of new registrations
│   ├── LogUserLogin.php            # Logs user login activity
│   ├── LogUserLogout.php           # Logs user logout activity
│   └── LogTokenRefresh.php         # Logs token refresh activity
│
├── Http/
│   ├── Controllers/Api/
│   │   ├── AuthController.php      # Authentication endpoints
│   │   └── UserController.php      # User CRUD endpoints
│   │
│   ├── Middleware/
│   │   └── EnsureUserIsActive.php  # Checks if user is verified & active
│   │
│   ├── Requests/
│   │   ├── LoginRequest.php        # Login validation
│   │   ├── RegisterRequest.php     # Registration validation
│   │   ├── StoreUserRequest.php    # Create user validation
│   │   └── UpdateUserRequest.php   # Update user validation
│   │
│   └── Resources/
│       ├── UserResource.php        # Single user transformation
│       └── UserCollection.php      # User collection transformation
│
├── Models/
│   └── User.php                    # User model with traits
│
├── Observers/
│   └── UserObserver.php            # Observes all User model events
│
├── Policies/
│   └── UserPolicy.php              # User authorization rules
│
├── Providers/
│   ├── AppServiceProvider.php      # Registers policies & observers
│   └── EventServiceProvider.php    # Maps events to listeners
│
├── Queries/
│   └── UserQuery.php               # Reusable query builder for users
│
└── Services/
    └── UserService.php             # Business logic layer
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

### 4. **Event/Listener Layer** (Side Effects)
- Decouples code
- Handles notifications
- Logs activity
- Triggers async jobs

### 5. **Observer Layer** (Model Hooks)
- Monitors model lifecycle
- Triggers events
- Manages related data
- Revokes tokens on delete

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
Observer (Model Events)
    ↓
Events → Listeners (Side Effects)
    ↓
Response (Resource Transformation)
```

---

## 📝 Code Examples

### Using UserService

```php
use App\Services\UserService;

class UserController extends Controller
{
    public function __construct(
        private readonly UserService $userService
    ) {}
    
    public function index(Request $request)
    {
        $users = $this->userService->getPaginatedUsers(
            search: $request->search,
            sortBy: $request->sort_by,
            perPage: 15
        );
        
        return new UserCollection($users);
    }
}
```

### Using UserQuery

```php
use App\Queries\UserQuery;

$query = new UserQuery();

// Get active users from last 7 days
$users = $query
    ->recentUsers(7)
    ->verified()
    ->sort('created_at', 'desc')
    ->limit(10)
    ->get();

// Search and paginate
$users = $query
    ->search('john')
    ->sort('name')
    ->paginate(20);
```

### Events in Controller

```php
use App\Events\UserRegistered;

$user = User::create($data);
$token = $user->createToken('auth_token')->plainTextToken;

event(new UserRegistered($user, $token));
// Triggers: SendWelcomeEmail, NotifyAdminOfNewUser
```

### Observer Auto-triggers

```php
// Creating user automatically triggers:
$user = User::create($data);
// → UserObserver::creating()
// → UserObserver::created()
// → UserCreated event
// → Logs to storage/logs/laravel.log

// Deleting user automatically:
$user->delete();
// → UserObserver::deleting()
// → Revokes all tokens
// → UserObserver::deleted()
// → UserDeleted event
```

---

## 🛡️ Middleware Usage

### EnsureUserIsActive

Prevents unverified or deactivated users from accessing API:

```php
Route::middleware(['auth:sanctum', EnsureUserIsActive::class])
    ->group(function () {
        // Only verified & active users
    });
```

**Error Responses:**

```json
// Unverified email
{
  "message": "Your email address is not verified.",
  "error": "email_not_verified"
}

// Deactivated account
{
  "message": "Your account has been deactivated.",
  "error": "account_deactivated"
}
```

---

## 🎯 Service Methods

### UserService API

```php
// Retrieval
$userService->getPaginatedUsers(?string $search, ?string $sortBy, ...);
$userService->findByUsername(string $username): ?User;
$userService->findByEmail(string $email): ?User;
$userService->getActiveUsersCount(): int;
$userService->getRecentUsers(int $limit = 10): array;
$userService->getStatistics(): array;

// Mutations
$userService->create(array $data): User;
$userService->update(User $user, array $data): User;
$userService->delete(User $user): bool;
$userService->restore(string $id): User;
$userService->forceDelete(string $id): bool;

// Status
$userService->isActive(User $user): bool;
$userService->activate(User $user): User;
$userService->deactivate(User $user): User; // Also revokes tokens
```

---

## 📊 User Statistics Endpoint

Add to UserController:

```php
public function statistics(): JsonResponse
{
    $this->authorize('viewAny', User::class);
    
    $stats = $this->userService->getStatistics();
    
    return response()->json($stats);
}
```

Add route:

```php
Route::get('users/statistics', [UserController::class, 'statistics'])
    ->name('users.statistics');
```

**Response:**

```json
{
  "total": 150,
  "active": 120,
  "inactive": 25,
  "deleted": 5,
  "registered_today": 3,
  "registered_this_week": 12,
  "registered_this_month": 45
}
```

---

## 🔔 Event System

### Available Events

| Event | Triggered When | Listeners |
|-------|---------------|-----------|
| `UserRegistered` | New user registers | SendWelcomeEmail, NotifyAdminOfNewUser |
| `UserLoggedIn` | User logs in | LogUserLogin |
| `UserLoggedOut` | User logs out | LogUserLogout |
| `UserTokenRefreshed` | Token is refreshed | LogTokenRefresh |
| `UserCreated` | User model created | (via Observer) |
| `UserUpdated` | User model updated | (via Observer) |
| `UserDeleted` | User model deleted | (via Observer) |
| `UserRestored` | User model restored | (via Observer) |

### Adding Custom Listener

1. Create listener:
```bash
php artisan make:listener SendSlackNotification
```

2. Register in EventServiceProvider:
```php
protected $listen = [
    UserRegistered::class => [
        SendWelcomeEmail::class,
        NotifyAdminOfNewUser::class,
        SendSlackNotification::class, // New
    ],
];
```

3. Implement:
```php
final class SendSlackNotification
{
    public function handle(UserRegistered $event): void
    {
        // Send Slack notification
        Http::post('slack-webhook-url', [
            'text' => "New user: {$event->user->email}"
        ]);
    }
}
```

---

## 🧪 Testing

### Service Layer Tests

```php
use App\Services\UserService;

test('service creates user correctly', function () {
    $service = app(UserService::class);
    
    $user = $service->create([
        'name' => 'Test User',
        'email' => 'test@example.com',
        'password' => 'Password123!',
    ]);
    
    expect($user->username)->toBe('test-user');
    expect($user->email)->toBe('test@example.com');
});
```

### Event Listeners Tests

```php
use App\Events\UserRegistered;
use App\Listeners\SendWelcomeEmail;
use Illuminate\Support\Facades\Event;

test('welcome email is sent on registration', function () {
    Event::fake();
    
    $user = User::factory()->create();
    event(new UserRegistered($user, 'fake-token'));
    
    Event::assertDispatched(UserRegistered::class);
    Event::assertListening(
        UserRegistered::class,
        SendWelcomeEmail::class
    );
});
```

### Observer Tests

```php
test('user tokens are revoked on delete', function () {
    $user = User::factory()->create();
    $user->createToken('test-token');
    
    expect($user->tokens)->toHaveCount(1);
    
    $user->delete();
    
    expect($user->tokens()->count())->toBe(0);
});
```

---

## 🚀 Setup Commands

```bash
# 1. Create all files
php artisan make:service UserService
php artisan make:class Queries/UserQuery
php artisan make:middleware EnsureUserIsActive
php artisan make:observer UserObserver --model=User

# 2. Create events
php artisan make:event UserRegistered
php artisan make:event UserLoggedIn
php artisan make:event UserLoggedOut
php artisan make:event UserTokenRefreshed

# 3. Create listeners
php artisan make:listener SendWelcomeEmail --event=UserRegistered
php artisan make:listener LogUserLogin --event=UserLoggedIn

# 4. Register providers
# Edit: app/Providers/AppServiceProvider.php
# Edit: app/Providers/EventServiceProvider.php

# 5. Clear caches
php artisan config:clear
php artisan event:clear
php artisan optimize

# 6. Run tests
php artisan test
```

---

## 📚 Best Practices

### 1. **Keep Controllers Thin**
✅ Do:
```php
public function store(StoreUserRequest $request): UserResource
{
    $user = $this->userService->create($request->validated());
    return new UserResource($user);
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
class UserService
{
    public function deactivate(User $user): User
    {
        $user->email_verified_at = null;
        $user->save();
        $user->tokens()->delete(); // Related logic together
        return $user;
    }
}
```

### 3. **Use Query Objects for Complex Queries**
✅ Do:
```php
$users = $this->userQuery
    ->activeUsers()
    ->search($search)
    ->recentUsers(30)
    ->paginate(20);
```

❌ Don't:
```php
$users = User::whereNotNull('email_verified_at')
    ->whereNull('deleted_at')
    ->where(function($q) use ($search) {
        $q->where('name', 'like', "%{$search}%")
        ->orWhere('email', 'like', "%{$search}%");
    })
    ->where('created_at', '>=', now()->subDays(30))
    ->paginate(20);
```

### 4. **Use Events for Side Effects**
✅ Do:
```php
event(new UserRegistered($user, $token));
// Listeners handle: emails, notifications, logging
```

❌ Don't:
```php
Mail::send(...);
Slack::notify(...);
Log::info(...);
// All in controller
```

---

## 🔍 Debugging

### View Registered Events

```bash
php artisan event:list
```

### View Registered Routes

```bash
php artisan route:list --path=api/v1
```

### Test Event Firing

```bash
php artisan tinker
```

```php
$user = User::first();
event(new App\Events\UserLoggedIn($user, 'token', '127.0.0.1', 'Chrome'));
// Check storage/logs/laravel.log
```

---

## 🎁 Bonus: Queue Listeners

Make listeners async:

```php
final class SendWelcomeEmail implements ShouldQueue
{
    use Queueable;
    
    public function handle(UserRegistered $event): void
    {
        // Heavy work in background
        Mail::to($event->user)->send(new WelcomeEmail);
    }
}
```

Run queue worker:

```bash
php artisan queue:work
```
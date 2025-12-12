# Expense API Documentation

## 📋 Огляд

API для управління Expenses (витратами). Expenses представляють витрати, пов'язані з продуктами, з інформацією про дату, суму та тип витрати.

## 🏗️ Структура Проекту

### Модель
- **Файл**: `app/Models/Expense.php`
- **Таблиця**: `expenses`
- **Primary Key**: `id` (ULID)
- **Relationships**:
  - `belongsTo(Product)` - зв'язок з продуктом через `ProductID`
  - `belongsTo(Expensetype)` - зв'язок з типом витрати через `ExpenseID`

### Міграція
- **Файл**: `database/migrations/2025_12_12_053235_create_expenses_table.php`
- **Ключові поля**:
  - `external_id` (string, nullable) - ID з зовнішньої БД
  - `ProductID` (bigInteger, nullable) - Foreign key до products
  - `ExpenseID` (bigInteger, nullable) - Foreign key до expensetypes
  - `ExpenseDate` (date) - Дата витрати
  - `Expense` (decimal:2) - Сума витрати
  - `softDeletes()` - М'яке видалення

## 🔐 Система Доступу

### Expense Policy
- **Файл**: `app/Policies/ExpensePolicy.php`
- **Правила доступу**:
  - Користувач компанії має доступ до всіх expenses
  - Користувач з доступами по продуктам має доступ до expenses цих продуктів
  - Користувач з доступами по брендам має доступ до expenses продуктів цих брендів
  - Доступ перевіряється через `HasAccessCheck` trait

## 📡 API Endpoints

### Основні Routes

#### Expenses CRUD
- `GET /api/v1/expenses` - Список expenses (фільтрований за доступом)
- `POST /api/v1/expenses` - Створення expense
- `GET /api/v1/expenses/{id}` - Отримання expense
- `PUT/PATCH /api/v1/expenses/{id}` - Оновлення expense
- `DELETE /api/v1/expenses/{id}` - Видалення expense
- `POST /api/v1/expenses/{id}/restore` - Відновлення expense
- `DELETE /api/v1/expenses/{id}/force` - Остаточне видалення
- `GET /api/v1/expenses/statistics` - Статистика expenses (фільтрована за доступом)

#### Nested Routes під Products
- `GET /api/v1/products/{product}/expenses` - Список expenses для продукту
- `POST /api/v1/products/{product}/expenses` - Створення expense для продукту (ProductID автоматично встановлюється)
- `GET /api/v1/products/{product}/expenses/{expense}` - Отримання expense для продукту
- `PUT/PATCH /api/v1/products/{product}/expenses/{expense}` - Оновлення expense для продукту
- `DELETE /api/v1/products/{product}/expenses/{expense}` - Видалення expense для продукту

#### Nested Routes під Brands
- `GET /api/v1/brands/{brand}/expenses` - Список expenses для бренду

#### Nested Routes під Categories
- `GET /api/v1/categories/{category}/expenses` - Список expenses для категорії

#### Nested Routes під Genders
- `GET /api/v1/genders/{gender}/expenses` - Список expenses для гендеру

#### Nested Routes під Expensetypes
- `GET /api/v1/expensetypes/{expensetype}/expenses` - Список expenses для типу витрати
- `POST /api/v1/expensetypes/{expensetype}/expenses` - Створення expense для типу витрати (ExpenseID автоматично встановлюється)
- `GET /api/v1/expensetypes/{expensetype}/expenses/{expense}` - Отримання expense для типу витрати
- `PUT/PATCH /api/v1/expensetypes/{expensetype}/expenses/{expense}` - Оновлення expense для типу витрати
- `DELETE /api/v1/expensetypes/{expensetype}/expenses/{expense}` - Видалення expense для типу витрати

### Query Parameters

#### Фільтрація
- `start_date` - Фільтр по початковій даті (формат: YYYY-MM-DD)
- `end_date` - Фільтр по кінцевій даті (формат: YYYY-MM-DD)
- `product_id` - Фільтр по ProductID
- `expensetype_id` - Фільтр по ExpenseID (тип витрати)

#### Сортування
- `sort_by` - Колонка для сортування (за замовчуванням: `ExpenseDate`)
- `sort_direction` - Напрямок сортування (`asc` або `desc`, за замовчуванням: `asc`)

#### Пагінація
- `per_page` - Кількість елементів на сторінку (за замовчуванням: 15)

## 🔄 Синхронізація

### Команда Синхронізації
```bash
php artisan expenses:sync {date}
```

Приклад:
```bash
php artisan expenses:sync 2022-07-02
```

### ExpenseSyncService
- **Файл**: `app/Services/ExpenseSyncService.php`
- **Метод**: `syncForDate(string $date)`
- **Функціонал**:
  - Отримує дані з зовнішньої БД (`mysql_external`) для конкретної дати
  - Створює expensetypes, якщо вони не існують
  - Створює categories та genders, якщо вони не існують
  - Створює продукти, якщо вони не існують
  - Створює або оновлює expenses
  - Повертає статистику синхронізації

### SQL Запит для Синхронізації
```sql
SELECT 
    e.id,
    e.ProductID,
    e.ExpenseID,
    e.ExpenseDate,
    e.Expense,
    et.Name,
    p.Product,
    p.newSystem,
    p.Visible,
    mc.category_name,
    mkt.category_name,
    g.gender_name,
    p.flyer,
    p.main_category_id,
    p.marketing_category_id,
    p.gender_id
FROM expenses e
LEFT JOIN product p ON p.ProductID = e.ProductID
LEFT JOIN category mc ON p.main_category_id = mc.category_id
LEFT JOIN category mkt ON p.marketing_category_id = mkt.category_id
LEFT JOIN gender g ON p.gender_id = g.gender_id
LEFT JOIN expensetype et ON et.ExpenseID = e.ExpenseID
WHERE e.ExpenseDate = '2022-07-02';
```

## 📝 Приклади Використання

### Створення Expense
```bash
POST /api/v1/expenses
{
    "ProductID": 12345,
    "ExpenseID": 1,
    "ExpenseDate": "2022-07-02",
    "Expense": 100.50
}
```

### Створення Expense через Nested Route (Product)
```bash
POST /api/v1/products/{product-slug}/expenses
{
    "ExpenseID": 1,
    "ExpenseDate": "2022-07-02",
    "Expense": 100.50
    // ProductID автоматично встановлюється з URL
}
```

### Створення Expense через Nested Route (Expensetype)
```bash
POST /api/v1/expensetypes/{expensetype-slug}/expenses
{
    "ProductID": 12345,
    "ExpenseDate": "2022-07-02",
    "Expense": 100.50
    // ExpenseID автоматично встановлюється з URL
}
```

### Фільтрація по Діапазону Дат
```bash
GET /api/v1/expenses?start_date=2022-07-01&end_date=2022-07-31&per_page=20
```

### Фільтрація по Продукту
```bash
GET /api/v1/expenses?product_id=12345
```

### Фільтрація по Типу Витрати
```bash
GET /api/v1/expenses?expensetype_id=1
```

### Статистика
```bash
GET /api/v1/expenses/statistics
```

Відповідь:
```json
{
    "total": 1000,
    "deleted": 50,
    "total_amount": 50000.00,
    "average_amount": 50.00,
    "created_today": 10,
    "created_this_week": 50,
    "created_this_month": 200
}
```

## 🧪 Тестування

### Feature Tests
- **Файл**: `tests/Feature/Api/ExpenseControllerTest.php`
- **Покриття**:
  - CRUD операції
  - Nested routes під products, brands, categories, genders, expensetypes
  - Фільтрація та сортування
  - Статистика
  - Restore та forceDelete
  - Валідація та авторизація

### Unit Tests
- **ExpenseService**: `tests/Unit/Services/ExpenseServiceTest.php`
- **ExpenseQuery**: `tests/Unit/Queries/ExpenseQueryTest.php`
- **ExpenseSyncService**: `tests/Unit/Services/ExpenseSyncServiceTest.php`
- **SyncExpensesCommand**: `tests/Feature/Commands/SyncExpensesCommandTest.php`

## 🔗 Зв'язки з Іншими Моделями

### Product
- Expense належить Product через `ProductID`
- Product має багато Expenses через `expenses()` relationship
- При видаленні Product, ProductID в Expense встановлюється в `null` (onDelete('set null'))

### Expensetype
- Expense належить Expensetype через `ExpenseID`
- Expensetype має багато Expenses через `expenses()` relationship
- При видаленні Expensetype, ExpenseID в Expense встановлюється в `null` (onDelete('set null'))

## 📊 Статистика

Статистика включає:
- Загальна кількість expenses (фільтрована за доступом)
- Кількість видалених (soft deleted)
- Загальна сума витрат
- Середня сума витрати
- Створені сьогодні/цього тижня/цього місяця

## ⚠️ Важливі Примітки

1. **ProductID** може бути `null` - не обов'язкове поле
2. **ExpenseID** може бути `null` - не обов'язкове поле
3. **ProductID** автоматично встановлюється при створенні через nested route під products
4. **ExpenseID** автоматично встановлюється при створенні через nested route під expensetypes
5. **Soft Delete** - expenses використовують м'яке видалення
6. **ExpenseDate** має формат `date` (YYYY-MM-DD)
7. **Expense** має формат `decimal:2` (два знаки після коми)

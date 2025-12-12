# Expensetype API Documentation

## 📋 Огляд

API для управління Expensetypes (типами витрат). Expensetypes представляють категорії або типи витрат, які можуть бути пов'язані з expenses.

## 🏗️ Структура Проекту

### Модель
- **Файл**: `app/Models/Expensetype.php`
- **Таблиця**: `expensetypes`
- **Primary Key**: `id` (ULID)
- **Route Key**: `slug`
- **Relationships**:
  - `hasMany(Expense)` - зв'язок з expenses через `ExpenseID`

### Міграція
- **Файл**: `database/migrations/2025_12_12_053234_create_expensetypes_table.php`
- **Ключові поля**:
  - `ExpenseTypeID` (bigInteger, nullable, unique) - ID з зовнішньої БД
  - `Name` (string) - Назва типу витрати
  - `slug` (string) - URL-friendly ідентифікатор (автоматично генерується)
  - `softDeletes()` - М'яке видалення

## 🔐 Система Доступу

### Expensetype Policy
- **Файл**: `app/Policies/ExpensetypePolicy.php`
- **Правила доступу**:
  - Всі авторизовані користувачі мають доступ до всіх expensetypes
  - Expensetypes є довідковою інформацією і доступні всім

## 📡 API Endpoints

### Основні Routes

#### Expensetypes CRUD
- `GET /api/v1/expensetypes` - Список expensetypes (доступно всім)
- `POST /api/v1/expensetypes` - Створення expensetype
- `GET /api/v1/expensetypes/{slug}` - Отримання expensetype
- `PUT/PATCH /api/v1/expensetypes/{slug}` - Оновлення expensetype
- `DELETE /api/v1/expensetypes/{slug}` - Видалення expensetype
- `POST /api/v1/expensetypes/{id}/restore` - Відновлення expensetype
- `DELETE /api/v1/expensetypes/{id}/force` - Остаточне видалення
- `GET /api/v1/expensetypes/statistics` - Статистика expensetypes

#### Nested Routes під Expensetypes
- `GET /api/v1/expensetypes/{expensetype}/expenses` - Список expenses для типу витрати
- `POST /api/v1/expensetypes/{expensetype}/expenses` - Створення expense для типу витрати (ExpenseID автоматично встановлюється)
- `GET /api/v1/expensetypes/{expensetype}/expenses/{expense}` - Отримання expense для типу витрати
- `PUT/PATCH /api/v1/expensetypes/{expensetype}/expenses/{expense}` - Оновлення expense для типу витрати
- `DELETE /api/v1/expensetypes/{expensetype}/expenses/{expense}` - Видалення expense для типу витрати

### Query Parameters

#### Фільтрація
- `search` - Пошук по Name або slug

#### Сортування
- `sort_by` - Колонка для сортування (за замовчуванням: `created_at`)
- `sort_direction` - Напрямок сортування (`asc` або `desc`, за замовчуванням: `asc`)

#### Пагінація
- `per_page` - Кількість елементів на сторінку (за замовчуванням: 15)

## 🔄 Синхронізація

### Команда Синхронізації
Expensetypes синхронізуються автоматично при синхронізації expenses:
```bash
php artisan expenses:sync {date}
```

### ExpenseSyncService
- **Файл**: `app/Services/ExpenseSyncService.php`
- **Функціонал**:
  - Створює expensetypes, якщо вони не існують
  - Використовує `ExpenseTypeID` з зовнішньої БД
  - Створює запис з `Name` з зовнішньої БД

## 📝 Приклади Використання

### Створення Expensetype
```bash
POST /api/v1/expensetypes
{
    "Name": "Marketing",
    "ExpenseTypeID": 1
}
```

### Пошук
```bash
GET /api/v1/expensetypes?search=Marketing
```

### Отримання Expensetype
```bash
GET /api/v1/expensetypes/{slug}
```

### Оновлення Expensetype
```bash
PUT /api/v1/expensetypes/{slug}
{
    "Name": "Marketing Updated"
}
```

### Видалення Expensetype
```bash
DELETE /api/v1/expensetypes/{slug}
```

### Відновлення Expensetype
```bash
POST /api/v1/expensetypes/{id}/restore
```

### Статистика
```bash
GET /api/v1/expensetypes/statistics
```

Відповідь:
```json
{
    "total": 50,
    "deleted": 5,
    "created_today": 2,
    "created_this_week": 5,
    "created_this_month": 15
}
```

### Отримання Expenses для Expensetype
```bash
GET /api/v1/expensetypes/{expensetype-slug}/expenses?start_date=2022-07-01&end_date=2022-07-31
```

### Створення Expense для Expensetype
```bash
POST /api/v1/expensetypes/{expensetype-slug}/expenses
{
    "ProductID": 12345,
    "ExpenseDate": "2022-07-02",
    "Expense": 100.50
    // ExpenseID автоматично встановлюється з URL
}
```

## 🧪 Тестування

### Feature Tests
- **Файл**: `tests/Feature/Api/ExpensetypeControllerTest.php`
- **Покриття**:
  - CRUD операції
  - Nested routes під expensetypes
  - Фільтрація та пошук
  - Статистика
  - Restore та forceDelete
  - Валідація та авторизація

### Unit Tests
- **ExpensetypeService**: `tests/Unit/Services/ExpensetypeServiceTest.php`
- **ExpensetypeQuery**: `tests/Unit/Queries/ExpensetypeQueryTest.php`

## 🔗 Зв'язки з Іншими Моделями

### Expense
- Expensetype має багато Expenses через `expenses()` relationship
- Expense належить Expensetype через `ExpenseID`
- При видаленні Expensetype, ExpenseID в Expense встановлюється в `null` (onDelete('set null'))

## 📊 Статистика

Статистика включає:
- Загальна кількість expensetypes
- Кількість видалених (soft deleted)
- Створені сьогодні/цього тижня/цього місяця

## ⚠️ Важливі Примітки

1. **ExpenseTypeID** може бути `null` - не обов'язкове поле
2. **Slug** генерується автоматично з `Name` і не змінюється при оновленні
3. **Soft Delete** - expensetypes використовують м'яке видалення
4. **Expensetypes доступні всім** - це довідкова інформація
5. При синхронізації expenses автоматично створюються expensetypes, якщо вони не існують

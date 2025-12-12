# Product Item API Documentation

## 📋 Огляд

API для управління Product Items (елементами продуктів). Product Items представляють окремі варіанти або компоненти продуктів з додатковою інформацією про SKU, кількість, статуси тощо.

## 🏗️ Структура Проекту

### Модель
- **Файл**: `app/Models/ProductItem.php`
- **Таблиця**: `product_items`
- **Primary Key**: `id` (ULID)
- **Route Key**: `slug`
- **Relationships**:
  - `belongsTo(Product)` - зв'язок з продуктом через `ProductID`

### Міграція
- **Файл**: `database/migrations/2025_12_12_085825_create_product_items_table.php`
- **Ключові поля**:
  - `ItemID` (bigInteger, nullable, unique) - ID з зовнішньої БД
  - `ProductID` (bigInteger, nullable) - Foreign key до products
  - `ProductName` (string) - Назва елемента продукту
  - `slug` (string) - URL-friendly ідентифікатор
  - `SKU` (string) - Артикул
  - `Quantity` (integer) - Кількість
  - `upSell` (boolean) - Чи є up-sell
  - `extraProduct` (boolean) - Чи є додатковим продуктом
  - `offerProducts` (string, nullable) - Пропозиції продуктів
  - `active` (boolean) - Активний статус
  - `deleted` (boolean) - Позначка видалення
  - `softDeletes()` - М'яке видалення

## 🔐 Система Доступу

### ProductItem Policy
- **Файл**: `app/Policies/ProductItemPolicy.php`
- **Правила доступу**:
  - Користувач компанії має доступ до всіх product items
  - Користувач з доступами по продуктам має доступ до product items цих продуктів
  - Користувач з доступами по брендам має доступ до product items продуктів цих брендів
  - Доступ перевіряється через `HasAccessCheck` trait

## 📡 API Endpoints

### Основні Routes

#### Product Items CRUD
- `GET /api/v1/product-items` - Список product items (фільтрований за доступом)
- `POST /api/v1/product-items` - Створення product item
- `GET /api/v1/product-items/{slug}` - Отримання product item
- `PUT/PATCH /api/v1/product-items/{slug}` - Оновлення product item
- `DELETE /api/v1/product-items/{slug}` - Видалення product item
- `POST /api/v1/product-items/{id}/restore` - Відновлення product item
- `DELETE /api/v1/product-items/{id}/force` - Остаточне видалення
- `GET /api/v1/product-items/statistics` - Статистика product items (фільтрована за доступом)

#### Nested Routes під Products
- `GET /api/v1/products/{product}/product-items` - Список product items для продукту
- `POST /api/v1/products/{product}/product-items` - Створення product item для продукту (ProductID автоматично встановлюється)
- `GET /api/v1/products/{product}/product-items/{product_item}` - Отримання product item для продукту
- `PUT/PATCH /api/v1/products/{product}/product-items/{product_item}` - Оновлення product item для продукту
- `DELETE /api/v1/products/{product}/product-items/{product_item}` - Видалення product item для продукту

#### Nested Routes під Brands
- `GET /api/v1/brands/{brand}/product-items` - Список product items для бренду

#### Nested Routes під Categories
- `GET /api/v1/categories/{category}/product-items` - Список product items для категорії

#### Nested Routes під Genders
- `GET /api/v1/genders/{gender}/product-items` - Список product items для гендеру

### Query Parameters

#### Фільтрація
- `search` - Пошук по ProductName, SKU або slug
- `active` - Фільтр по активному статусу (true/false)
- `deleted` - Фільтр по статусу видалення (true/false)
- `up_sell` - Фільтр по upSell статусу (true/false)
- `extra_product` - Фільтр по extraProduct статусу (true/false)

#### Сортування
- `sort_by` - Колонка для сортування (за замовчуванням: `created_at`)
- `sort_direction` - Напрямок сортування (`asc` або `desc`, за замовчуванням: `asc`)

#### Пагінація
- `per_page` - Кількість елементів на сторінку (за замовчуванням: 15)

## 🔄 Синхронізація

### Команда Синхронізації
```bash
php artisan product-items:sync
```

### ProductItemSyncService
- **Файл**: `app/Services/ProductItemSyncService.php`
- **Метод**: `sync()`
- **Функціонал**:
  - Отримує дані з зовнішньої БД (`mysql_external`)
  - Створює продукти, якщо вони не існують
  - Створює або оновлює product items
  - Обробляє soft delete на основі поля `deleted`
  - Повертає статистику синхронізації

### SQL Запит для Синхронізації
```sql
SELECT 
    ItemID,
    ProductID,
    ProductName,
    SKU,
    Quantity,
    upSell,
    extraProduct,
    offerProducts,
    deleted,
    active
FROM ProductItem;
```

## 📝 Приклади Використання

### Створення Product Item
```bash
POST /api/v1/product-items
{
    "ItemID": 1,
    "ProductID": 12345,
    "ProductName": "Saffron Premium (1 Bottle)",
    "SKU": "SFPRM",
    "Quantity": 1,
    "upSell": false,
    "extraProduct": false,
    "active": true
}
```

### Створення Product Item через Nested Route
```bash
POST /api/v1/products/{product-slug}/product-items
{
    "ProductName": "Premium Item",
    "SKU": "PREM-SKU",
    "Quantity": 10
    // ProductID автоматично встановлюється з URL
}
```

### Фільтрація по Active
```bash
GET /api/v1/product-items?active=true&per_page=20
```

### Пошук
```bash
GET /api/v1/product-items?search=Premium
```

### Статистика
```bash
GET /api/v1/product-items/statistics
```

Відповідь:
```json
{
    "total": 100,
    "deleted": 5,
    "active": 85,
    "inactive": 15,
    "upSell": 20,
    "extraProduct": 10,
    "created_today": 5,
    "created_this_week": 15,
    "created_this_month": 50
}
```

## 🧪 Тестування

### Feature Tests
- **Файл**: `tests/Feature/Api/ProductItemControllerTest.php`
- **Покриття**:
  - CRUD операції
  - Nested routes під products
  - Фільтрація та пошук
  - Статистика
  - Restore та forceDelete
  - Валідація та авторизація

### Unit Tests
- **ProductItemService**: `tests/Unit/Services/ProductItemServiceTest.php`
- **ProductItemQuery**: `tests/Unit/Queries/ProductItemQueryTest.php`
- **ProductItemSyncService**: `tests/Unit/Services/ProductItemSyncServiceTest.php`
- **SyncProductItemsCommand**: `tests/Feature/Commands/SyncProductItemsCommandTest.php`

## 🔗 Зв'язки з Іншими Моделями

### Product
- ProductItem належить Product через `ProductID`
- Product має багато ProductItems через `productItems()` relationship
- При видаленні Product, ProductID в ProductItem встановлюється в `null` (onDelete('set null'))

## 📊 Статистика

Статистика включає:
- Загальна кількість product items (фільтрована за доступом)
- Кількість видалених (soft deleted)
- Кількість активних/неактивних
- Кількість з upSell/extraProduct
- Створені сьогодні/цього тижня/цього місяця

## ⚠️ Важливі Примітки

1. **ItemID** може бути `null` - не обов'язкове поле
2. **ProductID** автоматично встановлюється при створенні через nested route
3. **Slug** генерується автоматично з `ProductName` і не змінюється при оновленні
4. **Soft Delete** - product items використовують м'яке видалення
5. **Deleted Flag** - поле `deleted` використовується для синхронізації з зовнішньою БД
6. **Access Control** - всі операції фільтруються за рівнем доступу користувача

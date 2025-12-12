<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\ProductFactory;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Sluggable\HasSlug;
use Spatie\Sluggable\SlugOptions;

/**
 * @property Brand|null $brand
 * @property Category|null $main_category
 * @property Category|null $marketing_category
 * @property Gender|null $gender
 */
final class Product extends Model
{
    /** @use HasFactory<ProductFactory> */
    use HasFactory;

    use HasSlug;
    use HasUlids;
    use SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'ProductID',
        'Product',
        'slug',
        'newSystem',
        'Visible',
        'flyer',
        'main_category_id',
        'marketing_category_id',
        'gender_id',
        'brand_id',
    ];

    public function getSlugOptions(): SlugOptions
    {
        return SlugOptions::create()
            ->generateSlugsFrom('Product')
            ->saveSlugsTo('slug')
            ->slugsShouldBeNoLongerThan(50)
            ->doNotGenerateSlugsOnUpdate(); // Slug не буде змінюватись при оновленні name
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    /**
     * Get all accesses for this product.
     *
     * @return MorphMany<Access, $this>
     */
    public function accesses(): MorphMany
    {
        return $this->morphMany(Access::class, 'accessible');
    }

    /**
     * Get all users that have access to this product.
     *
     * @return BelongsToMany<User, $this>
     */
    public function users(): BelongsToMany
    {
        // Використовуємо ключ 'product' з морф-мапи (визначено в AppServiceProvider)
        return $this->belongsToMany(
            User::class,
            'accesses',
            'accessible_id',
            'user_id'
        )->where('accesses.accessible_type', 'product')
            ->whereNull('accesses.deleted_at')
            ->withTimestamps();
    }

    /**
     * @return BelongsTo<Category, $this>
     */
    public function main_category(): BelongsTo
    {
        return $this->belongsTo(
            Category::class,
            'main_category_id',
            'id'
        );
    }

    /**
     * @return BelongsTo<Category, $this>
     */
    public function marketing_category(): BelongsTo
    {
        return $this->belongsTo(
            Category::class,
            'marketing_category_id',
            'id'
        );
    }

    /**
     * @return BelongsTo<Gender, $this>
     */
    public function gender(): BelongsTo
    {
        return $this->belongsTo(Gender::class, 'gender_id', 'id');
    }

    /**
     * @return BelongsTo<Brand, $this>
     */
    public function brand(): BelongsTo
    {
        return $this->belongsTo(Brand::class, 'brand_id', 'id');
    }

    /**
     * @return HasMany<Expense, $this>
     */
    public function expenses(): HasMany
    {
        return $this->hasMany(Expense::class, 'ProductID', 'ProductID');
    }
}

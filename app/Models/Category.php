<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\CategoryFactory;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Sluggable\HasSlug;
use Spatie\Sluggable\SlugOptions;

/**
 * @method static CategoryFactory factory()
 */
final class Category extends Model
{
    /** @use HasFactory<CategoryFactory> */
    use HasFactory;

    use HasSlug;
    use HasUlids;
    use SoftDeletes;

    protected $fillable = [
        'category_id',
        'category_name',
        'slug',
    ];

    public function getSlugOptions(): SlugOptions
    {
        return SlugOptions::create()
            ->generateSlugsFrom('category_name')
            ->saveSlugsTo('slug')
            ->slugsShouldBeNoLongerThan(50)
            ->doNotGenerateSlugsOnUpdate(); // Slug не буде змінюватись при оновленні name
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    /**
     * @return HasMany<Product, $this>
     */
    public function main_products(): HasMany
    {
        return $this->hasMany(
            Product::class,
            'main_category_id',
            'id'
        );
    }

    /**
     * @return HasMany<Product, $this>
     */
    public function marketing_products(): HasMany
    {
        return $this->hasMany(
            Product::class,
            'marketing_category_id',
            'id'
        );
    }
}

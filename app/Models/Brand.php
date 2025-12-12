<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\BrandFactory;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Sluggable\HasSlug;
use Spatie\Sluggable\SlugOptions;

final class Brand extends Model
{
    /** @use HasFactory<BrandFactory> */
    use HasFactory;

    use HasSlug;
    use HasUlids;
    use SoftDeletes;

    protected $fillable = [
        'name',
        'slug',
    ];

    public function getSlugOptions(): SlugOptions
    {
        return SlugOptions::create()
            ->generateSlugsFrom('name')
            ->saveSlugsTo('slug')
            ->allowDuplicateSlugs();
    }

    /**
     * Get the route key for the model.
     */
    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    /**
     * Get all accesses for this brand.
     *
     * @return MorphMany<Access, $this>
     */
    public function accesses(): MorphMany
    {
        return $this->morphMany(Access::class, 'accessible');
    }

    /**
     * Get all users that have access to this brand.
     *
     * @return BelongsToMany<User, $this>
     */
    public function users(): BelongsToMany
    {
        // Використовуємо ключ 'brand' з морф-мапи (визначено в AppServiceProvider)
        return $this->belongsToMany(
            User::class,
            'accesses',
            'accessible_id',
            'user_id'
        )->where('accesses.accessible_type', 'brand')
            ->whereNull('accesses.deleted_at')
            ->withTimestamps();
    }

    /**
     * Get all products for this brand.
     * Note: Products are filtered by user access when queried through services/queries.
     *
     * @return HasMany<Product, $this>
     */
    public function products()
    {
        return $this->hasMany(Product::class, 'brand_id', 'id');
    }
}

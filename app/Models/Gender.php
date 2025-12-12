<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\GenderFactory;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Sluggable\HasSlug;
use Spatie\Sluggable\SlugOptions;

/**
 * @method static GenderFactory factory()
 */
final class Gender extends Model
{
    /** @use HasFactory<GenderFactory> */
    use HasFactory;

    use HasSlug;
    use HasUlids;
    use SoftDeletes;

    protected $fillable = [
        'gender_id',
        'gender_name',
        'slug',
    ];

    public function getSlugOptions(): SlugOptions
    {
        return SlugOptions::create()
            ->generateSlugsFrom('gender_name')
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
    public function products(): HasMany
    {
        return $this->hasMany(Product::class, 'gender_id', 'id');
    }
}

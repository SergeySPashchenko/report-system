<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\CompanyFactory;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Sluggable\HasSlug;
use Spatie\Sluggable\SlugOptions;

final class Company extends Model
{
    /** @use HasFactory<CompanyFactory> */
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
            ->slugsShouldBeNoLongerThan(50)
            ->doNotGenerateSlugsOnUpdate(); // Slug не буде змінюватись при оновленні name
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    /**
     * Get all accesses for this company.
     *
     * @return MorphMany<Access, $this>
     */
    public function accesses(): MorphMany
    {
        return $this->morphMany(Access::class, 'accessible');
    }

    /**
     * Get all users that have access to this company.
     *
     * @return BelongsToMany<User, $this>
     */
    public function users(): BelongsToMany
    {
        // Використовуємо ключ 'company' з морф-мапи (визначено в AppServiceProvider)
        return $this->belongsToMany(
            User::class,
            'accesses',
            'accessible_id',
            'user_id'
        )->where('accesses.accessible_type', 'company')
            ->whereNull('accesses.deleted_at')
            ->withTimestamps();
    }
}

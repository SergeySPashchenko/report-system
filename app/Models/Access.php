<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\AccessFactory;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;

final class Access extends Model
{
    /** @use HasFactory<AccessFactory> */
    use HasFactory;

    use HasUlids;
    use SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'user_id',
        'accessible_id',
        'accessible_type',
    ];

    /**
     * Get the user that owns the access.
     *
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the parent accessible model (polymorphic).
     *
     * Supported types: 'company', 'brand', 'product', 'user', 'access'
     * Types are mapped in AppServiceProvider morph map.
     *
     * @return MorphTo<Model, $this>
     */
    public function accessable(): MorphTo
    {
        return $this->morphTo();
    }
}

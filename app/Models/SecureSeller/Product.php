<?php

declare(strict_types=1);

namespace App\Models\SecureSeller;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class Product extends Model
{

    protected $connection = 'mysql_external';

    protected $table = 'product';

    /**
     * Indicates if the model should be timestamped.
     */
    public $timestamps = false;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'ProductID',
        'Brand',
    ];
}

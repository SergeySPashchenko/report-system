<?php

namespace App\Models\SecureSeller;

use Illuminate\Database\Eloquent\Model;
use App\Models\SecureSeller\Product;

class Gender extends Model
{
    protected $connection = 'mysql_external';

    protected $table = 'gender';

    /**
     * Indicates if the model should be timestamped.
     */
    public $timestamps = false;

    protected $fillable = [
        'gender_id',
        'gender_name',
    ];

    public function products()
    {
        return $this->hasMany(Product::class, 'gender_id', 'gender_id');
    }
}

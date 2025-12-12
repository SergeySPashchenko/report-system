<?php

namespace App\Models\SecureSeller;

use Illuminate\Database\Eloquent\Model;
use App\Models\SecureSeller\Product;

class Category extends Model
{
    protected $connection = 'mysql_external';

    protected $table = 'category';

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
        'category_id',
        'category_name',
    ];
    public function main_products()
    {
        return $this->hasMany(
            Product::class,
            'main_category_id',
            'category_id'
        );
    }

    public function marketing_products()
    {
        return $this->hasMany(
            Product::class,
            'marketing_category_id',
            'category_id'
        );
    }
}

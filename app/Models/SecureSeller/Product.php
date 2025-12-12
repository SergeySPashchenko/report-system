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
        'Product',
        'newSystem',
        'Visible',
        'flyer',
        'main_category_id',
        'marketing_category_id',
        'gender_id',
        'brand_id',
        'Brand',
    ];
    public function main_category()
    {
        return $this->belongsTo(Category::class, 'main_category_id', 'category_id');
    }
    public function marketing_category()
    {
        return $this->belongsTo(Category::class, 'marketing_category_id', 'category_id');
    }
    public function gender()
    {
        return $this->belongsTo(Gender::class, 'gender_id', 'gender_id');
    }
}

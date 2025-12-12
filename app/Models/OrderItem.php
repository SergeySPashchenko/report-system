<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\OrderItemFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class OrderItem extends Model
{
    /** @use HasFactory<OrderItemFactory> */
    use HasFactory;
}

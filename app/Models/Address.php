<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\AddressFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class Address extends Model
{
    /** @use HasFactory<AddressFactory> */
    use HasFactory;
}

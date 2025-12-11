<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\PersonalAccessTokenFactory;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Laravel\Sanctum\PersonalAccessToken as SanctumPersonalAccessToken;

final class PersonalAccessToken extends SanctumPersonalAccessToken
{
    /** @use HasFactory<PersonalAccessTokenFactory> */
    use HasFactory;

    use HasUlids;
}

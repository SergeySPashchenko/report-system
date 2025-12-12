<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Events\UserCreated;
use App\Models\Access;
use App\Models\Company;
use Illuminate\Support\Facades\Log;

final class AssignCompanyToUser
{
    /**
     * Handle the event.
     */
    public function handle(UserCreated $event): void
    {
        $user = $event->user;

        // Перевіряємо чи існує компанія "Main"
        $mainCompany = Company::query()
            ->where('name', 'Main')
            ->first();

        // Якщо компанії "Main" немає, створюємо її (перший користувач)
        if ($mainCompany === null) {
            $mainCompany = Company::query()->create([
                'name' => 'Main',
            ]);

            Log::info('Main company created for first user', [
                'company_id' => $mainCompany->id,
                'user_id' => $user->id,
            ]);
        }

        // Створюємо доступ для користувача до компанії
        Access::query()->create([
            'user_id' => $user->id,
            'accessible_id' => $mainCompany->id,
            'accessible_type' => Company::class,
        ]);

        Log::info('Company access assigned to user', [
            'user_id' => $user->id,
            'company_id' => $mainCompany->id,
            'company_name' => $mainCompany->name,
        ]);
    }
}

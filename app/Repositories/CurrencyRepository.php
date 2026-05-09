<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;
use PDO;

final class CurrencyRepository
{
    /** @return array<int, array<string, mixed>> */
    public function allActive(): array
    {
        return Database::pdo()->query('SELECT * FROM currencies WHERE is_active = 1 ORDER BY sort_order, code')->fetchAll(PDO::FETCH_ASSOC);
    }
}

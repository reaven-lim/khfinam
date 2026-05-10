<?php

declare(strict_types=1);

use App\Helpers\Str;
use App\Helpers\WalletTypeUi;

$selectId = $selectId ?? 'wallet_type_icon';
$selected = (string) ($selected ?? 'wallet');
$choices = WalletTypeUi::iconChoices();
$keys = WalletTypeUi::presetIconKeySet();
$extras = strtolower(trim((string) ($extrasIcon ?? '')));
if ($extras !== '' && preg_match('/^[a-z0-9-]{1,64}$/', $extras) === 1 && ! isset($keys[$extras])) {
    array_unshift($choices, ['lucide' => $extras, 'label' => 'Current: ' . $extras, 'emoji' => '✨']);
}

?>
<select id="<?= Str::e($selectId) ?>" name="icon" <?= ! empty($required) ? 'required' : '' ?> class="<?= Str::e((string) ($selectClass ?? 'mt-1 w-full rounded-xl border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-900 px-3 py-2.5 text-sm')) ?>">
    <?php foreach ($choices as $c):
        $l = (string) $c['lucide'];
        $lab = (string) $c['label'];
        $em = (string) $c['emoji'];
        ?>
    <option value="<?= Str::e($l) ?>" <?= $l === $selected ? 'selected' : '' ?>><?= Str::e($em . ' ' . $lab) ?></option>
    <?php endforeach; ?>
</select>

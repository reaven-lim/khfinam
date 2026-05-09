<?php
declare(strict_types=1);

/** @var string $mainClasses Extra classes on <main> (optional) */
$mainClasses = $mainClasses ?? 'flex-1 overflow-y-auto p-4 md:px-5 md:py-5 lg:px-7 xl:px-8 lg:py-6';
?>
    <main id="mainContent" class="<?= htmlspecialchars($mainClasses, ENT_QUOTES, 'UTF-8') ?>">

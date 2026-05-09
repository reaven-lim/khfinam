<?php
declare(strict_types=1);

/** @var string $themeLocalStorageKey */
/** @var string $midToggleFunctionName e.g. toggleDashSidebarMid */
/** @var string $midToggleButtonId */
/** @var string $midToggleIconId */
/** @var string $midToggleStorageKey */
?>
<script>
function openSidebar() {
    document.getElementById('sidebar').classList.remove('-translate-x-full');
    document.getElementById('sideOverlay').classList.remove('hidden');
    document.body.style.overflow = 'hidden';
}
function closeSidebar() {
    document.getElementById('sidebar').classList.add('-translate-x-full');
    document.getElementById('sideOverlay').classList.add('hidden');
    document.body.style.overflow = '';
}
function toggleDark() {
    var html = document.documentElement;
    html.classList.toggle('dark');
    localStorage.setItem(<?= json_encode($themeLocalStorageKey, JSON_HEX_TAG | JSON_HEX_APOS | JSON_UNESCAPED_UNICODE) ?>, html.classList.contains('dark') ? 'dark' : 'light');
}
function <?= htmlspecialchars($midToggleFunctionName, ENT_QUOTES, 'UTF-8') ?>() {
    var sb = document.getElementById('sidebar');
    var iconWrap = document.getElementById(<?= json_encode($midToggleIconId, JSON_HEX_TAG | JSON_HEX_APOS | JSON_UNESCAPED_UNICODE) ?>);
    if (!sb || !iconWrap) return;
    if (!window.matchMedia('(min-width:768px) and (max-width:1279px)').matches) return;
    sb.classList.toggle('sidebar-mid-expanded');
    var exp = sb.classList.contains('sidebar-mid-expanded');
    try {
        localStorage.setItem(<?= json_encode($midToggleStorageKey, JSON_HEX_TAG | JSON_HEX_APOS | JSON_UNESCAPED_UNICODE) ?>, exp ? '1' : '0');
    } catch (e) {}
    iconWrap.setAttribute('data-lucide', exp ? 'chevrons-left' : 'chevrons-right');
    var btn = document.getElementById(<?= json_encode($midToggleButtonId, JSON_HEX_TAG | JSON_HEX_APOS | JSON_UNESCAPED_UNICODE) ?>);
    if (btn) btn.title = exp ? 'Use compact sidebar' : 'Expand sidebar';
    if (typeof lucide !== 'undefined') lucide.createIcons();
    setTimeout(function () { window.dispatchEvent(new Event('resize')); }, 240);
}
(function () {
    var sb = document.getElementById('sidebar');
    var iconWrap = document.getElementById(<?= json_encode($midToggleIconId, JSON_HEX_TAG | JSON_HEX_APOS | JSON_UNESCAPED_UNICODE) ?>);
    if (!sb || !window.matchMedia('(min-width:768px) and (max-width:1279px)').matches) return;
    if ((typeof localStorage !== 'undefined') && localStorage.getItem(<?= json_encode($midToggleStorageKey, JSON_HEX_TAG | JSON_HEX_APOS | JSON_UNESCAPED_UNICODE) ?>) === '1') {
        sb.classList.add('sidebar-mid-expanded');
        if (iconWrap) iconWrap.setAttribute('data-lucide', 'chevrons-left');
        var btn = document.getElementById(<?= json_encode($midToggleButtonId, JSON_HEX_TAG | JSON_HEX_APOS | JSON_UNESCAPED_UNICODE) ?>);
        if (btn) btn.title = 'Use compact sidebar';
    }
})();
lucide.createIcons();
</script>

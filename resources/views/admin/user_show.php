<?php

declare(strict_types=1);

use App\Core\Csrf;
use App\Helpers\Str;
use App\Helpers\Url;
use App\Helpers\View;

$profile = $profile ?? [];
$walletRows = $walletRows ?? [];
$totalBalanceBase = $totalBalanceBase ?? 0.0;
$recurringCount = $recurringCount ?? 0;
$totalsAll = $totalsAll ?? ['income' => 0.0, 'expense' => 0.0, 'savings' => 0.0];
$monthlySeries = $monthlySeries ?? [];
$transferStats = $transferStats ?? ['count' => 0, 'volume_base' => 0.0];
$recentTx = $recentTx ?? [];
$auditRows = $auditRows ?? [];
$savingsRate = $savingsRate ?? 0.0;
$donutWalletLabels = $donutWalletLabels ?? [];
$donutWalletSeries = $donutWalletSeries ?? [];
$donutCatLabels = $donutCatLabels ?? [];
$donutCatSeries = $donutCatSeries ?? [];
$message = $message ?? null;
$error = $error ?? null;
$baseCurrency = $baseCurrency ?? 'MYR';
$adminSelfId = $adminSelfId ?? null;

$uid = (int) ($profile['id'] ?? 0);
$uname = (string) ($profile['username'] ?? '');
$email = (string) ($profile['email'] ?? '');
$full = trim((string) ($profile['full_name'] ?? ''));
$role = (string) ($profile['role'] ?? 'user');
$active = ! empty($profile['is_active']);
$created = (string) ($profile['created_at'] ?? '');
$lastLogin = (string) ($profile['last_login_at'] ?? '');
$prefTheme = (string) ($profile['preference_theme'] ?? 'system');
$prefMute = ! empty($profile['preference_mute_low_balance']);
$includeAnalytics = filter_var($profile['include_in_analytics'] ?? true, FILTER_VALIDATE_BOOLEAN);
$ini = strtoupper(substr($uname, 0, 2));
$isSelf = $adminSelfId !== null && $uid === $adminSelfId;

/** Primary ops / management emphasis */
$manageShell = 'rounded-2xl border-2 border-teal-500/35 dark:border-teal-500/25 bg-gradient-to-br from-white via-white to-teal-50/40 dark:from-[#0d1424] dark:via-[#0c1426] dark:to-teal-950/20 shadow-[0_20px_50px_-28px_rgba(13,148,136,0.18)] dark:shadow-[0_24px_55px_-36px_rgba(0,0,0,0.55)] ring-1 ring-slate-900/[0.04] dark:ring-white/[0.06]';
$manageCardInner = 'rounded-xl border border-slate-200/95 dark:border-slate-700/70 bg-white/95 dark:bg-slate-900/50 p-4 sm:p-5';

/** Supporting cards */
$cardShell = 'rounded-2xl bg-white dark:bg-[#0d1424] border border-slate-300/85 dark:border-slate-700/65 p-5 sm:p-6 shadow-[0_20px_50px_-24px_rgba(15,23,42,0.14)] dark:shadow-[0_24px_56px_-36px_rgba(0,0,0,0.6)] ring-1 ring-slate-900/[0.07] dark:ring-white/[0.06] relative overflow-hidden';
$insightCardShell = 'rounded-xl bg-slate-50/90 dark:bg-slate-900/40 border border-slate-200/90 dark:border-slate-700/60 p-3.5 sm:p-4 shadow-sm ring-1 ring-slate-900/[0.03] dark:ring-white/[0.04]';
$insightChartShell = 'rounded-xl bg-slate-50/95 dark:bg-slate-900/45 border border-slate-200/90 dark:border-slate-700/55 p-3 sm:p-4 shadow-sm ring-1 ring-slate-900/[0.03] dark:ring-white/[0.04]';
$mutedShell = 'rounded-xl border border-dashed border-slate-300/80 dark:border-slate-700/65 bg-slate-50/50 dark:bg-slate-900/25 p-4 sm:p-5';

/** Section chrome */
$secKicker = 'text-[10px] font-extrabold uppercase tracking-[0.2em] text-teal-600 dark:text-teal-400 mb-1';
$secTitle = 'text-base sm:text-lg font-bold text-slate-900 dark:text-white tracking-tight';
$secSub = 'text-xs text-slate-600 dark:text-slate-400 mt-1 max-w-2xl';

$months = array_map(static fn (array $r): string => (string) ($r['ym'] ?? ''), $monthlySeries);
$incM = array_map(static fn (array $r): float => round((float) ($r['inc'] ?? 0), 2), $monthlySeries);
$expM = array_map(static fn (array $r): float => round((float) ($r['exp'] ?? 0), 2), $monthlySeries);
?>

<?php if (! empty($message)): ?>
<div class="mb-4 rounded-xl border border-emerald-300/70 bg-emerald-50/90 dark:bg-emerald-950/40 dark:border-emerald-800/60 px-4 py-3 text-sm font-semibold text-emerald-900 dark:text-emerald-200"><?= Str::e((string) $message) ?></div>
<?php endif; ?>
<?php if (! empty($error)): ?>
<div class="mb-4 rounded-xl border border-rose-300/70 bg-rose-50/90 dark:bg-rose-950/35 dark:border-rose-800/60 px-4 py-3 text-sm font-semibold text-rose-900 dark:text-rose-200"><?= Str::e((string) $error) ?></div>
<?php endif; ?>

<!-- Breadcrumb -->
<div class="mb-5 flex flex-wrap items-center gap-3 text-sm">
    <a href="<?= Str::e(Url::to('/admin/users')) ?>" class="inline-flex items-center gap-1.5 font-bold text-teal-700 dark:text-teal-300 hover:underline">
        <i data-lucide="arrow-left" class="w-4 h-4"></i> User directory
    </a>
    <span class="text-slate-300 dark:text-slate-600">/</span>
    <span class="font-semibold text-slate-800 dark:text-slate-200"><?= Str::e($uname) ?></span>
</div>

<?php if (! $includeAnalytics): ?>
<div class="mb-5 rounded-2xl border border-amber-300/65 dark:border-amber-900/55 bg-gradient-to-br from-amber-50/95 via-white to-orange-50/40 dark:from-amber-950/35 dark:via-[#0d1424] dark:to-orange-950/25 px-4 py-3.5 sm:px-5 ring-1 ring-amber-900/[0.05] dark:ring-amber-500/12">
    <p class="text-sm font-semibold text-amber-950 dark:text-amber-100">Excluded from analytics &amp; global reports</p>
    <p class="text-xs text-amber-900/90 dark:text-amber-200/90 mt-1 leading-relaxed max-w-3xl">This member remains fully active — their ledger below is unchanged. Excluded users remain active, but their financial records will not affect global analytics, reports, or platform dashboards.</p>
</div>
<?php endif; ?>

<!-- 1 · Identity header -->
<section class="mb-5 rounded-2xl border border-slate-200/95 dark:border-slate-700/60 bg-white dark:bg-[#0d1424] px-5 py-5 sm:px-6 sm:py-6 shadow-[0_14px_40px_-28px_rgba(15,23,42,0.12)] dark:shadow-none ring-1 ring-slate-900/[0.05] dark:ring-white/[0.05]" aria-labelledby="user-identity-heading">
    <div class="flex flex-col sm:flex-row sm:items-start gap-5">
        <div class="w-16 h-16 sm:w-[4.25rem] sm:h-[4.25rem] rounded-2xl bg-gradient-to-br from-violet-500 via-indigo-600 to-slate-900 flex items-center justify-center text-white text-xl font-extrabold shrink-0 ring-2 ring-slate-200/80 dark:ring-slate-700/80 shadow-md"><?= Str::e($ini) ?></div>
        <div class="flex-1 min-w-0">
            <h1 id="user-identity-heading" class="text-xl sm:text-2xl font-extrabold tracking-tight text-slate-900 dark:text-white"><?= Str::e($uname) ?></h1>
            <div class="mt-2 flex flex-wrap items-center gap-2">
                <?php if ($role === 'super_admin'): ?>
                    <span class="inline-flex rounded-full bg-violet-100 dark:bg-violet-950/70 text-violet-800 dark:text-violet-200 px-2.5 py-0.5 text-[10px] font-bold uppercase tracking-wide ring-1 ring-violet-300/60 dark:ring-violet-800/50">Super admin</span>
                <?php else: ?>
                    <span class="inline-flex rounded-full bg-slate-200/90 dark:bg-slate-800 text-slate-700 dark:text-slate-300 px-2.5 py-0.5 text-[10px] font-bold uppercase tracking-wide ring-1 ring-slate-300/60 dark:ring-slate-600/50">User</span>
                <?php endif; ?>
                <?php if ($active): ?>
                    <span class="inline-flex rounded-full bg-emerald-100 dark:bg-emerald-950/60 text-emerald-800 dark:text-emerald-200 px-2.5 py-0.5 text-[10px] font-bold uppercase tracking-wide ring-1 ring-emerald-300/60 dark:ring-emerald-800/50">Active</span>
                <?php else: ?>
                    <span class="inline-flex rounded-full bg-slate-300/80 dark:bg-slate-700 text-slate-800 dark:text-slate-200 px-2.5 py-0.5 text-[10px] font-bold uppercase tracking-wide">Inactive</span>
                <?php endif; ?>
                <?php if ($includeAnalytics): ?>
                    <span class="inline-flex rounded-full bg-teal-100 dark:bg-teal-950/65 text-teal-900 dark:text-teal-200 px-2.5 py-0.5 text-[10px] font-bold uppercase tracking-wide ring-1 ring-teal-300/60 dark:ring-teal-800/50">Included in analytics</span>
                <?php else: ?>
                    <span class="inline-flex rounded-full bg-amber-100 dark:bg-amber-950/55 text-amber-950 dark:text-amber-100 px-2.5 py-0.5 text-[10px] font-bold uppercase tracking-wide ring-1 ring-amber-400/65 dark:ring-amber-800/45">Excluded from analytics</span>
                <?php endif; ?>
            </div>
            <p class="mt-2 text-sm text-slate-600 dark:text-slate-400"><?= Str::e($email) ?></p>
            <?php if ($full !== ''): ?><p class="text-sm font-medium text-slate-700 dark:text-slate-300 mt-0.5"><?= Str::e($full) ?></p><?php endif; ?>

            <!-- Quick summary stats -->
            <dl class="mt-4 grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-2 sm:gap-3">
                <div class="rounded-lg border border-slate-200/90 dark:border-slate-700/70 bg-slate-50/80 dark:bg-slate-900/40 px-2.5 py-2">
                    <dt class="text-[10px] font-bold uppercase tracking-wide text-slate-500 dark:text-slate-400">Total balance</dt>
                    <dd class="text-sm font-extrabold tabular-nums text-slate-900 dark:text-white mt-0.5"><?= Str::e($baseCurrency) ?> <?= number_format((float) $totalBalanceBase, 2) ?></dd>
                </div>
                <div class="rounded-lg border border-slate-200/90 dark:border-slate-700/70 bg-slate-50/80 dark:bg-slate-900/40 px-2.5 py-2">
                    <dt class="text-[10px] font-bold uppercase tracking-wide text-slate-500 dark:text-slate-400">Wallets</dt>
                    <dd class="text-sm font-extrabold tabular-nums text-slate-900 dark:text-white mt-0.5"><?= count($walletRows) ?></dd>
                </div>
                <div class="rounded-lg border border-slate-200/90 dark:border-slate-700/70 bg-slate-50/80 dark:bg-slate-900/40 px-2.5 py-2">
                    <dt class="text-[10px] font-bold uppercase tracking-wide text-slate-500 dark:text-slate-400">Recurring</dt>
                    <dd class="text-sm font-extrabold tabular-nums text-slate-900 dark:text-white mt-0.5"><?= (int) $recurringCount ?></dd>
                </div>
                <div class="rounded-lg border border-slate-200/90 dark:border-slate-700/70 bg-slate-50/80 dark:bg-slate-900/40 px-2.5 py-2">
                    <dt class="text-[10px] font-bold uppercase tracking-wide text-slate-500 dark:text-slate-400">Joined</dt>
                    <dd class="text-sm font-semibold tabular-nums text-slate-800 dark:text-slate-100 mt-0.5"><?= Str::e(substr($created, 0, 10)) ?></dd>
                </div>
                <div class="rounded-lg border border-slate-200/90 dark:border-slate-700/70 bg-slate-50/80 dark:bg-slate-900/40 px-2.5 py-2 col-span-2 sm:col-span-1 lg:col-span-2">
                    <dt class="text-[10px] font-bold uppercase tracking-wide text-slate-500 dark:text-slate-400">Last login</dt>
                    <dd class="text-sm font-semibold tabular-nums text-slate-800 dark:text-slate-100 mt-0.5"><?= ($lastLogin !== '' && $lastLogin !== '0000-00-00 00:00:00') ? Str::e(substr($lastLogin, 0, 16)) : '—' ?></dd>
                </div>
            </dl>
        </div>
    </div>
</section>

<!-- 2 · Quick actions -->
<section class="mb-6 scroll-mt-20" aria-label="Quick actions">
    <p class="<?= Str::e($secKicker) ?>">Actions</p>
    <div class="flex flex-col gap-3">
        <div class="rounded-xl border border-slate-300/85 dark:border-slate-700/65 bg-white dark:bg-slate-900/40 px-3 py-3 sm:px-4 sm:py-3.5 shadow-sm ring-1 ring-slate-900/[0.04] dark:ring-white/[0.05]">
            <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-3">
                <div class="min-w-0">
                    <p class="text-[11px] font-bold uppercase tracking-[0.12em] text-slate-500 dark:text-slate-400 hidden sm:block">Operational shortcuts</p>
                    <p class="text-xs text-slate-600 dark:text-slate-400 sm:mt-0.5 leading-snug">Jump to edits, wallets, credentials, or access control.</p>
                </div>
                <div class="flex flex-wrap items-center gap-2">
                    <a href="#manage-profile" class="inline-flex items-center justify-center gap-2 rounded-xl bg-gradient-to-r from-teal-600 to-emerald-600 text-white px-4 py-2.5 text-xs font-bold shadow-md shadow-teal-900/20 hover:opacity-95 ring-1 ring-teal-950/15">
                        <i data-lucide="pencil" class="w-4 h-4 shrink-0"></i> Edit user
                    </a>
                    <a href="<?= Str::e(Url::to('/admin/wallets?' . http_build_query(['user_id' => $uid]))) ?>" class="inline-flex items-center justify-center gap-2 rounded-xl border-2 border-slate-300/90 dark:border-slate-600 bg-white dark:bg-slate-900 px-4 py-2.5 text-xs font-bold text-slate-800 dark:text-slate-100 hover:border-teal-500/50 dark:hover:border-teal-600 transition-colors">
                        <i data-lucide="wallet" class="w-4 h-4 text-teal-600 dark:text-teal-400 shrink-0"></i> Manage wallets
                    </a>
                    <a href="#field-new-password" id="quick-reset-password" class="inline-flex items-center justify-center gap-2 rounded-xl border border-slate-300/90 dark:border-slate-600 bg-slate-50 dark:bg-slate-800/60 px-4 py-2.5 text-xs font-bold text-slate-700 dark:text-slate-200 hover:bg-slate-100 dark:hover:bg-slate-800">
                        <i data-lucide="key" class="w-4 h-4 text-slate-600 dark:text-slate-400 shrink-0"></i> Reset password
                    </a>
                    <?php if (! $isSelf): ?>
                        <?php if ($active): ?>
                        <form method="post" action="<?= Str::e(Url::to('/admin/users/status')) ?>" class="inline-flex" onsubmit="return confirm('Suspend this user? They will not be able to sign in.');">
                            <?= Csrf::field() ?>
                            <input type="hidden" name="user_id" value="<?= $uid ?>" />
                            <input type="hidden" name="is_active" value="0" />
                            <input type="hidden" name="_redirect" value="detail" />
                            <button type="submit" class="inline-flex items-center justify-center gap-2 rounded-xl border border-amber-400/80 dark:border-amber-700/55 bg-amber-50 dark:bg-amber-950/35 px-4 py-2.5 text-xs font-bold text-amber-950 dark:text-amber-200 hover:bg-amber-100 dark:hover:bg-amber-950/55">
                                <i data-lucide="ban" class="w-4 h-4 shrink-0"></i> Suspend / deactivate
                            </button>
                        </form>
                        <?php else: ?>
                        <form method="post" action="<?= Str::e(Url::to('/admin/users/status')) ?>" class="inline-flex">
                            <?= Csrf::field() ?>
                            <input type="hidden" name="user_id" value="<?= $uid ?>" />
                            <input type="hidden" name="is_active" value="1" />
                            <input type="hidden" name="_redirect" value="detail" />
                            <button type="submit" class="inline-flex items-center justify-center gap-2 rounded-xl border border-emerald-400/80 dark:border-emerald-800/55 bg-emerald-50 dark:bg-emerald-950/35 px-4 py-2.5 text-xs font-bold text-emerald-900 dark:text-emerald-200 hover:bg-emerald-100 dark:hover:bg-emerald-950/55">
                                <i data-lucide="unlock" class="w-4 h-4 shrink-0"></i> Activate account
                            </button>
                        </form>
                        <?php endif; ?>
                    <?php else: ?>
                    <span class="inline-flex items-center gap-1.5 rounded-xl border border-slate-200 dark:border-slate-700 px-4 py-2.5 text-[11px] font-semibold text-slate-500 dark:text-slate-400 italic" title="Use another admin for lifecycle changes">
                        Lifecycle locked (you)
                    </span>
                    <?php endif; ?>
                    <a href="<?= Str::e(Url::to('/admin/transactions?' . http_build_query(['user_id' => $uid]))) ?>" class="inline-flex items-center justify-center gap-2 rounded-xl px-4 py-2.5 text-xs font-semibold text-slate-500 dark:text-slate-400 hover:text-teal-700 dark:hover:text-teal-300">
                        <i data-lucide="arrow-left-right" class="w-4 h-4 shrink-0"></i> Ledger
                    </a>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- 3 · Management -->
<section id="manage" class="scroll-mt-20 mb-8" aria-labelledby="manage-heading">
    <div class="mb-3">
        <p class="<?= Str::e($secKicker) ?>">Primary</p>
        <h2 id="manage-heading" class="<?= Str::e($secTitle) ?>">Management</h2>
        <p class="<?= Str::e($secSub) ?>">Identity, privileges, lifecycle, and how this member receives safety notifications.</p>
    </div>
    <div class="<?= Str::e($manageShell) ?> p-4 sm:p-5 lg:p-6">
        <div class="grid lg:grid-cols-12 gap-4 lg:gap-5">

            <!-- Profile & access -->
            <div id="manage-profile" class="scroll-mt-24 lg:col-span-7 space-y-0">
                <div class="<?= Str::e($manageCardInner) ?>">
                    <h3 class="text-sm font-bold text-slate-900 dark:text-white">Profile &amp; access</h3>
                    <p class="text-xs text-slate-600 dark:text-slate-400 mt-1 mb-4">Email, display name, role, account state, and optional password rotation.</p>
                    <form method="post" action="<?= Str::e(Url::to('/admin/users/update')) ?>" class="space-y-3">
                        <?= Csrf::field() ?>
                        <input type="hidden" name="user_id" value="<?= $uid ?>" />
                        <input type="hidden" name="_redirect" value="detail" />
                        <div>
                            <label class="text-[11px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wide">Email</label>
                            <input name="email" type="email" value="<?= Str::e($email) ?>" required class="mt-1 w-full rounded-xl border border-slate-300/90 dark:border-slate-600 bg-white dark:bg-slate-900 px-3 py-2 text-sm" />
                        </div>
                        <div>
                            <label class="text-[11px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wide">Full name</label>
                            <input name="full_name" value="<?= Str::e($full) ?>" class="mt-1 w-full rounded-xl border border-slate-300/90 dark:border-slate-600 bg-white dark:bg-slate-900 px-3 py-2 text-sm" />
                        </div>
                        <div>
                            <label class="text-[11px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wide">Role</label>
                            <select name="role" class="mt-1 w-full rounded-xl border border-slate-300/90 dark:border-slate-600 bg-white dark:bg-slate-900 px-3 py-2 text-sm font-semibold">
                                <option value="user" <?= $role === 'user' ? 'selected' : '' ?>>User</option>
                                <option value="super_admin" <?= $role === 'super_admin' ? 'selected' : '' ?>>Super admin</option>
                            </select>
                        </div>
                        <label class="flex items-center gap-2 text-sm font-semibold text-slate-700 dark:text-slate-200">
                            <input type="checkbox" name="is_active" value="1" <?= $active ? 'checked' : '' ?> class="rounded border-slate-300" />
                            Account active
                        </label>
                        <input type="hidden" name="include_in_analytics" value="0" />
                        <label class="flex items-start gap-2.5 text-sm text-slate-700 dark:text-slate-200">
                            <input type="checkbox" name="include_in_analytics" value="1" <?= $includeAnalytics ? 'checked' : '' ?> class="mt-0.5 rounded border-slate-300 shrink-0" />
                            <span>
                                <span class="font-semibold block">Include in analytics &amp; reports</span>
                                <span class="text-[11px] text-slate-500 dark:text-slate-400 font-normal leading-relaxed mt-0.5 block">Excluded users remain active, but their financial records will not affect global analytics, reports, or platform dashboards.</span>
                            </span>
                        </label>
                        <div id="field-new-password" class="scroll-mt-28">
                            <label class="text-[11px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wide">New password (optional)</label>
                            <input type="password" name="new_password" id="input-new-password" minlength="8" autocomplete="new-password" class="mt-1 w-full rounded-xl border border-slate-300/90 dark:border-slate-600 bg-white dark:bg-slate-900 px-3 py-2 text-sm" placeholder="Leave blank to keep current" />
                        </div>
                        <button type="submit" class="w-full rounded-xl bg-gradient-to-r from-teal-600 to-emerald-600 text-white py-2.5 text-sm font-bold shadow-md shadow-teal-900/20 hover:opacity-95">Save changes</button>
                    </form>
                </div>
            </div>

            <div class="lg:col-span-5 space-y-4">
                <!-- Lifecycle -->
                <div id="manage-lifecycle" class="scroll-mt-24">
                    <div class="<?= Str::e($manageCardInner) ?>">
                        <h3 class="text-sm font-bold text-slate-900 dark:text-white">Lifecycle</h3>
                        <p class="text-xs text-slate-600 dark:text-slate-400 mt-1 mb-3">Suspension stops sign-in. Use the action strip above for the fastest toggle.</p>
                        <?php if (! $isSelf): ?>
                            <?php if ($active): ?>
                            <form method="post" action="<?= Str::e(Url::to('/admin/users/status')) ?>" onsubmit="return confirm('Suspend this user?');">
                                <?= Csrf::field() ?>
                                <input type="hidden" name="user_id" value="<?= $uid ?>" />
                                <input type="hidden" name="is_active" value="0" />
                                <input type="hidden" name="_redirect" value="detail" />
                                <button type="submit" class="w-full inline-flex items-center justify-center gap-2 rounded-xl border border-amber-300/80 dark:border-amber-800/60 bg-amber-50/90 dark:bg-amber-950/30 px-4 py-2.5 text-xs font-bold text-amber-950 dark:text-amber-100 hover:bg-amber-100 dark:hover:bg-amber-950/50">
                                    <i data-lucide="pause-circle" class="w-4 h-4"></i> Suspend / deactivate
                                </button>
                            </form>
                            <?php else: ?>
                            <form method="post" action="<?= Str::e(Url::to('/admin/users/status')) ?>">
                                <?= Csrf::field() ?>
                                <input type="hidden" name="user_id" value="<?= $uid ?>" />
                                <input type="hidden" name="is_active" value="1" />
                                <input type="hidden" name="_redirect" value="detail" />
                                <button type="submit" class="w-full inline-flex items-center justify-center gap-2 rounded-xl border border-emerald-300/80 dark:border-emerald-800/60 bg-emerald-50/90 dark:bg-emerald-950/30 px-4 py-2.5 text-xs font-bold text-emerald-900 dark:text-emerald-100 hover:bg-emerald-100 dark:hover:bg-emerald-950/50">
                                    <i data-lucide="play-circle" class="w-4 h-4"></i> Activate account
                                </button>
                            </form>
                            <?php endif; ?>
                        <?php else: ?>
                            <p class="text-xs text-slate-500 dark:text-slate-400 italic">You cannot suspend your own account.</p>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Notifications -->
                <div id="manage-notifications" class="scroll-mt-24">
                    <div class="<?= Str::e($manageCardInner) ?>">
                        <h3 class="text-sm font-bold text-slate-900 dark:text-white">Notification preferences</h3>
                        <p class="text-xs text-slate-600 dark:text-slate-400 mt-1 mb-3">Theme default and muting low-balance warnings for this member.</p>
                        <form method="post" action="<?= Str::e(Url::to('/admin/users/prefs')) ?>" class="space-y-3">
                            <?= Csrf::field() ?>
                            <input type="hidden" name="user_id" value="<?= $uid ?>" />
                            <div>
                                <label class="text-[11px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wide">Theme preference</label>
                                <select name="preference_theme" class="mt-1 w-full rounded-xl border border-slate-300/90 dark:border-slate-600 bg-white dark:bg-slate-900 px-3 py-2 text-sm font-semibold">
                                    <option value="system" <?= $prefTheme === 'system' ? 'selected' : '' ?>>System</option>
                                    <option value="light" <?= $prefTheme === 'light' ? 'selected' : '' ?>>Light</option>
                                    <option value="dark" <?= $prefTheme === 'dark' ? 'selected' : '' ?>>Dark</option>
                                </select>
                            </div>
                            <label class="flex items-center gap-2 text-sm font-semibold text-slate-700 dark:text-slate-200">
                                <input type="checkbox" name="preference_mute_low_balance" value="1" <?= $prefMute ? 'checked' : '' ?> class="rounded border-slate-300" />
                                Mute low-balance warnings
                            </label>
                            <button type="submit" class="w-full rounded-xl border border-slate-300 dark:border-slate-600 py-2.5 text-sm font-bold text-slate-800 dark:text-slate-100 hover:bg-slate-50 dark:hover:bg-slate-800/80">Save preferences</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- 4 · Financial insights -->
<section class="mb-8" aria-labelledby="insights-heading">
    <div class="mb-4 pb-3 border-b border-slate-200/90 dark:border-slate-700/70">
        <p class="<?= Str::e($secKicker) ?>">Context</p>
        <h2 id="insights-heading" class="text-sm sm:text-base font-bold text-slate-700 dark:text-slate-300">Financial insights</h2>
        <p class="text-xs text-slate-500 dark:text-slate-500 mt-1">High-level liquidity signals — supplemental to operational tasks above.</p>
    </div>
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-2 sm:gap-3 mb-4">
        <div class="<?= Str::e($insightCardShell) ?>">
            <p class="text-[10px] font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">Income</p>
            <p class="text-base font-bold tabular-nums text-emerald-700 dark:text-emerald-400 mt-0.5"><?= Str::e($baseCurrency) ?> <?= number_format((float) $totalsAll['income'], 0) ?></p>
            <p class="text-[10px] text-slate-500 dark:text-slate-500 mt-0.5">Lifetime · base</p>
        </div>
        <div class="<?= Str::e($insightCardShell) ?>">
            <p class="text-[10px] font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">Expense</p>
            <p class="text-base font-bold tabular-nums text-rose-700 dark:text-rose-400 mt-0.5"><?= Str::e($baseCurrency) ?> <?= number_format((float) $totalsAll['expense'], 0) ?></p>
            <p class="text-[10px] text-slate-500 dark:text-slate-500 mt-0.5">Lifetime · base</p>
        </div>
        <div class="<?= Str::e($insightCardShell) ?>">
            <p class="text-[10px] font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">Savings rate</p>
            <p class="text-base font-bold tabular-nums text-teal-700 dark:text-teal-400 mt-0.5"><?= Str::e((string) $savingsRate) ?>%</p>
            <p class="text-[10px] text-slate-500 dark:text-slate-500 mt-0.5">Income vs spend</p>
        </div>
        <div class="<?= Str::e($insightCardShell) ?>">
            <p class="text-[10px] font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">Transfers · 90d</p>
            <p class="text-base font-bold tabular-nums text-slate-800 dark:text-slate-100 mt-0.5"><?= (int) $transferStats['count'] ?></p>
            <p class="text-[10px] text-slate-500 dark:text-slate-500 tabular-nums"><?= Str::e($baseCurrency) ?> <?= number_format((float) $transferStats['volume_base'], 0) ?> moved</p>
        </div>
    </div>
    <div class="grid lg:grid-cols-12 gap-3 lg:gap-4">
        <div class="lg:col-span-7"><?php View::partial('components/analytics/chart-shell-card', [
            'title' => 'Cashflow trend',
            'subtitle' => 'Trailing months · base currency',
            'chartId' => 'adminUserCashflowChart',
            'headerSimple' => true,
            'cardClass' => $insightChartShell,
            'chartContainerClass' => 'mt-2 min-h-[140px]',
        ]); ?></div>
        <div class="lg:col-span-5 flex">
            <div class="<?= Str::e($insightChartShell) ?> flex flex-col w-full">
                <h3 class="text-sm font-semibold text-slate-800 dark:text-slate-100">Savings rate</h3>
                <p class="text-[11px] text-slate-600 dark:text-slate-400 mt-0.5">Share of income retained</p>
                <div id="adminUserSavingsGauge" class="flex-1 min-h-[128px] max-h-[148px]"></div>
            </div>
        </div>
        <div class="lg:col-span-6"><?php View::partial('components/analytics/chart-shell-card', [
            'title' => 'Wallet mix',
            'subtitle' => 'Estimated balance · base',
            'chartId' => 'adminUserWalletDonut',
            'headerSimple' => true,
            'cardClass' => $insightChartShell,
            'chartContainerClass' => 'mt-2 min-h-[150px]',
        ]); ?></div>
        <div class="lg:col-span-6"><?php View::partial('components/analytics/chart-shell-card', [
            'title' => 'Expense mix',
            'subtitle' => 'Top categories · all time',
            'chartId' => 'adminUserCatDonut',
            'headerSimple' => true,
            'cardClass' => $insightChartShell,
            'chartContainerClass' => 'mt-2 min-h-[150px]',
        ]); ?></div>
    </div>
</section>

<!-- 5 · Wallets -->
<section class="mb-8" aria-labelledby="wallets-heading">
    <div class="mb-3">
        <p class="<?= Str::e($secKicker) ?>">Structures</p>
        <div class="flex flex-wrap items-end justify-between gap-3">
            <div>
                <h2 id="wallets-heading" class="<?= Str::e($secTitle) ?>">Linked wallets</h2>
                <p class="<?= Str::e($secSub) ?>">Balances are estimated in base currency for ops review.</p>
            </div>
            <a href="<?= Str::e(Url::to('/admin/wallets?' . http_build_query(['user_id' => $uid]))) ?>" class="inline-flex items-center gap-1.5 rounded-lg border border-teal-300/80 dark:border-teal-700/55 bg-teal-50 dark:bg-teal-950/30 px-3 py-2 text-[11px] font-bold text-teal-900 dark:text-teal-200 hover:bg-teal-100 dark:hover:bg-teal-950/50 shrink-0">
                <i data-lucide="external-link" class="w-3.5 h-3.5"></i> Wallet admin
            </a>
        </div>
    </div>
    <div class="<?= Str::e($mutedShell) ?> border-solid border-slate-200/95 dark:border-slate-700/65 bg-white/80 dark:bg-[#0d1424]/60">
        <?php if ($walletRows === []): ?>
            <p class="text-sm text-slate-500 dark:text-slate-400">No wallets yet.</p>
        <?php else: ?>
        <div class="overflow-x-auto rounded-lg border border-slate-200/90 dark:border-slate-800/80 bg-white dark:bg-slate-900/30">
            <table class="min-w-full text-sm">
                <thead class="bg-slate-100/95 dark:bg-slate-900/90 text-[10px] font-extrabold uppercase tracking-wider text-slate-500 dark:text-slate-400">
                    <tr>
                        <th class="px-3 py-2.5 text-left">Wallet</th>
                        <th class="px-3 py-2.5 text-left">Type</th>
                        <th class="px-3 py-2.5 text-center">Status</th>
                        <th class="px-3 py-2.5 text-right">Balance (est.)</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                    <?php foreach ($walletRows as $w):
                        $wname = (string) ($w['name'] ?? '');
                        $wtype = (string) ($w['type_label'] ?? '');
                        $wact = ! empty($w['is_active']);
                        $bb = (float) ($w['balance_base'] ?? 0);
                        $bell = ! empty($w['below_threshold']);
                        ?>
                    <tr class="hover:bg-slate-50/80 dark:hover:bg-slate-800/30 transition-colors">
                        <td class="px-3 py-2.5 font-semibold text-slate-800 dark:text-slate-100"><?= Str::e($wname) ?></td>
                        <td class="px-3 py-2.5 text-slate-600 dark:text-slate-400"><?= Str::e($wtype) ?></td>
                        <td class="px-3 py-2.5 text-center"><?php if ($wact): ?><span class="text-[10px] font-bold text-emerald-600 dark:text-emerald-400">Active</span><?php else: ?><span class="text-[10px] font-bold text-slate-500">Off</span><?php endif; ?></td>
                        <td class="px-3 py-2.5 text-right tabular-nums font-bold text-slate-900 dark:text-white">
                            <?= Str::e($baseCurrency) ?> <?= number_format($bb, 2) ?>
                            <?php if ($bell): ?><span class="ml-1 inline-block w-2 h-2 rounded-full bg-amber-500 align-middle" title="Below threshold"></span><?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>
</section>

<!-- 6 · Activity & audit -->
<section class="mb-4 opacity-[0.97]" aria-labelledby="activity-heading">
    <div class="mb-4 pb-2 border-b border-slate-200/70 dark:border-slate-800/70">
        <p class="text-[10px] font-bold uppercase tracking-[0.18em] text-slate-400 dark:text-slate-600 mb-1">History</p>
        <h2 id="activity-heading" class="text-sm font-semibold text-slate-600 dark:text-slate-400">Activity &amp; audit</h2>
    </div>
    <div class="grid lg:grid-cols-2 gap-4">
        <div class="<?= Str::e($mutedShell) ?>">
            <div class="flex items-center justify-between gap-2 mb-3">
                <h3 class="text-xs font-bold uppercase tracking-wide text-slate-500 dark:text-slate-500">Recent transactions</h3>
                <a href="<?= Str::e(Url::to('/admin/transactions?' . http_build_query(['user_id' => $uid]))) ?>" class="text-[11px] font-semibold text-slate-400 hover:text-teal-600 dark:hover:text-teal-400">Ledger</a>
            </div>
            <?php if ($recentTx === []): ?>
                <p class="text-xs text-slate-500 dark:text-slate-500 py-4">None yet.</p>
            <?php else: ?>
            <ul class="divide-y divide-slate-200/80 dark:divide-slate-800/80 rounded-lg border border-slate-200/70 dark:border-slate-800/70 overflow-hidden bg-white/50 dark:bg-slate-900/20">
                <?php foreach ($recentTx as $t):
                    $tt = (string) ($t['type'] ?? '');
                    $amt = (float) ($t['amount_base'] ?? 0);
                    $td = (string) ($t['transaction_date'] ?? '');
                    $title = (string) ($t['title'] ?? '');
                    $typeCls = $tt === 'income' ? 'text-emerald-600 dark:text-emerald-400/90' : ($tt === 'expense' ? 'text-rose-600 dark:text-rose-400/90' : 'text-sky-600 dark:text-sky-300/90');
                    $sign = $tt === 'expense' ? '−' : ($tt === 'income' ? '+' : '');
                    $amtLine = $sign . $baseCurrency . ' ' . number_format($amt, 2);
                    ?>
                <li class="flex items-center gap-2 px-2.5 py-2 hover:bg-slate-50/70 dark:hover:bg-slate-800/25 transition-colors">
                    <div class="flex-1 min-w-0">
                        <p class="text-xs font-medium text-slate-700 dark:text-slate-300 truncate"><?= Str::e($title) ?></p>
                        <p class="text-[10px] text-slate-400 dark:text-slate-600"><?= Str::e(substr($td, 0, 10)) ?> · <?= Str::e($tt) ?></p>
                    </div>
                    <span class="text-xs font-bold tabular-nums shrink-0 <?= Str::e($typeCls) ?>"><?= Str::e($amtLine) ?></span>
                </li>
                <?php endforeach; ?>
            </ul>
            <?php endif; ?>
        </div>
        <div class="<?= Str::e($mutedShell) ?>">
            <h3 class="text-xs font-bold uppercase tracking-wide text-slate-500 dark:text-slate-500 mb-3">Audit log</h3>
            <?php if ($auditRows === []): ?>
                <p class="text-xs text-slate-500 dark:text-slate-500 py-4">No rows matched.</p>
            <?php else: ?>
            <div class="overflow-x-auto rounded-lg border border-slate-200/70 dark:border-slate-800/70 max-h-[280px] overflow-y-auto bg-white/40 dark:bg-slate-900/15">
                <table class="min-w-full text-[11px] sm:text-xs">
                    <thead class="sticky top-0 bg-slate-100/90 dark:bg-slate-900/95 text-[9px] font-extrabold uppercase tracking-wider text-slate-400 dark:text-slate-600">
                        <tr>
                            <th class="px-2 py-1.5 text-left">When</th>
                            <th class="px-2 py-1.5 text-left">Action</th>
                            <th class="px-2 py-1.5 text-left">Entity</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                        <?php foreach ($auditRows as $a):
                            $ac = (string) ($a['action'] ?? '');
                            $et = (string) ($a['entity_type'] ?? '');
                            $ei = (string) ($a['entity_id'] ?? '');
                            $ca = (string) ($a['created_at'] ?? '');
                            ?>
                        <tr class="text-slate-600 dark:text-slate-400">
                            <td class="px-2 py-1.5 whitespace-nowrap tabular-nums"><?= Str::e(substr($ca, 0, 16)) ?></td>
                            <td class="px-2 py-1.5 font-medium text-slate-700 dark:text-slate-300"><?= Str::e($ac) ?></td>
                            <td class="px-2 py-1.5"><?= Str::e($et) ?><?= $ei !== '' ? ' · ' . Str::e($ei) : '' ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>
        </div>
    </div>
</section>

<script>
(function () {
    var pwdLink = document.getElementById('quick-reset-password');
    var pwdInput = document.getElementById('input-new-password');
    if (pwdLink && pwdInput) {
        pwdLink.addEventListener('click', function () {
            setTimeout(function () { pwdInput.focus(); }, 260);
        });
    }

    var cur = <?= json_encode($baseCurrency, JSON_THROW_ON_ERROR) ?>;
    var months = <?= json_encode($months, JSON_THROW_ON_ERROR) ?>;
    var inc = <?= json_encode($incM, JSON_THROW_ON_ERROR) ?>;
    var exp = <?= json_encode($expM, JSON_THROW_ON_ERROR) ?>;
    var wLbl = <?= json_encode($donutWalletLabels, JSON_THROW_ON_ERROR) ?>;
    var wSer = <?= json_encode($donutWalletSeries, JSON_THROW_ON_ERROR) ?>;
    var cLbl = <?= json_encode($donutCatLabels, JSON_THROW_ON_ERROR) ?>;
    var cSer = <?= json_encode($donutCatSeries, JSON_THROW_ON_ERROR) ?>;
    var sRate = <?= json_encode((float) $savingsRate, JSON_THROW_ON_ERROR) ?>;

    function fmt(v) {
        return cur + ' ' + Number(v).toLocaleString(undefined, { maximumFractionDigits: 0 });
    }

    var charts = [];
    function teardownCharts() {
        charts.forEach(function (c) {
            try {
                if (c && typeof c.destroy === 'function') {
                    c.destroy();
                }
            } catch (e) {}
        });
        charts = [];
    }

    function bindCharts() {
        teardownCharts();
        if (typeof ApexCharts === 'undefined' || typeof KhfApexTheme === 'undefined') {
            return;
        }
        var tt = KhfApexTheme.tokens();

        function pushChart(el, opts) {
            if (! el) return;
            var ch = new ApexCharts(el, opts);
            charts.push(ch);
            ch.render();
        }

        var cashEl = document.getElementById('adminUserCashflowChart');
        if (cashEl && months.length) {
            var cashMerge = Object.assign(
                {},
                KhfApexTheme.chart({ type: 'area', height: 165, animations: { enabled: true, speed: 520 } }),
                {
                    series: [{ name: 'Income', data: inc }, { name: 'Expense', data: exp }],
                    colors: ['#10b981', '#f43f5e'],
                    stroke: { curve: 'smooth', width: [2, 2] },
                    fill: {
                        type: 'gradient',
                        gradient: {
                            shade: tt.incomeExpenseFillShade,
                            type: 'vertical',
                            shadeIntensity: KhfApexTheme.isDark() ? 0.32 : 0.22,
                            opacityFrom: KhfApexTheme.isDark() ? 0.36 : 0.34,
                            opacityTo: KhfApexTheme.isDark() ? 0.03 : 0.05,
                            stops: [0, 92, 100]
                        }
                    },
                    dataLabels: { enabled: false },
                    xaxis: {
                        categories: months,
                        labels: { style: { colors: tt.axisLabel, fontSize: '10px', fontWeight: 600 } },
                        axisBorder: { show: false },
                        axisTicks: { show: false },
                        tooltip: { enabled: false }
                    },
                    yaxis: {
                        labels: {
                            style: { colors: tt.axisLabel, fontSize: '10px', fontWeight: 600 },
                            formatter: function (v) {
                                return fmt(v);
                            }
                        },
                        axisBorder: { show: false },
                        axisTicks: { show: false }
                    },
                    grid: Object.assign(KhfApexTheme.grid({ strokeDashArray: 3 }), { padding: { top: 4, bottom: 0, left: 0, right: 8 } }),
                    tooltip: Object.assign(KhfApexTheme.tooltip(), {
                        shared: true,
                        intersect: false,
                        y: {
                            formatter: function (v) {
                                return fmt(v);
                            }
                        }
                    }),
                    legend: KhfApexTheme.legendTopRight({ fontSize: '11px', labels: { colors: tt.legend } })
                }
            );
            pushChart(cashEl, cashMerge);
        } else if (cashEl) {
            cashEl.innerHTML = KhfApexTheme.emptyStateHtml('Not enough history for a trend.');
        }

        var gEl = document.getElementById('adminUserSavingsGauge');
        if (gEl) {
            var rateColor = sRate >= 20 ? '#0d9488' : sRate >= 10 ? '#f59e0b' : '#f43f5e';
            var radMerge = Object.assign(
                {},
                KhfApexTheme.chart({ type: 'radialBar', height: 135, offsetY: -4 }),
                {
                    series: [Math.max(0, Math.min(100, sRate))],
                    colors: [rateColor],
                    plotOptions: {
                        radialBar: {
                            startAngle: -120,
                            endAngle: 120,
                            hollow: { size: '68%' },
                            track: { background: tt.radialTrack, strokeWidth: '100%' },
                            dataLabels: {
                                show: true,
                                name: { show: false },
                                value: {
                                    fontSize: '16px',
                                    fontWeight: 800,
                                    offsetY: 5,
                                    color: tt.donutCenterValue,
                                    formatter: function (v) {
                                        return v + '%';
                                    }
                                }
                            }
                        }
                    },
                    stroke: { lineCap: 'round' }
                }
            );
            pushChart(gEl, radMerge);
        }

        function donutPaletteWallet() {
            return ['#6366f1', '#0ea5e9', '#10b981', '#f59e0b', '#ec4899', '#8b5cf6'];
        }
        function donutPaletteCat() {
            return ['#f43f5e', '#f97316', '#eab308', '#22c55e', '#06b6d4', '#8b5cf6', '#a855f7', '#ec4899'];
        }

        var wEl = document.getElementById('adminUserWalletDonut');
        if (wEl) {
            var wSum = wSer.reduce(function (a, b) {
                return a + b;
            }, 0);
            if (!wLbl.length || wSum <= 0) {
                wEl.innerHTML = KhfApexTheme.emptyStateHtml('No wallet balances.');
            } else {
                pushChart(
                    wEl,
                    Object.assign(
                        {},
                        KhfApexTheme.chart({ type: 'donut', height: 168 }),
                        {
                            stroke: {
                                show: true,
                                width: KhfApexTheme.isDark() ? 2 : 1.25,
                                colors: [tt.donutRingStroke]
                            },
                            series: wSer,
                            labels: wLbl,
                            colors: donutPaletteWallet(),
                            plotOptions: {
                                pie: {
                                    expandOnClick: false,
                                    donut: {
                                        size: '70%',
                                        background: 'transparent',
                                        labels: {
                                            show: true,
                                            name: { show: false },
                                            total: {
                                                show: true,
                                                label: 'Total',
                                                fontSize: '11px',
                                                fontWeight: 600,
                                                color: tt.donutCenterLabel,
                                                formatter: function () {
                                                    return fmt(wSum);
                                                }
                                            },
                                            value: { show: false }
                                        }
                                    }
                                }
                            },
                            dataLabels: { enabled: false },
                            legend: KhfApexTheme.legendBottom({ offsetY: 2, fontSize: '10px' }),
                            tooltip: Object.assign(KhfApexTheme.tooltip(), {
                                y: {
                                    formatter: function (v) {
                                        return fmt(v);
                                    }
                                }
                            })
                        }
                    )
                );
            }
        }

        var cEl = document.getElementById('adminUserCatDonut');
        if (cEl) {
            var cSum = cSer.reduce(function (a, b) {
                return a + b;
            }, 0);
            if (!cLbl.length || cSum <= 0) {
                cEl.innerHTML = KhfApexTheme.emptyStateHtml('No categorized spend.');
            } else {
                pushChart(
                    cEl,
                    Object.assign(
                        {},
                        KhfApexTheme.chart({ type: 'donut', height: 168 }),
                        {
                            stroke: {
                                show: true,
                                width: KhfApexTheme.isDark() ? 2 : 1.25,
                                colors: [tt.donutRingStroke]
                            },
                            series: cSer,
                            labels: cLbl,
                            colors: donutPaletteCat(),
                            plotOptions: {
                                pie: {
                                    expandOnClick: false,
                                    donut: {
                                        size: '70%',
                                        background: 'transparent',
                                        labels: {
                                            show: true,
                                            name: { show: false },
                                            total: {
                                                show: true,
                                                label: 'Spend',
                                                fontSize: '11px',
                                                fontWeight: 600,
                                                color: tt.donutCenterLabel,
                                                formatter: function () {
                                                    return fmt(cSum);
                                                }
                                            },
                                            value: { show: false }
                                        }
                                    }
                                }
                            },
                            dataLabels: { enabled: false },
                            legend: KhfApexTheme.legendBottom({ offsetY: 2, fontSize: '10px' }),
                            tooltip: Object.assign(KhfApexTheme.tooltip(), {
                                y: {
                                    formatter: function (v) {
                                        return fmt(v);
                                    }
                                }
                            })
                        }
                    )
                );
            }
        }
    }

    if (typeof KhfApexTheme !== 'undefined' && KhfApexTheme.mountOnTheme) {
        KhfApexTheme.mountOnTheme(bindCharts);
    } else if (typeof ApexCharts !== 'undefined') {
        bindCharts();
    }
})();
</script>

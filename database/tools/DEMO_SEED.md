# Rich demo dataset (`rich_demo_seed.php`)

## Command

After base schema + `database/seeders/002_demo_seed.sql` (and migrations `002_features` … as in README):

```bash
php database/tools/rich_demo_seed.php
```

Safe to **re-run**: removes the cohort users (`demo`, `demo_*`), relinks any stray `transactions.created_by` to `superadmin`, wipes superadmin wallets/transactions/recurring/notifications for a clean admin slice, then rebuilds everyone. `superadmin` password is unchanged (`Admin@123` from seed).

All cohort users below use **`Demo@123`** (same bcrypt as documented for `demo`).

## Personas & intent

| Username | Focus |
|----------|--------|
| `demo` | **Primary showcase** — salary + freelance bursts, fibre/utilities/bills, multi-wallet transfers, consolidated grocery trips + Penang envelope trip, recurring (salary Netflix Spotify gym telecom), mixed spending on bank/TNG/GrapPay/card/cash. |
| `demo_office` | Commuter PAY with rent and streaming bundle; RapidKL-heavy transport; predictable lunch/cash rhythm. |
| `demo_freelance` | Lumpy invoicing + tax-pot transfers (~8%); Adobe/fibre tooling; refunds sometimes. |
| `demo_student` | Parent allowance rhythm + sporadic campus income; light spend; Spotify Student recurring **paused** (story). |
| `demo_family` | High pooled income; mortgage/maintenance childcare; sinking fund transfers; consolidated wet-market haul. |
| `demo_sidebiz` | Salary + Shopee side wallet → bank sweep; hire purchase; occasional bonus storyline. |
| `demo_struggle` | Thin buffer after rent/loan/telecom; low thresholds to surface **warnings** + notifications. |

`superadmin` keeps a minimal **operations float** wallet, two seeded transactions, and fresh audit-ish entries (plus scripted tail events).

## Data coverage (~11 trailing months vs “today”)

- **Income**: salary variants, freelance, side/Shopee, allowance/bonus wording, cashback, refunds.
- **Expense**: granular global categories — groceries fuel toll housing telecom internet dining coffee entertainment shopping insurance loans childcare fitness travel subscriptions utilities healthcare education.
- **Transfers**: native `transfer` rows (bank ↔ e-wallet, sinking funds, Sweeps).
- **Consolidated parents + line items**: demo + family groceries; demo travel envelope.
- **Recurring schedules**: paused (`Spotify Premium` showcase; student Spotify paused), `skip_next` story removed in favour of sane telecom next dates; cron-friendly **future `next_occurrence`** on hot paths.
- **Notifications**: unread + read (with `data_json`), mixed types.
- **Audit**: assorted actions (login, wallet create, prefs, deletes, seed-style platform events).

## Behavioural notes

- **Deterministic RNG** per persona username seed — same cohort structure on every run; sliding window is anchored on the CLI run date (**today**).
- **Opening balances**: computed after inserts so targeted **ending balances** (incl. credit-card negative storytelling) reconcile with ledger math.
- **Wallet balances**: child split lines (`parent_transaction_id` set) do **not** double-count in wallets after the ledger adjustment in `WalletService::walletBalancesForUser()` (parent rows only contribute to flows).

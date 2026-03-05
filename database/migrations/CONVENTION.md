# Migration Naming Convention

## Rules for New Migrations

1. **Format**: `YYYY_MM_DD_HHMMSS_description.php`
2. **Description**: Use snake_case, start with verb:
   - `create_xxx_table` — new table
   - `add_xxx_to_yyy_table` — add columns
   - `modify_xxx_in_yyy_table` — alter columns
   - `remove_xxx_from_yyy_table` — drop columns
   - `drop_xxx_table` — drop table
   - `grant_xxx_permission` — permission changes
   - `backfill_xxx` — data migrations (one-time)

3. **One-time data migrations**: Add a guard check so they can't be re-run accidentally.

## Existing Migrations

Existing migration files should NOT be renamed — this would break the `migrations` table tracking and cause re-runs.

## Examples

```
2026_03_04_100000_add_status_index_to_slots_table.php  ✅
2026_03_04_100001_create_audit_logs_table.php           ✅
add_columns_to_users.php                                ❌ (no timestamp)
fix_stuff.php                                           ❌ (vague name)
```

# Queue workers

- Queue driver: `database`
- Ensure migrations are run: `php artisan migrate`
- Start workers (2-5 processes recommended):
  - `php artisan queue:work --queue=default --tries=3`
- Stop workers before deploying code changes, then restart.
- Failed jobs are recorded in `failed_jobs`; inspect via `php artisan queue:failed`.


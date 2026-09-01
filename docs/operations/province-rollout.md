# Province rollout and recovery runbook

## Release gates

A release may enter a station pilot only when all of these are true:

1. `/up` returns HTTP 200 and `/health/ready` returns `{"status":"ready"}`.
2. The complete feature suite passes, including cross-station authorization tests.
3. `ops:backup-database --verify` validates the latest off-site backup checksum.
4. The k6 readiness test stays below 1% errors, 750 ms p95, and 1.5 s p99 at 75 virtual users.
5. Both `general` and `forecast` worker heartbeats are fresh and the failed-job count is zero.
6. A recovery drill has met the RPO/RTO targets below without restoring over production.

Run the repeatable gate on Windows with:

```powershell
.\scripts\run-province-readiness.ps1 -BaseUrl https://staging.example.gov.ph
```

## Backup and recovery

- Target RPO: 24 hours during pilot, then 4 hours before province-wide activation.
- Target RTO: 4 hours during pilot, then 2 hours before province-wide activation.
- Keep 35 days of daily logical backups in a private bucket separate from public media.
- Retain the database provider's point-in-time recovery in addition to logical backups.
- Alert when the newest verified manifest is older than 26 hours.

Monthly recovery drill:

1. Create a new, isolated PostgreSQL recovery database. Never target production.
2. Run `php artisan ops:backup-database --verify` before downloading the artifact.
3. Restore with `pg_restore --clean --if-exists --no-owner --dbname=<isolated-recovery-url> <artifact.dump>`.
4. Point a temporary application instance at the recovered database.
5. Run migrations in status-only mode first, then the full feature and multi-station workflow suite.
6. Verify station counts, user counts, active incidents, AAR signatures, and the newest audit record against the backup manifest time.
7. Record achieved RPO/RTO, discrepancies, approver, and cleanup confirmation.

## Limited station pilot

Start with one station selected for reliable connectivity, trained leadership, and representative incident volume. Set `ROLLOUT_MODE=pilot` and record its ID in `PILOT_STATION_IDS`.

For 14 days:

- Maintain the existing dispatch/reporting fallback in parallel.
- Staff an incident commander, station champion, technical support contact, and privacy/security contact.
- Review readiness, worker health, failed jobs, backup age, notification delivery, and error rate twice daily.
- Reconcile incidents, personnel, equipment, reports, and approvals against the fallback record each day.
- Stop the pilot for authorization leakage, unrecoverable data inconsistency, missed critical dispatch, backup failure beyond RPO, or sustained error rate above 2%.

## Gradual expansion

Expand only after the prior wave completes seven consecutive days without a stop condition:

1. Wave 0: one station for 14 days.
2. Wave 1: three stations in one district for 7 days.
3. Wave 2: 25% of provincial stations for 7 days.
4. Wave 3: 50% of provincial stations for 7 days.
5. Wave 4: all stations, with enhanced monitoring for 14 additional days.

Each wave requires written approval from the provincial administrator, operations lead, security/privacy lead, and disaster-recovery owner. A rollback returns traffic to the previous release, preserves database evidence, stops new mutations if integrity is uncertain, and keeps emergency dispatch on the documented fallback process.

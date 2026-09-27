-- Supabase/PostgreSQL manual seed script.
-- Purpose:
-- 1) Preview the newest role_id = 5 users.
-- 2) Insert 50 fire incident reports and 50 risk/hazard records.
--
-- Notes:
-- - This does not invent user IDs, barangay IDs, station IDs, or barangay hall coordinates.
-- - Reporters come from the newest available users where role_id = 5.
-- - Barangays come from existing barangay_halls joined to their existing marker coordinates.
-- - Incident and risk/hazard marker coordinates are slightly offset from the barangay hall marker.
-- - Run this manually in Supabase SQL Editor. It creates new rows each run.

BEGIN;

-- Preview first. Supabase will show this result before the write CTEs below finish.
SELECT
    user_id,
    account_code,
    first_name,
    middle_name,
    last_name,
    email,
    created_at
FROM users
WHERE role_id = 5
ORDER BY created_at DESC NULLS LAST, user_id DESC
LIMIT 50;

CREATE TEMP TABLE _seed_meta AS
SELECT 'manual-seed-' || to_char(clock_timestamp(), 'YYYYMMDDHH24MISSMS') AS run_key;

CREATE TEMP TABLE _role5_users AS
SELECT
    row_number() OVER (ORDER BY created_at DESC NULLS LAST, user_id DESC) AS rn,
    user_id
FROM users
WHERE role_id = 5
ORDER BY created_at DESC NULLS LAST, user_id DESC
LIMIT 50;

CREATE TEMP TABLE _seed_halls AS
SELECT
    row_number() OVER (ORDER BY b.barangay_name, bh.barangay_hall_id) AS rn,
    bh.barangay_hall_id,
    bh.barangay_id,
    b.barangay_name,
    bh.marker_id AS hall_marker_id,
    hm.lat::numeric AS hall_lat,
    hm.lng::numeric AS hall_lng,
    coverage.station_id
FROM barangay_halls bh
JOIN barangays b ON b.barangay_id = bh.barangay_id
JOIN markers hm ON hm.marker_id = bh.marker_id
LEFT JOIN LATERAL (
    SELECT sbc.station_id
    FROM station_barangay_coverage sbc
    WHERE sbc.barangay_id = bh.barangay_id
      AND COALESCE(sbc.is_active, true) = true
    ORDER BY CASE WHEN sbc.coverage_type = 'primary' THEN 0 ELSE 1 END, sbc.station_id
    LIMIT 1
) coverage ON true
WHERE hm.lat IS NOT NULL
  AND hm.lng IS NOT NULL;

DO $$
DECLARE
    role_user_count integer;
    hall_count integer;
BEGIN
    SELECT count(*) INTO role_user_count FROM _role5_users;
    SELECT count(*) INTO hall_count FROM _seed_halls;

    IF role_user_count < 1 THEN
        RAISE EXCEPTION 'No users found with role_id = 5. Create/fix citizen users first, then rerun this script.';
    END IF;

    IF hall_count < 1 THEN
        RAISE EXCEPTION 'No barangay halls with marker coordinates found. Upload barangay hall markers first, then rerun this script.';
    END IF;
END $$;

CREATE TEMP TABLE _incident_seed AS
SELECT
    gs.seq,
    u.user_id AS reported_by,
    h.barangay_hall_id,
    h.barangay_id,
    h.barangay_name,
    h.station_id,
    h.hall_lat,
    h.hall_lng,
    round((h.hall_lat::double precision + (0.00038 + ((gs.seq % 5) * 0.00003)) * cos(radians((gs.seq * 47) % 360)))::numeric, 7) AS lat,
    round((h.hall_lng::double precision + (0.00038 + ((gs.seq % 5) * 0.00003)) * sin(radians((gs.seq * 47) % 360)))::numeric, 7) AS lng,
    format('[seed:%s] Fire incident report %s - %s', m.run_key, lpad(gs.seq::text, 2, '0'), h.barangay_name) AS marker_title,
    format('Manual seed fire incident report for %s. Coordinates are offset from the barangay hall marker for map testing.', h.barangay_name) AS marker_description,
    CASE
        WHEN gs.seq % 5 = 0 THEN 'high'
        WHEN gs.seq % 3 = 0 THEN 'low'
        ELSE 'medium'
    END AS dispatch_priority,
    now() - ((gs.seq || ' hours')::interval) AS occurred_at
FROM generate_series(1, 50) AS gs(seq)
CROSS JOIN _seed_meta m
CROSS JOIN (SELECT count(*) AS user_count FROM _role5_users) uc
CROSS JOIN (SELECT count(*) AS hall_count FROM _seed_halls) hc
JOIN _role5_users u ON u.rn = (((gs.seq - 1) % uc.user_count) + 1)
JOIN _seed_halls h ON h.rn = (((gs.seq - 1) % hc.hall_count) + 1);

CREATE TEMP TABLE _inserted_incident_markers AS
WITH inserted AS (
    INSERT INTO markers (title, description, lat, lng, status, category, created_at, updated_at)
    SELECT
        marker_title,
        marker_description,
        lat,
        lng,
        'active',
        'fire',
        now(),
        now()
    FROM _incident_seed
    RETURNING marker_id, title
)
SELECT
    s.*,
    i.marker_id
FROM _incident_seed s
JOIN inserted i ON i.title = s.marker_title;

CREATE TEMP TABLE _inserted_incidents AS
WITH inserted AS (
    INSERT INTO incidents (
        status,
        marker_id,
        station_id,
        team_id,
        reported_by,
        barangay_id,
        barangay_resolution_status,
        barangay_resolution_source,
        barangay_resolved_at,
        occurred_at,
        occurred_at_source,
        dispatch_priority,
        created_at,
        updated_at
    )
    SELECT
        'Reported',
        marker_id,
        station_id,
        NULL,
        reported_by,
        barangay_id,
        'resolved',
        'manual_seed_barangay_hall_offset',
        now(),
        occurred_at,
        'manual_seed',
        dispatch_priority,
        occurred_at,
        now()
    FROM _inserted_incident_markers
    RETURNING incident_id, marker_id
)
SELECT
    s.*,
    i.incident_id
FROM _inserted_incident_markers s
JOIN inserted i ON i.marker_id = s.marker_id;

INSERT INTO barangay_fire_records (
    barangay_hall_id,
    incident_id,
    reported_by,
    incident_date,
    incident_count,
    fire_classification,
    fire_cause,
    temperature,
    humidity,
    wind_speed,
    wind_direction,
    notes,
    created_at,
    updated_at
)
SELECT
    barangay_hall_id,
    incident_id,
    reported_by,
    occurred_at::date,
    1,
    CASE
        WHEN seq % 7 = 0 THEN 'intentional'
        WHEN seq % 4 = 0 THEN 'undetermined'
        ELSE 'accidental'
    END,
    CASE
        WHEN seq % 5 = 0 THEN 'Pending BFP validation'
        WHEN seq % 3 = 0 THEN 'Electrical source reported for validation'
        ELSE 'Residential fire report for validation'
    END,
    31 + (seq % 5),
    58 + (seq % 18),
    4 + (seq % 6),
    (ARRAY['NE', 'E', 'SE', 'S', 'SW', 'W', 'NW', 'N'])[1 + (seq % 8)],
    'Manual Supabase seed row linked to an incident and existing barangay hall.',
    occurred_at,
    now()
FROM _inserted_incidents;

INSERT INTO incident_report (
    incident_id,
    marker_id,
    station_id,
    status,
    victims,
    severity,
    "dateEnded",
    created_at,
    updated_at
)
SELECT
    incident_id,
    marker_id,
    station_id,
    'Reported',
    0,
    dispatch_priority,
    NULL,
    occurred_at,
    now()
FROM _inserted_incidents
ON CONFLICT (incident_id) DO NOTHING;

CREATE TEMP TABLE _risk_seed AS
SELECT
    gs.seq,
    u.user_id AS reported_by,
    h.barangay_hall_id,
    h.barangay_id,
    h.barangay_name,
    h.station_id,
    h.hall_lat,
    h.hall_lng,
    round((h.hall_lat::double precision + (0.00062 + ((gs.seq % 4) * 0.00004)) * cos(radians((gs.seq * 61 + 19) % 360)))::numeric, 7) AS lat,
    round((h.hall_lng::double precision + (0.00062 + ((gs.seq % 4) * 0.00004)) * sin(radians((gs.seq * 61 + 19) % 360)))::numeric, 7) AS lng,
    CASE WHEN gs.seq % 2 = 0 THEN 'hazard' ELSE 'risk' END AS entry_type,
    format('[seed:%s] %s record %s - %s', m.run_key, CASE WHEN gs.seq % 2 = 0 THEN 'Hazard' ELSE 'Risk' END, lpad(gs.seq::text, 2, '0'), h.barangay_name) AS marker_title,
    format('Manual seed %s record for %s. Coordinates are offset from the barangay hall marker for map testing.', CASE WHEN gs.seq % 2 = 0 THEN 'hazard' ELSE 'risk' END, h.barangay_name) AS marker_description,
    risk_profile.risk_type,
    hazard_profile.hazard_category,
    COALESCE(risk_profile.risk_factor, hazard_profile.risk_factor) AS risk_factor,
    COALESCE(risk_profile.mode, hazard_profile.mode) AS mode,
    COALESCE(hazard_profile.hazard_type, 'none') AS hazard_type,
    COALESCE(risk_profile.condition_code, hazard_profile.condition_code) AS condition_code,
    COALESCE(risk_profile.condition_label, hazard_profile.condition_label) AS condition_label,
    (ARRAY['House', 'School', 'Market', 'Chapel', 'Sari-sari store', 'Daycare center', 'Covered court', 'Residential block'])[1 + (gs.seq % 8)] AS nearby_establishments,
    (ARRAY['Purok 1', 'Purok 2', 'Purok 3', 'Purok 4'])[1 + (gs.seq % 4)] AS street_or_sitio,
    CASE
        WHEN gs.seq % 3 = 0 THEN 'high'
        WHEN gs.seq % 3 = 1 THEN 'moderate'
        ELSE 'low'
    END AS assessment_level,
    now() - ((gs.seq || ' hours')::interval) AS recorded_at
FROM generate_series(1, 50) AS gs(seq)
CROSS JOIN _seed_meta m
CROSS JOIN (SELECT count(*) AS user_count FROM _role5_users) uc
CROSS JOIN (SELECT count(*) AS hall_count FROM _seed_halls) hc
JOIN _role5_users u ON u.rn = (((gs.seq - 1) % uc.user_count) + 1)
JOIN _seed_halls h ON h.rn = (((gs.seq - 1) % hc.hall_count) + 1)
LEFT JOIN LATERAL (
    SELECT *
    FROM (VALUES
        (1, 'housing_material_risk', 'light_materials', 'Mostly light combustible materials', 'housing_material', 'light_materials'),
        (2, 'housing_spacing_risk', 'dikit_dikit', 'Dikit-dikit structures', 'housing_spacing', 'dikit_dikit'),
        (3, 'population_density_risk', 'high', 'High population density', 'people_density', 'high'),
        (4, 'road_accessibility_risk', 'motorcycle_only', 'Motorcycle or small vehicle only', 'housing_spacing', 'moderate'),
        (5, 'hydrant_coverage_risk', 'hydrant_too_far', 'Hydrant too far from the area', 'housing_spacing', 'moderate'),
        (6, 'combustible_storage_risk', 'heavy_combustible_buildup', 'Heavy combustible buildup', 'housing_material', 'mixed'),
        (7, 'electrical_load_risk', 'octopus_wiring_area', 'Octopus wiring observed in the area', 'electrical_risk', 'octopus')
    ) AS catalog(idx, risk_type, condition_code, condition_label, risk_factor, mode)
    WHERE idx = (((gs.seq - 1) % 7) + 1)
) risk_profile ON gs.seq % 2 <> 0
LEFT JOIN LATERAL (
    SELECT *
    FROM (VALUES
        (1, 'electrical_hazard', 'octopus_wiring', 'Octopus wiring', 'electrical', 'electrical_risk', 'octopus'),
        (2, 'lpg_gas_hazard', 'lpg_tanks_stored_indoors', 'LPG tanks stored indoors', 'flammable', 'housing_material', 'mixed'),
        (3, 'structural_hazard', 'houses_too_close_together', 'Houses built too close together', 'overcrowding', 'housing_spacing', 'dikit_dikit'),
        (4, 'flammable_material_hazard', 'improper_storage_of_gasoline', 'Improper storage of gasoline', 'flammable', 'housing_material', 'mixed'),
        (5, 'cooking_hazard', 'unattended_cooking', 'Unattended cooking', 'flammable', 'housing_material', 'overflow'),
        (6, 'population_exposure_hazard', 'high_population_density', 'High population density', 'overcrowding', 'people_density', 'high'),
        (7, 'fire_protection_deficiency_hazard', 'absence_of_smoke_detectors', 'Absence of smoke detectors', 'flammable', 'wiring', 'none')
    ) AS catalog(idx, hazard_category, condition_code, condition_label, hazard_type, risk_factor, mode)
    WHERE idx = (((gs.seq - 1) % 7) + 1)
) hazard_profile ON gs.seq % 2 = 0;

CREATE TEMP TABLE _inserted_risk_markers AS
WITH inserted AS (
    INSERT INTO markers (title, description, lat, lng, status, category, created_at, updated_at)
    SELECT
        marker_title,
        marker_description,
        lat,
        lng,
        'active',
        entry_type,
        recorded_at,
        now()
    FROM _risk_seed
    RETURNING marker_id, title
)
SELECT
    s.*,
    i.marker_id
FROM _risk_seed s
JOIN inserted i ON i.title = s.marker_title;

INSERT INTO risk_areas (
    marker_id,
    barangay_hall_id,
    station_id,
    entry_type,
    hazard_category,
    risk_type,
    street_or_sitio,
    risk_factor,
    mode,
    condition_code,
    condition_label,
    hazard_type,
    population_density_level,
    building_density_level,
    road_accessibility,
    distance_to_hydrant,
    distance_to_water_source,
    nearby_establishments,
    estimated_affected_households,
    risk_score,
    estimated_radius,
    assessment_level,
    assessment_description,
    remarks,
    near_hydrant,
    near_water_source,
    blocked_hydrant,
    informal_settlement,
    cooking_fire_prone_activity,
    combustible_materials_present,
    poor_waste_disposal,
    previous_fire_prone_location,
    status,
    review_status,
    review_type,
    review_notes,
    reviewed_at,
    reviewed_by,
    patrolled_by,
    reported_by,
    created_at,
    updated_at
)
SELECT
    marker_id,
    barangay_hall_id,
    station_id,
    entry_type,
    hazard_category,
    risk_type,
    street_or_sitio,
    risk_factor,
    mode,
    condition_code,
    condition_label,
    hazard_type,
    CASE WHEN assessment_level = 'high' THEN 'high' WHEN assessment_level = 'moderate' THEN 'moderate' ELSE 'low' END,
    CASE WHEN seq % 4 = 0 THEN 'high' WHEN seq % 4 = 1 THEN 'moderate' ELSE 'low' END,
    CASE WHEN seq % 5 = 0 THEN 'difficult' WHEN seq % 4 = 0 THEN 'moderate' ELSE 'easy' END,
    40 + (seq * 3),
    80 + (seq * 4),
    nearby_establishments,
    5 + (seq % 20),
    CASE WHEN assessment_level = 'high' THEN 78.00 WHEN assessment_level = 'moderate' THEN 54.00 ELSE 28.00 END,
    35 + (seq % 9) * 5,
    assessment_level,
    format('%s identified: %s in %s.', CASE WHEN entry_type = 'hazard' THEN 'Hazard' ELSE 'Risk' END, condition_label, street_or_sitio),
    NULL,
    (seq % 2 = 0),
    (seq % 3 = 0),
    (seq % 10 = 0),
    (seq % 7 = 0),
    (seq % 6 = 0),
    (seq % 5 = 0),
    (seq % 4 = 0),
    (seq % 8 = 0),
    'pending',
    'pending',
    'create',
    'Awaiting chief or administrator verification.',
    NULL,
    NULL,
    NULL,
    reported_by,
    recorded_at,
    now()
FROM _inserted_risk_markers;

-- Correct existing seeded risk/hazard rows. This is safe to run after a previous insert.
-- It normalizes rows above ID 118 to system risk/hazard type and condition selections.
WITH target AS (
    SELECT
        risk_area_id,
        COALESCE(NULLIF(entry_type, ''), 'risk') AS entry_type,
        row_number() OVER (ORDER BY risk_area_id) AS rn
    FROM risk_areas
    WHERE risk_area_id > 118
),
mapped AS (
    SELECT
        target.risk_area_id,
        target.entry_type,
        CASE WHEN target.entry_type = 'hazard' THEN NULL ELSE risk_profile.risk_type END AS risk_type,
        CASE WHEN target.entry_type = 'hazard' THEN hazard_profile.hazard_category ELSE NULL END AS hazard_category,
        COALESCE(risk_profile.risk_factor, hazard_profile.risk_factor) AS risk_factor,
        COALESCE(risk_profile.mode, hazard_profile.mode) AS mode,
        COALESCE(risk_profile.condition_code, hazard_profile.condition_code) AS condition_code,
        COALESCE(risk_profile.condition_label, hazard_profile.condition_label) AS condition_label,
        CASE WHEN target.entry_type = 'hazard' THEN hazard_profile.hazard_type ELSE 'none' END AS hazard_type,
        (ARRAY['House', 'School', 'Market', 'Chapel', 'Sari-sari store', 'Daycare center', 'Covered court', 'Residential block'])[1 + (target.risk_area_id % 8)] AS nearby_establishments,
        (ARRAY['Purok 1', 'Purok 2', 'Purok 3', 'Purok 4'])[1 + (target.risk_area_id % 4)] AS street_or_sitio
    FROM target
    LEFT JOIN LATERAL (
        SELECT *
        FROM (VALUES
            (1, 'housing_material_risk', 'light_materials', 'Mostly light combustible materials', 'housing_material', 'light_materials'),
            (2, 'housing_spacing_risk', 'dikit_dikit', 'Dikit-dikit structures', 'housing_spacing', 'dikit_dikit'),
            (3, 'population_density_risk', 'high', 'High population density', 'people_density', 'high'),
            (4, 'road_accessibility_risk', 'motorcycle_only', 'Motorcycle or small vehicle only', 'housing_spacing', 'moderate'),
            (5, 'hydrant_coverage_risk', 'hydrant_too_far', 'Hydrant too far from the area', 'housing_spacing', 'moderate'),
            (6, 'combustible_storage_risk', 'heavy_combustible_buildup', 'Heavy combustible buildup', 'housing_material', 'mixed'),
            (7, 'electrical_load_risk', 'octopus_wiring_area', 'Octopus wiring observed in the area', 'electrical_risk', 'octopus')
        ) AS catalog(idx, risk_type, condition_code, condition_label, risk_factor, mode)
        WHERE idx = (((target.rn - 1) % 7) + 1)
    ) risk_profile ON target.entry_type <> 'hazard'
    LEFT JOIN LATERAL (
        SELECT *
        FROM (VALUES
            (1, 'electrical_hazard', 'octopus_wiring', 'Octopus wiring', 'electrical', 'electrical_risk', 'octopus'),
            (2, 'lpg_gas_hazard', 'lpg_tanks_stored_indoors', 'LPG tanks stored indoors', 'flammable', 'housing_material', 'mixed'),
            (3, 'structural_hazard', 'houses_too_close_together', 'Houses built too close together', 'overcrowding', 'housing_spacing', 'dikit_dikit'),
            (4, 'flammable_material_hazard', 'improper_storage_of_gasoline', 'Improper storage of gasoline', 'flammable', 'housing_material', 'mixed'),
            (5, 'cooking_hazard', 'unattended_cooking', 'Unattended cooking', 'flammable', 'housing_material', 'overflow'),
            (6, 'population_exposure_hazard', 'high_population_density', 'High population density', 'overcrowding', 'people_density', 'high'),
            (7, 'fire_protection_deficiency_hazard', 'absence_of_smoke_detectors', 'Absence of smoke detectors', 'flammable', 'wiring', 'none')
        ) AS catalog(idx, hazard_category, condition_code, condition_label, hazard_type, risk_factor, mode)
        WHERE idx = (((target.rn - 1) % 7) + 1)
    ) hazard_profile ON target.entry_type = 'hazard'
)
UPDATE risk_areas AS risk_area
SET
    risk_type = mapped.risk_type,
    hazard_category = mapped.hazard_category,
    risk_factor = mapped.risk_factor,
    mode = mapped.mode,
    condition_code = mapped.condition_code,
    condition_label = mapped.condition_label,
    hazard_type = mapped.hazard_type,
    nearby_establishments = mapped.nearby_establishments,
    street_or_sitio = mapped.street_or_sitio,
    assessment_description = format('%s identified: %s in %s.', CASE WHEN mapped.entry_type = 'hazard' THEN 'Hazard' ELSE 'Risk' END, mapped.condition_label, mapped.street_or_sitio),
    remarks = NULL,
    review_notes = 'Awaiting chief or administrator verification.',
    updated_at = now()
FROM mapped
WHERE risk_area.risk_area_id = mapped.risk_area_id;

-- Summary of rows created by this run.
SELECT
    (SELECT run_key FROM _seed_meta) AS run_key,
    (SELECT count(*) FROM _role5_users) AS role5_users_used,
    (SELECT count(DISTINCT barangay_id) FROM _incident_seed) AS incident_barangays_used,
    (SELECT count(*) FROM _inserted_incidents) AS inserted_incidents,
    (SELECT count(*) FROM _inserted_risk_markers) AS inserted_risk_or_hazard_markers;

COMMIT;


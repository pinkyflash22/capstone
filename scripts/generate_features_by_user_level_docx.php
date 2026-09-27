<?php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\SimpleType\Jc;
use PhpOffice\PhpWord\SimpleType\JcTable;
use PhpOffice\PhpWord\Style\Language;
use PhpOffice\PhpWord\Style\TablePosition;
use PhpOffice\PhpWord\IOFactory;

$outputPath = dirname(__DIR__) . '/docs/pyrosense-features-by-user-level.docx';

$phpWord = new PhpWord();
$phpWord->getSettings()->setThemeFontLang(new Language(Language::EN_US));
$phpWord->setDefaultFontName('Aptos');
$phpWord->setDefaultFontSize(10.5);

$phpWord->addTitleStyle(1, [
    'name' => 'Aptos Display',
    'size' => 22,
    'bold' => true,
    'color' => '000000',
], [
    'spaceAfter' => 160,
]);

$phpWord->addTitleStyle(2, [
    'name' => 'Aptos Display',
    'size' => 15,
    'bold' => true,
    'color' => '000000',
], [
    'spaceBefore' => 260,
    'spaceAfter' => 100,
]);

$phpWord->addTitleStyle(3, [
    'name' => 'Aptos',
    'size' => 12,
    'bold' => true,
    'color' => '000000',
], [
    'spaceBefore' => 160,
    'spaceAfter' => 80,
]);

$phpWord->addParagraphStyle('Body', [
    'lineHeight' => 1.16,
    'spaceAfter' => 110,
]);

$phpWord->addParagraphStyle('SmallBody', [
    'lineHeight' => 1.08,
    'spaceAfter' => 50,
]);

$phpWord->addFontStyle('Lead', [
    'bold' => true,
]);

$phpWord->addTableStyle('FeatureTable', [
    'borderColor' => 'D9D9D9',
    'borderSize' => 6,
    'cellMarginTop' => 110,
    'cellMarginRight' => 120,
    'cellMarginBottom' => 110,
    'cellMarginLeft' => 120,
    'alignment' => JcTable::CENTER,
    'layout' => \PhpOffice\PhpWord\Style\Table::LAYOUT_FIXED,
    'position' => [
        'vertAnchor' => TablePosition::VANCHOR_TEXT,
    ],
], [
    'bgColor' => 'F3F6FA',
    'color' => '000000',
    'bold' => true,
]);

$section = $phpWord->addSection([
    'marginTop' => 900,
    'marginRight' => 820,
    'marginBottom' => 900,
    'marginLeft' => 820,
]);

$section->addTitle('PyroSense System Features by User Level', 1);
$section->addText(
    'This document summarizes the major features found in the PyroSense project, the operational problems each feature solves, and the advantage of the system compared with traditional fire response management and manual station processes.',
    [],
    'Body'
);
$section->addText(
    'Main conclusion: PyroSense improves fire response work by connecting citizen reports, barangay hazard evidence, station command decisions, firefighter response activity, investigation documents, predictive analytics, and mitigation follow-up in one role-based system.',
    ['bold' => true],
    'Body'
);

addOverviewTable($section);
addRoleSections($section);
addTraditionalComparison($section);
addClosing($section);

$writer = IOFactory::createWriter($phpWord, 'Word2007');
$writer->save($outputPath);

echo $outputPath . PHP_EOL;

function addOverviewTable($section): void
{
    $section->addTitle('System Overview', 2);
    $section->addText(
        'PyroSense is organized around user responsibility. Public users and citizens report incidents and receive information. Barangay users submit local risk evidence and prevention records. Firefighters respond, update status, and prepare operational reports. Station command and provincial administrators manage resources, analytics, validation, and accountability.',
        [],
        'Body'
    );

    $rows = [
        ['User level', 'Main purpose in the system'],
        ['Public or visitor', 'View public fire safety content, station and hydrant resources, and submit quick or anonymous emergency reports.'],
        ['Citizen', 'Report incidents and risk observations, follow community announcements, manage FSIC requests, and update personal reports.'],
        ['Barangay', 'Record barangay-level hazards, fire reports, checklists, mitigation work, awareness activities, and community requests.'],
        ['Firefighter', 'Receive incidents, update response progress, track station resources, create reports, manage schedules, and coordinate barangay cooperation.'],
        ['Station Admin or Chief', 'Command station operations, analytics, prediction, dispatch, mitigation, personnel, schedule, reports, and community coordination.'],
        ['Provincial Admin', 'Monitor province-level stations, maps, reports, landing content, after-activity review, risk review, and administrative oversight.'],
        ['All authenticated users', 'Use profile management, notifications, activity logging, secure logout, and role-based navigation.'],
    ];

    addTable($section, $rows, [2300, 6500]);
}

function addRoleSections($section): void
{
    $roles = [
        'Public or Visitor Level' => [
            ['Public homepage and PWA quick report', 'People can report emergencies or access basic fire safety entry points without waiting for office processing.', 'The traditional process depends on calls, walk-ins, or informal messages; PyroSense gives an immediate digital entry point.'],
            ['Anonymous incident reporting', 'Emergency information can still be submitted when the reporter has no account or cannot log in.', 'Traditional reporting may lose anonymous or urgent reports; the system captures them with location and incident details.'],
            ['Public map resources', 'Residents can see station, hydrant, and map resource information when exposed publicly.', 'Manual awareness relies on posters or office inquiries; digital maps make resources easier to locate.'],
            ['Registration and account verification', 'Citizens can create verified accounts with address and contact details.', 'Manual registration creates paper records; verified accounts improve traceability and follow-up.'],
        ],
        'Citizen Level' => [
            ['Citizen dashboard and mobile hub', 'Citizens have one place for alerts, incidents, neighborhood information, safety content, events, contacts, FSIC, and profile data.', 'Traditional systems scatter information across bulletin boards, calls, and office visits.'],
            ['Incident reporting and report updates', 'Citizens can submit, update, and track their own fire or emergency reports.', 'Traditional reports may be hard to correct after submission; digital records allow updates with accountability.'],
            ['Risk reports', 'Citizens can submit local safety concerns before they become incidents.', 'Traditional response is mostly reactive; citizen risk reporting supports prevention.'],
            ['Announcements with likes and comments', 'The public can receive station posts and interact with safety updates.', 'Traditional announcements are one-way and often slow to verify engagement.'],
            ['FSIC request management', 'Citizens can create, view, update, delete, claim, and monitor fire safety inspection certificate requests.', 'Traditional FSIC work requires more counter visits and manual status checking.'],
            ['Community activity requests', 'Citizens can request fire safety seminars, drills, or barangay activities.', 'Traditional scheduling relies on letters or personal coordination; the system standardizes requests and approvals.'],
            ['Profile, email, password, and photo updates', 'Citizen identity and contact details stay current for response follow-up.', 'Manual records often become outdated and require office correction.'],
        ],
        'Barangay Level' => [
            ['Barangay dashboard and records', 'Barangay users can monitor local fire records, risk records, checklists, and profile context.', 'Traditional barangay records are separated from station command visibility.'],
            ['Risk map and hazard reporting', 'Barangay users can place risk or hazard markers with conditions, photos, affected households, hydrant and water access details, and review status.', 'Traditional hazard reporting is often narrative or paper-based; map evidence makes location, severity, and verification clearer.'],
            ['Fire reports and quick fire reports', 'Barangay personnel can record fire events and local observations in a structured format.', 'Manual logbooks are harder to aggregate into station analytics and history.'],
            ['Prediction checklists and item review', 'Barangay users can update prevention checklist items, sign off visits, and record follow-through.', 'Traditional prevention tasks may be assigned verbally; the system records completion, exceptions, and review.'],
            ['Mitigation progress updates', 'Barangay users can update assigned mitigation work and submit proof for completion review.', 'Traditional prevention work may close without evidence; PyroSense requires proof and command review.'],
            ['Awareness and patrol modules', 'Barangay users can support preparedness, local monitoring, and community prevention.', 'Traditional coordination is episodic; the portal keeps prevention work visible.'],
            ['Barangay notifications', 'Barangay users receive system notifications about reviews, assignments, and updates.', 'Traditional coordination depends on calls or messenger threads that are difficult to audit.'],
        ],
        'Firefighter Level' => [
            ['Firefighter dashboard', 'Firefighters see active incidents, station context, nearby stations, crew information, and recent posts.', 'Traditional shift awareness depends on radio or verbal briefings; the dashboard gives shared situational visibility.'],
            ['Assigned incident view and registry', 'Firefighters can view assigned incidents, details, reporter data, location, status, route timestamps, and investigation links.', 'Traditional incident folders are slower to search and update.'],
            ['Response actions', 'Firefighters can mark responding, arrived, request backup, complete, false alarm, close, and team ready.', 'Traditional radio-only updates can be missed; digital state changes create an operational trail.'],
            ['Location updates and apparatus telemetry support', 'The system can track firefighter and apparatus locations for station visibility.', 'Traditional dispatch has limited live location awareness.'],
            ['My station view', 'Firefighters can review station overview, personnel, equipment, and reports.', 'Traditional station information is spread across rosters, boards, and manual inventories.'],
            ['Schedule and duty events', 'Firefighters can view duty shifts, official station schedules, and personal or station events.', 'Traditional scheduling is prone to conflict and stale copies.'],
            ['FSIC inspection handling', 'Assigned firefighters can inspect FSIC requests and submit inspection results.', 'Traditional inspection paperwork moves slowly between requester, inspector, and command.'],
            ['Investigation and after-activity reports', 'Firefighters can produce spot, progress, final, and after-activity reports in Word or PDF workflows with review support.', 'Traditional report creation is manual and inconsistent; templates improve completeness and formatting.'],
            ['Barangay cooperation', 'Firefighters can review checklists and update mitigation tasks linked to barangay cooperation.', 'Traditional inter-agency coordination lacks shared task status and proof.'],
            ['Training, certifications, announcements, and profile', 'Firefighters can access professional records and station communications.', 'Traditional personnel files and announcements are harder to maintain and search.'],
        ],
        'Station Admin or Chief Level' => [
            ['Command dashboard and station profile', 'Chief users can see station statistics, active response counts, FSIC claims, crew online, and station details.', 'Traditional command boards are not automatically connected to incidents, teams, and reports.'],
            ['Incident map and dispatch command', 'Command can monitor station-scoped incidents, dispatch options, reassign incidents, and create external responses.', 'Traditional dispatch depends heavily on manual radio coordination; digital dispatch records assignment decisions.'],
            ['Predictive analytics', 'The system uses incidents, historical records, weather, verified risk areas, staffing, equipment, checklists, and mitigation signals to produce barangay risk outlooks.', 'Traditional fire management mainly reacts to past incidents; PyroSense supports prevention and prioritization.'],
            ['Action plan checklists', 'Recommendations become stored, assignable, reviewable checklists with item history and confirmation.', 'Traditional recommendations can remain in reports; the system converts them into tracked tasks.'],
            ['Mitigation management', 'Chief users can create, approve, update, reject, complete, and verify mitigation actions with proof, outcomes, effectiveness, and follow-up.', 'Traditional mitigation closure may lack proof; PyroSense preserves ownership, evidence, and approval.'],
            ['Risk review workflow', 'Chief users can approve, reject, activate, update, or delete barangay-submitted risk records.', 'Traditional field reports may enter planning without validation; the system separates submission from verified evidence.'],
            ['Resource and personnel management', 'Command can manage station resources, teams, personnel, station officers, schedules, equipment, apparatus, hydrants, and assignments.', 'Traditional management uses separate paper or spreadsheet lists; PyroSense centralizes operational data.'],
            ['Reports and executive review', 'Chief users can review investigation and after-activity records and download Word/PDF outputs.', 'Traditional review requires file handoffs; system review keeps comments, signatures, and status in one workflow.'],
            ['Community requests and announcements', 'Command can approve community activity requests and publish station posts.', 'Traditional public coordination is less traceable and slower to approve.'],
            ['Weather context and operational readiness', 'Command can see weather-informed risk context and readiness signals from completed prevention work.', 'Traditional decisions often separate weather, hazards, and crew readiness.'],
        ],
        'Provincial Admin Level' => [
            ['Province dashboard, maps, alerts, and reports', 'Provincial users can oversee stations, incidents, maps, alerts, and reports at a broader level.', 'Traditional provincial monitoring waits for station submissions or manual consolidation.'],
            ['Station analytics access', 'Provincial users can inspect station analytics and risk trends.', 'Traditional comparison across stations requires manual report gathering.'],
            ['Landing content management', 'Admins can manage public landing media and visibility.', 'Traditional public information updates require developer or manual publishing support.'],
            ['After-activity report review', 'Provincial/admin reviewers can review reports and download PDF outputs.', 'Traditional review chains are slower and harder to audit.'],
            ['Risk reviews and mitigation oversight', 'Provincial admin routes include risk review and mitigation access for broader command oversight.', 'Traditional oversight may not see barangay risk evidence until after reports are compiled.'],
            ['Health and readiness status', 'Admin can access system readiness or health status routes.', 'Traditional operations do not usually expose application health to administrators.'],
        ],
        'All Authenticated Users' => [
            ['Role-based access and redirects', 'Users are automatically routed to dashboards that match their role.', 'Traditional systems often rely on shared folders or manual permissions.'],
            ['Notifications', 'Users receive role-specific notifications for assignments, reviews, and operational updates.', 'Traditional notification channels are fragmented and hard to audit.'],
            ['User activity logs', 'Important actions are recorded with actor and context.', 'Traditional paper processes can show the result but not always who changed what and when.'],
            ['Profile and credential management', 'Users can update contact details, email, password, and photos where supported.', 'Traditional profile updates require administrative editing.'],
            ['Secure authentication, OTP, Google verification, throttling, and logout safeguards', 'Access is protected and stale browser sessions are handled more safely.', 'Traditional physical records do not need login security, but digital fire operations require identity assurance.'],
        ],
    ];

    foreach ($roles as $role => $features) {
        $section->addTitle($role, 2);
        $rows = [['Feature', 'Problem it solves', 'Advantage over traditional process']];
        foreach ($features as $feature) {
            $rows[] = $feature;
        }
        addTable($section, $rows, [2300, 3300, 3300]);
    }
}

function addTraditionalComparison($section): void
{
    $section->addTitle('Overall Advantage Compared With Traditional Fire Response Management', 2);
    $rows = [
        ['Traditional limitation', 'PyroSense improvement'],
        ['Reports are often paper-based, delayed, or duplicated across offices.', 'Incident reports, barangay records, investigation reports, FSIC requests, and mitigation actions are stored in connected digital workflows.'],
        ['Dispatch decisions rely mainly on radio calls and manual assignment.', 'Command can view incident context, dispatch options, reassignment, external response, resource status, and response progress.'],
        ['Prevention is usually separated from response operations.', 'Predictive analytics, hazard mapping, action plan checklists, and mitigation tracking connect prevention to daily station work.'],
        ['Barangay observations may not reach BFP command quickly.', 'Barangay risk and hazard submissions enter a review workflow with map location, photos, and verification.'],
        ['Recommendations in reports may not become owned tasks.', 'Recommendations can become checklists and mitigation actions with owners, target dates, proof, outcomes, and review.'],
        ['Report quality depends on individual formatting habits.', 'Word and PDF exports standardize spot, progress, final, and after-activity reporting.'],
        ['Accountability is difficult when tasks are handled through informal messages.', 'Activity logs, notifications, review states, status changes, and proof attachments preserve a clear audit trail.'],
        ['Command has limited consolidated visibility into community requests and preparedness work.', 'Citizen and barangay activity requests, announcements, checklists, and mitigation records are visible in role-scoped dashboards.'],
        ['Planning often depends on past incident counts alone.', 'Risk outlooks combine incidents, weather, historical records, verified field conditions, resource readiness, and mitigation progress.'],
    ];
    addTable($section, $rows, [3300, 5600]);
}

function addClosing($section): void
{
    $section->addTitle('One Sentence Summary', 2);
    $section->addText(
        'PyroSense replaces scattered, reactive, and paper-heavy fire response processes with a role-based digital system that supports faster reporting, clearer dispatch, stronger prevention, verified mitigation, standardized reports, and accountable coordination across citizens, barangays, firefighters, station command, and provincial administration.',
        ['bold' => true],
        'Body'
    );
}

function addTable($section, array $rows, array $widths): void
{
    $table = $section->addTable('FeatureTable');
    foreach ($rows as $rowIndex => $row) {
        $table->addRow();
        foreach ($row as $cellIndex => $text) {
            $cell = $table->addCell($widths[$cellIndex] ?? 3000, [
                'valign' => 'center',
                'bgColor' => $rowIndex === 0 ? 'E9EEF6' : ($rowIndex % 2 === 0 ? 'FAFBFC' : 'FFFFFF'),
            ]);
            $cell->addText(
                $text,
                [
                    'bold' => $rowIndex === 0,
                    'size' => $rowIndex === 0 ? 9.5 : 8.7,
                    'color' => '000000',
                ],
                [
                    'alignment' => $rowIndex === 0 ? Jc::CENTER : Jc::START,
                    'lineHeight' => 1.05,
                    'spaceAfter' => 0,
                ]
            );
        }
    }
    $section->addTextBreak(1);
}

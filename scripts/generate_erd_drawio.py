from __future__ import annotations

import math
import json
import re
import subprocess
from dataclasses import dataclass, field
from pathlib import Path
from typing import Dict, List, Optional, Tuple
from xml.sax.saxutils import escape


ROOT = Path(__file__).resolve().parents[1]
MIGRATIONS_DIR = ROOT / "database" / "migrations"
OUTPUT_FILE = ROOT / "docs" / "capstone-erd.drawio"
COMPLETE_OUTPUT_FILE = ROOT / "docs" / "capstone-erd-all-tables.drawio"
TEMPLATE_FILE = ROOT / "docs" / "full ered.drawio"
LIVE_SCHEMA_EXPORTER = ROOT / "scripts" / "export_public_schema.php"

TEMPLATE_TABLE_ALIASES = {
    "analyses": "analysis",
    "user_location": "user_locations",
}

MANUAL_LAYOUT = {
    "barangay_officers": (-1180, 420, 220),
    "barangay_halls": (-900, 420, 220),
    "barangay_fire_records": (-620, 420, 220),
    "historical_fire_incidents": (-620, 760, 240),
    "barangay_prediction_checklists": (-900, 760, 240),
    "risk_areas": (-1180, 760, 220),
    "ai_action_plan_checklists": (1120, 240, 260),
    "ai_action_plan_checklist_items": (1410, 240, 240),
    "ai_recommendation_suggestions": (1120, 700, 260),
    "event_activities": (-1460, 420, 220),
}

DOMAIN_ORDER = [
    "Identity & Access",
    "Geography & Stations",
    "Incidents & Investigation",
    "Risk & Forecasting",
    "Mitigation & Checklists",
    "Resources & FSIC",
    "Community & Communication",
    "Platform",
]

DOMAIN_COLORS = {
    "Identity & Access": ("#e8f0fe", "#4f6bed"),
    "Geography & Stations": ("#e6f4ea", "#2e7d32"),
    "Incidents & Investigation": ("#fdecea", "#c62828"),
    "Risk & Forecasting": ("#fff4e5", "#ef6c00"),
    "Mitigation & Checklists": ("#f3e8fd", "#7b1fa2"),
    "Resources & FSIC": ("#e0f7fa", "#00838f"),
    "Community & Communication": ("#fff8e1", "#a66b00"),
    "Platform": ("#eceff1", "#546e7a"),
}

DOCUMENTATION_MODULES = {
    "01 Fire Response Management and Requests": {
        "incidents", "incident_report", "incident_contexts", "incident_occurrence_revisions",
        "community_activity_requests", "voice_calls", "teams", "routes", "markers", "stations",
        "station_officers", "station_barangay_coverage", "barangays", "users",
    },
    "02 Incident Investigation and Risk Reviews": {
        "incidents", "fire_origin", "spot_investigation_report", "progress_investigation_report",
        "final_investigation_report", "documents", "images", "inspection_checklist",
        "risk_areas", "risk_area_photos", "incident_contexts", "incident_occurrence_revisions",
        "historical_fire_incidents", "barangay_fire_records", "markers", "users",
    },
    "03 Predictive Analysis": {
        "analysis", "analysis_revision_snapshots", "risk_areas",
        "barangay_prediction_checklists", "historical_fire_incidents", "historical_data",
        "barangay_fire_records", "incident_contexts", "incidents", "barangays", "stations",
    },
    "04 Mitigation and Checklists": {
        "mitigation_actions", "mitigation_action_attachments", "ai_action_plan_checklists",
        "ai_action_plan_checklist_items", "ai_action_plan_checklist_activities",
        "ai_recommendation_suggestions", "recommendation_checklist", "suggestion_checklist",
        "analysis", "analysis_revision_snapshots", "barangay_prediction_checklists",
        "risk_areas", "users", "stations", "barangays",
    },
    "05 Communication and Resources": {
        "posts", "comments", "post_likes", "notifications", "event_activities",
        "community_activity_requests", "voice_calls", "equipments", "equipments_to_stations",
        "teams", "routes", "station_media", "stations", "station_officers", "users",
    },
}


def table_domain(table_name: str) -> str:
    if table_name in {"users", "roles", "user_locations", "user_logs", "otp_requests", "password_reset_tokens"}:
        return "Identity & Access"
    if table_name in {
        "provinces", "cities", "barangays", "barangay_halls", "barangay_officers",
        "boundary_location", "markers", "hydrants", "stations", "station_officers",
        "station_barangay_coverage", "station_media", "full_addresses",
    }:
        return "Geography & Stations"
    if table_name in {
        "incidents", "incident_report", "incident_contexts", "incident_occurrence_revisions",
        "fire_origin", "spot_investigation_report", "progress_investigation_report",
        "final_investigation_report", "documents", "images", "historical_fire_incidents",
        "historical_data", "barangay_fire_records",
    }:
        return "Incidents & Investigation"
    if table_name in {
        "analysis", "analysis_revision_snapshots", "risk_areas", "risk_area_photos",
        "barangay_prediction_checklists",
    }:
        return "Risk & Forecasting"
    if table_name.startswith("ai_") or table_name in {
        "mitigation_actions", "mitigation_action_attachments", "recommendation_checklist",
        "suggestion_checklist",
    }:
        return "Mitigation & Checklists"
    if table_name in {
        "equipments", "equipments_to_stations", "teams", "routes", "fsic_requests",
        "fsic_inspections", "fsic_approvals", "inspection_checklist",
    }:
        return "Resources & FSIC"
    if table_name in {
        "posts", "comments", "post_likes", "notifications", "event_activities",
        "community_activity_requests", "voice_calls",
    }:
        return "Community & Communication"
    return "Platform"


def humanize_table(table_name: str) -> str:
    label = table_name.replace("_", " ")
    irregular = {
        "analysis": "analysis",
        "cities": "city",
        "provinces": "province",
        "barangays": "barangay",
        "roles": "role",
        "users": "user",
        "incidents": "incident",
        "stations": "station",
        "activities": "activity",
    }
    if table_name in irregular:
        return irregular[table_name]
    if label.endswith("ies"):
        return label[:-3] + "y"
    if label.endswith("s") and not label.endswith("ss"):
        return label[:-1]
    return label


def relationship_label(source_table: str, fk: ForeignKey) -> str:
    subject = humanize_table(source_table)
    target = humanize_table(fk.target_table)
    verbs = {
        "assigned_to": "is assigned to",
        "approved_by": "is approved by",
        "completed_by": "is completed by",
        "confirmed_by": "is confirmed by",
        "created_by": "is created by",
        "deleted_by": "is deleted by",
        "investigator_id": "is investigated by",
        "reported_by": "is reported by",
        "reviewed_by": "is reviewed by",
        "updated_by": "is updated by",
        "user_id": "belongs to",
        "station_id": "belongs to",
        "barangay_id": "belongs to",
        "city_id": "belongs to",
        "province_id": "belongs to",
        "incident_id": "describes",
        "marker_id": "is located by",
        "team_id": "is handled by",
        "analysis_id": "derives from",
        "mitigation_action_id": "supports",
        "checklist_id": "belongs to",
    }
    verb = verbs.get(fk.column, "references")
    return f"{subject} {verb} {target}"


@dataclass
class Column:
    name: str
    type_name: str
    nullable: bool = False
    is_primary: bool = False
    is_foreign: bool = False
    notes: List[str] = field(default_factory=list)


@dataclass
class ForeignKey:
    column: str
    target_table: str
    target_column: Optional[str] = None


@dataclass
class Table:
    name: str
    columns: Dict[str, Column] = field(default_factory=dict)
    foreign_keys: List[ForeignKey] = field(default_factory=list)
    order: int = 0

    def ensure_column(self, name: str, type_name: str = "column") -> Column:
        column = self.columns.get(name)
        if column is None:
            column = Column(name=name, type_name=type_name)
            self.columns[name] = column
        elif column.type_name == "column" and type_name != "column":
            column.type_name = type_name
        return column


def normalized_name(name: str) -> str:
    return name.strip().lower()


TABLE_CREATE_RE = re.compile(
    r"Schema::create\(\s*'([^']+)'\s*,\s*function\s*\(Blueprint\s+\$table\)\s*\{(.*?)\n\s*\}\s*\);",
    re.S,
)
TABLE_ALTER_RE = re.compile(
    r"Schema::table\(\s*'([^']+)'\s*,\s*function\s*\(Blueprint\s+\$table\)\s*(?:use\s*\([^)]*\)\s*)?\{(.*?)\n\s*\}\s*\);",
    re.S,
)
CHAIN_START_RE = re.compile(r"\$table\s*->\s*([A-Za-z_][A-Za-z0-9_]*)\s*\(")
STRING_ARG_RE = re.compile(r"'([^']+)'")


def strip_comments(text: str) -> str:
    text = re.sub(r"/\*.*?\*/", "", text, flags=re.S)
    cleaned: List[str] = []
    for line in text.splitlines():
        if "//" in line:
            line = line.split("//", 1)[0]
        cleaned.append(line)
    return "\n".join(cleaned)


def split_table_chains(block: str) -> List[str]:
    lines = block.splitlines()
    chains: List[str] = []
    current: List[str] = []
    collecting = False

    for raw_line in lines:
        line = raw_line.strip()
        if not line:
            continue
        if "$table" in line and "->" in line and not collecting:
            current = [line]
            collecting = True
            if ";" in line:
                chains.append(" ".join(current))
                current = []
                collecting = False
            continue
        if collecting:
            current.append(line)
            if ";" in line:
                chains.append(" ".join(current))
                current = []
                collecting = False

    return chains


def normalize_table(name: str) -> str:
    if name == "incident":
        return "incidents"
    return name


def normalize_type(method: str, args: List[str]) -> Tuple[str, Optional[str]]:
    if method == "id":
        return "id", args[0] if args else "id"
    if method == "foreignId":
        return "foreignId", args[0] if args else None
    if method in {"string", "text", "longText", "integer", "unsignedBigInteger", "unsignedInteger",
                  "boolean", "json", "enum", "date", "timestamp", "timestamps", "decimal",
                  "float", "double", "year", "dateTime", "rememberToken"}:
        return method, args[0] if args else None
    return method, args[0] if args else None


def parse_column_chain(chain: str, table: Table) -> None:
    start = CHAIN_START_RE.search(chain)
    if not start:
        return

    method = start.group(1)
    args = STRING_ARG_RE.findall(chain)
    type_name, col_name = normalize_type(method, args)

    if method == "timestamps":
        table.ensure_column("created_at", "timestamp")
        table.ensure_column("updated_at", "timestamp")
        return
    if method == "rememberToken":
        table.ensure_column("remember_token", "string").nullable = True
        return
    if col_name is None:
        return

    column = table.ensure_column(col_name, type_name)
    if method == "id":
        column.is_primary = True
    if method == "foreignId":
        column.is_foreign = True

    if "->nullable()" in chain or "->nullOnDelete()" in chain:
        column.nullable = True
    if "->unique()" in chain:
        column.notes.append("unique")
    if "->default(" in chain:
        column.notes.append("default")

    constrained_match = re.search(
        r"->constrained\(\s*'([^']+)'\s*(?:,\s*'([^']+)')?\s*\)",
        chain,
    )
    if constrained_match:
        target_table = normalize_table(constrained_match.group(1))
        target_column = constrained_match.group(2)
        table.foreign_keys.append(ForeignKey(col_name, target_table, target_column))
        column.is_foreign = True


def parse_foreign_chain(chain: str, table: Table) -> None:
    foreign_match = re.search(r"->foreign\(\s*'([^']+)'", chain)
    if not foreign_match:
        return
    column_name = foreign_match.group(1)
    target_col_match = re.search(r"->references\(\s*'([^']+)'\s*\)", chain)
    target_table_match = re.search(r"->on\(\s*'([^']+)'\s*\)", chain)
    if not target_table_match:
        return

    target_table = normalize_table(target_table_match.group(1))
    target_column = target_col_match.group(1) if target_col_match else None
    table.ensure_column(column_name, "foreignId").is_foreign = True
    table.foreign_keys.append(ForeignKey(column_name, target_table, target_column))


def parse_block(block: str, table: Table) -> None:
    for chain in split_table_chains(strip_comments(block)):
        if "->foreign(" in chain:
            parse_foreign_chain(chain, table)
        else:
            parse_column_chain(chain, table)


def load_schema() -> Dict[str, Table]:
    tables: Dict[str, Table] = {}
    migration_files = sorted(MIGRATIONS_DIR.glob("*.php"))

    for index, path in enumerate(migration_files):
        content = path.read_text(encoding="utf-8", errors="ignore")

        for name, block in TABLE_CREATE_RE.findall(content):
            table_name = normalize_table(name)
            table = tables.get(table_name)
            if table is None:
                table = Table(name=table_name, order=index)
                tables[table_name] = table
            parse_block(block, table)

        for name, block in TABLE_ALTER_RE.findall(content):
            table_name = normalize_table(name)
            table = tables.get(table_name)
            if table is None:
                table = Table(name=table_name, order=index)
                tables[table_name] = table
            parse_block(block, table)

    return tables


def load_live_schema() -> Dict[str, Table]:
    """Read the authoritative public schema from the configured PostgreSQL database."""
    result = subprocess.run(
        ["php", str(LIVE_SCHEMA_EXPORTER)],
        cwd=ROOT,
        capture_output=True,
        text=True,
        check=True,
    )
    payload = json.loads(result.stdout)
    tables: Dict[str, Table] = {}

    for item in payload["columns"]:
        table_name = normalize_table(item["table_name"])
        table = tables.setdefault(table_name, Table(name=table_name))
        data_type = item.get("data_type") or item.get("udt_name") or "column"
        if data_type == "USER-DEFINED":
            data_type = item.get("udt_name") or data_type
        column = table.ensure_column(item["column_name"], data_type)
        column.nullable = item.get("is_nullable") == "YES"
        column.is_primary = bool(item.get("is_primary"))
        if item.get("column_default") is not None:
            column.notes.append("default")

    for item in payload["foreign_keys"]:
        table_name = normalize_table(item["table_name"])
        target_table = normalize_table(item["target_table"])
        if table_name not in tables or target_table not in tables:
            continue
        column_name = item["column_name"]
        column = tables[table_name].ensure_column(column_name, "foreignId")
        column.is_foreign = True
        tables[table_name].foreign_keys.append(
            ForeignKey(column_name, target_table, item.get("target_column"))
        )

    for index, table_name in enumerate(sorted(tables)):
        tables[table_name].order = index
    return tables


def ordered_columns(table: Table) -> List[Column]:
    return sorted(
        table.columns.values(),
        key=lambda col: (
            0 if col.is_primary else 1 if col.is_foreign else 2,
            col.name,
        ),
    )


def foreign_key_labels(table: Table) -> Dict[str, str]:
    labels: Dict[str, str] = {}
    index = 1
    for column in ordered_columns(table):
        if column.is_foreign:
            labels[column.name] = f"FK{index}"
            index += 1
    return labels


def column_indicator(column: Column, fk_labels: Dict[str, str]) -> str:
    if column.is_primary:
        return "PK"
    if column.is_foreign:
        return fk_labels.get(column.name, "FK")
    return ""


def column_value(column: Column) -> str:
    return (
        "&lt;span style=&quot;font-family: sans-serif; font-size: 13.12px; "
        "text-wrap-mode: nowrap; background-color: rgb(255, 255, 255);&quot;&gt;"
        f"{escape(column.name)}"
        "&lt;/span&gt;"
    )


def column_type_value(column: Column) -> str:
    suffix = " ?" if column.nullable else ""
    return escape(f"{column.type_name}{suffix}")


def load_template_layout() -> Dict[str, Tuple[int, int, int]]:
    if not TEMPLATE_FILE.exists():
        return {}

    text = TEMPLATE_FILE.read_text(encoding="utf-8", errors="ignore")
    pattern = re.compile(
        r'<mxCell id="[^"]+" parent="1" style="shape=table;[^"]*" value="([^"]*)" vertex="1">\s*'
        r'<mxGeometry height="([^"]+)" width="([^"]+)" x="([^"]+)" y="([^"]+)" as="geometry"',
        re.S,
    )

    layout: Dict[str, Tuple[int, int, int]] = {}
    for match in pattern.finditer(text):
        raw_name = match.group(1).strip()
        if not raw_name:
            continue
        table_name = TEMPLATE_TABLE_ALIASES.get(raw_name, raw_name)
        table_name = normalized_name(table_name)
        width = int(round(float(match.group(3))))
        x = int(round(float(match.group(4))))
        y = int(round(float(match.group(5))))
        layout[table_name] = (x, y, width)
    return layout


def row_relative_y(table: Table, column_name: str, header_height: int, row_height: int) -> float:
    cols = ordered_columns(table)
    for index, column in enumerate(cols):
        if column.name == column_name:
            center = header_height + (index * row_height) + (row_height / 2)
            total = header_height + len(cols) * row_height
            return round(center / total, 4)
    return 0.5


def find_fk(table: Table, column_name: str) -> Optional[ForeignKey]:
    for fk in table.foreign_keys:
        if fk.column == column_name:
            return fk
    return None


def distribute_positions(total: int) -> List[float]:
    if total <= 1:
        return [0.5]
    step = 0.7 / (total - 1)
    return [round(0.15 + (i * step), 4) for i in range(total)]


def build_target_entry_map(tables: Dict[str, Table]) -> Dict[Tuple[str, str], float]:
    incoming: Dict[str, List[str]] = {}
    for table in tables.values():
        for column in ordered_columns(table):
            fk = find_fk(table, column.name)
            if fk and fk.target_table in tables:
                incoming.setdefault(fk.target_table, []).append(column.name)

    entry_map: Dict[Tuple[str, str], float] = {}
    for target_table, source_columns in incoming.items():
        for column_name, pos in zip(source_columns, distribute_positions(len(source_columns))):
            entry_map[(target_table, column_name)] = pos
    return entry_map


def build_layout(
    ordered_tables: List[Table],
    header_height: int,
    row_height: int,
) -> Tuple[Dict[str, Tuple[int, int, int]], Dict[str, Tuple[int, int, int, int]]]:
    """Pack tables into domain panels without allowing table rectangles to overlap."""
    table_width = 320
    table_gap_x = 70
    table_gap_y = 55
    panel_padding_x = 35
    panel_header = 70
    panel_bottom = 35
    panels_per_row = 2
    panel_gap_x = 100
    panel_gap_y = 110
    panel_width = (panel_padding_x * 2) + (table_width * 2) + table_gap_x

    grouped: Dict[str, List[Table]] = {domain: [] for domain in DOMAIN_ORDER}
    for table in ordered_tables:
        grouped[table_domain(table.name)].append(table)

    active_domains = [domain for domain in DOMAIN_ORDER if grouped[domain]]
    packed: Dict[str, Tuple[List[List[Table]], int]] = {}
    for domain in active_domains:
        columns: List[List[Table]] = [[], []]
        heights = [0, 0]
        domain_tables = sorted(
            grouped[domain],
            key=lambda table: (-(header_height + len(ordered_columns(table)) * row_height), table.name),
        )
        for table in domain_tables:
            index = 0 if heights[0] <= heights[1] else 1
            columns[index].append(table)
            heights[index] += header_height + len(ordered_columns(table)) * row_height + table_gap_y
        content_height = max(heights) - (table_gap_y if domain_tables else 0)
        packed[domain] = (columns, panel_header + content_height + panel_bottom)

    layout: Dict[str, Tuple[int, int, int]] = {}
    panels: Dict[str, Tuple[int, int, int, int]] = {}
    cursor_y = 100
    for row_start in range(0, len(active_domains), panels_per_row):
        row_domains = active_domains[row_start:row_start + panels_per_row]
        row_height_px = max(packed[domain][1] for domain in row_domains)
        for local_index, domain in enumerate(row_domains):
            panel_x = 100 + local_index * (panel_width + panel_gap_x)
            panels[domain] = (panel_x, cursor_y, panel_width, row_height_px)
            columns, _ = packed[domain]
            for column_index, column_tables in enumerate(columns):
                table_x = panel_x + panel_padding_x + column_index * (table_width + table_gap_x)
                table_y = cursor_y + panel_header
                for table in column_tables:
                    layout[table.name] = (table_x, table_y, table_width)
                    table_y += header_height + len(ordered_columns(table)) * row_height + table_gap_y
        cursor_y += row_height_px + panel_gap_y
    return layout, panels


def drawio_diagram(tables: Dict[str, Table], diagram_name: str, diagram_id: str) -> str:
    ordered_tables = sorted(tables.values(), key=lambda item: (item.order, item.name))
    header_height = 32
    row_height = 24
    full_layout, domain_panels = build_layout(ordered_tables, header_height, row_height)
    target_entry_map = build_target_entry_map(tables)

    root_cells = [
        '<mxCell id="0"/>',
        '<mxCell id="1" parent="0"/>',
    ]
    positions: Dict[str, str] = {}
    geometries: Dict[str, Tuple[int, int, int, int]] = {}
    table_cells: List[str] = []
    panel_cells: List[str] = []
    edge_cells: List[str] = []
    id_counter = 1

    def next_id() -> str:
        nonlocal id_counter
        value = f"erd-{id_counter}"
        id_counter += 1
        return value

    min_x = 0
    min_y = 0
    max_x = 0
    max_y = 0

    for domain, (panel_x, panel_y, panel_width, panel_height) in domain_panels.items():
        panel_id = next_id()
        fill_color, stroke_color = DOMAIN_COLORS[domain]
        panel_cells.append(
            f'<mxCell id="{panel_id}" parent="1" style="rounded=1;whiteSpace=wrap;html=1;verticalAlign=top;align=left;spacingTop=14;spacingLeft=18;fontSize=18;fontStyle=1;fillColor={fill_color};strokeColor={stroke_color};strokeWidth=2;opacity=35;" value="{escape(domain)}" vertex="1"><mxGeometry x="{panel_x}" y="{panel_y}" width="{panel_width}" height="{panel_height}" as="geometry" /></mxCell>'
        )
        min_x = min(min_x, panel_x)
        min_y = min(min_y, panel_y)
        max_x = max(max_x, panel_x + panel_width)
        max_y = max(max_y, panel_y + panel_height)

    for table in ordered_tables:
        cols = ordered_columns(table)
        height = header_height + len(cols) * row_height
        x, preferred_y, box_width = full_layout[table.name]
        y = preferred_y
        cell_id = next_id()
        positions[table.name] = cell_id
        geometries[table.name] = (x, y, box_width, height)
        fk_labels = foreign_key_labels(table)
        min_x = min(min_x, x)
        min_y = min(min_y, y)
        max_x = max(max_x, x + box_width)
        max_y = max(max_y, y + height)

        fill_color, stroke_color = DOMAIN_COLORS[table_domain(table.name)]
        table_cells.append(
            f'<mxCell id="{cell_id}" parent="1" style="shape=table;startSize={header_height};container=1;collapsible=1;childLayout=tableLayout;fixedRows=1;rowLines=0;fontStyle=1;align=center;resizeLast=1;html=1;fillColor={fill_color};strokeColor={stroke_color};strokeWidth=2;" value="{escape(table.name)}" vertex="1"><mxGeometry height="{height}" width="{box_width}" x="{x}" y="{y}" as="geometry" /></mxCell>'
        )

        for row_index, column in enumerate(cols):
            row_id = next_id()
            indicator_id = next_id()
            value_id = next_id()
            bottom_flag = 1 if row_index == 0 else 0
            font_style = 5 if column.is_primary else 0
            y_offset = header_height + row_index * row_height
            indicator = column_indicator(column, fk_labels)
            table_cells.append(
                f'<mxCell id="{row_id}" parent="{cell_id}" style="shape=tableRow;horizontal=0;startSize=0;swimlaneHead=0;swimlaneBody=0;fillColor=none;collapsible=0;dropTarget=0;points=[[0,0.5],[1,0.5]];portConstraint=eastwest;top=0;left=0;right=0;bottom={bottom_flag};strokeColor=#000000;" value="" vertex="1"><mxGeometry height="{row_height}" width="{box_width}" y="{y_offset}" as="geometry" /></mxCell>'
            )
            table_cells.append(
                f'<mxCell id="{indicator_id}" parent="{row_id}" style="shape=partialRectangle;connectable=0;fillColor=none;top=0;left=0;bottom=0;right=1;fontStyle=1;fontSize=10;overflow=hidden;whiteSpace=wrap;html=1;strokeColor=#b0bec5;" value="{indicator}" vertex="1"><mxGeometry height="{row_height}" width="38" as="geometry"><mxRectangle height="{row_height}" width="38" as="alternateBounds" /></mxGeometry></mxCell>'
            )
            table_cells.append(
                f'<mxCell id="{value_id}" parent="{row_id}" style="shape=partialRectangle;connectable=0;fillColor=none;top=0;left=0;bottom=0;right=0;align=left;spacingLeft=8;fontStyle={font_style};fontSize=11;overflow=hidden;whiteSpace=wrap;html=1;strokeColor=#b0bec5;" value="{column_value(column)}" vertex="1"><mxGeometry height="{row_height}" width="{box_width - 38}" x="38" as="geometry"><mxRectangle height="{row_height}" width="{box_width - 38}" as="alternateBounds" /></mxGeometry></mxCell>'
            )

    seen_edges = set()
    for table in ordered_tables:
        for fk in table.foreign_keys:
            if fk.target_table not in positions:
                continue
            edge_key = (table.name, fk.column, fk.target_table, fk.target_column or "")
            if edge_key in seen_edges:
                continue
            seen_edges.add(edge_key)
            edge_cell_id = next_id()
            label_id = next_id()
            source_x, source_y, source_w, source_h = geometries[table.name]
            target_x, target_y, target_w, target_h = geometries[fk.target_table]
            exit_y = row_relative_y(table, fk.column, header_height, row_height)
            entry_y = target_entry_map.get((fk.target_table, fk.column), 0.5)
            source_center_y = source_y + (source_h * exit_y)
            target_center_y = target_y + (target_h * entry_y)
            if source_x <= target_x:
                exit_x = 1
                entry_x = 0
                mid_x = int(round(source_x + source_w + max(40, (target_x - (source_x + source_w)) / 2)))
            else:
                exit_x = 0
                entry_x = 1
                mid_x = int(round(target_x + target_w + max(40, (source_x - (target_x + target_w)) / 2)))
            source_center_y = int(round(source_center_y))
            target_center_y = int(round(target_center_y))
            edge_cells.append(
                f'<mxCell id="{edge_cell_id}" edge="1" parent="1" source="{positions[table.name]}" style="edgeStyle=orthogonalEdgeStyle;rounded=1;orthogonalLoop=1;jettySize=auto;html=1;jumpStyle=arc;jumpSize=9;strokeColor=#546e7a;strokeWidth=1;startArrow=ERmany;startFill=0;endArrow=ERone;endFill=0;exitX={exit_x};exitY={exit_y};exitDx=0;exitDy=0;entryX={entry_x};entryY={entry_y};entryDx=0;entryDy=0;" target="{positions[fk.target_table]}"><mxGeometry relative="1" as="geometry"><Array as="points"><mxPoint x="{mid_x}" y="{source_center_y}" /><mxPoint x="{mid_x}" y="{target_center_y}" /></Array></mxGeometry></mxCell>'
            )
            edge_cells.append(
                f'<mxCell id="{label_id}" connectable="0" parent="{edge_cell_id}" style="edgeLabel;html=1;align=center;verticalAlign=middle;resizable=0;points=[];labelBackgroundColor=#ffffff;labelBorderColor=#cfd8dc;fontSize=10;spacing=3;" value="{escape(relationship_label(table.name, fk))}" vertex="1"><mxGeometry relative="1" x="0" y="-10" as="geometry"><mxPoint as="offset" /></mxGeometry></mxCell>'
            )

    width = max(1920, (max_x - min_x) + 400)
    height = max(1080, (max_y - min_y) + 400)
    dx = width
    dy = height

    body = "\n      ".join(root_cells + panel_cells + edge_cells + table_cells)
    return (
        f'  <diagram id="{escape(diagram_id)}" name="{escape(diagram_name)}">\n'
        f'    <mxGraphModel dx="{dx}" dy="{dy}" grid="1" gridSize="10" guides="1" tooltips="1" connect="1" arrows="1" fold="1" page="1" pageScale="1" pageWidth="1920" pageHeight="1080" math="0" shadow="0">\n'
        '      <root>\n'
        f'      {body}\n'
        '      </root>\n'
        '    </mxGraphModel>\n'
        '  </diagram>\n'
    )


def module_tables(all_tables: Dict[str, Table], requested: set[str]) -> Dict[str, Table]:
    """Include the module tables and their direct referenced dependencies."""
    included = {name for name in requested if name in all_tables}
    for name in list(included):
        included.update(
            fk.target_table
            for fk in all_tables[name].foreign_keys
            if fk.target_table in all_tables
        )
    return {name: all_tables[name] for name in sorted(included)}


def overview_diagram() -> str:
    features = [
        ("identity", "Identity & Access", 120, 100, "Authenticates and authorizes system users"),
        ("coverage", "Station & Coverage", 520, 100, "Defines operational barangay jurisdiction"),
        ("community", "Community Reporting", 120, 350, "Receives citizen risk and incident reports"),
        ("response", "Incident Response", 520, 350, "Dispatches teams and manages incidents"),
        ("investigation", "Fire Investigation", 920, 350, "Verifies causes, occurrence, and evidence"),
        ("forecast", "Risk Forecasting", 920, 600, "Analyzes history, context, and current risk"),
        ("mitigation", "Mitigation & Checklists", 520, 600, "Turns forecasts into preventive action plans"),
        ("resources", "Resources & FSIC", 120, 600, "Supports operations, inspections, and readiness"),
    ]
    relations = [
        ("identity", "coverage", "identity controls access to station coverage"),
        ("coverage", "response", "station coverage scopes incident response"),
        ("community", "response", "community reporting initiates incident response"),
        ("response", "investigation", "incident response initiates fire investigation"),
        ("investigation", "forecast", "fire investigation supplies evidence to forecasting"),
        ("response", "forecast", "incident response supplies current incidents to forecasting"),
        ("forecast", "mitigation", "risk forecasting produces priorities for mitigation"),
        ("mitigation", "resources", "mitigation coordinates work with resources"),
        ("resources", "response", "resources support incident response"),
    ]
    cells = ['<mxCell id="0"/>', '<mxCell id="1" parent="0"/>']
    positions = {feature[0]: feature for feature in features}
    for feature_id, title, x, y, description in features:
        cells.append(
            f'<mxCell id="overview-{feature_id}" parent="1" style="rounded=1;whiteSpace=wrap;html=1;align=center;verticalAlign=middle;fontSize=18;fontStyle=1;fillColor=#fff4e5;strokeColor=#ef6c00;strokeWidth=2;spacing=12;" value="{escape(title)}&lt;br&gt;&lt;font style=&quot;font-size:12px;font-weight:normal;color:#607d8b&quot;&gt;{escape(description)}&lt;/font&gt;" vertex="1"><mxGeometry x="{x}" y="{y}" width="300" height="120" as="geometry" /></mxCell>'
        )
    for index, (source, target, label) in enumerate(relations, start=1):
        cells.append(
            f'<mxCell id="overview-edge-{index}" edge="1" parent="1" source="overview-{source}" target="overview-{target}" style="edgeStyle=orthogonalEdgeStyle;rounded=1;orthogonalLoop=1;jettySize=auto;html=1;jumpStyle=arc;strokeColor=#546e7a;strokeWidth=2;endArrow=block;endFill=1;"><mxGeometry relative="1" as="geometry" /></mxCell>'
        )
        cells.append(
            f'<mxCell id="overview-label-{index}" connectable="0" parent="overview-edge-{index}" style="edgeLabel;html=1;align=center;verticalAlign=middle;resizable=0;points=[];labelBackgroundColor=#ffffff;labelBorderColor=#cfd8dc;fontSize=11;spacing=4;" value="{escape(label)}" vertex="1"><mxGeometry relative="1" x="0" y="-10" as="geometry"><mxPoint as="offset" /></mxGeometry></mxCell>'
        )
    body = "\n      ".join(cells)
    return (
        '  <diagram id="system-overview" name="00 System Overview">\n'
        '    <mxGraphModel dx="1600" dy="1000" grid="1" gridSize="10" guides="1" tooltips="1" connect="1" arrows="1" fold="1" page="1" pageScale="1" pageWidth="1600" pageHeight="1000" math="0" shadow="0">\n'
        '      <root>\n'
        f'      {body}\n'
        '      </root>\n'
        '    </mxGraphModel>\n'
        '  </diagram>\n'
    )


def drawio_xml(tables: Dict[str, Table]) -> str:
    diagrams = []
    for index, (module_name, requested) in enumerate(DOCUMENTATION_MODULES.items(), start=1):
        subset = module_tables(tables, requested)
        diagrams.append(drawio_diagram(subset, module_name, f"module-{index}"))
    return (
        '<?xml version="1.0" encoding="UTF-8"?>\n'
        '<mxfile host="app.diagrams.net" modified="2026-08-16T00:00:00.000Z" agent="Codex" version="24.7.17">\n'
        + "".join(diagrams)
        + '</mxfile>\n'
    )


def complete_drawio_xml(tables: Dict[str, Table]) -> str:
    return (
        '<?xml version="1.0" encoding="UTF-8"?>\n'
        '<mxfile host="app.diagrams.net" modified="2026-08-16T00:00:00.000Z" agent="Codex" version="24.7.17">\n'
        + drawio_diagram(tables, "All Tables and Relationships", "all-tables-schema")
        + '</mxfile>\n'
    )


def main() -> None:
    try:
        tables = load_live_schema()
        source = "live PostgreSQL public schema"
    except (subprocess.SubprocessError, json.JSONDecodeError, OSError) as error:
        print(f"Live schema unavailable ({error}); falling back to migrations.")
        tables = load_schema()
        source = "Laravel migrations"
    OUTPUT_FILE.parent.mkdir(parents=True, exist_ok=True)
    OUTPUT_FILE.write_text(drawio_xml(tables), encoding="utf-8")
    COMPLETE_OUTPUT_FILE.write_text(complete_drawio_xml(tables), encoding="utf-8")
    print(f"Wrote {OUTPUT_FILE}")
    print(f"Wrote {COMPLETE_OUTPUT_FILE}")
    print(f"Source: {source}")
    print(f"Tables: {len(tables)}")
    print(f"Relationships: {sum(len(table.foreign_keys) for table in tables.values())}")


if __name__ == "__main__":
    main()

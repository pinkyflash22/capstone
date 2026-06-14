from __future__ import annotations

import math
import re
from dataclasses import dataclass, field
from pathlib import Path
from typing import Dict, List, Optional, Tuple
from xml.sax.saxutils import escape


ROOT = Path(__file__).resolve().parents[1]
MIGRATIONS_DIR = ROOT / "database" / "migrations"
OUTPUT_FILE = ROOT / "docs" / "capstone-erd.drawio"
TEMPLATE_FILE = ROOT / "docs" / "full ered.drawio"

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
    template_layout: Dict[str, Tuple[int, int, int]],
) -> Dict[str, Tuple[int, int, int]]:
    layout = dict(template_layout)
    for table_name, geometry in MANUAL_LAYOUT.items():
        layout[table_name] = geometry
    unresolved = [table for table in ordered_tables if normalized_name(table.name) not in layout]
    placed = True
    while unresolved and placed:
        placed = False
        remaining: List[Table] = []
        for table in unresolved:
            related_points: List[Tuple[int, int]] = []
            for fk in table.foreign_keys:
                key = normalized_name(fk.target_table)
                if key in layout:
                    x, y, w = layout[key]
                    related_points.append((x, y))
            for other in ordered_tables:
                for fk in other.foreign_keys:
                    if normalized_name(fk.target_table) == normalized_name(table.name):
                        key = normalized_name(other.name)
                        if key in layout:
                            x, y, w = layout[key]
                            related_points.append((x, y))
            if not related_points:
                remaining.append(table)
                continue
            avg_x = int(sum(p[0] for p in related_points) / len(related_points))
            avg_y = int(sum(p[1] for p in related_points) / len(related_points))
            offset_x = 320 if len(related_points) % 2 else -320
            offset_y = 120 * ((len(related_points) % 3) - 1)
            layout[normalized_name(table.name)] = (avg_x + offset_x, avg_y + offset_y, 220)
            placed = True
        unresolved = remaining

    fallback_x = 1200
    fallback_y = -1700
    for index, table in enumerate(unresolved):
        col = index % 3
        row = index // 3
        layout[normalized_name(table.name)] = (
            fallback_x + (col * 320),
            fallback_y + (row * 180),
            220,
        )
    return layout


def drawio_xml(tables: Dict[str, Table]) -> str:
    ordered_tables = sorted(tables.values(), key=lambda item: (item.order, item.name))
    header_height = 30
    row_height = 30
    template_layout = load_template_layout()
    full_layout = build_layout(ordered_tables, template_layout)
    target_entry_map = build_target_entry_map(tables)

    root_cells = [
        '<mxCell id="0"/>',
        '<mxCell id="1" parent="0"/>',
    ]
    positions: Dict[str, str] = {}
    geometries: Dict[str, Tuple[int, int, int, int]] = {}
    table_cells: List[str] = []
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

    for table in ordered_tables:
        cols = ordered_columns(table)
        height = header_height + len(cols) * row_height
        x, preferred_y, box_width = full_layout.get(normalized_name(table.name), (1200, -1700, 220))
        y = preferred_y
        cell_id = next_id()
        positions[table.name] = cell_id
        geometries[table.name] = (x, y, box_width, height)
        fk_labels = foreign_key_labels(table)
        min_x = min(min_x, x)
        min_y = min(min_y, y)
        max_x = max(max_x, x + box_width)
        max_y = max(max_y, y + height)

        table_cells.append(
            f'<mxCell id="{cell_id}" parent="1" style="shape=table;startSize=30;container=1;collapsible=1;childLayout=tableLayout;fixedRows=1;rowLines=0;fontStyle=1;align=center;resizeLast=1;html=1;strokeColor=#000000;" value="{escape(table.name)}" vertex="1"><mxGeometry height="{height}" width="{box_width}" x="{x}" y="{y}" as="geometry" /></mxCell>'
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
                f'<mxCell id="{indicator_id}" parent="{row_id}" style="shape=partialRectangle;connectable=0;fillColor=none;top=0;left=0;bottom=0;right=1;fontStyle=1;overflow=hidden;whiteSpace=wrap;html=1;strokeColor=#000000;" value="{indicator}" vertex="1"><mxGeometry height="{row_height}" width="40" as="geometry"><mxRectangle height="{row_height}" width="40" as="alternateBounds" /></mxGeometry></mxCell>'
            )
            table_cells.append(
                f'<mxCell id="{value_id}" parent="{row_id}" style="shape=partialRectangle;connectable=0;fillColor=none;top=0;left=0;bottom=0;right=0;align=left;spacingLeft=6;fontStyle={font_style};overflow=hidden;whiteSpace=wrap;html=1;strokeColor=#000000;" value="{column_value(column)}" vertex="1"><mxGeometry height="{row_height}" width="{box_width - 40}" x="40" as="geometry"><mxRectangle height="{row_height}" width="{box_width - 40}" as="alternateBounds" /></mxGeometry></mxCell>'
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
                f'<mxCell id="{edge_cell_id}" edge="1" parent="1" source="{positions[table.name]}" style="edgeStyle=orthogonalEdgeStyle;rounded=0;orthogonalLoop=1;jettySize=auto;html=1;jumpStyle=arc;jumpSize=10;startArrow=ERmandOne;startFill=0;endArrow=ERoneToMany;endFill=0;exitX={exit_x};exitY={exit_y};exitDx=0;exitDy=0;entryX={entry_x};entryY={entry_y};entryDx=0;entryDy=0;" target="{positions[fk.target_table]}"><mxGeometry relative="1" as="geometry"><Array as="points"><mxPoint x="{mid_x}" y="{source_center_y}" /><mxPoint x="{mid_x}" y="{target_center_y}" /></Array></mxGeometry></mxCell>'
            )
            edge_cells.append(
                f'<mxCell id="{label_id}" connectable="0" parent="{edge_cell_id}" style="edgeLabel;html=1;align=center;verticalAlign=middle;resizable=0;points=[];labelBackgroundColor=#ffffff;spacing=2;" value="belongs to" vertex="1"><mxGeometry relative="1" x="0" y="-10" as="geometry"><mxPoint as="offset" /></mxGeometry></mxCell>'
            )

    width = max(1920, (max_x - min_x) + 400)
    height = max(1080, (max_y - min_y) + 400)
    dx = width
    dy = height

    body = "\n      ".join(root_cells + edge_cells + table_cells)
    return (
        '<?xml version="1.0" encoding="UTF-8"?>\n'
        '<mxfile host="app.diagrams.net" modified="2026-05-17T00:00:00.000Z" agent="Codex" version="24.7.17">\n'
        '  <diagram id="capstone-erd" name="ERD">\n'
        f'    <mxGraphModel dx="{dx}" dy="{dy}" grid="1" gridSize="10" guides="1" tooltips="1" connect="1" arrows="1" fold="1" page="1" pageScale="1" pageWidth="1920" pageHeight="1080" math="0" shadow="0">\n'
        '      <root>\n'
        f'      {body}\n'
        '      </root>\n'
        '    </mxGraphModel>\n'
        '  </diagram>\n'
        '</mxfile>\n'
    )


def main() -> None:
    tables = load_schema()
    OUTPUT_FILE.parent.mkdir(parents=True, exist_ok=True)
    OUTPUT_FILE.write_text(drawio_xml(tables), encoding="utf-8")
    print(f"Wrote {OUTPUT_FILE}")
    print(f"Tables: {len(tables)}")


if __name__ == "__main__":
    main()

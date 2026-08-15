from __future__ import annotations

import sys
from io import BytesIO
from pathlib import Path
from zipfile import ZIP_DEFLATED, ZipFile
from xml.sax.saxutils import escape

from generate_erd_drawio import Column, ForeignKey, Table, load_schema, ordered_columns
from generate_db_dictionary_docx import field_description, foreign_key_for, humanize, titleize


ROOT = Path(__file__).resolve().parents[1]
OUTPUT_FILE = ROOT / "docs" / "system-database-tables.docx"
NS_W = "http://schemas.openxmlformats.org/wordprocessingml/2006/main"

# These are Laravel's technical tables. They are not part of the PyroSense
# application data model, so they are deliberately omitted from the document.
EXCLUDED_TABLES = {
    "cache", "cache_locks", "failed_jobs", "job_batches", "jobs",
    "password_reset_tokens", "sessions",
}

TYPE_MAP = {
    "id": "bigint(20)", "foreignId": "bigint(20)",
    "unsignedBigInteger": "bigint(20)", "integer": "int(11)",
    "unsignedInteger": "int(10)", "string": "varchar(255)",
    "text": "text", "longText": "longtext", "boolean": "tinyint(1)",
    "date": "date", "timestamp": "timestamp", "json": "json",
    "enum": "enum", "decimal": "decimal", "float": "float",
    "double": "double", "year": "year", "dateTime": "datetime",
}


def voice_calls_table() -> Table:
    """Schema parser cannot read this migration because it uses raw PostgreSQL SQL."""
    table = Table(name="voice_calls")
    columns = [
        ("id", "id", False, True), ("caller_id", "foreignId", False, False),
        ("assigned_admin_id", "foreignId", True, False), ("station_id", "foreignId", True, False),
        ("incident_id", "foreignId", True, False), ("created_incident_id", "foreignId", True, False),
        ("flagged_by", "foreignId", True, False), ("channel_name", "string", False, False),
        ("status", "string", False, False), ("queued_at", "timestamp", True, False),
        ("answered_at", "timestamp", True, False), ("ended_at", "timestamp", True, False),
        ("caller_latitude", "decimal", True, False), ("caller_longitude", "decimal", True, False),
        ("caller_accuracy", "integer", True, False), ("caller_location_label", "string", True, False),
        ("flagged_at", "timestamp", True, False), ("flag_reason", "string", True, False),
        ("created_at", "timestamp", True, False), ("updated_at", "timestamp", True, False),
    ]
    for name, type_name, nullable, primary in columns:
        table.columns[name] = Column(name, type_name, nullable, primary, name.endswith("_id"))
    table.foreign_keys = [
        ForeignKey("caller_id", "users", "user_id"), ForeignKey("assigned_admin_id", "users", "user_id"),
        ForeignKey("station_id", "stations", "station_id"), ForeignKey("incident_id", "incidents", "incident_id"),
        ForeignKey("created_incident_id", "incidents", "incident_id"), ForeignKey("flagged_by", "users", "user_id"),
    ]
    return table


def table_summary(table: Table) -> str:
    links = []
    for fk in table.foreign_keys:
        target = humanize(fk.target_table)
        if target not in links:
            links.append(target)
    relation = f" It is linked to {', '.join(links[:-1])} and {links[-1]}." if len(links) > 1 else (f" It is linked to {links[0]}." if links else "")
    return f"{relation} It contains the fields needed to manage this data."


def paragraph(text: str, *, bold: bool = False, center: bool = False, page_break: bool = False) -> str:
    jc = '<w:jc w:val="center"/>' if center else '<w:jc w:val="both"/>'
    pb = '<w:pageBreakBefore/>' if page_break else ''
    b = '<w:b/>' if bold else ''
    return (f'<w:p><w:pPr>{pb}{jc}<w:spacing w:line="480" w:lineRule="auto"/>'
            f'<w:ind w:firstLine="720"/></w:pPr><w:r><w:rPr>{b}'
            f'<w:rFonts w:ascii="Times New Roman" w:hAnsi="Times New Roman"/></w:rPr>'
            f'<w:t xml:space="preserve">{escape(text)}</w:t></w:r></w:p>')


def cell(text: str, width: int, *, header: bool = False, center: bool = True) -> str:
    jc = '<w:jc w:val="center"/>' if center else ''
    fill = '<w:shd w:val="clear" w:color="auto" w:fill="D9D9D9"/>' if header else ''
    b = '<w:b/>' if header else ''
    return (f'<w:tc><w:tcPr><w:tcW w:w="{width}" w:type="dxa"/>{fill}</w:tcPr>'
            f'<w:p><w:pPr>{jc}<w:spacing w:line="240" w:lineRule="auto"/></w:pPr>'
            f'<w:r><w:rPr><w:rFonts w:ascii="Times New Roman" w:hAnsi="Times New Roman"/>{b}</w:rPr>'
            f'<w:t xml:space="preserve">{escape(text)}</w:t></w:r></w:p></w:tc>')


def constraints(table: Table) -> dict[str, str]:
    labels, fk_number = {}, 1
    for column in ordered_columns(table):
        if column.is_primary:
            labels[column.name] = "PK"
        elif foreign_key_for(table, column.name):
            labels[column.name] = f"FK{fk_number}"
            fk_number += 1
        else:
            labels[column.name] = ""
    return labels


def table_xml(table: Table) -> str:
    headers = ["Fields", "Data Type", "Null", "Constraint", "Description"]
    widths = [3382, 1383, 615, 1404, 1826]
    rows = ['<w:tr><w:trPr><w:tblHeader/></w:trPr>' + ''.join(cell(h, w, header=True) for h, w in zip(headers, widths)) + '</w:tr>']
    labels = constraints(table)
    for column in ordered_columns(table):
        rows.append('<w:tr>' + ''.join([
            cell(column.name, widths[0]), cell(TYPE_MAP.get(column.type_name, column.type_name), widths[1]),
            cell("Yes" if column.nullable else "No", widths[2]), cell(labels[column.name], widths[3]),
            cell(field_description(table, column), widths[4], center=False),
        ]) + '</w:tr>')
    return ('<w:tbl><w:tblPr><w:tblW w:w="0" w:type="auto"/><w:tblBorders>'
            '<w:top w:val="single" w:sz="8" w:color="000000"/><w:left w:val="single" w:sz="8" w:color="000000"/>'
            '<w:bottom w:val="single" w:sz="8" w:color="000000"/><w:right w:val="single" w:sz="8" w:color="000000"/>'
            '<w:insideH w:val="single" w:sz="6" w:color="000000"/><w:insideV w:val="single" w:sz="6" w:color="000000"/>'
            '</w:tblBorders><w:tblCellMar><w:left w:w="10" w:type="dxa"/><w:right w:w="10" w:type="dxa"/></w:tblCellMar></w:tblPr>'
            '<w:tblGrid>' + ''.join(f'<w:gridCol w:w="{width}"/>' for width in widths) + '</w:tblGrid>' + ''.join(rows) + '</w:tbl>')


def document_xml() -> str:
    tables = {name: table for name, table in load_schema().items() if name not in EXCLUDED_TABLES}
    tables["voice_calls"] = voice_calls_table()
    body = []
    for number, table in enumerate(sorted(tables.values(), key=lambda item: item.name), start=1):
        body.append(paragraph(f"Table 3.5.{number} represents the {titleize(table.name)} data used by the PyroSense system." + table_summary(table), page_break=number > 1))
        body.append(table_xml(table))
        body.append(paragraph(f"Table 3.5.{number} {table.name}", bold=True, center=True))
    section = '<w:sectPr><w:pgSz w:w="12240" w:h="15840"/><w:pgMar w:top="1440" w:right="1440" w:bottom="1440" w:left="1440" w:header="708" w:footer="708" w:gutter="0"/></w:sectPr>'
    return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' + f'<w:document xmlns:w="{NS_W}"><w:body>' + ''.join(body) + section + '</w:body></w:document>'


def build_docx() -> bytes:
    styles = ('<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' + f'<w:styles xmlns:w="{NS_W}">'
              '<w:style w:type="paragraph" w:default="1" w:styleId="Normal"><w:name w:val="Normal"/>'
              '<w:rPr><w:rFonts w:ascii="Times New Roman" w:hAnsi="Times New Roman"/><w:sz w:val="20"/></w:rPr></w:style></w:styles>')
    with BytesIO() as buffer:
        with ZipFile(buffer, 'w', ZIP_DEFLATED) as zf:
            zf.writestr('[Content_Types].xml', '<?xml version="1.0" encoding="UTF-8"?><Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types"><Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/><Default Extension="xml" ContentType="application/xml"/><Override PartName="/word/document.xml" ContentType="application/vnd.openxmlformats-officedocument.wordprocessingml.document.main+xml"/><Override PartName="/word/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.wordprocessingml.styles+xml"/></Types>')
            zf.writestr('_rels/.rels', '<?xml version="1.0" encoding="UTF-8"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="word/document.xml"/></Relationships>')
            zf.writestr('word/document.xml', document_xml())
            zf.writestr('word/_rels/document.xml.rels', '<?xml version="1.0" encoding="UTF-8"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/></Relationships>')
            zf.writestr('word/styles.xml', styles)
        return buffer.getvalue()


def main() -> None:
    output = Path(sys.argv[1]).resolve() if len(sys.argv) > 1 else OUTPUT_FILE
    output.parent.mkdir(parents=True, exist_ok=True)
    output.write_bytes(build_docx())
    print(f"Wrote {output}")


if __name__ == '__main__':
    main()

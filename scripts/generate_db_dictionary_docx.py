from __future__ import annotations

import sys
from io import BytesIO
from pathlib import Path
from zipfile import ZIP_DEFLATED, ZipFile
from xml.sax.saxutils import escape

from generate_erd_drawio import ForeignKey, load_schema, ordered_columns


ROOT = Path(__file__).resolve().parents[1]
OUTPUT_FILE = ROOT / "docs" / "database-table-descriptions.docx"

NS_W = "http://schemas.openxmlformats.org/wordprocessingml/2006/main"


TYPE_MAP = {
    "id": "bigint(20)",
    "foreignId": "bigint(20)",
    "unsignedBigInteger": "bigint(20)",
    "integer": "int(11)",
    "unsignedInteger": "int(10)",
    "string": "varchar(255)",
    "text": "text",
    "longText": "longtext",
    "boolean": "tinyint(1)",
    "date": "date",
    "timestamp": "timestamp",
    "json": "json",
    "enum": "enum",
    "decimal": "decimal",
    "float": "float",
    "double": "double",
    "year": "year",
    "dateTime": "datetime",
}


def xml_header() -> str:
    return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'


def type_label(type_name: str) -> str:
    return TYPE_MAP.get(type_name, type_name)


def constraint_labels(table) -> dict[str, str]:
    labels: dict[str, str] = {}
    fk_index = 1
    for column in ordered_columns(table):
        if column.is_primary:
            labels[column.name] = "PK"
        elif column.is_foreign:
            labels[column.name] = f"FK{fk_index}"
            fk_index += 1
        else:
            labels[column.name] = ""
    return labels


def foreign_key_for(table, column_name: str) -> ForeignKey | None:
    for fk in table.foreign_keys:
        if fk.column == column_name:
            return fk
    return None


def humanize(name: str) -> str:
    return name.replace("_", " ").strip()


def singular(name: str) -> str:
    if name.endswith("ies"):
        return name[:-3] + "y"
    if name.endswith("s"):
        return name[:-1]
    return name


def article_for(word: str) -> str:
    return "an" if word[:1].lower() in {"a", "e", "i", "o", "u"} else "a"


def record_label(table) -> str:
    return humanize(singular(table.name))


def titleize(name: str) -> str:
    return " ".join(part.capitalize() for part in humanize(name).split())


def join_list(items: list[str]) -> str:
    if not items:
        return ""
    if len(items) == 1:
        return items[0]
    if len(items) == 2:
        return f"{items[0]} and {items[1]}"
    return f"{', '.join(items[:-1])}, and {items[-1]}"


def describe_column_role(table, column) -> str | None:
    fk = foreign_key_for(table, column.name)
    if column.is_primary:
        return "a primary key identifier for each record"
    if fk:
        target = humanize(singular(fk.target_table))
        return f"the related {target}"
    if column.name in {"created_at", "updated_at"}:
        return "timestamps"
    if column.name == "deleted_at":
        return "deletion timestamp"
    if column.name.endswith("_id"):
        base = humanize(column.name[:-3])
        return f"the {base} identifier"
    if column.name.endswith("_code"):
        base = humanize(column.name[:-5]) or record_label(table)
        return f"the {base} code"
    if column.name.endswith("_name"):
        base = humanize(column.name[:-5]) or record_label(table)
        return f"the {base} name"
    return humanize(column.name)


def core_property_summary(table, limit: int = 6) -> list[str]:
    summary: list[str] = []
    seen: set[str] = set()
    columns = ordered_columns(table)

    for column in columns:
        phrase = describe_column_role(table, column)
        if not phrase or phrase in seen:
            continue
        summary.append(phrase)
        seen.add(phrase)
        if len(summary) >= limit:
            break

    return summary


def column_discussion_phrase(table, column) -> str:
    fk = foreign_key_for(table, column.name)
    if column.is_primary:
        return f"{column.name} as the primary key identifier"
    if fk:
        target = humanize(singular(fk.target_table))
        return f"{column.name} for the related {target}"
    if column.name in {"created_at", "updated_at"}:
        return "timestamps"
    return column.name


def discussion_fields(table, limit: int = 7) -> list[str]:
    fields: list[str] = []
    seen: set[str] = set()
    for column in ordered_columns(table):
        phrase = column_discussion_phrase(table, column)
        if phrase in seen:
            continue
        fields.append(phrase)
        seen.add(phrase)
        if len(fields) >= limit:
            break
    return fields


def field_description(table, column) -> str:
    fk = foreign_key_for(table, column.name)
    subject = record_label(table)
    words = humanize(column.name)

    if column.is_primary:
        return f"Unique identifier for the {subject} record."
    if fk:
        target = humanize(singular(fk.target_table))
        return f"References the related {target} record used by this {subject} entry."

    if column.name.endswith("_id"):
        base = humanize(column.name[:-3])
        return f"Identifier assigned to the {base} associated with this {subject} record."

    if column.name.endswith("_code"):
        base = humanize(column.name[:-5])
        return f"Code used to identify the {base or subject} in the system."

    if column.name.endswith("_name"):
        base = humanize(column.name[:-5])
        target = base or subject
        return f"Official name of the {target}."

    if column.name.endswith("_date"):
        base = humanize(column.name[:-5]) or "relevant"
        return f"Date when the {base} event for this {subject} record occurred."

    if column.name.endswith("_time"):
        base = humanize(column.name[:-5]) or "relevant"
        return f"Time when the {base} event for this {subject} record occurred."

    if column.name.endswith("_at"):
        base = humanize(column.name[:-3]) or "relevant"
        return f"Date and time when the {base} event happened for this {subject} record."

    if column.name.endswith("_by"):
        base = humanize(column.name[:-3]) or "responsible"
        return f"Stores who {base} this {subject} record."

    if column.name.startswith("is_"):
        state = humanize(column.name[3:])
        return f"Boolean flag that shows whether the {subject} is {state}."

    if column.type_name == "boolean":
        return f"Boolean flag indicating the {words} status of this {subject} record."

    if column.type_name == "enum":
        return f"Stores the selected {words} category for this {subject} record."

    canned = {
        "created_at": "Date and time when the record was created.",
        "updated_at": "Date and time when the record was last updated.",
        "deleted_at": "Date and time when the record was deleted.",
        "status": "Current status of the record.",
        "notes": "Additional notes or remarks for the record.",
        "description": "Detailed description of the record.",
        "name": "Name of the record.",
        "title": "Title of the record.",
        "email": "Email address associated with the record.",
        "contact_number": "Contact number associated with the record.",
        "phone": "Phone number associated with the record.",
        "password": "Encrypted password for the account.",
        "image": "Image or image path associated with the record.",
        "lat": "Latitude coordinate of the location.",
        "lng": "Longitude coordinate of the location.",
        "latitude": "Latitude coordinate of the location.",
        "longitude": "Longitude coordinate of the location.",
        "incident_date": "Date when the incident occurred.",
        "incident_time": "Time when the incident occurred.",
        "incident_count": "Number of incidents recorded.",
        "risk_level": "Risk level assigned to the record.",
        "priority_level": "Priority level assigned to the record.",
        "target_date": "Target date associated with the record.",
        "station_name": "Name of the fire station.",
        "station_code": "Code used to identify the fire station.",
        "barangay_name": "Name of the barangay.",
        "city_name": "Name of the city.",
        "province_name": "Name of the province.",
        "first_name": "Given name of the person linked to the record.",
        "middle_name": "Middle name of the person linked to the record.",
        "last_name": "Family name or surname of the person linked to the record.",
        "account_code": "System-generated code used to uniquely identify the account.",
        "street": "Street or house address for the recorded location.",
        "postal_code": "Postal or ZIP code for the recorded address.",
        "role": "Role or access classification assigned in the system.",
        "payload": "Serialized session or cached data stored for processing.",
        "token": "Security token used to validate the related request.",
        "ip_address": "IP address recorded for the user session or activity.",
        "user_agent": "Browser or device information captured for the session.",
        "last_activity": "Timestamp of the most recent recorded activity for the session.",
    }
    if column.name in canned:
        return canned[column.name]

    return f"Stores {article_for(words)} {words} value for this {subject} record."


def table_brief(table) -> str:
    table_title = titleize(table.name)
    subject = humanize(table.name)
    subject_singular = record_label(table)
    columns = ordered_columns(table)
    core_fields = discussion_fields(table)

    parts = [
        f"Table {table_title} represents the {subject} table used to store {subject} records in the system."
    ]

    if core_fields:
        intro = f"It contains important fields such as {join_list(core_fields)}"
        if len(columns) > len(core_fields):
            intro += f", along with other details needed for each {subject_singular} record"
        parts.append(intro + ".")

    return " ".join(parts)


def p(text: str, bold: bool = False, center: bool = False, spacing_after: int = 120) -> str:
    jc = '<w:jc w:val="center"/>' if center else ""
    b = "<w:b/>" if bold else ""
    return (
        f"<w:p><w:pPr>{jc}<w:spacing w:after=\"{spacing_after}\"/></w:pPr>"
        f"<w:r><w:rPr>{b}<w:sz w:val=\"22\"/></w:rPr><w:t xml:space=\"preserve\">{escape(text)}</w:t></w:r></w:p>"
    )


def cell(text: str, width: int, bold: bool = False, center: bool = False, fill: str | None = None) -> str:
    jc = '<w:jc w:val="center"/>' if center else ""
    b = "<w:b/>" if bold else ""
    shd = f'<w:shd w:val="clear" w:color="auto" w:fill="{fill}"/>' if fill else ""
    return (
        f"<w:tc><w:tcPr><w:tcW w:w=\"{width}\" w:type=\"dxa\"/>{shd}</w:tcPr>"
        f"<w:p><w:pPr>{jc}</w:pPr><w:r><w:rPr>{b}<w:sz w:val=\"20\"/></w:rPr>"
        f"<w:t xml:space=\"preserve\">{escape(text)}</w:t></w:r></w:p></w:tc>"
    )


def row(cells: list[str]) -> str:
    return "<w:tr>" + "".join(cells) + "</w:tr>"


def table_xml(table, index: int) -> str:
    headers = ["Fields", "Data Type", "Null", "Constrained", "Description"]
    widths = [2600, 2400, 900, 900, 3300]
    labels = constraint_labels(table)

    rows = [
        row([cell(h, w, bold=True, center=True, fill="D9D9D9") for h, w in zip(headers, widths)])
    ]

    for column in ordered_columns(table):
        rows.append(
            row(
                [
                    cell(column.name, widths[0]),
                    cell(type_label(column.type_name), widths[1], center=True),
                    cell("Yes" if column.nullable else "No", widths[2], center=True),
                    cell(labels[column.name], widths[3], center=True),
                    cell(field_description(table, column), widths[4]),
                ]
            )
        )

    title = p(f"Table {index}. {table.name}", bold=True, center=True, spacing_after=80)
    tbl = (
        "<w:tbl>"
        "<w:tblPr>"
        "<w:tblStyle w:val=\"TableGrid\"/>"
        "<w:tblW w:w=\"0\" w:type=\"auto\"/>"
        "<w:tblBorders>"
        "<w:top w:val=\"single\" w:sz=\"8\" w:space=\"0\" w:color=\"000000\"/>"
        "<w:left w:val=\"single\" w:sz=\"8\" w:space=\"0\" w:color=\"000000\"/>"
        "<w:bottom w:val=\"single\" w:sz=\"8\" w:space=\"0\" w:color=\"000000\"/>"
        "<w:right w:val=\"single\" w:sz=\"8\" w:space=\"0\" w:color=\"000000\"/>"
        "<w:insideH w:val=\"single\" w:sz=\"6\" w:space=\"0\" w:color=\"000000\"/>"
        "<w:insideV w:val=\"single\" w:sz=\"6\" w:space=\"0\" w:color=\"000000\"/>"
        "</w:tblBorders>"
        "</w:tblPr>"
        "<w:tblGrid>"
        + "".join(f'<w:gridCol w:w="{w}"/>' for w in widths)
        + "</w:tblGrid>"
        + "".join(rows)
        + "</w:tbl>"
    )
    brief = p(table_brief(table), spacing_after=240)
    return title + tbl + brief


def document_xml() -> str:
    tables = load_schema()
    ordered = sorted(tables.values(), key=lambda item: item.name)

    body = [
        p("Database Tables and Descriptions", bold=True, center=True, spacing_after=180),
    ]
    for index, table in enumerate(ordered, start=1):
        body.append(table_xml(table, index))

    section = (
        "<w:sectPr>"
        "<w:pgSz w:w=\"12240\" w:h=\"15840\"/>"
        "<w:pgMar w:top=\"1440\" w:right=\"1440\" w:bottom=\"1440\" w:left=\"1440\" "
        "w:header=\"708\" w:footer=\"708\" w:gutter=\"0\"/>"
        "</w:sectPr>"
    )

    return (
        xml_header()
        + f'<w:document xmlns:w="{NS_W}"><w:body>'
        + "".join(body)
        + section
        + "</w:body></w:document>"
    )


def content_types_xml() -> str:
    return (
        xml_header()
        + '<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">'
        + '<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>'
        + '<Default Extension="xml" ContentType="application/xml"/>'
        + '<Override PartName="/word/document.xml" '
        + 'ContentType="application/vnd.openxmlformats-officedocument.wordprocessingml.document.main+xml"/>'
        + '<Override PartName="/word/styles.xml" '
        + 'ContentType="application/vnd.openxmlformats-officedocument.wordprocessingml.styles+xml"/>'
        + "</Types>"
    )


def rels_xml() -> str:
    return (
        xml_header()
        + '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
        + '<Relationship Id="rId1" '
        + 'Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" '
        + 'Target="word/document.xml"/>'
        + "</Relationships>"
    )


def document_rels_xml() -> str:
    return (
        xml_header()
        + '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
        + '<Relationship Id="rId1" '
        + 'Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" '
        + 'Target="styles.xml"/>'
        + "</Relationships>"
    )


def styles_xml() -> str:
    return (
        xml_header()
        + f'<w:styles xmlns:w="{NS_W}">'
        + '<w:style w:type="paragraph" w:default="1" w:styleId="Normal">'
        + '<w:name w:val="Normal"/>'
        + '<w:qFormat/>'
        + '<w:rPr><w:rFonts w:ascii="Times New Roman" w:hAnsi="Times New Roman"/>'
        + '<w:sz w:val="20"/></w:rPr>'
        + "</w:style>"
        + "</w:styles>"
    )


def build_docx() -> bytes:
    buffer = BytesIO()
    with ZipFile(buffer, "w", ZIP_DEFLATED) as zf:
        zf.writestr("[Content_Types].xml", content_types_xml())
        zf.writestr("_rels/.rels", rels_xml())
        zf.writestr("word/document.xml", document_xml())
        zf.writestr("word/_rels/document.xml.rels", document_rels_xml())
        zf.writestr("word/styles.xml", styles_xml())
    return buffer.getvalue()


def main() -> None:
    output_file = Path(sys.argv[1]).resolve() if len(sys.argv) > 1 else OUTPUT_FILE
    output_file.parent.mkdir(parents=True, exist_ok=True)
    output_file.write_bytes(build_docx())
    print(f"Wrote {output_file}")


if __name__ == "__main__":
    main()

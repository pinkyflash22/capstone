from __future__ import annotations

import copy
import json
import re
import subprocess
import zipfile
from collections import defaultdict
from datetime import datetime, timezone
from pathlib import Path
from typing import Any
from xml.etree import ElementTree as ET


ROOT = Path(__file__).resolve().parents[1]
TEMPLATE = ROOT / "docs" / "database-table-descriptions-updated.docx"
OUTPUT = ROOT / "docs" / "database-table-descriptions-complete.docx"
SCHEMA_EXPORTER = ROOT / "scripts" / "export_public_schema.php"

W_NS = "http://schemas.openxmlformats.org/wordprocessingml/2006/main"
CP_NS = "http://schemas.openxmlformats.org/package/2006/metadata/core-properties"
DC_NS = "http://purl.org/dc/elements/1.1/"
DCTERMS_NS = "http://purl.org/dc/terms/"
XSI_NS = "http://www.w3.org/2001/XMLSchema-instance"
XML_NS = "http://www.w3.org/XML/1998/namespace"
W = f"{{{W_NS}}}"

ET.register_namespace("w", W_NS)


def schema_payload() -> dict[str, Any]:
    result = subprocess.run(
        ["php", str(SCHEMA_EXPORTER)],
        cwd=ROOT,
        capture_output=True,
        text=True,
        check=True,
    )
    return json.loads(result.stdout)


def words(value: str) -> str:
    return value.replace("_", " ").strip()


def singular(value: str) -> str:
    label = words(value)
    irregular = {
        "analysis": "analysis",
        "cities": "city",
        "provinces": "province",
        "barangays": "barangay",
        "activities": "activity",
        "statuses": "status",
        "users": "user",
        "incidents": "incident",
        "roles": "role",
        "routes": "route",
        "sessions": "session",
    }
    if value in irregular:
        return irregular[value]
    if label.endswith("ies"):
        return label[:-3] + "y"
    if label.endswith("sses"):
        return label[:-2]
    if label.endswith("s") and not label.endswith("ss"):
        return label[:-1]
    return label


def title(value: str) -> str:
    return words(value).title()


def concise_type(column: dict[str, Any]) -> str:
    data_type = str(column.get("data_type") or column.get("udt_name") or "unknown")
    length = column.get("character_maximum_length")
    precision = column.get("numeric_precision")
    scale = column.get("numeric_scale")

    if data_type in {"character varying", "varchar"}:
        return f"varchar({length})" if length else "varchar"
    if data_type in {"character", "char"}:
        return f"char({length})" if length else "char"
    if data_type in {"timestamp without time zone", "timestamp with time zone"}:
        return "timestamp"
    if data_type in {"time without time zone", "time with time zone"}:
        return "time"
    if data_type in {"numeric", "decimal"} and precision:
        return f"decimal({precision},{scale or 0})"
    if data_type == "bigint":
        return "bigint(20)"
    if data_type == "integer":
        return "int(10)"
    if data_type == "smallint":
        return "smallint"
    if data_type == "double precision":
        return "double"
    if data_type == "boolean":
        return "boolean"
    if data_type == "USER-DEFINED":
        return str(column.get("udt_name") or "enum")
    if data_type == "ARRAY":
        return f"{column.get('udt_name') or 'array'}"
    return data_type


def column_description(
    table_name: str,
    column_name: str,
    is_primary: bool,
    target_table: str | None,
) -> str:
    entity = singular(table_name)
    field = words(column_name)
    if is_primary:
        return f"Unique identifier for the {entity} record."
    if target_table:
        return f"References the related {singular(target_table)} record used by this {entity} entry."

    fixed = {
        "created_at": f"Date and time when the {entity} record was created.",
        "updated_at": f"Date and time when the {entity} record was last updated.",
        "deleted_at": f"Date and time when the {entity} record was soft deleted.",
        "remember_token": "Token used to preserve an authenticated user session.",
        "email_verified_at": "Date and time when the email address was verified.",
        "lat": f"Latitude coordinate associated with the {entity} record.",
        "lng": f"Longitude coordinate associated with the {entity} record.",
        "latitude": f"Latitude coordinate associated with the {entity} record.",
        "longitude": f"Longitude coordinate associated with the {entity} record.",
        "status": f"Current workflow status of the {entity} record.",
        "description": f"Descriptive details recorded for the {entity}.",
        "notes": f"Additional notes recorded for the {entity}.",
        "metadata": f"Structured metadata associated with the {entity} record.",
    }
    if column_name in fixed:
        return fixed[column_name]
    if column_name.endswith("_at"):
        return f"Date and time when the {field[:-3]} event occurred for the {entity} record."
    if column_name.endswith("_date") or column_name == "date":
        return f"Calendar date recorded for the {field.replace(' date', '')} of the {entity}."
    if column_name.endswith("_time") or column_name == "time":
        return f"Time recorded for the {field.replace(' time', '')} of the {entity}."
    if column_name.endswith("_count") or column_name in {"attempts", "victims"}:
        return f"Number of {field.replace(' count', '')} associated with the {entity} record."
    if column_name.startswith("is_") or column_name.startswith("has_"):
        return f"Indicates whether the {field.removeprefix('is ').removeprefix('has ')} condition applies to the {entity}."
    if column_name.endswith("_name") or column_name == "name":
        return f"Name used to identify the {entity}."
    if column_name.endswith("_title") or column_name == "title":
        return f"Display title used for the {entity}."
    if column_name.endswith("_email") or column_name == "email":
        return f"Email address associated with the {entity}."
    if "contact" in column_name or column_name.endswith("_phone"):
        return f"Contact information associated with the {entity}."
    return f"Stores the {field} value for this {entity} record."


def set_text(element: ET.Element, value: str) -> None:
    text_nodes = element.findall(f".//{W}t")
    if not text_nodes:
        paragraph = element.find(f".//{W}p")
        if paragraph is None:
            paragraph = ET.SubElement(element, f"{W}p")
        run = ET.SubElement(paragraph, f"{W}r")
        text_node = ET.SubElement(run, f"{W}t")
        text_nodes = [text_node]
    text_nodes[0].text = value
    if value.startswith(" ") or value.endswith(" "):
        text_nodes[0].set(f"{{{XML_NS}}}space", "preserve")
    for node in text_nodes[1:]:
        node.text = ""


def table_intro(table_name: str, targets: list[str]) -> str:
    entity = singular(table_name)
    sentence = (
        f"Table {title(table_name)} represents the {table_name} table used to store "
        f"{entity} records in the system."
    )
    if targets:
        linked = [words(target) for target in targets]
        if len(linked) == 1:
            linked_text = linked[0]
        else:
            linked_text = ", ".join(linked[:-1]) + f", and {linked[-1]}"
        sentence += f" It is linked to {linked_text}."
    sentence += f" It contains the fields required to manage each {entity} record."
    return sentence


def replace_document_xml(template_xml: bytes, payload: dict[str, Any]) -> bytes:
    root = ET.fromstring(template_xml)
    body = root.find(f"{W}body")
    if body is None:
        raise RuntimeError("The Word template has no document body.")

    children = list(body)
    title_template = next(child for child in children if child.tag == f"{W}p")
    caption_template = next(
        child for child in children
        if child.tag == f"{W}p" and "Table 1." in "".join(node.text or "" for node in child.findall(f".//{W}t"))
    )
    table_template = next(child for child in children if child.tag == f"{W}tbl")
    table_index = children.index(table_template)
    description_template = next(
        child for child in children[table_index + 1:]
        if child.tag == f"{W}p"
    )
    section_properties = next((child for child in reversed(children) if child.tag == f"{W}sectPr"), None)

    foreign_keys: dict[tuple[str, str], dict[str, Any]] = {}
    targets_by_table: dict[str, list[str]] = defaultdict(list)
    fk_order: dict[tuple[str, str], int] = {}
    for item in payload["foreign_keys"]:
        key = (item["table_name"], item["column_name"])
        foreign_keys[key] = item
        if item["target_table"] not in targets_by_table[item["table_name"]]:
            targets_by_table[item["table_name"]].append(item["target_table"])
    columns_by_table: dict[str, list[dict[str, Any]]] = defaultdict(list)
    for item in payload["columns"]:
        columns_by_table[item["table_name"]].append(item)
    for table_name, columns in columns_by_table.items():
        foreign_column_names = [
            column["column_name"]
            for column in columns
            if (table_name, column["column_name"]) in foreign_keys
        ]
        for index, column_name in enumerate(foreign_column_names, start=1):
            fk_order[(table_name, column_name)] = index

    for child in list(body):
        body.remove(child)
    body.append(copy.deepcopy(title_template))

    template_rows = table_template.findall(f"{W}tr")
    if len(template_rows) < 2:
        raise RuntimeError("The Word template table must contain a header and data row.")
    header_template = template_rows[0]
    data_row_template = template_rows[1]

    for number, table_name in enumerate(sorted(columns_by_table), start=1):
        caption = copy.deepcopy(caption_template)
        set_text(caption, f"Table {number}. {table_name}")
        body.append(caption)

        generated_table = copy.deepcopy(table_template)
        for row in list(generated_table.findall(f"{W}tr")):
            generated_table.remove(row)
        generated_table.append(copy.deepcopy(header_template))

        for column in columns_by_table[table_name]:
            row = copy.deepcopy(data_row_template)
            cells = row.findall(f"{W}tc")
            if len(cells) != 5:
                raise RuntimeError("The Word template data row must contain five cells.")

            column_name = column["column_name"]
            fk = foreign_keys.get((table_name, column_name))
            constraints: list[str] = []
            if bool(column.get("is_primary")):
                constraints.append("PK")
            if fk:
                constraints.append(f"FK{fk_order[(table_name, column_name)]}")
            if bool(column.get("is_unique")) and not bool(column.get("is_primary")):
                constraints.append("UQ")

            values = [
                column_name,
                concise_type(column),
                "Yes" if column.get("is_nullable") == "YES" else "No",
                ", ".join(constraints),
                column_description(
                    table_name,
                    column_name,
                    bool(column.get("is_primary")),
                    fk["target_table"] if fk else None,
                ),
            ]
            for cell, value in zip(cells, values):
                set_text(cell, value)
            generated_table.append(row)

        body.append(generated_table)
        description = copy.deepcopy(description_template)
        set_text(description, table_intro(table_name, sorted(targets_by_table[table_name])))
        body.append(description)

    if section_properties is not None:
        body.append(copy.deepcopy(section_properties))
    return ET.tostring(root, encoding="utf-8", xml_declaration=True)


def updated_core_xml(core_xml: bytes) -> bytes:
    ET.register_namespace("cp", CP_NS)
    ET.register_namespace("dc", DC_NS)
    ET.register_namespace("dcterms", DCTERMS_NS)
    ET.register_namespace("xsi", XSI_NS)
    root = ET.fromstring(core_xml)
    modified = root.find(f"{{{DCTERMS_NS}}}modified")
    if modified is not None:
        modified.text = datetime.now(timezone.utc).replace(microsecond=0).isoformat().replace("+00:00", "Z")
    title_node = root.find(f"{{{DC_NS}}}title")
    if title_node is not None:
        title_node.text = "Complete Database Tables and Descriptions"
    return ET.tostring(root, encoding="utf-8", xml_declaration=True)


def generate() -> tuple[int, int]:
    payload = schema_payload()
    with zipfile.ZipFile(TEMPLATE, "r") as source:
        document_xml = replace_document_xml(source.read("word/document.xml"), payload)
        with zipfile.ZipFile(OUTPUT, "w") as destination:
            for info in source.infolist():
                data = source.read(info.filename)
                if info.filename == "word/document.xml":
                    data = document_xml
                elif info.filename == "docProps/core.xml":
                    data = updated_core_xml(data)
                destination.writestr(info, data)

    table_count = len({item["table_name"] for item in payload["columns"]})
    column_count = len(payload["columns"])
    return table_count, column_count


if __name__ == "__main__":
    tables, columns = generate()
    print(f"Wrote {OUTPUT}")
    print(f"Tables: {tables}")
    print(f"Columns: {columns}")

#!/usr/bin/env python3
"""Convert one DOCX to PDF through an already-running LibreOffice UNO listener."""

from __future__ import annotations

import argparse
import os
import sys

# Apache/XAMPP can omit these variables from child processes. Windows uses
# SystemRoot while loading its socket providers, including UNO's TCP connector.
if os.name == "nt":
    system_root = os.environ.get("SystemRoot") or os.environ.get("WINDIR") or r"C:\Windows"
    os.environ["SystemRoot"] = system_root
    os.environ["WINDIR"] = system_root

import uno


def property_value(name: str, value: object):
    prop = uno.createUnoStruct("com.sun.star.beans.PropertyValue")
    prop.Name = name
    prop.Value = value
    return prop


def main() -> int:
    parser = argparse.ArgumentParser()
    parser.add_argument("source")
    parser.add_argument("destination")
    parser.add_argument("--host", default="127.0.0.1")
    parser.add_argument("--port", type=int, default=2002)
    args = parser.parse_args()

    source = os.path.abspath(args.source)
    destination = os.path.abspath(args.destination)
    if not os.path.isfile(source):
        raise FileNotFoundError(f"Source DOCX does not exist: {source}")
    os.makedirs(os.path.dirname(destination), exist_ok=True)

    local_context = uno.getComponentContext()
    resolver = local_context.ServiceManager.createInstanceWithContext(
        "com.sun.star.bridge.UnoUrlResolver", local_context
    )
    context = resolver.resolve(
        f"uno:socket,host={args.host},port={args.port};urp;StarOffice.ComponentContext"
    )
    desktop = context.ServiceManager.createInstanceWithContext(
        "com.sun.star.frame.Desktop", context
    )
    document = desktop.loadComponentFromURL(
        uno.systemPathToFileUrl(source),
        "_blank",
        0,
        (
            property_value("Hidden", True),
            property_value("ReadOnly", True),
            property_value("UpdateDocMode", 0),
        ),
    )
    if document is None:
        raise RuntimeError("LibreOffice could not open the source DOCX.")

    try:
        document.storeToURL(
            uno.systemPathToFileUrl(destination),
            (
                property_value("FilterName", "writer_pdf_Export"),
                property_value("Overwrite", True),
            ),
        )
    finally:
        document.close(True)

    if not os.path.isfile(destination) or os.path.getsize(destination) == 0:
        raise RuntimeError("LibreOffice did not create the destination PDF.")
    return 0


if __name__ == "__main__":
    try:
        raise SystemExit(main())
    except Exception as error:
        print(str(error), file=sys.stderr)
        raise SystemExit(1)

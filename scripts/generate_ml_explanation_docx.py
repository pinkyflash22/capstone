from __future__ import annotations

from pathlib import Path
from xml.sax.saxutils import escape
from zipfile import ZIP_DEFLATED, ZipFile


ROOT = Path(__file__).resolve().parents[1]
OUTPUT_PATH = ROOT / "docs" / "python-ml-explanation.docx"


CONTENT = [
    ("PyroSense Python Machine Learning Explanation", "title"),
    ("What this document is for", "heading"),
    (
        "This file explains in simple terms how the Python machine learning part of the project works, "
        "what model is being used, and how Laravel communicates with it.",
        "body",
    ),
    ("Short answer", "heading"),
    (
        "Yes. This project uses a real machine learning model in Python. The model is trained in "
        "capstone-ai/train_model.py and used for prediction in capstone-ai/predict_event.py.",
        "body",
    ),
    ("Files involved", "heading"),
    ("capstone-ai/train_model.py: trains the machine learning model.", "body"),
    ("capstone-ai/predict_event.py: loads the trained model and predicts risk.", "body"),
    ("capstone-ai/fire_model.pkl: saved trained model file.", "body"),
    ("capstone-ai/fire_model_metadata.json: saved metadata about the model.", "body"),
    ("app/Services/BarangayPredictionService.php: Laravel service that runs the Python script.", "body"),
    ("app/Services/ForecastModelService.php: checks whether the model needs retraining.", "body"),
    ("What model is used", "heading"),
    (
        "The training code uses scikit-learn models. In the current code, the main imported models are "
        "RandomForestClassifier, RandomForestRegressor, and CalibratedClassifierCV.",
        "body",
    ),
    (
        "This means the project is not just using normal Python rules or if-statements. It is using a "
        "trained machine learning model saved into a .pkl file.",
        "body",
    ),
    ("Python libraries used", "heading"),
    ("pandas: for preparing tabular data.", "body"),
    ("joblib: for saving and loading the trained model.", "body"),
    ("scikit-learn: for the machine learning model and preprocessing pipeline.", "body"),
    ("How training works", "heading"),
    (
        "The train_model.py script reads historical fire-related data, prepares the features, trains the "
        "machine learning model, and saves the result into capstone-ai/fire_model.pkl.",
        "body",
    ),
    (
        "The model metadata is also saved into capstone-ai/fire_model_metadata.json so the application can "
        "track feature columns and model information.",
        "body",
    ),
    ("How prediction works", "heading"),
    (
        "When Laravel needs a prediction, it builds a payload for a station and barangay, then runs the "
        "Python prediction script.",
        "body",
    ),
    (
        "The Python script loads the saved model, transforms the input into the expected feature format, "
        "and returns prediction results as JSON.",
        "body",
    ),
    ("How Laravel communicates with Python", "heading"),
    (
        "Laravel does not currently call Python through a separate HTTP API service. Instead, Laravel runs "
        "the Python script directly as a local process on the same server.",
        "body",
    ),
    (
        "This happens inside app/Services/BarangayPredictionService.php using Laravel Process::run(...). "
        "So Python still executes, but you do not manually run it for normal app usage.",
        "body",
    ),
    ("Does this count as machine learning", "heading"),
    (
        "Yes. Because the project trains a model from historical data and saves that trained model for later "
        "predictions, this is a machine learning workflow.",
        "body",
    ),
    ("Deployment note", "heading"),
    (
        "If you deploy this project, the server must have Python installed together with the required ML "
        "libraries such as pandas, joblib, and scikit-learn. Since Laravel runs the Python script locally, "
        "both PHP and Python need to be available in the deployed environment.",
        "body",
    ),
]


CONTENT_TYPES_XML = """<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">
  <Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>
  <Default Extension="xml" ContentType="application/xml"/>
  <Override PartName="/word/document.xml" ContentType="application/vnd.openxmlformats-officedocument.wordprocessingml.document.main+xml"/>
  <Override PartName="/docProps/core.xml" ContentType="application/vnd.openxmlformats-package.core-properties+xml"/>
  <Override PartName="/docProps/app.xml" ContentType="application/vnd.openxmlformats-officedocument.extended-properties+xml"/>
</Types>
"""

RELS_XML = """<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">
  <Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="word/document.xml"/>
  <Relationship Id="rId2" Type="http://schemas.openxmlformats.org/package/2006/relationships/metadata/core-properties" Target="docProps/core.xml"/>
  <Relationship Id="rId3" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/extended-properties" Target="docProps/app.xml"/>
</Relationships>
"""

DOC_RELS_XML = """<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"/>
"""

APP_XML = """<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Properties xmlns="http://schemas.openxmlformats.org/officeDocument/2006/extended-properties"
            xmlns:vt="http://schemas.openxmlformats.org/officeDocument/2006/docPropsVTypes">
  <Application>Codex</Application>
</Properties>
"""

CORE_XML = """<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<cp:coreProperties xmlns:cp="http://schemas.openxmlformats.org/package/2006/metadata/core-properties"
                   xmlns:dc="http://purl.org/dc/elements/1.1/"
                   xmlns:dcterms="http://purl.org/dc/terms/"
                   xmlns:dcmitype="http://purl.org/dc/dcmitype/"
                   xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance">
  <dc:title>PyroSense Python Machine Learning Explanation</dc:title>
  <dc:creator>Codex</dc:creator>
  <cp:lastModifiedBy>Codex</cp:lastModifiedBy>
</cp:coreProperties>
"""


def paragraph_xml(text: str, style: str) -> str:
    escaped = escape(text)
    if style == "title":
        return (
            '<w:p><w:pPr><w:pStyle w:val="Title"/></w:pPr>'
            f'<w:r><w:t>{escaped}</w:t></w:r></w:p>'
        )
    if style == "heading":
        return (
            '<w:p><w:pPr><w:pStyle w:val="Heading1"/></w:pPr>'
            f'<w:r><w:t>{escaped}</w:t></w:r></w:p>'
        )
    return f'<w:p><w:r><w:t xml:space="preserve">{escaped}</w:t></w:r></w:p>'


def build_document_xml() -> str:
    paragraphs = "".join(paragraph_xml(text, style) for text, style in CONTENT)
    return f"""<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<w:document xmlns:wpc="http://schemas.microsoft.com/office/word/2010/wordprocessingCanvas"
    xmlns:mc="http://schemas.openxmlformats.org/markup-compatibility/2006"
    xmlns:o="urn:schemas-microsoft-com:office:office"
    xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships"
    xmlns:m="http://schemas.openxmlformats.org/officeDocument/2006/math"
    xmlns:v="urn:schemas-microsoft-com:vml"
    xmlns:wp14="http://schemas.microsoft.com/office/word/2010/wordprocessingDrawing"
    xmlns:wp="http://schemas.openxmlformats.org/drawingml/2006/wordprocessingDrawing"
    xmlns:w10="urn:schemas-microsoft-com:office:word"
    xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main"
    xmlns:w14="http://schemas.microsoft.com/office/word/2010/wordml"
    xmlns:wpg="http://schemas.microsoft.com/office/word/2010/wordprocessingGroup"
    xmlns:wpi="http://schemas.microsoft.com/office/word/2010/wordprocessingInk"
    xmlns:wne="http://schemas.microsoft.com/office/word/2006/wordml"
    xmlns:wps="http://schemas.microsoft.com/office/word/2010/wordprocessingShape"
    mc:Ignorable="w14 wp14">
  <w:body>
    {paragraphs}
    <w:sectPr>
      <w:pgSz w:w="12240" w:h="15840"/>
      <w:pgMar w:top="1440" w:right="1440" w:bottom="1440" w:left="1440" w:header="708" w:footer="708" w:gutter="0"/>
    </w:sectPr>
  </w:body>
</w:document>
"""


def main() -> None:
    OUTPUT_PATH.parent.mkdir(parents=True, exist_ok=True)

    with ZipFile(OUTPUT_PATH, "w", compression=ZIP_DEFLATED) as docx:
        docx.writestr("[Content_Types].xml", CONTENT_TYPES_XML)
        docx.writestr("_rels/.rels", RELS_XML)
        docx.writestr("docProps/app.xml", APP_XML)
        docx.writestr("docProps/core.xml", CORE_XML)
        docx.writestr("word/_rels/document.xml.rels", DOC_RELS_XML)
        docx.writestr("word/document.xml", build_document_xml())

    print(f"Created {OUTPUT_PATH}")


if __name__ == "__main__":
    main()

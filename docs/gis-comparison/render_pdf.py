"""Package the saved infographic as a PDF and render a review image."""
from pathlib import Path
from reportlab.pdfgen import canvas
from reportlab.lib.utils import ImageReader
import pypdfium2 as pdfium

ROOT = Path(__file__).resolve().parents[2]
out = ROOT / 'output/pdf'
png = out / 'gis-routing-comparison.png'
pdf = out / 'gis-routing-comparison.pdf'
c = canvas.Canvas(str(pdf), pagesize=(1008,720))
c.setTitle('GIS Routing API Comparison - Urbiztondo')
c.drawImage(ImageReader(str(png)), 0, 0, width=1008, height=720)
c.save()
preview = ROOT / 'tmp/pdfs/gis-comparison-preview.png'
preview.parent.mkdir(parents=True, exist_ok=True)
doc = pdfium.PdfDocument(str(pdf))
doc[0].render(scale=1).to_pil().save(str(preview))
print(f'Created {pdf}')

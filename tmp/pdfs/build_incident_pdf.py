import json
from pathlib import Path
from xml.sax.saxutils import escape

import pypdfium2 as pdfium
from pypdf import PdfReader
from reportlab.lib import colors
from reportlab.lib.pagesizes import A4
from reportlab.lib.styles import getSampleStyleSheet, ParagraphStyle
from reportlab.platypus import SimpleDocTemplate, Paragraph, Spacer, Table, TableStyle, PageBreak, KeepTogether

root = Path(__file__).resolve().parents[2]
data = json.loads((root / 'capstone-ai/historical_incident_response_times.json').read_text(encoding='utf-8-sig'))
out = root / 'output/pdf/historical-incident-response-times.pdf'
out.parent.mkdir(parents=True, exist_ok=True)
styles = getSampleStyleSheet()
styles.add(ParagraphStyle('TitleCustom', fontName='Helvetica-Bold', fontSize=23, leading=27, textColor=colors.HexColor('#14334B'), spaceAfter=9))
styles.add(ParagraphStyle('IntroCustom', fontSize=10, leading=14, spaceAfter=8, textColor=colors.HexColor('#385167')))
styles.add(ParagraphStyle('CellCustom', fontSize=8, leading=11))
styles.add(ParagraphStyle('SmallCustom', fontSize=8, leading=11, textColor=colors.HexColor('#385167')))
styles.add(ParagraphStyle('DetailCustom', fontSize=8.3, leading=11.3, spaceAfter=4))
def p(text, style='CellCustom'):
    return Paragraph(escape(str(text)), styles[style])

story = [p('Historical incident response times', 'TitleCustom'), p('Urbiztondo, Pangasinan | 2023-2026 | 12 incident records', 'IntroCustom')]
notice = Table([[p('SYNTHETIC DEMO DATA', 'IntroCustom')], [p(data['response_time_notice'], 'SmallCustom')]], colWidths=[499])
notice.setStyle(TableStyle([('BACKGROUND', (0,0), (-1,-1), colors.HexColor('#FFF2D7')), ('BOX', (0,0), (-1,-1), .5, colors.HexColor('#D7AF56')), ('LEFTPADDING',(0,0),(-1,-1),10), ('RIGHTPADDING',(0,0),(-1,-1),10), ('TOPPADDING',(0,0),(-1,-1),7), ('BOTTOMPADDING',(0,0),(-1,-1),6)]))
story.extend([notice, Spacer(1,14), p(data['response_time_definition'], 'IntroCustom')])
headers = ['#', 'Date', 'Time', 'Barangay', 'Recorded cause', 'Response\n(min)']
rows = [[Paragraph('<font color="white"><b>'+escape(x).replace('\n','<br/>')+'</b></font>', styles['CellCustom']) for x in headers]]
for i, r in enumerate(data['incidents'],1):
    rows.append([p(i), p(r['incident_date']), p(r['incident_time'] or 'Not recorded'), p(r['barangay']), p(r['fire_cause'] or 'Not recorded'), p(f"{r['response_time_minutes']:.2f}")])
table = Table(rows, colWidths=[22,66,61,72,218,60], repeatRows=1)
table.setStyle(TableStyle([('BACKGROUND',(0,0),(-1,0),colors.HexColor('#14334B')), ('TEXTCOLOR',(0,0),(-1,0),colors.white), ('VALIGN',(0,0),(-1,-1),'TOP'), ('LEFTPADDING',(0,0),(-1,-1),6), ('RIGHTPADDING',(0,0),(-1,-1),6), ('TOPPADDING',(0,0),(-1,-1),9), ('BOTTOMPADDING',(0,0),(-1,-1),9), ('ROWBACKGROUNDS',(0,1),(-1,-1),[colors.HexColor('#F0F5F8'),colors.white]), ('LINEBELOW',(0,0),(-1,0),.5,colors.HexColor('#14334B'))]))
# Paragraphs carry their own text color.
for cell in rows[0]:
    cell.style = ParagraphStyle('WhiteHeader', parent=styles['CellCustom'], textColor=colors.white, fontName='Helvetica-Bold')
story.extend([table, Spacer(1,12), p('Response values are fixed to retain the same incident/value pairing across imports. They do not establish actual operational performance.', 'SmallCustom'), PageBreak(), p('Incident locations and source notes', 'TitleCustom'), p('Record numbers match the response-time table. Source wording is preserved below.', 'IntroCustom')])
for i,r in enumerate(data['incidents'],1):
    title = Paragraph(f"<b>{i:02d} | {escape(r['incident_date'])} | {escape(r['barangay'])}</b>", styles['DetailCustom'])
    detail = Paragraph(f"<b>Location:</b> {escape(r['incident_location'])}<br/><b>Source notes:</b> {escape(r['source_notes'])}", styles['DetailCustom'])
    story.append(KeepTogether([title,detail,Spacer(1,3)]))
story.extend([Spacer(1,5), p('Data file: capstone-ai/historical_incident_response_times.json', 'SmallCustom'), p('Seeder source: '+data['source'], 'SmallCustom')])
def footer(canvas, doc):
    canvas.setStrokeColor(colors.HexColor('#D3DEE5'))
    canvas.line(48,38,A4[0]-48,38)
    canvas.setFont('Helvetica',8)
    canvas.setFillColor(colors.HexColor('#52697B'))
    canvas.drawString(48,25,'Urbiztondo historical incidents | Synthetic response times')
    canvas.drawRightString(A4[0]-48,25,str(doc.page))
SimpleDocTemplate(str(out), pagesize=A4, rightMargin=48,leftMargin=48,topMargin=40,bottomMargin=52,title='Historical Incident Response Times',author='Capstone Project').build(story,onFirstPage=footer,onLaterPages=footer)
doc=pdfium.PdfDocument(str(out))
for i,page in enumerate(doc):
    page.render(scale=1.3).to_pil().save(str(root / f'tmp/pdfs/incident-page-{i+1}.png'))
print(f'Created {out} ({len(doc)} pages)')
text = ''.join(page.extract_text() for page in PdfReader(out).pages)
print('All 12 response times included:', all(format(r['response_time_minutes'], '.2f') in text for r in data['incidents']))

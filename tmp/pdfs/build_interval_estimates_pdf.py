import json, math
from pathlib import Path
from xml.sax.saxutils import escape
from reportlab.lib import colors
from reportlab.lib.pagesizes import A4
from reportlab.lib.styles import ParagraphStyle
from reportlab.platypus import SimpleDocTemplate, Paragraph, Spacer, Table, TableStyle, PageBreak
from pypdf import PdfReader, PdfWriter
import pypdfium2 as pdfium

ROOT=Path(__file__).resolve().parents[2]
src=ROOT/'output/historical-incident-interval-estimates.json'
d=json.loads(src.read_text()); rows=d['records']
out=ROOT/'output/pdf/historical-fire-response-interval-estimates.pdf'
navy=colors.HexColor('#15354B');teal=colors.HexColor('#007E83');gray=colors.HexColor('#496171');light=colors.HexColor('#EFF5F7')
styles={
 'title':ParagraphStyle('title',fontName='Helvetica-Bold',fontSize=24,leading=28,textColor=navy,spaceAfter=9),
 'h':ParagraphStyle('h',fontName='Helvetica-Bold',fontSize=15,leading=19,textColor=navy,spaceAfter=8),
 'body':ParagraphStyle('body',fontName='Helvetica',fontSize=9.3,leading=13,textColor=gray,spaceAfter=8),
 'cell':ParagraphStyle('cell',fontName='Helvetica',fontSize=8,leading=11,textColor=navy),
 'small':ParagraphStyle('small',fontName='Helvetica',fontSize=8,leading=11,textColor=gray,spaceAfter=6),
 'th':ParagraphStyle('th',fontName='Helvetica-Bold',fontSize=8,leading=11,textColor=colors.white),
 'metric':ParagraphStyle('metric',fontName='Helvetica-Bold',fontSize=25,leading=30,textColor=teal,spaceAfter=4),
}
def p(s,style='body'):return Paragraph(escape(str(s)).replace('\n','<br/>'),styles[style])
def table(headers,records,widths):
 t=Table([[p(x,'th') for x in headers]]+[[p(x,'cell') for x in r] for r in records],colWidths=widths,repeatRows=1,hAlign='LEFT')
 t.setStyle(TableStyle([('BACKGROUND',(0,0),(-1,0),navy),('ROWBACKGROUNDS',(0,1),(-1,-1),[light,colors.white]),('VALIGN',(0,0),(-1,-1),'TOP'),('LEFTPADDING',(0,0),(-1,-1),6),('RIGHTPADDING',(0,0),(-1,-1),6),('TOPPADDING',(0,0),(-1,-1),5),('BOTTOMPADDING',(0,0),(-1,-1),5)]))
 return t
def callout(s):
 t=Table([[p(s,'small')]],colWidths=[499])
 t.setStyle(TableStyle([('BACKGROUND',(0,0),(-1,-1),colors.HexColor('#FFF3DB')),('BOX',(0,0),(-1,-1),.5,colors.HexColor('#D9B66A')),('LEFTPADDING',(0,0),(-1,-1),10),('RIGHTPADDING',(0,0),(-1,-1),10),('TOPPADDING',(0,0),(-1,-1),9),('BOTTOMPADDING',(0,0),(-1,-1),5)]))
 return t
a=sum(r['dispatch_to_arrival_minutes'] for r in rows)/len(rows)
b=sum(r['arrival_to_fire_out_minutes'] for r in rows)/len(rows)
def clock(m):
 s=round(m*60);return f'{s//60} min {s%60:02d} sec'
metric=Table([[p('Average dispatch to incident-location arrival','small'),p('Average incident arrival to fire out','small')],[p(f'{a:.2f} min','metric'),p(f'{b:.2f} min','metric')],[p(clock(a),'small'),p(clock(b),'small')]],colWidths=[249.5,249.5])
metric.setStyle(TableStyle([('BACKGROUND',(0,0),(-1,-1),light),('VALIGN',(0,0),(-1,-1),'TOP'),('LEFTPADDING',(0,0),(-1,-1),13),('RIGHTPADDING',(0,0),(-1,-1),13),('TOPPADDING',(0,0),(-1,0),12),('BOTTOMPADDING',(0,-1),(-1,-1),9)]))
story=[p('Historical fire response\nInterval estimates','title'),p('PYROSENSE | Urbiztondo, Pangasinan | 12 historical records','body'),p('Prepared 21 October 2026 | Documentation scenario','small'),callout('ESTIMATED AND SYNTHETIC DATA - These values are not measured historical response performance. Travel estimates use barangay hall proxies; arrival-to-fire-out intervals are generated scenario values.'),Spacer(1,12),metric,Spacer(1,16),p('Matched incident estimates','h')]
story += [table(['Record','Stored date','Barangay','Dispatch to arrival (min)','Arrival to fire out (min)'],[[f"H{r['historical_id']}",r['date'],r['barangay'],f"{r['dispatch_to_arrival_minutes']:.2f}",r['arrival_to_fire_out_minutes']] for r in rows],[45,78,136,120,120]),Spacer(1,11),p('Averages weight each of the 12 records equally and use the underlying travel values before display rounding. The assumed 2-minute report-to-dispatch delay is excluded from both averages.','small'),PageBreak()]
story += [p('Method and location alignment','title'),p('The estimates preserve the historical record IDs and barangay assignments. No database values, application code, or schema were changed for this PDF.'),p('Travel estimates','h'),p('Geoapify driving estimates are used for Poblacion, Bayaoas, Pasibi East and Angatel. The first three are saved routing results from 18 October 2026. Requests for Dalanguiring, Gueteb, Pasibi West and Real did not return usable results, so their travel times use this explicit fallback model:'),p('Modeled road distance = straight-line distance x 1.4\nTravel minutes = modeled road distance / 30 km/h x 60'),p('The origin is the saved sample station marker at 15.8222000, 120.3297000. A live database location lookup did not complete; the station position is not independently verified. Destinations use the saved barangay hall coordinates below.')]
unique={r['barangay']:r for r in rows}
story.append(table(['Barangay proxy','Latitude','Longitude','Travel source'],[[name,f"{r['destination_lat']:.7f}",f"{r['destination_lng']:.7f}",'Geoapify estimate' if 'Geoapify' in r['travel_method'] else 'Distance/speed model'] for name,r in unique.items()],[120,95,100,184]))
story += [Spacer(1,10),callout('A barangay hall is a location proxy, not a verified historical fire site. These coordinates do not demonstrate that the actual incident occurred within 300 metres. Descriptions naming sitios, a school or a farm have not been independently geolocated.'),Spacer(1,13),p('Arrival-to-fire-out scenario','h'),p('Each record receives an integer duration from 10 to 30 minutes using fixed random seed 20261021. These are synthetic values authorized for the documentation scenario. Low severity and one responding team are user-supplied assumptions; neither determines a measured suppression duration. H8 is described in the source notes as a large storage fire, so a uniform low-severity interpretation is not established by the records.'),p('Sources: saved historical database snapshot; user-provided incident extract and fire-out interpretation; SampleDefaultDataSeeder.php; UrbiztondoBarangayHallSeeder.php; saved Geoapify routing results; historical-incident-interval-estimates.json. Seeders were read only, never run.','small'),PageBreak()]
story += [p('Reconstructed timeline','title'),p('The recorded time is treated as fire out according to your clarification. Arrival, dispatch and report timestamps below are reconstructed scenario values, not original dispatch logs.'),p('Arrival = fire out - generated suppression duration\nDispatch = arrival - estimated travel duration\nReport = dispatch - assumed 2 minutes'),p('Dates remain as stored, including first-of-month placeholders. Times are shown without timezone conversion. Where fire-out time is missing, timestamps remain unavailable. Timestamps are displayed to whole seconds, as stored in the estimate export.','small')]
def stamp(x):
 if x is None:return 'Not recorded'
 return x.replace(' ','\n')
story.append(table(['ID','Synthetic report','Synthetic dispatch','Synthetic arrival','Fire out*'],[[f"H{r['historical_id']}",stamp(r['synthetic_report']),stamp(r['synthetic_dispatch']),stamp(r['synthetic_arrival']),stamp(r['fire_out_as_clarified_by_user'])] for r in rows],[45,113.5,113.5,113.5,113.5]))
story += [Spacer(1,10),p('*Fire out uses the original incident date/time reinterpreted by the user. The database field itself was not renamed or changed. H6, H7 and H9 have no recorded time, so their timelines cannot be reconstructed. Their generated intervals still contribute to the scenario averages.','small'),Spacer(1,12),p('Recommended next reviews','h'),p('1. Confirm the operational fire-station pin and the exact historical incident locations.\n2. Replace generated intervals with verified dispatch, arrival and fire-out logs.\n3. Review the low-severity assumption, particularly the H8 storage fire.'),p('The original estimate JSON is embedded in this PDF as historical-incident-interval-estimates.json. A PDF reader with an Attachments panel can open it.','small')]
def footer(c,doc):
 c.setStrokeColor(colors.HexColor('#D6E1E6'));c.line(48,38,A4[0]-48,38)
 c.setFillColor(gray);c.setFont('Helvetica',8)
 c.drawString(48,25,'PyroSense | Estimated scenario data | No database changes')
 c.drawRightString(A4[0]-48,25,str(doc.page))
body=ROOT/'tmp/pdfs/interval-estimates-body.pdf'
SimpleDocTemplate(str(body),pagesize=A4,leftMargin=48,rightMargin=48,topMargin=42,bottomMargin=51,title='PyroSense Historical Fire Response Interval Estimates',author='PyroSense').build(story,onFirstPage=footer,onLaterPages=footer)
w=PdfWriter(clone_from=str(body));w.add_attachment('historical-incident-interval-estimates.json',src.read_bytes())
with out.open('wb') as f:w.write(f)
reader=PdfReader(out);text='\n'.join(pg.extract_text() or '' for pg in reader.pages)
if not all(f"H{r['historical_id']}" in text for r in rows):raise ValueError('Missing incident IDs')
if f'{a:.2f} min' not in text or f'{b:.2f} min' not in text:raise ValueError('Summary value mismatch')
if json.loads(reader.attachments['historical-incident-interval-estimates.json'][0])!=d:raise ValueError('Attachment mismatch')
pdf=pdfium.PdfDocument(str(out))
for i in range(len(pdf)):pdf[i].render(scale=1.3).to_pil().save(str(ROOT/f'tmp/pdfs/interval-estimates-page-{i+1}.png'))
print(json.dumps({'pdf':str(out),'pages':len(pdf),'records':len(rows),'averages':[round(a,2),round(b,2)]}))

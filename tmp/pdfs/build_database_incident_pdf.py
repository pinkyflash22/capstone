import json, statistics, hashlib
from pathlib import Path
from datetime import datetime
from zoneinfo import ZoneInfo
from xml.sax.saxutils import escape
from reportlab.lib import colors
from reportlab.lib.pagesizes import A4
from reportlab.lib.styles import getSampleStyleSheet, ParagraphStyle
from reportlab.platypus import SimpleDocTemplate, Paragraph, Spacer, Table, TableStyle, PageBreak, KeepTogether
from pypdf import PdfReader, PdfWriter
import pypdfium2 as pdfium

ROOT = Path(__file__).resolve().parents[2]
D = json.loads((ROOT/'tmp/pdfs/historical_database_snapshot.json').read_text(encoding='utf-8'))
OUT = ROOT/'output/pdf/historical-fire-incidents-database-report.pdf'
OUT.parent.mkdir(parents=True, exist_ok=True)
ROWS = D['rows']
FIRES = [r for r in ROWS if (r.get('incident_count') or 0)>0]
BASE = [r for r in ROWS if (r.get('incident_count') or 0)<=0]
REPORTS = {r['incident_id']:r for r in D['reports']}
B = {r['barangay_id']:r['barangay_name'] for r in D['barangays']}
UNITS = {}
for u in D['units']:
    UNITS.setdefault(u['incident_id'],[]).append(u)
NAVY = colors.HexColor('#14334B')
TEAL = colors.HexColor('#007F85')
LIGHT = colors.HexColor('#EFF5F7')
GRAY = colors.HexColor('#496174')
styles=getSampleStyleSheet()
for name,size,leading,color in [('TitleX',25,29,NAVY),('HeadingX',17,21,NAVY),('BodyX',9.4,13,GRAY),('CellX',7.5,10,NAVY),('SmallX',8,11,GRAY)]:
    styles.add(ParagraphStyle(name,fontName='Helvetica-Bold' if name in ('TitleX','HeadingX') else 'Helvetica',fontSize=size,leading=leading,textColor=color,spaceAfter=7 if name in ('TitleX','HeadingX','BodyX') else 0))
styles.add(ParagraphStyle('HeaderX',parent=styles['CellX'],textColor=colors.white,fontName='Helvetica-Bold'))
def p(x,style='BodyX'):
    return Paragraph(escape(str(x)).replace('\n','<br/>'),styles[style])
def markup(x,style='BodyX'):
    return Paragraph(x,styles[style])
def val(x):
    if x is None or x=='': return 'Not recorded'
    if isinstance(x,bool): return 'Yes' if x else 'No'
    return str(x)
def dt(x): return datetime.fromisoformat(x) if x else None
def minutes(start,end):
    if not start or not end: return None
    return (dt(end)-dt(start)).total_seconds()/60
def duration(m):
    if m is None: return 'Not calculable'
    sec=round(m*60)
    h,rem=divmod(abs(sec),3600); mm,ss=divmod(rem,60)
    return ('-' if sec<0 else '')+f'{h:02}:{mm:02}:{ss:02}'
def response(r):
    complete=[u for u in UNITS.get(r['incident_id'],[]) if u['dispatched_at'] and u['arrived_at']]
    return min(complete,key=lambda u:u['arrived_at']) if complete else None
def table(headers,rows,widths):
    data=[[p(h,'HeaderX') for h in headers]]+[[p(val(c),'CellX') for c in row] for row in rows]
    t=Table(data,colWidths=widths,repeatRows=1,hAlign='LEFT')
    t.setStyle(TableStyle([('BACKGROUND',(0,0),(-1,0),NAVY),('VALIGN',(0,0),(-1,-1),'TOP'),('ROWBACKGROUNDS',(0,1),(-1,-1),[LIGHT,colors.white]),('LEFTPADDING',(0,0),(-1,-1),6),('RIGHTPADDING',(0,0),(-1,-1),6),('TOPPADDING',(0,0),(-1,-1),7),('BOTTOMPADDING',(0,0),(-1,-1),7),('LINEBELOW',(0,0),(-1,0),.5,NAVY)]))
    return t
def heading(title,desc=None):
    items=[p(title,'HeadingX')]
    if desc: items.append(p(desc))
    return items
def note(text):
    t=Table([[p(text,'SmallX')]],colWidths=[499])
    t.setStyle(TableStyle([('BACKGROUND',(0,0),(-1,-1),colors.HexColor('#FFF3DA')),('BOX',(0,0),(-1,-1),.5,colors.HexColor('#D8B468')),('LEFTPADDING',(0,0),(-1,-1),11),('RIGHTPADDING',(0,0),(-1,-1),11),('TOPPADDING',(0,0),(-1,-1),10),('BOTTOMPADDING',(0,0),(-1,-1),10)]))
    return t
exported=dt(D['meta']['extracted_at']).astimezone(ZoneInfo('Asia/Manila')).strftime('%d %B %Y, %I:%M %p PHT')
timed=[r for r in FIRES if (rp:=REPORTS.get(r['incident_id'])) and rp['fire_started'] and rp['fire_out']]
durs=[minutes(REPORTS[r['incident_id']]['fire_started'],REPORTS[r['incident_id']]['fire_out']) for r in timed]
responded=[r for r in FIRES if response(r)]
story=[p('Historical fire incidents\nDatabase records and timing','TitleX'),p('PYROSENSE | Urbiztondo, Pangasinan', 'BodyX'),p(f'Database snapshot: {exported}','SmallX'),Spacer(1,17)]
story.append(table(['Historical rows','Incident records','Weather baseline rows','With fire start/end'],[[len(ROWS),len(FIRES),len(BASE),len(timed)]],[125,125,125,124]))
story += [Spacer(1,16),p('Scope and method','HeadingX'),p('This report uses the configured PostgreSQL database, read through a repeatable-read, read-only transaction. It does not use the earlier seeder-only JSON or randomized response times. All 110 historical rows are included in the embedded data attachment; all 46 positive-count incident records are listed in the report.')]
story += [p('Fire duration = fire_out minus fire_started. These timestamps are stored in spot_investigation_report and linked to historical_fire_incidents by incident_id. The historical incident date/time is preserved separately and is not substituted for a missing fire-start timestamp.'),p('Dispatch-to-arrival interval = arrived_at minus dispatched_at, using incident_response_units. It represents the recorded travel interval for the first arrived unit with both times available. It excludes any delay before dispatch and must not be confused with total fire duration.')]
story += [note('No new database columns were added. The earlier file 2026_10_18_000001_add_response_time_to_historical_fire_incidents_table.php exists and proposes response_time_minutes and response_time_source, but the migration ledger has no matching entry and neither column exists in the live table. The file was not executed or changed for this report.'),Spacer(1,13)]
story += [p('Data quality and interpretation','HeadingX'),p(f'{len(FIRES)-len(timed)} incident records lack linked fire-start/fire-out pairs; their durations remain not calculable. The {len(BASE)} zero-count rows are weather baseline records and are not counted as fire incidents. Several source notes identify month-level dates stored using the first day of the month; those dates are retained as stored.'),p('Timestamps in the incident tables have no timezone. They are displayed exactly as stored, without a UTC-to-local conversion. Only the export timestamp above is converted to Asia/Manila. The database session timezone was UTC.'),p('Contents: measured timing register; complete incident register; source records and notes; zero-count baseline appendix; embedded database-record attachment.','SmallX'),PageBreak()]
story += heading('1. Measured timing register','Only records with linked start and end timestamps appear below. Duration format is hours:minutes:seconds; calculations retain recorded seconds.')
timingrows=[]
for r in timed:
    rp=REPORTS[r['incident_id']]; u=response(r)
    timingrows.append([f"H{r['historical_fire_incident_id']}\nI{r['incident_id']} / S{rp['spot_investigation_report_id']}",B.get(r['barangay_id'],'Not recorded'),rp['fire_started'],rp['fire_out'],duration(minutes(rp['fire_started'],rp['fire_out'])),duration(minutes(u['dispatched_at'],u['arrived_at'])) if u else 'Not calculable'])
story += [table(['Record IDs','Barangay','Fire started','Fire out','Fire duration','Dispatch to arrival'],timingrows,[60,77,108,108,73,73]),Spacer(1,12),p(f'Observed fire durations: minimum {min(durs):.0f} minutes; median {statistics.median(durs):.1f} minutes; maximum {max(durs):.0f} minutes. These summarize {len(timed)} stored pairs only and do not establish actual station performance.','SmallX'),Spacer(1,9)]
story += heading('Recorded dispatch and arrival timestamps')
unitrows=[]
for r in FIRES:
    for u in UNITS.get(r['incident_id'],[]):
        rp=REPORTS.get(r['incident_id']); issue='No arrival recorded' if not u['arrived_at'] else 'No chronology conflict detected'
        if rp and u['dispatched_at'] and rp['fire_out'] and dt(u['dispatched_at'])>dt(rp['fire_out']): issue='Dispatch occurs after recorded fire out; review required'
        unitrows.append([f"H{r['historical_fire_incident_id']} / I{r['incident_id']}",u['dispatched_at'],u['arrived_at'],duration(minutes(u['dispatched_at'],u['arrived_at'])),issue])
story += [table(['Record IDs','Dispatched','Arrived','Interval','Record check'],unitrows,[64,108,108,65,154]),Spacer(1,10),note('Important issue: incidents I21 and I27 have dispatch/arrival timestamps dated after their recorded fire-out timestamps. Their intervals are arithmetic differences from stored values and require source-record review. No dates or times have been corrected or invented.'),PageBreak()]
story += heading('2. Complete incident register',f'{len(FIRES)} rows with incident_count greater than zero. H = historical record ID; I = linked incident ID. NR = not recorded; NC = not calculable. Dates and times are preserved as stored.')
register=[]
for r in FIRES:
    rp=REPORTS.get(r['incident_id']); u=response(r)
    register.append([f"H{r['historical_fire_incident_id']}\nI{r['incident_id']}" if r['incident_id'] else f"H{r['historical_fire_incident_id']}\nNo link",r['incident_date'],r['incident_time'] or 'NR',B.get(r['barangay_id'],'NR'),r['fire_cause'] or 'NR',duration(minutes(rp['fire_started'],rp['fire_out'])) if rp else 'NC',duration(minutes(u['dispatched_at'],u['arrived_at'])) if u else 'NC'])
story += [table(['IDs','Incident date','Incident time','Barangay','Recorded cause','Fire duration','Dispatch to arrival'],register,[43,63,52,74,145,61,61]),PageBreak()]
story += heading('3. Incident source records','These details preserve database values and source notes. A stored source label or verification flag is reported as data, without independent verification. Complete original field values, including weather fields and audit timestamps, are embedded in the PDF attachment.')
for r in FIRES:
    rp=REPORTS.get(r['incident_id']); u=response(r)
    lines=[f"<b>H{r['historical_fire_incident_id']} | {escape(r['incident_date'])} | {escape(B.get(r['barangay_id'],'Not recorded'))}</b>"]
    items=[('Incident link',r['incident_id']),('Location',r['incident_location']),('Classification / cause',f"{val(r['fire_classification'])} / {val(r['fire_cause'])}"),('Recorded date / time',f"{r['incident_date']} / {val(r['incident_time'])}"),('Count / injured / deaths',f"{r['incident_count']} / {r['injured_count']} / {r['death_count']}"),('Estimated damage (PHP)',r['estimated_damage']),('Source',f"{val(r['source_name'])} ({val(r['source_type'])})"),('Source reference',r['source_reference']),('Stored verification flag',r['is_verified']),('Source notes',r['notes'])]
    if rp:
        items.append(('Linked report / started / fire out',f"S{rp['spot_investigation_report_id']} / {val(rp['fire_started'])} / {val(rp['fire_out'])}"))
        items.append(('Calculated fire duration',duration(minutes(rp['fire_started'],rp['fire_out']))))
    else: items.append(('Linked start/end / fire duration','No linked timestamp pair / not calculable'))
    for k,v in items: lines.append(f'<b>{escape(k)}:</b> {escape(val(v))}')
    card=Table([[markup('<br/>'.join(lines),'SmallX')]],colWidths=[499])
    card.setStyle(TableStyle([('BACKGROUND',(0,0),(-1,-1),LIGHT),('BOX',(0,0),(-1,-1),.4,colors.HexColor('#D4E1E7')),('LEFTPADDING',(0,0),(-1,-1),10),('RIGHTPADDING',(0,0),(-1,-1),10),('TOPPADDING',(0,0),(-1,-1),9),('BOTTOMPADDING',(0,0),(-1,-1),9)]))
    story.append(KeepTogether([card,Spacer(1,9)]))
story.append(PageBreak())
story += heading('Appendix A. Zero-count weather baseline rows',f'The following {len(BASE)} database records have incident_count = 0 and are excluded from the fire incident totals and timing statistics. Their stored source type is other. Original notes identify them as synthetic monthly weather baselines derived from Open-Meteo archive data.')
baserows=[[f"H{r['historical_fire_incident_id']}",r['incident_date'],B.get(r['barangay_id'],'NR'),r['incident_count'],r['temperature_2m'],r['relative_humidity_2m'],r['rain']] for r in BASE]
story += [table(['ID','Stored date','Barangay','Fires','Temp. (C)','RH (%)','Rain (mm)'],baserows,[42,72,120,40,75,75,75]),PageBreak()]
story += heading('Appendix B. Attached records and calculation notes')
story += [p('The PDF includes one embedded attachment named historical-fire-incidents-database-records.json. Open the Attachments panel in a PDF reader to access it. Some browser PDF viewers do not display attachments; the visible tables and incident source records remain available in every reader.'),p('The attachment contains all 110 historical table rows with all original field values, the 6 linked investigation timestamp records, the 7 linked response-unit timestamp rows, barangay names used for the joins, extraction metadata, and derived timing values. Derived fields exist only in this export, not in the database.')]
derived=[]
for r in FIRES:
    rp=REPORTS.get(r['incident_id']); u=response(r)
    derived.append({'historical_fire_incident_id':r['historical_fire_incident_id'],'incident_id':r['incident_id'],'fire_duration_minutes':minutes(rp['fire_started'],rp['fire_out']) if rp else None,'dispatch_to_arrival_minutes':minutes(u['dispatched_at'],u['arrived_at']) if u else None})
attachment={'source':'Configured PostgreSQL database; read-only snapshot','meta':D['meta'],'definitions':{'fire_duration':'spot_investigation_report.fire_out - fire_started, linked by incident_id','dispatch_to_arrival':'incident_response_units.arrived_at - dispatched_at; first arrived unit with complete pair','timestamp_policy':'Incident timestamps preserved as stored; no timezone inferred','no_random_values':True,'database_changes':False},'historical_fire_incidents':ROWS,'linked_spot_investigation_timestamps':D['reports'],'linked_response_unit_timestamps':D['units'],'barangay_names':[{'barangay_id':k,'barangay_name':v} for k,v in B.items() if k in {r['barangay_id'] for r in ROWS}],'derived_timing':derived}
attachment_bytes=json.dumps(attachment,ensure_ascii=False,indent=2).encode('utf-8')
story += [p('Database integrity: transaction_read_only = on. The transaction was rolled back after SELECT queries. No migration, seeder, test, insert, update, delete, or schema alteration was run.'),p('Attachment SHA-256: '+hashlib.sha256(attachment_bytes).hexdigest(),'SmallX'),Spacer(1,10)]
story += heading('Recommended follow-up reviews')
story += [p('1. Verify the chronology for incidents I21 and I27 against the original dispatch and investigation records.'),p('2. Supply missing start/end or dispatch/arrival records for incidents marked not calculable.'),p('3. Review the earlier unapplied response-time migration file before any future migration run; this report does not require those columns.')]
def footer(c,doc):
    c.setStrokeColor(colors.HexColor('#D4E1E7')); c.line(48,38,A4[0]-48,38)
    c.setFont('Helvetica',8); c.setFillColor(GRAY)
    c.drawString(48,25,'PyroSense | Historical database records | Read-only export')
    c.drawRightString(A4[0]-48,25,f'Page {doc.page}')
temp=ROOT/'tmp/pdfs/historical-fire-incidents-body.pdf'
SimpleDocTemplate(str(temp),pagesize=A4,rightMargin=48,leftMargin=48,topMargin=43,bottomMargin=53,title='Historical Fire Incidents - Database Records and Timing',author='PyroSense',pageCompression=1).build(story,onFirstPage=footer,onLaterPages=footer)
writer=PdfWriter(clone_from=str(temp)); writer.add_attachment('historical-fire-incidents-database-records.json',attachment_bytes)
with OUT.open('wb') as f: writer.write(f)
reader=PdfReader(str(OUT))
text='\n'.join(page.extract_text() or '' for page in reader.pages)
missing=[r['historical_fire_incident_id'] for r in ROWS if f"H{r['historical_fire_incident_id']}" not in text]
if missing: raise ValueError(f'Missing visible record IDs: {missing}')
embedded=reader.attachments['historical-fire-incidents-database-records.json'][0]
if json.loads(embedded)['historical_fire_incidents']!=ROWS: raise ValueError('Attachment differs from database export')
pdf=pdfium.PdfDocument(str(OUT))
for i in range(len(pdf)):
    pdf[i].render(scale=1.1).to_pil().save(str(ROOT/f'tmp/pdfs/database-report-page-{i+1:02}.png'))
print(json.dumps({'pdf':str(OUT),'pages':len(reader.pages),'incident_records':len(FIRES),'baseline_records':len(BASE),'fire_durations_minutes':durs,'embedded_records':len(ROWS),'all_record_ids_visible':not missing},indent=2))

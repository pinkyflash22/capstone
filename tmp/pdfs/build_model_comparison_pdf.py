from pathlib import Path
from xml.sax.saxutils import escape
from reportlab.lib import colors
from reportlab.lib.pagesizes import A4, landscape
from reportlab.lib.styles import ParagraphStyle
from reportlab.platypus import SimpleDocTemplate, Paragraph, Spacer, Table, TableStyle, PageBreak
from pypdf import PdfReader
import pypdfium2 as pdfium

ROOT=Path(__file__).resolve().parents[2]
OUT=ROOT/'output/pdf/pyrosense-llm-and-random-forest-comparison.pdf'
W,H=landscape(A4); WIDTH=W-72
navy=colors.HexColor('#14364C');teal=colors.HexColor('#007D83');gray=colors.HexColor('#435C6F');light=colors.HexColor('#F0F5F7')
S={
 'title':ParagraphStyle('TitleX',fontName='Helvetica-Bold',fontSize=21,leading=25,textColor=navy,spaceAfter=7),
 'heading':ParagraphStyle('HeadingX',fontName='Helvetica-Bold',fontSize=12,leading=16,textColor=navy,spaceAfter=5),
 'body':ParagraphStyle('BodyX',fontName='Helvetica',fontSize=9.2,leading=12.5,textColor=gray,spaceAfter=7),
 'cell':ParagraphStyle('CellX',fontName='Helvetica',fontSize=8.4,leading=11.2,textColor=navy),
 'head':ParagraphStyle('HeadX',fontName='Helvetica-Bold',fontSize=8.4,leading=11.2,textColor=colors.white),
 'small':ParagraphStyle('SmallX',fontName='Helvetica',fontSize=8,leading=10.5,textColor=gray,spaceAfter=5),
 'ref':ParagraphStyle('RefX',fontName='Helvetica',fontSize=8.7,leading=12,textColor=navy,spaceAfter=7),
}
def p(s,style='body'):return Paragraph(escape(str(s)).replace('\n','<br/>'),S[style])
def rich(s,style='body'):return Paragraph(s,S[style])
def table(headers,rows,widths,selected):
 data=[[p(x,'head') for x in headers]]+[[p(x,'cell') for x in r] for r in rows]
 t=Table(data,colWidths=widths,repeatRows=1,hAlign='LEFT')
 rules=[('BACKGROUND',(0,0),(-1,0),navy),('ROWBACKGROUNDS',(0,1),(-1,-1),[light,colors.white]),('VALIGN',(0,0),(-1,-1),'TOP'),('LEFTPADDING',(0,0),(-1,-1),8),('RIGHTPADDING',(0,0),(-1,-1),8),('TOPPADDING',(0,0),(-1,-1),6),('BOTTOMPADDING',(0,0),(-1,-1),6),('LINEBELOW',(0,0),(-1,0),.4,navy)]
 for row in selected:rules.append(('LINEBEFORE',(0,row),(0,row),3,teal))
 t.setStyle(TableStyle(rules));return t
llms=[
 ['DeepSeek-R1-Distill-Llama-70B\nSELECTED PRIMARY LLM [1, 2]','Reasoning-oriented distillation from DeepSeek-R1 into a Llama-based 70B model; supports analysis of multiple supplied factors.','Hosted availability, response time and format consistency depend on the provider. Full R1 benchmark results cannot be assigned automatically to this distilled variant.','Suitable primary LLM for predictive narratives, explaining incident and weather context, and drafting mitigation recommendations. This is not independent validation of numerical fire forecasts.'],
 ['GPT-4.1 Mini\nSELECTED FIRST FALLBACK [3]','Strong instruction following, tool calling and structured-output support; designed for low latency without a separate reasoning step.','Requires hosted API access and a usage budget. Generated content still needs evidence and format checks. A fallback cannot guarantee service availability.','Provides an alternative when the primary request fails or returns no usable content. Useful for concise summaries, restructuring and consistent response formats.'],
 ['Meta Llama 3.3 70B Instruct\nALTERNATIVE [4]','General-purpose multilingual instruction following with publicly available weights.','Self-hosting requires substantial resources; hosted speed depends on the provider. Subject to the Llama license.','A comparison model for assessing whether DeepSeek distillation improves task usefulness over its instruction-tuned Llama foundation.'],
 ['Qwen2.5-72B-Instruct\nALTERNATIVE [5]','Technical report emphasizes instruction following, reasoning, structured data and JSON generation.','Local deployment or provider hosting is required. General benchmark performance does not establish accuracy on local fire records.','Relevant alternative for structured incident summaries, evidence interpretation and mitigation checklist generation.'],
 ['Phi-4\nALTERNATIVE [6]','14B model emphasizing reasoning through carefully selected and synthetic training data.','Smaller parameter count alone does not determine actual speed or memory needs; implementation and quantization matter.','Tests whether a smaller model can provide adequate explanation quality with fewer deployment resources.'],
 ['Claude 3.5 Sonnet\nALTERNATIVE [7]','Developer evaluations report reasoning, instruction following and text-generation capabilities.','Commercial hosted access introduces pricing and availability dependencies. This is a historical model comparison, not a guarantee of current API access.','Provides a proprietary-model comparison for clarity, completeness and usefulness of operational recommendations.'],
]
story=[p('LLM selection and operational comparison','title'),p('PYROSENSE | Documentation rationale | 21 October 2026','small'),p('Table 1. Two selected LLMs and four alternatives. Literature-supported design rationale; no comparative PyroSense performance experiment is claimed.'),table(['Model and status','Relevant strengths','Operational considerations','Fit and selection rationale'],llms,[145,195,210,WIDTH-550],[1,2]),Spacer(1,10),p('Why this pair was selected','heading'),p('DeepSeek-R1-Distill-Llama-70B is a reasonable primary choice for reasoning-oriented predictive analysis and recommendations grounded in system-provided factors. GPT-4.1 Mini provides an instruction-following, low-latency alternative when that primary request cannot produce usable content. This supports continuity across model providers; it does not prove that the pair is more accurate than every alternative.'),p('Naming: use GPT-4.1 Mini, not ChatGPT 4.1 Mini. The selected DeepSeek model is distinct from Meta Llama 3.3 70B Instruct. Model cards and vendor reports supplement, but do not replace, peer-reviewed evidence.','small'),PageBreak()]
ml=[
 ['Random Forest\nSELECTED [8, 9]','Combines randomized trees to capture nonlinear relationships and interactions. Classification and regression are supported; ensemble averaging reduces single-tree instability.','Requires representative data and validation. Probability estimates need calibration assessment. Feature importance is not evidence of causation.','Fits structured incident, weather and barangay records. PyroSense implements RandomForestClassifier and RandomForestRegressor for risk classification and numerical prediction.'],
 ['XGBoost\nALTERNATIVE [8]','Strong tree-based approach for tabular prediction, using boosting to improve on earlier model errors.','Requires tuning and fair validation. Better performance than Random Forest cannot be assumed for the local dataset.','A strong comparison for determining whether boosting improves forecast quality enough to justify additional tuning.'],
 ['Decision Tree\nALTERNATIVE [10]','Produces understandable rules and supports classification and regression.','Can overfit and change substantially with changes in training data.','An interpretable baseline; Random Forest offers a practical way to reduce single-tree instability.'],
 ['Support Vector Machine / Support Vector Regression\nALTERNATIVE [11]','Kernel methods can model nonlinear relationships; classification and regression variants are available.','Feature scaling, kernel choice and regularization require attention. Probability estimation needs additional consideration.','Provides a different predictive approach for comparison, with more preprocessing and tuning choices than a basic tree pipeline.'],
 ['Multilayer Perceptron\nALTERNATIVE [12]','Neural-network approach capable of learning nonlinear relationships in tabular data.','Requires architecture, scaling, regularization and optimization choices. Competitiveness depends on the dataset and evaluation protocol.','A neural-network baseline for evaluating whether added modelling complexity improves PyroSense predictions.'],
]
story += [p('Machine-learning model comparison','title'),p('Table 2. Random Forest and four alternatives for structured-data prediction. These are candidate comparisons, not measured local rankings.'),table(['Model and status','Relevant strengths','Operational limitations','Fit and selection rationale'],ml,[145,195,210,WIDTH-550],[1]),Spacer(1,12),p('Why Random Forest was selected','heading'),p('Random Forest is a practical fit for structured historical incident, weather and geographic attributes. Its ensemble can represent nonlinear effects and interactions while supporting classification and regression in a consistent pipeline. The implementation uses separate classifiers and regressors; the classification target and regression targets must remain clearly defined. Selection does not establish universal superiority.'),p('Research support and limits','heading'),p('Grinsztajn, Oyallon and Varoquaux (2022) found tree-based models highly competitive on their medium-sized tabular benchmarks [8]. This supports considering Random Forest, but does not establish accuracy on a much smaller local dataset. Gorishniy et al. (2021) show why strong neural-network baselines and consistent evaluation protocols matter [12]. Neither study is a direct validation of PyroSense.'),PageBreak()]
story += [p('System roles and supporting discussion','title'),p('The operational distinction matters','heading'),table(['Layer','Selected model(s)','Appropriate documentation claim'],[
 ['Numerical prediction','Random Forest classifier and regressors','Trained numerical risk classification and regression, evaluated against held-out outcomes. The barangay-month pipeline includes incident-count and estimated-damage regression targets.'],
 ['AI analysis and recommendations','DeepSeek-R1-Distill-Llama-70B','Primary LLM for generated predictive analysis, explanations and recommendations based on the supplied incident, weather and operational context.'],
 ['Fallback generation','GPT-4.1 Mini, followed by other configured fallbacks','First configured fallback for AI text generation if the primary model cannot complete a usable response. Fallback availability is not guaranteed.'],
 ],[135,210,WIDTH-345],[]),Spacer(1,13),p('Suggested documentation discussion','heading'),p('PyroSense combines a trained machine-learning prediction layer with language-model assistance. Random Forest was selected because the inputs are structured records and the model family supports both classification and regression. DeepSeek-R1-Distill-Llama-70B was selected as the primary LLM for reasoning-oriented interpretation and operational recommendations, while GPT-4.1 Mini serves as the first fallback for continued text-generation support. These choices are justified by architectural fit and published capabilities; their effectiveness in the local setting still depends on data quality and task-specific evaluation.'),p('Peer-reviewed reasoning evidence','heading'),p('Guo et al. (2025) demonstrate that reasoning behavior can be developed through reinforcement learning and transferred to smaller models through distillation [1]. This supports investigating reasoning-oriented LLMs. It does not show that the selected 70B variant accurately forecasts local fire counts, and the full R1 model\'s results must not be transferred to that variant without evidence.'),p('Important implementation and evidence notes','heading'),p('The default configuration includes GPT-4o Mini and DeepSeek-R1-Distill-Qwen-32B after GPT-4.1 Mini. Therefore, GPT-4.1 Mini is the first configured fallback, not the only one. The analytics code also asks the LLM for predictive narratives; the numerical accuracy of those generated claims must not be treated as established by the Random Forest evaluation.'),p('Recommended next improvements','heading'),p('1. Record the exact model used for each generated response.\n2. Assess LLM outputs with the same evidence, prompt and expert-review rubric.\n3. Compare numerical models on the same chronological holdout with classification, calibration and regression metrics.'),p('This PDF compiles the existing discussion. No application code, database schema or database data was changed. No migrations, seeders, training runs or tests were executed.','small'),PageBreak()]
refs=[
 ('1','Guo, D., Yang, D., Zhang, H., et al. (2025). DeepSeek-R1 incentivizes reasoning in LLMs through reinforcement learning. Nature, 645, 633-638.','Peer-reviewed journal article','https://www.nature.com/articles/s41586-025-09422-z'),
 ('2','DeepSeek-AI. (2025). DeepSeek-R1: model and distilled-model documentation.','Official model documentation','https://github.com/deepseek-ai/DeepSeek-R1'),
 ('3','OpenAI. (n.d.). GPT-4.1 Mini model documentation. Model introduced in 2025; documentation consulted 21 October 2026.','Official vendor documentation','https://developers.openai.com/api/docs/models/gpt-4.1-mini'),
 ('4','Meta. (2024). Llama 3.3 70B Instruct model card.','Official model card','https://huggingface.co/meta-llama/Llama-3.3-70B-Instruct'),
 ('5','Qwen Team. (2024; revised 2025). Qwen2.5 Technical Report. arXiv:2412.15115.','Technical-report preprint','https://arxiv.org/abs/2412.15115'),
 ('6','Abdin, M., et al. (2024). Phi-4 Technical Report. arXiv:2412.08905.','Technical-report preprint','https://arxiv.org/abs/2412.08905'),
 ('7','Anthropic. (2024). Introducing Claude 3.5 Sonnet.','Vendor publication','https://www.anthropic.com/news/claude-3-5-sonnet'),
 ('8','Grinsztajn, L., Oyallon, E., & Varoquaux, G. (2022). Why do tree-based models still outperform deep learning on typical tabular data? Advances in Neural Information Processing Systems, 35.','Peer-reviewed conference paper','https://proceedings.neurips.cc/paper_files/paper/2022/hash/0378c7692da36807bdec87ab043cdadc-Abstract-Datasets_and_Benchmarks.html'),
 ('9','Scikit-learn developers. (n.d.). Ensembles: random forests and other randomized tree ensembles.','Official implementation documentation','https://scikit-learn.org/stable/modules/ensemble.html#random-forests-and-other-randomized-tree-ensembles'),
 ('10','Scikit-learn developers. (n.d.). Decision Trees.','Official implementation documentation','https://scikit-learn.org/stable/modules/tree.html'),
 ('11','Scikit-learn developers. (n.d.). Support Vector Machines.','Official implementation documentation','https://scikit-learn.org/stable/modules/svm.html'),
 ('12','Gorishniy, Y., Rubachev, I., Khrulkov, V., & Babenko, A. (2021). Revisiting Deep Learning Models for Tabular Data. Advances in Neural Information Processing Systems, 34.','Peer-reviewed conference paper','https://proceedings.neurips.cc/paper_files/paper/2021/hash/9d86d83f925f2149e9edb0ac3b49229c-Abstract.html'),
]
story += [p('References and evidence types','title'),p('Numbered references correspond to the tables and discussion. Scholarly studies cited here were published from 2021 onward. Living documentation is marked n.d. and is supplementary evidence, not a dated research study. Click each source link to open the original publication.','body')]
for num,citation,kind,url in refs:
 story.append(rich(f'<b>[{num}]</b> {escape(citation)} <font color="#496171">[{escape(kind)}]</font> <link href="{escape(url)}" color="#007D83"><u>Open source</u></link>','ref'))
story += [Spacer(1,5),p('Interpretation: the literature supports model capabilities and selection considerations. It does not provide a head-to-head evaluation of all listed models on PyroSense data. No invented accuracy, cost, latency or ranking figures are reported.','small')]
def footer(c,doc):
 c.setStrokeColor(colors.HexColor('#D6E1E6'));c.line(36,32,W-36,32)
 c.setFont('Helvetica',8);c.setFillColor(gray)
 c.drawString(36,20,'PyroSense | LLM and machine-learning selection rationale | 21 October 2026')
 c.drawRightString(W-36,20,f'Page {doc.page}')
OUT.parent.mkdir(parents=True,exist_ok=True)
SimpleDocTemplate(str(OUT),pagesize=(W,H),leftMargin=36,rightMargin=36,topMargin=32,bottomMargin=44,title='PyroSense LLM and Random Forest Comparison',author='PyroSense').build(story,onFirstPage=footer,onLaterPages=footer)
reader=PdfReader(OUT);text='\n'.join(pg.extract_text() or '' for pg in reader.pages)
for term in ['DeepSeek-R1-Distill-Llama-70B','GPT-4.1 Mini','Qwen2.5-72B-Instruct','Random Forest','XGBoost','Multilayer Perceptron','References and evidence types']:
 if term not in text:raise ValueError('Missing content: '+term)
links=sum(1 for pg in reader.pages for a in pg.get('/Annots',[]) if a.get_object().get('/Subtype')=='/Link')
if links<12:raise ValueError('Missing reference links')
pdf=pdfium.PdfDocument(str(OUT))
for i in range(len(pdf)):pdf[i].render(scale=1.3).to_pil().save(str(ROOT/f'tmp/pdfs/model-comparison-page-{i+1}.png'))
print({'pdf':str(OUT),'pages':len(pdf),'reference_links':links})

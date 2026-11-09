from pathlib import Path

from reportlab.lib import colors
from reportlab.lib.enums import TA_CENTER
from reportlab.lib.pagesizes import A4
from reportlab.lib.styles import getSampleStyleSheet, ParagraphStyle
from reportlab.lib.units import mm
from reportlab.platypus import SimpleDocTemplate, Paragraph, Spacer, PageBreak, Table, TableStyle, KeepTogether
from reportlab.lib.utils import ImageReader

ROOT = Path(__file__).resolve().parents[1]
OUT = ROOT / "output/pdf/Documento_de_Gestion_MateCodeUY_2026.pdf"
NAVY, TEAL, LIME = colors.HexColor("#122B3A"), colors.HexColor("#176B70"), colors.HexColor("#C8ED6B")
INK, MUTED, PALE = colors.HexColor("#18303A"), colors.HexColor("#64746F"), colors.HexColor("#F4F6F2")
LOGO = ROOT / "public/assets/images/anep-utu-logo.jpg"
S = getSampleStyleSheet()
S.add(ParagraphStyle(name="BodyX", parent=S["BodyText"], fontName="Helvetica", fontSize=9, leading=13, textColor=INK, spaceAfter=5))
S.add(ParagraphStyle(name="H1X", parent=S["Heading1"], fontName="Helvetica-Bold", fontSize=20, leading=24, textColor=NAVY, spaceAfter=9))
S.add(ParagraphStyle(name="H2X", parent=S["Heading2"], fontName="Helvetica-Bold", fontSize=12, leading=15, textColor=TEAL, spaceBefore=7, spaceAfter=5))
S.add(ParagraphStyle(name="CellX", parent=S["BodyText"], fontName="Helvetica", fontSize=7.6, leading=9.4, textColor=INK))
S.add(ParagraphStyle(name="HeadX", parent=S["BodyText"], fontName="Helvetica-Bold", fontSize=7.6, leading=9.4, textColor=colors.white))
S.add(ParagraphStyle(name="CoverX", parent=S["Title"], fontName="Helvetica-Bold", fontSize=26, leading=31, alignment=TA_CENTER, textColor=colors.white))

def para(t, style="BodyX"):
    return Paragraph(t, S[style])

def table(headers, rows, widths):
    data = [[para(x,"HeadX") for x in headers]] + [[para(str(x),"CellX") for x in row] for row in rows]
    t = Table(data, colWidths=widths, repeatRows=1)
    t.setStyle(TableStyle([("BACKGROUND",(0,0),(-1,0),NAVY),("GRID",(0,1),(-1,-1),.35,colors.HexColor("#D7E1DD")),("VALIGN",(0,0),(-1,-1),"TOP"),("LEFTPADDING",(0,0),(-1,-1),6),("RIGHTPADDING",(0,0),(-1,-1),6),("TOPPADDING",(0,0),(-1,-1),5),("BOTTOMPADDING",(0,0),(-1,-1),5),("ROWBACKGROUNDS",(0,1),(-1,-1),[colors.white,PALE])]))
    return t

def footer(c, d):
    if d.page == 1 and LOGO.exists():
        c.saveState()
        c.drawImage(ImageReader(str(LOGO)), 20*mm, A4[1]-34*mm, width=170*mm, height=19*mm, preserveAspectRatio=True, anchor="c")
        c.restoreState()
    if d.page > 1:
        c.saveState(); c.setStrokeColor(colors.HexColor("#D7E1DD")); c.line(17*mm,11*mm,193*mm,11*mm)
        c.setFont("Helvetica",7); c.setFillColor(MUTED); c.drawString(17*mm,7*mm,"MATECODEUY | DOCUMENTO DE GESTIÓN | 9 NOV 2026"); c.drawRightString(193*mm,7*mm,str(d.page-1)); c.restoreState()

def build():
    story=[]
    cover=Table([[para("MATECODEUY<br/><br/>DOCUMENTO DE<br/>GESTIÓN DEL EQUIPO", "CoverX")],[para("Proyecto de egreso 2026<br/>Sistema web de reservas para Seberih<br/><br/>Geisson De Castro · Gonzalo Ortega · Lucas Alfonzo · Alejo Montenegro<br/><br/>Escuela Técnica de Sauce · DGETP - UTU<br/><br/>Documento de gestión revisado · 9 de noviembre de 2026<br/><br/><font size='8'>Documento: Documento_de_Gestion_MateCodeUY_2026.pdf</font>","BodyX")]], colWidths=[170*mm], rowHeights=[90*mm,112*mm])
    cover.setStyle(TableStyle([("BACKGROUND",(0,0),(-1,0),NAVY),("BACKGROUND",(0,1),(-1,1),colors.HexColor("#F4F6F2")),("VALIGN",(0,0),(-1,-1),"MIDDLE"),("ALIGN",(0,0),(-1,-1),"CENTER"),("LEFTPADDING",(0,0),(-1,-1),15),("RIGHTPADDING",(0,0),(-1,-1),15)]))
    story += [Spacer(1,35*mm),cover,PageBreak()]
    story += [para("Documento de gestión del equipo","H1X"),para("Este documento reúne la organización interna, responsabilidades, calendario y registros de seguimiento del equipo. Los campos personales y las actas deben ser revisados y firmados por sus integrantes; las fechas aproximadas se incluyen como guía documental, no como prueba de reuniones que no estén confirmadas.")]
    story += [para("1. Presentación e identidad","H2X"),para("MateCodeUY es el nombre de trabajo de un equipo de estudiantes que desarrolla un sistema web para el complejo deportivo Seberih. La propuesta de valor es ordenar la consulta y gestión de reservas en una herramienta accesible desde computadora, tablet y celular. Para el ejercicio de Emprendedurismo se plantea una SRL; no significa que la sociedad esté constituida ni que el nombre esté registrado.")]
    story += [para("Identidad visual","H2X"),para("La marca combina las iniciales MC en un emblema circular con tonos azul oscuro, verde azulado y lima. El círculo comunica continuidad y colaboración; el contraste busca transmitir tecnología y actividad deportiva. La maqueta digital de logo 3D acompaña esta identidad, pero su impresión física debe realizarse y documentarse por separado.")]
    story += [para("2. Organización y responsabilidades","H2X"),table(["Integrante","Rol de trabajo","Responsabilidades","Dato personal"],[
        ["Gonzalo Ortega","Coordinación","Seguimiento general, priorización y coordinación de programación.","CI / teléfono / correo: completar"],
        ["Geisson De Castro","Suplencia / backend-ASO","Desarrollo de servidor, persistencia y preparación operativa.","CI / teléfono / correo: completar"],
        ["Lucas Alfonzo","Frontend / UI-UX","Interfaz, experiencia de usuario y adaptación responsive.","CI / teléfono / correo: completar"],
        ["Alejo Montenegro","QA / documentación","Diseño de pruebas, actas y apoyo al seguimiento.","CI / teléfono / correo: completar"],
    ],[31*mm,34*mm,70*mm,35*mm])]
    story += [Spacer(1,7),para("La estructura es funcional y liviana: coordinación distribuye prioridades; cada responsable desarrolla su área; las revisiones se hacen entre pares. Los roles se basan en el borrador disponible y deben confirmarse por el grupo antes de firmar.")]
    story += [para("3. Forma de trabajo","H2X"),para("Se propone un ciclo ágil sencillo con iteraciones semanales: acordar objetivo y responsable, dividir tareas, trabajar en ramas/commits, revisar cambios, integrar y registrar bloqueos. La revisión semanal permite ajustar prioridades con el tutor y validar reglas con Seberih. GitHub sirve como registro del código y cambios; el canal cotidiano de coordinación debe completarse con el medio realmente utilizado por el equipo.")]
    story += [table(["Acuerdo de trabajo propuesto","Aplicación"],[
        ["Tareas visibles","Mantener lista con prioridad, responsable y estado; no iniciar trabajo sin describir el resultado esperado."],
        ["Cambios trazables","Guardar cada modificación relevante en Git con mensaje claro y revisar antes de integrar."],
        ["Comunicación","Informar bloqueos y cambios de alcance en el canal grupal acordado; registrar decisiones en acta."],
        ["Calidad","Revisar la función en conjunto; separar pruebas diseñadas de pruebas ejecutadas y dejar evidencia."],
        ["Datos y privacidad","No incluir CI, teléfonos, contraseñas ni credenciales en el repositorio o documentos públicos."],
    ],[43*mm,127*mm]),PageBreak()]
    story += [para("4. Calendario del proyecto","H1X"),para("Las fechas académicas proceden de la pauta. Las fechas de reuniones internas se muestran como aproximadas y deben validarse con el equipo. No se afirma que una actividad se completó solo porque aparezca en el calendario.")]
    story += [table(["Fecha / período","Hito o actividad","Evidencia esperada","Estado documental"],[
        ["27 abr 2026","Carta de presentación del proyecto","Carta y datos confirmados del grupo.","Hito de pauta; confirmar envío"],
        ["4 may 2026","Conformación del grupo","Integrantes, roles y contacto verificados.","Hito de pauta; completar datos"],
        ["10 may 2026 (aprox.)","Reunión inicial y roles","Acta revisada y firmas.","Fecha anotada en borrador; validar"],
        ["24 may 2026 (aprox.)","Inicio de programación / datos","Acta con asistentes y tareas acordadas.","Fecha reconstruida; validar"],
        ["8 jun 2026","Primera entrega","Requisitos, análisis y diseño pedidos.","Hito de pauta; evidencias por reunir"],
        ["jun–ago 2026 (aprox.)","Construcción y revisión del prototipo","Código, capturas y revisión de requisitos.","Completar retrospectiva real"],
        ["7 sep 2026","Segunda entrega","Avance de implementación y documentación.","Hito de pauta; evidencias por reunir"],
        ["sep–oct 2026 (aprox.)","Pruebas, operación y documentación","Casos ejecutados, manuales y revisión.","No declarar ejecutado sin evidencia"],
        ["9 nov 2026","Tercera entrega documental","Informe integral y gestión actualizados.","Fecha de revisión de estos archivos"],
        ["23 nov 2026","Defensa del proyecto","Presentación de hasta 60 minutos y demo.","Hito futuro de pauta"],
    ],[34*mm,48*mm,61*mm,27*mm])]
    story += [Spacer(1,7),para("5. Reuniones y actas","H2X"),para("Los siguientes registros son borradores con fecha aproximada, preparados a partir del proyecto y las notas existentes. El equipo debe confirmar asistentes, decisiones y responsables. No se atribuyen citas textuales ni entrevistas al cliente sin una fuente real.")]
    story += [table(["Registro","Contenido para validar"],[
        ["Acta 1 · 10/05/2026 (aprox.)","Tema: inicio del proyecto, revisión de pauta, elección del sistema de reservas y distribución inicial de tareas. Borrador previo menciona a Gonzalo Ortega, Geisson De Castro, Lucas Alfonzo y Alejo Montenegro. Confirmar asistencia, acuerdos y firmas."],
        ["Acta 2 · 24/05/2026 (aprox.)","Tema propuesto: comienzo de programación y modelo de datos; organización de pendientes. Confirmar si ocurrió, fecha, participantes y decisiones antes de presentarla como acta definitiva."],
        ["Consulta con Seberih · fecha a completar","Necesidad que se busca atender: ordenar consultas y reservas de dos canchas. Validar con el propietario horarios, tarifa, anticipo, cancelación, devolución, datos a solicitar y responsable de administración. No incorporar respuestas hasta tener registro real."],
        ["Tutoría · fecha a completar","Revisar alcance por asignatura, entregables, formato de actas y evidencias requeridas. Completar con notas reales del encuentro."],
    ],[46*mm,124*mm]),PageBreak()]
    story += [para("6. Seguimiento y métricas","H1X"),para("Estas métricas son propuestas de seguimiento. No representan resultados medidos a la fecha; el equipo debería registrar su valor al cierre de cada iteración.")]
    story += [table(["Indicador","Cómo calcularlo","Meta propuesta","Frecuencia"],[
        ["Avance del plan","Tareas terminadas / tareas comprometidas en la iteración.","Revisar desvíos; no usar una meta sin estimación inicial.","Semanal"],
        ["Trazabilidad","Requisitos con tarea y evidencia / requisitos totales.","100% de requisitos de entrega vinculados.","Por hito"],
        ["Defectos abiertos","Incidencias abiertas por severidad y antigüedad.","Sin defectos críticos al presentar una demo.","Semanal"],
        ["Revisión de cambios","Cambios integrados con revisión / cambios integrados.","Revisar todos los cambios que afecten datos, acceso o pagos.","Por iteración"],
        ["Participación","Asistencia y tareas asumidas por integrante.","Distribución equilibrada; revisar causas de desbalance.","Mensual"],
    ],[35*mm,65*mm,51*mm,19*mm])]
    story += [Spacer(1,9),para("7. Riesgos de equipo y respuestas","H2X"),table(["Riesgo","Respuesta de gestión"],[
        ["Cambios de alcance tardíos","Registrar solicitud, impacto, responsable y aprobación del tutor/cliente."],
        ["Ausencia o concentración de tareas","Documentar pasos, compartir conocimiento y mantener suplente por área."],
        ["Fechas o actas incompletas","Confirmar con asistentes; marcar ‘pendiente’ en vez de crear evidencia ficticia."],
        ["Datos personales publicados","Usar campos privados en el ejemplar firmado; excluirlos de repositorios y copias abiertas."],
        ["Desacuerdo sobre reglas comerciales","Pedir aprobación escrita al propietario antes de activar tarifas o pagos."],
    ],[52*mm,118*mm])]
    story += [Spacer(1,8),para("8. Preparación para la defensa","H2X"),para("La pauta prevé una defensa de 60 minutos. Se sugiere repartir exposición entre integrantes, mostrar el objetivo y alcance, explicar arquitectura y persistencia, recorrer una reserva de demostración, presentar operación Docker, explicar pruebas diseñadas y pendientes, y cerrar con próximos pasos. Antes de la defensa: comprobar que la demo funciona sin conexión a datos personales, preparar capturas o video de respaldo y acordar quién responde cada tema.")]
    story += [para("Lista de cierre","H2X"),table(["Completar antes de entregar"],[
        ["Confirmar datos oficiales del grupo y firmar las actas reales."],
        ["Validar fechas aproximadas y reemplazarlas si existe calendario real."],
        ["Registrar entrevistas o consultas reales sin inventar respuestas."],
        ["Obtener confirmación de Seberih sobre reglas y tarifas."],
        ["Adjuntar evidencias auténticas de reuniones, pruebas y entregas."],
        ["Mantener los datos personales en una versión de acceso restringido."],
    ],[170*mm])]
    story += [PageBreak(),para("Validación y firmas","H1X"),para("Al firmar, el equipo confirma que revisó la organización y las fechas de este documento, y que corregirá cualquier dato que no refleje su trabajo real. Completar las fechas y firmas solo después de validar el contenido.")]
    story += [Spacer(1,12),para("Aprobación del equipo (completar a mano)","H2X"),table(["Integrante","Firma","Fecha"],[
        ["Geisson De Castro","________________________","____________"],
        ["Gonzalo Ortega","________________________","____________"],
        ["Lucas Alfonzo","________________________","____________"],
        ["Alejo Montenegro","________________________","____________"],
    ],[52*mm,75*mm,43*mm])]
    OUT.parent.mkdir(parents=True,exist_ok=True)
    SimpleDocTemplate(str(OUT),pagesize=A4,rightMargin=20*mm,leftMargin=20*mm,topMargin=18*mm,bottomMargin=17*mm,title="Documento de Gestión MateCodeUY 2026",author="Equipo MateCodeUY").build(story,onFirstPage=footer,onLaterPages=footer)
    print(OUT)

if __name__ == "__main__": build()

from pathlib import Path

from reportlab.lib import colors
from reportlab.lib.enums import TA_CENTER, TA_LEFT
from reportlab.lib.pagesizes import A4
from reportlab.lib.styles import ParagraphStyle, getSampleStyleSheet
from reportlab.lib.units import mm
from reportlab.platypus import (
    BaseDocTemplate,
    Flowable,
    Frame,
    PageBreak,
    PageTemplate,
    Paragraph,
    Spacer,
    Table,
    TableStyle,
)
from reportlab.pdfbase import pdfmetrics
from reportlab.pdfbase.ttfonts import TTFont


ROOT = Path(__file__).resolve().parents[1]
OUTPUT = ROOT / "output" / "pdf" / "Proyecto_Egreso_MateCodeUY_Seberih_2026.pdf"
PAGE_W, PAGE_H = A4
NAVY = colors.HexColor("#122B3A")
INK = colors.HexColor("#18303A")
TEAL = colors.HexColor("#176B70")
LIME = colors.HexColor("#C8ED6B")
GOLD = colors.HexColor("#EAB85A")
PAPER = colors.HexColor("#F4F6F2")
PALE = colors.HexColor("#E8EFEB")
MIST = colors.HexColor("#D7E1DD")
MUTED = colors.HexColor("#64746F")
WHITE = colors.white
RED = colors.HexColor("#A94442")


FONT_REGULAR = Path(r"C:\Windows\Fonts\arial.ttf")
FONT_BOLD = Path(r"C:\Windows\Fonts\arialbd.ttf")
FONT_ITALIC = Path(r"C:\Windows\Fonts\ariali.ttf")
if FONT_REGULAR.exists() and FONT_BOLD.exists():
    pdfmetrics.registerFont(TTFont("ProjectSans", str(FONT_REGULAR)))
    pdfmetrics.registerFont(TTFont("ProjectSans-Bold", str(FONT_BOLD)))
    if FONT_ITALIC.exists():
        pdfmetrics.registerFont(TTFont("ProjectSans-Italic", str(FONT_ITALIC)))
    FONT = "ProjectSans"
    FONT_BOLD_NAME = "ProjectSans-Bold"
    FONT_ITALIC_NAME = "ProjectSans-Italic" if FONT_ITALIC.exists() else "ProjectSans"
else:
    FONT = "Helvetica"
    FONT_BOLD_NAME = "Helvetica-Bold"
    FONT_ITALIC_NAME = "Helvetica-Oblique"


styles = getSampleStyleSheet()
styles.add(ParagraphStyle(
    name="BodyProject", parent=styles["BodyText"], fontName=FONT,
    fontSize=8.7, leading=12.1, textColor=INK, spaceAfter=5,
))
styles.add(ParagraphStyle(
    name="BodyCompact", parent=styles["BodyProject"], fontSize=7.7,
    leading=10.2, spaceAfter=3,
))
styles.add(ParagraphStyle(
    name="BodySmall", parent=styles["BodyProject"], fontSize=6.8,
    leading=8.7, spaceAfter=1.5,
))
styles.add(ParagraphStyle(
    name="PageTitle", fontName=FONT_BOLD_NAME, fontSize=21,
    leading=25, textColor=NAVY, spaceAfter=5,
))
styles.add(ParagraphStyle(
    name="PageSubtitle", fontName=FONT, fontSize=9,
    leading=12.2, textColor=MUTED, spaceAfter=12,
))
styles.add(ParagraphStyle(
    name="SectionTitle", fontName=FONT_BOLD_NAME, fontSize=12.3,
    leading=15, textColor=TEAL, spaceBefore=5, spaceAfter=5,
))
styles.add(ParagraphStyle(
    name="Subhead", fontName=FONT_BOLD_NAME, fontSize=9.2,
    leading=11.5, textColor=NAVY, spaceBefore=3, spaceAfter=3,
))
styles.add(ParagraphStyle(
    name="TableHeader", fontName=FONT_BOLD_NAME, fontSize=7.2,
    leading=8.5, textColor=WHITE,
))
styles.add(ParagraphStyle(
    name="TableBody", fontName=FONT, fontSize=7.1,
    leading=9.0, textColor=INK,
))
styles.add(ParagraphStyle(
    name="TableBodySmall", fontName=FONT, fontSize=6.4,
    leading=7.8, textColor=INK,
))
styles.add(ParagraphStyle(
    name="Callout", fontName=FONT, fontSize=8.3,
    leading=11.3, textColor=NAVY,
))
styles.add(ParagraphStyle(
    name="CoverKicker", fontName=FONT_BOLD_NAME, fontSize=9,
    leading=12, textColor=LIME, alignment=TA_CENTER, tracking=1.2,
))
styles.add(ParagraphStyle(
    name="CoverTitle", fontName=FONT_BOLD_NAME, fontSize=26,
    leading=31, textColor=WHITE, alignment=TA_CENTER,
))
styles.add(ParagraphStyle(
    name="CoverSubtitle", fontName=FONT, fontSize=13,
    leading=18, textColor=colors.HexColor("#D9E5E1"), alignment=TA_CENTER,
))
styles.add(ParagraphStyle(
    name="CoverMeta", fontName=FONT, fontSize=8.5,
    leading=13, textColor=INK, alignment=TA_CENTER,
))
styles.add(ParagraphStyle(
    name="Index", fontName=FONT, fontSize=8.1,
    leading=11.3, textColor=INK,
))
styles.add(ParagraphStyle(
    name="CenteredSmall", fontName=FONT, fontSize=7,
    leading=9, textColor=MUTED, alignment=TA_CENTER,
))


def p(text, style="BodyProject"):
    return Paragraph(text, styles[style])


def title(section, heading, subtitle=None):
    result = [p(section.upper(), "BodySmall"), p(heading, "PageTitle")]
    if subtitle:
        result.append(p(subtitle, "PageSubtitle"))
    result.append(Spacer(1, 2))
    return result


def h2(text):
    return p(text, "SectionTitle")


def h3(text):
    return p(text, "Subhead")


def callout(text, background=PALE, accent=TEAL):
    box = Table([[p(text, "Callout")]], colWidths=[PAGE_W - 2 * 17 * mm])
    box.setStyle(TableStyle([
        ("BACKGROUND", (0, 0), (-1, -1), background),
        ("BOX", (0, 0), (-1, -1), 0.5, MIST),
        ("LINEBEFORE", (0, 0), (0, -1), 3, accent),
        ("LEFTPADDING", (0, 0), (-1, -1), 10),
        ("RIGHTPADDING", (0, 0), (-1, -1), 10),
        ("TOPPADDING", (0, 0), (-1, -1), 8),
        ("BOTTOMPADDING", (0, 0), (-1, -1), 8),
    ]))
    return box


def make_table(headers, rows, widths, compact=False, row_background=True):
    header_style = "TableHeader"
    cell_style = "TableBodySmall" if compact else "TableBody"
    data = [[p(str(value), header_style) for value in headers]]
    data.extend([[p(str(value), cell_style) for value in row] for row in rows])
    table = Table(data, colWidths=widths, repeatRows=1, hAlign="LEFT")
    commands = [
        ("BACKGROUND", (0, 0), (-1, 0), NAVY),
        ("VALIGN", (0, 0), (-1, -1), "TOP"),
        ("LEFTPADDING", (0, 0), (-1, -1), 6),
        ("RIGHTPADDING", (0, 0), (-1, -1), 6),
        ("TOPPADDING", (0, 0), (-1, -1), 5),
        ("BOTTOMPADDING", (0, 0), (-1, -1), 5),
        ("LINEBELOW", (0, 0), (-1, 0), 0.6, TEAL),
        ("GRID", (0, 1), (-1, -1), 0.35, MIST),
    ]
    if row_background:
        for row_index in range(1, len(data)):
            if row_index % 2 == 0:
                commands.append(("BACKGROUND", (0, row_index), (-1, row_index), PAPER))
    table.setStyle(TableStyle(commands))
    return table


class ArchitectureDiagram(Flowable):
    def wrap(self, avail_width, avail_height):
        self.width = avail_width
        self.height = 215
        return self.width, self.height

    def draw(self):
        c = self.canv
        w = self.width
        c.setFillColor(PAPER)
        c.roundRect(0, 0, w, self.height, 10, fill=1, stroke=0)
        c.setFillColor(NAVY)
        c.setFont(FONT_BOLD_NAME, 9)
        c.drawString(12, self.height - 18, "Arquitectura lógica del prototipo")

        boxes = [
            (12, 68, 94, 94, "CLIENTE", ["Navegador", "HTML / CSS", "JavaScript"]),
            (130, 31, 176, 160, "APLICACIÓN PHP", []),
            (337, 101, 145, 76, "DATOS", ["MariaDB 11.4", "Usuarios y pagos"]),
            (337, 24, 145, 60, "MODO DEMO", ["storage/reservations.json", "Sin cobro en local"]),
        ]
        for x, y, bw, bh, label, lines in boxes:
            color = TEAL if label == "APLICACIÓN PHP" else NAVY
            c.setFillColor(WHITE)
            c.setStrokeColor(MIST)
            c.roundRect(x, y, bw, bh, 7, fill=1, stroke=1)
            c.setFillColor(color)
            c.roundRect(x, y + bh - 23, bw, 23, 7, fill=1, stroke=0)
            c.rect(x, y + bh - 23, bw, 6, fill=1, stroke=0)
            c.setFillColor(WHITE)
            c.setFont(FONT_BOLD_NAME, 7.1)
            c.drawCentredString(x + bw / 2, y + bh - 15, label)
            if label == "APLICACIÓN PHP":
                steps = [
                    "Front Controller: recibe HTTP",
                    "Router: URL -> destino",
                    "Controller: valida y responde",
                    "Application: coordina la acción",
                    "BookingPolicy: aplica reglas",
                    "Persistencia: PDO / JSON demo",
                ]
                row_height = 17
                gap = 3
                top = y + bh - 29
                for index, step in enumerate(steps):
                    row_y = top - (index + 1) * row_height - index * gap + 5
                    c.setFillColor(PAPER if index % 2 == 0 else WHITE)
                    c.setStrokeColor(MIST)
                    c.roundRect(x + 7, row_y, bw - 14, row_height, 4, fill=1, stroke=1)
                    c.setFillColor(INK)
                    c.setFont(FONT_BOLD_NAME if index in (0, 3, 4, 5) else FONT, 6.15)
                    c.drawCentredString(x + bw / 2, row_y + 5.8, step)
            else:
                c.setFillColor(INK)
                c.setFont(FONT, 6.5)
                start_y = y + bh - 36
                for index, line in enumerate(lines):
                    c.drawCentredString(x + bw / 2, start_y - index * 12, line)

        c.setStrokeColor(GOLD)
        c.setFillColor(GOLD)
        c.setLineWidth(1.5)
        self._arrow(108, 115, 128, 115)
        self._arrow(307, 133, 335, 143)
        self._arrow(307, 81, 335, 57)
        c.setFont(FONT, 6.2)
        c.setFillColor(MUTED)
        c.drawCentredString(220, 13, "Entorno local: Docker Compose, red privada y volumen persistente de MariaDB")

    def _arrow(self, x1, y1, x2, y2):
        c = self.canv
        c.line(x1, y1, x2, y2)
        c.saveState()
        c.translate(x2, y2)
        angle = __import__("math").atan2(y2 - y1, x2 - x1)
        c.rotate(angle * 180 / __import__("math").pi)
        c.line(0, 0, -6, 3)
        c.line(0, 0, -6, -3)
        c.restoreState()


class EntityDiagram(Flowable):
    def wrap(self, avail_width, avail_height):
        self.width = avail_width
        self.height = 177
        return self.width, self.height

    def draw(self):
        c = self.canv
        w = self.width
        c.setFillColor(PAPER)
        c.roundRect(0, 0, w, self.height, 10, fill=1, stroke=0)
        c.setFillColor(NAVY)
        c.setFont(FONT_BOLD_NAME, 9)
        c.drawString(12, self.height - 18, "Modelo de datos implementado")
        boxes = [
            (12, 40, 138, 99, "users", ["id (PK)", "full_name, email", "phone, password_hash"]),
            (182, 25, 186, 123, "reservations", ["id (PK)", "user_id (FK)", "court_id, starts_at, ends_at", "total, deposit, status", "payment ids, expires_at"]),
            (400, 40, 94, 99, "reservation_slots", ["court_id + slot_start", "(PK compuesta)", "reservation_id (FK)"]),
        ]
        for x, y, bw, bh, label, lines in boxes:
            c.setFillColor(WHITE)
            c.setStrokeColor(MIST)
            c.roundRect(x, y, bw, bh, 6, fill=1, stroke=1)
            c.setFillColor(TEAL)
            c.roundRect(x, y + bh - 21, bw, 21, 6, fill=1, stroke=0)
            c.rect(x, y + bh - 21, bw, 5, fill=1, stroke=0)
            c.setFillColor(WHITE)
            c.setFont(FONT_BOLD_NAME, 7.3)
            c.drawCentredString(x + bw / 2, y + bh - 14, label)
            c.setFillColor(INK)
            c.setFont(FONT, 6.2)
            for index, line in enumerate(lines):
                c.drawCentredString(x + bw / 2, y + bh - 35 - index * 12, line)
        c.setStrokeColor(GOLD)
        c.setFillColor(GOLD)
        c.setLineWidth(1.5)
        self._arrow(151, 95, 180, 95)
        self._arrow(370, 95, 398, 95)
        c.setFont(FONT, 6.2)
        c.setFillColor(MUTED)
        c.drawCentredString(165, 104, "1:N")
        c.drawCentredString(384, 104, "1:N")

    def _arrow(self, x1, y1, x2, y2):
        c = self.canv
        c.line(x1, y1, x2, y2)
        c.line(x2, y2, x2 - 6, y2 + 3)
        c.line(x2, y2, x2 - 6, y2 - 3)


class BookingFlow(Flowable):
    def wrap(self, avail_width, avail_height):
        self.width = avail_width
        self.height = 92
        return self.width, self.height

    def draw(self):
        c = self.canv
        w = self.width
        labels = [
            ("01", "Consultar", "cancha, fecha y horas"),
            ("02", "Validar", "cuenta y disponibilidad"),
            ("03", "Reservar", "archivo demo o turno pendiente"),
            ("04", "Confirmar", "sin cobro demo / webhook real"),
        ]
        gap = 17
        bw = (w - gap * 3) / 4
        for index, (number, heading, body) in enumerate(labels):
            x = index * (bw + gap)
            c.setFillColor(PAPER)
            c.setStrokeColor(MIST)
            c.roundRect(x, 10, bw, 72, 7, fill=1, stroke=1)
            c.setFillColor(TEAL if index < 3 else NAVY)
            c.circle(x + 17, 64, 8, fill=1, stroke=0)
            c.setFillColor(WHITE)
            c.setFont(FONT_BOLD_NAME, 5.3)
            c.drawCentredString(x + 17, 62, number)
            c.setFillColor(INK)
            c.setFont(FONT_BOLD_NAME, 7.1)
            c.drawString(x + 31, 61, heading)
            c.setFont(FONT, 6.3)
            c.setFillColor(MUTED)
            self._wrap_line(body, x + 10, 41, bw - 18)
            if index < 3:
                c.setStrokeColor(GOLD)
                c.setFillColor(GOLD)
                x1 = x + bw + 2
                x2 = x + bw + gap - 2
                c.line(x1, 45, x2, 45)
                c.line(x2, 45, x2 - 4, 48)
                c.line(x2, 45, x2 - 4, 42)

    def _wrap_line(self, text, x, y, max_width):
        c = self.canv
        words = text.split()
        line = ""
        lines = []
        for word in words:
            candidate = f"{line} {word}".strip()
            if pdfmetrics.stringWidth(candidate, FONT, 6.3) <= max_width:
                line = candidate
            else:
                lines.append(line)
                line = word
        if line:
            lines.append(line)
        for index, item in enumerate(lines[:3]):
            c.drawCentredString(x + max_width / 2, y - index * 10, item)


class ProjectDoc(BaseDocTemplate):
    def __init__(self, filename):
        super().__init__(
            str(filename), pagesize=A4,
            leftMargin=17 * mm, rightMargin=17 * mm,
            topMargin=18 * mm, bottomMargin=17 * mm,
            title="Proyecto de Egreso 2026 - MateCodeUY y Seberih",
            author="Equipo de Proyecto MateCodeUY",
            subject="Sistema web de gestión y reservas de canchas de fútbol 5",
        )
        frame = Frame(
            self.leftMargin, self.bottomMargin,
            self.width, self.height,
            leftPadding=0, rightPadding=0, topPadding=1, bottomPadding=0,
            id="main",
        )
        self.addPageTemplates(PageTemplate(id="project", frames=[frame], onPage=self._draw_page))

    def _draw_page(self, canvas, doc):
        canvas.saveState()
        if doc.page == 1:
            canvas.setFillColor(NAVY)
            canvas.rect(0, 0, PAGE_W, PAGE_H, fill=1, stroke=0)
            canvas.setFillColor(TEAL)
            canvas.rect(0, 0, PAGE_W, 14 * mm, fill=1, stroke=0)
            canvas.setFillColor(LIME)
            canvas.rect(0, PAGE_H - 7 * mm, PAGE_W, 7 * mm, fill=1, stroke=0)
            canvas.setFillColor(GOLD)
            canvas.circle(PAGE_W / 2, PAGE_H - 83 * mm, 32 * mm, fill=0, stroke=1)
            canvas.setLineWidth(2)
            canvas.setStrokeColor(LIME)
            canvas.circle(PAGE_W / 2, PAGE_H - 83 * mm, 27 * mm, fill=0, stroke=1)
            canvas.setFillColor(LIME)
            canvas.setFont(FONT_BOLD_NAME, 27)
            canvas.drawCentredString(PAGE_W / 2, PAGE_H - 86 * mm, "MC")
            canvas.setFont(FONT_BOLD_NAME, 9)
            canvas.setFillColor(WHITE)
            canvas.drawCentredString(PAGE_W / 2, PAGE_H - 119 * mm, "MATECODEUY")
            canvas.setFont(FONT, 7)
            canvas.setFillColor(colors.HexColor("#C8D8D4"))
            canvas.drawCentredString(PAGE_W / 2, PAGE_H - 125 * mm, "PROPUESTA ACADÉMICA DE EMPRENDIMIENTO")
            canvas.setStrokeColor(GOLD)
            canvas.setLineWidth(1)
            canvas.line(62 * mm, PAGE_H - 134 * mm, PAGE_W - 62 * mm, PAGE_H - 134 * mm)
            draw_cover_text(canvas, doc)
            canvas.restoreState()
            return

        canvas.setStrokeColor(MIST)
        canvas.setLineWidth(0.6)
        canvas.line(self.leftMargin, PAGE_H - 12 * mm, PAGE_W - self.rightMargin, PAGE_H - 12 * mm)
        canvas.setFont(FONT_BOLD_NAME, 6.8)
        canvas.setFillColor(TEAL)
        canvas.drawString(self.leftMargin, PAGE_H - 9 * mm, "MATECODEUY | PROYECTO DE EGRESO 2026")
        canvas.setFont(FONT, 6.8)
        canvas.setFillColor(MUTED)
        canvas.drawRightString(PAGE_W - self.rightMargin, PAGE_H - 9 * mm, "SEBERIH · SAUCE, CANELONES")
        canvas.line(self.leftMargin, 11 * mm, PAGE_W - self.rightMargin, 11 * mm)
        canvas.setFont(FONT, 6.5)
        canvas.setFillColor(MUTED)
        canvas.drawString(self.leftMargin, 7.5 * mm, "Versión revisada: 5 de noviembre de 2026")
        canvas.drawRightString(PAGE_W - self.rightMargin, 7.5 * mm, f"Página {doc.page - 1}")
        canvas.restoreState()


def build_story():
    story = [PageBreak()]

    # Page 2 - document control, summary, contents.
    story += title("Nuestro proyecto", "De qué se trata", "Una mirada clara al trabajo que estamos haciendo para Seberih.")
    story.append(make_table(
        ["Dato", "Definición"],
        [
            ["Institución", "ANEP - DGETP, Escuela Técnica de Sauce, Canelones"],
            ["Curso", "3.º MB - Bachillerato Tecnológico en Tecnologías de la Información"],
            ["Año lectivo", "2026"],
            ["Equipo desarrollador", "MateCodeUY; forma jurídica propuesta: SRL"],
            ["Cliente piloto", "Complejo deportivo Seberih, Sauce, Canelones"],
            ["Producto", "Sistema web de gestión y reservas para dos canchas de fútbol 5"],
        ], [38 * mm, 130 * mm], compact=True,
    ))
    story += [Spacer(1, 8), h2("Resumen ejecutivo")]
    story.append(p(
        "Estamos creando una página web para que Seberih muestre sus dos canchas y sus clientes puedan consultar horarios y hacer una reserva desde una cuenta. Queremos que sea fácil de usar tanto en una computadora como en un celular o una tablet. Para construirla usamos PHP, MariaDB, HTML, CSS y JavaScript; Docker nos ayuda a preparar el entorno donde funciona."
    ))
    story.append(p(
        "Por ahora estamos trabajando con una versión de prueba: las cuentas se guardan en MariaDB y las reservas en un archivo local. Se puede recorrer el proceso y ver cuánto sería la seña, pero no se cobra. Para recibir pagos reales con Mercado Pago todavía necesitamos la cuenta del comercio, una página segura con HTTPS y comprobar cada aviso de pago antes de confirmar una reserva."
    ))
    story.append(callout(
        "En estas páginas contamos qué ya podemos mostrar y qué falta para que el sistema se use con clientes. MateCodeUY SRL es la forma que proponemos para el trabajo de Emprendedurismo; la empresa todavía no está constituida.",
        background=colors.HexColor("#F1F5E3"), accent=GOLD,
    ))
    story += [Spacer(1, 9), h2("Índice")]
    index_rows = [
        ["01", "Empresa, figura propuesta y equipo", "2"],
        ["02", "Problema, alcance y personas interesadas", "3"],
        ["03", "Funciones de la página", "4"],
        ["04", "Calidad y situaciones de uso", "5"],
        ["05", "Riesgos y cómo responder", "6"],
        ["06", "FODA y posibilidades del proyecto", "7"],
        ["07", "Partes de la página", "8"],
        ["08", "Qué datos guardamos y cómo se relacionan", "9-10"],
        ["09", "La prueba y el pago real", "11"],
        ["10", "Preparación y mantenimiento", "12"],
        ["11", "Etapas, aceptación y conclusión", "13"],
        ["12", "Actas y palabras técnicas", "14"],
        ["13", "Fuentes y puntos por confirmar", "15"],
    ]
    story.append(make_table(["N.º", "Sección", "Pág."], index_rows, [14 * mm, 136 * mm, 18 * mm], compact=True))
    story.append(PageBreak())

    # Page 3 - business, legal status, team.
    story += title("01 | Quiénes somos", "MateCodeUY y nuestra propuesta", "Somos un equipo de estudiantes y este es el primer proyecto que desarrollamos para un cliente de nuestra zona.")
    story += [h2("Actividad y propuesta de valor"), p(
        "MateCodeUY es el nombre que elegimos para nuestro emprendimiento en este proyecto. La idea es ayudar a organizaciones de la zona a resolver tareas cotidianas con herramientas digitales sencillas. Nuestro primer trabajo es una página para consultar y reservar las canchas de Seberih."
    ), p(
        "Nuestra misión es acercar soluciones digitales útiles a pequeños emprendimientos. Para lograrlo, queremos escuchar a cada cliente, explicar con claridad cómo funciona el sistema y avanzar paso a paso."
    ), h2("Forma jurídica proyectada: Sociedad de Responsabilidad Limitada")]
    story.append(p(
        "Para la materia Emprendedurismo proponemos que MateCodeUY sea una Sociedad de Responsabilidad Limitada (SRL). Por eso en este trabajo usamos el nombre <b>MateCodeUY SRL</b>. Es una propuesta para el proyecto escolar: todavía no hemos creado ni registrado la empresa, y habría que comprobar si ese nombre está disponible."
    ))
    story.append(p(
        "Si en algún momento quisiéramos poner en marcha la empresa de verdad, habría que preparar el contrato, inscribirla y hacer los trámites correspondientes ante los organismos uruguayos. Antes de iniciar una actividad comercial, deberíamos informarnos con las oficinas y profesionales que correspondan. [1][2]",
        "BodyCompact",
    ))
    story.append(callout(
        "Seberih es el complejo deportivo para el que estamos pensando la solución. MateCodeUY es nuestro equipo y el nombre del emprendimiento que la desarrolla.",
        background=PALE,
    ))
    story += [Spacer(1, 5), h2("Equipo y responsabilidades del proyecto")]
    story.append(make_table(
        ["Integrante", "Responsabilidad documentada"],
        [
            ["Geisson De Castro", "Desarrollo del sistema y trabajo sobre la base de datos, según el acta de avance."],
            ["Gonzalo Ortega", "Coordinación del proyecto y programación junto al responsable de datos."],
            ["Lucas Alfonzo", "Diseño de interfaz y experiencia de usuario; maquetación responsive."],
            ["Alejo Montenegro", "Aseguramiento de calidad, documentación y apoyo en la coordinación de reuniones."],
        ], [43 * mm, 125 * mm], compact=True,
    ))
    story.append(Spacer(1, 4))
    story.append(p("Revisamos los roles con la información que aparece en el borrador y las actas. Antes de entregar, tenemos que confirmar que los nombres y las tareas estén escritos correctamente.", "BodySmall"))
    story.append(PageBreak())

    # Page 4 - problem and scope.
    story += title("02 | La necesidad", "Qué queremos resolver", "La primera versión está pensada para las dos canchas de Seberih.")
    story += [h2("Problema que se busca atender"), p(
        "Cuando los turnos se coordinan a mano, es fácil repetir consultas o confundirse sobre quién reservó y qué horario sigue libre. Queremos reunir esa información en un mismo lugar para que se pueda elegir la cancha, el día, la duración y consultar el estado de la reserva."
    ), h2("Objetivo general"), p(
        "Nuestro objetivo es construir y explicar una página que funcione en distintos dispositivos. Las personas con una cuenta podrán consultar horarios y reservar cualquiera de las dos canchas; el cliente también podrá revisar las reservas recibidas."
    ), h2("Objetivos específicos")]
    story.append(make_table(
        ["Objetivo", "Resultado esperado"],
        [
            ["Acceso", "Registro con nombre y correo, inicio y cierre de sesión."],
            ["Disponibilidad", "Elección de cancha, fecha dentro de tres meses y duración de una a ocho horas."],
            ["Reserva", "Validación de horarios y asociación del turno con la cuenta autenticada."],
            ["Operación", "Demostración local de consulta, cambio y cancelación de reservas de prueba."],
            ["Preparación", "Base PHP/MariaDB en contenedores, reglas de seguridad y scripts operativos documentados."],
        ], [34 * mm, 134 * mm], compact=True,
    ))
    story += [Spacer(1, 7), h2("Incluido y pendiente")]
    story.append(make_table(
        ["Incluido en el prototipo", "Pendiente para producción"],
        [
            ["Sitio responsive; cuentas; dos canchas; calendario; selección por horas; reservas demo en archivo local.", "Dominio y HTTPS; credenciales de Mercado Pago; webhook público validado; políticas acordadas de devolución y cancelación."],
            ["Panel local para revisar reservas demo; administración restringida al entorno de prueba.", "Panel administrativo completo para tarifas, mantenimiento, reportes e ingresos."],
            ["Entorno Docker Compose con MariaDB persistente y datos locales separados por equipo.", "Definir alojamiento, automatización de respaldos, retención y recuperación ante fallos."],
        ], [84 * mm, 84 * mm], compact=True,
    ))
    story += [Spacer(1, 5), p("Fuera del alcance actual: aplicación móvil nativa, múltiples complejos, analítica financiera avanzada y restablecimiento de contraseña por correo.", "BodySmall")]
    story.append(PageBreak())

    # Page 5 - functional requirements.
    story += title("03 | Lo que hace", "Funciones de la página", "Indicamos qué funciones ya se pueden probar y cuáles necesitan trabajo antes de usarse en serio.")
    functional = [
        ["RF-01", "Cuenta", "Registrar una cuenta con nombre, correo y contraseña; iniciar y cerrar sesión.", "Prototipo"],
        ["RF-02", "Canchas", "Permitir consultar y seleccionar una de las dos canchas configuradas.", "Prototipo"],
        ["RF-03", "Calendario", "Elegir una fecha entre hoy y tres meses desde la fecha actual.", "Prototipo"],
        ["RF-04", "Duración", "Elegir de una a ocho horas consecutivas, sujeto al horario operativo.", "Prototipo"],
        ["RF-05", "Disponibilidad", "Consultar horarios libres por cancha, fecha y duración antes de reservar.", "Prototipo"],
        ["RF-06", "Identidad", "Exigir sesión iniciada y asociar cada reserva a una cuenta concreta.", "Prototipo"],
        ["RF-07", "Precio", "Mostrar tarifa configurada, total estimado y anticipo del 15 por ciento.", "Prototipo"],
        ["RF-08", "Demo", "Guardar una reserva de prueba sin cobrar e informar que la seña es ilustrativa.", "Prototipo"],
        ["RF-09", "Autogestión", "Permitir consultar, cambiar o cancelar reservas demo de la cuenta activa.", "Prototipo"],
        ["RF-10", "Pago", "Crear el pago de la seña y confirmar el turno después de verificarlo con el proveedor.", "Preparado; activar con credenciales y HTTPS"],
        ["RF-11", "Gestión local", "Permitir al propietario revisar reservas demo en el entorno local.", "Prototipo"],
        ["RF-12", "Ubicación", "Mostrar el enlace de ubicación de Seberih para planificar el traslado.", "Prototipo"],
    ]
    story.append(make_table(["ID", "Área", "Requisito", "Estado"], functional, [15 * mm, 25 * mm, 101 * mm, 27 * mm], compact=True))
    story += [Spacer(1, 8), h2("Reglas operativas del prototipo")]
    story.append(p(
        "La tarifa de referencia está configurada en $ 1.200 UYU por hora y cancha, y el anticipo estimado en 15 por ciento ($ 180 UYU por hora). Estos valores son parámetros de demostración y deben ser validados por Seberih antes de publicarse."
    ))
    story.append(p(
        "Los horarios de inicio configurados son 09:00, 10:00, 11:00, 12:00 y de 14:00 a 21:00. La duración elegida debe caber en la franja disponible. El cliente debe confirmar el horario comercial real y días de cierre.", "BodyCompact"
    ))
    story.append(PageBreak())

    # Page 6 - quality requirements and use cases.
    story += title("04 | Cómo debe funcionar", "Calidad y situaciones de uso", "Además de funcionar, la página tiene que ser fácil de entender y cuidar la información de las personas.")
    quality = [
        ["RNF-01", "Adaptabilidad", "La interfaz debe ser legible y operable en celular, tablet y escritorio.", "Diseño implementado; revisión continua."],
        ["RNF-02", "Seguridad de acceso", "Contraseñas con hash; sesiones protegidas; formularios con token CSRF.", "Aplicado en el prototipo."],
        ["RNF-03", "Persistencia", "La base relacional usa transacciones InnoDB; los datos de demo se guardan con bloqueo de archivo.", "Implementado en sus modos respectivos."],
        ["RNF-04", "Integridad de turnos", "Los bloques horarios deben impedir reservas superpuestas para una misma cancha.", "Control de disponibilidad y slots en modo pago; validar con pruebas de carga."],
        ["RNF-05", "Mantenibilidad", "Separar vistas, controladores, reglas de dominio y persistencia.", "Estructura por capas en migración gradual."],
        ["RNF-06", "Despliegue", "Usar contenedores separados, red privada para base de datos y volumen persistente.", "Configurado en Compose."],
        ["RNF-07", "Continuidad", "Documentar respaldo, restauración, registros y permisos mínimos.", "Scripts disponibles; agenda y ensayo de restauración pendientes."],
        ["RNF-08", "Rendimiento", "Definir y medir tiempos y concurrencia con tráfico representativo.", "Meta por establecer; no hay mediciones certificadas."],
    ]
    story.append(make_table(["ID", "Atributo", "Criterio", "Estado"], quality, [16 * mm, 28 * mm, 84 * mm, 40 * mm], compact=True))
    story += [Spacer(1, 8), h2("Casos de uso prioritarios")]
    use_cases = [
        ["CU-01", "Crear cuenta / iniciar sesión", "Cliente", "Nombre y correo válidos; sesión aislada por usuario."],
        ["CU-02", "Consultar disponibilidad", "Visitante o cliente", "Seleccionar cancha, fecha y duración; consultar bloques libres."],
        ["CU-03", "Registrar reserva de prueba", "Cliente autenticado", "Validar disponibilidad; guardar como demo sin cobro."],
        ["CU-04", "Gestionar mis reservas demo", "Cliente autenticado", "Ver, cambiar o cancelar únicamente reservas de la cuenta activa."],
        ["CU-05", "Pagar seña", "Cliente autenticado", "Pendiente de credenciales, HTTPS y webhook de Mercado Pago."],
    ]
    story.append(make_table(["ID", "Caso", "Actor", "Condición / resultado"], use_cases, [16 * mm, 43 * mm, 31 * mm, 78 * mm], compact=True))
    story.append(PageBreak())

    # Page 7 - risks.
    story += title("05 | Cosas a tener en cuenta", "Riesgos y cómo responder", "Pensamos qué podría salir mal y qué podemos hacer para reducir esos problemas.")
    risks = [
        ["R-01", "La cuenta está en MariaDB local y las reservas demo en un archivo; copiar solo el código no transporta los datos.", "Alta", "Alta", "Documentar respaldo y copia íntegra; probar restauración y migrar a almacenamiento administrado antes de operar."],
        ["R-02", "Los pagos reales no pueden confirmarse sin credenciales, HTTPS y notificaciones verificables.", "Media", "Alta", "Mantener el modo demo claramente rotulado; activar producción solo tras prueba de webhooks y conciliación."],
        ["R-03", "Tarifas, horarios, cancelaciones y devoluciones pueden no coincidir con la política de Seberih.", "Media", "Alta", "Obtener validación escrita del cliente y convertirla en reglas visibles y configurables."],
        ["R-04", "Solicitudes simultáneas pueden competir por un mismo horario.", "Media", "Alta", "Usar transacciones y bloqueo de slots; diseñar pruebas concurrentes antes del piloto público."],
        ["R-05", "Falla de electricidad, internet, computadora o contenedor local.", "Media", "Media", "Respaldos fuera del equipo, guía de recuperación y monitoreo de disponibilidad."],
        ["R-06", "Exposición o pérdida de datos de cuentas y reservas.", "Media", "Alta", "Limitar acceso, no versionar secretos, definir retención, aviso de privacidad y proceso de incidentes."],
        ["R-07", "Desacuerdo de alcance o ausencia de un integrante.", "Media", "Media", "Acordar prioridades con el tutor y cliente; mantener actas, responsables y documentación actualizada."],
    ]
    story.append(make_table(["ID", "Riesgo", "Prob.", "Impacto", "Mitigación"], risks, [15 * mm, 69 * mm, 18 * mm, 19 * mm, 47 * mm], compact=True))
    story += [Spacer(1, 8), h2("Seguimiento")]
    story.append(p(
        "En las reuniones iremos repasando estos riesgos. Si cambiamos el precio, la forma de pagar, las cancelaciones o los datos que pedimos, primero tenemos que hablarlo con Seberih y dejarlo anotado."
    ))
    story.append(callout(
        "La versión de prueba guarda la reserva en la computadora donde está funcionando. No realiza cobros ni comparte automáticamente esa información con otros equipos.",
        background=colors.HexColor("#F9EEE9"), accent=RED,
    ))
    story.append(PageBreak())

    # Page 8 - SWOT and viability.
    story += title("06 | Lo que vemos", "FODA y posibilidades del proyecto", "Una primera mirada a los puntos fuertes, los desafíos y lo que todavía necesitamos comprobar.")
    swot = [
        ["FORTALEZAS", "OPORTUNIDADES"],
        ["- Prototipo funcional para dos canchas.\n- Interfaz adaptable y flujo de cuenta.\n- Reglas y persistencia diferenciadas para demo y pago.", "- Validar el flujo con un cliente real.\n- Activar pagos en línea después de acordar condiciones.\n- Adaptar el producto a otros complejos si el piloto funciona."],
        ["DEBILIDADES", "AMENAZAS"],
        ["- Persistencia local dividida entre MariaDB y archivo JSON en demo.\n- Pagos reales sin credenciales/HTTPS activos.\n- Panel de administración y reportes limitados.", "- Fallas de red/energía del equipo anfitrión.\n- Dobles reservas o disputas sobre anticipos.\n- Cambios en requisitos del cliente y protección de datos."],
    ]
    swot_table = Table(
        [[p(x, "TableHeader") for x in swot[0]],
         [p(swot[1][0].replace("\n", "<br/>"), "TableBody"), p(swot[1][1].replace("\n", "<br/>"), "TableBody")],
         [p(x, "TableHeader") for x in swot[2]],
         [p(swot[3][0].replace("\n", "<br/>"), "TableBody"), p(swot[3][1].replace("\n", "<br/>"), "TableBody")]],
        colWidths=[84 * mm, 84 * mm],
    )
    swot_table.setStyle(TableStyle([
        ("BACKGROUND", (0, 0), (-1, 0), TEAL),
        ("BACKGROUND", (0, 2), (-1, 2), NAVY),
        ("BACKGROUND", (0, 1), (-1, 1), PAPER),
        ("BACKGROUND", (0, 3), (-1, 3), PAPER),
        ("GRID", (0, 0), (-1, -1), 0.5, MIST),
        ("VALIGN", (0, 0), (-1, -1), "TOP"),
        ("LEFTPADDING", (0, 0), (-1, -1), 9),
        ("RIGHTPADDING", (0, 0), (-1, -1), 9),
        ("TOPPADDING", (0, 0), (-1, -1), 8),
        ("BOTTOMPADDING", (0, 0), (-1, -1), 8),
    ]))
    story.append(swot_table)
    story += [Spacer(1, 9), h2("Viabilidad")]
    viability = [
        ["Técnica", "La aplicación y el entorno Docker se construyen con tecnologías disponibles y compatibles con el plan PHP/MySQL/Docker. El paso a producción aún requiere validación operativa."],
        ["Operativa", "Seberih puede revisar el prototipo y validar reglas. El uso sostenido requiere responsable de soporte, respaldo probado, dominio y procedimiento de incidencias."],
        ["Económica", "El prototipo usa software de código abierto. No hay presupuesto aprobado para alojamiento, dominio, mantenimiento, comisiones de pago ni constitución de la SRL."],
        ["Comercial", "Existe un cliente piloto mencionado en el proyecto. La demanda y disposición de pago deben confirmarse con el cliente y sus usuarios."],
    ]
    story.append(make_table(["Dimensión", "Evaluación"], viability, [32 * mm, 136 * mm], compact=True))
    story.append(Spacer(1, 5))
    story.append(p("Por ahora vemos el proyecto como una buena base para la entrega y para hacer una prueba controlada con Seberih. Antes de abrirlo al público, aún tenemos que resolver los puntos pendientes de esta evaluación.", "BodyCompact"))
    story.append(PageBreak())

    # Page 9 - architecture.
    story += title("07 | Cómo está hecha", "Partes de la página", "Mostramos cómo se conectan la página, el servidor y los datos que se guardan.")
    story.append(ArchitectureDiagram())
    story += [Spacer(1, 8), h2("Capas y responsabilidades")]
    layer_rows = [
        ["Presentación", "Es lo que ve la persona en el navegador: páginas, formularios y estilos hechos con HTML, CSS y JavaScript. Desde aquí elige cancha, fecha y horario."],
        ["Control HTTP", "El Front Controller recibe la solicitud. El Router busca la URL en la tabla de rutas y la envía al destino correspondiente. Después el controller revisa el método, la sesión y el token CSRF, y prepara la respuesta."],
        ["Aplicación", "Coordina cada acción entre el controller, las reglas y el almacenamiento. Por ejemplo, AuthenticationService registra o autentica cuentas. En reservas, esta separación se está organizando gradualmente."],
        ["Dominio", "Contiene reglas propias del negocio, separadas de la pantalla: BookingPolicy valida fechas, horarios, duración de 1 a 8 horas, límite de tres meses y cálculo de precio y seña."],
        ["Persistencia", "Guarda y recupera datos: PDO conecta con MariaDB para cuentas y el flujo preparado de pago; el modo demo usa un repositorio JSON con bloqueo para las reservas."],
        ["Entorno de desarrollo", "Docker Compose levanta PHP con Apache y MariaDB. Ambos servicios se comunican por una red privada; la base de datos no queda expuesta directamente a Internet."],
    ]
    story.append(make_table(["Capa", "Responsabilidad"], layer_rows, [37 * mm, 131 * mm], compact=True))
    story += [Spacer(1, 6), h2("Qué hace la capa de aplicación")]
    story.append(p(
        "La capa de aplicación conecta lo que pide la persona con las reglas del sistema. Toma la acción y los datos que recibe del controller, invoca el servicio o la política que corresponde y solicita guardar o consultar la información. Luego devuelve un resultado para que el controller responda con un mensaje o datos para la página."
    ))
    story.append(p("Ejemplo: al crear una reserva, se comprueba que la persona inició sesión y envió un formulario válido; se revisan la fecha, la hora y la duración; se consulta la disponibilidad y se calcula el precio y la seña; finalmente, se guarda el turno asociado a esa cuenta. Si algo no cumple las reglas, se devuelve un error y no se guarda la reserva.", "BodyCompact"))
    story.append(p("En el código actual, AuthenticationService ya reúne las acciones de registro e inicio de sesión, y BookingPolicy contiene varias reglas de reserva. Parte del flujo de reservas sigue en funciones de src/reservations.php, así que la separación completa en casos de uso es una mejora pendiente.", "BodyCompact"))
    story.append(p("En el entorno de desarrollo, Docker Compose conecta la aplicación y MariaDB mediante una red privada. Esto permite que PHP acceda a la base de datos sin publicar su puerto para otros equipos.", "BodyCompact"))
    story.append(PageBreak())

    # Page 10 - DB.
    story += title("08 | La información", "Qué datos guardamos", "Estas son las principales piezas de información que necesita el sistema.")
    story.append(EntityDiagram())
    story += [Spacer(1, 7), h2("Cómo se relacionan las tablas")]
    entities = [
        ["users -> reservations", "Una cuenta puede tener varias reservas. Cada reserva guarda user_id como clave foránea para asociarla a su dueño."],
        ["reservations -> reservation_slots", "Una reserva puede ocupar varios bloques horarios. Cada bloque apunta a una reserva; al borrarla, sus bloques se eliminan en cascada."],
        ["reservation_slots: clave compuesta", "court_id + slot_start forman la clave primaria. Así la base impide guardar dos reservas para la misma cancha y hora."],
        ["Índices", "Hay índices para buscar las reservas de una cuenta y para encontrar reservas pendientes que estén por vencer."],
    ]
    story.append(make_table(["Relación / regla", "Qué significa"], entities, [47 * mm, 121 * mm], compact=True))
    story += [Spacer(1, 6), h2("Persistencia por modo")]
    story.append(make_table(
        ["Dato", "Demostración local", "Pago en producción"],
        [
            ["Cuentas", "MariaDB local", "MariaDB administrada por el despliegue"],
            ["Reservas", "storage/reservations.json con bloqueo de archivo", "reservations + reservation_slots en MariaDB"],
            ["Estado", "demo; no representa pago", "pending_payment, confirmed u otros estados controlados"],
            ["Traslado", "Copiar archivo y base/volumen de forma coordinada", "Respaldos y restauración desde la infraestructura"],
        ], [33 * mm, 69 * mm, 66 * mm], compact=True,
    ))
    story.append(Spacer(1, 5))
    story.append(p("En la prueba, las cuentas y las reservas se guardan por separado. Por eso, descargar el código desde GitHub no trae consigo las cuentas ni las reservas que se hayan creado en una computadora.", "BodySmall"))
    story.append(PageBreak())

    # Page 11 - detailed schema and planned extension.
    story += title("08 | La información", "Tablas actuales y mejoras propuestas", "Separamos el esquema que ya existe de las tablas que recomendamos agregar si el proyecto avanza a producción.")
    story.append(p("El esquema SQL actual tiene tres tablas. La primera guarda las cuentas, la segunda las reservas y la tercera bloquea cada hora ocupada. Debajo resumimos sus campos más importantes; PK significa clave primaria y FK, clave foránea.", "BodyCompact"))
    current_schema = [
        ["users", "id BIGINT (PK); full_name VARCHAR(120); email VARCHAR(190), único; phone opcional; password_hash; created_at. La contraseña se guarda como hash, nunca como texto legible."],
        ["reservations", "id CHAR(36) (PK); user_id BIGINT UNSIGNED (FK a users); court_id TINYINT UNSIGNED; starts_at y ends_at DATETIME; total_amount y deposit_amount INT UNSIGNED; currency CHAR(3); status ENUM; payment_preference_id, payment_id; expires_at y created_at."],
        ["reservation_slots", "court_id TINYINT UNSIGNED + slot_start DATETIME (PK compuesta); reservation_id CHAR(36) (FK a reservations). Tiene un índice por reserva y borrado en cascada para liberar sus bloques."],
    ]
    story.append(make_table(["Tabla actual", "Campos y reglas principales"], current_schema, [38 * mm, 130 * mm]))
    story.append(p("Estados de una reserva: pending_payment espera el pago; confirmed indica que fue verificado; demo identifica una prueba sin cobro; payment_failed, expired y cancelled representan un pago fallido, una solicitud vencida o una reserva cancelada.", "BodyCompact"))
    story += [Spacer(1, 7), h2("Tablas que proponemos para una versión más completa")]
    story.append(p("Estas tablas no están creadas todavía. Las proponemos para que las canchas, los pagos y los cambios de una reserva tengan su propio registro y sea más sencillo administrarlos.", "BodyCompact"))
    proposed_schema = [
        ["courts", "id (PK), name, description y is_active. Guardaría el nombre y estado de cada cancha. reservations.court_id pasaría a ser una FK a esta tabla, en vez de depender de un número fijo en el código."],
        ["payments", "id (PK), reservation_id (FK), provider, provider_payment_id, preference_id, amount, currency, status y created_at. Permitiría guardar más de un intento de pago por reserva y consultar su resultado."],
        ["reservation_history", "id (PK), reservation_id (FK), actor_user_id (FK), action, horario anterior y nuevo, created_at. Dejaría un registro de cambios y cancelaciones para resolver consultas."],
    ]
    story.append(make_table(["Tabla propuesta", "Para qué serviría y campos sugeridos"], proposed_schema, [43 * mm, 125 * mm]))
    story += [Spacer(1, 6), callout(
        "Antes de implementar esta ampliación habría que actualizar el esquema y el código, migrar court_id a una relación con courts y mover las referencias de pago desde reservations a payments. La tabla de historial debería guardar solo los datos necesarios y tener acceso restringido.",
        background=colors.HexColor("#F1F5E3"), accent=GOLD,
    )]
    story.append(PageBreak())

    # Page 12 - booking and payments.
    story += title("09 | Reservar", "La prueba y el pago real", "El recorrido se parece en ambos casos, pero solo el pago real confirma que se recibió la seña.")
    story.append(BookingFlow())
    story += [Spacer(1, 8), h2("Modo de prueba actual")]
    story.append(p(
        "Primero, la persona crea una cuenta o inicia sesión. Después elige la cancha, el día, cuántas horas quiere jugar y un horario libre. La reserva queda asociada a su cuenta, así puede verla y, mientras corresponda, cambiarla o cancelarla. En esta versión se muestra cuánto sería la seña, pero no se cobra nada."
    ))
    story += [h2("Si más adelante cobramos con Mercado Pago")]
    story.append(make_table(
        ["Paso", "Control esperado"],
        [
            ["1. Crear solicitud", "Bloquear cada bloque horario y crear una reserva pendiente por un plazo limitado."],
            ["2. Pagar seña", "Enviar al checkout el importe calculado; no pedir ni almacenar datos de tarjeta en la web."],
            ["3. Verificar", "Validar la firma del webhook y consultar el estado del pago directamente al proveedor."],
            ["4. Confirmar", "Cambiar el estado únicamente si el pago y el importe recibido coinciden; liberar slots si vence o falla."],
        ], [39 * mm, 129 * mm], compact=True,
    ))
    story += [Spacer(1, 6), callout(
        "Condiciones previas para activar pagos: acceso a la cuenta comercial de Seberih, token y secreto de webhook, dominio con HTTPS, URL pública de notificaciones, política de cancelación/devolución y pruebas de conciliación.",
        background=colors.HexColor("#F1F5E3"), accent=GOLD,
    )]
    story.append(PageBreak())

    # Page 13 - OS / operations.
    story += title("10 | Dónde funciona", "Preparación y mantenimiento", "Usamos Docker para preparar la aplicación y proponemos Linux para alojarla más adelante.")
    story += [h2("¿Qué hace Docker en este proyecto?"), p(
        "Docker ejecuta la web y la base de datos en contenedores separados. Docker Compose lee docker-compose.yml, prepara los servicios y los conecta para repetir el mismo entorno en distintas computadoras."
    )]
    story.append(make_table(
        ["Parte", "Qué hace", "Cómo está configurada"],
        [
            ["Dockerfile", "Describe cómo se construye la imagen de la web.", "Parte de PHP 8.3 con Apache e instala curl y PDO MySQL."],
            ["Servicio web", "Atiende las visitas y ejecuta el código PHP.", "El puerto 80 del contenedor se muestra como localhost:8080 en esta PC."],
            ["Servicio db", "Guarda las cuentas y los datos relacionales.", "MariaDB 11.4; solo la web accede a db:3306 por la red privada."],
            ["Docker Compose", "Levanta y conecta los servicios.", "Espera a que MariaDB esté lista antes de iniciar la web."],
            ["Almacenamiento", "Conserva los datos aunque se reemplace un contenedor.", "MariaDB usa el volumen mariadb_data; el archivo de reservas demo queda en storage."],
            ["Configuración", "Permite cambiar claves y opciones sin escribirlas en el código.", "Los valores se leen desde el archivo .env local, que no se debe subir al repositorio."],
        ], [31 * mm, 62 * mm, 75 * mm], compact=True,
    ))
    story += [Spacer(1, 5), callout(
        "Recorrido local: navegador -> localhost:8080 -> contenedor web -> red privada -> contenedor db. La base de datos no tiene un puerto publicado para el navegador ni para otros equipos de la red.",
        background=colors.HexColor("#F1F5E3"), accent=GOLD,
    )]
    story += [Spacer(1, 7), h2("Mostrar las tablas con Adminer")]
    story.append(p("Para mostrar las tablas, activa el perfil database-ui. Adminer abre en localhost:8081; inicia sesión con servidor db y los valores DB_NAME, DB_USER y DB_PASSWORD del archivo .env. Solo se abre en esta computadora y no arranca en producción.", "BodyCompact"))
    story += [Spacer(1, 7), h2("Entorno de desarrollo y destino futuro")]
    story.append(p("Hoy desarrollamos en Windows con Docker Desktop y WSL 2, para preparar PHP y MariaDB de forma reproducible. Más adelante proponemos alojar el sitio en Linux; esa configuración aún debe instalarse y probarse.", "BodyCompact"))
    story += [Spacer(1, 6), h2("Abrir el proyecto con doble clic")]
    story.append(p("Abrir Seberih.bat llama a PowerShell: revisa Docker y Compose, inicia Docker Desktop si está instalado pero cerrado y crea .env con claves aleatorias si falta. Compose prepara las imágenes, inicia web, MariaDB y Adminer, espera a que respondan y abre ambas páginas.", "BodyCompact"))
    story.append(p("Para otra PC con Windows, copia la carpeta completa e instala Docker Desktop allí. La primera vez se necesita Internet para descargar las imágenes. El acceso directo no instala Docker Desktop.", "BodyCompact"))
    story.append(p("Cada PC mantiene sus propios datos: cuentas en el volumen local de MariaDB y reservas demo en storage. Copiar la carpeta no copia la base; para reutilizar los datos hay que crear y restaurar un respaldo.", "BodyCompact"))
    story += [Spacer(1, 8), h2("Automatización y mantenimiento")]
    ops_rows = [
        ["backup_database.sh", "Comprime un volcado de MariaDB y copia el archivo de reservas demo; conserva una ventana configurable."],
        ["audit_resources.sh", "Registra uso de disco y consumo/estado de contenedores."],
        ["monitor_security.sh", "Procesa patrones de solicitudes sospechosas en registros web."],
        ["systemd user units", "Arranque de Compose y temporizador de auditoría de recursos; instalación prevista con usuario técnico."],
        ["logrotate", "Rotación de registros para limitar crecimiento del almacenamiento."],
    ]
    story.append(make_table(["Elemento", "Propósito / estado"], ops_rows, [42 * mm, 126 * mm], compact=True))
    story += [Spacer(1, 6), p(
        "Pendiente operativo: definir y ejecutar un calendario de respaldos independiente del temporizador de auditoría; guardar copias fuera del equipo; documentar recuperación y verificar permisos/retención. No guardar credenciales en el repositorio.",
        "BodyCompact",
    )]
    story.append(PageBreak())

    # Page 14 - roadmap/conclusion.
    story += title("11 | Plan de trabajo", "Etapas, aceptación y conclusión", "Propuesta para pasar de prototipo escolar a piloto preparado.")
    roadmap = [
        ["A", "Prototipo", "Registro, acceso, disponibilidad, reservas demo y vista responsive.", "Demostración en equipo local."],
        ["B", "Validación de negocio", "Tarifas, horario, reglas de anticipo, cancelación y roles de administración.", "Acta de aprobación de Seberih."],
        ["C", "Preparación de servidor", "Dominio, HTTPS, variables secretas, Docker rootless, usuarios y almacenamiento.", "Lista de configuración revisada."],
        ["D", "Pagos", "Checkout, webhook firmado, consulta del pago, vencimiento y conciliación.", "Pago de prueba validado de punta a punta."],
        ["E", "Seguridad y continuidad", "Pruebas de concurrencia, privacidad, respaldo automático, restauración y monitoreo.", "Informe de pruebas y plan de recuperación."],
        ["F", "Piloto", "Capacitación del propietario y prueba con usuarios invitados.", "Aprobación antes de abrir al público."],
    ]
    story.append(make_table(["Fase", "Etapa", "Trabajo", "Salida"], roadmap, [17 * mm, 34 * mm, 78 * mm, 39 * mm], compact=True))
    story += [Spacer(1, 8), h2("Criterios mínimos de aceptación del prototipo")]
    for line in [
        "Una persona puede crear cuenta e iniciar sesión sin elegir la fecha antes de acceder al formulario.",
        "Una cuenta autenticada consulta disponibilidad y crea una reserva de prueba asociada a su perfil.",
        "Al cambiar de cuenta, no se muestran reservas pertenecientes a otra persona.",
        "El modo demo identifica claramente que no hubo cobro y conserva el estado local de la reserva.",
        "La página y el proceso de reserva son legibles en dispositivos móviles y escritorio.",
    ]:
        story.append(p("- " + line, "BodyCompact"))
    story += [Spacer(1, 6), h2("Conclusión")]
    story.append(p(
        "Empezamos este proyecto para ayudar a Seberih a ordenar las reservas de sus dos canchas. Ya tenemos una primera versión que permite recorrer el proceso y guardar reservas de prueba. Todavía queda bastante por hacer antes de que pueda usarse con clientes de verdad: tenemos que acordar las reglas con Seberih, preparar los pagos, revisar la seguridad y decidir dónde se alojará la página. Esta versión nos da una base concreta para seguir trabajando juntos."
    ))
    story.append(PageBreak())

    # Page 15 - minutes/glossary.
    story += title("12 | Nuestro recorrido", "Actas resumidas y palabras técnicas", "Resumimos los acuerdos que encontramos en el borrador y explicamos algunos términos del proyecto.")
    story += [h2("Acta N.º 1 - Inicio y definición de roles")]
    story.append(p("Fecha indicada en el borrador: 10 de mayo de 2026. Asistentes registrados: Gonzalo Ortega, Geisson De Castro, Lucas Alfonzo y Alejo Montenegro.", "BodyCompact"))
    story.append(p("Temas: revisión de pautas de egreso; definición del sistema web de reservas de fútbol 5; asignación inicial de responsabilidades y propuesta de reglamento interno.", "BodyCompact"))
    story.append(p("Acuerdo: comenzar la especificación de requerimientos y mantener el seguimiento del proyecto en actas y repositorio.", "BodyCompact"))
    story += [Spacer(1, 5), h2("Acta N.º 2 - Inicio de programación")]
    story.append(p("La fecha no está consignada en el borrador. Se acuerda iniciar programación y base de datos; Geisson De Castro y Gonzalo Ortega toman tareas de desarrollo y datos; Alejo Montenegro apoya la organización de reuniones y actas.", "BodyCompact"))
    story.append(p("Dato a completar por el equipo: fecha, lista de asistentes, decisiones y responsables finales.", "BodySmall"))
    story += [Spacer(1, 8), h2("Glosario")]
    glossary = [
        ["ACID", "Propiedades de transacciones para mantener consistencia, aislamiento y durabilidad."],
        ["Clean Architecture", "Separación de reglas de negocio respecto de interfaces y tecnologías externas."],
        ["CSRF", "Ataque que intenta enviar acciones usando una sesión autenticada; se mitiga con tokens por formulario."],
        ["Docker Compose", "Herramienta para declarar y operar varios servicios de contenedores desde un archivo."],
        ["Webhook", "Notificación de servidor a servidor que comunica un evento, por ejemplo el resultado de un pago."],
        ["Modo demo", "Entorno local que guarda una reserva de prueba sin confirmar un pago ni conectar usuarios entre equipos."],
        ["SRL", "Sociedad de Responsabilidad Limitada; en este proyecto se plantea como forma jurídica académica propuesta."],
    ]
    story.append(make_table(["Término", "Definición"], glossary, [39 * mm, 129 * mm], compact=True))
    story.append(PageBreak())

    # Page 16 - sources and validation checklist.
    story += title("13 | Antes de cerrar", "Fuentes y puntos por confirmar", "Anotamos de dónde sacamos la información y qué cosas debemos revisar con el tutor y con Seberih.")
    story += [h2("Referencias")]
    references = [
        "[1] Ventanilla Única de Inversiones del Uruguay. (s. f.). <i>Apertura de empresas: Sociedad de Responsabilidad Limitada</i>. <link href='https://vui.gub.uy/procesos/apertura-de-empresas/' color='#176B70'>https://vui.gub.uy/procesos/apertura-de-empresas/</link>.",
        "[2] Ministerio de Economía y Finanzas del Uruguay. (2019). <i>Sociedades comerciales</i>. <link href='https://www.gub.uy/ministerio-economia-finanzas/institucional/empresas/sociedades-comerciales' color='#176B70'>https://www.gub.uy/ministerio-economia-finanzas/institucional/empresas/sociedades-comerciales</link>.",
        "[3] ANEP - DGETP. (2026). <i>Pautas del Proyecto de Egreso de 3.º MB - BT Tecnologías de la Información</i>. Material académico proporcionado por el equipo.",
        "[4] Martin, R. C. (2017). <i>Clean Architecture: A Craftsman's Guide to Software Structure and Design</i>. Prentice Hall.",
        "[5] Pressman, R. S. (2010). <i>Ingeniería del software: Un enfoque práctico</i> (7.ª ed.). McGraw-Hill.",
        "[6] Docker, Inc. (s. f.). <i>Docker Compose documentation</i>. <link href='https://docs.docker.com/compose/' color='#176B70'>https://docs.docker.com/compose/</link>.",
        "[7] Repositorio del sistema de egreso. <i>Esquema SQL, controladores, reglas de dominio, scripts y configuración Compose</i>. Versión de trabajo revisada el 5 de noviembre de 2026.",
    ]
    for item in references:
        story.append(p(item, "BodyCompact"))
    story += [Spacer(1, 8), h2("Validaciones antes de entregar o desplegar")]
    checks = [
        "Confirmar con el tutor el alcance académico y los entregables de cada asignatura.",
        "Confirmar la forma de nombrar a la empresa, disponibilidad de la denominación y condición real de constitución. El documento solo la plantea como propuesta.",
        "Verificar nombres y apellidos del equipo; el borrador contiene variantes en el apellido de Lucas y datos distintos sobre integrantes/roles.",
        "Obtener aprobación de Seberih para tarifa, horarios, anticipo, cancelación, reembolso, aviso de privacidad y datos de contacto.",
        "Antes de habilitar pagos, probar HTTPS, credenciales, firma de notificaciones y conciliación; realizar un ensayo completo y documentado.",
        "Acordar calendario de respaldos fuera del equipo local y demostrar una restauración.",
    ]
    for item in checks:
        story.append(p("- " + item, "BodyCompact"))
    story.append(Spacer(1, 6))
    story.append(callout(
        "Antes de entregar este trabajo, tenemos que revisar estos puntos entre todos. Las decisiones sobre la empresa las debemos conversar con el tutor, y las reglas de las reservas con Seberih.",
        background=colors.HexColor("#F1F5E3"), accent=GOLD,
    ))
    return story


def draw_cover_text(canvas, doc):
    canvas.saveState()
    canvas.setFillColor(LIME)
    canvas.setFont(FONT_BOLD_NAME, 8.5)
    canvas.drawCentredString(PAGE_W / 2, PAGE_H - 36 * mm, "PROYECTO DE EGRESO | 2026")
    canvas.setFillColor(WHITE)
    canvas.setFont(FONT_BOLD_NAME, 22)
    canvas.drawCentredString(PAGE_W / 2, PAGE_H - 151 * mm, "Sistema web de gestión")
    canvas.drawCentredString(PAGE_W / 2, PAGE_H - 162 * mm, "y reservas de canchas")
    canvas.drawCentredString(PAGE_W / 2, PAGE_H - 173 * mm, "de fútbol 5")
    canvas.setFillColor(colors.HexColor("#D9E5E1"))
    canvas.setFont(FONT, 11)
    canvas.drawCentredString(PAGE_W / 2, PAGE_H - 189 * mm, "Complejo deportivo Seberih")
    canvas.setFont(FONT, 9)
    canvas.drawCentredString(PAGE_W / 2, PAGE_H - 196 * mm, "Sauce, Canelones")
    canvas.setStrokeColor(GOLD)
    canvas.line(49 * mm, PAGE_H - 207 * mm, PAGE_W - 49 * mm, PAGE_H - 207 * mm)
    canvas.setFillColor(WHITE)
    canvas.setFont(FONT_BOLD_NAME, 9)
    canvas.drawCentredString(PAGE_W / 2, PAGE_H - 221 * mm, "MateCodeUY")
    canvas.setFont(FONT, 8)
    canvas.setFillColor(LIME)
    canvas.drawCentredString(PAGE_W / 2, PAGE_H - 228 * mm, "SRL propuesta para el ejercicio de Emprendedurismo")
    canvas.setFillColor(INK)
    canvas.setFont(FONT_BOLD_NAME, 8.5)
    canvas.drawCentredString(PAGE_W / 2, PAGE_H - 250 * mm, "Escuela Técnica de Sauce | DGETP - UTU")
    canvas.setFont(FONT, 8)
    canvas.drawCentredString(PAGE_W / 2, PAGE_H - 257 * mm, "3.º MB - BT Tecnologías de la Información")
    canvas.drawCentredString(PAGE_W / 2, PAGE_H - 266 * mm, "Geisson De Castro | Gonzalo Ortega | Lucas Alfonzo | Alejo Montenegro")
    canvas.setFillColor(MUTED)
    canvas.setFont(FONT, 7.2)
    canvas.drawCentredString(PAGE_W / 2, 22 * mm, "Documento integral revisado | 5 de noviembre de 2026")
    canvas.restoreState()


def main():
    OUTPUT.parent.mkdir(parents=True, exist_ok=True)
    doc = ProjectDoc(OUTPUT)
    story = build_story()
    doc.build(story)
    print(f"Created {OUTPUT}")


if __name__ == "__main__":
    main()

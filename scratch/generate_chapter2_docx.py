import os
import docx
from docx.shared import Inches, Pt, RGBColor, Cm
from docx.enum.text import WD_ALIGN_PARAGRAPH
from docx.enum.table import WD_TABLE_ALIGNMENT, WD_ALIGN_VERTICAL
from docx.oxml import OxmlElement, parse_xml
from docx.oxml.ns import nsdecls, qn

def set_cell_background(cell, hex_color):
    """Set background color of a table cell."""
    tcPr = cell._tc.get_or_add_tcPr()
    shd = parse_xml(f'<w:shd {nsdecls("w")} w:fill="{hex_color}"/>')
    tcPr.append(shd)

def set_cell_margins(cell, top=120, bottom=120, left=150, right=150):
    """Set cell margins in dxa (1 pt = 20 dxa)."""
    tcPr = cell._tc.get_or_add_tcPr()
    tcMar = parse_xml(f'<w:tcMar {nsdecls("w")}>'
                      f'<w:top w:w="{top}" w:type="dxa"/>'
                      f'<w:bottom w:w="{bottom}" w:type="dxa"/>'
                      f'<w:left w:w="{left}" w:type="dxa"/>'
                      f'<w:right w:w="{right}" w:type="dxa"/>'
                      f'</w:tcMar>')
    tcPr.append(tcMar)

def set_table_borders(table, color="D3D3D3"):
    """Set subtle gray borders for a table."""
    tblPr = table._tbl.tblPr
    borders = parse_xml(
        f'<w:tblBorders {nsdecls("w")}>'
        f'<w:top w:val="single" w:sz="6" w:space="0" w:color="{color}"/>'
        f'<w:bottom w:val="single" w:sz="6" w:space="0" w:color="{color}"/>'
        f'<w:insideH w:val="single" w:sz="4" w:space="0" w:color="{color}"/>'
        f'<w:insideV w:val="none"/>'
        f'<w:left w:val="none"/>'
        f'<w:right w:val="none"/>'
        f'</w:tblBorders>'
    )
    tblPr.append(borders)

def build_chapter_2():
    doc = docx.Document()

    # 1. Page Setup - Standard Vietnamese Academic Thesis Format (A4, Left: 3cm, Right: 2cm, Top: 2cm, Bottom: 2cm)
    for section in doc.sections:
        section.page_width = Cm(21.0)
        section.page_height = Cm(29.7)
        section.top_margin = Cm(2.0)
        section.bottom_margin = Cm(2.0)
        section.left_margin = Cm(3.0)
        section.right_margin = Cm(2.0)

    # Base font setup
    style_normal = doc.styles['Normal']
    font = style_normal.font
    font.name = 'Times New Roman'
    font.size = Pt(13)
    font.color.rgb = RGBColor(0x1A, 0x1A, 0x1A)

    def add_chapter_title(text):
        p = doc.add_paragraph()
        p.alignment = WD_ALIGN_PARAGRAPH.CENTER
        p.paragraph_format.space_before = Pt(18)
        p.paragraph_format.space_after = Pt(14)
        p.paragraph_format.line_spacing = 1.3
        run = p.add_run(text)
        run.bold = True
        run.font.name = 'Times New Roman'
        run.font.size = Pt(16)
        run.font.color.rgb = RGBColor(0x00, 0x33, 0x66)
        return p

    def add_h1(text):
        p = doc.add_paragraph()
        p.alignment = WD_ALIGN_PARAGRAPH.LEFT
        p.paragraph_format.space_before = Pt(14)
        p.paragraph_format.space_after = Pt(6)
        p.paragraph_format.line_spacing = 1.3
        p.paragraph_format.keep_with_next = True
        run = p.add_run(text)
        run.bold = True
        run.font.name = 'Times New Roman'
        run.font.size = Pt(14)
        run.font.color.rgb = RGBColor(0x00, 0x22, 0x44)
        return p

    def add_h2(text):
        p = doc.add_paragraph()
        p.alignment = WD_ALIGN_PARAGRAPH.LEFT
        p.paragraph_format.space_before = Pt(10)
        p.paragraph_format.space_after = Pt(4)
        p.paragraph_format.line_spacing = 1.3
        p.paragraph_format.keep_with_next = True
        run = p.add_run(text)
        run.bold = True
        run.font.name = 'Times New Roman'
        run.font.size = Pt(13)
        run.font.color.rgb = RGBColor(0x11, 0x11, 0x11)
        return p

    def add_h3(text):
        p = doc.add_paragraph()
        p.alignment = WD_ALIGN_PARAGRAPH.LEFT
        p.paragraph_format.space_before = Pt(8)
        p.paragraph_format.space_after = Pt(2)
        p.paragraph_format.line_spacing = 1.3
        p.paragraph_format.keep_with_next = True
        run = p.add_run(text)
        run.bold = True
        run.italic = True
        run.font.name = 'Times New Roman'
        run.font.size = Pt(13)
        return p

    def add_p(text, indent=True):
        p = doc.add_paragraph()
        p.alignment = WD_ALIGN_PARAGRAPH.JUSTIFY
        p.paragraph_format.space_after = Pt(6)
        p.paragraph_format.line_spacing = 1.3
        if indent:
            p.paragraph_format.first_line_indent = Cm(1.0)
        run = p.add_run(text)
        run.font.name = 'Times New Roman'
        run.font.size = Pt(13)
        return p

    def add_bullet(text, level=0):
        p = doc.add_paragraph(style='List Bullet')
        p.alignment = WD_ALIGN_PARAGRAPH.JUSTIFY
        p.paragraph_format.space_after = Pt(3)
        p.paragraph_format.line_spacing = 1.3
        p.paragraph_format.left_indent = Cm(1.2 + level * 0.5)
        run = p.add_run(text)
        run.font.name = 'Times New Roman'
        run.font.size = Pt(13)
        return p

    def add_formula_box(formula_text, label=""):
        p = doc.add_paragraph()
        p.alignment = WD_ALIGN_PARAGRAPH.CENTER
        p.paragraph_format.space_before = Pt(6)
        p.paragraph_format.space_after = Pt(6)
        p.paragraph_format.line_spacing = 1.3
        run = p.add_run(formula_text)
        run.font.name = 'Cambria Math'
        run.font.size = Pt(12)
        run.bold = True
        run.italic = True
        if label:
            run_lbl = p.add_run(f"    ({label})")
            run_lbl.font.name = 'Times New Roman'
            run_lbl.font.size = Pt(12)
            run_lbl.italic = False
            run_lbl.bold = False

    def add_code_block(text):
        tbl = doc.add_table(rows=1, cols=1)
        tbl.alignment = WD_TABLE_ALIGNMENT.CENTER
        cell = tbl.cell(0, 0)
        cell.width = Cm(16.0)
        set_cell_background(cell, "F4F6F7")
        set_cell_margins(cell, top=140, bottom=140, left=200, right=200)
        tcPr = cell._tc.get_or_add_tcPr()
        borders = parse_xml(
            f'<w:tcBorders {nsdecls("w")}>'
            f'<w:left w:val="single" w:sz="18" w:space="0" w:color="003366"/>'
            f'<w:top w:val="none"/>'
            f'<w:bottom w:val="none"/>'
            f'<w:right w:val="none"/>'
            f'</w:tcBorders>'
        )
        tcPr.append(borders)
        p = cell.paragraphs[0]
        p.alignment = WD_ALIGN_PARAGRAPH.LEFT
        p.paragraph_format.space_before = Pt(2)
        p.paragraph_format.space_after = Pt(2)
        run = p.add_run(text)
        run.font.name = 'Consolas'
        run.font.size = Pt(10)
        doc.add_paragraph().paragraph_format.space_after = Pt(4)

    # ─────────────────────────────────────────────────────────────
    # NỘI DUNG CHƯƠNG 2
    # ─────────────────────────────────────────────────────────────

    add_chapter_title("CHƯƠNG 2. CƠ SỞ LÝ THUYẾT")

    # 2.1
    add_h1("2.1. Khái niệm về hệ thống thông tin quản lý chấm công")

    add_h2("2.1.1. Khái niệm hệ thống thông tin quản lý (MIS)")
    add_p("Hệ thống thông tin quản lý (Management Information System - MIS) là một hệ thống có cấu trúc chặt chẽ, kết hợp hài hòa giữa con người, trang thiết bị phần cứng, phần mềm ứng dụng, mạng truyền thông và cơ sở dữ liệu. Mục tiêu trọng tâm của MIS là thu thập, lưu trữ, xử lý và phân phối thông tin kịp thời, chính xác nhằm phục vụ đắc lực cho công tác lập kế hoạch, kiểm soát, điều hành tác nghiệp và ra quyết định chiến lược trong mọi tổ chức, doanh nghiệp.")
    add_p("Trong kỷ nguyên công nghiệp 4.0 và quá trình chuyển đổi số doanh nghiệp, hệ thống thông tin quản lý không chỉ đơn thuần là công cụ tin học hóa các biểu mẫu giấy tờ truyền thống, mà đã trở thành cơ sở hạ tầng cốt lõi giúp chuẩn hóa quy trình, tối ưu hóa năng suất lao động và nâng cao năng lực cạnh tranh bền vững của tổ chức.")

    add_h2("2.1.2. Hệ thống thông tin quản lý chấm công (TAMIS)")
    add_p("Hệ thống thông tin quản lý chấm công (Time & Attendance Management Information System - TAMIS) là một phân hệ chuyên biệt nhưng giữ vị trí huyết mạch trong hệ sinh thái Quản trị nguồn nhân lực (Human Resource Management - HRM). Hệ thống đảm nhiệm các chức năng nền tảng sau:")
    add_bullet("Ghi nhận mốc thời gian làm việc: Thu thập chính xác thời điểm bắt đầu làm việc (Check-in), kết thúc ca làm việc (Check-out), thời gian nghỉ giữa ca cũng như các khoảng thời gian làm thêm giờ (Overtime - OT) của người lao động.")
    add_bullet("Quản lý ca kíp và lịch làm việc linh hoạt: Cho phép bộ phận nhân sự cấu hình đa dạng các loại hình ca làm việc (ca hành chính cố định, ca xoay tuần hoàn, ca gãy, ca đêm), phân ca làm việc chi tiết cho từng nhân viên hoặc phòng ban, thiết lập dung sai thời gian cho phép đi muộn hoặc về sớm.")
    add_bullet("Xử lý quy trình nghiệp vụ (Workflow): Tự động tiếp nhận, xử lý và phê duyệt các đề xuất xin nghỉ phép (nghỉ ốm, nghỉ phép năm, việc riêng), đơn xin đi muộn/về sớm, đơn giải trình quên chấm công và đề xuất làm thêm giờ theo cơ chế phân quyền phê duyệt đa cấp bậc.")
    add_bullet("Đối soát và tự động tổng hợp công: Tự động đối chiếu dữ liệu quẹt công thực tế với lịch trình ca phân công, tính toán số ngày công chuẩn, giờ công thực tế, thời lượng vi phạm kỷ luật lao động, từ đó xuất bảng tổng hợp công kỳ lương phục vụ trực tiếp cho phân hệ tính lương (Payroll).")

    add_h2("2.1.3. Vai trò của hệ thống chấm công đối với tổ chức")
    add_p("Việc triển khai một hệ thống quản lý chấm công hiện đại mang lại nhiều giá trị thiết thực:")
    add_bullet("Đảm bảo tính công bằng và minh bạch: Loại bỏ hoàn toàn sự can thiệp chủ quan hoặc cảm tính của con người trong việc đánh giá mức độ chuyên cần, bảo vệ quyền lợi hợp pháp và tạo động lực cống hiến cho người lao động.")
    add_bullet("Tiết kiệm chi phí và tối ưu nguồn lực vận hành: Cắt giảm hàng chục giờ làm việc thủ công mỗi tháng của bộ phận nhân sự và kế toán, loại trừ triệt để sai sót nhập liệu và thất thoát quỹ lương do gian lận chấm công.")
    add_bullet("Tăng cường kỷ luật lao động và hỗ trợ điều hành: Cung cấp báo cáo trực quan theo thời gian thực về tỷ lệ hiện diện, tỷ lệ vắng mặt hoặc đi muộn, giúp ban lãnh đạo nắm bắt kịp thời tình hình nhân sự để có phương án điều phối sản xuất - kinh doanh linh hoạt.")

    # 2.2
    add_h1("2.2. Tổng quan các hệ thống chấm công liên quan")
    add_p("Cùng với sự phát triển mạnh mẽ của khoa học kỹ thuật, các giải pháp ghi nhận thời gian làm việc đã trải qua nhiều giai đoạn chuyển biến công nghệ rõ rệt.")

    add_h2("2.2.1. Hệ thống chấm công truyền thống")
    add_p("Trong giai đoạn sơ khai, các doanh nghiệp chủ yếu áp dụng hai phương thức chấm công thủ công:")
    add_bullet("Chấm công bằng sổ sách: Nhân viên tự ký tên và ghi chép giờ đến/về vào sổ theo dõi đặt tại quầy lễ tân hoặc quản lý trực tiếp theo dõi và đánh dấu vào bảng chấm công giấy. Phương pháp này có độ chính xác rất thấp, rủi ro sửa chữa số liệu tùy tiện, ghi khống giờ và dễ thất lạc tài liệu lưu trữ.")
    add_bullet("Chấm công bằng thẻ giấy bấm giờ (Mechanical Punch Card): Nhân viên đưa thẻ giấy in sẵn các ô ngày vào khe dập của máy đồng hồ cơ khí để in mốc thời gian. Mặc dù đã tự động hóa được việc in giờ, song quy trình tổng hợp số liệu cuối tháng vẫn hoàn toàn phụ thuộc vào việc nhân viên nhân sự phải đọc từng thẻ giấy và gõ lại vào bảng tính Excel, rất tốn kém nhân lực và dễ sai sót.")

    add_h2("2.2.2. Hệ thống chấm công bằng thẻ từ và vân tay")
    add_p("Sự ra đời của công nghệ điện tử và nhận dạng tự động đã mở ra giai đoạn chấm công bán tự động và tự động hóa:")
    add_bullet("Hệ thống chấm công bằng thẻ từ (RFID / Proximity Card): Hoạt động dựa trên công nghệ nhận dạng tần số vô tuyến (Radio Frequency Identification) ở dải tần 125 kHz hoặc 13.56 MHz (Mifare). Mỗi nhân viên được cấp một thẻ từ mang mã định danh duy nhất (UID). Khi quẹt thẻ qua đầu đọc, hệ thống ghi nhận thời gian chỉ trong vòng chưa đầy một giây. Tuy nhiên, nhược điểm chí mạng của phương pháp này là lỗ hổng gian lận quẹt thẻ hộ (Buddy Punching), cùng với chi phí phát sinh khi nhân viên làm mất hoặc quên mang thẻ.")
    add_bullet("Hệ thống chấm công bằng sinh trắc học vân tay (Fingerprint Biometrics): Hoạt động dựa trên cảm biến quang học (Optical Sensor) hoặc điện dung (Capacitive Sensor) để ghi nhận hình ảnh vân tay, trích xuất các điểm đặc trưng hình thái (Minutiae: điểm kết thúc đường vân, điểm rẽ nhánh) và chuyển đổi thành chuỗi dữ liệu mã hóa để so khớp. Phương pháp này đã giải quyết triệt để vấn đề chấm công hộ vì đặc trưng vân tay là độc nhất vô nhị. Mặc dù vậy, hệ thống bộc lộ nhiều hạn chế thực tế: bắt buộc phải chạm ngón tay trực tiếp vào mặt kính cảm biến gây nguy cơ lây lan dịch bệnh; tỷ lệ từ chối sai (FRR) tăng cao khi tay bị dính nước, dầu mỡ, bụi bẩn hoặc vân tay bị mòn (thường gặp ở công nhân sản xuất); tốc độ xử lý tương đối chậm gây ùn ứ vào giờ cao điểm.")

    add_h2("2.2.3. Hệ thống chấm công qua ứng dụng di động/GPS")
    add_p("Sự phổ cập của điện thoại thông minh và mạng Internet di động đã thúc đẩy mô hình chấm công linh hoạt:")
    add_bullet("Cơ chế vận hành: Ứng dụng cài đặt trên smartphone của nhân viên thu thập tọa độ địa lý thông qua chip định vị vệ tinh GPS kết hợp với nhận diện địa chỉ MAC của trạm phát Wi-Fi nội bộ (BSSID). Khi nhân viên bấm chấm công, hệ thống đối soát xem tọa độ có nằm trong phạm vi hàng rào địa lý cho phép (Geofencing) của cơ quan hay không.")
    add_bullet("Ưu điểm: Vô cùng thuận tiện cho các mô hình làm việc từ xa (Remote / Work from home), làm việc kết hợp (Hybrid) hoặc các vị trí công tác di động thường xuyên như nhân viên bán hàng thị trường, tài xế vận tải, kỹ sư công trường.")
    add_bullet("Hạn chế: Dễ bị can thiệp bởi các phần mềm bẻ khóa và giả lập tọa độ ảo (Fake GPS / Mock Location); phụ thuộc hoàn toàn vào tình trạng thiết bị cá nhân (hết pin, máy cấu hình quá yếu, mất kết nối mạng); đồng thời vấp phải rào cản về quyền riêng tư (Privacy) khi người lao động e ngại việc bị theo dõi vị trí liên tục.")

    add_h2("2.2.4. Ưu điểm, hạn chế và định hướng cải tiến")
    add_p("Dưới đây là bảng tổng hợp so sánh toàn diện giữa các phương thức chấm công phổ biến:")

    # Table 1: So sánh các hệ thống chấm công
    table1 = doc.add_table(rows=6, cols=6)
    table1.alignment = WD_TABLE_ALIGNMENT.CENTER
    set_table_borders(table1)

    headers1 = ["Tiêu chí", "Sổ sách / Thẻ giấy", "Thẻ từ RFID", "Sinh trắc vân tay", "Di động / GPS", "Nhận diện khuôn mặt"]
    col_widths1 = [Cm(3.0), Cm(2.4), Cm(2.4), Cm(2.6), Cm(2.6), Cm(3.0)]

    for c_idx, title in enumerate(headers1):
        cell = table1.cell(0, c_idx)
        cell.width = col_widths1[c_idx]
        set_cell_background(cell, "003366")
        set_cell_margins(cell, top=140, bottom=140, left=100, right=100)
        p = cell.paragraphs[0]
        p.alignment = WD_ALIGN_PARAGRAPH.CENTER
        run = p.add_run(title)
        run.bold = True
        run.font.name = 'Times New Roman'
        run.font.size = Pt(11)
        run.font.color.rgb = RGBColor(0xFF, 0xFF, 0xFF)

    data1 = [
        ["Chi phí ban đầu", "Rất thấp", "Thấp", "Trung bình", "Thấp (Dùng ĐT cá nhân)", "Trung bình - Tiết kiệm"],
        ["Tốc độ ghi nhận", "Rất chậm", "Rất nhanh (< 1s)", "Trung bình (1-2s)", "Phụ thuộc mạng 4G/Wifi", "Rất nhanh (< 0.5s)"],
        ["Khả năng chống gian lận", "Kém", "Kém (Quẹt thẻ hộ)", "Tốt", "Trung bình (Dễ Fake GPS)", "Rất cao (Có Anti-Spoofing)"],
        ["Tính tiện dụng", "Kém", "Trung bình (Dễ quên thẻ)", "Tốt", "Tốt", "Tối ưu (Không cần mang vật dụng)"],
        ["Tiếp xúc vật lý", "Không tiếp xúc chung", "Chạm thẻ", "Tiếp xúc bề mặt cảm ứng", "Thiết bị cá nhân", "Không tiếp xúc (Contactless)"]
    ]

    for r_idx, row in enumerate(data1):
        bg_color = "F7F9FA" if r_idx % 2 == 1 else "FFFFFF"
        for c_idx, val in enumerate(row):
            cell = table1.cell(r_idx + 1, c_idx)
            cell.width = col_widths1[c_idx]
            set_cell_background(cell, bg_color)
            set_cell_margins(cell, top=100, bottom=100, left=100, right=100)
            p = cell.paragraphs[0]
            if c_idx == 0:
                p.alignment = WD_ALIGN_PARAGRAPH.LEFT
                run = p.add_run(val)
                run.bold = True
            elif c_idx == 5:
                p.alignment = WD_ALIGN_PARAGRAPH.CENTER
                run = p.add_run(val)
                run.bold = True
                run.font.color.rgb = RGBColor(0x00, 0x33, 0x66)
            else:
                p.alignment = WD_ALIGN_PARAGRAPH.CENTER
                run = p.add_run(val)
            run.font.name = 'Times New Roman'
            run.font.size = Pt(11)

    p_caption1 = doc.add_paragraph()
    p_caption1.alignment = WD_ALIGN_PARAGRAPH.CENTER
    p_caption1.paragraph_format.space_before = Pt(4)
    p_caption1.paragraph_format.space_after = Pt(10)
    run_cap1 = p_caption1.add_run("Bảng 2.1: So sánh tổng quan các phương thức chấm công hiện nay")
    run_cap1.italic = True
    run_cap1.font.name = 'Times New Roman'
    run_cap1.font.size = Pt(11)

    add_p("Định hướng cải tiến của đề tài: Từ những phân tích trên, việc phát triển Hệ thống thông tin quản lý chấm công ứng dụng Trí tuệ nhân tạo (AI) và Thị giác máy tính (Computer Vision) thông qua nhận diện khuôn mặt là hướng đi tất yếu và mang tính đột phá. Phương pháp này giải quyết triệt để yêu cầu chấm công không tiếp xúc (Contactless), thao tác tức thời, hoàn toàn không thể chấm công hộ, đồng thời tích hợp thuật toán kiểm tra tính sống (Liveness Detection / Anti-Spoofing) để ngăn chặn tuyệt đối các hành vi gian lận bằng hình ảnh hoặc video giả mạo.")

    # 2.3
    add_h1("2.3. Tổng quan các phương pháp nhận diện khuôn mặt")
    add_p("Hệ thống nhận diện khuôn mặt tự động (Automatic Face Recognition Pipeline) là một chuỗi liên hoàn các kỹ thuật xử lý ảnh và học sâu, bao gồm các giai đoạn tuần tự sau:")
    add_code_block("  [Ảnh từ Camera] \n       │\n       ▼\n  [1. Face Detection]  ──► Định vị Bounding Box & 5 Điểm mốc (Landmarks)\n       │\n       ▼\n  [2. Face Alignment]  ──► Biến đổi Affine xoay thẳng & chuẩn hóa kích thước (112x112)\n       │\n       ▼\n  [3. Anti-Spoofing]   ──► Kiểm tra người thật / ảnh in / video màn hình (Liveness Score)\n       │ (Người thật)\n       ▼\n  [4. Feature Extract] ──► Deep CNN (ArcFace) trích xuất Vector nhúng 512 chiều\n       │\n       ▼\n  [5. So khớp Cosine]  ──► So sánh Cosine Similarity với Vector khuôn mặt nhân viên\n       │\n       ▼\n  [Xác nhận danh tính & Ghi nhận chấm công]")

    add_h2("2.3.1. Phát hiện và xử lý khuôn mặt (Face Detection & Preprocessing)")
    add_p("Phát hiện khuôn mặt là bước tiên quyết trong toàn bộ quy trình, có nhiệm vụ quét toàn bộ bức ảnh đầu vào nhằm xác định tọa độ khung chữ nhật bao quanh khuôn mặt (Bounding Box gồm x, y, width, height) và vị trí của các điểm mốc giải phẫu học cơ bản (Facial Landmarks).")
    add_p("Trong lịch sử nghiên cứu, phương pháp Haar Cascade của Viola-Jones (kết hợp bộ phân loại AdaBoost) và HOG (Histogram of Oriented Gradients) kết hợp máy học véc-tơ hỗ trợ (Linear SVM) từng được ứng dụng rộng rãi. Tuy nhiên, các kỹ thuật cổ điển này có tỷ lệ phát hiện sót và nhận diện sai rất cao khi khuôn mặt bị nghiêng góc lớn, bị che khuất một phần hoặc môi trường làm việc bị ngược sáng, thiếu sáng.")
    add_p("Ngày nay, các mạng nơ-ron tích chập sâu (Deep CNN) như MTCNN, RetinaFace, SSD và YOLO đã chiếm ưu thế tuyệt đối. Nhờ cơ chế học biểu diễn đặc trưng phân tầng qua hàng triệu tham số, các mô hình này đạt độ nhạy cực cao và xác định điểm mốc với độ lệch dưới một vài pixel.")
    add_p("Tiền xử lý và Căn chỉnh khuôn mặt (Face Alignment): Khuôn mặt người trong ảnh thực tế luôn có xu hướng nghiêng, ngửa hoặc xoay nhẹ. Nếu đưa trực tiếp ảnh chưa chuẩn hóa vào mạng trích xuất đặc trưng, sự sai khác về góc nhìn sẽ làm sai lệch nghiêm trọng không gian biểu diễn. Phép biến đổi Affine (Affine Transformation) 2D được áp dụng dựa trên 5 điểm mốc chuẩn (2 mắt, 1 đỉnh mũi, 2 khóe miệng):")
    add_formula_box("x' = s · R(θ) · x + t", "2.1")
    add_p("Trong đó, s là tỉ lệ co giãn, R(θ) là ma trận quay quanh trục góc θ, và t là vector tịnh tiến. Nhờ phép biến đổi này, ảnh khuôn mặt được xoay thẳng trục hai mắt nằm ngang song song, cân đối vị trí trung tâm và cắt cúp (crop) chuẩn xác về kích thước tiêu chuẩn 112 × 112 pixels.")

    add_h2("2.3.2. Trích xuất đặc trưng và so khớp (Feature Extraction & Matching)")
    add_p("Bản chất của nhận diện khuôn mặt hiện đại là kỹ thuật nhúng đặc trưng (Face Embedding). Một mạng nơ-ron tích chập sâu đóng vai trò là một hàm ánh xạ phi tuyến f(x), chuyển đổi bức ảnh khuôn mặt đã căn chỉnh x thành một vector số thực đa chiều trong không gian Euclid:")
    add_formula_box("f(x) ∈ ℝ^d    (với d = 512)", "2.2")
    add_p("Vector 512 chiều này là đại diện số hóa tinh gọn, cô đọng toàn bộ các đặc trưng nhận dạng sinh trắc học cá nhân. Để đạt được tính phân biệt cao, các mô hình học sâu hiện đại sử dụng các hàm mất mát tối ưu hóa biên độ góc (Margin-based Loss Functions).")
    add_p("Trước đây, mạng FaceNet của Google sử dụng Triplet Loss nhằm tối ưu hóa khoảng cách giữa mẫu gốc (Anchor), mẫu cùng người (Positive) và mẫu khác người (Negative). Tuy nhiên, Triplet Loss đòi hỏi chiến lược lựa chọn mẫu bộ ba (Triplet mining) rất phức tạp và thời gian hội tụ chậm. Đột phá mới nhất là các hàm mất mát góc, tiêu biểu là ArcFace (Additive Angular Margin Loss), với cơ chế thêm lượng biên góc trực tiếp vào không gian mặt cầu đơn vị, giúp thu hẹp tối đa khoảng cách nội lớp (Intra-class compactness) và nới rộng tối đa khoảng cách liên lớp (Inter-class discrepancy).")
    add_p("Kỹ thuật so khớp đặc trưng (Feature Matching): Để so sánh mức độ tương đồng sinh trắc học giữa vector khuôn mặt quét từ camera (v₁) và vector khuôn mặt nhân viên đã đăng ký sẵn trong hệ thống (v₂), hệ thống sử dụng phép đo Độ tương đồng Cosine (Cosine Similarity):")
    add_formula_box("Cosine Similarity(v₁, v₂) = cos(θ) = (v₁ · v₂) / (||v₁||₂ · ||v₂||₂)", "2.3")
    add_p("Khi các vector đặc trưng đều đã được chuẩn hóa theo chuẩn L₂ (tức ||v₁||₂ = ||v₂||₂ = 1), công thức trên quy về phép tích vô hướng đơn giản: Similarity(v₁, v₂) = v₁ · v₂. Giá trị tương đồng dao động trong đoạn [-1, 1]. Hệ thống thiết lập một ngưỡng quyết định τ (ngưỡng tiêu chuẩn thông thường là 0.40 - 0.50):")
    add_bullet("Nếu Similarity(v₁, v₂) ≥ τ: Hệ thống khẳng định hai khuôn mặt thuộc về cùng một danh tính.")
    add_bullet("Nếu Similarity(v₁, v₂) < τ: Hệ thống kết luận không trùng khớp danh tính.")

    add_h2("2.3.3. Kiểm tra tính sống và chống giả mạo (Liveness Detection & Anti-Spoofing)")
    add_p("Trong môi trường doanh nghiệp thực tế, các cuộc tấn công giả mạo trình diện (Presentation Attacks - PA) là mối đe dọa lớn nhất đối với hệ thống chấm công khuôn mặt. Các hình thức tấn công phổ biến gồm:")
    add_bullet("Print Attack (Tấn công bằng ảnh in 2D): Kẻ gian in ảnh chân dung của đồng nghiệp lên giấy A4 hoặc giấy ảnh bóng chất lượng cao rồi đưa trước camera để chấm công hộ.")
    add_bullet("Replay Attack (Tấn công phát lại qua màn hình kỹ thuật số): Kẻ gian sử dụng màn hình điện thoại thông minh, máy tính bảng hoặc máy tính xách tay để phát lại ảnh tĩnh hoặc đoạn video quay lén khuôn mặt của người khác.")
    add_bullet("3D Mask Attack: Sử dụng mặt nạ silicon, tượng sáp hoặc mô hình khuôn đúc 3D mô phỏng khuôn mặt nạn nhân.")
    add_p("Để ngăn chặn các nguy cơ trên, công nghệ phát hiện tính sống được chia làm hai hướng tiếp cận chính:")
    add_bullet("Kiểm tra tính sống chủ động (Active Liveness): Bắt buộc người dùng thực hiện một chuỗi tương tác theo yêu cầu ngẫu nhiên của phần mềm (chớp mắt, quay đầu sang trái/phải, mỉm cười). Nhược điểm là làm kéo dài thời gian chấm công (từ 3 - 5 giây), gây phiền toái cho nhân viên và làm giảm lưu lượng thông suốt tại cửa ra vào.")
    add_bullet("Kiểm tra tính sống thụ động (Passive Liveness): Phân tích tức thời chỉ trên một khung hình đơn lẻ mà không đòi hỏi người dùng thực hiện bất kỳ thao tác tương tác nào. Thuật toán kết hợp phân tích kết cấu vi mô của da (da người sống có cấu trúc lỗ chân lông và tính chất tán xạ ánh sáng tự nhiên khác hoàn toàn với tính chất phản quang của kính màn hình điện tử hoặc độ nhám của giấy in), phân tích phổ tần số Fourier (nhằm phát hiện các dải vân sọc giao thoa Moire sinh ra khi camera quay lại một màn hình số), và sử dụng mạng nơ-ron học sâu chuyên biệt (như MiniFASNet) để đưa ra điểm số tính sống (Liveness Score).")

    # 2.4
    add_h1("2.4. Các mô hình nhận diện khuôn mặt được nghiên cứu")

    add_h2("2.4.1. TinyFace Detector")
    add_p("Mô hình TinyFace được đề xuất bởi Peiyun Hu và Deva Ramanan trong công trình nghiên cứu khoa học 'Finding Tiny Faces' (CVPR 2017). Đây là một trong những cột mốc quan trọng giải quyết bài toán phát hiện khuôn mặt có kích thước siêu nhỏ (dưới 20 pixels), chụp ở cự ly rất xa hoặc trong điều kiện độ phân giải cực thấp.")
    add_p("Về mặt kiến trúc, TinyFace tạo ra sự khác biệt nhờ hai nguyên lý then chốt:")
    add_bullet("Khai thác triệt để ngữ cảnh mở rộng (Contextual Cues): Khi khuôn mặt quá nhỏ hoặc bị mờ, các chi tiết nội tại như mắt, mũi, miệng hầu như bị xóa nhòa. TinyFace mở rộng vùng tiếp nhận đặc trưng ra các bộ phận xung quanh như cổ, vai và thân người để tăng cường độ tin cậy khi dự đoán vị trí đầu người.")
    add_bullet("Bộ dò chuyên biệt theo từng dải tỉ lệ (Scale-specific Detectors): Thay vì dùng một mạng duy nhất cho mọi kích cỡ khuôn mặt, TinyFace xây dựng kim tự tháp ảnh phân giải đa tỉ lệ và huấn luyện các bộ dò chuyên biệt tối ưu riêng cho từng dải kích thước (nhỏ, trung bình, lớn).")
    add_p("Đánh giá thực tế: TinyFace cực kỳ hiệu quả trong các hệ thống giám sát an ninh công cộng, tìm kiếm đối tượng trong đám đông từ camera tầm xa. Tuy nhiên, kích thước mô hình nặng, đòi hỏi tài nguyên tính toán lớn và tốc độ suy luận tương đối chậm. Đối với bài toán trạm chấm công nội bộ (nơi nhân viên chủ động đứng ở cự ly chuẩn từ 0.5m - 1.2m trước camera), việc triển khai TinyFace là không tối ưu về mặt hiệu năng.")

    add_h2("2.4.2. InsightFace – Buffalo_L")
    add_p("InsightFace là dự án mã nguồn mở thị giác máy tính hàng đầu thế giới hiện nay về phân tích khuôn mặt. Trong đó, gói mô hình Buffalo_L (Large) là cấu hình chuẩn công nghiệp cao cấp nhất, cung cấp một hệ thống nhận diện hoàn chỉnh đạt độ chính xác dẫn đầu (State-of-the-art):")
    add_bullet("RetinaFace-ResNet50 (Phát hiện và căn chỉnh khuôn mặt): Sử dụng mạng xương sống ResNet50 kết hợp cấu trúc mạng kim tự tháp đặc trưng (FPN - Feature Pyramid Network). RetinaFace thực hiện giám sát đa tác vụ đồng thời: phân loại vùng ảnh (Face/Non-face), hồi quy tọa độ hộp bao (Bounding Box), định vị 5 điểm mốc hình thái (Landmarks) với độ chuẩn xác vượt trội kể cả khi khuôn mặt bị che khuất một phần (đeo khẩu trang, đeo kính).")
    add_bullet("ArcFace-ResNet50 (Trích xuất vector đặc trưng sinh trắc học): Mạng nơ-ron tích chập sâu ResNet50 được tối ưu hóa bằng hàm mất mát ArcFace (Additive Angular Margin Loss), trích xuất vector đặc trưng 512 chiều có khả năng phân biệt danh tính siêu việt.")
    add_p("Công thức toán học của hàm mất mát ArcFace:")
    add_formula_box("L_ArcFace = - (1/N) ∑_{i=1}^N log [ e^(s · cos(θ_{y_i} + m)) / ( e^(s · cos(θ_{y_i} + m)) + ∑_{j ≠ y_i} e^(s · cos θ_j) ) ]", "2.4")
    add_p("Trong đó: N là kích thước lô huấn luyện (Batch size); s là hệ số tỷ lệ bán kính mặt cầu (Scale factor, thường cố định s = 64); m là tham số phạt biên độ góc cộng thêm (thường thiết lập m = 0.5 radian); θ_{y_i} là góc giữa vector đặc trưng chuẩn hóa x_i và vector trọng số lớp đúng W_{y_i}; θ_j là góc với các lớp nhãn khác.")
    add_p("Ý nghĩa: Việc trực tiếp cộng góc phạt m vào góc θ_{y_i} đã ép buộc mô hình phải tối ưu hóa khoảng cách góc trên mặt cầu siêu phẳng, tạo ra một 'vùng đệm an toàn' vững chắc phân tách giữa các cá nhân khác nhau, mang lại độ chính xác trên 99.8% trên tập chuẩn quốc tế LFW (Labeled Faces in the Wild).")

    add_h2("2.4.3. Một số mô hình/giải pháp khác")
    add_p("Ngoài TinyFace và Buffalo_L, các giải pháp học sâu khác cũng được nhóm nghiên cứu khảo sát:")
    add_bullet("MTCNN (Multi-task Cascaded Convolutional Networks): Là kiến trúc phân tầng 3 giai đoạn gồm P-Net (Proposal Network - rà soát nhanh ứng viên), R-Net (Refine Network - lọc bỏ hộp bao sai), và O-Net (Output Network - xuất tọa độ hộp bao và 5 điểm mốc). Ưu điểm nổi bật của MTCNN là dung lượng siêu nhẹ (chỉ khoảng 2MB), thời gian xử lý rất nhanh, hoạt động mượt mà trên môi trường CPU không cần card đồ họa chuyên dụng.")
    add_bullet("MobileFaceNet & Buffalo_S (Small): Mô hình rút gọn ứng dụng các lớp tích chập tách biệt theo chiều sâu (Depthwise Separable Convolution), tối ưu cho các thiết bị di động hoặc thiết bị nhúng IoT (Raspberry Pi).")
    add_bullet("MiniFASNet (Mini Face Anti-Spoofing Network): Mô hình tích chập gọn nhẹ chuyên trách bài toán kiểm tra tính sống dựa trên kỹ thuật Central Difference Convolution (CDC). Mạng có kích thước xấp xỉ 3MB nhưng đạt độ chính xác phân biệt giả mạo cực cao trên bộ dữ liệu chuẩn OULU-NPU và CASIA-SURF.")

    # Table 2: So sánh các mô hình AI
    table2 = doc.add_table(rows=6, cols=6)
    table2.alignment = WD_TABLE_ALIGNMENT.CENTER
    set_table_borders(table2)

    headers2 = ["Mô hình", "Nhiệm vụ", "Kiến trúc Backbone", "Dung lượng", "Thời gian xử lý", "Độ chính xác (LFW)"]
    col_widths2 = [Cm(3.0), Cm(2.6), Cm(2.8), Cm(2.2), Cm(2.6), Cm(2.8)]

    for c_idx, title in enumerate(headers2):
        cell = table2.cell(0, c_idx)
        cell.width = col_widths2[c_idx]
        set_cell_background(cell, "003366")
        set_cell_margins(cell, top=140, bottom=140, left=100, right=100)
        p = cell.paragraphs[0]
        p.alignment = WD_ALIGN_PARAGRAPH.CENTER
        run = p.add_run(title)
        run.bold = True
        run.font.name = 'Times New Roman'
        run.font.size = Pt(11)
        run.font.color.rgb = RGBColor(0xFF, 0xFF, 0xFF)

    data2 = [
        ["TinyFace", "Face Detection", "ResNet101", "~150 MB", "Chậm (~200ms)", "Cao với mặt ở xa"],
        ["MTCNN", "Detection & Align", "3-Stage CNN", "~2 MB", "Rất nhanh (~30ms)", "Tốt (~95%)"],
        ["InsightFace Buffalo_L", "Detection & Embedding", "ResNet50", "~160 MB", "Nhanh (~60ms GPU)", "SOTA (> 99.8%)"],
        ["MobileFaceNet", "Face Embedding", "MobileNetV2", "~4 MB", "Cực nhanh (~15ms)", "Khá cao (~99.2%)"],
        ["MiniFASNet", "Anti-Spoofing", "MiniFASNetV2", "~3 MB", "Rất nhanh (~20ms)", "Rất cao (SOTA liveness)"]
    ]

    for r_idx, row in enumerate(data2):
        bg_color = "F7F9FA" if r_idx % 2 == 1 else "FFFFFF"
        for c_idx, val in enumerate(row):
            cell = table2.cell(r_idx + 1, c_idx)
            cell.width = col_widths2[c_idx]
            set_cell_background(cell, bg_color)
            set_cell_margins(cell, top=100, bottom=100, left=100, right=100)
            p = cell.paragraphs[0]
            if c_idx == 0:
                p.alignment = WD_ALIGN_PARAGRAPH.LEFT
                run = p.add_run(val)
                run.bold = True
            elif c_idx == 2 and "Buffalo_L" in row[0]:
                p.alignment = WD_ALIGN_PARAGRAPH.CENTER
                run = p.add_run(val)
                run.bold = True
            else:
                p.alignment = WD_ALIGN_PARAGRAPH.CENTER
                run = p.add_run(val)
            run.font.name = 'Times New Roman'
            run.font.size = Pt(11)

    p_caption2 = doc.add_paragraph()
    p_caption2.alignment = WD_ALIGN_PARAGRAPH.CENTER
    p_caption2.paragraph_format.space_before = Pt(4)
    p_caption2.paragraph_format.space_after = Pt(10)
    run_cap2 = p_caption2.add_run("Bảng 2.2: So sánh thông số kỹ thuật giữa các mô hình thị giác máy tính được nghiên cứu")
    run_cap2.italic = True
    run_cap2.font.name = 'Times New Roman'
    run_cap2.font.size = Pt(11)

    # 2.5
    add_h1("2.5. Kiến trúc MVC và Microservices trong phát triển phần mềm")

    add_h2("2.5.1. Kiến trúc MVC (Model - View - Controller)")
    add_p("Kiến trúc MVC là một trong những khuôn mẫu kiến trúc phần mềm kinh điển và phổ biến nhất trong phát triển ứng dụng web, áp dụng triệt để nguyên lý phân tách mối quan tâm (Separation of Concerns - SoC):")
    add_bullet("Model (Mô hình dữ liệu): Đại diện cho cấu trúc dữ liệu, các ràng buộc quan hệ và toàn bộ logic nghiệp vụ (Business Rules). Model trực tiếp giao tiếp với Hệ quản trị cơ sở dữ liệu (MySQL) để thực hiện các thao tác truy vấn, cập nhật dữ liệu nhân viên, bảng phân ca, các bản ghi chấm công.")
    add_bullet("View (Giao diện hiển thị): Chịu trách nhiệm trực quan hóa dữ liệu và tương tác trực tiếp với người dùng cuối. View hoàn toàn không chứa mã xử lý nghiệp vụ hay truy vấn SQL, chỉ thuần túy hiển thị cấu trúc HTML/CSS/JS dựa trên dữ liệu được Controller chuyển giao.")
    add_bullet("Controller (Bộ điều phối): Đóng vai trò cầu nối trung gian. Controller tiếp nhận các yêu cầu HTTP Request gửi lên từ trình duyệt người dùng, phân tích tham số, gọi các phương thức tương ứng trong Model để xử lý nghiệp vụ, và lựa chọn View phù hợp kèm theo dữ liệu kết quả để phản hồi (HTTP Response) về phía client.")
    add_p("Đánh giá: MVC giúp cấu trúc mã nguồn gọn gàng, module hóa rõ ràng, dễ bảo trì và phân công công việc giữa lập trình viên giao diện (Frontend) và lập trình viên nghiệp vụ (Backend). Tuy nhiên, nếu phát triển theo mô hình khối duy nhất (Monolith MVC), khi tích hợp các thuật toán trí tuệ nhân tạo tính toán ma trận nặng nề, Web Server sẽ rất dễ bị quá tải, gây nghẽn toàn bộ hệ thống quản trị.")

    add_h2("2.5.2. Kiến trúc Microservices (Kiến trúc vi dịch vụ)")
    add_p("Kiến trúc Microservices là phong cách thiết kế phân rã một hệ thống phần mềm lớn thành một tập hợp các dịch vụ nhỏ độc lập. Mỗi dịch vụ chạy trên tiến trình riêng biệt, sở hữu cơ chế lưu trữ riêng và giao tiếp với nhau thông qua các giao thức mạng chuẩn hóa, gọn nhẹ (chủ yếu là RESTful API trên nền tảng HTTP/JSON hoặc gRPC).")
    add_p("Các ưu điểm vượt trội của Microservices:")
    add_bullet("Độc lập về công nghệ (Polyglot Programming): Mỗi vi dịch vụ có thể tự do lựa chọn ngôn ngữ lập trình, hệ điều hành và thư viện tối ưu nhất cho bài toán của mình mà không bị ràng buộc bởi phần còn lại của hệ thống.")
    add_bullet("Khả năng mở rộng quy mô độc lập (Independent Scalability): Doanh nghiệp có thể cấp phát thêm tài nguyên (CPU, RAM, GPU) cho riêng dịch vụ có nhu cầu tính toán cao mà không cần nâng cấp toàn bộ hệ thống máy chủ.")
    add_bullet("Cô lập lỗi (Fault Isolation): Sự cố hoặc tắt nghẽn ở một vi dịch vụ không làm sụp đổ các chức năng khác của hệ thống, gia tăng độ sẵn sàng và độ tin cậy của toàn bộ phần mềm.")

    add_h2("2.5.3. Mô hình kết hợp thực tế trong đề tài (Hybrid: Core Web MVC + AI Microservice)")
    add_p("Xuất phát từ yêu cầu thực tiễn của bài toán quản lý chấm công doanh nghiệp và đặc thù xử lý của các mô hình học sâu, đề tài đề xuất và triển khai Mô hình kiến trúc lai (Hybrid Architecture) kết hợp hài hòa giữa kiến trúc MVC và vi dịch vụ Microservices:")
    add_code_block("  [Người dùng / Trạm chấm công Kiosk] \n                  │\n                  ▼  (HTTP Web Traffic)\n  ┌────────────────────────────────────────────────────────┐\n  │     HỆ THỐNG QUẢN TRỊ TRUNG TÂM (CORE WEB APP - MVC)   │\n  │     - Ngôn ngữ: PHP (Kiến trúc MVC chuẩn)              │\n  │     - Chức năng: Quản lý nhân viên, phân ca, chấm công,│\n  │       xử lý đơn từ, xuất báo cáo, phân quyền RBAC      │\n  │     - Cơ sở dữ liệu: MySQL Database                    │\n  └───────────────────────────┬────────────────────────────┘\n                              │\n                              │ Giao tiếp RESTful API (HTTP JSON)\n                              │ [X-API-Key Authentication]\n                              ▼\n  ┌────────────────────────────────────────────────────────┐\n  │       VI DỊCH VỤ NHẬN DIỆN KHUÔN MẶT (AI MICROSERVICE) │\n  │     - Nền tảng: Python FastAPI + ONNX Runtime          │\n  │     - Kiến trúc: Vi dịch vụ độc lập (Microservice)     │\n  │     - Pipeline AI:                                     │\n  │       1. Face Detection (MTCNN / RetinaFace)           │\n  │       2. Anti-Spoofing (MiniFASNet Liveness)           │\n  │       3. Feature Extraction (ArcFace 512-d Embedding)  │\n  └────────────────────────────────────────────────────────┘")

    add_p("Lý do lựa chọn mô hình kiến trúc lai:")
    add_bullet("Phân tách tuyệt đối trách nhiệm nghiệp vụ và tính toán AI: Ứng dụng web quản trị (Core Web App) được phát triển bằng PHP theo mô hình MVC, tập trung giải quyết toàn bộ các luồng nghiệp vụ quản lý nhân sự, ca kíp, đơn từ và bảng công trên cơ sở dữ liệu quan hệ MySQL. Sự ổn định và tốc độ phản hồi nhanh của PHP/MySQL đảm bảo trải nghiệm quản trị mượt mà.")
    add_bullet("Khai thác tối đa hệ sinh thái AI của Python: Dịch vụ nhận diện khuôn mặt được đóng gói thành một vi dịch vụ độc lập chạy bằng Python FastAPI. FastAPI là nền tảng API bất đồng bộ (Asynchronous) hiện đại có tốc độ xử lý hàng đầu hiện nay, tương thích hoàn hảo với các thư viện thị giác máy tính như OpenCV, ONNX Runtime và PyTorch.")
    add_bullet("Bảo vệ hiệu năng và khả năng mở rộng: Các tác vụ suy luận mạng học sâu (Inference) đòi hỏi tải tính toán CPU/GPU rất lớn. Việc tách riêng thành Microservice giúp máy chủ web quản trị không bao giờ bị nghẽn tài nguyên khi hàng trăm nhân viên thực hiện quét mặt cùng lúc vào đầu giờ làm việc.")
    add_bullet("Giao tiếp bảo mật và chuẩn hóa: Hai hệ thống kết nối với nhau thông qua giao thức chuẩn RESTful API qua HTTP/JSON, được bảo vệ bằng cơ chế xác thực mã khóa bí mật (API Key Authentication qua header X-API-Key).")

    # TỔNG KẾT CHƯƠNG 2
    add_h1("TỔNG KẾT CHƯƠNG 2")
    add_p("Chương 2 đã trình bày một cách có hệ thống, toàn diện và sâu sắc toàn bộ nền tảng lý thuyết làm cơ sở khoa học cho việc xây dựng đề tài:")
    add_bullet("Làm rõ khái niệm, mục tiêu và tầm quan trọng của hệ thống thông tin quản lý chấm công đối với sự phát triển của tổ chức, đồng thời so sánh ưu nhược điểm của các giải pháp chấm công hiện hành để khẳng định tính cấp thiết của việc ứng dụng sinh trắc học khuôn mặt không tiếp xúc.")
    add_bullet("Phân tích chi tiết quy trình chuẩn của bài toán nhận diện khuôn mặt tự động, từ giai đoạn phát hiện (Detection), căn chỉnh chuẩn hóa (Alignment), kiểm tra tính sống chống giả mạo (Anti-Spoofing) cho đến trích xuất vector đặc trưng nhúng (Embedding) bằng hàm mất mát tối ưu hóa biên độ góc ArcFace và kỹ thuật so khớp Cosine Similarity.")
    add_bullet("Khảo sát chuyên sâu các mô hình học sâu tiên tiến (TinyFace, InsightFace Buffalo_L, MTCNN, MiniFASNet) để rút ra lựa chọn kỹ thuật tối ưu nhất cho bài toán thực tế của đề tài.")
    add_bullet("Phân tích cơ sở lý thuyết của kiến trúc phần mềm MVC và Microservices, từ đó xây dựng mô hình kiến trúc lai (Hybrid Architecture) kết hợp giữa hệ thống quản trị PHP MVC và vi dịch vụ nhận diện khuôn mặt Python FastAPI độc lập.")
    add_p("Những kết quả nghiên cứu lý thuyết trong chương này là tiền đề then chốt để tiến hành phân tích yêu cầu, thiết kế kiến trúc và hiện thực hóa phần mềm ở các chương tiếp theo của khóa luận.")

    # Save to document/ directory and workspace root
    os.makedirs(r"c:\Users\DELL5410\quanLyChamCong\document", exist_ok=True)
    target_path_doc = r"c:\Users\DELL5410\quanLyChamCong\document\CHUONG_2_CO_SO_LY_THUYET.docx"
    target_path_root = r"c:\Users\DELL5410\quanLyChamCong\CHUONG_2_CO_SO_LY_THUYET.docx"

    doc.save(target_path_doc)
    doc.save(target_path_root)
    print(f"File saved successfully to:\n1. {target_path_doc}\n2. {target_path_root}")

if __name__ == "__main__":
    build_chapter_2()

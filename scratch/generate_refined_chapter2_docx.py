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
    """Set subtle borders for an academic table."""
    tblPr = table._tbl.tblPr
    borders = parse_xml(
        f'<w:tblBorders {nsdecls("w")}>'
        f'<w:top w:val="single" w:sz="8" w:space="0" w:color="{color}"/>'
        f'<w:bottom w:val="single" w:sz="8" w:space="0" w:color="{color}"/>'
        f'<w:insideH w:val="single" w:sz="4" w:space="0" w:color="{color}"/>'
        f'<w:insideV w:val="none"/>'
        f'<w:left w:val="none"/>'
        f'<w:right w:val="none"/>'
        f'</w:tblBorders>'
    )
    tblPr.append(borders)

def build_refined_chapter_2():
    doc = docx.Document()

    # Page Setup - Standard Vietnamese Academic Format (A4: Left 3cm, Right 2cm, Top 2cm, Bottom 2cm)
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

    # =========================================================================
    # NỘI DUNG CHƯƠNG 2 ĐÃ ĐƯỢC CHỈNH SỬA TOÀN DIỆN THEO ĐÁNH GIÁ HỘI ĐỒNG
    # =========================================================================

    add_chapter_title("CHƯƠNG 2. CƠ SỞ LÝ THUYẾT")

    # 2.1
    add_h1("2.1. Khái niệm về hệ thống thông tin quản lý chấm công")

    add_h2("2.1.1. Khái niệm hệ thống thông tin quản lý (MIS)")
    add_p("Hệ thống thông tin quản lý (Management Information System - MIS) là tập hợp có cấu trúc gồm con người, phần cứng, phần mềm, cơ sở dữ liệu và mạng truyền thông, phối hợp nhằm thu thập, lưu trữ, xử lý và phân phối thông tin hỗ trợ công tác quản lý, điều hành và ra quyết định trong tổ chức. Trong môi trường doanh nghiệp hiện đại, MIS là công cụ then chốt giúp tự động hóa và chuẩn hóa các quy trình tác nghiệp nội bộ.")

    add_h2("2.1.2. Khái niệm và phạm vi hệ thống thông tin quản lý chấm công (TAMIS)")
    add_p("Hệ thống thông tin quản lý chấm công (Time & Attendance Management Information System - TAMIS) là một phân hệ chuyên biệt trong quản trị nguồn nhân lực, chịu trách nhiệm số hóa và theo dõi toàn diện việc chấp hành thời gian làm việc của người lao động. Trong phạm vi nghiên cứu của đề tài, hệ thống tập trung giải quyết các đối tượng dữ liệu và chức năng nghiệp vụ trọng tâm sau:")
    add_bullet("Quản lý hồ sơ nhân viên: Lưu trữ thông tin định danh, phòng ban, chức vụ, trạng thái công tác và dữ liệu khuôn mặt phục vụ xác thực sinh trắc học.")
    add_bullet("Quản lý ca làm việc: Thiết lập các khung giờ làm việc linh hoạt (giờ bắt đầu, giờ kết thúc, thời gian nghỉ giữa ca, dung sai cho phép đi muộn hoặc về sớm) và phân bổ lịch phân ca cho từng cá nhân hoặc bộ phận.")
    add_bullet("Ghi nhận bản ghi vào/ra (Check-in / Check-out): Thu thập mốc thời gian thực tế kèm định danh nhân viên và bằng chứng hình ảnh khi quét qua thiết bị chấm công.")
    add_bullet("Xử lý quy trình đơn từ: Tiếp nhận và điều phối xét duyệt trực tuyến các loại đơn xin nghỉ phép, đơn xin đi muộn/về sớm, đơn giải trình quên chấm công theo phân quyền quản lý.")
    add_bullet("Tổng hợp bảng công và báo cáo thống kê: Tự động đối soát giữa dữ liệu quẹt công thực tế với lịch ca phân công để tính toán ngày công chuẩn, giờ làm thực tế, thời lượng vi phạm, từ đó kết xuất các báo cáo chuyên cần phục vụ công tác điều hành hoặc cung cấp số liệu đầu vào cho quy trình tính lương.")

    add_h2("2.1.3. Vai trò của hệ thống chấm công đối với tổ chức")
    add_p("Việc triển khai hệ thống thông tin chấm công mang lại các giá trị cốt lõi:")
    add_bullet("Nâng cao tính khách quan và minh bạch: Giảm thiểu sự can thiệp chủ quan trong theo dõi chuyên cần, bảo đảm quyền lợi chính đáng cho người lao động.")
    add_bullet("Tiết kiệm thời gian vận hành: Cắt giảm phần lớn công việc đối soát thủ công của bộ phận nhân sự, hạn chế tối đa sai lệch trong quá trình tổng hợp số liệu.")
    add_bullet("Tăng cường kỷ luật lao động: Cung cấp báo cáo trực quan và kịp thời về tỷ lệ hiện diện, hỗ trợ quản lý điều phối nhân sự hiệu quả.")

    # 2.2
    add_h1("2.2. Tổng quan các hệ thống chấm công liên quan")
    add_p("Quá trình phát triển của các công nghệ ghi nhận thời gian làm việc phản ánh rõ nét sự chuyển dịch từ thao tác thủ công sang tự động hóa.")

    add_h2("2.2.1. Hệ thống chấm công truyền thống")
    add_bullet("Chấm công bằng sổ sách: Nhân viên tự ký tên hoặc người quản lý ghi nhận giờ đến/về vào sổ theo dõi. Phương pháp này có độ tin cậy thấp, dễ phát sinh tình trạng ghi chép tùy tiện, ghi hộ giờ và tốn nhiều công sức tổng hợp thủ công vào cuối tháng.")
    add_bullet("Chấm công bằng máy thẻ giấy bấm giờ (Punch Card): Sử dụng máy dập giờ cơ học để in mốc thời gian lên thẻ giấy. Dù đã tự động hóa việc ghi nhận mốc giờ, việc tổng hợp công cuối kỳ vẫn đòi hỏi nhân sự phải nhập liệu lại vào bảng tính, tiềm ẩn nguy cơ sai sót và thất lạc dữ liệu lưu trữ vật lý.")

    add_h2("2.2.2. Hệ thống chấm công bằng thẻ từ và vân tay")
    add_bullet("Hệ thống thẻ từ (RFID / Proximity Card): Dựa trên công nghệ sóng vô tuyến (dải tần 125 kHz hoặc 13.56 MHz). Thẻ có độ bền cao, thao tác quẹt nhanh (dưới 1 giây). Tuy nhiên, hạn chế cố hữu của phương pháp này là không xác thực được người quẹt thẻ thực tế, dẫn đến nguy cơ quẹt thẻ hộ (Buddy Punching), đồng thời phát sinh chi phí in ấn và cấp lại khi nhân viên quên hoặc mất thẻ.")
    add_bullet("Hệ thống sinh trắc học vân tay (Fingerprint Biometrics): Sử dụng cảm biến quang học hoặc điện dung để trích xuất các điểm đặc trưng hình thái (Minutiae) của vân tay. Phương pháp này khắc phục được vấn đề chấm công hộ nhờ tính độc nhất của sinh trắc học. Dù vậy, phương pháp bộc lộ nhiều điểm hạn chế: bắt buộc phải tiếp xúc bề mặt cảm ứng gây lo ngại về vệ sinh; tỷ lệ từ chối sai (FRR) tăng cao khi ngón tay bị dính bẩn, dầu mỡ, trầy xước hoặc vân tay bị mòn do tuổi tác/lao động chân tay; tốc độ quét còn chậm gây ùn ứ vào khung giờ cao điểm.")

    add_h2("2.2.3. Hệ thống chấm công qua ứng dụng di động/GPS")
    add_p("Một số giải pháp hiện đại cho phép nhân viên chấm công trực tiếp trên điện thoại thông minh thông qua tọa độ định vị vệ tinh GPS kết hợp khoanh vùng địa lý (Geofencing) của cơ quan. Một số hệ thống nghiên cứu mở rộng có thể đối chiếu thêm mã trạm phát Wi-Fi nội bộ (BSSID) như một lớp tham chiếu phụ trợ.")
    add_p("Ưu điểm nổi bật của phương thức này là tính linh hoạt cao, phù hợp với mô hình làm việc từ xa hoặc nhân viên thường xuyên di chuyển ngoài hiện trường. Tuy nhiên, phương pháp tiềm ẩn rủi ro bị can thiệp bởi các ứng dụng giả lập tọa độ ảo (Fake GPS / Mock Location), phụ thuộc lớn vào phần cứng và tình trạng kết nối của thiết bị cá nhân, đồng thời làm dấy lên các quan ngại về quyền riêng tư khi theo dõi vị trí nhân viên.")

    add_h2("2.2.4. Ưu điểm, hạn chế và định hướng cải tiến")
    add_p("Bảng 2.1 tổng hợp so sánh các phương thức chấm công theo các tiêu chí kỹ thuật và vận hành chính:")

    # Table 2.1
    table1 = doc.add_table(rows=6, cols=6)
    table1.alignment = WD_TABLE_ALIGNMENT.CENTER
    set_table_borders(table1)

    headers1 = ["Tiêu chí", "Sổ sách / Thẻ giấy", "Thẻ từ RFID", "Vân tay", "Di động / GPS", "Nhận diện khuôn mặt"]
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
        ["Chi phí đầu tư", "Rất thấp", "Thấp", "Trung bình", "Thấp (Dùng ĐT cá nhân)", "Trung bình - Tiết kiệm"],
        ["Tốc độ ghi nhận", "Rất chậm", "Rất nhanh (< 1s)", "Trung bình (1-2s)", "Phụ thuộc kết nối mạng", "Nhanh (< 0.5s)"],
        ["Khả năng hạn chế gian lận", "Kém", "Kém (Dễ quẹt hộ)", "Tốt", "Trung bình (Dễ Fake GPS)", "Rất cao (Khi có Anti-Spoofing)"],
        ["Tính tiện dụng", "Kém", "Trung bình (Dễ quên thẻ)", "Tốt", "Tốt", "Tối ưu (Không cần mang vật dụng)"],
        ["Hình thức tiếp xúc", "Không tiếp xúc chung", "Chạm thẻ", "Tiếp xúc cảm ứng", "Thiết bị cá nhân", "Không tiếp xúc (Contactless)"]
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

    p_cap1 = doc.add_paragraph()
    p_cap1.alignment = WD_ALIGN_PARAGRAPH.CENTER
    p_cap1.paragraph_format.space_before = Pt(4)
    p_cap1.paragraph_format.space_after = Pt(10)
    run_c1 = p_cap1.add_run("Bảng 2.1: So sánh tổng quan giữa các phương thức chấm công hiện nay")
    run_c1.italic = True
    run_c1.font.name = 'Times New Roman'
    run_c1.font.size = Pt(11)

    add_p("Định hướng cải tiến: Nhận diện khuôn mặt bằng thị giác máy tính là giải pháp tối ưu nhằm kết hợp ưu thế không tiếp xúc vật lý (Contactless), thao tác tự nhiên, và hạn chế tối đa tình trạng chấm công hộ. Tuy nhiên, để hệ thống thực sự khả thi trong triển khai thực tế, giải pháp nhận diện cần được nghiên cứu đồng bộ với cơ chế kiểm tra tính sống (Liveness Detection / Anti-Spoofing) nhằm ngăn ngừa rủi ro giả mạo bằng hình ảnh hoặc video.")

    # 2.3
    add_h1("2.3. Tổng quan các phương pháp nhận diện khuôn mặt")
    add_p("Quy trình nhận diện khuôn mặt tự động trong bài toán chấm công là một pipeline xử lý tuần tự gồm các giai đoạn kỹ thuật chính:")
    add_code_block("  [Camera ghi hình khuôn mặt] \n               │\n               ▼\n  [1. Phát hiện khuôn mặt (Face Detection)] ──► Định vị Bounding Box & Điểm mốc (Landmarks)\n               │\n               ▼\n  [2. Căn chỉnh chuẩn hóa (Face Alignment)] ──► Phép biến đổi Affine xoay thẳng & đưa về 112x112\n               │\n               ▼\n  [3. Kiểm tra tính sống (Anti-Spoofing)]    ──► Xác thực người thật, ngăn ảnh in / video phát lại\n               │ (Hợp lệ)\n               ▼\n  [4. Trích xuất đặc trưng (Embedding)]     ──► Mạng nơ-ron sâu trích xuất Vector nhúng 512 chiều\n               │\n               ▼\n  [5. So khớp đặc trưng (Matching)]         ──► Tính độ tương đồng Cosine với dữ liệu đã đăng ký\n               │ (Đạt ngưỡng quy định)\n               ▼\n  [Ghi nhận giờ vào/ra & Cập nhật dữ liệu chấm công]")

    add_h2("2.3.1. Phát hiện và xử lý khuôn mặt (Face Detection & Preprocessing)")
    add_p("Phát hiện khuôn mặt là bước đầu vào bắt buộc, có nhiệm vụ xác định sự hiện diện của khuôn mặt trong ảnh, xác định tọa độ khung chữ nhật bao quanh (Bounding Box) và vị trí của các điểm mốc hình thái (Facial Landmarks như mắt, mũi, miệng).")
    add_p("Trong lịch sử, phương pháp Haar Cascade của Viola-Jones và HOG kết hợp bộ phân loại Linear SVM từng được ứng dụng phổ biến nhờ tốc độ tính toán nhanh. Dẫu vậy, các kỹ thuật truyền thống này dễ bỏ sót hoặc phát hiện sai khi khuôn mặt xoay góc nghiêng, bị che khuất hoặc trong điều kiện ánh sáng không đồng đều.")
    add_p("Sự phát triển của mạng nơ-ron tích chập (CNN) đã đem lại các bước tiến nhảy vọt. Các kiến trúc học sâu hiện đại (như MTCNN, RetinaFace, SSD) có khả năng định vị chính xác vùng mặt và các điểm mốc với sai số cực nhỏ ngay cả trong môi trường ánh sáng phức tạp.")
    add_p("Căn chỉnh khuôn mặt (Face Alignment): Ảnh chụp thực tế thường bị nghiêng hoặc lệch trục. Nhằm tránh làm sai lệch phân bố đặc trưng khi trích xuất, thuật toán sử dụng phép biến đổi Affine 2D dựa trên tọa độ 5 điểm mốc chuẩn (2 mắt, 1 đỉnh mũi, 2 khóe miệng):")
    add_formula_box("x' = s · R(θ) · x + t", "2.1")
    add_p("Trong đó, s là hệ số tỉ lệ thu phóng, R(θ) là ma trận quay theo góc nghiêng θ giữa hai mắt, và t là vector dịch chuyển tịnh tiến. Phép biến đổi này đưa ảnh khuôn mặt về tư thế thẳng chuẩn hóa với kích thước cố định (thông thường là 112 × 112 pixels).")

    add_h2("2.3.2. Trích xuất đặc trưng và so khớp (Feature Extraction & Matching)")
    add_p("Trích xuất đặc trưng khuôn mặt (Face Embedding): Mô hình học sâu đóng vai trò là hàm ánh xạ phi tuyến f(x), chuyển đổi bức ảnh khuôn mặt đã căn chỉnh x thành một vector số thực đa chiều trong không gian Euclid:")
    add_formula_box("f(x) ∈ ℝ^d    (với d = 512 chiều)", "2.2")
    add_p("Vector 512 chiều này là đại diện số hóa của các đặc trưng nhân trắc học cá nhân. Để đạt được năng lực phân biệt cao, các mô hình học sâu hiện đại sử dụng hàm mất mát tối ưu hóa biên độ góc (Margin-based Loss), tiêu biểu là ArcFace (Additive Angular Margin Loss) [2]. ArcFace chuẩn hóa các vector đặc trưng và ma trận trọng số về mặt cầu đơn vị, sau đó bổ sung trực tiếp một lượng phạt góc m vào góc quay giữa vector đặc trưng và trọng số của lớp mục tiêu, giúp tối thiểu hóa khoảng cách nội lớp và tối đa hóa khoảng cách giữa các cá nhân khác nhau.")
    add_p("So khớp đặc trưng (Feature Matching): Mức độ tương đồng sinh trắc học giữa vector khuôn mặt truy vấn quét từ camera (v₁) và vector khuôn mặt nhân viên lưu trong hệ thống (v₂) được đo bằng Độ tương đồng Cosine (Cosine Similarity):")
    add_formula_box("Cosine Similarity(v₁, v₂) = cos(θ) = (v₁ · v₂) / (||v₁||₂ · ||v₂||₂)", "2.3")
    add_p("Khi các vector đặc trưng đã được chuẩn hóa theo chuẩn L₂ (||v₁||₂ = ||v₂||₂ = 1), công thức quy về phép tích vô hướng: Similarity(v₁, v₂) = v₁ · v₂. Giá trị tương đồng thuộc đoạn [-1, 1]. Hệ thống thiết lập một ngưỡng quyết định τ (ngưỡng thực nghiệm thường nằm trong khoảng 0.40 - 0.50):")
    add_bullet("Nếu Similarity(v₁, v₂) ≥ τ: Xác nhận khuôn mặt trùng khớp với hồ sơ nhân viên đã đăng ký.")
    add_bullet("Nếu Similarity(v₁, v₂) < τ: Xác nhận không trùng khớp hoặc đối tượng chưa được cấp quyền.")

    add_h2("2.3.3. Kiểm tra tính sống và chống giả mạo (Liveness Detection & Anti-Spoofing)")
    add_p("Trong môi trường doanh nghiệp thực tế, hệ thống nhận diện khuôn mặt luôn phải đối mặt với các hình thức tấn công giả mạo trình diện (Presentation Attacks - PA):")
    add_bullet("Tấn công bằng ảnh in 2D (Print Attack): Sử dụng ảnh chân dung in trên giấy hoặc ảnh chụp chất lượng cao đưa trước camera.")
    add_bullet("Tấn công phát lại qua màn hình (Replay Attack): Sử dụng màn hình smartphone, tablet hoặc laptop phát lại video hoặc ảnh của nhân viên.")
    add_bullet("Tấn công mặt nạ 3D (3D Mask Attack): Sử dụng mô hình mặt nạ nhân tạo mô phỏng khuôn mặt.")
    add_p("Về phương pháp luận, các kỹ thuật kiểm tra tính sống được phân loại thành:")
    add_bullet("Kiểm tra tính sống chủ động (Active Liveness): Bắt buộc người dùng thực hiện một chuỗi thao tác ngẫu nhiên theo lệnh của phần mềm (chớp mắt, mỉm cười, quay đầu sang trái/phải). Phương pháp này có độ tin cậy tốt nhưng làm kéo dài thời gian xử lý và giảm trải nghiệm người dùng tại trạm chấm công.")
    add_bullet("Kiểm tra tính sống thụ động (Passive Liveness): Phân tích đặc trưng trực tiếp từ khung hình tĩnh mà không yêu cầu người dùng tương tác thêm. Kỹ thuật này khai thác sự khác biệt về vi kết cấu bề mặt da, phản xạ quang học và hiện tượng vân sọc giao thoa Moire phát ra từ màn hình điện tử, kết hợp cùng các mạng nơ-ron học sâu chuyên biệt (như MiniFASNet [5]) để đưa ra điểm số tính sống (Liveness Score).")
    add_p("Cần nhấn mạnh rằng, các giải pháp chống giả mạo thụ động dựa trên phần mềm chỉ giúp giảm thiểu đáng kể nguy cơ gian lận trong điều kiện ánh sáng chuẩn và không thể bảo đảm ngăn chặn tuyệt đối 100% mọi hình thức tấn công tinh vi trong mọi hoàn cảnh thực tế nếu không có sự phối hợp của cảm biến chuyên dụng (như camera hồng ngoại IR hoặc camera độ sâu 3D ToF).")

    add_h2("2.3.4. Các chỉ số đo lường hiệu năng nhận diện sinh trắc học")
    add_p("Để đánh giá khách quan độ chính xác và hiệu năng của hệ thống nhận diện khuôn mặt, các chỉ số đo lường sinh trắc học chuẩn được định nghĩa như sau:")
    add_bullet("Tỷ lệ chấp nhận sai (False Acceptance Rate - FAR): Tỷ lệ phần trăm các trường hợp hệ thống nhận diện nhầm một người không có quyền (hoặc người khác) thành nhân viên hợp lệ:")
    add_formula_box("FAR = (Số lần chấp nhận sai / Tổng số lần thử của người không hợp lệ) × 100%", "2.4")
    add_bullet("Tỷ lệ từ chối sai (False Rejection Rate - FRR): Tỷ lệ phần trăm các trường hợp nhân viên hợp lệ bị hệ thống từ chối nhận diện:")
    add_formula_box("FRR = (Số lần từ chối sai / Tổng số lần thử của nhân viên hợp lệ) × 100%", "2.5")
    add_bullet("Độ chính xác tổng thể (Accuracy / TAR - True Acceptance Rate): Tỷ lệ các trường hợp hệ thống đưa ra quyết định xác thực đúng trên toàn bộ tập dữ liệu thử nghiệm.")
    add_bullet("Thời gian xử lý (Inference Latency): Tổng thời gian trễ từ thời điểm camera thu nhận khung hình đến khi hoàn tất quá trình nhận diện (đo bằng mili-giây - ms hoặc khung hình trên giây - FPS).")
    add_p("Trong thiết kế hệ thống chấm công, luôn tồn tại sự đánh đổi giữa FAR và FRR phụ thuộc vào việc lựa chọn ngưỡng so khớp τ. Việc thiết lập ngưỡng cần cân bằng giữa yêu cầu bảo mật nghiêm ngặt (hạ thấp FAR) và tính thuận tiện cho nhân viên (hạ thấp FRR).")

    add_h2("2.3.5. Liên kết giữa nhận diện khuôn mặt và bài toán quản lý chấm công")
    add_p("Thị giác máy tính chỉ là công cụ định danh ở tầng đầu vào; để hoàn chỉnh nghiệp vụ chấm công, kết quả nhận diện phải được tích hợp chặt chẽ với logic quản trị của hệ thống:")
    add_bullet("Cơ chế đăng ký dữ liệu khuôn mặt (Enrollment): Nhân viên được chụp ảnh mẫu trong điều kiện chuẩn để trích xuất và lưu trữ vector nhúng chuẩn (Baseline Embedding) vào hệ thống.")
    add_bullet("Ràng buộc tính sống và ngưỡng nhận diện: Chỉ khi điểm số tính sống vượt qua ngưỡng an toàn (ví dụ: Liveness Score ≥ 0.60) và độ tương đồng khuôn mặt vượt ngưỡng (Cosine Similarity ≥ 0.40), danh tính nhân viên mới được công nhận.")
    add_bullet("Quy tắc nghiệp vụ ghi nhận giờ vào/ra: Dữ liệu nhận diện thành công được đối chiếu với lịch ca phân công của ngày làm việc để tự động phân định bản ghi Check-in (đầu ca) hoặc Check-out (cuối ca), kiểm tra trạng thái đi muộn/về sớm và ngăn chặn hành vi quẹt công trùng lặp liên tiếp trong khoảng thời gian ngắn.")

    # 2.4
    add_h1("2.4. Các mô hình nhận diện khuôn mặt được nghiên cứu")

    add_h2("2.4.1. TinyFace Detector")
    add_p("Mô hình TinyFace được đề xuất bởi Peiyun Hu và Deva Ramanan [1] tại hội nghị CVPR 2017 nhằm giải quyết bài toán phát hiện khuôn mặt kích thước siêu nhỏ (dưới 20 pixels) trong ảnh chụp ở cự ly xa hoặc có độ phân giải thấp. Cần phân biệt rõ: TinyFace thuần túy là một bộ phát hiện khuôn mặt (Face Detector), không đảm nhiệm chức năng trích xuất vector đặc trưng danh tính hay nhận diện người.")
    add_p("Đặc điểm kiến trúc của TinyFace:")
    add_bullet("Khai thác ngữ cảnh mở rộng (Contextual Cues): Khi khuôn mặt quá nhỏ hoặc bị mờ, các chi tiết nội tại hầu như bị suy giảm hoàn toàn; TinyFace mở rộng vùng tiếp nhận ra các bộ phận lân cận như bờ vai và cổ để gia tăng độ tin cậy phát hiện.")
    add_bullet("Kim tự tháp đặc trưng đa tỉ lệ (Scale-specific Detectors): Huấn luyện các bộ dò riêng biệt tối ưu cho từng dải kích thước khuôn mặt khác nhau.")
    add_p("Đánh giá thực tế trong đề tài: Trong giai đoạn nghiên cứu ban đầu, TinyFace đã được khảo sát cho bài toán phát hiện khuôn mặt. Tuy nhiên, qua phân tích và thử nghiệm sơ bộ trong điều kiện trạm chấm công thực tế (nơi nhân viên chủ động đứng ở khoảng cách tiêu chuẩn từ 0.5m đến 1.2m trước camera), TinyFace bộc lộ sự không phù hợp: kiến trúc mô hình nặng, tốc độ xử lý chậm và tiêu tốn nhiều tài nguyên tính toán trong khi lợi thế phát hiện mặt siêu nhỏ ở cự ly xa không được tận dụng. Do đó, nhóm nghiên cứu đã chuyển đổi sang các giải pháp dò khuôn mặt nhẹ và tối ưu hơn cho bài toán cự ly gần.")

    add_h2("2.4.2. InsightFace – Bộ mô hình Buffalo_L")
    add_p("Nhằm tránh sự nhầm lẫn giữa các khái niệm kỹ thuật, cần phân định rõ ba thành phần cốt lõi:")
    add_bullet("InsightFace: Là hệ sinh thái mã nguồn mở hàng đầu thế giới về phân tích khuôn mặt 2D/3D (do DeepInsight phát triển), cung cấp nền tảng toàn diện từ huấn luyện, tối ưu hóa đến triển khai mô hình.")
    add_bullet("Buffalo_L: Là gói mô hình được đóng gói và cấu hình sẵn (Pre-packaged Model Pack) thuộc InsightFace. Gói này bao gồm nhiều mô hình phục vụ các tác vụ chuyên biệt: mô hình phát hiện khuôn mặt RetinaFace-ResNet50 (tệp det_10g.onnx) kết hợp trích xuất 5 điểm mốc chuẩn; mô hình trích xuất đặc trưng embedding 512 chiều (tệp w600k_r50.onnx); cùng các mô hình phụ trợ xác định giới tính, độ tuổi và điểm mốc 3D.")
    add_bullet("ArcFace: Là phương pháp học biểu diễn đặc trưng khuôn mặt đề xuất bởi Jiankang Deng và cộng sự [2] với hàm mất mát Additive Angular Margin Loss. ArcFace là cơ sở thuật toán được áp dụng để huấn luyện mô hình trích xuất đặc trưng trong gói Buffalo_L, chứ không phải tên gọi đồng nhất của toàn bộ gói mô hình.")
    add_p("Hàm mất mát ArcFace (Additive Angular Margin Loss):")
    add_formula_box("L_ArcFace = - (1/N) ∑_{i=1}^N log [ e^(s · cos(θ_{y_i} + m)) / ( e^(s · cos(θ_{y_i} + m)) + ∑_{j ≠ y_i} e^(s · cos θ_j) ) ]", "2.6")
    add_p("Trong đó: N là kích thước lô huấn luyện; s là hệ số tỷ lệ bán kính mặt cầu (chuẩn s = 64); m là tham số phạt biên góc cộng thêm (m = 0.5 radian); θ_{y_i} là góc giữa vector đặc trưng chuẩn hóa và trọng số của lớp nhãn đúng. Tham số phạt biên góc m trực tiếp cưỡng chế việc thu hẹp khoảng cách góc của cùng một danh tính trên mặt cầu đơn vị, giúp vector đặc trưng 512 chiều có năng lực phân biệt danh tính vượt trội.")
    add_p("Cần lưu ý: Số liệu độ chính xác công bố đạt trên 99.8% của mô hình sử dụng ArcFace là kết quả đánh giá trên bộ dữ liệu chuẩn học thuật quốc tế LFW (Labeled Faces in the Wild [7]) theo công bố của nhóm tác giả gốc, phục vụ làm cơ sở tham chiếu lý thuyết chứ không phải kết quả thử nghiệm trực tiếp trên dữ liệu nội bộ của hệ thống này.")

    add_h2("2.4.3. Một số mô hình và giải pháp khác")
    add_bullet("MTCNN (Multi-task Cascaded Convolutional Networks) [3]: Mạng nơ-ron phân tầng 3 giai đoạn (P-Net, R-Net, O-Net) thực hiện đồng thời phát hiện khuôn mặt và định vị 5 điểm mốc. MTCNN có ưu điểm dung lượng siêu nhẹ (khoảng 2MB), tốc độ suy luận nhanh trên CPU thông thường và triển khai thuận tiện.")
    add_bullet("MobileFaceNet [4]: Mạng nơ-ron thu nhỏ ứng dụng các lớp tích chập tách biệt theo chiều sâu (Depthwise Separable Convolution), tối ưu cho các thiết bị di động và nhúng.")
    add_bullet("MiniFASNet [5]: Mô hình học sâu gọn nhẹ phục vụ bài toán chống giả mạo khuôn mặt thụ động (Silent Face Anti-Spoofing), được thiết kế dựa trên các lớp tích chập Central Difference Convolution (CDC) nhằm phát hiện sự sai khác bề mặt giữa khuôn mặt người thật và bề mặt giả mạo.")

    # Table 2.2
    table2 = doc.add_table(rows=6, cols=6)
    table2.alignment = WD_TABLE_ALIGNMENT.CENTER
    set_table_borders(table2)

    headers2 = ["Mô hình", "Nhiệm vụ chính", "Kiến trúc Backbone", "Dung lượng", "Tốc độ xử lý", "Độ chính xác tham chiếu (LFW)"]
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
        ["TinyFace [1]", "Face Detection", "ResNet101", "~150 MB", "Chậm (~200ms)", "Cao với mặt nhỏ/xa"],
        ["MTCNN [3]", "Detection & Align", "3-Stage CNN", "~2 MB", "Rất nhanh (~30ms)", "95.1% (Tham chiếu [3])"],
        ["InsightFace Buffalo_L [2,6]", "Detection & Embedding", "ResNet50", "~160 MB", "Nhanh (~60ms GPU)", "99.83% (Công bố [2])"],
        ["MobileFaceNet [4]", "Face Embedding", "MobileNetV2", "~4 MB", "Cực nhanh (~15ms)", "99.28% (Công bố [4])"],
        ["MiniFASNet [5]", "Anti-Spoofing (Passive)", "MiniFASNetV2", "~3 MB", "Rất nhanh (~20ms)", "SOTA trên OULU-NPU [5]"]
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
            else:
                p.alignment = WD_ALIGN_PARAGRAPH.CENTER
                run = p.add_run(val)
            run.font.name = 'Times New Roman'
            run.font.size = Pt(11)

    p_cap2 = doc.add_paragraph()
    p_cap2.alignment = WD_ALIGN_PARAGRAPH.CENTER
    p_cap2.paragraph_format.space_before = Pt(4)
    p_cap2.paragraph_format.space_after = Pt(10)
    run_c2 = p_cap2.add_run("Bảng 2.2: So sánh thông số kỹ thuật và độ chính xác tham chiếu của các mô hình nghiên cứu")
    run_c2.italic = True
    run_c2.font.name = 'Times New Roman'
    run_c2.font.size = Pt(11)

    # 2.5
    add_h1("2.5. Kiến trúc MVC và Microservices trong phát triển phần mềm")

    add_h2("2.5.1. Kiến trúc MVC (Model - View - Controller)")
    add_p("Kiến trúc MVC là một mẫu kiến trúc phần mềm tiêu chuẩn trong phát triển ứng dụng web, dựa trên nguyên lý phân tách mối quan tâm (Separation of Concerns - SoC):")
    add_bullet("Model: Đảm nhiệm biểu diễn cấu trúc dữ liệu, các ràng buộc quan hệ và toàn bộ logic nghiệp vụ (quản lý nhân sự, phân ca, phê duyệt đơn từ, tính toán dữ liệu công). Model tương tác trực tiếp với cơ sở dữ liệu quan hệ (MySQL).")
    add_bullet("View: Phụ trách giao diện trực quan và trải nghiệm người dùng (HTML, CSS, JavaScript). View hiển thị thông tin và gửi yêu cầu hành động của người dùng đến hệ thống.")
    add_bullet("Controller: Tiếp nhận các yêu cầu HTTP từ View, điều phối xử lý dữ liệu thông qua Model, và trả về phản hồi thích hợp (giao diện web hoặc dữ liệu JSON).")
    add_p("Kiến trúc MVC đem lại cấu trúc mã nguồn tường minh, dễ bảo trì và phân công công việc. Tuy nhiên, nếu xây dựng theo dạng một khối duy nhất (Monolith), việc tích hợp trực tiếp các tác vụ tính toán ma trận nặng của trí tuệ nhân tạo vào Web Server sẽ dễ gây quá tải và làm chậm các chức năng quản trị thông thường.")

    add_h2("2.5.2. Kiến trúc Microservices (Kiến trúc vi dịch vụ)")
    add_p("Kiến trúc Microservices phân rã hệ thống thành các dịch vụ nhỏ hoạt động độc lập, mỗi dịch vụ chạy trên tiến trình riêng và giao tiếp thông qua các giao thức mạng chuẩn hóa (như RESTful API qua HTTP/JSON). Các ưu điểm cốt lõi gồm:")
    add_bullet("Độc lập về công nghệ (Polyglot Programming): Cho phép sử dụng ngôn ngữ lập trình và thư viện tối ưu nhất cho từng nhiệm vụ chuyên biệt (ví dụ: PHP cho quản lý dữ liệu nghiệp vụ, Python cho xử lý trí tuệ nhân tạo).")
    add_bullet("Khả năng mở rộng linh hoạt (Scalability): Có thể cấp phát tài nguyên tính toán (CPU, RAM, GPU) độc lập cho dịch vụ có lưu lượng xử lý cao.")
    add_bullet("Cô lập lỗi (Fault Isolation): Tránh tình trạng sự cố ở phân hệ tính toán nặng làm sụp đổ toàn bộ hệ thống quản trị chung.")

    add_h2("2.5.3. Mô hình kiến trúc phân tách thực tế của hệ thống (Core Web MVC & Face Service)")
    add_p("Dựa trên đặc thù công nghệ của đề tài, hệ thống được thiết kế theo mô hình kiến trúc phân tách giữa Ứng dụng quản trị trung tâm và Dịch vụ nhận diện khuôn mặt:")
    add_code_block("  [Người dùng / Thiết bị đầu cuối] \n                 │\n                 ▼  (HTTP Web Requests)\n  ┌────────────────────────────────────────────────────────┐\n  │     HỆ THỐNG QUẢN TRỊ TRUNG TÂM (CORE WEB APP - MVC)   │\n  │     - Nền tảng: PHP (Kiến trúc MVC chuẩn)              │\n  │     - Cơ sở dữ liệu: MySQL Database                    │\n  │     - Nhiệm vụ: Quản lý nhân viên, phân ca làm việc,   │\n  │       xử lý đơn từ, đối soát giờ vào/ra, báo cáo công  │\n  └───────────────────────────┬────────────────────────────┘\n                              │\n                              │ Gọi RESTful API (HTTP POST / JSON)\n                              │ [Cơ chế xác thực: Header X-API-Key]\n                              ▼\n  ┌────────────────────────────────────────────────────────┐\n  │        DỊCH VỤ NHẬN DIỆN KHUÔN MẶT (FACE SERVICE)      │\n  │     - Nền tảng: Python FastAPI + ONNX Runtime          │\n  │     - Cơ chế vận hành: Vi dịch vụ độc lập              │\n  │     - Các endpoints chính:                             │\n  │       • POST /detect     : Phát hiện khuôn mặt         │\n  │       • POST /anti-spoof : Kiểm tra tính sống          │\n  │       • POST /embedding  : Trích xuất vector 512 chiều │\n  │       • POST /verify     : Chuỗi xử lý tổng hợp        │\n  └────────────────────────────────────────────────────────┘")

    add_p("Mô tả chi tiết các thành phần kiến trúc đã triển khai:")
    add_bullet("Ứng dụng web quản trị (PHP MVC): Đóng vai trò hạt nhân điều hành toàn bộ quy trình nghiệp vụ của hệ thống chấm công. Ứng dụng quản lý các bảng cơ sở dữ liệu quan hệ trong MySQL, cung cấp giao diện trực quan cho nhân viên và quản lý. Khi phát sinh tác vụ nhận diện khuôn mặt, ứng dụng sử dụng lớp hỗ trợ FaceServiceClient để gửi yêu cầu xử lý ảnh tới dịch vụ AI.")
    add_bullet("Dịch vụ nhận diện khuôn mặt độc lập (Python FastAPI): Chạy trên một tiến trình riêng biệt (cổng 8000), tận dụng các thư viện chuyên dụng về thị giác máy tính và học sâu (ONNX Runtime, Pillow, NumPy, facenet-pytorch). Dịch vụ cung cấp các endpoint API chuẩn hóa để nhận ảnh mã hóa Base64 và phản hồi kết quả tính toán.")
    add_bullet("Cơ chế tích hợp và dự phòng linh hoạt: Hai thành phần giao tiếp qua giao thức RESTful API được bảo vệ bằng mã khóa bảo mật trong header X-API-Key. Cấu trúc mã nguồn cũng hỗ trợ cấu hình chuyển hướng linh hoạt giữa vi dịch vụ Python nội bộ và các endpoint dịch vụ nhận diện bên ngoài thông qua biến môi trường.")

    # TÀI LIỆU THAM KHẢO CHƯƠNG 2
    add_h1("TÀI LIỆU THAM KHẢO CHỌN LỌC CHƯƠNG 2")
    add_bullet("[1] P. Hu and D. Ramanan, 'Finding Tiny Faces', in IEEE Conference on Computer Vision and Pattern Recognition (CVPR), Honolulu, HI, USA, 2017, pp. 951-959.")
    add_bullet("[2] J. Deng, J. Guo, N. Xue, and S. Zafeiriou, 'ArcFace: Additive Angular Margin Loss for Deep Face Recognition', in IEEE/CVF Conference on Computer Vision and Pattern Recognition (CVPR), Long Beach, CA, USA, 2019, pp. 4690-4699.")
    add_bullet("[3] K. Zhang, Z. Zhang, Z. Li, and Y. Qiao, 'Joint Face Detection and Alignment Using Multitask Cascaded Convolutional Networks', IEEE Signal Processing Letters, vol. 23, no. 10, pp. 1499-1503, 2016.")
    add_bullet("[4] S. Chen, Y. Liu, X. Gao, and Z. Han, 'MobileFaceNets: Efficient CNNs for Accurate Real-time Face Verification on Mobile Devices', in Chinese Conference on Biometric Recognition (CCBR), Urumqi, China, 2018, pp. 428-438.")
    add_bullet("[5] Z. Yu et al., 'Searching Central Difference Convolutional Networks for Face Anti-Spoofing', in IEEE/CVF Conference on Computer Vision and Pattern Recognition (CVPR), Seattle, WA, USA, 2020, pp. 5295-5305.")
    add_bullet("[6] J. Deng, J. Guo, E. Ververas, I. Kotsia, and S. Zafeiriou, 'RetinaFace: Single-Shot Multi-Level Face Localisation in the Wild', in IEEE/CVF Conference on Computer Vision and Pattern Recognition (CVPR), Seattle, WA, USA, 2020, pp. 5203-5212.")
    add_bullet("[7] G. B. Huang, M. Mattar, T. Berg, and E. Learned-Miller, 'Labeled Faces in the Wild: A Database for Studying Face Recognition in Unconstrained Environments', University of Massachusetts, Amherst, Technical Report 07-49, 2008.")

    # Save to document/ directory, workspace root, and Desktop
    target_path_doc = r"c:\Users\DELL5410\quanLyChamCong\document\CHUONG_2_CO_SO_LY_THUYET.docx"
    target_path_root = r"c:\Users\DELL5410\quanLyChamCong\CHUONG_2_CO_SO_LY_THUYET.docx"
    target_path_desktop = r"C:\Users\DELL5410\Desktop\CHUONG_2_CO_SO_LY_THUYET.docx"

    doc.save(target_path_doc)
    doc.save(target_path_root)
    doc.save(target_path_desktop)
    print(f"Refined Chapter 2 successfully saved to:\n1. {target_path_doc}\n2. {target_path_root}\n3. {target_path_desktop}")

if __name__ == "__main__":
    build_refined_chapter_2()

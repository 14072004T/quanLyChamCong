import os
import docx
from docx.shared import Inches, Pt, RGBColor, Cm
from docx.enum.text import WD_ALIGN_PARAGRAPH
from docx.enum.table import WD_TABLE_ALIGNMENT, WD_ALIGN_VERTICAL
from docx.oxml import parse_xml
from docx.oxml.ns import nsdecls

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
    """Set subtle academic borders for a table."""
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

def build_chapter_5():
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
        run.font.size = Pt(9.5)
        doc.add_paragraph().paragraph_format.space_after = Pt(4)

    # =========================================================================
    # NỘI DUNG CHƯƠNG 5. GIẢI PHÁP CÔNG NGHỆ
    # =========================================================================

    add_chapter_title("CHƯƠNG 5. GIẢI PHÁP CÔNG NGHỆ")

    # 5.1
    add_h1("5.1. Tổng quan giải pháp nhận diện khuôn mặt trong hệ thống chấm công")
    add_p("Trong bối cảnh các tổ chức, doanh nghiệp đẩy mạnh chuyển đổi số và nâng cao tính chuyên nghiệp trong quản trị nhân lực, việc áp dụng công nghệ sinh trắc học không tiếp xúc (Contactless Biometrics) vào bài toán chấm công ngày càng trở nên cấp thiết. Các phương thức điểm danh truyền thống bằng thẻ từ hay vân tay quang học dần bộc lộ nhiều điểm nghẽn nghiêm trọng: nguy cơ chấm công hộ, thẻ bị quên hoặc mất, tiếp xúc vật lý gây mất vệ sinh dịch bệnh, và chi phí thiết bị nhập ngoại đắt đỏ.")
    add_p("Nhằm giải quyết triệt để những bài toán trên, đề tài đề xuất và hiện thực hóa một Giải pháp công nghệ nhận diện khuôn mặt tự động hóa toàn diện, hướng tới việc biến mọi thiết bị có gắn camera thông thường (như webcam máy tính, camera USB, hoặc máy tính bảng gắn tường) thành một trạm chấm công Kiosk thông minh. Mục tiêu giải pháp bao gồm:")
    add_bullet("Tối ưu hóa trải nghiệm người dùng: Thao tác chấm công diễn ra hoàn toàn tự nhiên, không tiếp xúc, thời gian xử lý và phản hồi danh tính dưới 0.5 giây.")
    add_bullet("Bảo mật và chống gian lận đa lớp: Kết hợp thuật toán nhận diện khuôn mặt độ chính xác cao với bộ lọc kiểm tra tính sống (Face Anti-Spoofing / Liveness Detection) nhằm ngăn chặn các hành vi sử dụng ảnh in hoặc video điện thoại để chấm công hộ.")
    add_bullet("Tự động hóa chu trình khép kín: Tích hợp trực tiếp dữ liệu nhận diện vào hệ thống quản lý ca kíp, tự động ghép cặp giờ vào/ra (Check-in / Check-out), tính toán giờ công chuẩn, thời lượng đi muộn, về sớm và kết xuất bảng công kỳ lương minh bạch.")

    # 5.2
    add_h1("5.2. Quy trình xử lý nhận diện khuôn mặt")
    add_p("Quy trình nhận diện khuôn mặt và chấm công tự động trong hệ thống được thiết kế theo một pipeline khép kín, kết nối liền mạch từ thiết bị đầu cuối của người dùng đến hệ thống xử lý trung tâm và vi dịch vụ AI:")
    add_code_block("  [Thiết bị Kiosk / Tablet / Trình duyệt]\n                     │\n      1. WebRTC Stream (Chụp khung hình Canvas)\n                     ▼\n  [Tiền xử lý Client] ──► Mã hóa Base64 JPEG + Phiên Liveness\n                     │\n      2. Gửi HTTP POST JSON (/api/tablet/scan hoặc /verify)\n                     ▼\n  [Core Web App - PHP MVC]\n                     │\n      3. Giao tiếp RESTful API (Kèm X-API-Key Authentication)\n                     ▼\n  [Face AI Service - Python FastAPI]\n       ├─► BƯỚC 1: Phát hiện khuôn mặt (MTCNN / RetinaFace) ──► 5 Landmarks\n       ├─► BƯỚC 2: Căn chỉnh Affine xoay thẳng & đưa về kích thước 112x112\n       ├─► BƯỚC 3: Kiểm tra tính sống (MiniFASNet CDC) ──► Liveness Score ≥ 0.60\n       └─► BƯỚC 4: Trích xuất ArcFace Embedding ──► Vector số thực 512 chiều\n                     │\n      4. Trả về kết quả xác thực & Vector nhúng\n                     ▼\n  [So khớp & Nghiệp vụ Chấm công - Core Backend]\n       ├─► So sánh Cosine Similarity với Vector khuôn mặt đã đăng ký (DB)\n       ├─► Kiểm tra điều kiện ngưỡng (Cosine ≥ 0.40) & Phân tích biên độ an toàn\n       ├─► Xác định Mã nhân viên duy nhất (MaND)\n       ├─► Đối chiếu lịch phân ca hôm nay ──► Ghi nhận Check-in / Check-out\n       └─► Lưu ảnh bằng chứng quét mặt vào thư mục lưu trữ hệ thống")

    add_h2("5.2.1. Thu nhận và xử lý hình ảnh")
    add_p("Quy trình bắt đầu từ việc thu nhận dữ liệu thị giác từ camera thiết bị đầu cuối:")
    add_bullet("Thu nhận luồng video (Video Stream Acquisition): Ứng dụng client sử dụng API chuẩn HTML5 MediaDevices.getUserMedia() để khởi tạo luồng camera thời gian thực qua WebRTC. Độ phân giải khung hình được tối ưu hóa ở mức HD (1280 × 720 pixels) nhằm cân bằng giữa độ chi tiết của khuôn mặt và băng thông truyền tải mạng.")
    add_bullet("Trích xuất khung hình và mã hóa (Frame Capture & Encoding): Khi phát hiện có người đứng trước màn hình, hệ thống trích xuất khung hình hiện tại lên đối tượng HTML5 Canvas ẩn, chuyển đổi thành chuỗi dữ liệu ảnh Base64 nén định dạng JPEG chất lượng cao (quality = 0.85). Chuỗi dữ liệu này được đóng gói vào payload HTTP POST kèm token kiểm tra tính hợp lệ của phiên.")
    add_bullet("Phát hiện khuôn mặt (Face Detection): Tại dịch vụ AI, bức ảnh giải mã được đưa qua mạng nơ-ron tích chập (MTCNN hoặc RetinaFace). Bộ phát hiện quét toàn bộ bức ảnh để xác định hộp bao chữ nhật (Bounding Box: x, y, width, height) và tọa độ 5 điểm mốc giải phẫu cốt lõi (2 đồng tử mắt, đỉnh chóp mũi, 2 khóe môi). Những bức ảnh không tìm thấy khuôn mặt hoặc khuôn mặt nằm quá sát mép ảnh sẽ bị từ chối ngay lập tức.")
    add_bullet("Căn chỉnh hình học (Face Alignment): Để khắc phục hiện tượng người dùng nghiêng đầu, ngửa mặt hoặc lệch góc nhìn, hệ thống áp dụng phép biến đổi Affine 2D dựa trên 5 điểm mốc hình thái đã phát hiện. Thuật toán tính toán ma trận xoay quanh tâm và tỉ lệ co giãn sao cho đường nối hai mắt luôn nằm ngang song song, cân đối vị trí trung tâm bức ảnh và cắt cúp (crop) chuẩn xác về kích thước tiêu chuẩn 112 × 112 pixels.")
    add_bullet("Chuẩn hóa dải màu (Color Normalization): Ảnh sau khi căn chỉnh được chuyển đổi hệ màu phù hợp theo quy ước của mô hình học sâu (BGR sang RGB hoặc ngược lại) và chuẩn hóa dải giá trị pixel từ [0, 255] về dải số thực chuẩn [-1.0, 1.0].")

    add_h2("5.2.2. Trích xuất đặc trưng và so khớp")
    add_p("Sau khi bức ảnh khuôn mặt đã được tiền xử lý và căn chỉnh chuẩn mực, dữ liệu được chuyển tiếp qua tầng trích xuất đặc trưng sinh trắc học:")
    add_bullet("Trích xuất Vector nhúng (Face Embedding Extraction): Bức ảnh khuôn mặt kích thước 112 × 112 × 3 được đưa qua mạng xương sống học sâu ResNet50 huấn luyện theo phương pháp ArcFace. Tại tầng tích chập cuối cùng kết hợp cấu trúc Batch Normalization và Dropout, mô hình nén toàn bộ thông tin nhân trắc học cá nhân thành một vector số thực gồm đúng 512 phần tử:")
    add_formula_box("v = f(x) = [e₁, e₂, e₃, ..., e₅₁₂] ∈ ℝ⁵¹²", "5.1")
    add_bullet("Chuẩn hóa vector theo chuẩn L₂: Nhằm triệt tiêu sự ảnh hưởng của độ lớn vector và đưa mọi biểu diễn lên bề mặt của một quả cầu siêu phẳng đơn vị, vector v được chuẩn hóa theo chuẩn L₂:")
    add_formula_box("v_norm = v / ||v||₂ = v / √(∑_{i=1}^{512} e_i²)", "5.2")
    add_bullet("Thuật toán so khớp Độ tương đồng Cosine (Cosine Similarity): Khi nhân viên thực hiện chấm công, vector khuôn mặt truy vấn quét tức thời (v_query) được so sánh lần lượt với các vector khuôn mặt của nhân viên đã được lưu trữ sẵn trong cơ sở dữ liệu (v_stored). Độ tương đồng được tính theo công thức tích vô hướng:")
    add_formula_box("Similarity(v_query, v_stored) = v_query · v_stored = ∑_{i=1}^{512} (v_query,i × v_stored,i)", "5.3")
    add_p("Giá trị Similarity nằm trong khoảng [-1.0, 1.0]. Hai vector càng chỉ về cùng một hướng trên mặt cầu siêu phẳng thì giá trị càng tiến gần về 1.0, minh chứng cho việc hai khuôn mặt thuộc về cùng một người.")

    add_h2("5.2.3. Xác định nhân viên")
    add_p("Để chuyển đổi từ chỉ số tương đồng toán học sang quyết định công nhận danh tính nhân viên trong hệ thống quản trị doanh nghiệp, hệ thống áp dụng cơ chế xác thực kép kết hợp phân tích biên độ an toàn (Margin Analysis):")
    add_bullet("Thiết lập ngưỡng quyết định cơ bản: Hệ thống áp dụng ngưỡng tương đồng chuẩn τ = 0.40 (hoặc 0.45 trong các cấu hình nghiêm ngặt). Nếu mức độ tương đồng lớn nhất giữa vector truy vấn và toàn bộ cơ sở dữ liệu nhỏ hơn τ, hệ thống kết luận: 'Không nhận diện được nhân viên'.")
    add_bullet("Cơ chế phân tích khoảng cách an toàn (Ambiguous Margin Check): Trong trường hợp có nhiều ứng viên đạt điểm số vượt ngưỡng, để loại trừ nguy cơ nhận nhầm giữa hai nhân viên có nét mặt gần giống nhau (anh chị em ruột, người có cấu trúc xương mặt tương đồng), hệ thống sắp xếp danh sách ứng viên theo thứ tự điểm số giảm dần và tính độ chênh lệch giữa ứng viên cao nhất (Best Match: S₁) và ứng viên đứng thứ nhì (Second Match: S₂):")
    add_formula_box("ΔS = S₁ - S₂", "5.4")
    add_p("Nếu S₁ nằm trong vùng mập mờ (gần ngưỡng τ) nhưng độ chênh lệch ΔS lại nhỏ hơn ngưỡng an toàn (ví dụ: ΔS < 0.05), hệ thống chủ động từ chối xác nhận để đảm bảo nguyên tắc: 'Thà từ chối để nhân viên quét lại, tuyệt đối không được ghi nhận nhầm công của người này sang người khác'.")
    add_bullet("So khớp đa góc mẫu (Multi-template Matching): Trong quá trình đăng ký hồ sơ nhân viên ban đầu, hệ thống lưu trữ đồng thời 3 góc nhìn khuôn mặt: chính diện (front), nghiêng nhẹ góc trái (left) và nghiêng nhẹ góc phải (right). Khi chấm công, thuật toán so sánh với cả 3 mẫu và lấy giá trị tương đồng cao nhất, giúp nhân viên không cần phải đứng cứng nhắc ở một góc cố định trước camera.")

    # 5.3
    add_h1("5.3. Giải pháp nhận diện khuôn mặt sử dụng Buffalo_L")

    add_h2("5.3.1. Kiến trúc Buffalo_L")
    add_p("Trong dự án mã nguồn mở InsightFace, Buffalo_L (Large) là gói giải pháp chuẩn công nghiệp hoàn chỉnh nhất, tích hợp các mô hình học sâu quy mô lớn nhằm tối ưu hóa độ chính xác nhận dạng:")
    add_bullet("Thành phần phát hiện khuôn mặt RetinaFace-ResNet50 (det_10g.onnx): Sử dụng kiến trúc mạng kim tự tháp đặc trưng (Feature Pyramid Network - FPN) kết hợp mô-đun bối cảnh SSH (Single Stage Headless). Mô hình thực hiện cùng lúc 3 nhiệm vụ: phân loại vùng ảnh có chứa khuôn mặt hay không, hồi quy tọa độ hộp bao (Bounding Box), và định vị chính xác 5 điểm mốc hình thái (Landmarks). Mô hình hoạt động đặc biệt bền bỉ trước các biến đổi về ánh sáng ngược, che khuất một phần (đeo kính, khẩu trang nhẹ) hoặc biểu cảm khuôn mặt thay đổi.")
    add_bullet("Thành phần trích xuất đặc trưng ArcFace-ResNet50 (w600k_r50.onnx): Là mạng nơ-ron tích chập sâu 50 tầng với các khối phần dư (Residual Blocks), được huấn luyện trên tập dữ liệu khuôn mặt khổng lồ Glint360k (chứa hơn 360.000 danh tính và hàng triệu hình ảnh). Mô hình ánh xạ khuôn mặt thành vector 512 chiều có năng lực phân biệt siêu việt.")
    add_p("Tối ưu hóa tốc độ thực thi với ONNX Runtime: Thay vì chạy trực tiếp trên các framework nặng nề như PyTorch hay TensorFlow, các mô hình trong giải pháp được chuyển đổi sang định dạng chuẩn trung gian ONNX (Open Neural Network Exchange) và thực thi thông qua ONNX Runtime Engine. Nhờ cơ chế tối ưu hóa đồ thị tính toán (Graph Optimization), lượng tử hóa trọng số và tận dụng tập lệnh tăng tốc phần cứng AVX-512 trên CPU, thời gian suy luận (Inference Latency) cho một khuôn mặt giảm xuống chỉ còn khoảng 50 - 80ms trên CPU phổ thông và dưới 15ms khi có card đồ họa GPU.")

    add_h2("5.3.2. Face Embedding và phương pháp so khớp")
    add_p("Nền tảng sức mạnh của Buffalo_L bắt nguồn từ phương pháp học biểu diễn đặc trưng góc ArcFace (Additive Angular Margin Loss). Thay vì tối ưu hóa khoảng cách Euclid trong không gian phẳng, ArcFace chuẩn hóa các vector đặc trưng và vector trọng số lớp lên bề mặt hình cầu siêu phẳng bán kính s = 64:")
    add_formula_box("L_ArcFace = - (1/N) ∑_{i=1}^N log [ e^(s · cos(θ_{y_i} + m)) / ( e^(s · cos(θ_{y_i} + m)) + ∑_{j ≠ y_i} e^(s · cos θ_j) ) ]", "5.5")
    add_p("Bằng cách cộng một lượng phạt biên góc m = 0.5 radian trực tiếp vào góc quay θ_{y_i} của lớp nhãn đúng, hàm mất mát buộc mô hình phải 'ép' các vector biểu diễn của cùng một nhân viên túm tụm lại trong một cung góc cực kỳ nhỏ (Intra-class compactness), đồng thời đẩy tâm góc của các nhân viên khác nhau ra thật xa nhau (Inter-class discrepancy).")
    add_p("Chiến lược so khớp và quản trị bộ nhớ đệm (In-memory Caching): Khi hệ thống mở rộng lên hàng nghìn nhân viên, việc liên tục đọc cơ sở dữ liệu MySQL để lấy chuỗi JSON vector rồi giải mã sẽ gây nghẽn I/O. Hệ thống triển khai giải pháp lưu trữ bộ nhớ đệm (In-memory Array / Memory Cache): toàn bộ vector nhúng 512 chiều chuẩn hóa của các nhân viên đang hoạt động được tải sẵn lên RAM máy chủ. Quá trình so khớp 1:N được thực hiện thông qua phép nhân ma trận véc-tơ hóa (Vectorized Dot Product) bằng thư viện NumPy, cho phép quét qua hàng nghìn hồ sơ chỉ trong vòng dưới 2 mili-giây.")

    # 5.4
    add_h1("5.4. Giải pháp chống giả mạo khuôn mặt")
    add_p("An toàn sinh trắc học là điều kiện tiên quyết để một hệ thống chấm công tự động có thể vận hành ổn định mà không bị lợi dụng. Các hình thức tấn công giả mạo trình diện (Presentation Attacks - PA) thường gặp bao gồm:")
    add_bullet("Tấn công bằng ảnh in 2D (Print Attack): Kẻ gian in ảnh chân dung của đồng nghiệp lên giấy A4 hoặc ảnh thẻ đưa trước camera chấm công.")
    add_bullet("Tấn công phát lại qua màn hình (Replay Attack): Kẻ gian sử dụng smartphone hoặc máy tính bảng phát lại video quay lén khuôn mặt của người khác.")
    add_p("Đề tài triển khai Giải pháp chống giả mạo thụ động (Passive Face Anti-Spoofing) dựa trên mô hình học sâu MiniFASNet kết hợp cùng cơ chế thẩm định phiên tương tác:")

    add_h2("5.4.1. Mô hình học sâu kiểm tra tính sống MiniFASNet")
    add_p("Mô hình MiniFASNet (thuộc dự án Silent-Face-Anti-Spoofing) được xây dựng dựa trên kiến trúc tích chập vi phân tâm (Central Difference Convolution - CDC). Khác với các lớp tích chập thông thường chỉ quan sát cường độ pixel bề mặt, CDC tập trung khai thác sự chênh lệch gradient cường độ sáng giữa pixel trung tâm và các pixel láng giềng xung quanh:")
    add_formula_box("y(p₀) = ∑_{p_n ∈ R} w(p_n) · (x(p₀ + p_n) - x(p₀))", "5.6")
    add_p("Cơ chế này mang lại khả năng phân tích cực kỳ nhạy bén đối với các chi tiết quang học vi mô:")
    add_bullet("Phân tích kết cấu phản xạ da người: Làn da người sống có tính chất tán xạ ánh sáng dưới bề mặt (Subsurface Scattering) mềm mại tự nhiên, khác biệt hoàn toàn với độ bóng loáng phản quang của kính màn hình điện thoại hoặc bề mặt nhám mờ của chất liệu giấy in.")
    add_bullet("Phát hiện dải vân giao thoa Moire: Khi camera quay lại một màn hình LCD/OLED, hiện tượng giao thoa giữa mạng lưới điểm ảnh (pixel grid) của màn hình và cảm biến camera sẽ tạo ra các dải vân sọc tần số cao (Moire Patterns) mà mắt thường khó nhận biết nhưng mạng CDC dễ dàng phát hiện.")
    add_bullet("Ngưỡng tin cậy tính sống: Mô hình xuất ra điểm số tính sống Liveness Score ∈ [0.0, 1.0]. Hệ thống cấu hình ngưỡng an toàn SPOOF_THRESHOLD = 0.60. Chỉ những lượt quét có điểm số vượt ngưỡng mới được xem là người thật và được chuyển tiếp sang bước trích xuất ArcFace.")

    add_h2("5.4.2. Cơ chế thẩm định phiên và lưu vết bằng chứng (Audit Trail)")
    add_p("Bên cạnh mô hình AI, hệ thống áp dụng cơ chế bảo mật kết hợp ở tầng ứng dụng:")
    add_bullet("Token phiên ngẫu nhiên (Liveness Session Token): Mỗi phiên quét mặt trên giao diện Kiosk được gắn một mã token ngẫu nhiên sinh ra từ máy chủ với thời gian sống ngắn (Time-to-Live từ 30 - 60 giây). Token này ký mã hóa thời điểm yêu cầu, ngăn chặn hành vi bắt gói tin (Replay API Request) để gửi lại ảnh cũ.")
    add_bullet("Lưu vết ảnh bằng chứng (Proof Photo Logging): Mọi lượt quét mặt thành công hay thất bại đều tự động lưu bức ảnh chụp thực tế vào thư mục uploads/attendance_faces/ với tên tệp định danh gắn liền mã nhân viên và mốc thời gian Unix (ví dụ: 12_1729153800_a1b2c3.jpg). Bộ phận nhân sự (HR) có thể trực tiếp mở lại ảnh bằng chứng trên giao diện web để đối soát minh bạch khi phát sinh bất kỳ tranh chấp nào.")

    # 5.5
    add_h1("5.5. Giải pháp tự động hóa tổng hợp dữ liệu công và báo cáo")
    add_p("Mục tiêu tối hậu của hệ thống chấm công là chuyển đổi các sự kiện nhận diện khuôn mặt tức thời thành các chỉ số công chuẩn xác, phục vụ trực tiếp cho công tác điều hành và tính lương của doanh nghiệp.")

    add_h2("5.5.1. Thu thập và phân loại bản ghi quẹt công")
    add_p("Tại trạm Kiosk chấm công Tablet, nhân viên có thể thực hiện quét mặt nhiều lần trong ngày. Hệ thống áp dụng quy trình xử lý 2 tầng dữ liệu:")
    add_bullet("Bảng ghi nhận thô (TABLET_SCANS): Mọi lượt quét mặt hợp lệ đều được ghi nhận ngay lập tức vào bảng TABLET_SCANS (gồm mã nhân viên maND, thời gian quét thoiGianQuet, và tên tệp ảnh anhQuet). Điều này đảm bảo toàn vẹn dữ liệu gốc, không bao giờ bị mất mát dấu vết.")
    add_bullet("Thuật toán tự động ghép cặp giờ vào/ra (IN/OUT Pairing Logic): Hệ thống tự động truy vấn số lần quét trong ngày của nhân viên:")
    add_bullet("Lần quét đầu tiên trong ngày: Được xác định là sự kiện Check-in (Giờ vào). Hệ thống đối chiếu thời điểm này với giờ bắt đầu ca làm việc được phân công trong ngày (gioBatDau). Nếu thời gian vào vượt quá dung sai cho phép (ví dụ: trễ trên 15 phút), hệ thống tự động gắn nhãn vi phạm 'Đi muộn x phút'; nếu trong dung sai, ghi nhận 'Đúng giờ'.", 1)
    add_bullet("Các lần quét tiếp theo trong ngày: Hệ thống tự động cập nhật sự kiện Check-out (Giờ ra) thành mốc thời gian của lần quét mới nhất. Thời điểm này được đối chiếu với giờ kết thúc ca (gioKetThuc) để tự động tính toán trạng thái 'Về sớm y phút' hoặc 'Đúng giờ'.", 1)
    add_bullet("Xử lý ca đặc biệt: Đối với các ngày nhân viên được xếp ca nghỉ (ca OFF) hoặc ca cấu hình cotinhcong = 'no', hệ thống hiển thị thông báo rõ ràng và không tính công sai lệch.")

    add_h2("5.5.2. Quy trình xử lý đơn từ và điều chỉnh công trực tuyến")
    add_p("Nhằm giải quyết các tình huống phát sinh trong thực tế, hệ thống tích hợp luồng xử lý đơn từ điện tử:")
    add_bullet("Đơn xin nghỉ phép (Nghỉ ốm, thai sản, phép năm, việc riêng): Nhân viên gửi đơn trực tuyến qua ứng dụng. Khi Quản lý hoặc Nhân sự duyệt đơn, hệ thống tự động cập nhật trạng thái ngày làm việc tương ứng thành ngày nghỉ hợp lệ có hưởng lương hoặc không lương theo quy chế.")
    add_bullet("Đơn giải trình quên chấm công và xin đi muộn/về sớm: Nhân viên có thể tạo yêu cầu điều chỉnh kèm lý do giải trình. Sau khi được cấp quản lý phê chuẩn, bản ghi chấm công thực tế sẽ được tự động đồng bộ lại.")

    add_h2("5.5.3. Thuật toán tự động tổng hợp bảng công tháng và phê duyệt")
    add_p("Vào cuối mỗi kỳ tính công, hệ thống kích hoạt hàm tổng hợp chuyên sâu getMonthlyAttendanceDetailNew() để xử lý dữ liệu của toàn bộ nhân viên trong tháng:")
    add_formula_box("Tổng ngày công = Ngày công làm việc thực tế + Ngày nghỉ phép hưởng lương", "5.7")
    add_formula_box("Tổng giờ công = ∑ (Giờ ra thực tế - Giờ vào thực tế) - Giờ nghỉ giữa ca", "5.8")
    add_p("Thuật toán tự động phân tách và thống kê chi tiết các tham số quan trọng:")
    add_bullet("Số ngày công chuẩn (work_days) và tổng số giờ làm việc thực tế (work_hours).")
    add_bullet("Số giờ làm thêm ngoài giờ (overtime_hours - OT) dựa trên đăng ký ca và giờ quẹt ra thực tế.")
    add_bullet("Tổng số lần và tổng số phút đi muộn (phutDiTre), số lần và số phút về sớm (phutVeSom).")
    add_bullet("Quy trình phê duyệt bảng công 2 cấp (Timesheet Approval Workflow): Bộ phận Nhân sự (HR) kiểm tra bảng tổng hợp công trên giao diện tính công, thực hiện các điều chỉnh ngoại lệ nếu có, sau đó bấm 'Gửi duyệt' sang cấp Quản lý (Manager). Quản lý tiến hành xem xét, đối soát và bấm 'Phê duyệt' để chính thức khóa sổ dữ liệu kỳ công, sẵn sàng chuyển giao cho bộ phận kế toán tính lương.")

    # 5.6
    add_h1("5.6. Kiến trúc tổng thể của giải pháp")
    add_p("Để đáp ứng đồng thời yêu cầu về tính sẵn sàng cao, sự linh hoạt trong quản trị nghiệp vụ và năng lực tính toán nặng của các mô hình học sâu, đề tài triển khai Kiến trúc lai phân tách độc lập (Hybrid Architecture: Core Web App MVC + Face AI Microservice):")
    add_code_block("  ┌────────────────────────────────────────────────────────────────────────┐\n  │                TẦNG THIẾT BỊ ĐẦU CUỐI & GIAO DIỆN (CLIENT)             │\n  │  • Trạm chấm công Kiosk Tablet (WebRTC Stream qua Camera gắn tường)    │\n  │  • Ứng dụng Web Quản trị (Admin, HR, Quản lý, Kỹ thuật, Nhân viên)     │\n  └───────────────────────────────────┬────────────────────────────────────┘\n                                      │ HTTP Request / Web Traffic\n                                      ▼\n  ┌────────────────────────────────────────────────────────────────────────┐\n  │          HỆ THỐNG QUẢN TRỊ TRUNG TÂM (CORE WEB APPLICATION - MVC)      │\n  │  • Ngôn ngữ & Framework: PHP (Kiến trúc MVC chuẩn hóa)                 │\n  │  • Bộ điều phối Controller: FaceController, ChamCongController,         │\n  │    HRController, ManagerController, LoginController...                 │\n  │  • Xử lý nghiệp vụ Model: ChamCongModel, FaceModel...                  │\n  │  • Phân quyền truy cập đa cấp: Role-Based Access Control (RBAC)        │\n  │  • Hệ quản trị CSDL quan hệ: MySQL Database (InnoDB, UTF-8 Unicode)    │\n  └───────────────────────────────────┬────────────────────────────────────┘\n                                      │ RESTful API (HTTP POST / JSON)\n                                      │ Header: X-API-Key Authentication\n                                      ▼\n  ┌────────────────────────────────────────────────────────────────────────┐\n  │         VI DỊCH VỤ NHẬN DIỆN KHUÔN MẶT (FACE AI MICROSERVICE)          │\n  │  • Nền tảng thực thi: Python 3.10+ & FastAPI Asynchronous Framework    │\n  │  • Động cơ suy luận học sâu: ONNX Runtime High-Performance Engine      │\n  │  • Chuỗi xử lý AI độc lập:                                             │\n  │    1. POST /detect     : Phát hiện & Định vị Bounding Box, Landmarks   │\n  │    2. POST /anti-spoof : MiniFASNet CDC kiểm tra tính sống Liveness     │\n  │    3. POST /embedding  : ArcFace ResNet50 trích xuất Vector 512 chiều  │\n  │    4. POST /verify     : Chuỗi xử lý nhận diện & so khớp tổng hợp      │\n  └────────────────────────────────────────────────────────────────────────┘\n                                      │\n                                      ▼\n  ┌────────────────────────────────────────────────────────────────────────┐\n  │                     TẦNG LƯU TRỮ DỮ LIỆU (DATA & STORAGE)              │\n  │  • CSDL MySQL: Lưu hồ sơ nhân viên, ca làm, bảng công, vector nhúng    │\n  │  • File Storage: Thư mục uploads/attendance_faces lưu ảnh bằng chứng   │\n  └────────────────────────────────────────────────────────────────────────┘")

    add_h2("5.6.1. Phân tích chức năng các thành phần kiến trúc")
    add_bullet("Tầng Client / Kiosk: Chạy trên trình duyệt của máy tính bảng hoặc máy tính có camera. Tận dụng năng lực xử lý cục bộ của trình duyệt để stream video mượt mà, phát hiện khuôn mặt sơ bộ và gửi ảnh chụp về máy chủ chỉ khi có sự hiện diện của nhân viên.")
    add_bullet("Tầng Core Web Application (PHP MVC): Đóng vai trò là hạt nhân điều hành hệ thống. Đảm nhận toàn bộ các tương tác nghiệp vụ người dùng, xử lý phân ca, quản lý đơn từ, đối soát giờ vào ra, phân quyền đa cấp bậc và tổng hợp báo cáo. Việc sử dụng PHP MVC kết hợp MySQL giúp các tác vụ nghiệp vụ quản trị đạt tốc độ phản hồi cực nhanh, ổn định và tiêu thụ rất ít tài nguyên máy chủ.")
    add_bullet("Tầng Face AI Microservice (Python FastAPI): Chạy trên một tiến trình độc lập (cổng 8000), chuyên trách toàn bộ các phép tính toán ma trận nặng của mạng nơ-ron học sâu. Nhờ cơ chế bất đồng bộ (Asynchronous I/O) của FastAPI và động cơ ONNX Runtime, dịch vụ có khả năng xử lý song song hàng chục yêu cầu nhận diện cùng lúc mà không làm treo hay ảnh hưởng đến tốc độ của trang web quản trị.")
    add_bullet("Cơ chế tích hợp và chuyển đổi linh hoạt: Core Web App giao tiếp với AI Microservice thông qua lớp hỗ trợ FaceServiceClient bằng giao thức RESTful API chuẩn mực. Hệ thống cũng hỗ trợ cơ chế cấu hình biến môi trường (ENV) cho phép chuyển đổi nhanh chóng giữa dịch vụ Python nội bộ và các API nhận diện đám mây bên ngoài khi cần mở rộng quy mô.")

    add_h2("5.6.2. Các ưu điểm nổi bật của kiến trúc giải pháp")
    add_bullet("Tính sẵn sàng cao và cô lập lỗi (Fault Isolation): Nếu dịch vụ AI gặp sự cố hoặc cần tạm dừng để nâng cấp mô hình, toàn bộ các chức năng quản trị nhân sự, duyệt đơn, xem bảng công của Web App vẫn hoạt động bình thường.")
    add_bullet("Khả năng mở rộng độc lập (Independent Scalability): Vào khung giờ cao điểm đầu ca làm việc khi hàng trăm nhân viên chấm công cùng lúc, doanh nghiệp có thể dễ dàng nhân bản (Scale-out) riêng dịch vụ Python Face Service hoặc cấp thêm GPU tính toán mà không cần nâng cấp toàn bộ hệ thống máy chủ web.")
    add_bullet("Bảo mật và an toàn dữ liệu: Giao tiếp giữa hai dịch vụ được bảo vệ bằng mã khóa bí mật X-API-Key. Dữ liệu vector khuôn mặt được lưu trữ dưới dạng mảng số thực mã hóa, không lưu trữ ảnh gốc nhạy cảm của nhân viên trong cơ sở dữ liệu, bảo đảm tính riêng tư sinh trắc học.")

    # Save Word file
    os.makedirs(r"c:\Users\DELL5410\quanLyChamCong\document", exist_ok=True)
    target_path_doc = r"c:\Users\DELL5410\quanLyChamCong\document\CHUONG_5_GIAI_PHAP_CONG_NGHE.docx"
    target_path_root = r"c:\Users\DELL5410\quanLyChamCong\CHUONG_5_GIAI_PHAP_CONG_NGHE.docx"
    target_path_desktop = r"C:\Users\DELL5410\Desktop\CHUONG_5_GIAI_PHAP_CONG_NGHE.docx"

    doc.save(target_path_doc)
    doc.save(target_path_root)
    doc.save(target_path_desktop)
    print(f"Chapter 5 Word file successfully saved to:\n1. {target_path_doc}\n2. {target_path_root}\n3. {target_path_desktop}")

if __name__ == "__main__":
    build_chapter_5()

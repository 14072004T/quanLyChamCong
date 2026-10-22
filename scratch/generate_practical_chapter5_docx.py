import os
import docx
from docx.shared import Inches, Pt, RGBColor, Cm
from docx.enum.text import WD_ALIGN_PARAGRAPH
from docx.enum.table import WD_TABLE_ALIGNMENT, WD_ALIGN_VERTICAL
from docx.oxml import parse_xml
from docx.oxml.ns import nsdecls

def set_cell_background(cell, hex_color):
    tcPr = cell._tc.get_or_add_tcPr()
    shd = parse_xml(f'<w:shd {nsdecls("w")} w:fill="{hex_color}"/>')
    tcPr.append(shd)

def set_cell_margins(cell, top=120, bottom=120, left=150, right=150):
    tcPr = cell._tc.get_or_add_tcPr()
    tcMar = parse_xml(f'<w:tcMar {nsdecls("w")}>'
                      f'<w:top w:w="{top}" w:type="dxa"/>'
                      f'<w:bottom w:w="{bottom}" w:type="dxa"/>'
                      f'<w:left w:w="{left}" w:type="dxa"/>'
                      f'<w:right w:w="{right}" w:type="dxa"/>'
                      f'</w:tcMar>')
    tcPr.append(tcMar)

def set_table_borders(table, color="D3D3D3"):
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

def build_practical_chapter_5():
    doc = docx.Document()

    # Standard A4 Margins for Vietnamese Academic Thesis (Left 3cm, Right 2cm, Top 2cm, Bottom 2cm)
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
    # NỘI DUNG CHƯƠNG 5 - TẬP TRUNG 100% VÀO THIẾT KẾ GIẢI PHÁP THỰC TẾ
    # =========================================================================

    add_chapter_title("CHƯƠNG 5. GIẢI PHÁP CÔNG NGHỆ")

    # 5.1
    add_h1("5.1. Tổng quan giải pháp nhận diện khuôn mặt trong hệ thống chấm công")
    add_p("Khác với Chương 2 tập trung vào nghiên cứu cơ sở lý thuyết và khảo sát các mô hình học thuật trên thế giới, Chương 5 trình bày chi tiết giải pháp thiết kế công nghệ và hiện thực hóa thực tế của đề tài nhằm giải quyết bài toán quản lý chấm công tự động trong doanh nghiệp.")
    add_p("Trong thực tế triển khai, một giải pháp chấm công khuôn mặt ứng dụng công nghiệp không chỉ dừng lại ở việc áp dụng một mô hình AI đơn lẻ, mà đòi hỏi phải giải quyết đồng bộ chuỗi bài toán kỹ thuật từ thiết bị đầu cuối đến máy chủ:")
    add_bullet("Tính sẵn sàng trên thiết bị phổ thông: Hệ thống phải hoạt động ổn định trên các thiết bị phần cứng có sẵn (máy tính bảng gắn tường, máy tính cá nhân hoặc webcam phổ thông) mà không đòi hỏi các hệ thống cảm biến quang học đắt tiền hay card đồ họa máy trạm chuyên dụng.")
    add_bullet("Tốc độ phản hồi thời gian thực: Đảm bảo thời gian từ khi nhân viên xuất hiện trước camera đến khi màn hình hiển thị kết quả xác nhận danh tính và giờ vào/ra phải dưới 0.5 giây, đáp ứng yêu cầu lưu thông liên tục tại cửa văn phòng vào khung giờ cao điểm đầu ca.")
    add_bullet("Hàng rào an toàn chống gian lận kép: Phối hợp kiểm tra tính sống (Passive Anti-Spoofing) với cơ chế thẩm định phiên và lưu vết ảnh bằng chứng thực tế nhằm loại bỏ hoàn toàn các rủi ro gian lận chấm công hộ bằng ảnh chụp hoặc video điện thoại.")
    add_bullet("Liên kết trực tiếp với dữ liệu nghiệp vụ: Dữ liệu nhận diện khuôn mặt tức thời được chuyển giao tự động vào hệ thống quản lý ca kíp, tự động ghép cặp giờ vào/ra (Check-in/Check-out), tính toán các trường hợp vi phạm đi muộn, về sớm và tổng hợp bảng công tháng minh bạch.")

    # 5.2
    add_h1("5.2. Quy trình xử lý nhận diện khuôn mặt")
    add_p("Để tối ưu hóa hiệu năng và bảo đảm tính toàn vẹn của dữ liệu, quy trình nhận diện khuôn mặt và chấm công tự động được thiết kế thành một pipeline 5 giai đoạn tuần tự từ Client đến Server:")
    add_code_block("  [TRẠM CHẤM CÔNG KIOSK / TABLET]\n         │\n         ▼ (1. Luồng WebRTC Stream capture khung hình Canvas)\n  [TIỀN XỬ LÝ PHÍA CLIENT]\n  • Chuyển đổi sang ảnh JPEG Base64 (Quality 0.85)\n  • Đóng gói kèm Token phiên xác thực (Liveness Token)\n         │\n         ▼ (2. Gửi HTTP POST JSON tới Controller trung tâm)\n  [HỆ THỐNG QUẢN TRỊ TRUNG TÂM (CORE BACKEND - PHP)]\n         │\n         ▼ (3. FaceServiceClient gọi RESTful API qua cổng 8000)\n  [DỊCH VỤ NHẬN DIỆN AI (PYTHON FASTAPI SERVICE)]\n  • Giai đoạn 1: MTCNN / RetinaFace phát hiện BBox & 5 Facial Landmarks\n  • Giai đoạn 2: Biến đổi Affine xoay thẳng & chuẩn hóa kích thước 112x112\n  • Giai đoạn 3: MiniFASNet CDC dự đoán Liveness Score (Ngưỡng ≥ 0.60)\n  • Giai đoạn 4: ArcFace ResNet50 trích xuất Vector nhúng đặc trưng 512 chiều\n         │\n         ▼ (4. Trả về kết quả xác thực & Vector nhúng)\n  [XÁC ĐỊNH NHÂN VIÊN & XỬ LÝ CHẤM CÔNG (CORE BACKEND)]\n  • Giai đoạn 5: So khớp Cosine Similarity với Vector khuôn mặt đã đăng ký\n  • Kiểm tra ngưỡng an toàn (Cosine ≥ 0.40) & Phân tích biên độ chênh lệch\n  • Đối chiếu lịch phân ca hôm nay ──► Ghi nhận Check-in hoặc Check-out\n  • Lưu ảnh bằng chứng quét mặt vào hệ thống tệp lưu trữ")

    add_h2("5.2.1. Thu nhận và xử lý hình ảnh")
    add_p("Quy trình bắt đầu từ tầng thu nhận dữ liệu hình ảnh trực tiếp tại trạm Kiosk chấm công:")
    add_bullet("Thu nhận luồng video qua WebRTC: Ứng dụng giao diện Kiosk (tablet_cham_cong.php) sử dụng API HTML5 MediaDevices.getUserMedia() để kích hoạt camera thiết bị với độ phân giải chuẩn HD (1280 × 720). Luồng video được hiển thị trực tiếp lên màn hình kèm khung định hướng hình oval để nhân viên căn chỉnh vị trí đứng phù hợp.")
    add_bullet("Cơ chế kích hoạt chụp thông minh (Auto-Trigger): Hệ thống sử dụng mô-đun phát hiện chuyển động hoặc bộ dò nhẹ phía client để nhận diện khi có người đứng ổn định trước khung hình trong khoảng 0.3 giây, sau đó vẽ khung hình video hiện tại lên một thẻ Canvas ẩn và kết xuất chuỗi ảnh Base64 định dạng JPEG chất lượng cao (quality = 0.85). Việc này loại bỏ yêu cầu nhân viên phải bấm nút bằng tay, đảm bảo tính năng chấm công không tiếp xúc hoàn toàn.")
    add_bullet("Phát hiện khuôn mặt tại dịch vụ AI: Ảnh Base64 được giải mã thành mảng NumPy đa chiều trên bộ nhớ RAM của dịch vụ Python FastAPI. Bộ phát hiện nơ-ron tích chập (MTCNN Detector) thực hiện quét nhanh để xác định tọa độ hộp bao (Bounding Box: x₁, y₁, x₂, y₂) và 5 điểm mốc giải phẫu chính (tâm mắt trái, tâm mắt phải, đỉnh mũi, khóe môi trái, khóe môi phải). Nếu không phát hiện thấy khuôn mặt hoặc khuôn mặt bị che khuất quá 50%, hệ thống lập tức phản hồi thông báo yêu cầu nhân viên đứng thẳng hướng vào camera.")
    add_bullet("Căn chỉnh Affine 2D chuẩn hóa: Dựa trên tọa độ của 2 mắt, thuật toán tính toán góc nghiêng θ giữa đường nối 2 mắt so với phương ngang và ma trận biến đổi afin 2D. Ảnh được xoay trục, co giãn tỉ lệ và tịnh tiến tâm để đưa khuôn mặt về tư thế nhìn thẳng chuẩn mực với kích thước cố định 112 × 112 pixels. Đồng thời, ảnh được chuẩn hóa các giá trị điểm ảnh về phân phối chuẩn phục vụ cho các mô hình học sâu phía sau.")

    add_h2("5.2.2. Trích xuất đặc trưng và so khớp")
    add_p("Khác với việc huấn luyện mô hình nghiên cứu trong phòng thí nghiệm, giải pháp của đề tài sử dụng mô hình học sâu ResNet50 đã được nén tối ưu hóa dưới định dạng ONNX Runtime để phục vụ suy luận (Inference):")
    add_bullet("Trích xuất Vector nhúng 512 chiều: Bức ảnh khuôn mặt đã căn chỉnh 112 × 112 × 3 được đưa qua mô hình trích xuất đặc trưng ArcFace. Tại tầng fully-connected cuối cùng, mạng xuất ra một vector số thực gồm đúng 512 chiều biểu diễn trọn vẹn đặc trưng nhận dạng sinh trắc học cá nhân:")
    add_formula_box("v = [e₁, e₂, e₃, ..., e₅₁₂] ∈ ℝ⁵¹²", "5.1")
    add_bullet("Chuẩn hóa vector theo chuẩn L₂: Vector v được chuẩn hóa theo độ dài Euclid:")
    add_formula_box("v_norm = v / ||v||₂ = v / √(∑_{i=1}^{512} e_i²)", "5.2")
    add_bullet("Phương pháp so khớp Độ tương đồng Cosine: Khi các vector đều đã có độ dài đơn vị (||v||₂ = 1), độ tương đồng Cosine giữa vector truy vấn mới chụp (v_query) và vector nhân viên lưu trong hệ thống (v_stored) chính là tích vô hướng trực tiếp giữa hai vector:")
    add_formula_box("Cosine Similarity(v_query, v_stored) = v_query · v_stored = ∑_{i=1}^{512} (v_query,i × v_stored,i)", "5.3")
    add_p("Giá trị Cosine dao động từ -1.0 đến 1.0. Điểm số càng cao thể hiện mức độ trùng khớp sinh trắc học càng lớn.")

    add_h2("5.2.3. Xác định nhân viên")
    add_p("Để đưa ra quyết định công nhận danh tính nhân viên chính xác tuyệt đối trong môi trường sản xuất, hệ thống áp dụng cơ chế thiết lập ngưỡng quyết định linh hoạt kết hợp giải thuật kiểm tra biên độ an toàn (Ambiguous Margin Check):")
    add_bullet("Ngưỡng chấp nhận cơ sở: Hệ thống cấu hình ngưỡng tương đồng mặc định ARCFACE_COSINE_THRESHOLD = 0.40. Bất kỳ hồ sơ nào có điểm tương đồng dưới 0.40 đều bị loại bỏ.")
    add_bullet("Giải thuật kiểm tra biên độ an toàn (Margin Analysis): Trong bài toán nhận diện 1:N với toàn bộ nhân viên công ty, để giải quyết triệt để trường hợp hai nhân viên có khuôn mặt tương đối giống nhau (như anh em họ, hoặc góc chụp bị nhiễu ánh sáng), hệ thống sắp xếp danh sách các ứng viên có điểm vượt ngưỡng theo thứ tự giảm dần và so sánh giữa ứng viên cao nhất (Best Match: S₁) và ứng viên đứng thứ nhì (Second Match: S₂):")
    add_bullet("Nếu S₁ ≥ 0.55: Điểm số nhận diện vượt trội, hệ thống công nhận ngay danh tính mà không cần xét thêm điều kiện biên.", 1)
    add_bullet("Nếu 0.40 ≤ S₁ < 0.55 (vùng điểm số mập mờ): Hệ thống bắt buộc kiểm tra độ chênh lệch biên độ: ΔS = S₁ - S₂. Nếu ΔS < 0.05 (khoảng cách giữa người thứ nhất và người thứ hai quá sít sao), hệ thống chủ động từ chối xác nhận và ghi nhận log REJECT để yêu cầu nhân viên quét lại dưới góc nhìn rõ nét hơn. Điều này bảo đảm triệt để nguyên tắc: Thà từ chối để nhân viên quét lại, tuyệt đối không được ghi nhận nhầm công của người này sang người khác.", 1)
    add_bullet("Cơ chế nhận diện đa góc mẫu (Multi-template Matching): Trong cơ sở dữ liệu FACE_PROFILE, mỗi nhân viên được lưu trữ 3 vector đại diện cho 3 góc nhìn: chính diện (embedding_front), nghiêng trái (embedding_left), và nghiêng phải (embedding_right). Khi chấm công, hệ thống tính tương đồng với cả 3 vector và lấy giá trị lớn nhất, giúp nhân viên không cần phải căn chỉnh góc mặt quá cứng nhắc.")

    # 5.3
    add_h1("5.3. Giải pháp nhận diện khuôn mặt sử dụng Buffalo_L")

    add_h2("5.3.1. Kiến trúc triển khai của gói Buffalo_L trong hệ thống")
    add_p("Trong giải pháp công nghệ của đề tài, gói mô hình Buffalo_L của hệ sinh thái InsightFace được tích hợp vào dịch vụ Python FastAPI theo mô hình suy luận hiệu năng cao:")
    add_bullet("Mô hình phát hiện khuôn mặt RetinaFace-ResNet50 (det_10g.onnx): Đảm nhiệm vai trò phát hiện vị trí khuôn mặt và trích xuất 5 điểm mốc chuẩn xác. Mô hình có kích thước xấp xỉ 16MB, hoạt động hiệu quả ngay cả khi nhân viên đeo kính hoặc ánh sáng môi trường bị ngược sáng nhẹ.")
    add_bullet("Mô hình trích xuất đặc trưng ArcFace-ResNet50 (w600k_r50.onnx): Được nén và tối ưu hóa dưới định dạng ONNX Runtime. Mạng nhận đầu vào là ảnh khuôn mặt đã căn chỉnh kích thước 112 × 112 và xuất ra vector đặc trưng 512 chiều trong thời gian khoảng 40 - 60ms trên CPU thông thường.")
    add_bullet("Cơ chế nạp mô hình lười (Lazy-load Singleton): Thay vì khởi tạo mô hình mỗi khi có yêu cầu HTTP gửi đến, hệ thống áp dụng mẫu thiết kế Singleton trong tệp arcface.py. Mô hình ONNX InferenceSession chỉ được nạp lên RAM một lần duy nhất khi dịch vụ khởi động (Lifespan Startup), giúp tiết kiệm tài nguyên bộ nhớ và loại bỏ hoàn toàn độ trễ khởi tạo.")

    add_h2("5.3.2. Cơ chế lưu trữ và tăng tốc so khớp (In-Memory Caching)")
    add_p("Khi số lượng nhân viên trong công ty tăng lên hàng trăm hoặc hàng nghìn người, việc truy vấn cơ sở dữ liệu MySQL để đọc chuỗi JSON vector của từng nhân viên cho mỗi lượt quét mặt sẽ gây tắc nghẽn I/O và làm chậm thời gian phản hồi. Đề tài triển khai giải pháp tối ưu hóa bộ nhớ đệm:")
    add_bullet("Lưu trữ cấu trúc vector tại máy chủ: Bảng FACE_PROFILE trong MySQL đóng vai trò lưu trữ bền vững (Persistent Storage). Khi khởi động hoặc khi có thay đổi hồ sơ nhân viên, danh sách các vector 512 chiều của toàn bộ nhân viên đang hoạt động được nạp trực tiếp vào mảng đa chiều trong bộ nhớ RAM.")
    add_bullet("Tăng tốc so khớp véc-tơ hóa bằng NumPy: Quá trình so sánh vector quét truy vấn v_query với toàn bộ cơ sở dữ liệu N nhân viên được thực hiện thông qua phép nhân ma trận véc-tơ hóa (Matrix-Vector Multiplication):")
    add_formula_box("S = M_enrolled · v_query", "5.4")
    add_p("Trong đó, M_enrolled là ma trận kích thước N × 512 chứa toàn bộ vector nhân viên đã chuẩn hóa. Phép nhân ma trận này tận dụng tập lệnh tính toán song song SIMD (AVX2/AVX-512) của CPU, cho phép quét qua 1.000 hồ sơ nhân viên trong thời gian chưa tới 1.5 mili-giây.")

    # 5.4
    add_h1("5.4. Giải pháp chống giả mạo khuôn mặt")
    add_p("Để đảm bảo tính trung thực và ngăn chặn triệt để hành vi chấm công hộ bằng hình ảnh in trên giấy (Print Attack) hoặc phát lại video trên màn hình smartphone (Replay Attack), hệ thống xây dựng giải pháp bảo mật đa tầng kết hợp giữa học sâu thụ động và kiểm soát ứng dụng:")

    add_h2("5.4.1. Bộ lọc kiểm tra tính sống thụ động MiniFASNet")
    add_p("Giải pháp tích hợp mô hình MiniFASNet (phiên bản MiniFASNetV1SE_80x80.onnx và MiniFASNetV2) chạy độc lập trước khi thực hiện bước nhận diện ArcFace:")
    add_bullet("Kỹ thuật tích chập vi phân tâm (Central Difference Convolution - CDC): Thay vì chỉ quét cường độ pixel như mạng CNN thông thường, MiniFASNet tính toán sự chênh lệch gradient giữa pixel trung tâm và láng giềng xung quanh. Nhờ đó, mô hình cực kỳ nhạy bén trong việc bóc tách sự khác biệt giữa kết cấu tán xạ dưới bề mặt da người thật với độ phẳng quang học của giấy in hoặc màn hình LCD.")
    add_bullet("Nhận diện dải vân sọc Moire: Khi kẻ gian đưa màn hình điện thoại trước camera chấm công, sự xung đột tần số quét giữa màn hình số và cảm biến camera sẽ tạo ra các dải vân Moire tần số cao. Mạng MiniFASNet phát hiện dấu hiệu này và chấm điểm tính sống rất thấp.")
    add_bullet("Quy tắc chặn ngưỡng nghiêm ngặt: Mô hình tính toán điểm tin cậy tính sống Liveness Score ∈ [0.0, 1.0]. Hệ thống thiết lập SPOOF_THRESHOLD = 0.60. Nếu điểm tính sống < 0.60, hệ thống lập tức từ chối lượt chấm công và phát cảnh báo 'Phát hiện hình ảnh giả mạo', đồng thời dừng toàn bộ các bước tính toán phía sau để tiết kiệm tài nguyên.")

    add_h2("5.4.2. Cơ chế thẩm định phiên và lưu vết ảnh bằng chứng (Audit Trail)")
    add_p("Nhằm ngăn chặn hành vi can thiệp vào gói tin API mạng (chèn ảnh tĩnh cũ gửi trực tiếp vào endpoint backend mà không qua camera), hệ thống triển khai cơ chế kiểm soát bổ trợ:")
    add_bullet("Mã xác thực phiên ngẫu nhiên (Liveness Session Token): Mỗi khi màn hình Kiosk chuẩn bị chụp ảnh, client phải yêu cầu máy chủ cấp một mã Token phiên ngẫu nhiên có chữ ký số bí mật và thời hạn sống ngắn (TTL = 30 giây). Khi gửi ảnh lên, nếu Token đã hết hạn hoặc không khớp chữ ký, yêu cầu chấm công sẽ bị hủy bỏ.")
    add_bullet("Lưu trữ ảnh bằng chứng đối soát: Toàn bộ ảnh chụp thực tế tại thời điểm quét mặt đều được lưu tự động vào thư mục uploads/attendance_faces/ với tên tệp chuẩn hóa: [maND]_[timestamp]_[random].jpg. Cán bộ nhân sự (HR) có thể trực tiếp nhấp xem lại ảnh chụp của bất kỳ lượt chấm công nào ngay trên bảng lịch sử để hậu kiểm minh bạch.")

    # 5.5
    add_h1("5.5. Giải pháp tự động hóa tổng hợp dữ liệu công và báo cáo")
    add_p("Điểm nhấn thực tiễn của đề tài là việc khép kín quy trình từ lúc nhận diện khuôn mặt thành công đến khi tự động tổng hợp thành bảng công kỳ lương hoàn chỉnh, loại bỏ hoàn toàn các thao tác đối soát thủ công phức tạp.")

    add_h2("5.5.1. Thu thập và tự động ghép cặp giờ vào/ra (IN/OUT Pairing Logic)")
    add_p("Tại trạm Kiosk Tablet, nhân viên có thể quét mặt nhiều lần trong một ngày. Hệ thống triển khai thuật toán tự động phân loại thông minh:")
    add_bullet("Lưu vết toàn bộ lượt quét (Bảng TABLET_SCANS): Mọi lần quét mặt thành công đều được ghi lại vào bảng nhật ký thô TABLET_SCANS kèm mã nhân viên, thời gian quét chính xác tới từng giây và tên tệp ảnh bằng chứng.")
    add_bullet("Thuật toán tự động ghép cặp Check-in và Check-out:")
    add_bullet("Lần quét đầu tiên trong ngày: Hệ thống tự động ghi nhận là sự kiện VÀO CA (Check-in IN) vào bảng LICHSUCHAMCONG. Hệ thống đối chiếu thời điểm này với giờ bắt đầu ca làm việc (gioBatDau) được cấu hình trong ngày. Nếu thời gian vào trễ hơn giờ bắt đầu ca cộng thêm dung sai cho phép (15 phút), hệ thống tự động ghi chú vi phạm 'Đi muộn x phút'; nếu trong giới hạn, ghi chú 'Đúng giờ'.", 1)
    add_bullet("Các lần quét tiếp theo trong ngày: Hệ thống tự động cập nhật sự kiện RA CA (Check-out OUT) thành thời điểm của lần quét mới nhất. Mốc thời gian này được so sánh với giờ kết thúc ca (gioKetThuc) để tự động đánh dấu trạng thái 'Về sớm y phút' hoặc 'Đúng giờ'.", 1)
    add_bullet("Xử lý ngày nghỉ và ca đặc biệt: Đối với các ngày nhân viên được phân ca nghỉ (ca OFF) hoặc ca được cấu hình không tính công (cotinhcong = 'no'), hệ thống sẽ hiển thị thông báo rõ ràng cho nhân viên và ngăn chặn việc tính toán sai lệch vào bảng công tổng hợp.")

    add_h2("5.5.2. Quy trình xử lý đơn từ và điều chỉnh công trực tuyến")
    add_p("Hệ thống số hóa 100% các quy trình đơn từ hành chính phát sinh:")
    add_bullet("Đơn xin nghỉ phép trực tuyến: Nhân viên tạo đơn xin nghỉ (phép năm, nghỉ ốm, việc riêng) trên ứng dụng. Khi cấp Quản lý hoặc Nhân sự phê duyệt, hệ thống tự động cập nhật trạng thái ngày nghỉ hợp lệ vào bảng công.")
    add_bullet("Đơn giải trình quên chấm công và điều chỉnh giờ: Cho phép nhân viên gửi giải trình kèm lý do cụ thể khi có sự cố quên quét mặt. Sau khi được duyệt, hệ thống sẽ tự động cập nhật giờ vào/ra bổ sung.")

    add_h2("5.5.3. Thuật toán tự động tổng hợp bảng công tháng và phê duyệt 2 cấp")
    add_p("Vào cuối kỳ công, hàm xử lý chuyên sâu getMonthlyAttendanceDetailNew() trong ChamCongModel tự động quét toàn bộ dữ liệu quẹt công thực tế, lịch phân ca và các đơn từ đã duyệt trong tháng để kết xuất bảng tổng hợp công chi tiết cho từng nhân viên:")
    add_formula_box("Số ngày công chuẩn = Số ngày làm việc thực tế + Số ngày nghỉ phép hưởng lương", "5.5")
    add_formula_box("Tổng giờ làm thực tế = ∑ [ Giờ ra thực tế - Giờ vào thực tế - Thời gian nghỉ giữa ca ]", "5.6")
    add_p("Thuật toán tự động tính toán chính xác:")
    add_bullet("Số ngày công chuẩn (work_days) và tổng số giờ làm việc thực tế (work_hours).")
    add_bullet("Số giờ làm thêm ngoài giờ (overtime_hours - OT) được tính khi giờ ra thực tế vượt quá giờ kết thúc ca theo phê duyệt làm thêm giờ.")
    add_bullet("Tổng số lần và tổng số phút đi muộn (phutDiTre), số lần và số phút về sớm (phutVeSom).")
    add_bullet("Quy trình phê duyệt bảng công 2 cấp (Timesheet Approval Workflow):")
    add_bullet("Cấp 1 - Nhân sự (HR): Rà soát bảng tổng hợp công trên giao diện tính công (tinhcong.php), thực hiện điều chỉnh ngoại lệ nếu cần, sau đó bấm 'Gửi duyệt Quản lý'.", 1)
    add_bullet("Cấp 2 - Quản lý (Manager): Đăng nhập vào Manager Panel để kiểm tra số liệu toàn bộ phòng ban và bấm 'Phê duyệt chính thức'. Khi đã phê duyệt, bảng công tháng được khóa dữ liệu chống chỉnh sửa tùy tiện và sẵn sàng xuất báo cáo Excel phục vụ chi trả lương.", 1)

    # 5.6
    add_h1("5.6. Kiến trúc tổng thể của giải pháp")
    add_p("Để kết hợp hài hòa giữa tính ổn định, chặt chẽ trong quản lý nghiệp vụ cơ sở dữ liệu quan hệ với năng lực tính toán ma trận nặng của các mạng nơ-ron thị giác máy tính, đề tài lựa chọn Mô hình kiến trúc lai phân tách độc lập (Hybrid Architecture: Core Web App MVC + AI Microservice):")
    add_code_block("  ┌────────────────────────────────────────────────────────────────────────┐\n  │               TẦNG THIẾT BỊ ĐẦU CUỐI & GIAO DIỆN (CLIENT)              │\n  │  • Trạm Kiosk Tablet: Camera WebRTC stream tự động chụp ảnh khuôn mặt  │\n  │  • Ứng dụng Web Quản trị: Trình duyệt Desktop/Mobile cho HR & Quản lý  │\n  └───────────────────────────────────┬────────────────────────────────────┘\n                                      │ HTTP Request (JSON / Form Data)\n                                      ▼\n  ┌────────────────────────────────────────────────────────────────────────┐\n  │         HỆ THỐNG QUẢN TRỊ NGHIỆP VỤ TRUNG TÂM (CORE WEB APP - MVC)     │\n  │  • Nền tảng: PHP 8.x chuẩn mô hình MVC                                 │\n  │  • Controller: FaceController, ChamCongController, HRController...     │\n  │  • Model: ChamCongModel, FaceModel xử lý logic nghiệp vụ               │\n  │  • Middleware: Phân quyền vai trò RBAC (Admin, HR, Quản lý, Nhân viên)│\n  │  • Client Helper: FaceServiceClient thực hiện kết nối dịch vụ ngoài    │\n  └─────────────────┬──────────────────────────────────┬───────────────────┘\n                    │                                  │\n     SQL Queries    │                                  │ RESTful API (HTTP JSON)\n     (InnoDB)       │                                  │ Header: X-API-Key\n                    ▼                                  ▼\n  ┌───────────────────────────────┐  ┌─────────────────────────────────────┐\n  │  CƠ SỞ DỮ LIỆU (DATABASE)     │  │  DỊCH VỤ NHẬN DIỆN KHUÔN MẶT        │\n  │  • Hệ quản trị: MySQL RDBMS   │  │  (FACE AI MICROSERVICE)             │\n  │  • Bảng dữ liệu chính:        │  │  • Nền tảng: Python FastAPI         │\n  │    - NGUOIDUNG, TAIKHOAN      │  │  • Động cơ: ONNX Runtime            │\n  │    - CALAMVIEC, CANHANVIEN    │  │  • Endpoints chính:                 │\n  │    - TABLET_SCANS (quét thô)  │  │    • POST /detect                   │\n  │    - LICHSUCHAMCONG (IN/OUT)  │  │    • POST /anti-spoof               │\n  │    - FACE_PROFILE (512-d emb) │  │    • POST /embedding                │\n  │    - DUYETCONGTHANG (kỳ lương)│  │    • POST /verify                   │\n  └───────────────────────────────┘  └──────────────────┬──────────────────┘\n                                                        │\n                                                        ▼\n                                     ┌─────────────────────────────────────┐\n                                     │  HỆ THỐNG TỆP (FILE STORAGE)        │\n                                     │  • uploads/attendance_faces/        │\n                                     │    Lưu ảnh bằng chứng quét thực tế  │\n                                     └─────────────────────────────────────┘")

    add_h2("5.6.1. Chi tiết các thành phần kiến trúc đã hiện thực hóa")
    add_bullet("Core Web Application (PHP MVC): Đóng vai trò là trung tâm điều phối toàn bộ hệ thống. Sử dụng ngôn ngữ PHP theo kiến trúc MVC chuẩn mực giúp các thao tác nghiệp vụ (đăng nhập, phân ca làm việc, xử lý đơn từ, tính toán ngày công và hiển thị giao diện) đạt tốc độ phản hồi cực nhanh, độ trễ thấp và hoàn toàn ổn định trên các máy chủ web thông thường (Apache/Nginx).")
    add_bullet("Face AI Microservice (Python FastAPI): Chạy trên một tiến trình độc lập (cổng mạng 8000), chuyên trách toàn bộ các tác vụ xử lý ảnh và suy luận mạng học sâu (MTCNN, MiniFASNet, ArcFace ResNet50). Nhờ cơ chế bất đồng bộ (Asynchronous) của FastAPI kết hợp khả năng tối ưu hóa đồ thị của ONNX Runtime, dịch vụ có thể xử lý đồng thời nhiều luồng quét mặt gửi về từ các trạm Kiosk khác nhau mà không bao giờ làm treo máy chủ web quản trị.")
    add_bullet("Cơ chế tích hợp và chuyển đổi linh hoạt: Core Web App gọi sang AI Microservice thông qua lớp hỗ trợ FaceServiceClient.php. Cơ chế bảo mật được thực hiện qua mã khóa bí mật X-API-Key trên tiêu đề HTTP. Hệ thống cũng cho phép cấu hình chuyển hướng linh hoạt sang các endpoint dịch vụ nhận diện bên ngoài thông qua biến môi trường ENV khi doanh nghiệp có nhu cầu mở rộng quy mô.")

    add_h2("5.6.2. Ưu điểm nổi bật của kiến trúc giải pháp")
    add_bullet("Cách ly tải tính toán và cô lập lỗi (Fault Isolation): Các tác vụ học sâu tiêu tốn nhiều năng lượng CPU/GPU được cô lập hoàn toàn tại dịch vụ Python. Nếu dịch vụ AI tạm thời quá tải hoặc cần khởi động lại để cập nhật mô hình, toàn bộ các chức năng quản trị nhân sự, xem bảng công, duyệt đơn của hệ thống web chính vẫn hoạt động bình thường.")
    add_bullet("Khả năng mở rộng độc lập (Independent Scalability): Vào các khung giờ cao điểm đầu giờ sáng khi lưu lượng chấm công tăng đột biến, quản trị viên có thể dễ dàng tăng thêm tài nguyên tính toán hoặc nhân bản (Scale-out) riêng dịch vụ Python Face Service mà không cần nâng cấp toàn bộ hệ thống máy chủ.")
    add_bullet("Bảo mật dữ liệu sinh trắc học cá nhân: Hệ thống không lưu trữ trực tiếp ảnh chụp khuôn mặt gốc của nhân viên vào cơ sở dữ liệu quan hệ, mà chỉ lưu trữ chuỗi vector nhúng 512 chiều đã được mã hóa toán học. Kẻ gian dù có can thiệp vào cơ sở dữ liệu cũng không thể tái tạo lại được khuôn mặt gốc của nhân viên, bảo đảm tính riêng tư sinh trắc học theo các tiêu chuẩn an toàn thông tin hiện đại.")

    # Save to all locations
    os.makedirs(r"c:\Users\DELL5410\quanLyChamCong\document", exist_ok=True)
    target_path_doc = r"c:\Users\DELL5410\quanLyChamCong\document\CHUONG_5_GIAI_PHAP_CONG_NGHE.docx"
    target_path_root = r"c:\Users\DELL5410\quanLyChamCong\CHUONG_5_GIAI_PHAP_CONG_NGHE.docx"
    target_path_desktop = r"C:\Users\DELL5410\Desktop\CHUONG_5_GIAI_PHAP_CONG_NGHE.docx"

    doc.save(target_path_doc)
    doc.save(target_path_root)
    doc.save(target_path_desktop)
    print(f"Refined Practical Chapter 5 successfully saved to:\n1. {target_path_doc}\n2. {target_path_root}\n3. {target_path_desktop}")

if __name__ == "__main__":
    build_practical_chapter_5()

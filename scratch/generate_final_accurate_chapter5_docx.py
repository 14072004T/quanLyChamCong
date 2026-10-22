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

def build_perfect_chapter_5():
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
    # NỘI DUNG CHƯƠNG 5: GIẢI PHÁP CÔNG NGHỆ (CHÍNH XÁC VÀ HOÀN CHỈNH 100%)
    # =========================================================================

    add_chapter_title("CHƯƠNG 5. GIẢI PHÁP CÔNG NGHỆ")

    # 5.1
    add_h1("5.1. Tổng quan giải pháp nhận diện khuôn mặt trong hệ thống chấm công")
    add_p("Trong quá trình xây dựng hệ thống quản lý chấm công, việc ứng dụng thị giác máy tính nhằm định danh nhân viên không chỉ đòi hỏi sự chính xác về mặt thuật toán mà còn phải đáp ứng tính ổn định, thời gian đáp ứng nhanh và khả năng tương thích cao với hạ tầng sẵn có của doanh nghiệp. Đề tài tập trung xây dựng giải pháp công nghệ kết hợp giữa ứng dụng web quản lý nghiệp vụ và dịch vụ trích xuất đặc trưng khuôn mặt hiện đại:")
    add_bullet("Khai thác thiết bị sẵn có: Tận dụng các thiết bị có sẵn camera như máy tính bảng gắn tường (trạm Kiosk chấm công) hoặc máy tính có webcam thông qua giao diện web, giúp doanh nghiệp không cần đầu tư máy chấm công phần cứng chuyên dụng tốn kém.")
    add_bullet("Tích hợp Dịch vụ AI trích xuất embedding dựa trên ArcFace (ArcFace Embedding Service): Sử dụng dịch vụ API chuyên dụng để chuyển đổi hình ảnh khuôn mặt thành vector nhúng đặc trưng 512 chiều, kết hợp thuật toán so khớp độ tương đồng Cosine ngay trong hệ thống máy chủ quản trị PHP.")
    add_bullet("Kiểm soát chống giả mạo đa lớp: Kết hợp thuật toán kiểm tra tính sống động trên giao diện client (phát hiện chớp mắt tự nhiên, cử động), cơ chế xác thực token bảo mật có chữ ký HMAC tại máy chủ, và lưu vết ảnh chụp thực tế phục vụ đối soát.")
    add_bullet("Tự động hóa hoàn toàn chu trình nghiệp vụ: Từ sự kiện nhận diện thành công, hệ thống tự động đối chiếu lịch ca trong ngày để ghi nhận giờ vào (Check-in), giờ ra (Check-out), tính toán đi muộn, về sớm và tổng hợp bảng công tháng.")

    # 5.2
    add_h1("5.2. Quy trình xử lý nhận diện khuôn mặt")
    add_p("Quy trình nhận diện khuôn mặt và chấm công trong hệ thống đang vận hành thực tế được thực hiện tuần tự qua các bước sau:")
    add_code_block("  [THIẾT BỊ ĐẦU CUỐI (TABLET KIOSK / WEBCAM)]\n         │\n         ▼ (1. Camera WebRTC quay video, kiểm tra tính sống Liveness)\n  [TIỀN XỬ LÝ & BẢO MẬT CLIENT]\n  • Kiểm tra chớp mắt tự nhiên, chuyển động khuôn mặt\n  • Chụp ảnh khuôn mặt, mã hóa Base64 JPEG\n  • Gửi kèm Token phiên có chữ ký HMAC\n         │\n         ▼ (2. Gửi HTTP POST request tới FaceController)\n  [HỆ THỐNG QUẢN TRỊ TRUNG TÂM (CORE WEB BACKEND - PHP)]\n  • Kiểm tra tính hợp lệ của Token qua LivenessHelper\n  • Gọi dịch vụ FaceServiceClient gửi ảnh tới ArcFace Embedding Service\n         │\n         ▼ (3. Giao tiếp HTTP POST qua cURL)\n  [DỊCH VỤ AI TRÍCH XUẤT EMBEDDING (ARCFACE EMBEDDING SERVICE)]\n  • Tiếp nhận ảnh, phát hiện khuôn mặt và căn chỉnh chuẩn hóa\n  • Trích xuất vector nhúng đặc trưng 512 chiều\n  • Trả về mảng JSON vector embedding 512 phần tử\n         │\n         ▼ (4. Nhận kết quả vector nhúng)\n  [SO KHỚP & XÁC ĐỊNH NHÂN VIÊN (PHP BACKEND)]\n  • Tính toán Cosine Similarity giữa vector mới và cơ sở dữ liệu face_profile\n  • So sánh ngưỡng quy định (ARCFACE_COSINE_THRESHOLD = 0.40)\n  • Kiểm tra biên độ an toàn giữa ứng viên hạng 1 và hạng 2 (Margin Check)\n         │\n         ▼ (5. Ghi nhận chấm công & Lưu trữ)\n  [CẬP NHẬT DỮ LIỆU & LƯU BẰNG CHỨNG]\n  • Lưu ảnh chụp thực tế vào thư mục uploads/attendance_faces/\n  • Ghi nhận bản ghi vào bảng TABLET_SCANS và LICHSUCHAMCONG\n  • Đối chiếu ca làm việc để ghi nhận Check-in/Check-out")

    add_h2("5.2.1. Thu nhận và xử lý hình ảnh")
    add_bullet("Thu nhận luồng hình ảnh: Trên giao diện trạm chấm công (tablet_cham_cong.php), ứng dụng sử dụng API HTML5 MediaDevices.getUserMedia() để bắt luồng video thời gian thực từ camera thiết bị với độ phân giải HD (1280 × 720). Khung hình được đưa vào một khung định hướng để hỗ trợ người dùng đứng đúng cự ly.")
    add_bullet("Chụp khung hình và đóng gói: Khi điều kiện ổn định khuôn mặt và tính sống được thỏa mãn, hệ thống trích xuất khung hình video hiện tại sang đối tượng Canvas ẩn, nén thành chuỗi dữ liệu ảnh Base64 định dạng JPEG chất lượng cao.")
    add_bullet("Gửi yêu cầu tới ArcFace Embedding Service: Từ phía máy chủ backend (FaceServiceClient.php), chuỗi ảnh Base64 được giải mã thành tệp ảnh nhị phân tạm thời và gửi qua giao thức HTTP POST (sử dụng cURL multipart/form-data) đến dịch vụ API trích xuất đặc trưng (được cấu hình qua biến môi trường ARCFACE_EMBED_URL, mặc định trỏ tới endpoint dịch vụ ArcFace). Dịch vụ này thực hiện các bước phát hiện khuôn mặt, căn chỉnh xoay thẳng trục và trích xuất vector đặc trưng.")

    add_h2("5.2.2. Trích xuất đặc trưng và so khớp")
    add_bullet("Tiếp nhận Vector nhúng 512 chiều: Sau khi xử lý ảnh, dịch vụ ArcFace trả về kết quả mảng số thực gồm đúng 512 phần tử đại diện cho đặc trưng sinh trắc học của khuôn mặt:")
    add_formula_box("v = [e₁, e₂, e₃, ..., e₅₁₂] ∈ ℝ⁵¹²", "5.1")
    add_bullet("Chuẩn hóa vector: Để đảm bảo tính đồng nhất khi tính toán, hàm normalizeEmbedding() trong FaceController thực hiện chuẩn hóa vector theo chuẩn L₂ đưa độ dài về đơn vị (||v||₂ = 1):")
    add_formula_box("v_norm = v / ||v||₂ = v / √(∑_{i=1}^{512} e_i²)", "5.2")
    add_bullet("Thuật toán so khớp Cosine Similarity trong PHP: Hệ thống duyệt qua danh sách hồ sơ nhân viên đã đăng ký từ bảng face_profile. Mỗi hồ sơ chứa chuỗi JSON của vector ArcFace (cột embedding_arcface). Độ tương đồng giữa vector khuôn mặt quét mới (v_query) và vector đã lưu (v_stored) được tính trực tiếp bằng hàm cosineSimilarity():")
    add_formula_box("Cosine Similarity(v_query, v_stored) = ∑_{i=1}^{512} (v_query,i × v_stored,i)", "5.3")
    add_p("Giá trị Cosine Similarity nằm trong khoảng [-1.0, 1.0]. Điểm số càng cao thể hiện mức độ tương đồng giữa hai khuôn mặt càng lớn.")

    add_h2("5.2.3. Xác định nhân viên")
    add_bullet("Áp dụng ngưỡng tương đồng (Threshold): Trong hệ thống của đề tài, ngưỡng so khớp được cấu hình thông qua biến môi trường ARCFACE_COSINE_THRESHOLD, với giá trị mặc định là 0.40. Giá trị này là tham số cấu hình của hệ thống và cần được hiệu chỉnh theo dữ liệu thực tế cũng như yêu cầu cụ thể về tỷ lệ nhận đúng (TAR) và tỷ lệ nhận nhầm (FAR). Những ứng viên có điểm tương đồng nhỏ hơn ngưỡng quy định sẽ bị loại bỏ.")
    add_bullet("Giải thuật kiểm tra biên độ an toàn (Margin Check): Để loại trừ rủi ro nhận nhầm giữa hai nhân viên có nét mặt gần giống nhau trong điều kiện ánh sáng phức tạp, hệ thống sắp xếp các ứng viên đạt ngưỡng theo thứ tự điểm giảm dần và kiểm tra khoảng cách an toàn:")
    add_bullet("Hệ thống xác định ứng viên có điểm cao nhất S₁ (Best Match) và ứng viên đứng thứ nhì S₂ (Second Match).", 1)
    add_bullet("Nếu S₁ nằm trong khoảng điểm số cận biên (từ 0.40 đến dưới 0.55), hệ thống bắt buộc kiểm tra độ chênh lệch: ΔS = S₁ - S₂. Nếu khoảng chênh lệch này nhỏ hơn 0.05 (tức S₁ và S₂ quá sát nhau), hệ thống chủ động từ chối xác nhận để tránh nhận diện nhầm người.", 1)
    add_bullet("Cơ chế nhận diện đa góc mặt: Trong bảng face_profile, hệ thống lưu trữ đồng thời các vector khuôn mặt ở các góc: chính diện (embedding_front), nghiêng trái (embedding_left), và nghiêng phải (embedding_right). Khi nhận diện, hệ thống tính toán và lấy giá trị tương đồng cao nhất trong các góc, giúp nhân viên không phải đứng quá gò bó trước camera.")

    # 5.3
    add_h1("5.3. Giải pháp nhận diện khuôn mặt sử dụng ArcFace và nghiên cứu phân hệ AI nội bộ")
    add_p("Để tránh nhầm lẫn giữa mô hình đang được sử dụng trong luồng vận hành và mô hình được nghiên cứu trong đề tài, phần này phân biệt rõ hai hướng triển khai: ArcFace Embedding Service là giải pháp đang được sử dụng trực tiếp trong hệ thống hiện tại; phân hệ AI nội bộ (dựa trên ArcFace/Buffalo_L kết hợp MTCNN và MiniFASNet) là bộ mô hình được nghiên cứu và tích hợp sẵn nhằm phục vụ khả năng triển khai độc lập, tự chủ hạ tầng trong tương lai.")

    add_h2("5.3.1. Dịch vụ AI trích xuất embedding dựa trên ArcFace")
    add_p("Trong luồng vận hành thực tế của hệ thống, việc trích xuất đặc trưng sinh trắc học được ủy quyền cho dịch vụ ArcFace Embedding Service chuyên dụng, kết nối thông qua lớp FaceServiceClient.php:")
    add_bullet("Đóng gói độc lập qua API: Ứng dụng quản trị PHP chỉ cần gửi ảnh khuôn mặt qua giao thức HTTP và tiếp nhận vector 512 chiều. Cách tiếp cận này giúp máy chủ web quản trị hoàn toàn nhẹ tải, không bị chiếm dụng tài nguyên CPU cho các phép toán ma trận phức tạp của mô hình học sâu.")
    add_bullet("Đặc trưng vector 512 chiều: Trong cấu hình ArcFace được sử dụng trong đề tài, vector đặc trưng có kích thước 512 chiều, tạo sự cân bằng giữa chi phí lưu trữ trong cơ sở dữ liệu và khả năng phân biệt đặc trưng khuôn mặt giữa các cá nhân.")

    add_h2("5.3.2. Nghiên cứu phân hệ AI nội bộ và khảo sát Buffalo_L")
    add_p("Bên cạnh việc tích hợp dịch vụ ArcFace Embedding Service bên ngoài, đề tài đã nghiên cứu và phát triển một phân hệ dịch vụ AI Python độc lập (đặt trong thư mục face_service/):")
    add_bullet("Khảo sát bộ mô hình Buffalo_L: Gói mô hình Buffalo_L của dự án mã nguồn mở InsightFace trên lý thuyết cung cấp bộ giải pháp toàn diện gồm mô hình phát hiện RetinaFace-ResNet50 và mô hình trích xuất ArcFace-ResNet50 (tệp w600k_r50.onnx). Phân hệ này được thiết kế để chạy trên nền tảng Python FastAPI và động cơ suy luận tối ưu hóa ONNX Runtime.")
    add_bullet("Thành phần thực tế trong phân hệ Python face_service: Để tối ưu hóa tài nguyên phần cứng nhẹ hơn và dễ dàng triển khai thực tế trên CPU phổ thông, phân hệ nội bộ face_service sử dụng MTCNN làm bộ phát hiện và căn chỉnh khuôn mặt, kết hợp cùng mô hình trích xuất đặc trưng ArcFace ResNet50 (tệp w600k_r50.onnx) và mô hình chống giả mạo MiniFASNet. Phân hệ này đóng vai trò là giải pháp công nghệ dự phòng độc lập khi doanh nghiệp cần tự chủ hoàn toàn hệ thống AI nội bộ.")
    add_bullet("Làm rõ về mô hình TinyFace: Mô hình TinyFace (đề xuất bởi Hu và Ramanan) là giải pháp phát hiện khuôn mặt kích thước nhỏ được nhóm khảo sát thử nghiệm trong giai đoạn đầu của đề tài. Tuy nhiên, do đặc thù trạm chấm công là nhân viên đứng ở cự ly gần (0.5m - 1.2m), TinyFace tiêu tốn tài nguyên không cần thiết nên không được đưa vào luồng triển khai chính thức.")

    # 5.4
    add_h1("5.4. Giải pháp chống giả mạo khuôn mặt")
    add_p("Để ngăn chặn các hành vi gian lận như sử dụng ảnh in trên giấy (Print Attack) hoặc phát lại video khuôn mặt trên điện thoại (Replay Attack), hệ thống triển khai giải pháp chống giả mạo đa tầng kết hợp giữa client và server:")

    add_h2("5.4.1. Cơ chế kiểm tra trạng thái sống trên giao diện Client và xác thực Token tại Server")
    add_p("Đây là cơ chế chống giả mạo chính thức đang vận hành trực tiếp trong luồng chấm công của hệ thống:")
    add_bullet("Kiểm tra trạng thái sống phía Client (LivenessDetector JavaScript): Giao diện chấm công theo dõi khuôn mặt người dùng qua các khung hình video liên tiếp. Thuật toán phân tích cử động khuôn mặt, sự thay đổi vi kết cấu da và đặc biệt là phát hiện hành vi chớp mắt tự nhiên. Chỉ khi người dùng chớp mắt thật sự và khuôn mặt cử động tự nhiên, client mới đóng gói dữ liệu kiểm tra.")
    add_bullet("Mã xác thực phiên có chữ ký HMAC (Liveness Token): Để ngăn chặn việc bắt gói tin mạng và gửi lại dữ liệu cũ (Replay API Request), máy chủ PHP sinh ra một khóa bí mật phiên (session secret). Client tính toán chữ ký số HMAC-SHA256 trên toàn bộ thông số kiểm tra trạng thái sống và gửi kèm ảnh lên backend.")
    add_bullet("Xác thực nghiêm ngặt tại Server qua LivenessHelper: Hàm validateLivenessToken() trong LivenessHelper.php tiến hành kiểm tra một chuỗi các ràng buộc an ninh:")
    add_bullet("Kiểm tra chữ ký số HMAC: Khớp chính xác với secret của phiên để chống làm giả dữ liệu.", 1)
    add_bullet("Kiểm tra thời hạn token: Dữ liệu gửi lên phải có thời gian tạo không quá 120 giây (maxAgeMs = 120 * 1000).", 1)
    add_bullet("Kiểm tra số lượng khung hình: Yêu cầu tối thiểu 10 khung hình được quét.", 1)
    add_bullet("Ràng buộc chớp mắt bắt buộc: Bắt buộc phải phát hiện có chớp mắt (blinkDetected = true và blinkCount >= 1). Cơ chế này giúp giảm đáng kể khả năng ảnh tĩnh in trên giấy vượt qua bước xác thực trạng thái sống.", 1)
    add_bullet("Ngưỡng điểm số tổng hợp: Điểm đánh giá tổng hợp combinedScore phải đạt từ 0.55 (tức 55%) trở lên.", 1)

    add_h2("5.4.2. Nghiên cứu mô hình học sâu MiniFASNet trong phân hệ Python")
    add_p("Trong phân hệ AI nội bộ (face_service/), đề tài đã tích hợp và thử nghiệm mô hình học sâu chuyên biệt MiniFASNet (sử dụng kiến trúc tích chập vi phân tâm Central Difference Convolution - CDC). Mô hình này phân tích sự sai khác về phản xạ ánh sáng bề mặt và dải sọc Moire để phân loại ảnh thật hay ảnh phát lại qua màn hình, đóng vai trò là giải pháp mở rộng khi hệ thống triển khai kiểm tra tính sống thụ động hoàn toàn tại tầng AI backend.")

    add_h2("5.4.3. Lưu trữ ảnh chụp thực tế phục vụ hậu kiểm (Audit Log)")
    add_p("Mọi lượt quét mặt chấm công thành công tại trạm Tablet đều được tự động lưu lại ảnh chụp thực tế vào thư mục uploads/attendance_faces/ với tên tệp chuẩn hóa: [maND]_[timestamp]_[random].jpg. Đường dẫn tệp này được lưu đồng thời trong cơ sở dữ liệu. Bộ phận nhân sự (HR) có thể trực tiếp mở lại ảnh chụp của bất kỳ lần chấm công nào để đối soát minh bạch, tạo ra một kênh hậu kiểm chống gian lận hữu hiệu.")

    # 5.5
    add_h1("5.5. Giải pháp tự động hóa tổng hợp dữ liệu công và báo cáo")
    add_p("Điểm nhấn thực tiễn của đề tài là việc khép kín quy trình từ dữ liệu nhận diện khuôn mặt thành các chỉ số công chuẩn xác phục vụ công tác quản lý nhân sự thông qua các thuật toán xử lý dữ liệu chuyên biệt:")

    add_h2("5.5.1. Thu thập và tự động ghép cặp giờ vào/ra (IN/OUT Pairing)")
    add_bullet("Lưu nhật ký quét thô (TABLET_SCANS): Mọi lần quét mặt hợp lệ tại Kiosk đều được ghi vào bảng nhật ký thô TABLET_SCANS (gồm mã nhân viên maND, thời điểm thoiGianQuet và tên tệp ảnh anhQuet) để bảo toàn dữ liệu gốc.")
    add_bullet("Thuật toán ghép cặp Check-in và Check-out:")
    add_bullet("Lần quét đầu tiên trong ngày: Được xác định là sự kiện Check-in (Vào ca), chuyển dịch dữ liệu ghi vào bảng LICHSUCHAMCONG. Hệ thống đối chiếu thời điểm này với giờ bắt đầu ca (gioBatDau) được cấu hình trong bảng CALAMVIEC. Nếu thời gian vào trễ hơn giờ bắt đầu ca quá 15 phút (dung sai cho phép), hệ thống tự động gắn nhãn vi phạm 'Đi muộn x phút'; nếu trong giới hạn, ghi chú 'Đúng giờ'.", 1)
    add_bullet("Các lần quét tiếp theo trong ngày: Thuật toán tự động cập nhật sự kiện Check-out (Ra ca) thành thời điểm của lần quét mới nhất. Mốc thời gian này được so sánh với giờ kết thúc ca (gioKetThuc) để tự động đánh dấu trạng thái 'Về sớm y phút' hoặc 'Đúng giờ'.", 1)
    add_bullet("Xử lý ca OFF: Đối với những ngày nhân viên được phân ca nghỉ (ca OFF trong bảng CANHANVIEN) hoặc ca cấu hình cotinhcong = 'no', hệ thống hiển thị thông báo rõ ràng và không tính công sai lệch.", 1)

    add_h2("5.5.2. Quy trình xử lý đơn từ trực tuyến")
    add_bullet("Đơn xin nghỉ phép: Nhân viên gửi đơn trực tuyến (nghỉ phép năm, nghỉ ốm, việc riêng). Khi Quản lý hoặc Nhân sự phê duyệt trên hệ thống, trạng thái ngày nghỉ hợp lệ được tự động liên kết vào bảng công.")
    add_bullet("Đơn giải trình quên chấm công: Cho phép nhân viên gửi giải trình kèm lý do cụ thể khi có sự cố quên quét mặt để được bổ sung giờ công hợp lệ sau khi được duyệt.")

    add_h2("5.5.3. Thuật toán tổng hợp bảng công tháng và phê duyệt 2 cấp")
    add_p("Cuối mỗi kỳ công, hàm xử lý chuyên sâu getMonthlyAttendanceDetailNew() trong ChamCongModel quét toàn bộ dữ liệu quẹt công thực tế từ LICHSUCHAMCONG, lịch phân ca CANHANVIEN và đơn từ đã duyệt để tổng hợp bảng công chi tiết:")
    add_bullet("Số ngày công chuẩn (work_days) và tổng số giờ làm việc thực tế (work_hours).")
    add_bullet("Số giờ làm thêm ngoài giờ (overtime_hours - OT) dựa trên đăng ký ca và giờ quẹt ra thực tế.")
    add_bullet("Tổng số lần và tổng số phút đi muộn (phutDiTre), số lần và số phút về sớm (phutVeSom).")
    add_bullet("Quy trình phê duyệt 2 cấp: Cán bộ Nhân sự (HR) rà soát số liệu trên giao diện tinhcong.php và bấm 'Gửi duyệt'. Quản lý (Manager) kiểm tra và bấm 'Phê duyệt chính thức' để khóa số liệu kỳ lương trong bảng DUYETCONGTHANG, xuất báo cáo phục vụ thanh toán lương.")

    # 5.6
    add_h1("5.6. Kiến trúc tổng thể của giải pháp")
    add_p("Kiến trúc tổng thể của hệ thống được thiết kế theo mô hình phân tầng rõ ràng, kết nối chặt chẽ giữa giao diện người dùng, máy chủ quản trị PHP MVC, cơ sở dữ liệu và dịch vụ nhận diện ArcFace:")
    add_code_block("  ┌────────────────────────────────────────────────────────────────────────┐\n  │               TẦNG GIAO DIỆN & THIẾT BỊ ĐẦU CUỐI (CLIENT)              │\n  │  • Trạm Kiosk Tablet: Camera WebRTC stream, kiểm tra chớp mắt Liveness │\n  │  • Ứng dụng Web Quản trị: Trình duyệt web cho HR, Quản lý, Nhân viên   │\n  └───────────────────────────────────┬────────────────────────────────────┘\n                                      │ HTTP Request (JSON / Form Data)\n                                      ▼\n  ┌────────────────────────────────────────────────────────────────────────┐\n  │         HỆ THỐNG QUẢN TRỊ NGHIỆP VỤ TRUNG TÂM (CORE WEB APP - PHP MVC) │\n  │  • Controllers: FaceController, ChamCongController, HRController...     │\n  │  • Helpers: LivenessHelper (xác thực token), FaceServiceClient         │\n  │  • Models: ChamCongModel, FaceModel kết nối CSDL                       │\n  │  • Phân quyền RBAC: Admin, Nhân sự (HR), Quản lý, Nhân viên            │\n  └──────────────────┬──────────────────────────────────┬──────────────────┘\n                     │                                  │\n      Truy vấn SQL   │                                  │ HTTP POST (cURL)\n      (MySQL RDBMS)  │                                  │ Multipart / JSON\n                     ▼                                  ▼\n  ┌───────────────────────────────┐  ┌─────────────────────────────────────┐\n  │     CƠ SỞ DỮ LIỆU MYSQL       │  │    ARCFACE EMBEDDING SERVICE        │\n  │  • NGUOIDUNG, TAIKHOAN        │  │  (Dịch vụ AI trích xuất embedding)  │\n  │  • CALAMVIEC, CANHANVIEN      │  │  • Tiếp nhận ảnh khuôn mặt          │\n  │  • TABLET_SCANS (quét thô)    │  │  • Trích xuất vector đặc trưng      │\n  │  • LICHSUCHAMCONG (IN/OUT)    │  │    ArcFace 512 chiều                │\n  │  • FACE_PROFILE (vector nhúng)│  │  • Trả về mảng JSON embedding       │\n  │  • DUYETCONGTHANG (kỳ lương)  │  └─────────────────────────────────────┘\n  └───────────────────────────────┘  \n                     │\n                     ▼\n  ┌─────────────────────────────────────┐  ┌─────────────────────────────────────┐\n  │      HỆ THỐNG TỆP (FILE STORAGE)    │  │ [PHÂN HỆ NGHIÊN CỨU DỰ PHÒNG]       │\n  │  • uploads/attendance_faces/        │  │ DỊCH VỤ AI PYTHON FASTAPI (CỔNG 8000│\n  │    Lưu ảnh chụp thực tế mỗi lần quét│  │ • MTCNN Face Detector               │\n  │  • uploads/liveness_logs/           │  │ • MiniFASNet Anti-Spoofing          │\n  │    Lưu nhật ký xác thực tính sống   │  │ • ArcFace ResNet50 (ONNX Runtime)   │\n  └─────────────────────────────────────┘  └─────────────────────────────────────┘")

    add_h2("5.6.1. Chi tiết cơ chế lưu trữ dữ liệu thực tế")
    add_bullet("Dữ liệu thông tin nhân viên: Được lưu trữ trong bảng NGUOIDUNG và NHANVIEN (bao gồm họ tên, mã nhân viên, phòng ban, chức vụ, trạng thái công tác).")
    add_bullet("Dữ liệu vector sinh trắc học khuôn mặt: Bảng face_profile lưu trữ trực tiếp các chuỗi JSON của vector số thực, bao gồm vector ArcFace 512 chiều (embedding_arcface) và các vector 128 chiều ở các góc mặt (embedding_front, embedding_left, embedding_right).")
    add_bullet("Dữ liệu ảnh khuôn mặt thực tế: Hình ảnh chụp minh chứng mỗi lượt quét mặt được lưu trữ trực tiếp dưới dạng tệp ảnh JPEG trong thư mục uploads/attendance_faces/ trên máy chủ, tên tệp được liên kết chặt chẽ vào bảng TABLET_SCANS và LICHSUCHAMCONG phục vụ đối soát.")

    add_h2("5.6.2. Ưu điểm của kiến trúc giải pháp")
    add_bullet("Độ tin cậy và phân định trách nhiệm rõ ràng: Ứng dụng PHP MVC tập trung quản trị nghiệp vụ CSDL quan hệ ổn định; tác vụ trích xuất đặc trưng AI nặng được ủy quyền cho dịch vụ ArcFace Embedding Service.")
    add_bullet("Khả năng mở rộng và thay thế linh hoạt: Nhờ lớp trừu tượng FaceServiceClient, hệ thống có thể dễ dàng chuyển đổi giữa việc gọi dịch vụ ArcFace Embedding Service bên ngoài sang phân hệ dịch vụ AI Python nội bộ (face_service/) khi cần triển khai hoàn toàn khép kín.")
    add_bullet("Kiểm soát minh bạch và an toàn: Cơ chế xác thực Token bảo vệ chống giả mạo kết hợp kênh hậu kiểm bằng ảnh chụp thực tế giúp hệ thống vận hành minh bạch, góp phần giảm nguy cơ gian lận và hỗ trợ hậu kiểm trong quá trình vận hành.")

    # 5.7 KẾT LUẬN CHƯƠNG 5
    add_h1("5.7. Kết luận chương")
    add_p("Chương 5 đã trình bày toàn diện giải pháp công nghệ được thiết kế và áp dụng trong hệ thống quản lý chấm công nhận diện khuôn mặt của đề tài. Giải pháp làm rõ quy trình xử lý khép kín từ thu nhận hình ảnh qua WebRTC, cơ chế kiểm tra trạng thái sống kết hợp token HMAC, trích xuất vector đặc trưng 512 chiều bằng dịch vụ ArcFace Embedding Service, thuật toán so khớp Cosine Similarity có kiểm tra biên độ an toàn, cho đến quy trình tự động hóa ghép cặp giờ vào/ra và tổng hợp bảng công tháng hai cấp.")
    add_p("Bên cạnh luồng vận hành chính thức, đề tài cũng đã nghiên cứu, xây dựng phân hệ AI nội bộ độc lập (face_service/) kết hợp bộ phát hiện MTCNN, mạng chống giả mạo MiniFASNet và mô hình ArcFace ResNet50 (ONNX Runtime) dựa trên nghiên cứu bộ mô hình Buffalo_L, tạo tiền đề vững chắc cho việc tự chủ hoàn toàn hạ tầng tính toán trong tương lai. Các giải pháp công nghệ được đề xuất trong chương này là cơ sở trực tiếp cho công tác hiện thực hóa hệ thống và đánh giá thực nghiệm ở các chương tiếp theo.")

    # Save to all locations
    os.makedirs(r"c:\Users\DELL5410\quanLyChamCong\document", exist_ok=True)
    target_path_doc = r"c:\Users\DELL5410\quanLyChamCong\document\CHUONG_5_GIAI_PHAP_CONG_NGHE.docx"
    target_path_root = r"c:\Users\DELL5410\quanLyChamCong\CHUONG_5_GIAI_PHAP_CONG_NGHE.docx"
    target_path_desktop = r"C:\Users\DELL5410\Desktop\CHUONG_5_GIAI_PHAP_CONG_NGHE.docx"

    doc.save(target_path_doc)
    doc.save(target_path_root)
    doc.save(target_path_desktop)
    print(f"Perfect Chapter 5 successfully saved to:\n1. {target_path_doc}\n2. {target_path_root}\n3. {target_path_desktop}")

if __name__ == "__main__":
    build_perfect_chapter_5()

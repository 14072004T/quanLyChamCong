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

def build_chapter_6_sections():
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
        run.font.color.rgb = RGBColor(0x22, 0x22, 0x22)
        return p

    def add_p(text):
        p = doc.add_paragraph()
        p.alignment = WD_ALIGN_PARAGRAPH.JUSTIFY
        p.paragraph_format.first_line_indent = Cm(0.8)
        p.paragraph_format.space_before = Pt(0)
        p.paragraph_format.space_after = Pt(4)
        p.paragraph_format.line_spacing = 1.3
        run = p.add_run(text)
        run.font.name = 'Times New Roman'
        run.font.size = Pt(13)
        return p

    def add_bullet(text, level=0):
        p = doc.add_paragraph()
        p.alignment = WD_ALIGN_PARAGRAPH.JUSTIFY
        p.paragraph_format.left_indent = Cm(0.8 * (level + 1))
        p.paragraph_format.space_before = Pt(0)
        p.paragraph_format.space_after = Pt(3)
        p.paragraph_format.line_spacing = 1.25
        run = p.add_run("• " + text)
        run.font.name = 'Times New Roman'
        run.font.size = Pt(13)
        return p

    def add_formula_box(formula_text, formula_num):
        tbl = doc.add_table(rows=1, cols=2)
        tbl.alignment = WD_TABLE_ALIGNMENT.CENTER
        cell_formula = tbl.cell(0, 0)
        cell_num = tbl.cell(0, 1)
        cell_formula.width = Cm(14.0)
        cell_num.width = Cm(2.0)
        set_cell_background(cell_formula, "F8F9FA")
        set_cell_background(cell_num, "F8F9FA")
        set_cell_margins(cell_formula, top=80, bottom=80, left=150, right=100)
        set_cell_margins(cell_num, top=80, bottom=80, left=50, right=150)
        set_table_borders(tbl, "E0E0E0")

        pf = cell_formula.paragraphs[0]
        pf.alignment = WD_ALIGN_PARAGRAPH.CENTER
        rf = pf.add_run(formula_text)
        rf.italic = True
        rf.font.name = 'Times New Roman'
        rf.font.size = Pt(12)

        pn = cell_num.paragraphs[0]
        pn.alignment = WD_ALIGN_PARAGRAPH.RIGHT
        rn = pn.add_run(f"({formula_num})")
        rn.font.name = 'Times New Roman'
        rn.font.size = Pt(12)
        doc.add_paragraph().paragraph_format.space_after = Pt(3)

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
    # NỘI DUNG CHƯƠNG 6: 6.3 VÀ 6.4
    # =========================================================================

    add_chapter_title("CHƯƠNG 6. HIỆN THỰC VÀ TRIỂN KHAI HỆ THỐNG")

    # 6.3
    add_h1("6.3. Hiện thực chức năng nhận diện khuôn mặt")
    add_p("Chức năng nhận diện khuôn mặt là phân hệ công nghệ nòng cốt của đề tài, thực hiện việc định danh nhân viên một cách tự động, chính xác và không tiếp xúc. Trong chương này, tác giả trình bày chi tiết hiện thực kỹ thuật từ phía máy khách (Client Kiosk), công cụ phân tích trạng thái sống chống giả mạo, tầng bảo mật dịch vụ máy chủ, giải thuật trích xuất - so khớp vector đặc trưng sinh trắc học ArcFace và quy trình tự động hóa khép kín với nghiệp vụ chấm công.")

    # 6.3.1
    add_h2("6.3.1. Hiện thực giao diện và luồng thu nhận hình ảnh thời gian thực (Kiosk Tablet Client)")
    add_p("Giao diện trạm chấm công Kiosk được thiết kế tối ưu hóa cho màn hình cảm ứng gắn tường (máy tính bảng Android/iPad hoặc màn hình Kiosk chuyên dụng), được lập trình trong tệp app/views/chamcong/tablet_cham_cong.php:")
    add_bullet("Thu nhận luồng video thời gian thực: Hệ thống sử dụng API chuẩn HTML5 navigator.mediaDevices.getUserMedia() để bắt luồng video từ camera trước (facingMode: 'user') với độ phân giải tiêu chuẩn HD 1280 × 720 px. Cấu hình này bảo đảm ảnh chụp khuôn mặt có độ sắc nét cao, đủ thông tin quang học cho thuật toán trích xuất đặc trưng.")
    add_bullet("Thiết kế giao diện định hướng người dùng: Phần tử <video> và <canvas> hiển thị được áp dụng thuộc tính CSS transform: scaleX(-1) nhằm đảo chiều trục ngang, tạo ra hiệu ứng phản chiếu gương (mirror effect) tự nhiên giúp nhân viên căn chỉnh tư thế đứng dễ dàng.")
    add_bullet("Khung canh khuôn mặt Oval Guide cố định: Nhằm khắc phục triệt để hiện tượng khung canh bị co méo hoặc bẹp khi người dùng xoay thiết bị giữa hai chế độ màn hình ngang (landscape) và màn hình đứng (portrait), khung định vị khuôn mặt được thiết lập bằng thuộc tính tỉ lệ cố định aspect-ratio: 3 / 4 kết hợp giới hạn chiều cao height: min(78vh, 640px). Đường viền nét đứt màu xanh dương (#60a5fa) cùng hiệu ứng đổ bóng mờ khu vực xung quanh (box-shadow: 0 0 0 999px #0006) giúp nhân viên tự giác đưa khuôn mặt vào đúng trọng tâm camera ở cự ly khuyến nghị 0.5m – 1.2m.")
    add_bullet("Tải trước mô hình học sâu máy khách (face-api.js): Khi nạp trang, hệ thống tải sẵn các trọng số mô hình từ thư mục public/models/ (có cơ chế dự phòng nạp qua CDN nếu tệp cục bộ gián đoạn), bao gồm TinyFaceDetector, FaceLandmark68Net và FaceRecognitionNet. Đặc biệt, tham số kích thước đầu vào được cấu hình chuẩn hóa inputSize: 224 (scoreThreshold: 0.5) đồng bộ tuyệt đối với mô-đun đăng ký khuôn mặt, giúp tăng độ chi tiết của các điểm mốc và tránh hiện tượng sai lệch khoảng cách vector giữa bước đăng ký và bước quẹt thẻ thực tế.")
    add_bullet("Vòng lặp phát hiện khung hình liên tục: Ứng dụng triển khai hàm detect() chạy qua hàm API trình duyệt requestAnimationFrame() để bắt từng frame hình từ thẻ video, chuyển tiếp dữ liệu đối tượng nhận diện vào bộ thẩm tra tính sống LivenessDetector.")

    # 6.3.2
    add_h2("6.3.2. Hiện thực cơ chế phát hiện trạng thái sống đa lớp (Client Liveness Engine)")
    add_p("Để ngăn chặn triệt để hành vi chấm công hộ bằng ảnh in giấy (Print Attack) hoặc video phát lại qua màn hình điện thoại (Replay Attack), hệ thống hiện thực lớp LivenessDetector phiên bản 3.0 trong tệp public/js/face-liveness.js:")
    add_bullet("Giải thuật phát hiện chớp mắt tự nhiên (Eye Blink Detection qua EAR): Ảnh in tĩnh hoặc video tĩnh không có khả năng chớp mắt sinh học thật sự. Lớp LivenessDetector trích xuất 6 tọa độ mốc mi mắt cho mỗi mắt từ 68 điểm landmark (mắt trái từ p36 đến p41, mắt phải từ p42 đến p47) và tính toán tỷ lệ co mở mắt (Eye Aspect Ratio - EAR):")
    add_formula_box("EAR = (||p₂ - p₆|| + ||p₃ - p₅||) / (2 × ||p₁ - p₄||)", "6.1")
    add_p("Trong đó ||p_i - p_j|| là khoảng cách Euclid giữa các điểm mốc mi mắt. Máy trạng thái (Blink State Machine) theo dõi liên tục giá trị EAR qua từng khung hình:")
    add_code_block("  [TRẠNG THÁI: OPEN] ──(EAR giảm < Ngưỡng nhắm)──► [TRẠNG THÁI: CLOSING / CLOSED]\n          ▲                                                          │\n          │                                                          ▼\n          └───────────(EAR phục hồi > Ngưỡng mở)──────────── [GHI NHẬN CHỚP MẮT]\n                                                              (blinkCount >= 1,\n                                                               blinkDetected = true)")
    add_bullet("Máy trạng thái quy định: Khi người dùng mở mắt bình thường, EAR dao động trong khoảng 0.25 - 0.35. Khi chớp mắt, khoảng cách giữa mí trên và mí dưới thu hẹp đột ngột, EAR giảm xuống dưới 0.18. Khi mắt mở lại, hệ thống ghi nhận một chu kỳ chớp mắt hợp lệ (blinkCount tăng 1) và đánh dấu cờ blinkDetected = true.")
    add_bullet("Tích hợp các lớp kiểm tra quang học và hình thái học thụ động:")
    add_bullet("Lớp 1 - Static Frame Check & Geometric Jitter: Đánh giá phương sai tọa độ của 68 điểm landmark giữa các khung hình liên tiếp. Người thật luôn có các vi dao động cơ mặt tự nhiên (vi rung đầu, cơ mắt). Nếu phương sai bằng 0 hoặc nhỏ hơn ngưỡng GEOMETRIC_VARIANCE_MIN = 0.00002, hệ thống xác định đây là ảnh tĩnh in trên giấy được đưa trước camera.", 1)
    add_bullet("Lớp 2 - LBP Texture & Laplacian Moire: Thuật toán phân tích kết cấu cục bộ (Local Binary Pattern - LBP) và toán tử đạo hàm bậc hai Laplacian trích xuất mẫu vân bề mặt da. Khi kẻ gian đưa màn hình điện thoại hoặc máy tính bảng trước camera, hiện tượng tán xạ ánh sáng nền và dải sọc vân Moire sẽ bị bộ lọc phát hiện và từ chối.", 1)
    add_bullet("Lớp 3 - Head Pose & Face Size Variation: Tính toán biến thiên góc quay đầu (Yaw, Pitch) và biến thiên diện tích khung bao mặt (Face Size Variance >= 0.2) phát sinh từ nhịp thở tự nhiên của cơ thể người.", 1)
    add_bullet("Cơ chế tiền nạp Secret và ký số HMAC-SHA256 bảo vệ tính toàn vẹn:")
    add_bullet("Để chống tấn công chèn ép gói tin (Race Condition) và bắt lại gói tin (Replay Attack), client sử dụng hàm prefetchSessionSecret() và consumeSessionSecret() theo cơ chế Promise duy nhất (Single-Promise Pattern) gọi đến endpoint index.php?page=face-liveness-session để lấy khóa bí mật phiên (sessionSecret) từ máy chủ.", 1)
    add_bullet("Khi quá trình quét đạt đủ tối thiểu 10 khung hình hợp lệ (FRAME_COUNT_REQUIRED = 10) và thỏa mãn điều kiện chớp mắt, client đóng gói toàn bộ dữ liệu gồm mốc thời gian, số lượng khung hình, điểm số thành phần và trạng thái chớp mắt thành chuỗi JSON Payload, sau đó sử dụng khóa sessionSecret tính toán mã băm xác thực HMAC-SHA256 trước khi gửi lên máy chủ.", 1)

    # 6.3.3
    add_h2("6.3.3. Hiện thực tầng xác thực bảo mật Server-side (LivenessHelper)")
    add_p("Toàn bộ dữ liệu kiểm tra trạng thái sống gửi từ máy khách được thẩm định nghiêm ngặt tại máy chủ PHP thông qua lớp LivenessHelper (tệp app/helpers/LivenessHelper.php) bằng hàm validateLivenessToken():")
    add_bullet("Kiểm tra tính toàn vẹn và chữ ký số HMAC-SHA256: Máy chủ tính lại mã băm mong đợi dựa trên chuỗi JSON payload gốc và khóa bí mật lưu trong $_SESSION['liveness_session_secret']: hash_hmac('sha256', $payloadStr, $secret). Việc so sánh giữa chữ ký gửi lên và chữ ký máy chủ được thực hiện qua hàm hash_equals() – hàm so sánh chuỗi với thời gian bất biến (Constant-time comparison) nhằm triệt tiêu hoàn toàn lỗ hổng tấn công phân tích thời gian (Timing Attack).")
    add_bullet("Kiểm tra độ tươi của Token (Freshness Check): Thời điểm hoàn tất kiểm tra trạng thái sống ($payload['completedAt']) được đối chiếu với thời gian thực của máy chủ. Token chỉ được chấp nhận nếu thời gian tạo không vượt quá 120 giây ($ageMs <= 120.000 ms). Các gói tin cũ bị bắt lại và gửi trễ sẽ bị từ chối ngay lập tức.")
    add_bullet("Ràng buộc số lượng khung hình và thời lượng phiên: Yêu cầu số khung hình xử lý tối thiểu là 10 ($frameCount >= 10) và thời lượng quét không vượt quá 300 giây (5 phút), loại trừ các hành vi giả lập gửi dữ liệu tức thời.")
    add_bullet("Ràng buộc hành vi chớp mắt bắt buộc: Máy chủ kiểm tra nghiêm ngặt hai giá trị !empty($payload['blinkDetected']) và intval($payload['blinkCount']) >= 1. Nếu không có sự kiện chớp mắt tự nhiên trong quá trình quét, máy chủ trả về mã từ chối BLINK_NOT_DETECTED và thông báo: 'Chưa phát hiện chớp mắt tự nhiên. Vui lòng thử lại.'")
    add_bullet("Kiểm tra ngưỡng điểm tổng hợp: Chỉ số tổng hợp $combinedScore phải đạt từ 0.55 (tức 55%) trở lên mới được xem là vượt qua bài kiểm tra người thật.")
    add_bullet("Hủy Secret dùng một lần (One-Time Token): Ngay khi phiên thẩm định kết thúc (dù thành công hay thất bại), hệ thống chủ động gọi unset($_SESSION['liveness_session_secret']), biến Token thành khóa dùng một lần duy nhất, loại bỏ hoàn toàn khả năng sử dụng lại cùng một token cho các lượt chấm công tiếp theo.")
    add_bullet("Ghi nhật ký kiểm toán (Audit Trail): Mọi nỗ lực xác thực đều được ghi nhận vào tệp nhật ký uploads/liveness_logs/YYYY-MM-DD.log với đầy đủ thông tin: địa chỉ IP máy khách, mã người dùng, kết quả kiểm tra, số frame và số lần chớp mắt phục vụ công tác rà soát an ninh.")

    # 6.3.4
    add_h2("6.3.4. Hiện thực trích xuất vector đặc trưng và so khớp sinh trắc học (FaceController & FaceServiceClient)")
    add_p("Sau khi vượt qua tầng kiểm tra tính sống, dữ liệu hình ảnh được tiếp nhận bởi FaceController.php tại hàm tabletVerifyApi():")
    add_bullet("Giao tiếp trích xuất đặc trưng với ArcFace Embedding Service: Hình ảnh chụp thời gian thực dạng chuỗi Base64 JPEG chất lượng cao được FaceServiceClient::getArcFaceEmbedding($photo) chuyển đổi thành tệp nhị phân tạm thời và gửi qua cURL HTTP POST multipart/form-data đến dịch vụ ArcFace. Dịch vụ AI thực hiện phát hiện, căn chỉnh xoay trục và trả về vector nhúng đặc trưng 512 chiều:")
    add_formula_box("v = [e₁, e₂, e₃, ..., e₅₁₂] ∈ ℝ⁵¹²", "6.2")
    add_bullet("Chuẩn hóa vector L2: Để loại bỏ sai số về độ sáng và tỷ lệ khung hình, hàm normalizeEmbedding() thực hiện chuẩn hóa vector theo chuẩn L2 đưa độ dài về đơn vị:")
    add_formula_box("v_{norm} = v / ||v||₂ = [e₁ / ||v||₂, e₂ / ||v||₂, ..., e₅₁₂ / ||v||₂]", "6.3")
    add_bullet("Thuật toán so khớp độ tương đồng Cosine (Cosine Similarity): Máy chủ PHP tải toàn bộ hồ sơ khuôn mặt nhân viên từ bảng face_profile và tính toán độ tương đồng giữa vector khuôn mặt quét mới (v_query) với từng vector đã lưu trữ (v_stored) qua hàm cosineSimilarity():")
    add_formula_box("Cosine Similarity(v_query, v_stored) = ∑_{i=1}^{512} (v_{query,i} × v_{stored,i})", "6.4")
    add_bullet("Giải thuật áp dụng ngưỡng và kiểm tra biên độ an toàn (Margin Check):")
    add_bullet("Ngưỡng tương đồng ArcFace: Biến môi trường ARCFACE_COSINE_THRESHOLD quy định mức sàn so khớp mặc định là 0.40. Các hồ sơ có độ tương đồng dưới 0.40 bị loại bỏ.", 1)
    add_bullet("Cơ chế phân vùng mập mờ và Margin Check: Hệ thống sắp xếp các ứng viên đạt ngưỡng theo thứ tự điểm tương đồng giảm dần, xác định ứng viên có điểm cao nhất S₁ và ứng viên đứng thứ nhì S₂. Nếu điểm S₁ rơi vào khoảng điểm mấp mé cận biên (0.40 <= S₁ < 0.55), hệ thống bắt buộc kiểm tra độ chênh lệch biên độ: ΔS = S₁ - S₂. Nếu khoảng cách giữa hai ứng viên nhỏ hơn 0.05 (ΔS < 0.05), hệ thống chủ động từ chối nhận diện để ngăn chặn rủi ro nhận diện nhầm giữa hai nhân viên có nét mặt gần giống nhau trong điều kiện ánh sáng thay đổi.", 1)
    add_bullet("Cơ chế nhận diện dự phòng (Dual-Mode Fallback): Nhằm bảo đảm tính tương thích với các dữ liệu cũ, nếu nhân viên chưa có vector ArcFace 512 chiều, hệ thống tự động chuyển sang so khớp khoảng cách Euclidean và Cosine trên các vector đặc trưng 128 chiều ở các góc mặt (front, left, right).")

    # 6.3.5
    add_h2("6.3.5. Hiện thực quy trình đăng ký hồ sơ khuôn mặt nhân viên (registerView & registerApi)")
    add_p("Quy trình đăng ký khuôn mặt được chuẩn hóa dành riêng cho cán bộ Nhân sự (HR) thông qua giao diện chuyên biệt app/views/face/register.php và bộ điều khiển FaceController.php:")
    add_bullet("Giao diện đăng ký trực quan với hiệu ứng quét Laser: Giao diện bố trí theo dạng Grid 2 cột: Cột bên trái hiển thị luồng camera trực tiếp với hiệu ứng đường quét Laser xanh chuyển động (scan-motion) tạo trải nghiệm công nghệ hiện đại; Cột bên phải hiển thị danh sách nhân viên được gom nhóm theo phòng ban, sắp xếp ưu tiên hiển thị nhân viên chưa đăng ký lên đầu danh sách.")
    add_bullet("Thu nhận đa góc mặt và kiểm soát độ nét: Quá trình quét yêu cầu nhân viên nhìn thẳng và quay nhẹ các góc mặt. Thuật toán kiểm tra độ tự tin nhận diện (confidence >= 0.80), nếu ảnh mờ nhòe hoặc mất nét, hệ thống yêu cầu quét lại.")
    add_bullet("Giải thuật chống trùng lặp khuôn mặt giữa các nhân viên (Duplicate Prevention):")
    add_bullet("Khi nhân viên mới quét mặt, hệ thống tính toán vector ArcFace 512 chiều và đối soát với toàn bộ nhân viên đã có hồ sơ trong bảng face_profile theo ngưỡng trùng lặp ARCFACE_DUPLICATE_COSINE_THRESHOLD = 0.75.", 1)
    add_bullet("Cơ chế xác nhận trùng lặp 2 bước (2-Step Duplicate Probe): Để loại trừ hiện tượng cảnh báo trùng nhầm do góc chụp ngẫu nhiên bị tương đồng, hệ thống lưu tạm ID nghi vấn vào session ($_SESSION['face_duplicate_probe_' . $maND]). Nếu lần quét thứ 2 liên tiếp trong vòng 300 giây vẫn trùng với cùng một nhân viên, hệ thống mới chính thức khóa đăng ký (BLOCK) và thông báo rõ tên nhân viên bị trùng; nếu mới trùng lần đầu, hệ thống chỉ yêu cầu quét lại ở góc nhìn khác (RETRY). Cơ chế này ngăn chặn tuyệt đối tình trạng một nhân viên dùng khuôn mặt của mình đăng ký cho tài khoản người khác để chấm công hộ.", 1)
    add_bullet("Lưu trữ hồ sơ khuôn mặt: Khi kiểm tra hợp lệ, hàm FaceModel::saveFaceProfile() lưu đồng thời chuỗi JSON vector ArcFace 512 chiều và các vector góc mặt vào bảng face_profile.")

    # 6.3.6
    add_h2("6.3.6. Hiện thực tự động ghi nhận chấm công và lưu vết minh chứng")
    add_p("Sau khi xác định chính xác danh tính nhân viên qua khuôn mặt, hệ thống tự động hoàn tất chu trình chấm công mà không cần người dùng thao tác bấm nút:")
    add_bullet("Lưu trữ ảnh chụp thực tế làm bằng chứng minh bạch: Ảnh chụp khuôn mặt tại thời điểm quét được trích xuất từ Canvas, giải mã và lưu trữ vĩnh viễn vào thư mục uploads/attendance_faces/ với tên tệp chuẩn hóa độc nhất: [maND]_[timestamp]_[random].jpg.")
    add_bullet("Ghi nhận nhật ký thô (TABLET_SCANS): Gọi hàm insertTabletScan(maND, photoFilename) để ghi một bản ghi vào bảng tablet_face_scans, bảo toàn dữ liệu nhật ký gốc phục vụ đối soát.")
    add_bullet("Thuật toán tự động ghép cặp giờ vào/ra trong ngày:")
    add_bullet("Kiểm tra số lần quét hôm nay qua getTabletScanRangeToday(maND).", 1)
    add_bullet("Lần quét đầu tiên trong ngày (soLanQuet <= 1): Được xác định là sự kiện Check-in (Vào ca). Hệ thống đối chiếu thời điểm hiện tại với giờ bắt đầu ca làm việc (gioBatDau). Nếu quét trễ hơn giờ quy định quá 15 phút, hệ thống tự động gắn nhãn vi phạm 'Đi muộn x phút'; nếu trong giới hạn, ghi chú 'Đúng giờ'. Bản ghi được đồng bộ vào bảng lichsuchamcong với phương thức 'TABLET'.", 1)
    add_bullet("Các lần quét tiếp theo trong ngày (soLanQuet > 1): Thuật toán tự động cập nhật mốc Check-out (Ra ca) thành thời gian quét mới nhất, đối chiếu với giờ kết thúc ca (gioKetThuc) để ghi chú 'Về sớm y phút' hoặc 'Đã lưu'.", 1)
    add_bullet("Xử lý ca nghỉ (Ca OFF): Nếu hôm nay nhân viên được phân ca nghỉ hoặc ca không tính công (cotinhcong = 'no'), hệ thống thông báo rõ ràng cho nhân viên và không ghi nhận công sai lệch.", 1)
    add_bullet("Phản hồi giao diện và tự động tái lập (Auto Restart): Giao diện Kiosk hiển thị thanh trạng thái màu xanh báo thành công kèm họ tên nhân viên, sau 1.2 giây sẽ tự động xóa phiên và tái lập chu kỳ nhận diện mới cho nhân viên tiếp theo.")

    # 6.4
    add_h1("6.4. Triển khai hệ thống")
    add_p("Triển khai hệ thống là giai đoạn hiện thực hóa giải pháp phần mềm trên hạ tầng phần cứng và môi trường mạng thực tế của doanh nghiệp. Nội dung phần này phân tích chi tiết mô hình kiến trúc triển khai, quy trình cấu hình máy chủ Web, cơ sở dữ liệu, dịch vụ AI Python, yêu cầu an ninh mạng WebRTC qua HTTPS và công tác quản lý lưu trữ, sao lưu dữ liệu vận hành.")

    # 6.4.1
    add_h2("6.4.1. Mô hình kiến trúc hạ tầng triển khai (Deployment Architecture)")
    add_p("Hệ thống quản lý chấm công nhận diện khuôn mặt được triển khai theo mô hình kiến trúc phân tán 3 tầng (3-Tier Distributed Architecture), bảo đảm tính tách bạch trách nhiệm, an toàn dữ liệu và khả năng mở rộng quy mô:")
    add_code_block("  ┌────────────────────────────────────────────────────────────────────────┐\n  │                       TẦNG MÁY KHÁCH (CLIENT TIER)                      │\n  │  • Trạm Kiosk chấm công: Máy tính bảng (Android / iPad / Mini PC Kiosk) │\n  │    Chạy trình duyệt Chrome / WebView toàn màn hình qua giao thức HTTPS   │\n  │  • Máy trạm quản trị & nhân viên: Trình duyệt Web máy tính / điện thoại  │\n  └───────────────────────────────────┬────────────────────────────────────┘\n                                      │ HTTPS (Cổng 443) / WSS\n                                      ▼\n  ┌────────────────────────────────────────────────────────────────────────┐\n  │                   TẦNG MÁY CHỦ ỨNG DỤNG (APPLICATION TIER)              │\n  │                                                                        │\n  │  [MÁY CHỦ WEB CHÍNH - CORE WEB SERVER]                                 │\n  │  • Nền tảng: Apache 2.4 + PHP 8.x (Kiến trúc MVC)                       │\n  │  • Định tuyến qua URL Rewrite (.htaccess / index.php / api.php)        │\n  │  • Quản lý phiên đăng nhập dài hạn (Session 30 ngày)                   │\n  │  • Phân hệ bảo mật LivenessHelper & Controller nghiệp vụ               │\n  │                                                                        │\n  │           │ cURL HTTP POST (Multipart)     ▲                           │\n  │           ▼ (Cổng 8000 hoặc HTTPS API)     │ Mảng JSON 512-D Vector    │\n  │                                                                        │\n  │  [DỊCH VỤ AI TRÍCH XUẤT ĐẶC TRƯNG - ARCFACE EMBEDDING SERVICE]         │\n  │  • Dịch vụ trích xuất đặc trưng ArcFace (512 chiều)                    │\n  │  • [Dự phòng nội bộ]: Python 3.10+ FastAPI / Uvicorn (face_service/)   │\n  │    Chạy mô hình MTCNN + MiniFASNet CDC + ArcFace ResNet50              │\n  └──────────────────┬─────────────────────────────────────────────────────┘\n                     │ Kết nối TCP (Cổng 3306) / Local Unix Socket\n                     ▼\n  ┌────────────────────────────────────────────────────────────────────────┐\n  │                     TẦNG DỮ LIỆU & LƯU TRỮ (DATA TIER)                 │\n  │  • Hệ quản trị CSDL quan hệ: MySQL 8.0 / MariaDB (Bảng mã utf8mb4)     │\n  │    Lưu trữ thông tin nhân sự, ca kíp, vector ArcFace, lịch sử quẹt thẻ │\n  │  • Hệ thống tệp (File Storage System):                                 │\n  │    - uploads/attendance_faces/ (Ảnh chụp thực tế minh chứng chấm công) │\n  │    - uploads/liveness_logs/    (Nhật ký thẩm tra an ninh tính sống)    │\n  └────────────────────────────────────────────────────────────────────────┘")
    add_bullet("Ưu điểm nổi bật của kiến trúc triển khai:")
    add_bullet("Tối ưu hóa tài nguyên phần cứng: Tác vụ nặng về tính toán ma trận của mạng nơ-ron học sâu được ủy quyền riêng cho dịch vụ AI hoặc bộ suy luận tối ưu hóa ONNX Runtime, giúp máy chủ web PHP duy trì tốc độ phản hồi nhanh chóng cho hàng trăm yêu cầu nghiệp vụ đồng thời.", 1)
    add_bullet("Tính sẵn sàng và linh hoạt cao: Máy chủ Web và Dịch vụ AI có thể được triển khai trên cùng một máy chủ vật lý trong giai đoạn đầu (sử dụng địa chỉ cục bộ 127.0.0.1:8000) hoặc dễ dàng tách thành hai máy chủ độc lập khi doanh nghiệp mở rộng quy mô mà không cần sửa đổi mã nguồn.", 1)

    # 6.4.2
    add_h2("6.4.2. Cấu hình môi trường máy chủ Web & Cơ sở dữ liệu (Core Backend & Database)")
    add_p("Hệ thống máy chủ ứng dụng trung tâm được thiết lập trên nền tảng phần mềm mã nguồn mở chuẩn mực, có thể vận hành ổn định trên môi trường Windows Server (thông qua bộ công cụ XAMPP) hoặc máy chủ Linux (Ubuntu Server 22.04 LTS):")
    add_bullet("Cấu hình Web Server Apache 2.4:")
    add_bullet("Kích hoạt mô-đun mod_rewrite nhằm xử lý cơ chế định tuyến RESTful API thông qua tệp .htaccess: chuyển hướng các đường dẫn ảo /api/* đến tệp xử lý tập trung api.php và định tuyến trang qua index.php.", 1)
    add_bullet("Cấu hình ảo hóa VirtualHost với DocumentRoot trỏ chính xác vào thư mục gốc của dự án, thiết lập quyền AllowOverride All để cho phép tệp .htaccess can thiệp cấu hình.", 1)
    add_bullet("Cấu hình môi trường thực thi PHP 8.x:")
    add_bullet("Kích hoạt các phần mở rộng bắt buộc: mysqli và pdo_mysql (truy vấn CSDL), curl (giao tiếp dịch vụ AI), gd (xử lý hình ảnh Base64, nén ảnh JPEG), openssl (ký mã hash HMAC-SHA256), mbstring (xử lý chuỗi đa byte UTF-8).", 1)
    add_bullet("Thiết lập các thông số tối ưu trong php.ini: memory_limit = 256M (đáp ứng bộ nhớ xử lý đồng thời nhiều frame ảnh chất lượng cao), upload_max_filesize = 20M, post_max_size = 25M, max_execution_time = 60.", 1)
    add_bullet("Cấu hình duy trì phiên làm việc lâu dài (Long-lived Session): Để trạm Tablet Kiosk hoạt động liên tục trong nhiều tuần mà không bị ngắt kết nối do hết hạn session, hệ thống cấu hình session.gc_maxlifetime = 2592000 (30 ngày) và cookie thời lượng 30 ngày, kèm cờ an ninh httponly = true và samesite = 'Lax'.", 1)
    add_bullet("Triển khai Cơ sở dữ liệu MySQL 8.0:")
    add_bullet("Khởi tạo cấu trúc bảng từ tệp kịch bản CSDL dl_final.sql bao gồm các thực thể quan hệ: NGUOIDUNG, NHANVIEN, TAIKHOAN, CALAMVIEC, CANHANVIEN, face_profile, tablet_face_scans, lichsuchamcong, duyetcongthang.", 1)
    add_bullet("Thiết lập bảng mã mặc định là utf8mb4 cùng bộ đối chiếu utf8mb4_unicode_ci để lưu trữ và hiển thị hoàn hảo tiếng Việt có dấu.", 1)
    add_bullet("Đồng bộ múi giờ Việt Nam: Lớp KetNoi (app/models/ketNoi.php) tự động thực thi truy vấn SET time_zone = '+07:00' ngay khi mở kết nối, bảo đảm các hàm thời gian của CSDL (NOW(), CURRENT_DATE) luôn trùng khớp với múi giờ của máy chủ PHP (Asia/Ho_Chi_Minh).", 1)

    # 6.4.3
    add_h2("6.4.3. Triển khai phân hệ dịch vụ AI nhận diện khuôn mặt (Python FastAPI Service)")
    add_p("Bên cạnh việc kết nối tới dịch vụ ArcFace Embedding Service, đề tài đã đóng gói hoàn chỉnh phân hệ dịch vụ AI nội bộ đặt tại thư mục face_service/ để sẵn sàng triển khai độc lập:")
    add_bullet("Môi trường thực thi: Phân hệ chạy trên nền tảng Python 3.10 trở lên, sử dụng môi trường ảo cô lập venv để tránh xung đột thư viện hệ thống.")
    add_bullet("Danh mục thư viện phụ thuộc (requirements.txt): Bao gồm fastapi==0.111.0, uvicorn==0.29.0, onnxruntime==1.18.0, torch==2.3.0, torchvision==0.18.0, facenet-pytorch==2.6.0, opencv-python-headless==4.9.0.80, Pillow==10.3.0, numpy==1.26.4.")
    add_bullet("Cài đặt bộ trọng số mô hình học sâu (Weights): Kịch bản tự động download_weights.py thực hiện tải và nạp vào thư mục face_service/weights/ các mô hình tối ưu:")
    add_bullet("w600k_r50.onnx: Mô hình ArcFace ResNet-50 trích xuất vector đặc trưng 512 chiều được tối ưu hóa qua động cơ ONNX Runtime, hỗ trợ thực thi mượt mà trên cả CPU phổ thông và GPU CUDA.", 1)
    add_bullet("MiniFASNetV1SE_80x80.onnx và MiniFASNetV2_80x80.onnx: Bộ đôi mô hình chống giả mạo dựa trên tích chập vi phân tâm CDC, phân loại chính xác ảnh thật và ảnh tái phát qua màn hình.", 1)
    add_bullet("Tự động hóa vận hành qua tệp kịch bản (start.bat): Kịch bản khởi động trên môi trường Windows tự động kiểm tra sự tồn tại của Python, tự tạo venv nếu chưa có, tự cài đặt gói thư viện phụ thuộc, tự sao chép tệp cấu hình .env.example thành .env, kiểm tra tệp trọng số và khởi chạy máy chủ FastAPI qua lệnh uvicorn main:app --host 127.0.0.1 --port 8000.")
    add_bullet("Khả năng chuyển đổi thành dịch vụ hệ thống (System Daemon): Trên môi trường máy chủ Linux, phân hệ được đăng ký dưới dạng tiến trình nền Systemd (face_service.service) hoặc quản lý qua Supervisor, bảo đảm dịch vụ tự động khởi động cùng hệ điều hành và tự phục hồi khi xảy ra sự cố.")

    # 6.4.4
    add_h2("6.4.4. Yêu cầu mạng, cấu hình bảo mật SSL/HTTPS và thiết bị Kiosk Tablet")
    add_p("Việc triển khai thực tế trạm chấm công đòi hỏi sự chuẩn bị khắt khe về hạ tầng mạng và phần cứng thiết bị:")
    add_bullet("Yêu cầu bắt buộc về chứng chỉ bảo mật SSL/HTTPS:")
    add_bullet("Chính sách an ninh trình duyệt (Secure Contexts Policy): Nhằm bảo vệ quyền riêng tư của người dùng, toàn bộ các trình duyệt web hiện đại (Google Chrome, Microsoft Edge, Safari, Android System WebView) áp dụng quy định bảo mật khắt khe: API truy cập camera navigator.mediaDevices.getUserMedia() bị chặn hoàn toàn nếu trang web chạy trên giao thức không an toàn http:// (ngoại trừ tên miền localhost). Do đó, việc triển khai giao thức https:// là điều kiện tiên quyết.", 1)
    add_bullet("Giải pháp chứng chỉ SSL: Đối với hệ thống triển khai trên Internet, sử dụng chứng chỉ số miễn phí Let's Encrypt được tự động gia hạn qua Certbot. Đối với hệ thống triển khai hoàn toàn trong mạng nội bộ (Intranet LAN), quản trị viên sử dụng công cụ mkcert hoặc OpenSSL để khởi tạo Root CA và cấp chứng chỉ SSL nội bộ cho tên miền máy chủ (ví dụ: https://chamcong.local), sau đó cài đặt chứng chỉ tin cậy vào thiết bị Tablet.", 1)
    add_bullet("Cấu hình thiết bị phần cứng Kiosk Tablet:")
    add_bullet("Thiết bị đề xuất: Máy tính bảng Android màn hình 10-11 inch, độ phân giải Full HD, camera trước tối thiểu 5.0 MP, hoặc máy tính tất cả trong một (All-in-one) gắn tường.", 1)
    add_bullet("Vị trí và độ cao lắp đặt: Gắn cố định trên tường hoặc chân đế Kiosk tại sảnh ra vào với độ cao từ mặt sàn đến tâm ống kính camera trong khoảng 1.45m – 1.55m, góc nghiêng nhẹ hướng lên 5° - 10° để bao quát tốt tầm vóc nhân viên cao từ 1.50m đến 1.85m.", 1)
    add_bullet("Điều kiện ánh sáng: Bố trí tại khu vực có ánh sáng khuếch tán đồng đều (độ rọi khuyến nghị 300 - 500 lux), tuyệt đối tránh đặt đối diện trực tiếp cửa sổ lớn hoặc nguồn sáng ngược mạnh (Backlight) gây lóa ống kính.", 1)
    add_bullet("Cấu hình phần mềm chuyên dụng (Kiosk Mode): Thiết bị được cài đặt trình duyệt chế độ Kiosk (như Fully Kiosk Browser trên Android hoặc Guided Access trên iPad). Trình duyệt được cấu hình khóa toàn màn hình, chặn thanh điều hướng hệ thống, kích hoạt tính năng luôn bật màn hình (Stay Awake), và cấp quyền mặc định vĩnh viễn cho Camera.")

    # 6.4.5
    add_h2("6.4.5. Quản lý lưu trữ tệp, nhật ký vận hành (Audit Logs) và sao lưu dữ liệu")
    add_p("Hệ thống chấm công là ứng dụng lưu trữ dữ liệu theo thời gian thực liên tục, đòi hỏi phương án quản lý tệp và sao lưu dữ liệu chặt chẽ:")
    add_bullet("Phân quyền và bảo vệ thư mục lưu trữ: Hệ thống tệp trên máy chủ được phân quyền nghiêm ngặt (chmod 755 đối với thư mục và chmod 644 đối với tệp):")
    add_bullet("uploads/attendance_faces/: Thư mục lưu trữ hình ảnh minh chứng chấm công thực tế. Mỗi tệp được đặt tên ngẫu nhiên kết hợp mã nhân viên và dấu thời gian để tránh trùng lặp.", 1)
    add_bullet("uploads/liveness_logs/: Thư mục lưu vết thẩm tra tính sống và nhật ký so khớp khuôn mặt.", 1)
    add_bullet("Nhật ký vận hành phục vụ hậu kiểm (Audit Trail): Toàn bộ các quyết định MATCH / REJECT, điểm số tương đồng Cosine, khoảng cách Euclid và biên độ an toàn Margin giữa các ứng viên đều được ghi chi tiết vào tệp uploads/liveness_logs/tablet_recognition.log. Khi nhân viên có thắc mắc hoặc báo lỗi không nhận diện được, cán bộ nhân sự và quản trị kỹ thuật có thể tra cứu chính xác mốc thời gian và nguyên nhân từ chối.", 1)
    add_bullet("Chiến lược sao lưu dự phòng định kỳ (Backup & Disaster Recovery):")
    add_bullet("Sao lưu CSDL tự động: Lập lịch tác vụ tự động (Cron job trên Linux hoặc Task Scheduler trên Windows) thực thi lệnh mysqldump xuất dữ liệu hàng ngày vào lúc 02:00 sáng. Tệp nén sao lưu được chuyển tiếp lưu trữ tại máy chủ sao lưu độc lập.", 1)
    add_bullet("Chính sách lưu trữ ảnh chấm công (Data Retention): Hình ảnh chấm công được phân tầng lưu trữ: lưu trữ trực tuyến tốc độ cao trong vòng 6 tháng gần nhất để phục vụ HR tra cứu đối soát kỳ công; dữ liệu ảnh quá 6 tháng được tự động đóng gói nén zip và chuyển sang kho lưu trữ phụ để giải phóng dung lượng ổ cứng cho máy chủ chính.", 1)

    # Save to documents
    os.makedirs(r"document", exist_ok=True)
    target_doc = r"document\CHUONG_6_MUC_6.3_6.4.docx"
    target_root = r"CHUONG_6_MUC_6.3_6.4.docx"

    doc.save(target_doc)
    doc.save(target_root)
    print(f"Chapter 6 sections successfully saved to:\n1. {target_doc}\n2. {target_root}")

if __name__ == "__main__":
    build_chapter_6_sections()

"""
Script chuyển đổi file PDF sang file Word (.docx)
Sử dụng thư viện pdf2docx

Cách dùng:
    python pdf_to_word.py <đường_dẫn_file_pdf>
    python pdf_to_word.py <đường_dẫn_file_pdf> <đường_dẫn_file_docx_output>
    python pdf_to_word.py <thư_mục_chứa_nhiều_pdf>

Ví dụ:
    python pdf_to_word.py "C:\\Users\\Documents\\baocao.pdf"
    python pdf_to_word.py "C:\\Users\\Documents\\baocao.pdf" "C:\\Users\\Documents\\baocao.docx"
    python pdf_to_word.py "C:\\Users\\Documents\\PDFs"
"""

import sys
import os
from pathlib import Path

try:
    from pdf2docx import Converter
except ImportError:
    print("❌ Chưa cài thư viện pdf2docx. Đang cài đặt...")
    os.system(f"{sys.executable} -m pip install pdf2docx")
    from pdf2docx import Converter


def convert_pdf_to_word(pdf_path: str, docx_path: str = None) -> str:
    """
    Chuyển đổi một file PDF sang Word.
    
    Args:
        pdf_path: Đường dẫn file PDF đầu vào
        docx_path: Đường dẫn file Word đầu ra (tùy chọn, mặc định cùng tên với PDF)
    
    Returns:
        Đường dẫn file Word đã tạo
    """
    pdf_path = Path(pdf_path).resolve()
    
    if not pdf_path.exists():
        raise FileNotFoundError(f"Không tìm thấy file: {pdf_path}")
    
    if not pdf_path.suffix.lower() == '.pdf':
        raise ValueError(f"File không phải PDF: {pdf_path}")
    
    if docx_path is None:
        docx_path = pdf_path.with_suffix('.docx')
    else:
        docx_path = Path(docx_path).resolve()
    
    print(f"📄 Đang chuyển đổi: {pdf_path.name}")
    print(f"   → {docx_path.name}")
    
    cv = Converter(str(pdf_path))
    cv.convert(str(docx_path))
    cv.close()
    
    print(f"✅ Hoàn thành: {docx_path}")
    return str(docx_path)


def convert_folder(folder_path: str) -> list:
    """
    Chuyển đổi tất cả file PDF trong một thư mục sang Word.
    
    Args:
        folder_path: Đường dẫn thư mục chứa các file PDF
    
    Returns:
        Danh sách các file Word đã tạo
    """
    folder = Path(folder_path).resolve()
    pdf_files = list(folder.glob("*.pdf"))
    
    if not pdf_files:
        print(f"⚠️ Không tìm thấy file PDF nào trong: {folder}")
        return []
    
    print(f"📁 Tìm thấy {len(pdf_files)} file PDF trong: {folder}")
    print("-" * 50)
    
    results = []
    for i, pdf_file in enumerate(pdf_files, 1):
        print(f"\n[{i}/{len(pdf_files)}]")
        try:
            result = convert_pdf_to_word(str(pdf_file))
            results.append(result)
        except Exception as e:
            print(f"❌ Lỗi khi chuyển đổi {pdf_file.name}: {e}")
    
    print(f"\n{'=' * 50}")
    print(f"📊 Kết quả: {len(results)}/{len(pdf_files)} file đã chuyển đổi thành công")
    return results


def main():
    if len(sys.argv) < 2:
        print(__doc__)
        print("\n⚠️ Vui lòng cung cấp đường dẫn file PDF hoặc thư mục.")
        sys.exit(1)
    
    input_path = sys.argv[1]
    path = Path(input_path)
    
    if path.is_dir():
        # Chuyển đổi hàng loạt
        convert_folder(input_path)
    elif path.is_file():
        # Chuyển đổi một file
        output_path = sys.argv[2] if len(sys.argv) > 2 else None
        convert_pdf_to_word(input_path, output_path)
    else:
        print(f"❌ Đường dẫn không hợp lệ: {input_path}")
        sys.exit(1)


if __name__ == "__main__":
    main()

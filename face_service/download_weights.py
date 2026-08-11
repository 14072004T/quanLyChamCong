"""
Download model weights cho Face Service
=========================================
Tải về:
  1. ArcFace w600k_r50.onnx (từ InsightFace)
  2. MiniFASNet V1SE + V2 ONNX (từ Silent-Face-Anti-Spoofing)
"""

import os
import sys
import urllib.request
import hashlib

WEIGHTS_DIR = "weights"
os.makedirs(WEIGHTS_DIR, exist_ok=True)

MODELS = [
    {
        "name": "ArcFace w600k_r50 (ResNet50 512-dim)",
        "filename": "w600k_r50.onnx",
        # Model chính thức từ InsightFace
        "url": "https://github.com/deepinsight/insightface/releases/download/v0.7/w600k_r50.onnx",
        "size_mb": 166,
    },
    {
        "name": "MiniFASNet V1SE (Anti-Spoofing)",
        "filename": "MiniFASNetV1SE_80x80.onnx",
        # Chuyển đổi từ PyTorch weights của Silent-Face-Anti-Spoofing
        "url": "https://github.com/minivision-ai/Silent-Face-Anti-Spoofing/raw/master/resources/anti_spoof_models/2.7_80x80_MiniFASNetV2.pth",
        "size_mb": 2.7,
        "note": "Cần convert PyTorch → ONNX (xem hướng dẫn bên dưới)",
    },
    {
        "name": "MiniFASNet V2 (Anti-Spoofing)",
        "filename": "MiniFASNetV2_80x80.onnx",
        "url": "https://github.com/minivision-ai/Silent-Face-Anti-Spoofing/raw/master/resources/anti_spoof_models/4_0_0_80x80_MiniFASNetV1SE.pth",
        "size_mb": 4,
        "note": "Cần convert PyTorch → ONNX (xem hướng dẫn bên dưới)",
    },
]


def download_file(url: str, dest: str, name: str):
    """Download file với progress bar đơn giản."""
    if os.path.exists(dest):
        print(f"  ✓ {name} đã tồn tại: {dest}")
        return True

    print(f"  Đang tải {name}...")
    print(f"    URL: {url}")
    try:
        def reporthook(count, block_size, total_size):
            if total_size > 0:
                pct = count * block_size * 100 // total_size
                sys.stdout.write(f"\r    Tiến độ: {min(pct, 100)}%")
                sys.stdout.flush()

        urllib.request.urlretrieve(url, dest, reporthook)
        print(f"\n  ✓ Đã tải xong: {dest}")
        return True
    except Exception as e:
        print(f"\n  ✗ Lỗi tải {name}: {e}")
        return False


def convert_pth_to_onnx():
    """Hướng dẫn convert PyTorch MiniFASNet → ONNX."""
    print("""
╔══════════════════════════════════════════════════════════════╗
║  Hướng dẫn convert MiniFASNet PyTorch → ONNX               ║
╠══════════════════════════════════════════════════════════════╣
║                                                              ║
║  1. Clone repo Silent-Face-Anti-Spoofing:                    ║
║     git clone https://github.com/minivision-ai/              ║
║             Silent-Face-Anti-Spoofing.git                    ║
║                                                              ║
║  2. Cài dependencies:                                        ║
║     pip install torch torchvision                            ║
║                                                              ║
║  3. Chạy script convert (xem convert_antispoof.py):          ║
║     python convert_antispoof.py                              ║
║                                                              ║
║  4. Copy file .onnx vào thư mục weights/                     ║
║     - MiniFASNetV1SE_80x80.onnx                              ║
║     - MiniFASNetV2_80x80.onnx                                ║
║                                                              ║
╚══════════════════════════════════════════════════════════════╝
""")


def main():
    print("=" * 60)
    print("Face Service — Download Model Weights")
    print("=" * 60)

    success_all = True
    for model in MODELS:
        dest = os.path.join(WEIGHTS_DIR, model["filename"])
        print(f"\n[{model['name']}]")
        if "note" in model:
            print(f"  ⚠ Lưu ý: {model['note']}")
            continue
        ok = download_file(model["url"], dest, model["name"])
        if not ok:
            success_all = False

    convert_pth_to_onnx()

    print("\n" + "=" * 60)
    if success_all:
        print("✓ Tất cả model đã sẵn sàng!")
    else:
        print("⚠ Một số model chưa tải được. Kiểm tra log ở trên.")
    print("=" * 60)


if __name__ == "__main__":
    main()

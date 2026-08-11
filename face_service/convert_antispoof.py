"""
Convert MiniFASNet PyTorch → ONNX
====================================
Chạy script này để convert model weights PyTorch sang ONNX.
Cần clone repo Silent-Face-Anti-Spoofing trước.
"""

import sys
import os
import torch
import torch.nn as nn

# ── Đường dẫn tới repo Silent-Face-Anti-Spoofing ──────────────────────────────
SILENT_FACE_DIR = os.environ.get(
    "SILENT_FACE_DIR",
    os.path.join(os.path.dirname(__file__), "..", "Silent-Face-Anti-Spoofing")
)

sys.path.insert(0, SILENT_FACE_DIR)

WEIGHTS_DIR = os.path.join(os.path.dirname(__file__), "weights")
os.makedirs(WEIGHTS_DIR, exist_ok=True)

MODELS_TO_CONVERT = [
    {
        "pth_path": os.path.join(SILENT_FACE_DIR, "resources", "anti_spoof_models", "2.7_80x80_MiniFASNetV2.pth"),
        "onnx_path": os.path.join(WEIGHTS_DIR, "MiniFASNetV1SE_80x80.onnx"),
        "model_type": "MiniFASNetV2",
        "input_size": 80,
    },
    {
        "pth_path": os.path.join(SILENT_FACE_DIR, "resources", "anti_spoof_models", "4_0_0_80x80_MiniFASNetV1SE.pth"),
        "onnx_path": os.path.join(WEIGHTS_DIR, "MiniFASNetV2_80x80.onnx"),
        "model_type": "MiniFASNetV1SE",
        "input_size": 80,
    },
]


def convert_model(config: dict):
    pth_path = config["pth_path"]
    onnx_path = config["onnx_path"]
    model_type = config["model_type"]
    size = config["input_size"]

    if not os.path.exists(pth_path):
        print(f"  ✗ Không tìm thấy file PyTorch: {pth_path}")
        print(f"    Clone repo: git clone https://github.com/minivision-ai/Silent-Face-Anti-Spoofing.git")
        return False

    if os.path.exists(onnx_path):
        print(f"  ✓ ONNX đã tồn tại: {onnx_path}")
        return True

    print(f"  Converting {model_type} → ONNX...")

    try:
        # Import từ Silent-Face-Anti-Spoofing
        from src.models.MiniFASNet import MiniFASNetV1SE, MiniFASNetV2

        if model_type == "MiniFASNetV1SE":
            model = MiniFASNetV1SE(conv6_kernel=(5, 5))
        else:
            model = MiniFASNetV2()

        # Load weights
        state = torch.load(pth_path, map_location="cpu")
        if "state_dict" in state:
            state = state["state_dict"]
        # Remove 'module.' prefix nếu có
        state = {k.replace("module.", ""): v for k, v in state.items()}
        model.load_state_dict(state, strict=False)
        model.eval()

        # Tạo dummy input
        dummy = torch.randn(1, 3, size, size)

        # Export ONNX
        torch.onnx.export(
            model,
            dummy,
            onnx_path,
            opset_version=11,
            input_names=["input"],
            output_names=["output"],
            dynamic_axes={"input": {0: "batch_size"}, "output": {0: "batch_size"}},
        )
        print(f"  ✓ Đã convert: {onnx_path}")
        return True

    except ImportError as e:
        print(f"  ✗ Import error: {e}")
        print(f"    Đảm bảo đường dẫn SILENT_FACE_DIR đúng: {SILENT_FACE_DIR}")
        return False
    except Exception as e:
        print(f"  ✗ Lỗi convert: {e}")
        return False


def main():
    print("=" * 60)
    print("Convert MiniFASNet PyTorch → ONNX")
    print("=" * 60)
    print(f"Silent-Face-Anti-Spoofing dir: {SILENT_FACE_DIR}")
    print()

    for cfg in MODELS_TO_CONVERT:
        print(f"[{cfg['model_type']}]")
        convert_model(cfg)
        print()

    print("Hoàn tất!")


if __name__ == "__main__":
    main()

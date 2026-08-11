"""
MiniFASNet Anti-Spoofing
=========================
Dùng Silent-Face-Anti-Spoofing (minivision-ai) model để phát hiện ảnh/video giả.
Model chạy qua ONNX Runtime để không cần PyTorch đầy đủ.

Tham khảo: https://github.com/minivision-ai/Silent-Face-Anti-Spoofing
"""

import os
import numpy as np
import onnxruntime as ort
from PIL import Image
import logging

logger = logging.getLogger(__name__)

# Singleton
_session_28: ort.InferenceSession = None  # MiniFASNet V1 2.7M
_session_4: ort.InferenceSession = None   # MiniFASNet V2 4M

WEIGHTS_DIR = os.environ.get("WEIGHTS_DIR", "weights")


def _get_session_28() -> ort.InferenceSession:
    global _session_28
    if _session_28 is None:
        path = os.path.join(WEIGHTS_DIR, "MiniFASNetV1SE_80x80.onnx")
        if not os.path.exists(path):
            raise FileNotFoundError(
                f"Anti-Spoofing model không tìm thấy tại: {path}\n"
                f"Chạy: python download_weights.py"
            )
        _session_28 = ort.InferenceSession(path, providers=["CPUExecutionProvider"])
        logger.info(f"MiniFASNet V1 loaded from {path}")
    return _session_28


def _get_session_4() -> ort.InferenceSession:
    global _session_4
    if _session_4 is None:
        path = os.path.join(WEIGHTS_DIR, "MiniFASNetV2_80x80.onnx")
        if not os.path.exists(path):
            raise FileNotFoundError(
                f"Anti-Spoofing model không tìm thấy tại: {path}\n"
                f"Chạy: python download_weights.py"
            )
        _session_4 = ort.InferenceSession(path, providers=["CPUExecutionProvider"])
        logger.info(f"MiniFASNet V2 loaded from {path}")
    return _session_4


def _preprocess_face(pil_face: Image.Image, size: int = 80) -> np.ndarray:
    """
    Chuẩn hóa ảnh khuôn mặt theo cách của Silent-Face-Anti-Spoofing.
    """
    img = pil_face.resize((size, size), Image.LANCZOS).convert("RGB")
    arr = np.array(img, dtype=np.float32)
    # Normalize theo ImageNet mean/std
    mean = np.array([0.485, 0.456, 0.406], dtype=np.float32) * 255
    std = np.array([0.229, 0.224, 0.225], dtype=np.float32) * 255
    arr = (arr - mean) / std
    # HWC → CHW → NCHW
    arr = arr.transpose(2, 0, 1)[np.newaxis, ...]
    return arr.astype(np.float32)


def predict_liveness(face_crop_80: Image.Image) -> dict:
    """
    Dự đoán xem khuôn mặt có phải là người thật không.

    Args:
        face_crop_80: PIL Image, kích thước 80x80 (khuôn mặt đã crop từ MTCNN)

    Returns:
        {
            'is_real': bool,
            'score': float,      # Xác suất là người thật (0.0 - 1.0)
            'spoof_type': str,   # 'real' | 'print' | 'replay'
        }
    """
    try:
        inp = _preprocess_face(face_crop_80, size=80)

        # Model 1: MiniFASNet V1SE
        sess1 = _get_session_28()
        out1 = sess1.run(None, {sess1.get_inputs()[0].name: inp})[0]
        prob1 = _softmax(out1[0])  # [fake, real] hoặc [real, fake] tùy config

        # Model 2: MiniFASNet V2
        sess2 = _get_session_4()
        out2 = sess2.run(None, {sess2.get_inputs()[0].name: inp})[0]
        prob2 = _softmax(out2[0])

        # Ensemble: trung bình xác suất 2 model
        # Label index 1 = real face (theo Silent-Face-Anti-Spoofing convention)
        real_score1 = float(prob1[1]) if len(prob1) >= 2 else float(prob1[0])
        real_score2 = float(prob2[1]) if len(prob2) >= 2 else float(prob2[0])
        ensemble_score = (real_score1 + real_score2) / 2.0

        threshold = float(os.environ.get("SPOOF_THRESHOLD", "0.6"))
        is_real = ensemble_score >= threshold

        return {
            "is_real": is_real,
            "score": round(ensemble_score, 4),
            "score_v1": round(real_score1, 4),
            "score_v2": round(real_score2, 4),
            "spoof_type": "real" if is_real else "spoof",
        }

    except Exception as e:
        logger.error(f"Anti-spoof prediction error: {e}", exc_info=True)
        return {
            "is_real": False,
            "score": 0.0,
            "score_v1": 0.0,
            "score_v2": 0.0,
            "spoof_type": "error",
        }


def _softmax(x: np.ndarray) -> np.ndarray:
    e = np.exp(x - np.max(x))
    return e / e.sum()

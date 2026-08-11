"""
MTCNN Face Detector
===================
Dùng facenet-pytorch MTCNN để phát hiện và align khuôn mặt.
Output: face crop 112x112 (chuẩn ArcFace) hoặc 80x80 (Anti-Spoofing).
"""

import numpy as np
from PIL import Image
from facenet_pytorch import MTCNN
import torch
import logging

logger = logging.getLogger(__name__)

# Singleton instance
_mtcnn: MTCNN = None


def get_mtcnn() -> MTCNN:
    """Lazy-load MTCNN model (singleton)."""
    global _mtcnn
    if _mtcnn is None:
        device = torch.device("cuda" if torch.cuda.is_available() else "cpu")
        _mtcnn = MTCNN(
            image_size=112,
            margin=20,
            min_face_size=40,
            thresholds=[0.6, 0.7, 0.7],
            factor=0.709,
            post_process=True,
            keep_all=False,    # Chỉ lấy khuôn mặt lớn nhất
            device=device,
        )
        logger.info(f"MTCNN initialized on {device}")
    return _mtcnn


def detect_and_align(
    pil_image: Image.Image,
    output_size: int = 112,
    margin: int = 20,
) -> dict:
    """
    Phát hiện và align khuôn mặt từ ảnh PIL.

    Returns:
        {
            'detected': bool,
            'face_crop': PIL.Image | None,     # 112x112 aligned face
            'face_crop_80': PIL.Image | None,  # 80x80 cho Anti-Spoofing
            'bbox': list | None,               # [x1, y1, x2, y2]
            'prob': float | None,              # detection confidence
        }
    """
    mtcnn = get_mtcnn()

    try:
        img_rgb = pil_image.convert("RGB")
        img_np = np.array(img_rgb)

        # Detect face + get box + probability
        boxes, probs, landmarks = mtcnn.detect(img_rgb, landmarks=True)

        if boxes is None or len(boxes) == 0 or probs[0] < 0.85:
            return {
                "detected": False,
                "face_crop": None,
                "face_crop_80": None,
                "bbox": None,
                "prob": float(probs[0]) if probs is not None else 0.0,
            }

        # Lấy khuôn mặt có confidence cao nhất
        best_idx = int(np.argmax(probs))
        box = boxes[best_idx]
        prob = float(probs[best_idx])

        x1, y1, x2, y2 = [int(v) for v in box]
        # Thêm margin
        h, w = img_np.shape[:2]
        x1 = max(0, x1 - margin)
        y1 = max(0, y1 - margin)
        x2 = min(w, x2 + margin)
        y2 = min(h, y2 + margin)

        face_img = img_rgb.crop((x1, y1, x2, y2))

        # Resize cho ArcFace (112x112)
        face_crop_112 = face_img.resize((112, 112), Image.LANCZOS)

        # Resize cho Anti-Spoofing (80x80)
        face_crop_80 = face_img.resize((80, 80), Image.LANCZOS)

        return {
            "detected": True,
            "face_crop": face_crop_112,
            "face_crop_80": face_crop_80,
            "bbox": [x1, y1, x2, y2],
            "prob": prob,
        }

    except Exception as e:
        logger.error(f"MTCNN detect error: {e}", exc_info=True)
        return {
            "detected": False,
            "face_crop": None,
            "face_crop_80": None,
            "bbox": None,
            "prob": 0.0,
        }

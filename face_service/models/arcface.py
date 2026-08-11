"""
ArcFace ResNet50 Feature Extractor
=====================================
Dùng InsightFace ArcFace model để tạo embedding 512-chiều.
Cosine similarity để so sánh 2 embedding.

InsightFace models: https://github.com/deepinsight/insightface
"""

import os
import numpy as np
from PIL import Image
import logging
import onnxruntime as ort

logger = logging.getLogger(__name__)

WEIGHTS_DIR = os.environ.get("WEIGHTS_DIR", "weights")

# Singleton
_session: ort.InferenceSession = None


def get_arcface_session() -> ort.InferenceSession:
    """Lazy-load ArcFace ONNX model (singleton)."""
    global _session
    if _session is None:
        # Ưu tiên buffalo_l (ResNet100) → buffalo_s (MobileNet) → w600k_r50
        candidates = [
            os.path.join(WEIGHTS_DIR, "w600k_r50.onnx"),     # ResNet50 ArcFace
            os.path.join(WEIGHTS_DIR, "buffalo_l.onnx"),      # ResNet100
            os.path.join(WEIGHTS_DIR, "arcface_r50.onnx"),    # Generic name
        ]
        model_path = None
        for p in candidates:
            if os.path.exists(p):
                model_path = p
                break

        if model_path is None:
            raise FileNotFoundError(
                f"ArcFace model không tìm thấy trong: {WEIGHTS_DIR}\n"
                "Cần file: w600k_r50.onnx (hoặc buffalo_l.onnx)\n"
                "Chạy: python download_weights.py"
            )

        _session = ort.InferenceSession(
            model_path,
            providers=["CUDAExecutionProvider", "CPUExecutionProvider"]
        )
        logger.info(f"ArcFace loaded from {model_path}")
    return _session


def _preprocess_face_arcface(pil_face: Image.Image) -> np.ndarray:
    """
    Chuẩn bị input cho ArcFace:
    - Resize về 112x112
    - BGR convert (InsightFace convention)
    - Normalize về [-1, 1]
    - NCHW format
    """
    img = pil_face.resize((112, 112), Image.LANCZOS).convert("RGB")
    arr = np.array(img, dtype=np.float32)
    # RGB → BGR (InsightFace)
    arr = arr[:, :, ::-1]
    # Normalize [-1, 1]
    arr = (arr - 127.5) / 127.5
    # HWC → CHW → NCHW
    arr = arr.transpose(2, 0, 1)[np.newaxis, ...]
    return arr.astype(np.float32)


def get_embedding(face_crop_112: Image.Image) -> list:
    """
    Tạo embedding vector 512-chiều từ khuôn mặt.

    Args:
        face_crop_112: PIL Image 112x112 (aligned face từ MTCNN)

    Returns:
        list[float] — 512-dim L2-normalized embedding
    """
    try:
        sess = get_arcface_session()
        inp = _preprocess_face_arcface(face_crop_112)
        input_name = sess.get_inputs()[0].name
        output = sess.run(None, {input_name: inp})[0]  # (1, 512)
        embedding = output[0]  # (512,)

        # L2 normalize
        norm = np.linalg.norm(embedding)
        if norm > 0:
            embedding = embedding / norm

        return embedding.tolist()

    except Exception as e:
        logger.error(f"ArcFace embedding error: {e}", exc_info=True)
        return []


def cosine_similarity(emb1: list, emb2: list) -> float:
    """
    Tính cosine similarity giữa 2 embedding (đã L2-normalized).
    Kết quả trong [-1, 1]; càng gần 1 = càng giống nhau.
    """
    v1 = np.array(emb1, dtype=np.float32)
    v2 = np.array(emb2, dtype=np.float32)
    if len(v1) == 0 or len(v2) == 0:
        return -1.0
    return float(np.dot(v1, v2))


def find_best_match(
    query_embedding: list,
    stored_profiles: list,
    threshold: float = None,
) -> dict:
    """
    Tìm khuôn mặt khớp nhất trong danh sách đã lưu.

    Args:
        query_embedding: Embedding của khuôn mặt cần nhận diện
        stored_profiles: List of {'maND': int, 'embedding': list[float]}
        threshold: Ngưỡng cosine similarity (default từ env RECOGNITION_THRESHOLD)

    Returns:
        {
            'found': bool,
            'maND': int | None,
            'similarity': float,
            'top_matches': list,
        }
    """
    if threshold is None:
        threshold = float(os.environ.get("RECOGNITION_THRESHOLD", "0.4"))

    if not query_embedding or not stored_profiles:
        return {"found": False, "maND": None, "similarity": -1.0, "top_matches": []}

    results = []
    for profile in stored_profiles:
        emb = profile.get("embedding", [])
        if not emb or len(emb) != 512:
            continue
        sim = cosine_similarity(query_embedding, emb)
        results.append({"maND": profile["maND"], "similarity": sim})

    if not results:
        return {"found": False, "maND": None, "similarity": -1.0, "top_matches": []}

    # Sắp xếp theo similarity giảm dần
    results.sort(key=lambda x: x["similarity"], reverse=True)
    best = results[0]

    return {
        "found": best["similarity"] >= threshold,
        "maND": best["maND"] if best["similarity"] >= threshold else None,
        "similarity": round(best["similarity"], 4),
        "top_matches": results[:3],
    }

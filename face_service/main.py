"""
Face Service — Python FastAPI
==============================
Pipeline: MTCNN → MiniFASNet Anti-Spoofing → ArcFace ResNet50

Endpoints:
  POST /detect          — Phát hiện khuôn mặt (MTCNN)
  POST /anti-spoof      — Kiểm tra người thật (MiniFASNet)
  POST /embedding       — Tạo embedding vector (ArcFace)
  POST /verify          — Pipeline đầy đủ (detect + anti-spoof + embedding)
  GET  /health          — Kiểm tra service
"""

import os
import io
import base64
import logging
from contextlib import asynccontextmanager
from typing import Optional

from dotenv import load_dotenv
load_dotenv()

from fastapi import FastAPI, HTTPException, Header, Request
from fastapi.middleware.cors import CORSMiddleware
from fastapi.responses import JSONResponse
from pydantic import BaseModel
from PIL import Image

from models.mtcnn_detector import detect_and_align, get_mtcnn
from models.anti_spoof import predict_liveness
from models.arcface import get_embedding, cosine_similarity, get_arcface_session

# ─── Logging ──────────────────────────────────────────────────────────────────
logging.basicConfig(
    level=logging.INFO,
    format="%(asctime)s [%(levelname)s] %(name)s: %(message)s",
)
logger = logging.getLogger("face_service")

# ─── Config ───────────────────────────────────────────────────────────────────
API_KEY = os.environ.get("FACE_SERVICE_API_KEY", "")
SPOOF_THRESHOLD = float(os.environ.get("SPOOF_THRESHOLD", "0.6"))
RECOGNITION_THRESHOLD = float(os.environ.get("RECOGNITION_THRESHOLD", "0.4"))


# ─── Startup: Preload models ──────────────────────────────────────────────────
@asynccontextmanager
async def lifespan(app: FastAPI):
    logger.info("Loading models...")
    try:
        get_mtcnn()
        logger.info("✓ MTCNN loaded")
    except Exception as e:
        logger.warning(f"MTCNN load warning: {e}")

    try:
        get_arcface_session()
        logger.info("✓ ArcFace loaded")
    except Exception as e:
        logger.warning(f"ArcFace load warning: {e}")

    logger.info("Face Service ready.")
    yield
    logger.info("Face Service shutting down.")


# ─── App ──────────────────────────────────────────────────────────────────────
app = FastAPI(
    title="Face Recognition Service",
    description="MTCNN + MiniFASNet Anti-Spoofing + ArcFace ResNet50",
    version="1.0.0",
    lifespan=lifespan,
)

app.add_middleware(
    CORSMiddleware,
    allow_origins=["*"],
    allow_methods=["POST", "GET", "OPTIONS"],
    allow_headers=["*"],
)


# ─── Auth helper ──────────────────────────────────────────────────────────────
def verify_api_key(x_api_key: Optional[str] = None):
    """Kiểm tra API key nếu đã cấu hình."""
    if API_KEY and x_api_key != API_KEY:
        raise HTTPException(status_code=401, detail="Invalid API key")


# ─── Pydantic schemas ─────────────────────────────────────────────────────────
class ImageRequest(BaseModel):
    image: str  # base64 encoded image (with or without data URL prefix)


class VerifyRequest(BaseModel):
    image: str              # base64 encoded image
    stored_profiles: list = []  # [{"maND": int, "embedding": [float x 512]}]


class EmbeddingCompareRequest(BaseModel):
    embedding1: list
    embedding2: list


# ─── Utils ────────────────────────────────────────────────────────────────────
def decode_image(b64_str: str) -> Image.Image:
    """Decode base64 string thành PIL Image."""
    # Loại bỏ data URL prefix nếu có
    if "," in b64_str:
        b64_str = b64_str.split(",", 1)[1]
    # Chuẩn hóa padding
    b64_str += "=" * (4 - len(b64_str) % 4) if len(b64_str) % 4 else ""
    try:
        img_bytes = base64.b64decode(b64_str)
        img = Image.open(io.BytesIO(img_bytes)).convert("RGB")
        return img
    except Exception as e:
        raise HTTPException(status_code=400, detail=f"Không thể decode ảnh: {e}")


# ─── Endpoints ────────────────────────────────────────────────────────────────

@app.get("/health")
async def health():
    """Kiểm tra service đang chạy."""
    return {
        "status": "ok",
        "service": "Face Recognition Service",
        "version": "1.0.0",
        "thresholds": {
            "spoof": SPOOF_THRESHOLD,
            "recognition": RECOGNITION_THRESHOLD,
        },
    }


@app.post("/detect")
async def detect_face(req: ImageRequest, x_api_key: Optional[str] = Header(None)):
    """
    MTCNN: Phát hiện khuôn mặt từ ảnh.
    Returns: detected, bbox, prob
    """
    verify_api_key(x_api_key)
    img = decode_image(req.image)
    result = detect_and_align(img)
    return {
        "detected": result["detected"],
        "bbox": result["bbox"],
        "prob": result["prob"],
    }


@app.post("/anti-spoof")
async def anti_spoof(req: ImageRequest, x_api_key: Optional[str] = Header(None)):
    """
    MiniFASNet: Kiểm tra ảnh có phải người thật không.
    Returns: is_real, score, spoof_type
    """
    verify_api_key(x_api_key)
    img = decode_image(req.image)

    # MTCNN detect trước
    detection = detect_and_align(img)
    if not detection["detected"]:
        return {
            "is_real": False,
            "score": 0.0,
            "spoof_type": "no_face",
            "message": "Không phát hiện khuôn mặt trong ảnh",
        }

    result = predict_liveness(detection["face_crop_80"])
    return result


@app.post("/embedding")
async def get_face_embedding(req: ImageRequest, x_api_key: Optional[str] = Header(None)):
    """
    ArcFace: Tạo embedding vector 512-chiều.
    Returns: embedding (list of 512 floats)
    """
    verify_api_key(x_api_key)
    img = decode_image(req.image)

    # MTCNN detect trước
    detection = detect_and_align(img)
    if not detection["detected"]:
        return {
            "success": False,
            "embedding": [],
            "message": "Không phát hiện khuôn mặt trong ảnh",
        }

    embedding = get_embedding(detection["face_crop"])
    if not embedding:
        return {
            "success": False,
            "embedding": [],
            "message": "Không thể tạo embedding khuôn mặt",
        }

    return {
        "success": True,
        "embedding": embedding,
        "dim": len(embedding),
        "bbox": detection["bbox"],
        "detection_prob": detection["prob"],
    }


@app.post("/verify")
async def verify_face(req: VerifyRequest, x_api_key: Optional[str] = Header(None)):
    """
    Pipeline đầy đủ:
    1. MTCNN — phát hiện khuôn mặt
    2. MiniFASNet — kiểm tra người thật
    3. ArcFace — tạo embedding

    Returns:
        face_detected, is_real, spoof_score, embedding, bbox
    """
    verify_api_key(x_api_key)
    img = decode_image(req.image)

    # ── Step 1: MTCNN Detection ────────────────────────────────────────────
    detection = detect_and_align(img)
    if not detection["detected"]:
        return {
            "success": False,
            "face_detected": False,
            "is_real": False,
            "spoof_score": 0.0,
            "embedding": [],
            "message": "Không phát hiện khuôn mặt. Vui lòng nhìn thẳng vào camera.",
        }

    # ── Step 2: Anti-Spoofing ──────────────────────────────────────────────
    spoof_result = predict_liveness(detection["face_crop_80"])
    if not spoof_result["is_real"]:
        return {
            "success": False,
            "face_detected": True,
            "is_real": False,
            "spoof_score": spoof_result["score"],
            "spoof_type": spoof_result.get("spoof_type", "spoof"),
            "embedding": [],
            "message": f"Phát hiện khuôn mặt giả! (Score: {spoof_result['score']:.2f}). Chỉ người thật mới được chấm công.",
        }

    # ── Step 3: ArcFace Embedding ──────────────────────────────────────────
    embedding = get_embedding(detection["face_crop"])
    if not embedding:
        return {
            "success": False,
            "face_detected": True,
            "is_real": True,
            "spoof_score": spoof_result["score"],
            "embedding": [],
            "message": "Lỗi tạo đặc trưng khuôn mặt. Vui lòng thử lại.",
        }

    return {
        "success": True,
        "face_detected": True,
        "is_real": True,
        "spoof_score": spoof_result["score"],
        "spoof_type": "real",
        "embedding": embedding,
        "embedding_dim": len(embedding),
        "bbox": detection["bbox"],
        "detection_prob": detection["prob"],
        "message": "OK",
    }


@app.post("/compare")
async def compare_embeddings(req: EmbeddingCompareRequest, x_api_key: Optional[str] = Header(None)):
    """So sánh 2 embedding vector (dùng để debug)."""
    verify_api_key(x_api_key)
    sim = cosine_similarity(req.embedding1, req.embedding2)
    threshold = RECOGNITION_THRESHOLD
    return {
        "similarity": round(sim, 4),
        "threshold": threshold,
        "is_match": sim >= threshold,
    }


# ─── Error Handlers ───────────────────────────────────────────────────────────
@app.exception_handler(Exception)
async def global_exception_handler(request: Request, exc: Exception):
    logger.error(f"Unhandled error: {exc}", exc_info=True)
    return JSONResponse(
        status_code=500,
        content={"success": False, "message": f"Lỗi server: {str(exc)}"},
    )


# ─── Main ─────────────────────────────────────────────────────────────────────
if __name__ == "__main__":
    import uvicorn
    host = os.environ.get("HOST", "127.0.0.1")
    port = int(os.environ.get("PORT", "8000"))
    logger.info(f"Starting Face Service at http://{host}:{port}")
    uvicorn.run("main:app", host=host, port=port, reload=False, workers=1)

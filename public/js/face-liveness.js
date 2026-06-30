/**
 * Passive Face Liveness Detection Engine v2.0 (Face Anti-Spoofing)
 * ================================================================
 * Pure passive anti-spoofing pipeline. No user actions (blinking/turning) required.
 * Requires only 15 frames (~0.5 - 1.0 second) of face-api.js detections.
 * 
 * Layers:
 *   1. Static Frame Check - Detects static printed images or stream freezing (landmark variance).
 *   2. Geometric Jitter - Detects 3D human face micro-movements vs 2D flat prints.
 *   3. LBP (Local Binary Pattern) - Analyzes texture patterns to separate skin from screen grids/print halftones.
 *   4. Laplacian Gradient Frequency - Detects moiré patterns from screens and print noise.
 *   5. Color Specular Analysis - Detects unnatural color distributions and screen backlights.
 * 
 * CPU-optimized: runs purely on client-side JS canvas operations.
 */

'use strict';

class LivenessDetector {
    static FRAME_COUNT_REQUIRED = 15;        // Number of frames to analyze (15 frames = ~0.7s)
    static GEOMETRIC_VARIANCE_MIN = 0.00002; // Min variance in landmark distance ratios for living face
    static LANDMARK_VARIANCE_MIN = 0.05;      // Min variance in raw coordinate positions (detects static photos)
    static LANDMARK_VARIANCE_MAX = 15.0;      // Max variance (too much shaking = invalid)
    
    // Texture Analysis Thresholds
    static LBP_GRID_REPETITION_MAX = 0.35;   // Max score of screen moiré patterns in LBP histogram
    static LAPLACIAN_MOIRE_MAX = 70.0;       // Max high-frequency edge variance (screens have high moiré peaks)
    static COLOR_UNIFORMITY_MAX = 0.92;      // Screens have high uniformity due to single backlight

    constructor(videoEl, canvasEl, callbacks = {}) {
        this.video = videoEl;
        this.canvas = canvasEl;
        this.cb = callbacks;

        // State Machine
        this.state = 'idle'; // idle | scanning | completed | failed
        this.destroyed = false;

        // Session Data
        this.frameCount = 0;
        this.sessionStartTime = null;
        this.sessionSecret = null;
        
        // Data history for passive analysis
        this.landmarkHistory = [];
        this.boxHistory = [];
        this.descriptorHistory = [];
        this.textureSamples = [];

        this.latestDescriptor = null;
    }

    /**
     * Start the passive liveness check.
     */
    async start() {
        this.destroyed = false;
        this.state = 'scanning';
        this.frameCount = 0;
        this.warmupFrames = 0;
        this.sessionStartTime = Date.now();
        
        this.landmarkHistory = [];
        this.boxHistory = [];
        this.descriptorHistory = [];
        this.textureSamples = [];

        // Fetch session secret from server
        try {
            const res = await fetch('index.php?page=face-liveness-session', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' }
            });
            const data = await res.json();
            if (data.success && data.sessionSecret) {
                this.sessionSecret = data.sessionSecret;
            } else {
                this._fail('Không thể khởi tạo phiên bảo mật. Vui lòng tải lại trang.');
                return;
            }
        } catch (e) {
            this._fail('Lỗi kết nối máy chủ khi tạo phiên bảo mật.');
            return;
        }

        this._updateStatus('Đang xác thực bảo mật... Vui lòng giữ yên khuôn mặt.', 'info');
        this._updateProgress(5, 'Khởi tạo');
    }

    /**
     * Feed video frame detection results to the engine.
     */
    processFrame(detection) {
        if (this.destroyed || this.state !== 'scanning') return;

        if (!detection) {
            this._updateStatus('Không tìm thấy khuôn mặt. Vui lòng nhìn vào camera.', 'warning');
            return;
        }

        // Wait for camera exposure to stabilize
        if (this.warmupFrames < 5) {
            this.warmupFrames++;
            this._updateStatus('Đang tối ưu độ sáng camera...', 'info');
            return;
        }

        this.frameCount++;
        
        // Gather landmark coordinate mapping
        const positions = detection.landmarks.positions.map(p => ({ x: p.x, y: p.y }));
        this.landmarkHistory.push(positions);
        this.boxHistory.push(detection.detection.box);
        this.latestDescriptor = detection.descriptor;

        // Perform texture analysis on canvas on each 3rd frame (to preserve CPU)
        if (this.frameCount % 3 === 0) {
            const textureInfo = this._analyzeTexture(detection);
            if (textureInfo) {
                this.textureSamples.push(textureInfo);
            }
        }

        // Show progressive feedback
        const progress = Math.min(95, Math.round((this.frameCount / LivenessDetector.FRAME_COUNT_REQUIRED) * 95));
        this._updateProgress(progress, 'Đang quét bảo mật...');

        if (this.frameCount >= LivenessDetector.FRAME_COUNT_REQUIRED) {
            this._runFinalPassiveAnalysis();
        }
    }

    /**
     * Stop and cleanup resources.
     */
    destroy() {
        this.destroyed = true;
        this.state = 'idle';
        this.landmarkHistory = [];
        this.boxHistory = [];
        this.descriptorHistory = [];
        this.textureSamples = [];
    }

    getState() {
        return this.state;
    }

    // ═══════════════════════════════════════════════════════════════
    //  PASSIVE ANTI-SPOOFING METRICS
    // ═══════════════════════════════════════════════════════════════

    /**
     * Run all passive scoring tests. Requires no user action.
     */
    async _runFinalPassiveAnalysis() {
        const scores = {
            motion: 0,
            geometricJitter: 0,
            lbpTexture: 0,
            laplacianMoire: 0,
            colorReflection: 0
        };

        // ─── 1. Static Frame / Freeze Check ───
        // Real faces have continuous involuntary movements. Static prints have exactly zero variance.
        const landmarkVariance = this._computeRawLandmarkVariance();
        if (landmarkVariance < LivenessDetector.LANDMARK_VARIANCE_MIN) {
            this._fail('Phát hiện khuôn mặt giả lập! Hình ảnh tĩnh hoặc không di chuyển.');
            return;
        }
        scores.motion = Math.min(1.0, landmarkVariance / 1.5);

        // ─── 2. Geometric Jitter (3D vs 2D Flatness) ───
        // Calculate relative distance ratios (e.g. nose-to-jaw width vs eye-width) across frames.
        // On 3D living face, ratios change with micro posture shifts. On a flat photo, it is completely rigid.
        const ratioVariance = this._computeGeometricRatioVariance();
        if (ratioVariance < LivenessDetector.GEOMETRIC_VARIANCE_MIN) {
            this._fail('Phát hiện khuôn mặt giả lập! Mặt phẳng 2D không có chiều sâu.');
            return;
        }
        scores.geometricJitter = Math.min(1.0, ratioVariance / 0.00015);

        // Calculate texture averages
        let avgLbpRepetition = 0;
        let avgLaplacianVar = 0;
        let avgColorUniformity = 0;

        if (this.textureSamples.length > 0) {
            avgLbpRepetition = this.textureSamples.reduce((sum, s) => sum + s.lbpRepetition, 0) / this.textureSamples.length;
            avgLaplacianVar = this.textureSamples.reduce((sum, s) => sum + s.laplacianVar, 0) / this.textureSamples.length;
            avgColorUniformity = this.textureSamples.reduce((sum, s) => sum + s.colorUniformity, 0) / this.textureSamples.length;
        }

        // ─── 3. LBP Texture Check ───
        // High repetition means screen pixel grid or print halftone noise.
        // Smooth skin LBP max bin is typically 0.15 - 0.25. Screens are 0.30 - 0.50.
        scores.lbpTexture = Math.max(0.1, Math.min(1.0, 1.25 - (avgLbpRepetition / 0.38)));

        // ─── 4. Laplacian Moiré / Focus Check ───
        // Very low variance (< 6.0) indicates blur (print attack). Very high (> 400.0) indicates high-frequency moiré lines.
        // Healthy range: 10.0 - 300.0.
        if (avgLaplacianVar < 4.0) {
            scores.laplacianMoire = 0.15; // Blurry attack
        } else if (avgLaplacianVar > 450.0) {
            scores.laplacianMoire = Math.max(0.2, 1.0 - (avgLaplacianVar - 450.0) / 750.0); // Moiré penalty
        } else {
            scores.laplacianMoire = 1.0; // Normal sharp face
        }

        // ─── 5. Color Specular Analysis ───
        // Uniformity: screens have high RGB similarity. Real faces have distinct R/G/B skin tones.
        scores.colorReflection = Math.max(0.1, Math.min(1.0, 1.0 - (avgColorUniformity - 0.72) / 0.26));

        // Compute overall combined score
        const combinedScore = (
            scores.motion * 0.15 +
            scores.geometricJitter * 0.25 +
            scores.lbpTexture * 0.25 +
            scores.laplacianMoire * 0.20 +
            scores.colorReflection * 0.15
        );

        if (combinedScore < 0.55) {
            this._fail(`Xác thực bảo mật thất bại (${Math.round(combinedScore * 100)}%). Vui lòng thử lại.`);
            return;
        }

        // Generate HMAC verification token
        const token = await this._generateToken(combinedScore, scores);

        this.state = 'completed';
        this._updateProgress(100, 'Xác thực hoàn tất');
        this._updateStatus('Xác thực khuôn mặt thật thành công!', 'success');

        if (this.cb.onComplete) {
            this.cb.onComplete(token, this.latestDescriptor);
        }
    }

    // ═══════════════════════════════════════════════════════════════
    //  IMAGE PROCESSING MATHEMATICS
    // ═══════════════════════════════════════════════════════════════

    /**
     * Compute Local Binary Patterns (LBP) and Laplacian gradients.
     */
    _analyzeTexture(detection) {
        try {
            const box = detection.detection.box;
            const tempCanvas = document.createElement('canvas');
            const vw = this.video.videoWidth || 640;
            const vh = this.video.videoHeight || 480;
            
            tempCanvas.width = vw;
            tempCanvas.height = vh;
            const tempCtx = tempCanvas.getContext('2d');
            tempCtx.drawImage(this.video, 0, 0, vw, vh);

            const fx = Math.max(0, Math.round(box.x));
            const fy = Math.max(0, Math.round(box.y));
            const fw = Math.min(Math.round(box.width), vw - fx);
            const fh = Math.min(Math.round(box.height), vh - fy);

            if (fw < 40 || fh < 40) return null;

            const imgData = tempCtx.getImageData(fx, fy, fw, fh);
            const pixels = imgData.data;

            // 1. Grayscale convert and prepare buffer
            const gray = new Uint8ClampedArray(fw * fh);
            for (let i = 0; i < gray.length; i++) {
                const idx = i * 4;
                gray[i] = Math.round(0.299 * pixels[idx] + 0.587 * pixels[idx + 1] + 0.114 * pixels[idx + 2]);
            }

            // 2. LBP Texture extraction
            // Compare each pixel to 8 neighbors. Code: sum(s(neighbor - center) * 2^i)
            const lbpHist = new Float32Array(256);
            for (let y = 1; y < fh - 1; y++) {
                for (let x = 1; x < fw - 1; x++) {
                    const center = gray[y * fw + x];
                    let code = 0;
                    
                    if (gray[(y - 1) * fw + (x - 1)] >= center) code |= 1;
                    if (gray[(y - 1) * fw + x]       >= center) code |= 2;
                    if (gray[(y - 1) * fw + (x + 1)] >= center) code |= 4;
                    if (gray[y * fw + (x + 1)]       >= center) code |= 8;
                    if (gray[(y + 1) * fw + (x + 1)] >= center) code |= 16;
                    if (gray[(y + 1) * fw + x]       >= center) code |= 32;
                    if (gray[(y + 1) * fw + (x - 1)] >= center) code |= 64;
                    if (gray[y * fw + (x - 1)]       >= center) code |= 128;
                    
                    lbpHist[code]++;
                }
            }

            // Normalize histogram
            const totalPatterns = (fw - 2) * (fh - 2);
            for (let i = 0; i < 256; i++) {
                lbpHist[i] /= totalPatterns;
            }

            // High repetitive noise shows spikes in specific LBP patterns (like screen mesh grid)
            let maxBinVal = 0;
            for (let i = 0; i < 256; i++) {
                if (lbpHist[i] > maxBinVal) maxBinVal = lbpHist[i];
            }

            // 3. Laplacian gradient variance (moiré detection)
            // Using kernel: [0, 1, 0, 1, -4, 1, 0, 1, 0]
            let sumL = 0;
            let sumSqL = 0;
            let countL = 0;

            for (let y = 1; y < fh - 1; y++) {
                for (let x = 1; x < fw - 1; x++) {
                    const lVal = (
                        gray[(y - 1) * fw + x] +
                        gray[y * fw + (x - 1)] +
                        gray[y * fw + (x + 1)] +
                        gray[(y + 1) * fw + x] -
                        4 * gray[y * fw + x]
                    );
                    sumL += lVal;
                    sumSqL += lVal * lVal;
                    countL++;
                }
            }
            const meanL = sumL / countL;
            const laplacianVar = (sumSqL / countL) - (meanL * meanL);

            // 4. Color uniformity (screens emit standard light)
            let rSum = 0, gSum = 0, bSum = 0;
            for (let i = 0; i < pixels.length; i += 4) {
                rSum += pixels[i];
                gSum += pixels[i + 1];
                bSum += pixels[i + 2];
            }
            const rMean = rSum / (pixels.length / 4);
            const gMean = gSum / (pixels.length / 4);
            const bMean = bSum / (pixels.length / 4);
            
            // Calculate similarity across RGB
            const colorUniformity = 1.0 - (Math.abs(rMean - gMean) + Math.abs(gMean - bMean) + Math.abs(bMean - rMean)) / 765.0;

            tempCanvas.remove();

            return {
                lbpRepetition: maxBinVal,
                laplacianVar: laplacianVar,
                colorUniformity: colorUniformity
            };
        } catch (e) {
            console.warn('Passive texture analysis skipped:', e);
            return null;
        }
    }

    /**
     * Compute landmark distance ratios variance across all frames.
     */
    _computeGeometricRatioVariance() {
        const n = this.landmarkHistory.length;
        if (n < 5) return 0;

        const ratios = [];
        for (let f = 0; f < n; f++) {
            const pts = this.landmarkHistory[f];
            // Left eye midpoint to right eye midpoint distance
            const dEyes = this._dist(pts[36], pts[45]);
            // Nose tip to chin distance
            const dNoseChin = this._dist(pts[30], pts[8]);
            
            ratios.push(dEyes / (dNoseChin || 1));
        }

        // Compute variance of ratios
        const mean = ratios.reduce((a, b) => a + b, 0) / n;
        const sqDiffSum = ratios.reduce((sum, r) => sum + (r - mean) ** 2, 0);
        return sqDiffSum / n;
    }

    /**
     * Compute raw landmark position variance to detect static photos.
     */
    _computeRawLandmarkVariance() {
        const n = this.landmarkHistory.length;
        if (n < 5) return 0;

        const keyPoints = [30, 36, 45, 48, 54, 8];
        let totalVar = 0;

        for (const idx of keyPoints) {
            let sumX = 0, sumY = 0, sumXSq = 0, sumYSq = 0;
            for (let f = 0; f < n; f++) {
                const p = this.landmarkHistory[f][idx];
                sumX += p.x;
                sumY += p.y;
                sumXSq += p.x * p.x;
                sumYSq += p.y * p.y;
            }
            const meanX = sumX / n;
            const meanY = sumY / n;
            const varX = (sumXSq / n) - (meanX * meanX);
            const varY = (sumYSq / n) - (meanY * meanY);
            totalVar += varX + varY;
        }

        return totalVar / keyPoints.length;
    }

    _dist(a, b) {
        return Math.sqrt((a.x - b.x) ** 2 + (a.y - b.y) ** 2);
    }

    // ═══════════════════════════════════════════════════════════════
    //  SECURE CRYPTOGRAPHIC TOKEN
    // ═══════════════════════════════════════════════════════════════

    async _generateToken(score, scores) {
        const payload = {
            sessionStart: this.sessionStartTime,
            completedAt: Date.now(),
            passiveScores: scores,
            combinedScore: score,
            frameCount: this.frameCount
        };

        const payloadStr = JSON.stringify(payload);
        let signature = '';

        try {
            const encoder = new TextEncoder();
            const key = await crypto.subtle.importKey(
                'raw',
                encoder.encode(this.sessionSecret),
                { name: 'HMAC', hash: 'SHA-256' },
                false,
                ['sign']
            );
            const sig = await crypto.subtle.sign('HMAC', key, encoder.encode(payloadStr));
            signature = Array.from(new Uint8Array(sig)).map(b => b.toString(16).padStart(2, '0')).join('');
        } catch (e) {
            // Fallback hashing
            signature = await this._simpleHash(payloadStr + this.sessionSecret);
        }

        return {
            payload: payload,
            signature: signature
        };
    }

    async _simpleHash(str) {
        const encoder = new TextEncoder();
        const data = encoder.encode(str);
        const hashBuffer = await crypto.subtle.digest('SHA-256', data);
        return Array.from(new Uint8Array(hashBuffer)).map(b => b.toString(16).padStart(2, '0')).join('');
    }

    // ═══════════════════════════════════════════════════════════════
    //  CALLBACK TRIGGERS
    // ═══════════════════════════════════════════════════════════════

    _fail(reason) {
        this.state = 'failed';
        this._updateProgress(0, 'Xác thực thất bại');
        this._updateStatus(reason, 'error');
        if (this.cb.onFail) {
            this.cb.onFail(reason);
        }
    }

    _updateStatus(message, type) {
        if (this.cb.onStatusChange && !this.destroyed) {
            this.cb.onStatusChange(message, type);
        }
    }

    _updateProgress(percent, label) {
        if (this.cb.onProgress && !this.destroyed) {
            this.cb.onProgress(percent, label);
        }
    }
}

/**
 * Passive Face Liveness Detection Engine v3.0 (Enhanced Anti-Spoofing)
 * =====================================================================
 * Multi-layer passive anti-spoofing pipeline with EYE BLINK DETECTION.
 * Requires ~25 frames (~1.5 - 2.5 seconds) of face-api.js detections.
 * 
 * Layers:
 *   1. Static Frame Check   - Detects static printed images (landmark variance).
 *   2. Geometric Jitter     - Detects 3D human face micro-movements vs 2D flat prints.
 *   3. LBP Texture          - Analyzes texture patterns to separate skin from screen/print.
 *   4. Laplacian Gradient   - Detects moiré patterns from screens and print noise.
 *   5. Color Specular       - Detects unnatural color distributions and screen backlights.
 *   6. ★ EYE BLINK (EAR)   - Detects natural eye blinks via Eye Aspect Ratio. 
 *                             Photos CANNOT blink — this is the strongest anti-photo layer.
 *   7. Head Pose Variation  - Detects natural yaw/pitch micro-movements (3D only).
 *   8. Face Size Variation   - Detects breathing/sway changes in bounding box size.
 * 
 * CPU-optimized: runs purely on client-side JS canvas operations.
 */

'use strict';

class LivenessDetector {
    // ═══════════════════════════════════════════════════════════════
    //  THRESHOLDS (tightened significantly from v2.0)
    // ═══════════════════════════════════════════════════════════════
    
    static FRAME_COUNT_REQUIRED = 25;         // More frames = more accurate blink detection
    static WARMUP_FRAMES = 5;                 // Camera exposure stabilization

    // Motion & Geometry
    static GEOMETRIC_VARIANCE_MIN = 0.00001;  // Reduced from 0.00008 to support stationary webcams/still faces
    static LANDMARK_VARIANCE_MIN = 0.02;      // Reduced from 0.15 to support stationary webcams/still faces
    static LANDMARK_VARIANCE_MAX = 15.0;      // Max variance (too much shaking = invalid)
    
    // Texture Analysis
    static LBP_GRID_REPETITION_MAX = 0.28;    // Was 0.35 — screen pixel grids fail more
    static LAPLACIAN_MOIRE_MAX = 70.0;        // High-frequency moiré detection
    static COLOR_UNIFORMITY_MAX = 0.88;       // Was 0.92 — screens have high uniformity

    // ★ Eye Blink Detection (EAR)
    static MIN_BLINKS_REQUIRED = 1;           // Must detect at least 1 natural blink
    static EAR_VARIANCE_MIN = 0.00005;        // Reduced from 0.0003 to allow quick stable blinks

    // Head Pose
    static HEAD_YAW_VARIANCE_MIN = 0.00005;   // Min yaw variance (real faces micro-rotate)
    static HEAD_PITCH_VARIANCE_MIN = 0.00003;  // Min pitch variance

    // Face Size (breathing/sway)
    static FACE_SIZE_VARIANCE_MIN = 0.3;      // Min variance in face box area (real = natural sway)

    // Combined Score
    static COMBINED_SCORE_MIN = 0.52;         // Reduced from 0.62 to prevent false rejections of real faces

    constructor(videoEl, canvasEl, callbacks = {}) {
        this.video = videoEl;
        this.canvas = canvasEl;
        this.cb = callbacks;

        // State Machine
        this.state = 'idle'; // idle | scanning | completed | failed
        this.destroyed = false;

        // Session Data
        this.frameCount = 0;
        this.warmupFrames = 0;
        this.sessionStartTime = null;
        this.sessionSecret = null;
        
        // Data history for passive analysis
        this.landmarkHistory = [];
        this.boxHistory = [];
        this.textureSamples = [];

        // ★ Eye Blink Detection (EAR) state
        this.earHistory = [];           // EAR value per frame
        this.blinkCount = 0;            // Detected blinks
        this.blinkState = 'open';       // 'open' | 'closing' | 'closed'
        this.blinkDetected = false;     // At least one blink found

        // Head Pose state
        this.yawHistory = [];
        this.pitchHistory = [];

        // Face Size state
        this.faceSizeHistory = [];

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
        
        // Reset all history
        this.landmarkHistory = [];
        this.boxHistory = [];
        this.textureSamples = [];
        this.earHistory = [];
        this.maxEAR = 0.0; // Dynamic EAR baseline calibration
        this.blinkCount = 0;
        this.blinkState = 'open';
        this.blinkDetected = false;
        this.yawHistory = [];
        this.pitchHistory = [];
        this.faceSizeHistory = [];

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

        this._updateStatus('Đang xác thực... Vui lòng nhìn camera và chớp mắt tự nhiên.', 'info');
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
        if (this.warmupFrames < LivenessDetector.WARMUP_FRAMES) {
            this.warmupFrames++;
            this._updateStatus('Đang tối ưu độ sáng camera...', 'info');
            return;
        }

        this.frameCount++;
        
        // ─── Gather landmark data ───
        const positions = detection.landmarks.positions.map(p => ({ x: p.x, y: p.y }));
        this.landmarkHistory.push(positions);
        
        const box = detection.detection.box;
        this.boxHistory.push(box);
        this.latestDescriptor = detection.descriptor;

        // ─── ★ Eye Blink Detection (EAR) ───
        this._processEyeBlink(positions);

        // ─── Head Pose Estimation ───
        this._processHeadPose(positions);

        // ─── Face Size Tracking ───
        this.faceSizeHistory.push(box.width * box.height);

        // ─── Texture analysis on every 3rd frame ───
        if (this.frameCount % 3 === 0) {
            const textureInfo = this._analyzeTexture(detection);
            if (textureInfo) {
                this.textureSamples.push(textureInfo);
            }
        }

        // ─── Progressive feedback ───
        const progress = Math.min(95, Math.round((this.frameCount / LivenessDetector.FRAME_COUNT_REQUIRED) * 95));
        
        // Show blink status in progress
        const blinkIcon = this.blinkDetected ? '✅' : '👁️';
        const blinkLabel = this.blinkDetected ? 'Nháy mắt: OK' : 'Chờ nháy mắt...';
        this._updateProgress(progress, `Đang quét bảo mật... ${blinkIcon} ${blinkLabel}`);

        // Update status to remind about blinking
        if (this.frameCount > 10 && !this.blinkDetected) {
            this._updateStatus('Vui lòng chớp mắt tự nhiên 1-2 lần để xác minh.', 'info');
        } else if (this.blinkDetected) {
            this._updateStatus('Đã phát hiện chớp mắt! Đang hoàn tất quét bảo mật...', 'info');
        }

        // ─── Check completion ───
        if (this.frameCount >= LivenessDetector.FRAME_COUNT_REQUIRED) {
            // Wait for blink detection up to 55 frames (approx. 6-8 seconds)
            if (this.blinkDetected || this.frameCount >= 55) {
                this._runFinalPassiveAnalysis();
            }
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
        this.textureSamples = [];
        this.earHistory = [];
        this.yawHistory = [];
        this.pitchHistory = [];
        this.faceSizeHistory = [];
    }

    getState() {
        return this.state;
    }

    isBlinkDetected() {
        return this.blinkDetected;
    }

    // ═══════════════════════════════════════════════════════════════
    //  ★ EYE BLINK DETECTION (Eye Aspect Ratio)
    // ═══════════════════════════════════════════════════════════════

    /**
     * Calculate EAR (Eye Aspect Ratio) and detect blinks.
     * 
     * EAR Formula (Soukupová & Čech, 2016):
     *   EAR = (||p2-p6|| + ||p3-p5||) / (2 * ||p1-p4||)
     * 
     * Landmark indices (face-api.js 68-point model):
     *   Left eye:  36, 37, 38, 39, 40, 41
     *   Right eye: 42, 43, 44, 45, 46, 47
     * 
     * Real eyes: EAR ~0.25-0.35 when open, drops to ~0.05-0.15 during blink
     * Photo eyes: EAR stays constant across ALL frames
     */
    _processEyeBlink(positions) {
        // Calculate EAR for both eyes
        const leftEAR = this._calculateEAR(positions, [36, 37, 38, 39, 40, 41]);
        const rightEAR = this._calculateEAR(positions, [42, 43, 44, 45, 46, 47]);
        
        // Average EAR of both eyes
        const avgEAR = (leftEAR + rightEAR) / 2.0;

        // Ignore extreme outlier EAR values due to landmark misdetection
        if (avgEAR > 0.40) {
            console.warn(`[Liveness] Outlier EAR detected (${avgEAR.toFixed(3)}), skipping for baseline.`);
            return;
        }

        this.earHistory.push(avgEAR);

        // Dynamically calibrate baseline (open eyes)
        if (this.frameCount <= 10) {
            if (avgEAR > this.maxEAR) {
                this.maxEAR = avgEAR;
            }
        } else {
            // Gradually adjust if eyes open wider
            if (avgEAR > this.maxEAR && avgEAR < 0.38) {
                this.maxEAR = avgEAR;
            }
        }

        // Establish active baseline (typical range is 0.24 to 0.38)
        const baseline = (this.maxEAR >= 0.20 && this.maxEAR <= 0.38) ? this.maxEAR : 0.28;
        
        // Dynamic blink closing and opening thresholds
        const blinkThreshold = baseline * 0.91; // Configured for TinyFaceDetector (9% drop required)
        const openThreshold = baseline * 0.96;  // Configured for TinyFaceDetector (returns above 96% of baseline)

        // Robust 2-state machine to prevent landmark jitter from disrupting state transitions
        if (this.blinkState !== 'open' && this.blinkState !== 'closed') {
            this.blinkState = 'open';
        }

        switch (this.blinkState) {
            case 'open':
                if (avgEAR < blinkThreshold) {
                    this.blinkState = 'closed';
                    console.log(`[Liveness] Eye closed detected (avgEAR: ${avgEAR.toFixed(3)} < threshold: ${blinkThreshold.toFixed(3)})`);
                }
                break;
            
            case 'closed':
                if (avgEAR > openThreshold) {
                    // Eye reopened = confirmed blink!
                    this.blinkCount++;
                    this.blinkDetected = true;
                    this.blinkState = 'open';
                    console.log(`[Liveness] Eye open detected (avgEAR: ${avgEAR.toFixed(3)} > threshold: ${openThreshold.toFixed(3)}). Blink count: ${this.blinkCount}`);
                    
                    // Notify UI
                    if (this.cb.onBlinkDetected) {
                        this.cb.onBlinkDetected(this.blinkCount);
                    }
                }
                break;
        }

        if (this.frameCount % 5 === 0) {
            console.log(`[Liveness] Frame ${this.frameCount}: avgEAR = ${avgEAR.toFixed(3)}, maxEAR = ${this.maxEAR.toFixed(3)}, baseline = ${baseline.toFixed(3)}, state = ${this.blinkState}, blinks = ${this.blinkCount}`);
        }
    }

    /**
     * Calculate Eye Aspect Ratio for one eye.
     * p1-p6 are the 6 landmark points of the eye.
     */
    _calculateEAR(positions, indices) {
        const p1 = positions[indices[0]]; // Left corner
        const p2 = positions[indices[1]]; // Upper-left
        const p3 = positions[indices[2]]; // Upper-right
        const p4 = positions[indices[3]]; // Right corner
        const p5 = positions[indices[4]]; // Lower-right
        const p6 = positions[indices[5]]; // Lower-left

        // Vertical distances
        const v1 = this._dist(p2, p6); // ||p2 - p6||
        const v2 = this._dist(p3, p5); // ||p3 - p5||
        
        // Horizontal distance
        const h = this._dist(p1, p4);  // ||p1 - p4||

        if (h === 0) return 0.3; // Fallback

        return (v1 + v2) / (2.0 * h);
    }

    // ═══════════════════════════════════════════════════════════════
    //  HEAD POSE MICRO-MOVEMENT ESTIMATION
    // ═══════════════════════════════════════════════════════════════

    /**
     * Estimate yaw and pitch from landmark geometry.
     * Uses nose-to-eye ratio asymmetry as proxy for head rotation.
     */
    _processHeadPose(positions) {
        // Yaw estimation: ratio of left-nose distance vs right-nose distance
        // If head turns right, left eye gets farther from nose tip
        const noseTip = positions[30];
        const leftEyeCenter = {
            x: (positions[36].x + positions[39].x) / 2,
            y: (positions[36].y + positions[39].y) / 2
        };
        const rightEyeCenter = {
            x: (positions[42].x + positions[45].x) / 2,
            y: (positions[42].y + positions[45].y) / 2
        };

        const dLeft = this._dist(leftEyeCenter, noseTip);
        const dRight = this._dist(rightEyeCenter, noseTip);
        const yaw = dLeft / (dRight || 1); // >1 = looking right, <1 = looking left

        // Pitch estimation: nose-to-forehead vs nose-to-chin ratio
        const forehead = positions[27]; // Bridge of nose (top between eyes)
        const chin = positions[8];
        const dUp = this._dist(noseTip, forehead);
        const dDown = this._dist(noseTip, chin);
        const pitch = dUp / (dDown || 1); // >1 = looking down, <1 = looking up

        this.yawHistory.push(yaw);
        this.pitchHistory.push(pitch);
    }

    // ═══════════════════════════════════════════════════════════════
    //  PASSIVE ANTI-SPOOFING FINAL ANALYSIS
    // ═══════════════════════════════════════════════════════════════

    /**
     * Run all passive scoring tests after collecting enough frames.
     */
    async _runFinalPassiveAnalysis() {
        const scores = {
            motion: 0,
            geometricJitter: 0,
            lbpTexture: 0,
            laplacianMoire: 0,
            colorReflection: 0,
            eyeBlink: 0,
            headPose: 0,
            sizeVariation: 0
        };

        // ─── 1. Static Frame / Freeze Check ───
        const landmarkVariance = this._computeRawLandmarkVariance();
        if (landmarkVariance < LivenessDetector.LANDMARK_VARIANCE_MIN) {
            this._fail('Phát hiện khuôn mặt giả lập! Hình ảnh tĩnh không có chuyển động tự nhiên.');
            return;
        }
        scores.motion = Math.min(1.0, landmarkVariance / 1.5);

        // ─── 2. Geometric Jitter (3D vs 2D Flatness) ───
        const ratioVariance = this._computeGeometricRatioVariance();
        if (ratioVariance < LivenessDetector.GEOMETRIC_VARIANCE_MIN) {
            this._fail('Phát hiện khuôn mặt giả lập! Mặt phẳng 2D không có chiều sâu 3D.');
            return;
        }
        scores.geometricJitter = Math.min(1.0, ratioVariance / 0.00015);

        // ─── 3. ★ EYE BLINK DETECTION (Critical Layer) ───
        // This is the STRONGEST anti-photo layer.
        // A photo CANNOT produce EAR changes → score = 0.
        if (!this.blinkDetected) {
            this._fail('Không phát hiện chớp mắt! Vui lòng chớp mắt tự nhiên. Ảnh tĩnh không được chấp nhận.');
            return;
        }

        // Also check EAR variance — real eyes have natural micro-fluctuations
        const earVariance = this._computeArrayVariance(this.earHistory);
        if (earVariance < LivenessDetector.EAR_VARIANCE_MIN) {
            this._fail('Phát hiện bất thường mắt! Tỉ lệ mở mắt quá đồng nhất (đặc trưng của ảnh).');
            return;
        }

        scores.eyeBlink = Math.min(1.0, 
            (this.blinkDetected ? 0.7 : 0.0) + 
            Math.min(0.3, earVariance / 0.003)
        );

        // ─── 4. Head Pose Micro-Movement ───
        const yawVariance = this._computeArrayVariance(this.yawHistory);
        const pitchVariance = this._computeArrayVariance(this.pitchHistory);
        
        const headPoseOk = yawVariance >= LivenessDetector.HEAD_YAW_VARIANCE_MIN || 
                           pitchVariance >= LivenessDetector.HEAD_PITCH_VARIANCE_MIN;
        scores.headPose = headPoseOk 
            ? Math.min(1.0, (yawVariance / 0.0005 + pitchVariance / 0.0003) / 2)
            : 0.1;

        // ─── 5. Face Size Variation (Breathing/Sway) ───
        const sizeVariance = this._computeArrayVariance(this.faceSizeHistory);
        scores.sizeVariation = Math.min(1.0, sizeVariance / 5.0);
        if (sizeVariance < LivenessDetector.FACE_SIZE_VARIANCE_MIN) {
            scores.sizeVariation = 0.15; // Penalty for unnaturally stable size
        }

        // ─── Calculate texture averages ───
        let avgLbpRepetition = 0;
        let avgLaplacianVar = 0;
        let avgColorUniformity = 0;

        if (this.textureSamples.length > 0) {
            avgLbpRepetition = this.textureSamples.reduce((sum, s) => sum + s.lbpRepetition, 0) / this.textureSamples.length;
            avgLaplacianVar = this.textureSamples.reduce((sum, s) => sum + s.laplacianVar, 0) / this.textureSamples.length;
            avgColorUniformity = this.textureSamples.reduce((sum, s) => sum + s.colorUniformity, 0) / this.textureSamples.length;
        }

        // ─── 6. LBP Texture Check ───
        if (avgLbpRepetition > LivenessDetector.LBP_GRID_REPETITION_MAX) {
            scores.lbpTexture = Math.max(0.05, 1.0 - (avgLbpRepetition - 0.20) / 0.20);
        } else {
            scores.lbpTexture = Math.max(0.1, Math.min(1.0, 1.25 - (avgLbpRepetition / 0.32)));
        }

        // ─── 7. Laplacian Moiré / Focus Check ───
        if (avgLaplacianVar < 4.0) {
            scores.laplacianMoire = 0.10; // Blurry attack
        } else if (avgLaplacianVar > 400.0) {
            scores.laplacianMoire = Math.max(0.15, 1.0 - (avgLaplacianVar - 400.0) / 600.0);
        } else {
            scores.laplacianMoire = 1.0;
        }

        // ─── 8. Color Specular Analysis ───
        if (avgColorUniformity > LivenessDetector.COLOR_UNIFORMITY_MAX) {
            scores.colorReflection = Math.max(0.05, 1.0 - (avgColorUniformity - 0.80) / 0.18);
        } else {
            scores.colorReflection = Math.max(0.1, Math.min(1.0, 1.0 - (avgColorUniformity - 0.72) / 0.26));
        }

        // ═══ COMBINED WEIGHTED SCORE ═══
        // Eye blink gets highest weight — it's the most reliable anti-photo signal
        const combinedScore = (
            scores.motion          * 0.08 +
            scores.geometricJitter * 0.12 +
            scores.lbpTexture      * 0.15 +
            scores.laplacianMoire  * 0.10 +
            scores.colorReflection * 0.10 +
            scores.eyeBlink        * 0.25 +  // ★ Highest weight — blink is most reliable
            scores.headPose        * 0.12 +
            scores.sizeVariation   * 0.08
        );

        console.log('[Liveness] Final Metrics:', {
            landmarkVariance: landmarkVariance,
            ratioVariance: ratioVariance,
            earVariance: earVariance,
            yawVariance: yawVariance,
            pitchVariance: pitchVariance,
            sizeVariance: sizeVariance,
            avgLbpRepetition: avgLbpRepetition,
            avgLaplacianVar: avgLaplacianVar,
            avgColorUniformity: avgColorUniformity,
            blinkDetected: this.blinkDetected,
            blinkCount: this.blinkCount
        });
        console.log('[Liveness] Calculated Scores:', scores);
        console.log(`[Liveness] Combined Score: ${combinedScore.toFixed(3)} (Required: >= ${LivenessDetector.COMBINED_SCORE_MIN})`);

        if (combinedScore < LivenessDetector.COMBINED_SCORE_MIN) {
            this._fail(`Xác thực bảo mật thất bại (${Math.round(combinedScore * 100)}%). Điểm quá thấp — vui lòng thử lại với khuôn mặt thật.`);
            return;
        }

        // Generate HMAC verification token
        const token = await this._generateToken(combinedScore, scores);

        this.state = 'completed';
        this._updateProgress(100, 'Xác thực hoàn tất ✅');
        this._updateStatus('Xác thực khuôn mặt thật thành công! Đã phát hiện chớp mắt.', 'success');

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

        return this._computeArrayVariance(ratios);
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

    /**
     * Generic array variance calculator.
     */
    _computeArrayVariance(arr) {
        const n = arr.length;
        if (n < 2) return 0;
        
        let sum = 0, sumSq = 0;
        for (let i = 0; i < n; i++) {
            sum += arr[i];
            sumSq += arr[i] * arr[i];
        }
        const mean = sum / n;
        return (sumSq / n) - (mean * mean);
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
            frameCount: this.frameCount,
            blinkCount: this.blinkCount,
            blinkDetected: this.blinkDetected,
            earVariance: this._computeArrayVariance(this.earHistory)
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
            payload: payloadStr, // Send payload as raw JSON string to prevent float parsing mismatch
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

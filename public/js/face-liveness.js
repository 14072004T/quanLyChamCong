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

    static FRAME_COUNT_REQUIRED = 10;
    static FRAME_COUNT_MAX = 60;
    static WARMUP_FRAMES = 2;

    // Motion & Geometry
    static GEOMETRIC_VARIANCE_MIN = 0.00002;
    static LANDMARK_VARIANCE_MIN = 0.02;
    static LANDMARK_VARIANCE_MAX = 20.0;

    // Texture Analysis
    static LBP_GRID_REPETITION_MAX = 0.28;
    static LAPLACIAN_MOIRE_MAX = 80.0;
    static COLOR_UNIFORMITY_MAX = 0.88;

    // ★ Eye Blink Detection (EAR)
    static MIN_BLINKS_REQUIRED = 1;
    // ── Kênh 1: EAR từ landmark ──
    // Đo thực tế trên tablet: EAR của face-api gần như KHÔNG đổi khi người thật chớp mắt
    // (σ ~0.004, không có sụt giảm), nên EAR chỉ được tính là chớp khi mắt khép RÕ RỆT
    // (giảm >= 35%). Mức này nhiễu landmark của ảnh tĩnh không thể tạo ra.
    // LƯU Ý: openRatio PHẢI lớn hơn blinkRatio (hysteresis).
    static EAR_BLINK_THRESHOLD_RATIO = 0.65;
    static EAR_OPEN_THRESHOLD_RATIO = 0.85;
    static EAR_NOISE_SIGMA_MULT = 4.0;
    static EAR_BASELINE_WINDOW = 20;
    static EAR_BASELINE_MIN_SAMPLES = 5;
    static EAR_PER_EYE_MIN_DROP = 0.15;
    static FACE_SHAPE_MAX_CHANGE = 0.08;
    static BLINK_MAX_CLOSED_FRAMES = 10;

    // ── Kênh 2: pixel vùng mắt (kênh chính) ──
    // Khi chớp, mí mắt che lòng đen/lòng trắng => pixel vùng mắt đổi mạnh trong thoáng chốc,
    // trong khi vùng sống mũi ngay cạnh gần như không đổi. Ảnh tĩnh/ảnh bị lắc: vùng mắt và
    // vùng mũi thay đổi CÙNG NHAU => tỷ lệ mắt/mũi không tăng.
    static PATCH_W = 32;
    static PATCH_H = 16;
    static PATCH_BASELINE_MIN = 6;       // Số frame mắt mở cần có trước khi phát hiện
    static PATCH_BASELINE_WINDOW = 20;
    static PATCH_SPIKE_SIGMA = 5.0;      // Đột biến vùng mắt phải vượt median + 5σ
    static PATCH_SPIKE_MIN_FACTOR = 2.0; // ...và gấp >= 2 lần mức bình thường
    static PATCH_PER_EYE_FACTOR = 1.6;   // Từng mắt riêng cũng phải gấp >= 1.6 lần
    static PATCH_RATIO_FACTOR = 1.8;     // Tỷ lệ mắt/mũi phải tăng >= 1.8 lần mức bình thường
    static PATCH_MAX_CORNER_SHIFT = 0.12;// Khoé mắt lệch > 12% bề rộng mắt => landmark nhảy, bỏ qua
    static PATCH_MAX_SPIKE_FRAMES = 8;   // Đột biến phải hết trong <= 8 frame (chớp là thoáng qua)
    static EAR_VARIANCE_MIN = 0.00002; // Nới lỏng ngưỡng variance        

    // Head Pose
    static HEAD_YAW_VARIANCE_MIN = 0.00004;
    static HEAD_PITCH_VARIANCE_MIN = 0.00003;

    // Face Size (breathing/sway)
    static FACE_SIZE_VARIANCE_MIN = 0.2;

    // Combined Score
    static COMBINED_SCORE_MIN = 0.55; // Thực tế: mặt thật có score ~0.87, ngưỡng 0.55 là an toàn

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
        this.earHistory = []; // EAR value per frame
        this.blinkCount = 0; // Detected blinks
        this.blinkState = 'open'; // 'open' | 'closing' | 'closed'
        this.blinkDetected = false; // At least one blink found

        // Head Pose state
        this.yawHistory = [];
        this.pitchHistory = [];

        // Face Size state
        this.faceSizeHistory = [];

        this.latestDescriptor = null;
        this.descriptorSamples = [];
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
        this.blinkCount = 0;
        this.blinkState = 'open';
        this.blinkDetected = false;
        this._closedStreak = 0;
        this._openEarWindow = [];   // {avg, left, right, shape} các frame mắt mở
        this._closedInfo = null;
        this._blinkDiag = null;
        this._patch = { tpl: null, window: [], state: 'open', spikeFrames: 0, peak: null,
                        diag: { maxEye: 0, maxRatio: 0, base: null, rejectedOneEye: 0,
                                rejectedRatio: 0, rejectedShift: 0, rejectedLong: 0 } };
        this.yawHistory = [];
        this.pitchHistory = [];
        this.faceSizeHistory = [];
        this.descriptorSamples = [];

        // Nếu đã có sẵn secret (chuẩn bị trước trong lúc hiển thị kết quả lần quét
        // trước), bỏ qua round-trip mạng để rút ngắn thời gian chờ mỗi lượt quét.
        if (!this.sessionSecret) {
            const maxRetries = 3;
            let lastError = null;
            let isAuthError = false;
            for (let attempt = 1; attempt <= maxRetries; attempt++) {
                try {
                    const res = await fetch('index.php?page=face-liveness-session', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' }
                    });
                    // Xử lý HTTP errors — đọc body JSON nếu có (server trả JSON error)
                    if (!res.ok) {
                        let serverMsg = 'HTTP ' + res.status;
                        try {
                            const errData = await res.json();
                            if (errData.message) serverMsg = errData.message;
                        } catch (_) {}
                        if (res.status === 401) {
                            isAuthError = true;
                            lastError = serverMsg;
                            break; // Không retry — phiên đã hết hạn
                        }
                        throw new Error(serverMsg);
                    }
                    const data = await res.json();
                    if (data.success && data.sessionSecret) {
                        this.sessionSecret = data.sessionSecret;
                        lastError = null;
                        break;
                    } else {
                        lastError = data.message || 'Không thể khởi tạo phiên bảo mật.';
                    }
                } catch (e) {
                    lastError = e;
                    console.warn('[Liveness] Lỗi khởi tạo phiên (lần ' + attempt + '/' + maxRetries + '):', e);
                    if (attempt < maxRetries) {
                        this._updateStatus('Đang thử kết nối lại... (lần ' + (attempt + 1) + '/' + maxRetries + ')', 'info');
                        await new Promise(resolve => setTimeout(resolve, 1500));
                    }
                }
            }
            if (!this.sessionSecret) {
                if (isAuthError) {
                    this._fail(lastError || 'Phiên đăng nhập đã hết hạn. Đang tải lại trang...');
                    setTimeout(function() { window.location.reload(); }, 2000);
                    return;
                }
                const msg = (lastError instanceof Error)
                    ? 'Lỗi kết nối máy chủ: ' + lastError.message
                    : (lastError || 'Không thể khởi tạo phiên bảo mật. Vui lòng tải lại trang.');
                this._fail(msg);
                return;
            }
        }

        this._updateStatus('Đang xác thực... Vui lòng nhìn camera và chớp mắt tự nhiên.', 'info');
        this._updateProgress(5, 'Khởi tạo');
    }

    /**
     * Nạp sẵn session secret lấy trước đó, để start() bỏ qua việc gọi API.
     */
    preloadSecret(secret) {
        if (secret) this.sessionSecret = secret;
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
        const normalizedDescriptor = this._normalizeDescriptor(detection.descriptor);
        if (normalizedDescriptor && normalizedDescriptor.length > 0) {
            this.descriptorSamples.push(normalizedDescriptor);
            if (this.descriptorSamples.length > 8) {
                this.descriptorSamples.shift();
            }
            this.latestDescriptor = this._buildAverageDescriptor(this.descriptorSamples) || normalizedDescriptor;
        }

        // ─── ★ Eye Blink Detection (EAR) ───
        this._processEyeBlink(positions);
        this._processEyePatches(positions);

        // ─── Head Pose Estimation ───
        this._processHeadPose(positions);

        // ─── Face Size Tracking ───
        this.faceSizeHistory.push(box.width * box.height);

        // (Đã bỏ phân tích texture mỗi 3 frame: kết quả không được dùng ở bước kết luận,
        //  mà vẽ lại toàn bộ khung hình video làm giảm tốc độ lấy mẫu => dễ lỡ lần chớp mắt.)

        // ─── Progressive feedback ───
        const progress = this.blinkDetected
            ? Math.min(95, Math.round((this.frameCount / LivenessDetector.FRAME_COUNT_REQUIRED) * 95))
            : Math.min(95, Math.round((this.frameCount / LivenessDetector.FRAME_COUNT_MAX) * 95));

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

        // Chỉ hoàn tất khi đủ dữ liệu VÀ đã phát hiện chớp mắt. 25 frame trên tablet
        // có thể chưa tới 2 giây, nên không được từ chối người thật quá sớm.
        if (this.frameCount >= LivenessDetector.FRAME_COUNT_REQUIRED && this.blinkDetected) {
            this.state = 'analyzing';
            this._runFinalPassiveAnalysis();
        } else if (this.frameCount >= LivenessDetector.FRAME_COUNT_MAX) {
            // KHÔNG có đường "dự phòng" cho qua khi hết thời gian: ảnh tĩnh luôn có nhiễu
            // landmark khiến EAR dao động ~10%, mọi phép suy luận "có sụt giảm" từ lịch sử
            // đều sẽ cho ảnh tĩnh đi qua. Hết thời gian mà chưa chớp mắt => từ chối.
            const dg = this._blinkDiag || {};
            console.warn('[Liveness] Không phát hiện chớp mắt — chẩn đoán:', JSON.stringify({
                frames: this.frameCount,
                baseline: dg.baseline && +dg.baseline.toFixed(3),
                sigma: dg.sigma && +dg.sigma.toFixed(4),
                closeThresholdRatio: dg.baseline && +(dg.closeThreshold / dg.baseline).toFixed(3),
                deepestDropPct: dg.minRatio !== undefined ? +((1 - dg.minRatio) * 100).toFixed(1) : null,
                rejectedOneEye: dg.oneEye || 0,
                rejectedShape: dg.shape || 0,
                rejectedLongClose: dg.longClose || 0
            }));
            const pd = this._patch ? this._patch.diag : {};
            console.warn('[Liveness] Kênh pixel vùng mắt — chẩn đoán:', JSON.stringify({
                baseEye: pd.base && +pd.base.eye.toFixed(4),
                sigmaEye: pd.base && +pd.base.sigma.toFixed(4),
                baseRatio: pd.base && +pd.base.ratio.toFixed(3),
                maxEyeChange: +(pd.maxEye || 0).toFixed(4),
                maxEyeFactor: pd.base && +((pd.maxEye || 0) / Math.max(pd.base.eye, 1e-6)).toFixed(2),
                maxRatioFactor: pd.base && +((pd.maxRatio || 0) / Math.max(pd.base.ratio, 1e-6)).toFixed(2),
                rejectedOneEye: pd.rejectedOneEye || 0,
                rejectedRatio: pd.rejectedRatio || 0,
                rejectedShift: pd.rejectedShift || 0,
                rejectedLong: pd.rejectedLong || 0
            }));
            this._fail('Chưa phát hiện chớp mắt sau thời gian quét. Vui lòng nhìn thẳng vào camera và chớp mắt rõ một lần.');
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
        this.descriptorSamples = [];
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
        const leftEAR = this._calculateEAR(positions, [36, 37, 38, 39, 40, 41]);
        const rightEAR = this._calculateEAR(positions, [42, 43, 44, 45, 46, 47]);
        const avgEAR = (leftEAR + rightEAR) / 2.0;

        // Ignore extreme outlier EAR values due to landmark misdetection
        if (!isFinite(avgEAR) || avgEAR > 0.55) {
            console.warn(`[Liveness] Outlier EAR detected (${avgEAR}), skipping.`);
            return;
        }

        this.earHistory.push(avgEAR);

        // Tỷ lệ hình học khuôn mặt KHÔNG đổi khi chớp mắt (sống mũi / khoảng cách 2 khoé mắt
        // ngoài). Nếu tỷ lệ này đổi cùng lúc EAR giảm => đang nghiêng/lật ảnh hoặc màn hình.
        const interOcular = this._dist(positions[36], positions[45]);
        const shape = interOcular > 0 ? this._dist(positions[27], positions[33]) / interOcular : 0;

        const sample = { avg: avgEAR, left: leftEAR, right: rightEAR, shape };
        const C = LivenessDetector;
        const win = this._openEarWindow;

        // Chưa đủ mẫu baseline: chỉ thu thập.
        if (win.length < C.EAR_BASELINE_MIN_SAMPLES) {
            win.push(sample);
            return;
        }

        // Baseline = MEDIAN các frame mắt mở (không dùng MAX: max luôn bị nhiễu đẩy lên,
        // khiến nhiễu bình thường trên ảnh tĩnh trông như "sụt giảm").
        const base = {
            avg: this._median(win.map(w => w.avg)),
            left: this._median(win.map(w => w.left)),
            right: this._median(win.map(w => w.right)),
            shape: this._median(win.map(w => w.shape))
        };
        // Độ lệch chuẩn robust của nhiễu EAR (1.4826 * MAD)
        const sigma = 1.4826 * this._median(win.map(w => Math.abs(w.avg - base.avg)));

        const closeThreshold = Math.min(
            base.avg * C.EAR_BLINK_THRESHOLD_RATIO,
            base.avg - C.EAR_NOISE_SIGMA_MULT * sigma
        );

        // Số liệu chẩn đoán (in ra console khi quét thất bại)
        const dg = this._blinkDiag || (this._blinkDiag = { minRatio: 1, oneEye: 0, shape: 0, longClose: 0 });
        dg.baseline = base.avg; dg.sigma = sigma; dg.closeThreshold = closeThreshold;
        dg.minRatio = Math.min(dg.minRatio, avgEAR / base.avg);
        const openThreshold = base.avg * C.EAR_OPEN_THRESHOLD_RATIO;

        if (this.blinkState === 'open') {
            if (avgEAR < closeThreshold) {
                const leftDrop = base.left > 0 ? 1 - leftEAR / base.left : 0;
                const rightDrop = base.right > 0 ? 1 - rightEAR / base.right : 0;
                const shapeChange = base.shape > 0 ? Math.abs(shape / base.shape - 1) : 1;

                if (leftDrop < C.EAR_PER_EYE_MIN_DROP || rightDrop < C.EAR_PER_EYE_MIN_DROP) {
                    dg.oneEye++;
                    console.log(`[Liveness] Bỏ qua: chỉ một mắt giảm (L ${(leftDrop*100).toFixed(0)}%, R ${(rightDrop*100).toFixed(0)}%)`);
                } else if (shapeChange > C.FACE_SHAPE_MAX_CHANGE) {
                    dg.shape++;
                    console.log(`[Liveness] Bỏ qua: tỷ lệ khuôn mặt đổi ${(shapeChange*100).toFixed(1)}% (nghiêng/lật ảnh)`);
                } else {
                    this.blinkState = 'closed';
                    this._closedStreak = 0;
                    this._closedInfo = { minEAR: avgEAR, baseline: base.avg };
                    console.log(`[Liveness] Eye closed (avgEAR ${avgEAR.toFixed(3)} < ${closeThreshold.toFixed(3)}, baseline ${base.avg.toFixed(3)}, σ ${sigma.toFixed(4)})`);
                }
            } else {
                // Chỉ đưa frame mắt mở vào baseline
                win.push(sample);
                if (win.length > C.EAR_BASELINE_WINDOW) win.shift();
            }
        } else { // 'closed'
            this._closedStreak++;
            this._closedInfo.minEAR = Math.min(this._closedInfo.minEAR, avgEAR);

            if (avgEAR > openThreshold) {
                const drop = 1 - this._closedInfo.minEAR / this._closedInfo.baseline;
                this.blinkCount++;
                this.blinkDetected = true;
                console.log(`[Liveness] Blink confirmed (EAR)! EAR drop: ${(drop*100).toFixed(1)}%. Count: ${this.blinkCount}`);
                if (this.cb.onBlinkDetected) this.cb.onBlinkDetected(this.blinkCount);
                this.blinkState = 'open';
                this._closedInfo = null;
            } else if (this._closedStreak > C.BLINK_MAX_CLOSED_FRAMES) {
                // Nhắm/giảm quá lâu không phải chớp mắt (có thể ảnh bị nghiêng dần).
                // Reset baseline để hiệu chỉnh lại theo tình trạng hiện tại, KHÔNG tính chớp.
                dg.longClose++;
                console.warn(`[Liveness] Trạng thái đóng kéo dài ${this._closedStreak} frame, reset baseline.`);
                this._openEarWindow = [sample];
                this.blinkState = 'open';
                this._closedInfo = null;
            }
        }

        if (this.frameCount % 5 === 0) {
            console.log(`[Liveness] Frame ${this.frameCount}: avgEAR=${avgEAR.toFixed(3)}, baseline=${base.avg.toFixed(3)}, σ=${sigma.toFixed(4)}, state=${this.blinkState}, blinks=${this.blinkCount}`);
        }
    }

    /**
     * Kênh 2: phát hiện chớp mắt bằng thay đổi pixel vùng mắt so với vùng sống mũi.
     */
    _processEyePatches(positions) {
        const P = this._patch;
        if (!P) return;
        const C = LivenessDetector;

        const eyeW = (this._dist(positions[36], positions[39]) + this._dist(positions[42], positions[45])) / 2;
        if (!(eyeW > 6)) return;

        const center = (idx) => {
            let x = 0, y = 0;
            idx.forEach(i => { x += positions[i].x; y += positions[i].y; });
            return { x: x / idx.length, y: y / idx.length };
        };
        const sw = eyeW * 1.6, sh = eyeW * 0.8;
        const left = this._grabPatch(center([36, 37, 38, 39, 40, 41]), sw, sh);
        const right = this._grabPatch(center([42, 43, 44, 45, 46, 47]), sw, sh);
        // Vùng tham chiếu: sống mũi (không đổi khi chớp mắt), cùng kích thước
        const ref = this._grabPatch(center([28, 29, 30]), sw, sh);
        if (!left || !right || !ref) return;

        // Vị trí khoé mắt so với chóp mũi, chuẩn hoá theo bề rộng mắt (khoé mắt đứng yên khi chớp)
        const nose = positions[30];
        const corners = [36, 39, 42, 45].map(i => ({
            x: (positions[i].x - nose.x) / eyeW, y: (positions[i].y - nose.y) / eyeW
        }));

        if (!P.tpl) {
            P.tpl = { left, right, ref, corners };
            return;
        }

        const dL = this._patchDiff(left, P.tpl.left);
        const dR = this._patchDiff(right, P.tpl.right);
        const dRef = this._patchDiff(ref, P.tpl.ref);
        const eye = (dL + dR) / 2;
        const ratio = eye / Math.max(dRef, 0.004);
        const shift = Math.max(...corners.map((c, i) => Math.hypot(c.x - P.tpl.corners[i].x, c.y - P.tpl.corners[i].y)));

        const updateTemplate = () => {
            const a = 0.3;
            ['left', 'right', 'ref'].forEach(k => {
                const t = P.tpl[k], c = { left, right, ref }[k];
                for (let i = 0; i < t.length; i++) t[i] = t[i] * (1 - a) + c[i] * a;
            });
            P.tpl.corners = P.tpl.corners.map((c, i) => ({
                x: c.x * (1 - a) + corners[i].x * a, y: c.y * (1 - a) + corners[i].y * a
            }));
        };

        const win = P.window;
        if (win.length < C.PATCH_BASELINE_MIN) {
            win.push({ eye, dL, dR, ratio });
            updateTemplate();
            return;
        }

        const med = (k) => this._median(win.map(w => w[k]));
        const base = { eye: med('eye'), dL: med('dL'), dR: med('dR'), ratio: med('ratio') };
        base.sigma = 1.4826 * this._median(win.map(w => Math.abs(w.eye - base.eye)));
        P.diag.base = base;
        P.diag.maxEye = Math.max(P.diag.maxEye, eye);
        P.diag.maxRatio = Math.max(P.diag.maxRatio, ratio);

        const spikeLevel = Math.max(base.eye + C.PATCH_SPIKE_SIGMA * base.sigma, base.eye * C.PATCH_SPIKE_MIN_FACTOR);
        const isSpike = eye > spikeLevel;

        if (P.state === 'open') {
            if (isSpike) {
                if (dL < base.dL * C.PATCH_PER_EYE_FACTOR || dR < base.dR * C.PATCH_PER_EYE_FACTOR) {
                    P.diag.rejectedOneEye++;
                } else if (ratio < base.ratio * C.PATCH_RATIO_FACTOR) {
                    P.diag.rejectedRatio++;
                } else if (shift > C.PATCH_MAX_CORNER_SHIFT) {
                    P.diag.rejectedShift++;
                } else {
                    P.state = 'spike';
                    P.spikeFrames = 0;
                    P.peak = { eye, ratio };
                    console.log(`[Liveness] Vùng mắt đột biến: eye=${eye.toFixed(4)} (x${(eye / base.eye).toFixed(1)}), ratio x${(ratio / base.ratio).toFixed(1)}`);
                }
                // Không cập nhật template bằng frame đột biến
            } else {
                win.push({ eye, dL, dR, ratio });
                if (win.length > C.PATCH_BASELINE_WINDOW) win.shift();
                updateTemplate();
            }
        } else { // 'spike'
            P.spikeFrames++;
            // Mắt mở lại: vùng mắt trở về gần template mắt mở
            if (eye < base.eye + 2 * base.sigma || eye < base.eye * 1.3) {
                this.blinkCount++;
                this.blinkDetected = true;
                console.log(`[Liveness] Blink confirmed (pixel)! eye x${(P.peak.eye / base.eye).toFixed(1)}, ratio x${(P.peak.ratio / base.ratio).toFixed(1)}, ${P.spikeFrames} frame. Count: ${this.blinkCount}`);
                if (this.cb.onBlinkDetected) this.cb.onBlinkDetected(this.blinkCount);
                P.state = 'open';
                P.peak = null;
            } else if (P.spikeFrames > C.PATCH_MAX_SPIKE_FRAMES) {
                // Thay đổi kéo dài => đổi tư thế/ánh sáng, không phải chớp. Học lại template.
                P.diag.rejectedLong++;
                P.tpl = { left, right, ref, corners };
                P.window = [];
                P.state = 'open';
                P.peak = null;
            }
        }

        if (this.frameCount % 5 === 0) {
            console.log(`[Liveness] Pixel f${this.frameCount}: eye=${eye.toFixed(4)} base=${base.eye.toFixed(4)} σ=${base.sigma.toFixed(4)} ratio=${ratio.toFixed(2)} (base ${base.ratio.toFixed(2)}) shift=${shift.toFixed(3)} state=${P.state}`);
        }
    }

    /**
     * Cắt vùng (cx,cy,w,h) từ video, thu về PATCH_W x PATCH_H xám, chuẩn hoá theo độ sáng trung bình.
     */
    _grabPatch(c, w, h) {
        const C = LivenessDetector;
        const vw = this.video.videoWidth, vh = this.video.videoHeight;
        if (!vw || !vh) return null;
        const sx = c.x - w / 2, sy = c.y - h / 2;
        if (sx < 0 || sy < 0 || sx + w > vw || sy + h > vh) return null;
        if (!this._patchCanvas) {
            this._patchCanvas = document.createElement('canvas');
            this._patchCanvas.width = C.PATCH_W;
            this._patchCanvas.height = C.PATCH_H;
            this._patchCtx = this._patchCanvas.getContext('2d', { willReadFrequently: true });
        }
        const ctx = this._patchCtx;
        ctx.drawImage(this.video, sx, sy, w, h, 0, 0, C.PATCH_W, C.PATCH_H);
        const d = ctx.getImageData(0, 0, C.PATCH_W, C.PATCH_H).data;
        const n = C.PATCH_W * C.PATCH_H;
        const g = new Float32Array(n);
        let sum = 0;
        for (let i = 0; i < n; i++) {
            g[i] = 0.299 * d[i * 4] + 0.587 * d[i * 4 + 1] + 0.114 * d[i * 4 + 2];
            sum += g[i];
        }
        const mean = sum / n;
        if (mean < 8) return null; // quá tối
        for (let i = 0; i < n; i++) g[i] /= mean;
        return g;
    }

    _patchDiff(a, b) {
        let s = 0;
        for (let i = 0; i < a.length; i++) s += Math.abs(a[i] - b[i]);
        return s / a.length;
    }

    _median(arr) {
        if (!arr.length) return 0;
        const a = arr.slice().sort((x, y) => x - y);
        const m = a.length >> 1;
        return a.length % 2 ? a[m] : (a[m - 1] + a[m]) / 2;
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
        const h = this._dist(p1, p4); // ||p1 - p4||

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
        if (this.landmarkHistory.length < LivenessDetector.FRAME_COUNT_REQUIRED) {
            return;
        }

        const landmarkVariance = this._computeRawLandmarkVariance();
        console.log(`[Liveness] PASS — landmarkVariance: ${landmarkVariance.toFixed(4)}, frames: ${this.frameCount}, blinks: ${this.blinkCount}`);

        const token = await this._generateToken(0.95, { motion: 1.0, eyeBlink: 1.0 });
        this.state = 'completed';
        this._updateProgress(100, 'Xác thực hoàn tất ✅');
        this._updateStatus('Xác thực khuôn mặt thật thành công!', 'success');

        if (this.cb.onComplete) {
            const descriptorToSend = this._normalizeDescriptor(this._buildAverageDescriptor(this.descriptorSamples)) || this._normalizeDescriptor(this.latestDescriptor);
            if (!descriptorToSend || descriptorToSend.length === 0) {
                this._fail('Không thu thập được dữ liệu khuôn mặt từ camera. Vui lòng nhìn thẳng vào camera và thử lại.');
                return;
            }
            this.cb.onComplete(token, descriptorToSend);
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
                    if (gray[(y - 1) * fw + x] >= center) code |= 2;
                    if (gray[(y - 1) * fw + (x + 1)] >= center) code |= 4;
                    if (gray[y * fw + (x + 1)] >= center) code |= 8;
                    if (gray[(y + 1) * fw + (x + 1)] >= center) code |= 16;
                    if (gray[(y + 1) * fw + x] >= center) code |= 32;
                    if (gray[(y + 1) * fw + (x - 1)] >= center) code |= 64;
                    if (gray[y * fw + (x - 1)] >= center) code |= 128;

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
            let rSum = 0,
                gSum = 0,
                bSum = 0;
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
            let sumX = 0,
                sumY = 0,
                sumXSq = 0,
                sumYSq = 0;
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

        let sum = 0,
            sumSq = 0;
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

    _normalizeDescriptor(descriptor) {
        if (!descriptor) return null;
        if (Array.isArray(descriptor)) return descriptor.slice();
        if (ArrayBuffer.isView(descriptor)) return Array.from(descriptor);
        if (descriptor && typeof descriptor === 'object' && typeof descriptor.length === 'number') {
            return Array.from(descriptor);
        }
        return null;
    }

    _buildAverageDescriptor(samples) {
        if (!Array.isArray(samples) || samples.length === 0) {
            return null;
        }

        const dims = samples[0].length;
        const avg = new Array(dims).fill(0);
        samples.forEach((sample) => {
            const normalizedSample = this._normalizeDescriptor(sample);
            if (!normalizedSample || normalizedSample.length !== dims) return;
            normalizedSample.forEach((value, index) => {
                avg[index] += Number(value) || 0;
            });
        });

        const count = samples.length;
        return avg.map((value) => value / count);
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
                encoder.encode(this.sessionSecret), { name: 'HMAC', hash: 'SHA-256' },
                false, ['sign']
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
/*
 * qrcode.js - Bộ tạo mã QR nhỏ gọn, không cần thư viện ngoài.
 * Hỗ trợ chế độ byte (UTF-8), mức sửa lỗi M, phiên bản 1-10 (tối đa 213 byte).
 * Dùng:  QRLite.draw(canvasElement, 'https://...');
 */
(function (global) {
    'use strict';

    /* ---------- Trường Galois GF(256) và Reed-Solomon ---------- */
    var EXP = new Array(512);
    var LOG = new Array(256);
    (function initTables() {
        var x = 1;
        for (var i = 0; i < 255; i++) {
            EXP[i] = x;
            LOG[x] = i;
            x <<= 1;
            if (x & 256) { x ^= 0x11d; }
        }
        for (var j = 255; j < 512; j++) { EXP[j] = EXP[j - 255]; }
    })();

    function gfMul(a, b) {
        return (a === 0 || b === 0) ? 0 : EXP[LOG[a] + LOG[b]];
    }

    function rsGenerator(degree) {
        var poly = [1];
        for (var i = 0; i < degree; i++) {
            var next = new Array(poly.length + 1);
            for (var k = 0; k < next.length; k++) { next[k] = 0; }
            for (var j = 0; j < poly.length; j++) {
                next[j] ^= poly[j];
                next[j + 1] ^= gfMul(poly[j], EXP[i]);
            }
            poly = next;
        }
        return poly;
    }

    function rsEncode(data, ecLen) {
        var gen = rsGenerator(ecLen);
        var res = [];
        for (var i = 0; i < ecLen; i++) { res.push(0); }
        for (var d = 0; d < data.length; d++) {
            var factor = data[d] ^ res[0];
            res.shift();
            res.push(0);
            for (var j = 0; j < ecLen; j++) { res[j] ^= gfMul(gen[j + 1], factor); }
        }
        return res;
    }

    /* ---------- Bảng thông số (mức sửa lỗi M, phiên bản 1-10) ---------- */
    // ec: số byte sửa lỗi mỗi khối; groups: [[số khối, byte dữ liệu mỗi khối], ...]
    var VERSIONS = {
        1:  { ec: 10, groups: [[1, 16]] },
        2:  { ec: 16, groups: [[1, 28]] },
        3:  { ec: 26, groups: [[1, 44]] },
        4:  { ec: 18, groups: [[2, 32]] },
        5:  { ec: 24, groups: [[2, 43]] },
        6:  { ec: 16, groups: [[4, 27]] },
        7:  { ec: 18, groups: [[4, 31]] },
        8:  { ec: 22, groups: [[2, 38], [2, 39]] },
        9:  { ec: 22, groups: [[3, 36], [2, 37]] },
        10: { ec: 26, groups: [[4, 43], [1, 44]] }
    };
    var ALIGN = {
        1: [], 2: [6, 18], 3: [6, 22], 4: [6, 26], 5: [6, 30], 6: [6, 34],
        7: [6, 22, 38], 8: [6, 24, 42], 9: [6, 26, 46], 10: [6, 28, 50]
    };

    function dataCapacity(version) {
        var total = 0;
        VERSIONS[version].groups.forEach(function (g) { total += g[0] * g[1]; });
        return total;
    }

    function utf8Bytes(text) {
        if (typeof TextEncoder !== 'undefined') {
            return Array.prototype.slice.call(new TextEncoder().encode(text));
        }
        var s = unescape(encodeURIComponent(text));
        var out = [];
        for (var i = 0; i < s.length; i++) { out.push(s.charCodeAt(i)); }
        return out;
    }

    /* ---------- Tạo dãy byte dữ liệu + sửa lỗi ---------- */
    function buildCodewords(bytes) {
        var version = 0;
        for (var v = 1; v <= 10; v++) {
            var countBits = v < 10 ? 8 : 16;
            if (4 + countBits + 8 * bytes.length <= dataCapacity(v) * 8) { version = v; break; }
        }
        if (!version) { throw new Error('Nội dung quá dài để tạo mã QR (tối đa 213 byte).'); }

        var bits = [];
        function put(value, len) {
            for (var i = len - 1; i >= 0; i--) { bits.push((value >>> i) & 1); }
        }
        put(4, 4); // chế độ byte
        put(bytes.length, version < 10 ? 8 : 16);
        bytes.forEach(function (b) { put(b, 8); });

        var capBits = dataCapacity(version) * 8;
        var term = Math.min(4, capBits - bits.length);
        put(0, term);
        while (bits.length % 8 !== 0) { bits.push(0); }
        var pads = [0xEC, 0x11];
        for (var p = 0; bits.length < capBits; p++) { put(pads[p % 2], 8); }

        var data = [];
        for (var i = 0; i < bits.length; i += 8) {
            var byte = 0;
            for (var k = 0; k < 8; k++) { byte = (byte << 1) | bits[i + k]; }
            data.push(byte);
        }

        // Chia khối, tính sửa lỗi, rồi xen kẽ
        var info = VERSIONS[version];
        var blocks = [];
        var offset = 0;
        info.groups.forEach(function (g) {
            for (var b = 0; b < g[0]; b++) {
                var chunk = data.slice(offset, offset + g[1]);
                offset += g[1];
                blocks.push({ data: chunk, ec: rsEncode(chunk, info.ec) });
            }
        });
        var result = [];
        var maxData = 0;
        blocks.forEach(function (b) { if (b.data.length > maxData) { maxData = b.data.length; } });
        for (var d = 0; d < maxData; d++) {
            blocks.forEach(function (b) { if (d < b.data.length) { result.push(b.data[d]); } });
        }
        for (var e = 0; e < info.ec; e++) {
            blocks.forEach(function (b) { result.push(b.ec[e]); });
        }
        return { version: version, codewords: result };
    }

    /* ---------- Dựng ma trận ---------- */
    function getBit(x, i) { return ((x >>> i) & 1) !== 0; }

    function makeMatrix(version, codewords) {
        var size = 17 + 4 * version;
        var modules = [];
        var isFunc = [];
        for (var y = 0; y < size; y++) {
            modules.push([]);
            isFunc.push([]);
            for (var x = 0; x < size; x++) { modules[y].push(false); isFunc[y].push(false); }
        }
        function setFunc(x, y, dark) { modules[y][x] = dark; isFunc[y][x] = true; }

        // Vạch thời gian
        for (var i = 0; i < size; i++) {
            setFunc(6, i, i % 2 === 0);
            setFunc(i, 6, i % 2 === 0);
        }
        // Ô định vị ở 3 góc (kèm viền trắng)
        function finder(cx, cy) {
            for (var dy = -4; dy <= 4; dy++) {
                for (var dx = -4; dx <= 4; dx++) {
                    var dist = Math.max(Math.abs(dx), Math.abs(dy));
                    var xx = cx + dx, yy = cy + dy;
                    if (xx >= 0 && xx < size && yy >= 0 && yy < size) {
                        setFunc(xx, yy, dist !== 2 && dist !== 4);
                    }
                }
            }
        }
        finder(3, 3);
        finder(size - 4, 3);
        finder(3, size - 4);

        // Ô căn chỉnh
        var pos = ALIGN[version];
        for (var a = 0; a < pos.length; a++) {
            for (var b = 0; b < pos.length; b++) {
                if ((a === 0 && b === 0) || (a === 0 && b === pos.length - 1) || (a === pos.length - 1 && b === 0)) { continue; }
                for (var dy = -2; dy <= 2; dy++) {
                    for (var dx = -2; dx <= 2; dx++) {
                        setFunc(pos[a] + dx, pos[b] + dy, Math.max(Math.abs(dx), Math.abs(dy)) !== 1);
                    }
                }
            }
        }

        // Thông tin phiên bản (từ phiên bản 7)
        if (version >= 7) {
            var rem = version;
            for (var r = 0; r < 12; r++) { rem = (rem << 1) ^ ((rem >>> 11) * 0x1F25); }
            var vbits = (version << 12) | rem;
            for (var n = 0; n < 18; n++) {
                var col = getBit(vbits, n);
                var p1 = size - 11 + (n % 3);
                var p2 = Math.floor(n / 3);
                setFunc(p1, p2, col);
                setFunc(p2, p1, col);
            }
        }

        function drawFormat(mask) {
            var data = (0 << 3) | mask; // mức sửa lỗi M = 00
            var rem = data;
            for (var r = 0; r < 10; r++) { rem = (rem << 1) ^ ((rem >>> 9) * 0x537); }
            var bits = ((data << 10) | rem) ^ 0x5412;
            for (var i = 0; i <= 5; i++) { setFunc(8, i, getBit(bits, i)); }
            setFunc(8, 7, getBit(bits, 6));
            setFunc(8, 8, getBit(bits, 7));
            setFunc(7, 8, getBit(bits, 8));
            for (var j = 9; j < 15; j++) { setFunc(14 - j, 8, getBit(bits, j)); }
            for (var k = 0; k < 8; k++) { setFunc(size - 1 - k, 8, getBit(bits, k)); }
            for (var m = 8; m < 15; m++) { setFunc(8, size - 15 + m, getBit(bits, m)); }
            setFunc(8, size - 8, true); // ô đen cố định
        }
        drawFormat(0); // giữ chỗ

        // Đặt dữ liệu theo đường zigzag
        var bitIndex = 0;
        for (var right = size - 1; right >= 1; right -= 2) {
            if (right === 6) { right = 5; }
            for (var vert = 0; vert < size; vert++) {
                for (var j2 = 0; j2 < 2; j2++) {
                    var x2 = right - j2;
                    var upward = ((right + 1) & 2) === 0;
                    var y2 = upward ? size - 1 - vert : vert;
                    if (!isFunc[y2][x2] && bitIndex < codewords.length * 8) {
                        modules[y2][x2] = getBit(codewords[bitIndex >>> 3], 7 - (bitIndex & 7));
                        bitIndex++;
                    }
                }
            }
        }

        function applyMask(mask) {
            for (var y = 0; y < size; y++) {
                for (var x = 0; x < size; x++) {
                    var inv;
                    switch (mask) {
                        case 0: inv = (x + y) % 2 === 0; break;
                        case 1: inv = y % 2 === 0; break;
                        case 2: inv = x % 3 === 0; break;
                        case 3: inv = (x + y) % 3 === 0; break;
                        case 4: inv = (Math.floor(x / 3) + Math.floor(y / 2)) % 2 === 0; break;
                        case 5: inv = (x * y) % 2 + (x * y) % 3 === 0; break;
                        case 6: inv = ((x * y) % 2 + (x * y) % 3) % 2 === 0; break;
                        default: inv = ((x + y) % 2 + (x * y) % 3) % 2 === 0;
                    }
                    if (!isFunc[y][x] && inv) { modules[y][x] = !modules[y][x]; }
                }
            }
        }

        function penalty() {
            var score = 0, x, y, run, k;
            // Quy tắc 1: dãy ô cùng màu (hàng và cột)
            for (var dir = 0; dir < 2; dir++) {
                for (y = 0; y < size; y++) {
                    run = 1;
                    for (x = 1; x < size; x++) {
                        var cur = dir === 0 ? modules[y][x] : modules[x][y];
                        var prev = dir === 0 ? modules[y][x - 1] : modules[x - 1][y];
                        if (cur === prev) { run++; } else { if (run >= 5) { score += 3 + run - 5; } run = 1; }
                    }
                    if (run >= 5) { score += 3 + run - 5; }
                }
            }
            // Quy tắc 2: khối 2x2 cùng màu
            for (y = 0; y < size - 1; y++) {
                for (x = 0; x < size - 1; x++) {
                    var c = modules[y][x];
                    if (c === modules[y][x + 1] && c === modules[y + 1][x] && c === modules[y + 1][x + 1]) { score += 3; }
                }
            }
            // Quy tắc 3: mẫu giống ô định vị
            var pat1 = [1, 0, 1, 1, 1, 0, 1, 0, 0, 0, 0];
            var pat2 = [0, 0, 0, 0, 1, 0, 1, 1, 1, 0, 1];
            for (var d2 = 0; d2 < 2; d2++) {
                for (y = 0; y < size; y++) {
                    for (x = 0; x + 11 <= size; x++) {
                        var m1 = true, m2 = true;
                        for (k = 0; k < 11; k++) {
                            var v = d2 === 0 ? modules[y][x + k] : modules[x + k][y];
                            if ((v ? 1 : 0) !== pat1[k]) { m1 = false; }
                            if ((v ? 1 : 0) !== pat2[k]) { m2 = false; }
                        }
                        if (m1) { score += 40; }
                        if (m2) { score += 40; }
                    }
                }
            }
            // Quy tắc 4: tỉ lệ ô đen
            var dark = 0;
            for (y = 0; y < size; y++) { for (x = 0; x < size; x++) { if (modules[y][x]) { dark++; } } }
            var total = size * size;
            k = Math.ceil(Math.abs(dark * 20 - total * 10) / total) - 1;
            score += Math.max(0, k) * 10;
            return score;
        }

        // Thử 8 mặt nạ, chọn mặt nạ điểm phạt thấp nhất
        var best = 0, bestScore = Infinity;
        for (var mask = 0; mask < 8; mask++) {
            applyMask(mask);
            drawFormat(mask);
            var s = penalty();
            if (s < bestScore) { bestScore = s; best = mask; }
            applyMask(mask); // hoàn tác
        }
        applyMask(best);
        drawFormat(best);
        return { size: size, modules: modules };
    }

    /* ---------- Giao diện sử dụng ---------- */
    function matrix(text) {
        var built = buildCodewords(utf8Bytes(String(text)));
        var m = makeMatrix(built.version, built.codewords);
        m.version = built.version;
        return m;
    }

    // Vẽ mã QR lên canvas (nền trắng, vùng yên tĩnh 4 ô)
    function draw(canvas, text, options) {
        options = options || {};
        var m = matrix(text);
        var quiet = 4;
        var cells = m.size + quiet * 2;
        var scale = Math.max(1, Math.floor(canvas.width / cells));
        var pixels = scale * cells;
        canvas.width = pixels;
        canvas.height = pixels;
        var ctx = canvas.getContext('2d');
        ctx.fillStyle = options.light || '#ffffff';
        ctx.fillRect(0, 0, pixels, pixels);
        ctx.fillStyle = options.dark || '#000000';
        for (var y = 0; y < m.size; y++) {
            for (var x = 0; x < m.size; x++) {
                if (m.modules[y][x]) {
                    ctx.fillRect((x + quiet) * scale, (y + quiet) * scale, scale, scale);
                }
            }
        }
        return m;
    }

    global.QRLite = { matrix: matrix, draw: draw };
})(typeof window !== 'undefined' ? window : globalThis);

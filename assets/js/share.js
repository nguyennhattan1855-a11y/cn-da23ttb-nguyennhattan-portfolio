// assets/js/share.js - Trang "Chia sẻ & xuất hồ sơ": sao chép đường dẫn, tạo và tải mã QR
(function () {
    'use strict';

    // ----- Sao chép đường dẫn -----
    var input = document.getElementById('share-url');
    var copyBtn = document.getElementById('copy-btn');
    var status = document.getElementById('copy-status');

    function setStatus(text) { if (status) { status.textContent = text; } }

    if (input && copyBtn) {
        copyBtn.addEventListener('click', function () {
            var text = input.value;
            function fallback() {
                input.focus();
                input.select();
                try {
                    setStatus(document.execCommand('copy') ? 'Đã sao chép đường dẫn.' : 'Không sao chép tự động được, hãy nhấn Ctrl+C.');
                } catch (e) {
                    setStatus('Không sao chép tự động được, hãy nhấn Ctrl+C.');
                }
            }
            if (navigator.clipboard && window.isSecureContext) {
                navigator.clipboard.writeText(text).then(function () {
                    setStatus('Đã sao chép đường dẫn.');
                }, fallback);
            } else {
                fallback();
            }
        });
    }

    // ----- Mã QR -----
    var canvas = document.getElementById('qr-canvas');
    var qrError = document.getElementById('qr-error');
    var dlBtn = document.getElementById('qr-download');
    if (!canvas || !window.QRLite) { return; }

    var text = canvas.getAttribute('data-text') || '';
    var ok = false;
    try {
        window.QRLite.draw(canvas, text);
        ok = true;
    } catch (e) {
        if (qrError) { qrError.hidden = false; qrError.textContent = e.message; }
    }

    if (dlBtn) {
        if (!ok) { dlBtn.disabled = true; }
        dlBtn.addEventListener('click', function () {
            // Vẽ lại ở kích thước lớn (khoảng 1000 px) để ảnh tải về sắc nét
            var big = document.createElement('canvas');
            big.width = 1000;
            big.height = 1000;
            window.QRLite.draw(big, text);
            big.toBlob(function (blob) {
                if (!blob) { return; }
                var a = document.createElement('a');
                a.href = URL.createObjectURL(blob);
                a.download = 'ma-qr-ho-so.png';
                document.body.appendChild(a);
                a.click();
                document.body.removeChild(a);
                setTimeout(function () { URL.revokeObjectURL(a.href); }, 1000);
            }, 'image/png');
        });
    }
})();

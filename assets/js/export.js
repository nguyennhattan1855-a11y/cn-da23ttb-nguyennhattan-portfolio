// assets/js/export.js - Xuất hồ sơ công khai thành PDF (hộp thoại in) hoặc ảnh PNG
(function () {
    'use strict';
    var root = document.getElementById('profile-root');
    if (!root) { return; }

    var btnPdf = document.getElementById('btn-pdf');
    var btnPng = document.getElementById('btn-png');
    var busy = false;

    function toast(text, isError) {
        var el = document.getElementById('pf-toast');
        if (!el) {
            el = document.createElement('div');
            el.id = 'pf-toast';
            el.className = 'pf-toast';
            el.setAttribute('role', 'status');
            document.body.appendChild(el);
        }
        el.textContent = text;
        el.className = 'pf-toast show' + (isError ? ' error' : '');
        clearTimeout(toast.t);
        toast.t = setTimeout(function () { el.className = 'pf-toast'; }, 5000);
    }

    // Chờ tất cả ảnh tải xong
    function imagesReady() {
        var imgs = Array.prototype.slice.call(document.images);
        return Promise.all(imgs.map(function (img) {
            if (img.complete) { return Promise.resolve(); }
            return new Promise(function (resolve) {
                img.addEventListener('load', resolve);
                img.addEventListener('error', resolve);
            });
        }));
    }

    /* ---------- PDF ---------- */
    function exportPdf() {
        imagesReady().then(function () {
            toast('Trong hộp thoại in, hãy chọn máy in "Lưu dưới dạng PDF" (Save as PDF).');
            window.print();
        });
    }

    /* ---------- PNG ---------- */
    function blobToDataUrl(blob) {
        return new Promise(function (resolve, reject) {
            var fr = new FileReader();
            fr.onload = function () { resolve(fr.result); };
            fr.onerror = reject;
            fr.readAsDataURL(blob);
        });
    }

    // Nhúng ảnh vào thẳng trang (data URL) để ảnh xuất ra không phụ thuộc mạng
    function inlineImage(img) {
        var src = img.currentSrc || img.src;
        if (!src || src.indexOf('data:') === 0) { return Promise.resolve(); }
        return fetch(src, { credentials: 'same-origin' })
            .then(function (r) { if (!r.ok) { throw new Error('http'); } return r.blob(); })
            .then(blobToDataUrl)
            .then(function (data) { img.removeAttribute('srcset'); img.setAttribute('src', data); })
            .catch(function () { img.parentNode && img.parentNode.removeChild(img); });
    }

    function collectCss() {
        var css = '';
        Array.prototype.forEach.call(document.styleSheets, function (sheet) {
            try {
                Array.prototype.forEach.call(sheet.cssRules, function (rule) {
                    if (rule.type === 7 || rule.type === 5) { return; } // bỏ @keyframes / @font-face
                    css += rule.cssText + '\n';
                });
            } catch (e) { /* bỏ qua stylesheet không đọc được */ }
        });
        return css.replace(/&/g, '&amp;').replace(/</g, '&lt;');
    }

    function exportPng() {
        if (busy) { return Promise.resolve(); }
        busy = true;
        toast('Đang tạo ảnh, vui lòng chờ...');

        return imagesReady().then(function () {
            // Ẩn các phần không đưa vào ảnh, đo kích thước thật
            root.classList.add('pf-exporting');
            var width = Math.ceil(root.scrollWidth);
            var height = Math.ceil(root.scrollHeight);
            var clone = root.cloneNode(true);
            root.classList.remove('pf-exporting');
            clone.style.width = width + 'px';
            clone.style.margin = '0';

            var imgs = Array.prototype.slice.call(clone.querySelectorAll('img'));
            return Promise.all(imgs.map(inlineImage)).then(function () {
                var xml = new XMLSerializer().serializeToString(clone);
                var svg = '<svg xmlns="http://www.w3.org/2000/svg" width="' + width + '" height="' + height + '">' +
                    '<foreignObject x="0" y="0" width="100%" height="100%">' +
                    '<div xmlns="http://www.w3.org/1999/xhtml"><style>' + collectCss() + '</style>' + xml + '</div>' +
                    '</foreignObject></svg>';
                return new Promise(function (resolve, reject) {
                    var image = new Image();
                    image.onload = function () { resolve(image); };
                    image.onerror = function () { reject(new Error('render')); };
                    image.src = 'data:image/svg+xml;charset=utf-8,' + encodeURIComponent(svg);
                }).then(function (image) {
                    var scale = Math.min(2, 16000 / height, 16000 / width);
                    var canvas = document.createElement('canvas');
                    canvas.width = Math.floor(width * scale);
                    canvas.height = Math.floor(height * scale);
                    var ctx = canvas.getContext('2d');
                    ctx.fillStyle = '#ffffff';
                    ctx.fillRect(0, 0, canvas.width, canvas.height);
                    ctx.scale(scale, scale);
                    ctx.drawImage(image, 0, 0, width, height);
                    return new Promise(function (resolve, reject) {
                        canvas.toBlob(function (blob) {
                            if (blob) { resolve(blob); } else { reject(new Error('blob')); }
                        }, 'image/png');
                    });
                });
            });
        }).then(function (blob) {
            var a = document.createElement('a');
            a.href = URL.createObjectURL(blob);
            a.download = (root.getAttribute('data-filename') || 'ho-so') + '.png';
            document.body.appendChild(a);
            a.click();
            document.body.removeChild(a);
            setTimeout(function () { URL.revokeObjectURL(a.href); }, 1500);
            toast('Đã tạo ảnh PNG.');
        }).catch(function () {
            toast('Trình duyệt này không tạo được ảnh. Hãy dùng "Lưu PDF" hoặc thử trình duyệt Chrome/Edge.', true);
        }).then(function () { busy = false; });
    }

    if (btnPdf) { btnPdf.addEventListener('click', exportPdf); }
    if (btnPng) { btnPng.addEventListener('click', exportPng); }

    // Tự chạy khi mở từ trang "Chia sẻ & xuất hồ sơ" (?export=pdf|png)
    var mode = root.getAttribute('data-export');
    window.addEventListener('load', function () {
        if (mode === 'pdf') { setTimeout(exportPdf, 300); }
        if (mode === 'png') { setTimeout(exportPng, 300); }
    });

    window.DevPortfolioExport = { exportPng: exportPng, exportPdf: exportPdf };
})();

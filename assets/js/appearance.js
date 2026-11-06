// assets/js/appearance.js - Xem trước giao diện hồ sơ ngay khi đổi mẫu hoặc màu
(function () {
    'use strict';
    var form = document.getElementById('appearance-form');
    var frame = document.getElementById('preview-frame');
    var color = document.getElementById('primary_color');
    if (!form || !frame || !color) { return; }

    var base = frame.getAttribute('data-base') || '';
    var timer = null;

    function currentTemplate() {
        var checked = form.querySelector('input[name="template"]:checked');
        return checked ? checked.value : 'classic';
    }

    function refresh() {
        var sep = base.indexOf('?') === -1 ? '?' : '&';
        frame.src = base + sep + 'tpl=' + encodeURIComponent(currentTemplate()) +
            '&color=' + encodeURIComponent(color.value);
    }

    function later() {
        if (timer) { clearTimeout(timer); }
        timer = setTimeout(refresh, 250);
    }

    form.querySelectorAll('input[name="template"]').forEach(function (r) {
        r.addEventListener('change', later);
    });
    color.addEventListener('input', later);
    form.querySelectorAll('.color-suggestion').forEach(function (btn) {
        btn.addEventListener('click', function () {
            color.value = btn.getAttribute('data-color');
            syncFromColor();
            later();
        });
    });
    var hue = document.getElementById('color-hue');
    var sat = document.getElementById('color-saturation');
    var lit = document.getElementById('color-lightness');
    var hexOut = document.getElementById('color-hex');
    if (!hue || !sat || !lit) { return; }

    function rgbToHsl(r, g, b) {
        r /= 255; g /= 255; b /= 255;
        var max = Math.max(r, g, b), min = Math.min(r, g, b), d = max - min;
        var h = 0, s = 0, l = (max + min) / 2;
        if (d !== 0) {
            s = d / (1 - Math.abs(2 * l - 1));
            switch (max) {
                case r: h = ((g - b) / d) % 6; break;
                case g: h = (b - r) / d + 2; break;
                default: h = (r - g) / d + 4;
            }
            h *= 60;
        }
        return [Math.round((h + 360) % 360), Math.round(s * 100), Math.round(l * 100)];
    }
    function hslToHex(h, s, l) {
        s /= 100; l /= 100;
        var a = s * Math.min(l, 1 - l);
        function channel(n) {
            var k = (n + h / 30) % 12;
            return Math.round(255 * (l - a * Math.max(-1, Math.min(k - 3, 9 - k, 1))));
        }
        return '#' + [channel(0), channel(8), channel(4)].map(function (n) {
            return n.toString(16).padStart(2, '0');
        }).join('');
    }
    function updateLabels() {
        document.getElementById('hue-value').textContent = hue.value + '°';
        document.getElementById('saturation-value').textContent = sat.value + '%';
        document.getElementById('lightness-value').textContent = lit.value + '%';
        hexOut.textContent = color.value.toUpperCase();
        form.querySelectorAll('.color-suggestion').forEach(function (btn) {
            var selected = btn.getAttribute('data-color').toLowerCase() === color.value.toLowerCase();
            btn.setAttribute('aria-pressed', selected ? 'true' : 'false');
        });
    }
    function syncFromColor() {
        var c = color.value.replace('#', '');
        var hsl = rgbToHsl(parseInt(c.slice(0, 2), 16), parseInt(c.slice(2, 4), 16), parseInt(c.slice(4, 6), 16));
        hue.value = hsl[0]; sat.value = hsl[1]; lit.value = hsl[2];
        updateLabels();
    }
    function syncFromSliders() {
        color.value = hslToHex(Number(hue.value), Number(sat.value), Number(lit.value));
        updateLabels();
        later();
    }
    [hue, sat, lit].forEach(function (slider) {
        slider.addEventListener('input', syncFromSliders);
    });
    color.addEventListener('input', syncFromColor);
    syncFromColor();
})();

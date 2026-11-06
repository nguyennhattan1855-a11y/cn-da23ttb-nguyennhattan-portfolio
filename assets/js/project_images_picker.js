/* Preview ảnh minh họa mới; giữ nguyên quy trình upload PHP hiện có. */
(function () {
    'use strict';
    const input = document.getElementById('images');
    const preview = document.getElementById('project-images-preview');
    const status = document.getElementById('project-images-selection-status');
    const error = document.getElementById('project-images-client-error');
    if (!input || !preview || !status || !error) return;

    const max = Number(input.dataset.maxImages) || 8;
    const existing = Number(input.dataset.existingCount) || 0;
    const maxBytes = 3 * 1024 * 1024;
    const allowed = ['image/jpeg', 'image/png', 'image/webp'];
    const selected = [];
    let objectUrls = [];

    function showError(message) {
        error.textContent = message;
        error.hidden = !message;
    }
    function syncInput() {
        const dt = new DataTransfer();
        selected.forEach(file => dt.items.add(file));
        input.files = dt.files;
    }
    function render() {
        objectUrls.forEach(URL.revokeObjectURL);
        objectUrls = [];
        preview.replaceChildren();
        selected.forEach((file, index) => {
            const item = document.createElement('div');
            item.className = 'project-image-preview-item';
            const img = document.createElement('img');
            const url = URL.createObjectURL(file);
            objectUrls.push(url);
            img.src = url;
            img.alt = 'Xem trước: ' + file.name;
            const label = document.createElement('span');
            label.className = 'project-image-preview-name';
            label.textContent = file.name;
            const remove = document.createElement('button');
            remove.type = 'button';
            remove.className = 'project-image-remove';
            remove.textContent = 'Bỏ ảnh';
            remove.setAttribute('aria-label', 'Bỏ ảnh ' + file.name);
            remove.addEventListener('click', function () {
                selected.splice(index, 1);
                syncInput();
                showError('');
                render();
            });
            item.append(img, label, remove);
            preview.appendChild(item);
        });
        status.textContent = selected.length
            ? `Đã chọn ${selected.length} ảnh mới. Tổng sau khi lưu: ${existing + selected.length}/${max} ảnh minh họa.`
            : `Chưa chọn ảnh mới. Hiện có ${existing}/${max} ảnh minh họa.`;
    }

    input.addEventListener('change', function () {
        const incoming = Array.from(input.files || []);
        // Khôi phục danh sách đã chọn vì thay đổi input có thể thay thế lựa chọn cũ.
        for (const file of incoming) {
            if (!allowed.includes(file.type)) {
                showError('Chỉ hỗ trợ ảnh JPG, PNG hoặc WEBP: ' + file.name);
                continue;
            }
            if (file.size > maxBytes) {
                showError('Ảnh vượt quá 3 MB: ' + file.name);
                continue;
            }
            if (existing + selected.length >= max) {
                showError('Mỗi dự án chỉ được tối đa ' + max + ' ảnh minh họa.');
                break;
            }
            if (selected.some(f => f.name === file.name && f.size === file.size && f.lastModified === file.lastModified)) continue;
            selected.push(file);
        }
        syncInput();
        render();
    });
    render();
})();

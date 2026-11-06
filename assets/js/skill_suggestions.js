/* Gợi ý tên kỹ năng dựa theo nhóm được chọn. Không gửi dữ liệu ra ngoài. */
document.addEventListener('DOMContentLoaded', function () {
  const category = document.getElementById('category');
  const name = document.getElementById('name');
  const list = document.getElementById('skill-suggestion-list');
  const hint = document.getElementById('skill-suggestion-hint');
  const source = document.getElementById('skill-suggestions-data');
  if (!category || !name || !list || !source) return;
  let data;
  try { data = JSON.parse(source.textContent); } catch (_) { return; }
  const initialCategory = category.value;
  const initialName = name.value;
  const normalize = s => String(s).trim().toLocaleLowerCase('vi');
  const groupFor = value => {
    for (const [key, names] of Object.entries(data)) {
      if (names.some(n => normalize(n) === normalize(value))) return key;
    }
    return null;
  };
  const close = () => { list.hidden = true; name.setAttribute('aria-expanded', 'false'); };
  const render = () => {
    const names = (data[category.value] || []).filter(n => normalize(n).includes(normalize(name.value))).slice(0, 12);
    list.replaceChildren();
    if (category.value === 'other') {
      hint.textContent = 'Nhóm Khác: bạn có thể tự nhập kỹ năng không thuộc các nhóm trên.';
      close(); return;
    }
    const knownGroup = name.value.trim() ? groupFor(name.value) : null;
    if (knownGroup && knownGroup !== category.value) {
      hint.textContent = 'Tên này thuộc nhóm khác. Hãy đổi nhóm cho phù hợp để lưu.';
    } else {
      hint.textContent = 'Chọn gợi ý bên dưới hoặc tự nhập kỹ năng mới. Danh sách không bắt buộc.';
    }
    names.forEach(value => {
      const b = document.createElement('button');
      b.type = 'button'; b.className = 'skill-suggestion'; b.setAttribute('role','option');
      b.textContent = value;
      b.addEventListener('click', () => { name.value = value; close(); name.focus(); hint.textContent='Đã chọn: '+value; });
      list.appendChild(b);
    });
    list.hidden = names.length === 0;
    name.setAttribute('aria-expanded', String(names.length > 0));
  };
  category.addEventListener('change', () => {
    // Khi sửa, giữ tên cũ chỉ nếu nhóm không thay đổi; đổi nhóm thì xóa tên để tránh sai nhóm.
    name.value = '';
    render();
  });
  name.addEventListener('focus', render);
  name.addEventListener('input', render);
  name.addEventListener('keydown', e => { if (e.key === 'Escape') close(); });
  document.addEventListener('click', e => { if (e.target !== name && !list.contains(e.target)) close(); });
  // Không thay đổi tên kỹ năng cũ nếu người dùng chỉ mở form Sửa.
  if (initialName && initialCategory === category.value) close();
});

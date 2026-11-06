// assets/js/app.js - Mã JavaScript dùng chung

// 1. Hỏi xác nhận trước khi gửi form có thuộc tính data-confirm (ví dụ: nút Xóa)
document.addEventListener('submit', function (event) {
    var form = event.target;
    if (form && form.getAttribute && form.getAttribute('data-confirm')) {
        if (!window.confirm(form.getAttribute('data-confirm'))) {
            event.preventDefault();
        }
    }
});

// 2. Ô tích có data-disables="id_o_nhap": tích vào thì vô hiệu hóa ô nhập đó
//    (dùng cho "Tôi đang làm việc tại đây" -> khóa ô ngày kết thúc)
document.addEventListener('DOMContentLoaded', function () {
    var boxes = document.querySelectorAll('input[type="checkbox"][data-disables]');
    boxes.forEach(function (box) {
        var target = document.getElementById(box.getAttribute('data-disables'));
        if (!target) { return; }
        function sync() { target.disabled = box.checked; }
        box.addEventListener('change', sync);
        sync();
    });
});

/* DevPortfolio – Gợi ý vị trí nghề nghiệp (vẫn cho phép nhập tự do).
 * Chỉ hoạt động tại trang dashboard/profile.php.
 */
(function () {
    'use strict';

    const root = document.querySelector('[data-position-combo]');
    if (!root) return;

    const input = root.querySelector('#headline');
    const list = root.querySelector('#position-options');
    if (!input || !list) return;

    const groups = [
        { label: 'Phát triển Web', roles: [
            'Web Developer', 'Frontend Developer', 'Backend Developer',
            'Full-stack Developer', 'PHP Developer', 'JavaScript Developer',
            'React Developer', 'Laravel Developer'
        ] },
        { label: 'Phát triển phần mềm', roles: [
            'Software Developer', 'Mobile Developer', 'Java Developer',
            'Python Developer', '.NET Developer'
        ] },
        { label: 'Kiểm thử và dữ liệu', roles: [
            'Software Tester', 'QA Engineer', 'Data Analyst', 'Database Developer'
        ] },
        { label: 'Hạ tầng và vận hành', roles: [
            'DevOps Engineer', 'Cloud Engineer', 'System Administrator'
        ] }
    ];

    // Gõ "Fresher PHP" hoặc "Junior Frontend" cũng có thể nhận gợi ý phù hợp.
    const levels = ['Intern', 'Fresher', 'Junior', 'Middle', 'Senior'];
    let options = [];
    let activeIndex = -1;
    let isOpen = false;

    function normalized(value) {
        return String(value).normalize('NFD')
            .replace(/[\u0300-\u036f]/g, '')
            .replace(/đ/gi, 'd')
            .toLocaleLowerCase('vi')
            .trim();
    }

    function close() {
        isOpen = false;
        list.hidden = true;
        input.setAttribute('aria-expanded', 'false');
        input.removeAttribute('aria-activedescendant');
        activeIndex = -1;
    }

    function selectOption(value) {
        input.value = value;
        // Kích hoạt event input cho các listener khác nếu có trong tương lai.
        input.dispatchEvent(new Event('change', { bubbles: true }));
        close();
        input.focus();
    }

    function markActive(index) {
        if (!options.length) return;
        activeIndex = (index + options.length) % options.length;
        options.forEach(function (option, idx) {
            option.setAttribute('aria-selected', idx === activeIndex ? 'true' : 'false');
        });
        const selected = options[activeIndex];
        input.setAttribute('aria-activedescendant', selected.id);
        selected.scrollIntoView({ block: 'nearest' });
    }

    function render() {
        const query = normalized(input.value);
        const level = levels.find(function (item) {
            const text = normalized(item);
            return query === text || query.startsWith(text + ' ');
        }) || '';

        // Khi gõ tên cấp độ, tạo gợi ý "Fresher + tên nghề" thay vì
        // phải lưu thêm hàng trăm tên công việc gần giống nhau.
        const effectiveGroups = groups.map(function (group) {
            return {
                label: group.label,
                roles: group.roles
                    .map(function (role) { return level ? level + ' ' + role : role; })
                    .filter(function (role) { return normalized(role).includes(query); })
            };
        }).filter(function (group) { return group.roles.length; });

        list.replaceChildren();
        options = [];
        activeIndex = -1;
        input.removeAttribute('aria-activedescendant');

        effectiveGroups.forEach(function (group) {
            const section = document.createElement('div');
            section.className = 'position-group';
            section.setAttribute('role', 'group');
            section.setAttribute('aria-label', group.label);

            const heading = document.createElement('div');
            heading.className = 'position-group-label';
            heading.textContent = group.label;
            section.appendChild(heading);

            group.roles.forEach(function (role) {
                const option = document.createElement('button');
                option.type = 'button';
                option.id = 'position-option-' + options.length;
                option.className = 'position-option';
                option.setAttribute('role', 'option');
                option.setAttribute('aria-selected', 'false');
                option.textContent = role;
                // Giữ focus trên ô nhập để tiếp tục thao tác bằng bàn phím.
                option.addEventListener('mousedown', function (event) {
                    event.preventDefault();
                });
                option.addEventListener('click', function () { selectOption(role); });
                options.push(option);
                section.appendChild(option);
            });
            list.appendChild(section);
        });

        if (!options.length) {
            const empty = document.createElement('div');
            empty.className = 'position-empty';
            empty.textContent = 'Không có gợi ý trùng khớp. Bạn vẫn có thể tự nhập vị trí này.';
            list.appendChild(empty);
        }

        isOpen = true;
        list.hidden = false;
        input.setAttribute('aria-expanded', 'true');
    }

    input.addEventListener('focus', render);
    input.addEventListener('input', render);
    input.addEventListener('keydown', function (event) {
        if (event.key === 'Escape' && isOpen) {
            close();
            event.preventDefault();
        } else if ((event.key === 'ArrowDown' || event.key === 'ArrowUp')) {
            if (!isOpen) render();
            if (options.length) {
                markActive(activeIndex + (event.key === 'ArrowDown' ? 1 : -1));
            }
            event.preventDefault();
        } else if (event.key === 'Enter' && isOpen && activeIndex >= 0) {
            selectOption(options[activeIndex].textContent);
            event.preventDefault();
        } else if (event.key === 'Tab') {
            close();
        }
    });

    document.addEventListener('pointerdown', function (event) {
        if (!root.contains(event.target)) close();
    });

    const form = input.closest('form');
    if (form) form.addEventListener('submit', close);
})();

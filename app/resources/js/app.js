import './bootstrap';
import QRCode from 'qrcode';

const syncInteractiveTitles = (root = document) => {
    root.querySelectorAll('button[aria-label]:not([title]), a[aria-label]:not([title]), [role="button"][aria-label]:not([title])').forEach((element) => {
        if (element.hasAttribute('data-tooltip')) {
            return;
        }

        const label = element.getAttribute('aria-label')?.trim();

        if (! label) {
            return;
        }

        element.setAttribute('title', label);
    });
};

syncInteractiveTitles();

const mobileChannelTrigger = document.querySelector('[data-mobile-channel-trigger]');
const mobileChannelDrawer = document.querySelector('[data-mobile-channel-drawer]');

if (mobileChannelTrigger && mobileChannelDrawer) {
    document.body.classList.add('has-mobile-channel-drawer');

    const closeButtons = mobileChannelDrawer.querySelectorAll('[data-mobile-channel-close]');
    const closeButton = mobileChannelDrawer.querySelector('[data-mobile-channel-close-primary]');
    const channelLinks = mobileChannelDrawer.querySelectorAll('[data-mobile-channel-link]');
    const desktopMediaQuery = window.matchMedia('(min-width: 768px)');
    let lastFocusedElement = null;

    const closeDrawer = () => {
        if (mobileChannelDrawer.hidden) {
            return;
        }

        mobileChannelDrawer.hidden = true;
        mobileChannelTrigger.setAttribute('aria-expanded', 'false');
        document.body.style.overflow = '';

        if (lastFocusedElement instanceof HTMLElement) {
            lastFocusedElement.focus();
        }
    };

    const openDrawer = () => {
        lastFocusedElement = document.activeElement instanceof HTMLElement
            ? document.activeElement
            : mobileChannelTrigger;

        mobileChannelDrawer.hidden = false;
        mobileChannelTrigger.setAttribute('aria-expanded', 'true');
        document.body.style.overflow = 'hidden';

        window.requestAnimationFrame(() => {
            if (closeButton instanceof HTMLElement) {
                closeButton.focus();
            }
        });
    };

    mobileChannelTrigger.addEventListener('click', () => {
        if (mobileChannelDrawer.hidden) {
            openDrawer();
            return;
        }

        closeDrawer();
    });

    closeButtons.forEach((button) => {
        button.addEventListener('click', closeDrawer);
    });

    channelLinks.forEach((link) => {
        link.addEventListener('click', closeDrawer);
    });

    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape') {
            closeDrawer();
        }
    });

    const handleDesktopBreakpointChange = (event) => {
        if (event.matches) {
            closeDrawer();
        }
    };

    if (typeof desktopMediaQuery.addEventListener === 'function') {
        desktopMediaQuery.addEventListener('change', handleDesktopBreakpointChange);
    } else if (typeof desktopMediaQuery.addListener === 'function') {
        desktopMediaQuery.addListener(handleDesktopBreakpointChange);
    }
}

// RSS 复制功能
document.querySelectorAll('[data-copy-rss]').forEach((button) => {
    button.addEventListener('click', async () => {
        const url = button.getAttribute('data-copy-rss');

        try {
            await navigator.clipboard.writeText(url);

            // 保存原始内容
            const originalHTML = button.innerHTML;

            // 显示成功提示
            button.innerHTML = `
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                </svg>
                <span>已复制</span>
            `;
            button.classList.remove('hover:bg-orange-100');
            button.classList.add('bg-green-100', 'text-green-700', 'border-green-300');

            // 2秒后恢复原状
            setTimeout(() => {
                button.innerHTML = originalHTML;
                button.classList.remove('bg-green-100', 'text-green-700', 'border-green-300');
                button.classList.add('hover:bg-orange-100');
            }, 2000);
        } catch (error) {
            // 复制失败时的提示
            const originalHTML = button.innerHTML;
            button.innerHTML = `
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
                <span>复制失败</span>
            `;
            button.classList.add('bg-red-100', 'text-red-700', 'border-red-300');

            setTimeout(() => {
                button.innerHTML = originalHTML;
                button.classList.remove('bg-red-100', 'text-red-700', 'border-red-300');
            }, 2000);
        }
    });
});

document.querySelectorAll('[data-qr-value]').forEach(async (element) => {
    const value = element.getAttribute('data-qr-value');

    if (! value) {
        return;
    }

    element.innerHTML = '';

    try {
        const dataUrl = await QRCode.toDataURL(value, {
            margin: 1,
            width: 260,
            color: {
                dark: '#020617',
                light: '#ffffff',
            },
        });

        const image = document.createElement('img');
        image.src = dataUrl;
        image.alt = 'QR Code';
        image.className = 'h-full w-full rounded-2xl';
        element.appendChild(image);
    } catch (error) {
        element.textContent = value;
    }
});

const statusNode = document.querySelector('[data-qr-status-url]');

if (statusNode) {
    const statusText = document.querySelector('[data-qr-status-text]');
    const statusUrl = statusNode.getAttribute('data-qr-status-url');

    const timer = window.setInterval(async () => {
        try {
            const response = await fetch(statusUrl, {
                headers: {
                    Accept: 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
            });

            const payload = await response.json();

            if (payload.status === 'consumed' && payload.redirect) {
                if (statusText) {
                    statusText.textContent = '扫码成功，正在跳转…';
                }

                window.clearInterval(timer);
                window.location.href = payload.redirect;
                return;
            }

            if (payload.status === 'expired') {
                if (statusText) {
                    statusText.textContent = '二维码已过期，请返回重新生成。';
                }

                window.clearInterval(timer);
                return;
            }

            if (payload.status === 'approved' && statusText) {
                statusText.textContent = '已确认授权，正在建立登录会话…';
            }
        } catch (error) {
            if (statusText) {
                statusText.textContent = '状态轮询失败，请稍后刷新页面。';
            }

            window.clearInterval(timer);
        }
    }, 2000);
}

const themeRoot = document.documentElement;
const normalizeTimeValue = (value, fallback) => {
    if (! value) {
        return fallback;
    }

    const trimmed = value.trim();
    if (! /^([01]\d|2[0-3]):[0-5]\d$/.test(trimmed)) {
        return fallback;
    }

    return trimmed;
};

const toMinutes = (timeValue) => {
    const [hours, minutes] = timeValue.split(':').map(Number);
    return (hours * 60) + minutes;
};

const resolveTheme = (mode, dayStart, nightStart, now) => {
    if (mode === 'light' || mode === 'dark') {
        return mode;
    }

    const dayMinutes = toMinutes(dayStart);
    const nightMinutes = toMinutes(nightStart);
    const currentMinutes = now.getHours() * 60 + now.getMinutes();

    if (dayMinutes === nightMinutes) {
        return 'light';
    }

    if (dayMinutes < nightMinutes) {
        return currentMinutes >= dayMinutes && currentMinutes < nightMinutes ? 'light' : 'dark';
    }

    return currentMinutes >= dayMinutes || currentMinutes < nightMinutes ? 'light' : 'dark';
};

const applyThemeSchedule = () => {
    if (! themeRoot) {
        return;
    }

    const mode = (themeRoot.dataset.themeMode || 'auto').toLowerCase();
    const dayStart = normalizeTimeValue(themeRoot.dataset.themeDayStart, '07:00');
    const nightStart = normalizeTimeValue(themeRoot.dataset.themeNightStart, '19:00');
    const resolvedTheme = resolveTheme(mode, dayStart, nightStart, new Date());
    const currentTheme = themeRoot.getAttribute('data-theme');

    if (resolvedTheme !== currentTheme) {
        themeRoot.setAttribute('data-theme', resolvedTheme);
    }

    if (themeRoot.style.colorScheme !== resolvedTheme) {
        themeRoot.style.colorScheme = resolvedTheme;
    }
};

applyThemeSchedule();
window.setInterval(applyThemeSchedule, 60 * 1000);

const adminUsersPage = document.querySelector('[data-admin-users-page]');

if (adminUsersPage) {
    const userCards = Array.from(adminUsersPage.querySelectorAll('[data-user-card]'));
    const selectableCheckboxes = Array.from(adminUsersPage.querySelectorAll('[data-bulk-select-item]'));
    const selectAllCheckbox = adminUsersPage.querySelector('[data-bulk-select-all]');
    const selectedCount = adminUsersPage.querySelector('[data-bulk-selected-count]');
    const bulkDeleteButton = adminUsersPage.querySelector('[data-bulk-delete-submit]');
    const clearSelectionButton = adminUsersPage.querySelector('[data-bulk-clear-selection]');

    const syncCardExpansion = (card, expanded) => {
        const panel = card.querySelector('[data-user-card-panel]');
        const toggle = card.querySelector('[data-user-card-toggle]');
        const expandIcon = toggle?.querySelector('[data-toggle-icon-expand]');
        const collapseIcon = toggle?.querySelector('[data-toggle-icon-collapse]');
        const toggleText = toggle?.querySelector('[data-toggle-text]');

        if (! panel || ! toggle) {
            return;
        }

        card.dataset.expanded = expanded ? 'true' : 'false';
        panel.hidden = ! expanded;
        card.classList.toggle('user-management-card-collapsed', ! expanded);
        toggle.setAttribute('aria-expanded', expanded ? 'true' : 'false');

        const accessibleLabel = expanded
            ? toggle.getAttribute('data-label-collapse')
            : toggle.getAttribute('data-label-expand');

        if (accessibleLabel) {
            toggle.setAttribute('aria-label', accessibleLabel);
            toggle.setAttribute('title', accessibleLabel.replace(/：.+$/, ''));
        }

        expandIcon?.classList.toggle('hidden', expanded);
        collapseIcon?.classList.toggle('hidden', ! expanded);

        if (toggleText) {
            toggleText.textContent = expanded ? '收起用户' : '展开用户';
        }
    };

    const syncSelectionState = () => {
        const checkedItems = selectableCheckboxes.filter((checkbox) => checkbox.checked);
        const selectableItems = selectableCheckboxes.filter((checkbox) => ! checkbox.disabled);
        const checkedCount = checkedItems.length;

        if (selectedCount) {
            selectedCount.textContent = String(checkedCount);
        }

        if (selectAllCheckbox instanceof HTMLInputElement) {
            selectAllCheckbox.checked = selectableItems.length > 0 && checkedCount === selectableItems.length;
            selectAllCheckbox.indeterminate = checkedCount > 0 && checkedCount < selectableItems.length;
        }

        if (bulkDeleteButton instanceof HTMLButtonElement) {
            bulkDeleteButton.disabled = checkedCount === 0;
        }

        userCards.forEach((card) => {
            const checkbox = card.querySelector('[data-bulk-select-item]');
            card.classList.toggle('user-management-card-selected', checkbox instanceof HTMLInputElement && checkbox.checked);
        });
    };

    userCards.forEach((card) => {
        syncCardExpansion(card, card.getAttribute('data-initial-expanded') === 'true');
    });

    adminUsersPage.querySelectorAll('[data-user-card-toggle]').forEach((button) => {
        button.addEventListener('click', () => {
            const card = button.closest('[data-user-card]');

            if (! card) {
                return;
            }

            syncCardExpansion(card, card.dataset.expanded !== 'true');
        });
    });

    selectableCheckboxes.forEach((checkbox) => {
        checkbox.addEventListener('change', syncSelectionState);
    });

    if (selectAllCheckbox instanceof HTMLInputElement) {
        selectAllCheckbox.addEventListener('change', () => {
            selectableCheckboxes
                .filter((checkbox) => ! checkbox.disabled)
                .forEach((checkbox) => {
                    checkbox.checked = selectAllCheckbox.checked;
                });

            syncSelectionState();
        });
    }

    clearSelectionButton?.addEventListener('click', () => {
        selectableCheckboxes.forEach((checkbox) => {
            checkbox.checked = false;
        });

        if (selectAllCheckbox instanceof HTMLInputElement) {
            selectAllCheckbox.checked = false;
            selectAllCheckbox.indeterminate = false;
        }

        syncSelectionState();
    });

    syncSelectionState();
}

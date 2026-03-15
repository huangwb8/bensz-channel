import './bootstrap';
import QRCode from 'qrcode';
import { initMarkdownImageUploads, initMarkdownVideoEmbeds } from './markdown-image-upload';

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

initMarkdownImageUploads();
initMarkdownVideoEmbeds();

const mobileChannelTrigger = document.querySelector('[data-mobile-channel-trigger]');
const mobileChannelDrawer = document.querySelector('[data-mobile-channel-drawer]');

if (mobileChannelTrigger && mobileChannelDrawer) {
    document.body.classList.add('has-mobile-channel-drawer');

    const closeButtons = mobileChannelDrawer.querySelectorAll('[data-mobile-channel-close]');
    const closeButton = mobileChannelDrawer.querySelector('[data-mobile-channel-close-primary]');
    const channelLinks = mobileChannelDrawer.querySelectorAll('[data-mobile-channel-link]');
    const desktopMediaQuery = window.matchMedia('(min-width: 768px)');
    let lastFocusedElement = null;

    const setDrawerState = (isOpen) => {
        mobileChannelDrawer.hidden = ! isOpen;
        mobileChannelDrawer.setAttribute('aria-hidden', isOpen ? 'false' : 'true');
        mobileChannelTrigger.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
        document.body.style.overflow = isOpen ? 'hidden' : '';
    };

    const closeDrawer = () => {
        if (mobileChannelDrawer.hidden) {
            return;
        }

        setDrawerState(false);

        if (lastFocusedElement instanceof HTMLElement) {
            lastFocusedElement.focus();
        }
    };

    const openDrawer = () => {
        lastFocusedElement = document.activeElement instanceof HTMLElement
            ? document.activeElement
            : mobileChannelTrigger;

        setDrawerState(true);

        window.requestAnimationFrame(() => {
            if (closeButton instanceof HTMLElement) {
                closeButton.focus();
            }
        });
    };

    const toggleDrawer = (event) => {
        // 防止默认行为和事件冒泡
        if (event) {
            event.preventDefault();
            event.stopPropagation();
        }

        if (mobileChannelDrawer.hidden) {
            openDrawer();
        } else {
            closeDrawer();
        }
    };

    const handleCloseDrawer = (event) => {
        if (event) {
            event.preventDefault();
            event.stopPropagation();
        }

        closeDrawer();
    };

    mobileChannelTrigger.addEventListener('click', toggleDrawer);

    closeButtons.forEach((button) => {
        button.addEventListener('click', handleCloseDrawer);
    });

    channelLinks.forEach((link) => {
        link.addEventListener('click', closeDrawer);
    });

    setDrawerState(false);

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

const userMenuShells = Array.from(document.querySelectorAll('[data-user-menu-shell]'));

if (userMenuShells.length > 0) {
    const hoverCapableMediaQuery = window.matchMedia('(hover: hover) and (pointer: fine)');

    const closeAllUserMenus = (exceptShell = null) => {
        userMenuShells.forEach((shell) => {
            if (shell === exceptShell) {
                return;
            }

            const trigger = shell.querySelector('[data-user-menu-trigger]');
            const panel = shell.querySelector('[data-user-menu-panel]');

            if (! trigger || ! panel) {
                return;
            }

            panel.hidden = true;
            panel.setAttribute('aria-hidden', 'true');
            trigger.setAttribute('aria-expanded', 'false');
        });
    };

    userMenuShells.forEach((shell, index) => {
        const trigger = shell.querySelector('[data-user-menu-trigger]');
        const panel = shell.querySelector('[data-user-menu-panel]');
        const menuItems = Array.from(shell.querySelectorAll('[role="menuitem"]'));

        if (! (trigger instanceof HTMLElement) || ! (panel instanceof HTMLElement)) {
            return;
        }

        if (! panel.id) {
            panel.id = `user-menu-panel-${index + 1}`;
            trigger.setAttribute('aria-controls', panel.id);
        }

        // 延迟关闭定时器
        let closeTimer = null;

        const setMenuState = (isOpen) => {
            panel.hidden = ! isOpen;
            panel.setAttribute('aria-hidden', isOpen ? 'false' : 'true');
            trigger.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
        };

        const closeMenu = () => {
            // 清除任何待处理的关闭定时器
            if (closeTimer) {
                clearTimeout(closeTimer);
                closeTimer = null;
            }
            setMenuState(false);
        };

        const openMenu = () => {
            // 清除任何待处理的关闭定时器
            if (closeTimer) {
                clearTimeout(closeTimer);
                closeTimer = null;
            }
            closeAllUserMenus(shell);
            setMenuState(true);
        };

        const scheduleClose = () => {
            // 清除之前的定时器
            if (closeTimer) {
                clearTimeout(closeTimer);
            }
            // 延迟 300ms 关闭，给用户足够时间移动鼠标
            closeTimer = setTimeout(() => {
                closeMenu();
            }, 300);
        };

        const cancelScheduledClose = () => {
            if (closeTimer) {
                clearTimeout(closeTimer);
                closeTimer = null;
            }
        };

        trigger.addEventListener('click', (event) => {
            event.preventDefault();
            event.stopPropagation();

            if (panel.hidden) {
                openMenu();

                return;
            }

            closeMenu();
        });

        trigger.addEventListener('keydown', (event) => {
            if (event.key !== 'ArrowDown' && event.key !== 'Enter' && event.key !== ' ') {
                return;
            }

            event.preventDefault();
            openMenu();
            menuItems[0]?.focus();
        });

        panel.addEventListener('keydown', (event) => {
            if (event.key !== 'Escape') {
                return;
            }

            event.preventDefault();
            closeMenu();
            trigger.focus();
        });

        menuItems.forEach((item) => {
            item.addEventListener('click', () => {
                closeMenu();
            });
        });

        shell.addEventListener('focusout', (event) => {
            const nextFocusedElement = event.relatedTarget;

            if (nextFocusedElement instanceof Node && shell.contains(nextFocusedElement)) {
                return;
            }

            closeMenu();
        });

        if (hoverCapableMediaQuery.matches) {
            shell.addEventListener('mouseenter', () => {
                // 取消任何待处理的关闭操作
                cancelScheduledClose();
                openMenu();
            });

            shell.addEventListener('mouseleave', () => {
                // 延迟关闭，而不是立即关闭
                scheduleClose();
            });
        }

        setMenuState(false);
    });

    document.addEventListener('click', (event) => {
        const clickedShell = event.target instanceof Element
            ? event.target.closest('[data-user-menu-shell]')
            : null;

        closeAllUserMenus(clickedShell);
    });

    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape') {
            closeAllUserMenus();
        }
    });
}

// RSS 复制功能
document.querySelectorAll('[data-copy-rss]').forEach((button) => {
    button.addEventListener('click', async () => {
        const url = button.getAttribute('data-copy-rss');

        try {
            await navigator.clipboard.writeText(url);

            // 显示成功提示 Toast
            showCopyToast(button, 'success', '已复制到剪贴板');

            // 按钮状态变化
            button.classList.add('rss-copy-success');
            setTimeout(() => {
                button.classList.remove('rss-copy-success');
            }, 2000);
        } catch (error) {
            // 显示失败提示 Toast
            showCopyToast(button, 'error', '复制失败，请重试');

            // 按钮状态变化
            button.classList.add('rss-copy-error');
            setTimeout(() => {
                button.classList.remove('rss-copy-error');
            }, 2000);
        }
    });
});

// Toast 提示函数
function showCopyToast(button, type, message) {
    // 创建 Toast 元素
    const toast = document.createElement('div');
    toast.className = `copy-toast copy-toast-${type}`;

    // 图标 SVG
    const icon = type === 'success'
        ? `<svg class="copy-toast-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
             <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
           </svg>`
        : `<svg class="copy-toast-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
             <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
           </svg>`;

    toast.innerHTML = `
        ${icon}
        <span class="copy-toast-text">${message}</span>
    `;

    // 定位 Toast（相对于按钮）
    const rect = button.getBoundingClientRect();
    toast.style.position = 'fixed';
    toast.style.left = `${rect.left + rect.width / 2}px`;
    toast.style.top = `${rect.top - 10}px`;

    // 添加到页面
    document.body.appendChild(toast);

    // 触发动画
    requestAnimationFrame(() => {
        toast.classList.add('copy-toast-show');
    });

    // 2秒后移除
    setTimeout(() => {
        toast.classList.remove('copy-toast-show');
        setTimeout(() => {
            document.body.removeChild(toast);
        }, 300);
    }, 2000);
}

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

const initializeBulkSelection = (root, options = {}) => {
    if (! root) {
        return;
    }

    const selectableCheckboxes = Array.from(root.querySelectorAll('[data-bulk-select-item]'));

    if (selectableCheckboxes.length === 0) {
        return;
    }

    const selectAllCheckbox = root.querySelector('[data-bulk-select-all]');
    const selectedCount = root.querySelector('[data-bulk-selected-count]');
    const bulkDeleteButton = root.querySelector('[data-bulk-delete-submit]');
    const clearSelectionButton = root.querySelector('[data-bulk-clear-selection]');
    const cardSelector = options.cardSelector || '[data-bulk-select-card]';
    const selectedClass = options.selectedClass || 'bulk-select-card-selected';
    const cards = Array.from(root.querySelectorAll(cardSelector));

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

        cards.forEach((card) => {
            const checkbox = card.querySelector('[data-bulk-select-item]');
            card.classList.toggle(selectedClass, checkbox instanceof HTMLInputElement && checkbox.checked);
        });
    };

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
};

const adminUsersPage = document.querySelector('[data-admin-users-page]');

if (adminUsersPage) {
    const userCards = Array.from(adminUsersPage.querySelectorAll('[data-user-card]'));

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

    initializeBulkSelection(adminUsersPage, {
        cardSelector: '[data-user-card]',
        selectedClass: 'user-management-card-selected',
    });
}

initializeBulkSelection(document.querySelector('[data-admin-articles-page]'));

// 评论回复面板切换（事件委托，支持页面内所有评论项）
document.addEventListener('click', (event) => {
    // 展开/收起回复表单
    const replyToggle = event.target instanceof Element
        ? event.target.closest('[data-reply-toggle]')
        : null;

    if (replyToggle) {
        const commentCard = replyToggle.closest('[data-comment-card]');

        if (! commentCard) {
            return;
        }

        const panel = commentCard.querySelector('[data-reply-panel]');

        if (! panel) {
            return;
        }

        const isExpanded = replyToggle.getAttribute('aria-expanded') === 'true';

        panel.hidden = isExpanded;
        replyToggle.setAttribute('aria-expanded', isExpanded ? 'false' : 'true');

        if (! isExpanded) {
            const textarea = panel.querySelector('textarea');

            if (textarea instanceof HTMLTextAreaElement) {
                textarea.focus();
            }
        }

        return;
    }

    // 取消回复
    const replyCancel = event.target instanceof Element
        ? event.target.closest('[data-reply-cancel]')
        : null;

    if (replyCancel) {
        const commentCard = replyCancel.closest('[data-comment-card]');

        if (! commentCard) {
            return;
        }

        const panel = commentCard.querySelector('[data-reply-panel]');
        const toggle = commentCard.querySelector('[data-reply-toggle]');

        if (panel) {
            panel.hidden = true;
        }

        if (toggle) {
            toggle.setAttribute('aria-expanded', 'false');
        }
    }
});

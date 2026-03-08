import './bootstrap';
import QRCode from 'qrcode';

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

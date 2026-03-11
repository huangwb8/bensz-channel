const MIME_EXTENSION_MAP = Object.freeze({
    'image/avif': 'avif',
    'image/gif': 'gif',
    'image/jpeg': 'jpg',
    'image/jpg': 'jpg',
    'image/png': 'png',
    'image/webp': 'webp',
});

export const setMarkdownUploadStatus = (statusNode, state, message) => {
    if (! statusNode) {
        return;
    }

    statusNode.hidden = ! message;
    statusNode.dataset.state = state;
    statusNode.textContent = message;
};

export const insertMarkdownAtCursor = (textarea, text) => {
    const start = textarea.selectionStart ?? textarea.value.length;
    const end = textarea.selectionEnd ?? textarea.value.length;
    const prefix = start > 0 && ! textarea.value.slice(0, start).endsWith('\n') ? '\n' : '';
    const suffix = end < textarea.value.length && ! textarea.value.slice(end).startsWith('\n') ? '\n' : '';
    const snippet = `${prefix}${text}${suffix}`;

    textarea.setRangeText(snippet, start, end, 'end');
    textarea.dispatchEvent(new Event('input', { bubbles: true }));
    textarea.focus();
};

const clipboardItemImageFiles = (event) => Array.from(event.clipboardData?.items ?? [])
    .filter((item) => item.kind === 'file' && item.type.startsWith('image/'))
    .map((item) => item.getAsFile())
    .filter((file) => file instanceof File);

const clipboardFileListImageFiles = (event) => Array.from(event.clipboardData?.files ?? [])
    .filter((file) => file instanceof File && file.type.startsWith('image/'));

export const clipboardImageFiles = (event) => {
    const filesFromItems = clipboardItemImageFiles(event);

    if (filesFromItems.length > 0) {
        return filesFromItems;
    }

    return clipboardFileListImageFiles(event);
};

export const resolvePastedImageFileName = (file, index = 0) => {
    const originalName = typeof file?.name === 'string' ? file.name.trim() : '';

    if (originalName !== '' && /\.[a-z0-9]+$/i.test(originalName)) {
        return originalName;
    }

    const extension = MIME_EXTENSION_MAP[file?.type] ?? 'png';

    return `pasted-image-${Date.now()}-${index + 1}.${extension}`;
};

export const uploadPastedImages = async (textarea, files, options = {}) => {
    const uploadUrl = textarea.dataset.imageUploadUrl;
    const context = textarea.dataset.uploadContext ?? 'comment';
    const uploadLabel = textarea.dataset.uploadLabel ?? '图片';
    const statusNode = textarea.closest('[data-markdown-upload-shell]')?.querySelector('[data-image-upload-status]');
    const axiosInstance = options.axiosInstance ?? window.axios;
    const timeoutFn = options.timeoutFn ?? window.setTimeout.bind(window);

    if (! uploadUrl || files.length === 0) {
        return;
    }

    const uploadedMarkdown = [];

    for (const [index, file] of files.entries()) {
        setMarkdownUploadStatus(statusNode, 'uploading', `正在上传${uploadLabel} ${index + 1}/${files.length}…`);

        const formData = new FormData();
        formData.append('image', file, resolvePastedImageFileName(file, index));
        formData.append('context', context);

        const response = await axiosInstance.post(uploadUrl, formData);

        uploadedMarkdown.push(response.data.markdown);
    }

    insertMarkdownAtCursor(textarea, uploadedMarkdown.join('\n\n'));
    setMarkdownUploadStatus(statusNode, 'success', `${uploadLabel}上传完成，已插入 Markdown 链接。`);

    timeoutFn(() => {
        setMarkdownUploadStatus(statusNode, 'idle', '');
    }, 2400);
};

const bindMarkdownImageUpload = (textarea) => {
    textarea.addEventListener('paste', async (event) => {
        const files = clipboardImageFiles(event);

        if (files.length === 0) {
            return;
        }

        event.preventDefault();

        try {
            await uploadPastedImages(textarea, files);
        } catch (error) {
            const statusNode = textarea.closest('[data-markdown-upload-shell]')?.querySelector('[data-image-upload-status]');
            const responseMessage = error?.response?.data?.message;

            setMarkdownUploadStatus(statusNode, 'error', responseMessage || '图片上传失败，请稍后重试。');
        }
    });
};

export const initMarkdownImageUploads = (root = document) => {
    root.querySelectorAll('textarea[data-image-upload-url]').forEach(bindMarkdownImageUpload);
};

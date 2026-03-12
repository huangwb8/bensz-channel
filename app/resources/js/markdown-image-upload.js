const MIME_EXTENSION_MAP = Object.freeze({
    'image/avif': 'avif',
    'image/gif': 'gif',
    'image/jpeg': 'jpg',
    'image/jpg': 'jpg',
    'image/png': 'png',
    'image/webp': 'webp',
    'video/mp4': 'mp4',
    'video/ogg': 'ogg',
    'video/webm': 'webm',
});

const MEDIA_TYPE_CONFIG = Object.freeze({
    image: {
        datasetKey: 'imageUploadUrl',
        labelKey: 'imageUploadLabel',
        fallbackLabelKey: 'uploadLabel',
        defaultLabel: '图片',
        fieldName: 'image',
        prefix: 'pasted-image',
    },
    video: {
        datasetKey: 'videoUploadUrl',
        labelKey: 'videoUploadLabel',
        defaultLabel: '视频',
        fieldName: 'video',
        prefix: 'pasted-video',
    },
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

const clipboardItemMediaFiles = (event) => Array.from(event.clipboardData?.items ?? [])
    .filter((item) => item.kind === 'file' && (item.type.startsWith('image/') || item.type.startsWith('video/')))
    .map((item) => item.getAsFile())
    .filter((file) => file instanceof File);

const clipboardFileListMediaFiles = (event) => Array.from(event.clipboardData?.files ?? [])
    .filter((file) => file instanceof File && (file.type.startsWith('image/') || file.type.startsWith('video/')));

const resolveMediaKind = (file) => {
    if (file?.type?.startsWith('video/')) {
        return 'video';
    }

    if (file?.type?.startsWith('image/')) {
        return 'image';
    }

    return null;
};

const resolveStatusNode = (textarea) => textarea
    .closest('[data-markdown-upload-shell]')
    ?.querySelector('[data-markdown-upload-status], [data-image-upload-status]');

export const clipboardImageFiles = (event) => {
    return clipboardMediaFiles(event).filter((file) => resolveMediaKind(file) === 'image');
};

export const clipboardMediaFiles = (event) => {
    const filesFromItems = clipboardItemMediaFiles(event);

    if (filesFromItems.length > 0) {
        return filesFromItems;
    }

    return clipboardFileListMediaFiles(event);
};

export const resolvePastedImageFileName = (file, index = 0) => {
    return resolvePastedMediaFileName(file, index);
};

export const resolvePastedMediaFileName = (file, index = 0) => {
    const originalName = typeof file?.name === 'string' ? file.name.trim() : '';

    if (originalName !== '' && /\.[a-z0-9]+$/i.test(originalName)) {
        return originalName;
    }

    const mediaKind = resolveMediaKind(file) ?? 'image';
    const extension = MIME_EXTENSION_MAP[file?.type] ?? (mediaKind === 'video' ? 'mp4' : 'png');
    const prefix = MEDIA_TYPE_CONFIG[mediaKind].prefix;

    return `${prefix}-${Date.now()}-${index + 1}.${extension}`;
};

const supportedTextareaMediaFiles = (textarea, files) => files.filter((file) => {
    const mediaKind = resolveMediaKind(file);

    if (! mediaKind) {
        return false;
    }

    return Boolean(textarea.dataset[MEDIA_TYPE_CONFIG[mediaKind].datasetKey]);
});

const resolveUploadLabel = (textarea, mediaKind) => {
    const config = MEDIA_TYPE_CONFIG[mediaKind];

    if (textarea.dataset[config.labelKey]) {
        return textarea.dataset[config.labelKey];
    }

    if (config.fallbackLabelKey && textarea.dataset[config.fallbackLabelKey]) {
        return textarea.dataset[config.fallbackLabelKey];
    }

    return config.defaultLabel;
};

export const uploadPastedMedia = async (textarea, files, options = {}) => {
    const context = textarea.dataset.uploadContext ?? 'comment';
    const statusNode = resolveStatusNode(textarea);
    const axiosInstance = options.axiosInstance ?? window.axios;
    const timeoutFn = options.timeoutFn ?? window.setTimeout.bind(window);
    const uploadableFiles = supportedTextareaMediaFiles(textarea, files);

    if (uploadableFiles.length === 0) {
        return;
    }

    const uploadedMarkdown = [];

    for (const [index, file] of uploadableFiles.entries()) {
        const mediaKind = resolveMediaKind(file);

        if (! mediaKind) {
            continue;
        }

        const config = MEDIA_TYPE_CONFIG[mediaKind];
        const uploadUrl = textarea.dataset[config.datasetKey];

        if (! uploadUrl) {
            continue;
        }

        const uploadLabel = resolveUploadLabel(textarea, mediaKind);

        setMarkdownUploadStatus(statusNode, 'uploading', `正在上传${uploadLabel} ${index + 1}/${uploadableFiles.length}…`);

        const formData = new FormData();
        formData.append(config.fieldName, file, resolvePastedMediaFileName(file, index));
        formData.append('context', context);

        const response = await axiosInstance.post(uploadUrl, formData);

        uploadedMarkdown.push(response.data.markdown);
    }

    insertMarkdownAtCursor(textarea, uploadedMarkdown.join('\n\n'));
    setMarkdownUploadStatus(statusNode, 'success', '媒体上传完成，已插入 Markdown 片段。');

    timeoutFn(() => {
        setMarkdownUploadStatus(statusNode, 'idle', '');
    }, 2400);
};

export const uploadPastedImages = async (textarea, files, options = {}) => {
    await uploadPastedMedia(
        textarea,
        files.filter((file) => resolveMediaKind(file) === 'image'),
        options,
    );
};

const bindMarkdownImageUpload = (textarea) => {
    if (textarea.dataset.markdownUploadBound === 'true') {
        return;
    }

    textarea.dataset.markdownUploadBound = 'true';

    textarea.addEventListener('paste', async (event) => {
        const files = supportedTextareaMediaFiles(textarea, clipboardMediaFiles(event));

        if (files.length === 0) {
            return;
        }

        event.preventDefault();

        try {
            await uploadPastedMedia(textarea, files);
        } catch (error) {
            const statusNode = resolveStatusNode(textarea);
            const responseMessage = error?.response?.data?.message;

            setMarkdownUploadStatus(statusNode, 'error', responseMessage || '媒体上传失败，请稍后重试。');
        }
    });
};

export const initMarkdownImageUploads = (root = document) => {
    root
        .querySelectorAll('textarea[data-image-upload-url], textarea[data-video-upload-url]')
        .forEach(bindMarkdownImageUpload);
};

const requestFullscreen = (video) => {
    if (typeof video.requestFullscreen === 'function') {
        return video.requestFullscreen();
    }

    if (typeof video.webkitRequestFullscreen === 'function') {
        return video.webkitRequestFullscreen();
    }

    if (typeof video.mozRequestFullScreen === 'function') {
        return video.mozRequestFullScreen();
    }

    if (typeof video.msRequestFullscreen === 'function') {
        return video.msRequestFullscreen();
    }

    return undefined;
};

export const initMarkdownVideoEmbeds = (root = document) => {
    root.querySelectorAll('.nextcloud-video video').forEach((video) => {
        if (video.dataset.markdownVideoBound === 'true') {
            return;
        }

        video.dataset.markdownVideoBound = 'true';
        video.addEventListener('dblclick', () => {
            requestFullscreen(video);
        });
    });
};

import assert from 'node:assert/strict';
import test from 'node:test';

import {
    clipboardMediaFiles,
    clipboardImageFiles,
    resolvePastedImageFileName,
    resolvePastedMediaFileName,
    uploadPastedMedia,
    uploadPastedImages,
} from './markdown-image-upload.js';

const createTextarea = () => {
    const statusNode = {
        hidden: true,
        dataset: {},
        textContent: '',
    };

    const textarea = {
        value: '',
        selectionStart: 0,
        selectionEnd: 0,
        dataset: {
            imageUploadUrl: '/uploads/images',
            videoUploadUrl: '/uploads/videos',
            uploadContext: 'article',
            uploadLabel: '图片',
        },
        closest: () => ({
            querySelector: () => statusNode,
        }),
        setRangeText(replacement, start, end, selectionMode) {
            this.value = `${this.value.slice(0, start)}${replacement}${this.value.slice(end)}`;
            const cursor = start + replacement.length;

            if (selectionMode === 'end') {
                this.selectionStart = cursor;
                this.selectionEnd = cursor;
            }
        },
        dispatchEvent() {},
        focus() {},
    };

    return { textarea, statusNode };
};

test('clipboardImageFiles falls back to clipboardData.files when items are unavailable', () => {
    const textFile = new File(['hello'], 'notes.txt', { type: 'text/plain' });
    const imageFile = new File(['jpeg'], '', { type: 'image/jpeg' });

    const files = clipboardImageFiles({
        clipboardData: {
            items: [],
            files: [textFile, imageFile],
        },
    });

    assert.deepEqual(files, [imageFile]);
});

test('clipboardMediaFiles keeps pasted video blobs alongside images', () => {
    const imageFile = new File(['png'], 'shot.png', { type: 'image/png' });
    const videoFile = new File(['mp4'], 'demo.mp4', { type: 'video/mp4' });

    const files = clipboardMediaFiles({
        clipboardData: {
            items: [],
            files: [imageFile, videoFile],
        },
    });

    assert.deepEqual(files, [imageFile, videoFile]);
});

test('resolvePastedImageFileName creates a jpg filename for clipboard jpeg blobs', () => {
    const file = new File(['jpeg'], '', { type: 'image/jpeg' });
    const fileName = resolvePastedImageFileName(file, 0);

    assert.match(fileName, /^pasted-image-\d+-1\.jpg$/);
});

test('resolvePastedMediaFileName creates an mp4 filename for clipboard video blobs', () => {
    const file = new File(['mp4'], '', { type: 'video/mp4' });
    const fileName = resolvePastedMediaFileName(file, 1);

    assert.match(fileName, /^pasted-video-\d+-2\.mp4$/);
});

test('uploadPastedImages posts FormData without forcing multipart header', async () => {
    const { textarea, statusNode } = createTextarea();
    const calls = [];

    const axiosInstance = {
        async post(url, formData, config) {
            calls.push({ url, formData, config });

            return {
                data: {
                    markdown: '![粘贴图片](/storage/media/article/2026/03/pasted-image.jpg)',
                },
            };
        },
    };

    await uploadPastedImages(textarea, [new File(['jpeg'], '', { type: 'image/jpeg' })], {
        axiosInstance,
        timeoutFn: (callback) => callback(),
    });

    assert.equal(calls.length, 1);
    assert.equal(calls[0].url, '/uploads/images');
    assert.equal(calls[0].config, undefined);
    assert.equal(calls[0].formData.get('context'), 'article');
    assert.match(calls[0].formData.get('image').name, /^pasted-image-\d+-1\.jpg$/);
    assert.equal(textarea.value, '![粘贴图片](/storage/media/article/2026/03/pasted-image.jpg)');
    assert.equal(statusNode.dataset.state, 'idle');
    assert.equal(statusNode.hidden, true);
});

test('uploadPastedMedia uploads image and video files to their respective endpoints', async () => {
    const { textarea, statusNode } = createTextarea();
    const calls = [];

    const axiosInstance = {
        async post(url, formData) {
            calls.push({ url, formData });

            if (url === '/uploads/images') {
                return {
                    data: {
                        markdown: '![封面图](/storage/media/article/2026/03/cover.png)',
                    },
                };
            }

            return {
                data: {
                    markdown: '[视频：demo](/storage/media/article/2026/03/demo.mp4)',
                },
            };
        },
    };

    await uploadPastedMedia(textarea, [
        new File(['png'], 'cover.png', { type: 'image/png' }),
        new File(['mp4'], '', { type: 'video/mp4' }),
    ], {
        axiosInstance,
        timeoutFn: (callback) => callback(),
    });

    assert.equal(calls.length, 2);
    assert.equal(calls[0].url, '/uploads/images');
    assert.equal(calls[0].formData.get('context'), 'article');
    assert.equal(calls[1].url, '/uploads/videos');
    assert.equal(calls[1].formData.get('context'), 'article');
    assert.match(calls[1].formData.get('video').name, /^pasted-video-\d+-2\.mp4$/);
    assert.equal(
        textarea.value,
        '![封面图](/storage/media/article/2026/03/cover.png)\n\n[视频：demo](/storage/media/article/2026/03/demo.mp4)',
    );
    assert.equal(statusNode.dataset.state, 'idle');
    assert.equal(statusNode.hidden, true);
});

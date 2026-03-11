import assert from 'node:assert/strict';
import test from 'node:test';

import {
    clipboardImageFiles,
    resolvePastedImageFileName,
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

test('resolvePastedImageFileName creates a jpg filename for clipboard jpeg blobs', () => {
    const file = new File(['jpeg'], '', { type: 'image/jpeg' });
    const fileName = resolvePastedImageFileName(file, 0);

    assert.match(fileName, /^pasted-image-\d+-1\.jpg$/);
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

const store = new Map();

export const rememberPreviewCode = (key, code, expiresInSeconds) => {
    store.set(key, {
        code,
        expiresAt: Date.now() + (expiresInSeconds * 1000),
    });
};

export const takePreviewCode = (key) => {
    const entry = store.get(key);

    if (!entry) {
        return null;
    }

    if (entry.expiresAt <= Date.now()) {
        store.delete(key);

        return null;
    }

    return entry.code;
};

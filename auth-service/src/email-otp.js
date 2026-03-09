const normalizeOtp = (value) => {
    if (typeof value === 'string' && value.trim() !== '') {
        return value.trim();
    }

    if (value && typeof value === 'object' && typeof value.otp === 'string' && value.otp.trim() !== '') {
        return value.otp.trim();
    }

    return null;
};

export const issueEmailOtp = async ({ authApi, email, headers, sendEmail, type = 'sign-in' }) => {
    let otp = null;

    try {
        otp = normalizeOtp(await authApi.createVerificationOTP({
            body: {
                email,
                type,
            },
            headers,
        }));
    } catch (error) {
        const existing = await authApi.getVerificationOTP({
            query: {
                email,
                type,
            },
            headers,
        }).catch(() => null);

        otp = normalizeOtp(existing);

        if (otp === null) {
            throw error;
        }
    }

    await sendEmail({
        email,
        otp,
        type,
    });
};

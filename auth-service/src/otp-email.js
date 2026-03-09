import nodemailer from 'nodemailer';

import { deriveTransportOptions } from './mail-config.js';

const buildOtpSubject = (type) => type === 'sign-in'
    ? 'Bensz Channel 登录验证码'
    : 'Bensz Channel 验证码';

const buildOtpHtml = (otp, ttlSeconds) => `
    <div style="font-family:system-ui,sans-serif;line-height:1.6;color:#111827">
        <h2 style="margin:0 0 12px">Bensz Channel 登录验证码</h2>
        <p>你的验证码为：</p>
        <p style="font-size:28px;font-weight:700;letter-spacing:6px">${otp}</p>
        <p>验证码将在 ${Math.round(ttlSeconds / 60)} 分钟后失效。</p>
    </div>
`;

export const sendOtpEmail = async ({
    email,
    otp,
    type,
    ttlSeconds,
    getMailConfig,
    transportFactory = nodemailer.createTransport,
    logger,
}) => {
    const mailConfig = await getMailConfig();
    const transporter = transportFactory(deriveTransportOptions(mailConfig));

    try {
        await transporter.sendMail({
            from: `${mailConfig.fromName} <${mailConfig.fromAddress}>`,
            to: email,
            subject: buildOtpSubject(type),
            html: buildOtpHtml(otp, ttlSeconds),
            text: `你的验证码是 ${otp}，${Math.round(ttlSeconds / 60)} 分钟内有效。`,
        });
    } catch (error) {
        logger?.error?.('Failed to send OTP email', {
            email,
            error: error instanceof Error ? error.message : String(error),
        });

        throw error;
    }
};

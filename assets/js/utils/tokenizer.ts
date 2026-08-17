import {hash_equal, hex2bytes, unpack_unsigned_long_long} from "./conversion";

export type ParsedToken = {
    token: string;
    payload: string;
    signature: string;
    browser_signature: string;
    random: string;
    pathSignature: string;
    user_signature: string;
    timestamp: number;
    expired_at: number;
    user_id: number;
}

export const tokenParser = (token: string) : ParsedToken|null  => {
    if (typeof token !== "string"
        || token.length !== 400
        || /[^0-9a-f]/.test(token) // token is hex
    ) {
        return null;
    }
    const length = token.length;
    const payload = token.substring(0, length - 128);
    if (payload.length !== 272) { // payload length is 272 bytes (400 - 128)
        return null;
    }
    // concat of: payload + (32 bytes) signature + (32 bytes) browser signature
    const browser_signature = token.substring(length - 64);
    const signature = token.substring(length - 128, length - 64);
    const payload_browser_signature = payload.substring(payload.length - 64);
    if (!hash_equal(payload_browser_signature, browser_signature)) {
        return null;
    }
    const random = payload.substring(0, 32);
    const pathSignature = payload.substring(32, 96);
    const user_signature = payload.substring(96, 160);
    const timestamp_hex = payload.substring(160, 176);
    const duration_hex = payload.substring(176, 192);
    const user_hex = payload.substring(192, 208);
    if (random.length !== 32
        || pathSignature.length !== 64
        || user_signature.length !== 64
        || timestamp_hex.length !== 16
        || duration_hex.length !== 16
        || user_hex.length !== 16
    ) {
        return null;
    }
    try {
        const user_id = unpack_unsigned_long_long(hex2bytes(user_hex));
        const expired_at = unpack_unsigned_long_long(hex2bytes(duration_hex));
        const timestamp = unpack_unsigned_long_long(hex2bytes(timestamp_hex));
        return {
            token,
            payload,
            signature,
            browser_signature,
            random,
            pathSignature,
            user_signature,
            timestamp,
            expired_at,
            user_id
        };
    } catch (e) {
        return null;
    }
}

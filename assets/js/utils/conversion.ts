export const str2bytes = (str: string | Uint8Array<ArrayBufferLike> | ArrayBuffer): Uint8Array<ArrayBufferLike> => {
    if (str instanceof Uint8Array) {
        return str;
    }
    if (str instanceof ArrayBuffer) {
        return new Uint8Array(str);
    }
    if (typeof str !== "string") {
        throw new Error("Invalid string");
    }
    const bytes = new Uint8Array(str.length);
    for (let i = 0; i < str.length; i++) {
        bytes[i] = str.charCodeAt(i);
    }
    return bytes;
}
export const bytes2str = (bytes: Uint8Array<ArrayBufferLike> | ArrayBuffer): string => {
    if (bytes instanceof ArrayBuffer) {
        bytes = new Uint8Array(bytes);
    }
    if (!(bytes instanceof Uint8Array)) {
        throw new Error("Invalid bytes array");
    }
    let str = "";
    for (let i = 0; i < bytes.length; i++) {
        str += String.fromCharCode(bytes[i]);
    }
    return str;
}

export const hex2bytes = (hex: string | Uint8Array<ArrayBufferLike> | ArrayBuffer): Uint8Array<ArrayBufferLike> => {
    if (hex instanceof Uint8Array || hex instanceof ArrayBuffer) {
        return new Uint8Array(hex);
    }
    if (typeof hex !== "string" || hex.length % 2 !== 0 || /[^0-9a-f]/.test(hex)) {
        throw new Error("Invalid hex string");
    }
    const bytes = new Uint8Array(hex.length / 2);
    for (let i = 0; i < hex.length; i += 2) {
        bytes[i / 2] = parseInt(hex.substring(i, i + 2), 16);
    }
    return bytes;
}

export const bytes2hex = (bytes: Uint8Array<ArrayBufferLike>|string): string => {
    bytes = str2bytes(bytes);
    if (!(bytes instanceof Uint8Array)) {
        throw new Error("Invalid bytes array");
    }
    let hex = "";
    for (let i = 0; i < bytes.length; i++) {
        hex += bytes[i].toString(16).padStart(2, "0");
    }
    return hex;
}

export const pack_unsigned_long_long = (j: number): Uint8Array<ArrayBufferLike> => {
    if (typeof j !== "number" || !Number.isInteger(j) || j < 0 || j > Number.MAX_SAFE_INTEGER) {
        throw new Error("Invalid unsigned long long integer");
    }
    const bytes = new Uint8Array(8);
    for (let i = 7; i >= 0; i--) {
        bytes[i] = j % 256;
        j = Math.floor(j / 256);
    }
    return bytes;
}

export const unpack_unsigned_long_long = (bytes: Uint8Array<ArrayBufferLike> | ArrayBuffer | string): number => {
    bytes = str2bytes(bytes);
    if (bytes.length !== 8) {
        throw new Error("Invalid unsigned long long integer bytes");
    }
    let j = 0;
    for (let i = 0; i < 8; i++) {
        j = j * 256 + bytes[i];
    }
    if (j > Number.MAX_SAFE_INTEGER) {
        throw new Error("Value exceeds Number.MAX_SAFE_INTEGER, precision would be lost");
    }
    return j;
}

export const hash_equal = (a: string, b: string): boolean => {
    if (typeof a !== "string" || typeof b !== "string") {
        throw new Error("Invalid string");
    }
    if (a.length !== b.length) {
        return false;
    }
    let result = 0;
    for (let i = 0; i < a.length; i++) {
        result |= a.charCodeAt(i) ^ b.charCodeAt(i);
    }
    return result === 0;
}

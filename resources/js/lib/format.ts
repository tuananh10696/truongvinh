/**
 * Dung lượng file kiểu Việt Nam: "48 KB", "1,2 MB".
 */
export function formatSize(bytes: number): string {
    if (bytes < 1024) {
        return `${bytes} B`;
    }

    const units = ['KB', 'MB', 'GB'];
    let value = bytes / 1024;
    let unit = 0;

    while (value >= 1024 && unit < units.length - 1) {
        value /= 1024;
        unit++;
    }

    const digits = value < 10 && unit > 0 ? 1 : 0;

    return `${value.toLocaleString('vi-VN', { maximumFractionDigits: digits })} ${units[unit]}`;
}

/**
 * "2026-10-15" -> "15/10".
 */
export function formatShortDate(isoDate: string): string {
    const [, month, day] = isoDate.split('-');

    return `${day}/${month}`;
}

export function todayIso(): string {
    const now = new Date();
    const pad = (n: number) => String(n).padStart(2, '0');

    return `${now.getFullYear()}-${pad(now.getMonth() + 1)}-${pad(now.getDate())}`;
}

/**
 * Tên gọi thân mật từ họ tên tiếng Việt: "Nguyễn Lan" -> "Lan".
 */
export function givenName(fullName: string): string {
    const parts = fullName.trim().split(/\s+/);

    return parts[parts.length - 1] ?? fullName;
}

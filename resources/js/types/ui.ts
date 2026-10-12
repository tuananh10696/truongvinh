export type Appearance = 'light' | 'dark' | 'system';
export type ResolvedAppearance = 'light' | 'dark';

export type AppVariant = 'header' | 'sidebar';

export type FlashToast = {
    type: 'success' | 'info' | 'warning' | 'error';
    message: string;
    /** URL (POST) để hoàn tác thao tác vừa làm, ví dụ khôi phục file vừa xoá. */
    undo?: string;
};

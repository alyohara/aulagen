export interface User {
    id: number;
    name: string;
    email: string;
    email_verified_at?: string;
    role?: 'admin' | 'teacher' | 'student';
    role_label?: string;
    title?: string | null;
    institution?: string | null;
    can?: {
        manage_courses: boolean;
        admin_panel: boolean;
    };
}

export interface FlashData {
    success?: string | null;
    warning?: string | null;
    error?: string | null;
}

export type PageProps<T extends Record<string, unknown> = Record<string, unknown>> = T & {
    auth: {
        user: User;
    };
    flash: FlashData;
    errors: Record<string, string>;
};

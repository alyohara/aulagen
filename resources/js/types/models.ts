export interface TeacherOption {
    id: number;
    name: string;
}

export interface CourseSummary {
    id: number;
    name: string;
    slug: string;
    status?: string;
    status_label?: string;
    institution?: string | null;
    career?: string | null;
    course_year?: string | null;
    duration?: string | null;
    modality?: string | null;
    description?: string | null;
    objectives?: string | null;
    program?: string | null;
    owner?: string | null;
    owner_id?: number;
    collaborators?: string | null;
    published_at?: string | null;
    stats?: Record<string, number>;
    created_at?: string;
}

export interface ModuleNode {
    id: number;
    title: string;
    slug: string;
    summary?: string | null;
    type: string;
    type_label?: string;
    position: number;
    status?: string;
    is_ai_generated?: boolean;
    lessons_count?: number;
    lessons?: LessonRow[];
}

export interface LessonRow {
    id: number;
    title: string;
    slug: string;
    summary?: string | null;
    status: string;
    status_label: string;
    badge?: string;
    position: number;
    has_content?: boolean;
    is_ai_generated?: boolean;
    module_id?: number;
    module?: { id?: number; slug?: string; title?: string } | null;
    url?: string;
    updated_at?: string | null;
}

export interface DocumentRow {
    id: number;
    name: string;
    type: string;
    type_label?: string;
    status: string;
    status_label: string;
    size?: string;
    error?: string | null;
    chunk_count?: number;
    page_count?: number | null;
    source_url?: string | null;
    module: { id: number; title: string } | null;
    created_at?: string;
    processed_at?: string | null;
    url?: string;
    external?: boolean;
}

export interface ActivityRow {
    id: number;
    title: string;
    slug: string;
    type: string;
    type_label: string;
    status: string;
    instructions?: string | null;
    questions_count?: number;
    cards_count?: number;
    module?: string | null;
    is_ai_generated?: boolean;
    created_at?: string;
    questions?: QuestionRow[];
    cards?: { front: string; back: string }[];
}

export interface QuestionRow {
    id: number;
    type: string;
    prompt: string;
    options?: string[] | null;
    correct_answer?: string | null;
    explanation?: string | null;
}

export interface BibliographyRow {
    id: number;
    kind: string;
    authors?: string | null;
    title: string;
    edition?: string | null;
    publisher?: string | null;
    year?: string | null;
    url?: string | null;
    note?: string | null;
    formatted?: string;
}

export interface GenerationRow {
    id: number;
    type: string;
    type_label?: string;
    provider?: string | null;
    model?: string | null;
    status: string;
    duration_ms?: number | null;
    course?: string | null;
    user?: string | null;
    error?: string | null;
    created_at?: string;
}

export interface UserRow {
    id: number;
    name: string;
    email: string;
    role: string;
    role_label: string;
    is_active: boolean;
    courses?: number;
    created_at?: string;
}

export interface AulaNavigation {
    id: number;
    title: string;
    slug: string;
    type: string;
    lessons: { id: number; title: string; slug: string; status: string }[];
}

export interface AulaSettings {
    ai_assistant_enabled: boolean;
    enable_search: boolean;
    show_progress: boolean;
    show_sources: boolean;
    allow_downloads: boolean;
    allow_external_knowledge: boolean;
}

export interface AulaCourse {
    id: number;
    name: string;
    slug: string;
    institution?: string | null;
    career?: string | null;
    course_year?: string | null;
    duration?: string | null;
    modality?: string | null;
    description?: string | null;
    owner?: string | null;
    collaborators?: string | null;
    status?: string;
    published_at?: string | null;
}

export interface AulaShared {
    course: AulaCourse;
    navigation: AulaNavigation[];
    settings: AulaSettings;
    isPreview: boolean;
    previewUrl: string;
}

export interface ChatAnswer {
    answer: string;
    sources?: { label: string; quote?: string }[];
    error?: boolean;
}

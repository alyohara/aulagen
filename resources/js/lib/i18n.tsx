import { usePage } from '@inertiajs/react';
import { PropsWithChildren, useEffect } from 'react';

type Locale = 'en' | 'es';

// Fixed interface copy lives here so pages can retain their domain markup without
// duplicating locale conditionals. Course content and server-provided values are
// deliberately not translated.
const english: Record<string, string> = {
    'Administración': 'Administration', 'Usuarios': 'Users', 'Volver': 'Back', 'Listado': 'List',
    'Nombre': 'Name', 'Rol': 'Role', 'Materias': 'Courses', 'Activo': 'Active', 'Alta': 'Joined',
    'Sí': 'Yes', 'No': 'No', 'Generaciones de IA': 'AI generations', 'Últimas 100 ejecuciones · proveedor activo:': 'Latest 100 runs · active provider:',
    'Registro': 'Log', 'Todo lo que la IA generó en la plataforma, con duración y errores.': 'Everything AI generated on the platform, with duration and errors.',
    'Sin generaciones registradas.': 'No generations recorded.', 'Fecha': 'Date', 'Materia': 'Course', 'Tipo': 'Type',
    'Usuario': 'User', 'Proveedor': 'Provider', 'Estado': 'Status', 'Error': 'Error',
    'Configuración de IA': 'AI settings', 'Proveedor activo:': 'Active provider:', 'modelo': 'model',
    'General': 'General', 'Selección del proveedor y parámetros globales.': 'Provider selection and global settings.',
    'Proveedor principal': 'Primary provider', 'Intentar primero': 'Try first', 'Respaldo': 'Fallback',
    'Consultas IA por minuto': 'AI requests per minute', 'Fragmentos RAG (top K)': 'RAG chunks (top K)',
    'Dimensión de embeddings': 'Embedding dimensions', 'Generación con IA habilitada': 'AI generation enabled',
    'Guardar configuración': 'Save settings', 'Guardando…': 'Saving…', 'Probar conexión': 'Test connection', 'Probando…': 'Testing…',
    'Nombre del proveedor': 'Provider name', 'URL base': 'Base URL', 'Modelo': 'Model',
    'Modelo de embeddings': 'Embedding model', 'Otro proveedor compatible': 'Other compatible provider',
    'Nueva materia': 'New course', 'Datos de la materia': 'Course details', 'Nombre de la materia *': 'Course name *',
    'Descripción': 'Description', 'Institución': 'Institution', 'Carrera': 'Program', 'Año / ciclo': 'Year / term',
    'Duración': 'Duration', 'Modalidad': 'Delivery mode', 'Seleccionar…': 'Select…', 'Presencial': 'In person',
    'Presencial con material digital': 'In person with digital materials', 'Virtual sincrónico': 'Live online',
    'Virtual asincrónico': 'Self-paced online', 'Híbrida': 'Hybrid', 'Otros docentes': 'Other instructors',
    'Objetivos de aprendizaje (uno por línea)': 'Learning objectives (one per line)',
    'Programa / plan de contenidos (uno por línea)': 'Syllabus / content plan (one per line)',
    'Bibliografía principal (una por línea)': 'Primary bibliography (one per line)',
    'Bibliografía complementaria (una por línea)': 'Supplementary bibliography (one per line)',
    'Pedirle a la IA que proponga una estructura cuando haya material cargado': 'Ask AI to propose a structure when material is uploaded',
    'Crear materia': 'Create course', 'Cancelar': 'Cancel', 'Editar:': 'Edit:', 'Editar datos': 'Edit details',
    'Guardar cambios': 'Save changes', 'Configuración del aula': 'Classroom settings',
    'Define qué pueden ver y hacer los alumnos.': 'Define what students can see and do.',
    'Asistente de IA disponible para alumnos': 'AI assistant available to students', 'Búsqueda en el contenido del aula': 'Search classroom content',
    'Mostrar progreso de lectura': 'Show reading progress', 'Mostrar fuentes citadas en las lecciones': 'Show sources cited in lessons',
    'Permitir descargar materiales': 'Allow downloading materials', 'Permitir a la IA usar conocimiento externo al material': 'Allow AI to use knowledge outside the material',
    'Generar recursos automáticamente al aprobar una lección': 'Generate resources automatically when approving a lesson',
    'Estructura y contenido': 'Structure and content', 'Proponer estructura con IA': 'Propose structure with AI',
    'Generar contenido faltante': 'Generate missing content', '+ Módulo': '+ Module', 'Propuesta de estructura (IA)': 'Structure proposal (AI)',
    'Aplicar estructura': 'Apply structure', 'Descartar': 'Discard', 'Editar': 'Edit', '+ Lección': '+ Lesson',
    'Eliminar': 'Delete', 'Sin lecciones todavía.': 'No lessons yet.', 'generado por IA': 'AI generated', '· con contenido': '· has content',
    'Generar con IA': 'Generate with AI', 'Editar módulo': 'Edit module', 'Nuevo módulo': 'New module',
    'Guardar': 'Save', 'Título': 'Title', 'Resumen': 'Summary', 'Nueva lección': 'New lesson',
    'Título de la lección': 'Lesson title', 'Resumen (opcional)': 'Summary (optional)', 'Borrador': 'Draft',
    'Generado': 'Generated', 'Revisión': 'Review', 'Aprobado': 'Approved', 'Publicado': 'Published',
    'Material de la materia': 'Course materials', 'Cargar material': 'Upload material', 'Archivos': 'Files',
    'Enlace o video': 'Link or video', 'Texto pegado': 'Pasted text', 'Sin módulo asignado': 'No module assigned',
    'Subir': 'Upload', 'Enlace': 'Link', 'Video': 'Video', 'Agregar enlace': 'Add link', 'Contenido': 'Content',
    'Guardar texto': 'Save text', 'Materiales': 'Materials', 'fragmentos': 'chunks', 'págs.': 'pages',
    'Sin módulo': 'No module', 'Reprocesar': 'Reprocess', 'Bibliografía': 'Bibliography',
    'Bibliografía principal': 'Primary bibliography', 'Bibliografía complementaria': 'Supplementary bibliography',
    'Año': 'Year', 'Edición': 'Edition', 'Material optativo y enlaces útiles.': 'Optional material and useful links.',
    'Actividades': 'Activities', 'Autoevaluación': 'Self-assessment', 'Ejercicios prácticos': 'Practice exercises',
    'Trabajo práctico': 'Assignment', 'Módulo (opcional)': 'Module (optional)', 'Opciones (una por línea)': 'Options (one per line)',
    'Respuesta correcta (debe coincidir con una opción)': 'Correct answer (must match an option)', 'Explicación': 'Explanation',
    'Asistente IA': 'AI assistant', 'Ver aula': 'View classroom', 'Publicar aula': 'Publish classroom', 'Despublicar': 'Unpublish',
    'Módulos': 'Modules', 'Unidades': 'Units', 'Lecciones': 'Lessons', 'Lecciones pendientes': 'Pending lessons',
    'Proveedor de IA': 'AI provider', 'Últimos materiales': 'Latest materials', 'Estado del procesamiento': 'Processing status',
    'Accesos rápidos': 'Quick links', 'Cargar materiales': 'Upload materials',
    'Mis materias': 'My courses', 'Publicadas': 'Published', 'Todavía no tenés materias': 'You do not have any courses yet',
    'Crear la primera materia': 'Create your first course', 'Abrir': 'Open', 'Ver aula →': 'View classroom →',
    'Ver toda la bibliografía →': 'View all bibliography →',
    'Inicio': 'Home', 'Módulo': 'Module', 'Material del módulo': 'Module materials',
    'Lección': 'Lesson', 'Fuentes de esta lección': 'Sources for this lesson', 'Marcar como completada': 'Mark as complete',
    '✓ Lección completada': '✓ Lesson completed', 'Buscar': 'Search', 'Glosario': 'Glossary',
    'Disponible en línea': 'Available online', 'Tarjeta': 'Card', 'reverso': 'back', 'anverso': 'front',
    '(tocá para voltear)': '(tap to flip)', 'Asistente de la materia': 'Course assistant', 'Ejemplos:': 'Examples:',
    'Enviar': 'Send', 'Pensando…': 'Thinking…', 'Ingresar': 'Sign in',
    'Cuestionario': 'Quiz', 'Preguntas de examen': 'Exam questions', 'Cuestionarios, autoevaluaciones, flashcards y exámenes.': 'Quizzes, self-assessments, flashcards, and exams.',
    '+ Crear': '+ Create', 'Usa el material procesado y el contenido aprobado de la materia como fuente.': 'Uses processed material and approved course content as its source.',
    'Toda la materia': 'Entire course', 'Generar': 'Generate', 'Ocultar': 'Hide', 'Ver preguntas': 'View questions',
    'Aprobada': 'Approved', 'Publicada': 'Published', 'preguntas': 'questions', 'tarjetas': 'cards',
    'Sin preguntas ni tarjetas todavía.': 'No questions or cards yet.', 'Nueva actividad': 'New activity',
    'Instrucciones': 'Instructions', 'Editar pregunta': 'Edit question', 'Enunciado': 'Question prompt',
    'No hay actividades todavía. Creá una manualmente o generá una con IA.': 'No activities yet. Create one manually or generate one with AI.',
    'Referencias que ven los alumnos en el aula.': 'References students see in the classroom.', '+ Referencia': '+ Reference',
    'Sin referencias.': 'No references.', 'Obras de consulta obligatoria.': 'Required reference works.', '¿Eliminar esta referencia?': 'Delete this reference?',
    'Editar referencia': 'Edit reference', 'Nueva referencia': 'New reference', 'Principal': 'Primary',
    'Complementaria': 'Supplementary', 'Autores': 'Authors', 'Editorial': 'Publisher', 'Nota': 'Note',
    'Actualización estado': 'Refresh status', 'Actualizar estado': 'Refresh status', 'Proponer estructura': 'Propose structure',
    'Hay una estructura propuesta pendiente.': 'There is a pending proposed structure.', 'Revisarla en Contenido': 'Review it in Content',
    'Proveedores configurados': 'Configured providers', 'disponible': 'available', 'no disponible': 'unavailable',
    'Flujos de generación': 'Generation workflows', 'Estructura y lecciones': 'Structure and lessons',
    'Actividades y evaluaciones': 'Activities and assessments', 'Generar glosario con IA': 'Generate glossary with AI',
    'Glosario del aula': 'Classroom glossary', 'Término': 'Term', 'Definición': 'Definition', '+ Término': '+ Term',
    'Guardar glosario': 'Save glossary', 'Registro de generaciones': 'Generation log',
    'Volver al contenido': 'Back to content', 'Regenerar con IA': 'Regenerate with AI', 'Vista previa': 'Preview',
    'Guardar lección': 'Save lesson', 'Fuentes citadas en el contenido': 'Sources cited in content',
    'Fragmentos del material relacionados': 'Related material chunks',
    'Todavía no hay actividades publicadas.': 'There are no published activities yet.',
    'Esta actividad aún no tiene preguntas.': 'This activity does not have questions yet.',
    'Material optativo, artículos y enlaces.': 'Optional material, articles, and links.',
    'Definiciones de los términos clave de la materia.': 'Definitions of the course’s key terms.',
    'Buscar término o definición…': 'Search for a term or definition…',
    'Esta lección todavía no tiene contenido aprobado.': 'This lesson does not have approved content yet.',
    'Este módulo todavía no tiene lecciones visibles.': 'This module does not have visible lessons yet.',
    'Búsqueda semántica sobre las lecciones, módulos y materiales de la materia.': 'Semantic search across the course lessons, modules, and materials.',
    'Sin resultados. Probá con otras palabras: el buscador también entiende sinónimos del contenido.': 'No results. Try other words: search also understands content synonyms.',
    'Automático (Ollama con respaldo local)': 'Automatic (Ollama with local fallback)', 'Ollama (IA local, gratis)': 'Ollama (local AI, free)',
    'Otro (compatible con OpenAI)': 'Other (OpenAI-compatible)', 'Local heurístico (sin modelos)': 'Local heuristic (no models)',
    'No se pudo contactar al servidor.': 'Could not contact the server.', 'Error desconocido.': 'Unknown error.',
    'Modelos corriendo en tu servidor, sin costo ni límites.': 'Models running on your server, with no cost or limits.',
    'API de Google con free tier.': 'Google API with a free tier.', 'API oficial de OpenAI.': 'Official OpenAI API.',
    'Cualquier endpoint que hable el protocolo de OpenAI.': 'Any endpoint implementing the OpenAI protocol.',
    'Estado general de la plataforma.': 'Overall platform status.', 'Generaciones IA': 'AI generations',
    'Configurar IA': 'Configure AI', 'Docentes': 'Instructors', 'Docs. fallidos': 'Failed documents',
    'Errores IA': 'AI errors', 'Configuración efectiva de generación y embeddings.': 'Effective generation and embedding configuration.',
    'Configurado en .env': 'Configured in .env', 'Errores recientes': 'Recent errors',
    'Materiales fallidos y generaciones con error.': 'Failed materials and generations with errors.', 'Sin errores registrados.': 'No errors recorded.',
    'Hola': 'Hello', 'módulos': 'modules', 'lecciones': 'lessons', 'materiales': 'materials',
    'visibles': 'visible', 'pendientes': 'pending',
    'Con estos datos se arma el encabezado del aula virtual. Podés completarlos después.': 'These details build the virtual classroom header. You can complete them later.',
    'PDF, DOCX, PPTX, XLSX, TXT, Markdown, imágenes, enlaces o texto pegado.': 'PDF, DOCX, PPTX, XLSX, TXT, Markdown, images, links, or pasted text.',
    'El procesamiento (extracción, fragmentación y vectores) corre en segundo plano.': 'Processing (extraction, chunking, and vectors) runs in the background.',
    'Todavía no cargaste material.': 'You have not uploaded any material yet.',
    'La IA propuso esta organización basada en tu material cargado.': 'AI proposed this organization based on your uploaded material.',
    'Todavía no hay módulos. Crealos manualmente con «+ Módulo» o pedile a la IA que proponga una estructura a partir del material cargado.': 'There are no modules yet. Create them manually with “+ Module” or ask AI to propose a structure from uploaded material.',
    'La materia está en borrador: los alumnos no pueden verla todavía.': 'The course is a draft: students cannot see it yet.',
    'Hay una estructura propuesta por la IA pendiente de revisión.': 'There is an AI-proposed structure pending review.',
    'Revisarla': 'Review it', 'Sin materiales cargados.': 'No materials uploaded.',
    'El contenido es HTML. Todo cambio de contenido vuelve a estado «Revisión» hasta que lo apruebes.': 'Content is HTML. Any content change returns it to “Review” until you approve it.',
    'Se muestran al alumno si la configuración lo permite.': 'They are shown to students if enabled in settings.',
    'Esta lección no tiene fuentes declaradas.': 'This lesson has no declared sources.',
    'Cargá material en la materia para indexar fragmentos.': 'Upload course material to index chunks.',
};

const spanish: Record<string, string> = {
    'Log in': 'Ingresar', 'Register': 'Registrarse', 'Forgot Password': 'Recuperar contraseña',
    'Reset Password': 'Restablecer contraseña', 'Confirm Password': 'Confirmar contraseña',
    'Email Verification': 'Verificación de correo', 'Password': 'Contraseña',
    'Confirm': 'Confirmar', 'Remember me': 'Recordarme', 'Forgot your password?': '¿Olvidaste tu contraseña?',
    'Already registered?': '¿Ya tenés una cuenta?', 'Current Password': 'Contraseña actual',
    'New Password': 'Nueva contraseña', 'Email Password Reset Link': 'Enviar enlace para restablecer la contraseña',
    'Thanks for signing up! Before getting started, could you verify your email address by clicking on the link we just emailed to you? If you didn\'t receive the email, we will gladly send you another.': '¡Gracias por registrarte! Antes de empezar, verificá tu correo electrónico haciendo clic en el enlace que te enviamos. Si no lo recibiste, podemos enviarte otro.',
    'A new verification link has been sent to the email address you provided during registration.': 'Se envió un nuevo enlace de verificación a la dirección de correo que ingresaste durante el registro.',
    'Resend Verification Email': 'Reenviar correo de verificación',
    'Log Out': 'Cerrar sesión',
    'Profile': 'Perfil', 'Profile Information': 'Información del perfil', 'Update your account’s profile information and email address.': 'Actualizá la información de tu perfil y dirección de correo electrónico.',
    'Name': 'Nombre', 'Email': 'Correo electrónico', 'Save': 'Guardar', 'Saved.': 'Guardado.',
    'Update Password': 'Actualizar contraseña', 'Ensure your account is using a long, random password to stay secure.': 'Asegurate de que tu cuenta use una contraseña larga y aleatoria para mantenerla segura.',
    'Delete Account': 'Eliminar cuenta', 'Once your account is deleted, all of its resources and data will be permanently deleted.': 'Una vez eliminada tu cuenta, todos sus recursos y datos se eliminarán permanentemente.',
    'Are you sure you want to delete your account?': '¿Seguro que querés eliminar tu cuenta?',
    'Cancel': 'Cancelar',
};

export function translate(value: string, locale: Locale = 'en'): string {
    const leading = value.match(/^\s*/)?.[0] ?? '';
    const trailing = value.match(/\s*$/)?.[0] ?? '';
    const text = value.slice(leading.length, value.length - trailing.length);
    return leading + (locale === 'es' ? (spanish[text] ?? text) : (english[text] ?? text)) + trailing;
}

export function confirmLocalized(message: string): boolean {
    const locale = document.documentElement.lang.startsWith('es') ? 'es' : 'en';
    const translated = locale === 'en'
        ? message
            .replace(/^¿Eliminar "(.+)"\?$/, 'Delete "$1"?')
            .replace(/^¿Eliminar el módulo "(.+)" con sus lecciones\?$/, 'Delete module "$1" and its lessons?')
            .replace(/^¿Eliminar la lección "(.+)"\?$/, 'Delete lesson "$1"?')
        : message;
    return window.confirm(translate(translated, locale));
}

/**
 * Applies the catalog to existing JSX copy, including native placeholders and
 * titles. It leaves non-catalogued data untouched, which protects user-created
 * course content and API responses.
 */
export function LocalizedContent({ children }: PropsWithChildren) {
    const locale = (usePage().props.locale ?? 'en') as Locale;

    useEffect(() => {
        const localize = (root: ParentNode) => {
            const walker = document.createTreeWalker(root, NodeFilter.SHOW_TEXT);
            const nodes: Text[] = [];
            while (walker.nextNode()) nodes.push(walker.currentNode as Text);
            nodes.forEach((node) => {
                const translated = translate(node.nodeValue ?? '', locale);
                if (translated !== node.nodeValue) node.nodeValue = translated;
            });
            if (root instanceof Element) {
                ['placeholder', 'title', 'aria-label'].forEach((name) => {
                    const value = root.getAttribute(name);
                    if (value) root.setAttribute(name, translate(value, locale));
                });
                root.querySelectorAll<HTMLElement>('[placeholder], [title], [aria-label]').forEach((element) => {
                    ['placeholder', 'title', 'aria-label'].forEach((name) => {
                        const value = element.getAttribute(name);
                        if (value) element.setAttribute(name, translate(value, locale));
                    });
                });
            }
        };
        localize(document.documentElement);
        const observer = new MutationObserver((changes) => changes.forEach((change) => change.addedNodes.forEach((node) => {
            if (node instanceof Element) localize(node);
            if (node instanceof Text) node.nodeValue = translate(node.nodeValue ?? '', locale);
        })));
        observer.observe(document.documentElement, { childList: true, characterData: true, subtree: true });
        return () => observer.disconnect();
    }, [locale]);

    return <>{children}</>;
}

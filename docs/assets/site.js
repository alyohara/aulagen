const languageKey = "aulagen-language";
const language = new URLSearchParams(window.location.search).get("lang") || localStorage.getItem(languageKey) || "en";

const chrome = {
  en: { docs: "AulaGen Docs", install: "Installation", teachers: "For educators", architecture: "Architecture", github: "GitHub", start: "Get started", home: "Home", use: "Product guides", admin: "Administration & AI", reference: "Reference", operations: "Operations" },
  es: { docs: "AulaGen Docs", install: "Instalación", teachers: "Para docentes", architecture: "Arquitectura", github: "GitHub", start: "Empezar", home: "Inicio", use: "Guías del producto", admin: "Administración e IA", reference: "Referencia", operations: "Operación" },
};

const guides = {
  installation: {
    en: ["Quick start", "Install AulaGen", "Run the complete learning platform with Docker Compose, or prepare a local environment with PHP, Node.js, and SQLite.", `
      <h2>Requirements</h2><table><thead><tr><th>Option</th><th>You need</th></tr></thead><tbody><tr><td>Docker (recommended)</td><td>Docker Desktop 20.10+, Docker Compose v2, Git, and ports <code>8000</code>, <code>5432</code>, and <code>6379</code>.</td></tr><tr><td>Local development</td><td>PHP 8.2+, Composer, Node.js 22+, npm, and SQLite.</td></tr></tbody></table>
      <h2>Docker Compose</h2><pre><code>git clone https://github.com/alyohara/aulagen.git
cd aulagen
cp .env.example .env
docker compose up -d --build</code></pre><p>Open <a href="http://localhost:8000">http://localhost:8000</a>. Generate an application key if needed:</p><pre><code>docker compose exec app php artisan key:generate</code></pre>
      <h3>Optional local AI</h3><pre><code>docker compose --profile ollama up -d
docker compose exec ollama ollama pull llama3.2
docker compose exec ollama ollama pull nomic-embed-text</code></pre>
      <h2>Local development on Windows</h2><pre><code>composer install
npm install
Copy-Item .env.example .env
php artisan key:generate
New-Item -ItemType File -Force database\\database.sqlite
php artisan migrate --seed
npm run build
php artisan serve --host=127.0.0.1 --port=8000</code></pre><p>Use <code>php artisan horizon</code> in a second terminal when processing uploads. The seeded local accounts use password <code>password</code>: <code>admin@aulagen.test</code>, <code>docente@aulagen.test</code>, and <code>alumno@aulagen.test</code>.</p>`],
    es: ["Inicio rápido", "Instalá AulaGen", "Ejecutá la plataforma completa con Docker Compose o prepará un entorno local con PHP, Node.js y SQLite.", `
      <h2>Requisitos</h2><table><thead><tr><th>Opción</th><th>Necesitás</th></tr></thead><tbody><tr><td>Docker (recomendada)</td><td>Docker Desktop 20.10+, Docker Compose v2, Git y los puertos <code>8000</code>, <code>5432</code> y <code>6379</code>.</td></tr><tr><td>Desarrollo local</td><td>PHP 8.2+, Composer, Node.js 22+, npm y SQLite.</td></tr></tbody></table>
      <h2>Docker Compose</h2><pre><code>git clone https://github.com/alyohara/aulagen.git
cd aulagen
cp .env.example .env
docker compose up -d --build</code></pre><p>Abrí <a href="http://localhost:8000">http://localhost:8000</a>. Si hace falta, generá la clave de aplicación:</p><pre><code>docker compose exec app php artisan key:generate</code></pre>
      <h3>IA local opcional</h3><pre><code>docker compose --profile ollama up -d
docker compose exec ollama ollama pull llama3.2
docker compose exec ollama ollama pull nomic-embed-text</code></pre>
      <h2>Desarrollo local en Windows</h2><pre><code>composer install
npm install
Copy-Item .env.example .env
php artisan key:generate
New-Item -ItemType File -Force database\\database.sqlite
php artisan migrate --seed
npm run build
php artisan serve --host=127.0.0.1 --port=8000</code></pre><p>Usá <code>php artisan horizon</code> en otra terminal al procesar materiales. Las cuentas locales usan <code>password</code>: <code>admin@aulagen.test</code>, <code>docente@aulagen.test</code> y <code>alumno@aulagen.test</code>.</p>`],
  },
  teachers: {
    en: ["Product guide", "Build a classroom with confidence", "AulaGen accelerates course preparation while educators retain the final decision over every published item.", `<div class="notice"><strong>Review first:</strong> treat generated material as a draft. Verify accuracy, learning design, accessibility, and sources before approving it.</div><h2>Recommended workflow</h2><ol class="steps"><li class="step"><strong>Create the course.</strong> Add its name, objectives, program, and context.</li><li class="step"><strong>Add source material.</strong> Upload PDF, DOCX, PPTX, XLSX, text files, links, or video references. Wait until processing finishes.</li><li class="step"><strong>Request a structure.</strong> Review the proposed modules and lessons against your syllabus before applying it.</li><li class="step"><strong>Develop learning content.</strong> Edit modules and lessons, then generate or create activities, bibliography, and a glossary.</li><li class="step"><strong>Preview and publish.</strong> Approve ready lessons, inspect the student view, then publish the course.</li></ol><h2>Student experience</h2><p>Published classrooms can include module navigation, lesson progress, self-check activities, bibliography, complementary resources, a glossary, semantic search, downloads, and a course assistant.</p><h2>Course assistant and privacy</h2><p>The assistant uses retrieval-augmented generation (RAG): it retrieves relevant processed course fragments and uses them as context. Course settings control the assistant, search, progress, downloads, source labels, and external knowledge. Confirm that your institution permits sharing material with a cloud provider; use local Ollama when self-hosting is required.</p>`],
    es: ["Guía del producto", "Creá un aula con confianza", "AulaGen acelera la preparación de una materia mientras el equipo docente conserva la decisión final sobre todo lo publicado.", `<div class="notice"><strong>Revisá primero:</strong> tratá el material generado como un borrador. Verificá precisión, diseño pedagógico, accesibilidad y fuentes antes de aprobarlo.</div><h2>Recorrido recomendado</h2><ol class="steps"><li class="step"><strong>Creá la materia.</strong> Agregá nombre, objetivos, programa y contexto.</li><li class="step"><strong>Incorporá fuentes.</strong> Subí PDF, DOCX, PPTX, XLSX, texto, enlaces o referencias de video. Esperá a que termine el procesamiento.</li><li class="step"><strong>Solicitá una estructura.</strong> Contrastá módulos y lecciones con el programa antes de aplicarlos.</li><li class="step"><strong>Desarrollá contenido.</strong> Editá módulos y lecciones; generá o creá actividades, bibliografía y glosario.</li><li class="step"><strong>Previsualizá y publicá.</strong> Aprobá las lecciones listas, revisá la vista de estudiante y publicá la materia.</li></ol><h2>Experiencia de estudiantes</h2><p>Las aulas publicadas pueden incluir navegación, progreso, actividades autocorregibles, bibliografía, recursos complementarios, glosario, búsqueda semántica, descargas y asistente de la materia.</p><h2>Asistente y privacidad</h2><p>El asistente usa RAG: recupera fragmentos procesados de la materia y los usa como contexto. La configuración controla asistente, búsqueda, progreso, descargas, fuentes y conocimiento externo. Confirmá que tu institución permite compartir materiales con un proveedor en la nube; usá Ollama local cuando debas autoalojar el procesamiento.</p>`],
  },
  administration: {
    en: ["Administration", "Manage access and AI responsibly", "Administrators manage users, the active AI configuration, and the generation record.", `<h2>Users and roles</h2><p>Use the administration area to update a user’s role or active status. Apply the minimum access needed: educators manage their courses and students consume published classrooms.</p><h2>AI providers</h2><table><thead><tr><th>Value</th><th>Use</th></tr></thead><tbody><tr><td><code>auto</code></td><td>Uses a primary provider and configured fallback.</td></tr><tr><td><code>ollama</code></td><td>Local or self-hosted Ollama service.</td></tr><tr><td><code>gemini</code></td><td>Google Gemini API.</td></tr><tr><td><code>openai</code></td><td>OpenAI API.</td></tr><tr><td><code>custom</code></td><td>OpenAI-compatible endpoint.</td></tr><tr><td><code>local</code></td><td>Built-in heuristic fallback.</td></tr></tbody></table><pre><code>AI_PROVIDER=ollama
OLLAMA_HOST=http://127.0.0.1:11434
OLLAMA_MODEL=llama3.2
OLLAMA_EMBED_MODEL=nomic-embed-text</code></pre><h2>Privacy</h2><p>Keep keys in deployment secrets, never in version control. Review the provider’s data policy before material is sent to it, and use the generation history and connection test to investigate errors or limits.</p>`],
    es: ["Administración", "Gestioná acceso e IA de forma responsable", "Las personas administradoras gestionan usuarios, la configuración activa de IA y el historial de generaciones.", `<h2>Usuarios y roles</h2><p>Usá el área administrativa para actualizar el rol o estado de una cuenta. Aplicá el acceso mínimo: docentes administran sus materias y estudiantes consumen aulas publicadas.</p><h2>Proveedores de IA</h2><table><thead><tr><th>Valor</th><th>Uso</th></tr></thead><tbody><tr><td><code>auto</code></td><td>Usa proveedor primario y fallback configurado.</td></tr><tr><td><code>ollama</code></td><td>Servicio Ollama local o autoalojado.</td></tr><tr><td><code>gemini</code></td><td>API de Google Gemini.</td></tr><tr><td><code>openai</code></td><td>API de OpenAI.</td></tr><tr><td><code>custom</code></td><td>Endpoint compatible con OpenAI.</td></tr><tr><td><code>local</code></td><td>Fallback heurístico integrado.</td></tr></tbody></table><pre><code>AI_PROVIDER=ollama
OLLAMA_HOST=http://127.0.0.1:11434
OLLAMA_MODEL=llama3.2
OLLAMA_EMBED_MODEL=nomic-embed-text</code></pre><h2>Privacidad</h2><p>Guardá las claves como secretos de despliegue, nunca en control de versiones. Revisá la política de datos del proveedor antes de enviar material y usá el historial y prueba de conexión para investigar errores o límites.</p>`],
  },
  operations: {
    en: ["Technical reference", "Operate and deploy reliably", "Processing and generation can run in the background; production needs persistent data services, storage, and workers.", `<h2>Queues and storage</h2><p>Docker starts Horizon in the <code>queue</code> service. In a local environment, run <code>php artisan horizon</code> before processing uploads or generating content. Use persistent storage and backups in production; run <code>php artisan storage:link</code> when serving public local files.</p><h2>Production checklist</h2><ol class="steps"><li class="step">Set <code>APP_ENV=production</code>, <code>APP_DEBUG=false</code>, and a unique <code>APP_KEY</code>.</li><li class="step">Use secure PostgreSQL, Redis, and AI-provider credentials.</li><li class="step">Configure HTTPS and a correct <code>APP_URL</code>.</li><li class="step">Persist and back up database and application storage.</li><li class="step">Run Horizon or a queue worker under a reliable supervisor.</li></ol><h2>Troubleshooting</h2><table><thead><tr><th>Problem</th><th>Check</th></tr></thead><tbody><tr><td>Material fails</td><td>File type and size, worker, document status, and its error message.</td></tr><tr><td>AI generation fails</td><td>Provider, connection test, key, URL, model, rate limit, and worker.</td></tr><tr><td>Students cannot see content</td><td>Course publication state and approved or published lesson states.</td></tr></tbody></table><pre><code>php artisan optimize:clear
php artisan migrate:fresh --seed
npm run build
vendor\\bin\\phpunit</code></pre>`],
    es: ["Referencia técnica", "Operá y desplegá con confianza", "El procesamiento y las generaciones pueden ejecutarse en segundo plano; producción requiere servicios de datos, almacenamiento y workers persistentes.", `<h2>Colas y almacenamiento</h2><p>Docker inicia Horizon en el servicio <code>queue</code>. En local, ejecutá <code>php artisan horizon</code> antes de procesar materiales o generar contenido. Usá almacenamiento persistente y backups en producción; ejecutá <code>php artisan storage:link</code> para servir archivos públicos locales.</p><h2>Lista de producción</h2><ol class="steps"><li class="step">Definí <code>APP_ENV=production</code>, <code>APP_DEBUG=false</code> y un <code>APP_KEY</code> único.</li><li class="step">Usá credenciales seguras para PostgreSQL, Redis y el proveedor de IA.</li><li class="step">Configurá HTTPS y un <code>APP_URL</code> correcto.</li><li class="step">Persistí y respaldá base de datos y almacenamiento.</li><li class="step">Ejecutá Horizon o un worker bajo un supervisor confiable.</li></ol><h2>Diagnóstico</h2><table><thead><tr><th>Problema</th><th>Verificá</th></tr></thead><tbody><tr><td>Falla un material</td><td>Tipo y tamaño, worker, estado del documento y error.</td></tr><tr><td>Falla una generación</td><td>Proveedor, conexión, clave, URL, modelo, límite y worker.</td></tr><tr><td>Estudiantes no ven contenido</td><td>Publicación de la materia y estado aprobado o publicado de lecciones.</td></tr></tbody></table><pre><code>php artisan optimize:clear
php artisan migrate:fresh --seed
npm run build
vendor\\bin\\phpunit</code></pre>`],
  },
  architecture: {
    en: ["Technical reference", "Architecture", "AulaGen combines a Laravel application, a React interface, asynchronous processing, and configurable AI providers.", `<h2>Components</h2><table><thead><tr><th>Layer</th><th>Technology / role</th></tr></thead><tbody><tr><td>Application</td><td>PHP 8.2+, Laravel 12, Horizon, and Sanctum.</td></tr><tr><td>Interface</td><td>Inertia.js, React 18, TypeScript, Vite, and Tailwind CSS.</td></tr><tr><td>Production data</td><td>PostgreSQL 17 with pgvector and Redis.</td></tr><tr><td>AI</td><td>Provider manager, embeddings, vector retrieval, and RAG.</td></tr></tbody></table><h2>From source to classroom</h2><ol class="steps"><li class="step">Educators upload files or external resources.</li><li class="step">A job extracts text, metadata, and searchable chunks.</li><li class="step">Embeddings support relevant retrieval when available.</li><li class="step">AI can propose a structure, lesson content, activities, and glossary.</li><li class="step">Educators review and approve before publication.</li><li class="step">Search and the assistant retrieve course context for students.</li></ol><h2>Documentation deployment</h2><p>GitHub Actions publishes <code>docs/</code> to GitHub Pages when documentation changes. It does not build or run the Laravel application.</p>`],
    es: ["Referencia técnica", "Arquitectura", "AulaGen combina una aplicación Laravel, interfaz React, procesamiento asíncrono y proveedores de IA configurables.", `<h2>Componentes</h2><table><thead><tr><th>Capa</th><th>Tecnología / función</th></tr></thead><tbody><tr><td>Aplicación</td><td>PHP 8.2+, Laravel 12, Horizon y Sanctum.</td></tr><tr><td>Interfaz</td><td>Inertia.js, React 18, TypeScript, Vite y Tailwind CSS.</td></tr><tr><td>Datos de producción</td><td>PostgreSQL 17 con pgvector y Redis.</td></tr><tr><td>IA</td><td>Gestor de proveedores, embeddings, recuperación vectorial y RAG.</td></tr></tbody></table><h2>De fuente a aula</h2><ol class="steps"><li class="step">Docentes suben archivos o recursos externos.</li><li class="step">Un job extrae texto, metadatos y fragmentos consultables.</li><li class="step">Los embeddings permiten recuperación relevante cuando están disponibles.</li><li class="step">La IA puede proponer estructura, contenido, actividades y glosario.</li><li class="step">Docentes revisan y aprueban antes de publicar.</li><li class="step">Búsqueda y asistente recuperan el contexto de la materia.</li></ol><h2>Despliegue de documentación</h2><p>GitHub Actions publica <code>docs/</code> en GitHub Pages cuando cambia la documentación. No compila ni ejecuta la aplicación Laravel.</p>`],
  },
};

const landing = {
  en: {
    eyebrow: "AI-assisted learning, educator-led",
    title: "Turn teaching materials into learning experiences students can follow.",
    lead: "AulaGen helps education teams shape their own sources into structured, reviewable virtual classrooms—without giving up control of what gets published.",
    primary: "Explore the documentation",
    secondary: "View on GitHub",
    proofTitle: "From source material to a published classroom",
    proofLead: "Bring your existing material into one workflow that makes preparation faster and publication safer.",
    features: [
      ["Use the material you trust", "Upload documents and add external resources. AulaGen turns them into a searchable knowledge base for each course."],
      ["Create with an AI copilot", "Generate course structures, lessons, activities, and glossaries from your sources—not a generic starting point."],
      ["Keep educators in control", "Edit, approve, preview, and publish deliberately. Students only see the material your team is ready to share."],
    ],
    audienceTitle: "One platform for the people behind learning",
    audienceLead: "Educators build the experience. Students explore it. Administrators keep the system aligned with institutional needs.",
    audience: [
      ["For educators", "Organize content, assess AI drafts, and publish meaningful learning paths."],
      ["For students", "Navigate lessons, track progress, practice with activities, search, and ask grounded questions."],
      ["For administrators", "Manage roles, AI providers, generation history, and privacy-sensitive configuration."],
    ],
    ctaTitle: "Build the next classroom from the material you already have.",
    ctaLead: "Run AulaGen locally or deploy it on infrastructure you control.",
    cta: "Get started",
  },
  es: {
    eyebrow: "Aprendizaje asistido por IA, liderado por docentes",
    title: "Transformá materiales docentes en experiencias de aprendizaje que los estudiantes puedan recorrer.",
    lead: "AulaGen ayuda a los equipos educativos a convertir sus propias fuentes en aulas virtuales estructuradas y revisables, sin perder el control de lo que se publica.",
    primary: "Explorar la documentación",
    secondary: "Ver en GitHub",
    proofTitle: "Del material fuente a un aula publicada",
    proofLead: "Reuní tu material en un flujo que acelera la preparación y hace más segura la publicación.",
    features: [
      ["Usá material en el que confiás", "Subí documentos y agregá recursos externos. AulaGen los convierte en una base de conocimiento consultable para cada materia."],
      ["Creá con un copiloto de IA", "Generá estructuras, lecciones, actividades y glosarios desde tus fuentes, no desde un punto de partida genérico."],
      ["Mantené el control docente", "Editá, aprobá, previsualizá y publicá deliberadamente. Los estudiantes solo ven el material que el equipo está listo para compartir."],
    ],
    audienceTitle: "Una plataforma para quienes hacen posible aprender",
    audienceLead: "Docentes construyen la experiencia. Estudiantes la exploran. Administradores la alinean con las necesidades institucionales.",
    audience: [
      ["Para docentes", "Organizá contenido, evaluá borradores de IA y publicá recorridos de aprendizaje significativos."],
      ["Para estudiantes", "Navegá lecciones, seguí tu progreso, practicá con actividades, buscá y hacé preguntas contextualizadas."],
      ["Para administradores", "Gestioná roles, proveedores de IA, historial de generaciones y configuración sensible de privacidad."],
    ],
    ctaTitle: "Construí la próxima aula con el material que ya tenés.",
    ctaLead: "Ejecutá AulaGen localmente o desplegalo en infraestructura bajo tu control.",
    cta: "Empezar",
  },
};

function setLanguage(nextLanguage) {
  localStorage.setItem(languageKey, nextLanguage);
  const url = new URL(window.location);
  url.searchParams.set("lang", nextLanguage);
  window.location.assign(url);
}

function navigation(current) {
  const t = chrome[language];
  return `<header class="site-header"><div class="header-inner"><a class="brand" href="./"><span class="brand-mark">A</span>${t.docs}</a><nav class="top-nav" aria-label="Primary navigation"><a href="instalacion.html"${current === "installation" ? ' aria-current="page"' : ""}>${t.install}</a><a href="docentes.html"${current === "teachers" ? ' aria-current="page"' : ""}>${t.teachers}</a><a href="arquitectura.html"${current === "architecture" ? ' aria-current="page"' : ""}>${t.architecture}</a><a class="github-link" href="https://github.com/alyohara/aulagen">${t.github}</a><button class="language-switcher" type="button" data-language-toggle>ES</button></nav></div></header>`;
}

function sidebar(current) {
  const t = chrome[language];
  return `<aside class="sidebar" aria-label="Documentation sections"><div class="sidebar-group"><p class="sidebar-title">${t.start}</p><a href="./">${t.home}</a><a href="instalacion.html"${current === "installation" ? ' aria-current="page"' : ""}>${t.install}</a></div><div class="sidebar-group"><p class="sidebar-title">${t.use}</p><a href="docentes.html"${current === "teachers" ? ' aria-current="page"' : ""}>${t.teachers}</a><a href="administracion.html"${current === "administration" ? ' aria-current="page"' : ""}>${t.admin}</a></div><div class="sidebar-group"><p class="sidebar-title">${t.reference}</p><a href="operacion.html"${current === "operations" ? ' aria-current="page"' : ""}>${t.operations}</a><a href="arquitectura.html"${current === "architecture" ? ' aria-current="page"' : ""}>${t.architecture}</a></div></aside>`;
}

function renderGuide() {
  const page = document.body.dataset.page;
  if (page === "landing") {
    renderLanding();
    return;
  }
  if (!page) return;
  const [eyebrow, title, lead, content] = guides[page][language];
  document.documentElement.lang = language;
  document.title = `${title} | AulaGen`;
  document.body.innerHTML = `${navigation(page)}<main class="layout">${sidebar(page)}<article class="content"><p class="eyebrow">${eyebrow}</p><h1>${title}</h1><p class="lead">${lead}</p>${content}</article></main><footer class="page-footer"><div class="footer-inner">AulaGen · <a href="https://github.com/alyohara/aulagen">GitHub</a> · MIT License</div></footer>`;
  document.querySelector("[data-language-toggle]").addEventListener("click", () => setLanguage(language === "en" ? "es" : "en"));
  document.querySelector("[data-language-toggle]").textContent = language === "en" ? "ES" : "EN";
}

function renderLanding() {
  const t = landing[language];
  const cards = (items) => items.map(([title, description]) => `<article class="benefit"><h3>${title}</h3><p>${description}</p></article>`).join("");
  document.documentElement.lang = language;
  document.title = "AulaGen | AI-assisted classrooms";
  document.body.innerHTML = `<header class="site-header"><div class="header-inner"><a class="brand" href="./"><span class="brand-mark">A</span>AulaGen</a><nav class="top-nav" aria-label="Primary navigation"><a href="instalacion.html">${chrome[language].install}</a><a href="docentes.html">${chrome[language].teachers}</a><a href="https://github.com/alyohara/aulagen">GitHub</a><button class="language-switcher" type="button" data-language-toggle>ES</button></nav></div></header><main class="landing"><section class="landing-hero"><div class="header-inner"><p class="eyebrow">${t.eyebrow}</p><h1>${t.title}</h1><p class="lead">${t.lead}</p><div class="cta-row"><a class="button" href="instalacion.html">${t.primary}</a><a class="button button-secondary" href="https://github.com/alyohara/aulagen">${t.secondary}</a></div></div></section><section class="landing-section"><div class="header-inner"><h2>${t.proofTitle}</h2><p class="lead">${t.proofLead}</p><div class="benefit-grid">${cards(t.features)}</div></div></section><section class="landing-section"><div class="header-inner"><h2>${t.audienceTitle}</h2><p class="lead">${t.audienceLead}</p><div class="benefit-grid">${cards(t.audience)}</div></div></section><section class="landing-section"><div class="header-inner"><h2>${t.ctaTitle}</h2><p class="lead">${t.ctaLead}</p><div class="cta-row"><a class="button" href="instalacion.html">${t.cta}</a></div></div></section></main><footer class="page-footer"><div class="footer-inner">AulaGen · <a href="https://github.com/alyohara/aulagen">GitHub</a> · MIT License</div></footer>`;
  const toggle = document.querySelector("[data-language-toggle]");
  toggle.addEventListener("click", () => setLanguage(language === "en" ? "es" : "en"));
  toggle.textContent = language === "en" ? "ES" : "EN";
}

renderGuide();

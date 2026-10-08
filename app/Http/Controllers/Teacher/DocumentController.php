<?php

namespace App\Http\Controllers\Teacher;

use App\Enums\DocumentStatus;
use App\Enums\DocumentType;
use App\Http\Controllers\Controller;
use App\Jobs\ProcessDocument;
use App\Models\Course;
use App\Models\Document;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class DocumentController extends Controller
{
    private const MAX_MB = 40;

    public function index(Course $course)
    {
        $this->authorize('view', $course);

        return [
            'documents' => $course->documents()->with('module')->latest()->get()->map(fn (Document $doc) => $this->transform($doc)),
            'modules' => $course->modules()->orderBy('position')->get()->map(fn ($m) => [
                'id' => $m->id,
                'title' => $m->title,
                'type' => $m->type,
            ]),
        ];
    }

    public function store(Request $request, Course $course): RedirectResponse
    {
        $this->authorize('update', $course);

        $validated = $request->validate([
            'files' => ['nullable', 'array', 'max:20'],
            'files.*' => ['file', 'max:'.(self::MAX_MB * 1024), 'mimes:pdf,docx,pptx,xlsx,txt,md,markdown,csv,png,jpg,jpeg,webp,gif'],
            'link_url' => ['nullable', 'url:http,https', 'max:500'],
            'link_title' => ['nullable', 'string', 'max:180'],
            'link_type' => ['nullable', Rule::in(['link', 'video'])],
            'text_title' => ['nullable', 'string', 'max:180'],
            'text_content' => ['nullable', 'string', 'max:400000'],
            'module_id' => ['nullable', 'integer', Rule::exists('modules', 'id')->where('course_id', $course->id)],
        ], [
            'files.*.mimes' => 'Formato no permitido. Se aceptan PDF, DOCX, PPTX, XLSX, TXT, Markdown, CSV e imágenes.',
            'files.*.max' => 'Cada archivo puede pesar como máximo '.self::MAX_MB.' MB.',
        ]);

        $created = 0;
        $moduleId = $validated['module_id'] ?? null;

        foreach ($request->file('files', []) as $file) {
            if (! $file->isValid()) {
                continue;
            }

            $type = DocumentType::fromMime((string) $file->getMimeType(), (string) $file->getClientOriginalExtension());

            $path = $file->store('courses/'.$course->id.'/documents', 'local');

            $document = Document::create([
                'course_id' => $course->id,
                'module_id' => $moduleId,
                'uploaded_by' => $request->user()->id,
                'original_name' => $file->getClientOriginalName(),
                'type' => $type,
                'mime_type' => $file->getMimeType(),
                'size_bytes' => $file->getSize() ?: 0,
                'disk' => 'local',
                'path' => $path,
                'status' => DocumentStatus::Pending,
            ]);

            ProcessDocument::dispatch($document);
            $created++;
        }

        if (filled($validated['link_url'] ?? null)) {
            $document = Document::create([
                'course_id' => $course->id,
                'module_id' => $moduleId,
                'uploaded_by' => $request->user()->id,
                'original_name' => $validated['link_title'] ?: $validated['link_url'],
                'type' => $validated['link_type'] === 'video' ? DocumentType::Video : DocumentType::Link,
                'source_url' => $validated['link_url'],
                'size_bytes' => 0,
                'path' => $validated['link_url'],
                'disk' => 'local',
                'status' => DocumentStatus::Pending,
            ]);

            ProcessDocument::dispatch($document);
            $created++;
        }

        if (filled($validated['text_content'] ?? null)) {
            $name = ($validated['text_title'] ?: 'Texto pegado').'.txt';
            $path = 'courses/'.$course->id.'/documents/'.now()->format('Ymd_His').'-'.uniqid().'.txt';

            Storage::disk('local')->put($path, $validated['text_content']);

            $document = Document::create([
                'course_id' => $course->id,
                'module_id' => $moduleId,
                'uploaded_by' => $request->user()->id,
                'original_name' => $validated['text_title'] ?: 'Texto pegado',
                'type' => DocumentType::Text,
                'mime_type' => 'text/plain',
                'size_bytes' => strlen($validated['text_content']),
                'disk' => 'local',
                'path' => $path,
                'status' => DocumentStatus::Pending,
            ]);

            ProcessDocument::dispatch($document);
            $created++;
        }

        if ($created === 0) {
            return back()->withErrors(['files' => 'Cargá al menos un archivo, un enlace o un texto.']);
        }

        return back()->with('success', $created.($created === 1 ? ' material cargado' : ' materiales cargados').'. El procesamiento corre en segundo plano.');
    }

    public function destroy(Course $course, Document $document): RedirectResponse
    {
        $this->authorize('update', $course);

        abort_unless($document->course_id === $course->id, 404);

        if (! in_array($document->type, [DocumentType::Link, DocumentType::Video], true) && filled($document->path)) {
            Storage::disk($document->disk)->delete($document->path);
        }

        $document->delete();

        return back()->with('success', 'Material eliminado.');
    }

    public function reprocess(Course $course, Document $document): RedirectResponse
    {
        $this->authorize('update', $course);

        abort_unless($document->course_id === $course->id, 404);

        $document->forceFill(['status' => DocumentStatus::Pending, 'error_message' => null])->save();

        ProcessDocument::dispatch($document);

        return back()->with('success', 'Reprocesando "'.$document->original_name.'".');
    }

    public function assign(Request $request, Course $course, Document $document): RedirectResponse
    {
        $this->authorize('update', $course);

        abort_unless($document->course_id === $course->id, 404);

        $data = $request->validate([
            'module_id' => ['nullable', 'integer', Rule::exists('modules', 'id')->where('course_id', $course->id)],
        ]);

        $document->update(['module_id' => $data['module_id'] ?? null]);

        return back()->with('success', 'Material reasignado.');
    }

    private function transform(Document $doc): array
    {
        return [
            'id' => $doc->id,
            'name' => $doc->original_name,
            'type' => $doc->type->value,
            'type_label' => $doc->type->label(),
            'size' => $doc->humanSize(),
            'status' => $doc->status->value,
            'status_label' => $doc->status->label(),
            'error' => $doc->error_message,
            'chunk_count' => $doc->chunk_count,
            'page_count' => $doc->page_count,
            'source_url' => $doc->source_url,
            'module' => $doc->module ? ['id' => $doc->module->id, 'title' => $doc->module->title] : null,
            'created_at' => $doc->created_at?->format('d/m/Y H:i'),
            'processed_at' => $doc->processed_at?->format('d/m/Y H:i'),
        ];
    }
}

<?php

use App\Models\Course;
use App\Http\Controllers\Aula\AulaController;
use App\Http\Controllers\Admin\AdminController;
use App\Http\Controllers\Admin\AiSettingsController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\Teacher\ActivityController;
use App\Http\Controllers\Teacher\AiController;
use App\Http\Controllers\Teacher\BibliographyController;
use App\Http\Controllers\Teacher\CourseController;
use App\Http\Controllers\Teacher\DashboardController;
use App\Http\Controllers\Teacher\DocumentController;
use App\Http\Controllers\Teacher\StructureController;
use App\Http\Controllers\LocaleController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    if (auth()->check()) {
        return redirect()->route('dashboard');
    }

    $demo = Course::published()->orderBy('id')->first();

    return Inertia::render('Welcome', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
        'demo' => $demo ? [
            'name' => $demo->name,
            'url' => route('aula.home', $demo),
        ] : null,
    ]);
});

Route::put('/locale', [LocaleController::class, 'update'])->name('locale.update');

/*
|--------------------------------------------------------------------------
| Panel del docente
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'verified', 'can:manage-courses'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    Route::get('/courses/create', [CourseController::class, 'create'])->name('courses.create');
    Route::post('/courses', [CourseController::class, 'store'])->name('courses.store');

    Route::prefix('courses/{course}')->name('courses.')->group(function () {
        Route::get('/', [CourseController::class, 'overview'])->name('overview');
        Route::get('/editar', [CourseController::class, 'edit'])->name('edit');
        Route::put('/editar', [CourseController::class, 'update'])->name('update');
        Route::delete('/', [CourseController::class, 'destroy'])->name('destroy');
        Route::post('/publicar', [CourseController::class, 'publish'])->name('publish');
        Route::post('/despublicar', [CourseController::class, 'unpublish'])->name('unpublish');
        Route::put('/configuracion', [CourseController::class, 'settings'])->name('settings');
        Route::get('/vista-previa', [CourseController::class, 'preview'])->name('preview');

        // Contenido / estructura
        Route::get('/contenido', [StructureController::class, 'index'])->name('content');
        Route::post('/modulos', [StructureController::class, 'storeModule'])->name('modules.store');
        Route::put('/modulos/{module}', [StructureController::class, 'updateModule'])->name('modules.update');
        Route::delete('/modulos/{module}', [StructureController::class, 'destroyModule'])->name('modules.destroy');
        Route::post('/modulos/reorder', [StructureController::class, 'reorderModules'])->name('modules.reorder');
        Route::post('/modulos/{module}/lecciones', [StructureController::class, 'storeLesson'])->name('lessons.store');
        Route::post('/modulos/{module}/reorder', [StructureController::class, 'reorderLessons'])->name('lessons.reorder');
        Route::get('/lecciones/{lesson}/editar', [StructureController::class, 'editLesson'])->name('lessons.edit');
        Route::put('/lecciones/{lesson}', [StructureController::class, 'updateLesson'])->name('lessons.update');
        Route::delete('/lecciones/{lesson}', [StructureController::class, 'destroyLesson'])->name('lessons.destroy');
        Route::post('/lecciones/{lesson}/estado', [StructureController::class, 'setLessonStatus'])->name('lessons.status');
        Route::post('/lecciones/{lesson}/generar', [StructureController::class, 'generateLesson'])->name('lessons.generate');
        Route::post('/generar-contenido', [StructureController::class, 'generateAllContent'])->name('content.generate-all');

        // Estructura con IA
        Route::post('/ia/estructura', [StructureController::class, 'proposeStructure'])->name('structure.propose');
        Route::post('/ia/estructura/aplicar', [StructureController::class, 'applyStructure'])->name('structure.apply');
        Route::post('/ia/estructura/descartar', [StructureController::class, 'discardStructure'])->name('structure.discard');

        // Materiales
        Route::get('/materiales', [DocumentController::class, 'index'])->name('documents.index');
        Route::post('/materiales', [DocumentController::class, 'store'])->middleware('throttle:uploads')->name('documents.store');
        Route::delete('/materiales/{document}', [DocumentController::class, 'destroy'])->name('documents.destroy');
        Route::post('/materiales/{document}/reprocesar', [DocumentController::class, 'reprocess'])->name('documents.reprocess');
        Route::put('/materiales/{document}/asignar', [DocumentController::class, 'assign'])->name('documents.assign');

        // Actividades
        Route::get('/actividades', [ActivityController::class, 'index'])->name('activities.index');
        Route::post('/actividades', [ActivityController::class, 'store'])->name('activities.store');
        Route::put('/actividades/{activity}', [ActivityController::class, 'update'])->name('activities.update');
        Route::delete('/actividades/{activity}', [ActivityController::class, 'destroy'])->name('activities.destroy');
        Route::post('/actividades/generar', [ActivityController::class, 'generate'])->name('activities.generate');
        Route::put('/actividades/{activity}/preguntas/{question}', [ActivityController::class, 'updateQuestion'])->name('activities.questions.update');

        // Bibliografía
        Route::get('/bibliografia', [BibliographyController::class, 'index'])->name('bibliography.index');
        Route::post('/bibliografia', [BibliographyController::class, 'store'])->name('bibliography.store');
        Route::put('/bibliografia/{bibliography}', [BibliographyController::class, 'update'])->name('bibliography.update');
        Route::delete('/bibliografia/{bibliography}', [BibliographyController::class, 'destroy'])->name('bibliography.destroy');

        // IA
        Route::get('/ia', [AiController::class, 'index'])->name('ai.index');
        Route::post('/ia/glosario', [AiController::class, 'generateGlossary'])->name('ai.glossary');
        Route::put('/ia/glosario', [AiController::class, 'updateGlossary'])->name('ai.glossary.update');
        Route::post('/ia/actualizar', [AiController::class, 'clearCache'])->name('ai.refresh');
    });
});

/*
|--------------------------------------------------------------------------
| Administración
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'verified', 'can:admin-panel'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/', [AdminController::class, 'index'])->name('dashboard');
    Route::get('/usuarios', [AdminController::class, 'users'])->name('users');
    Route::patch('/usuarios/{user}', [AdminController::class, 'updateUser'])->name('users.update');
    Route::get('/generaciones', [AdminController::class, 'generations'])->name('generations');

    Route::get('/ia', [AiSettingsController::class, 'index'])->name('ai.index');
    Route::put('/ia', [AiSettingsController::class, 'update'])->name('ai.update');
    Route::post('/ia/probar', [AiSettingsController::class, 'test'])->name('ai.test');
});

/*
|--------------------------------------------------------------------------
| Aula pública (alumnos)
|--------------------------------------------------------------------------
*/
Route::group([], function () {
    Route::get('/aula/{course:slug}/buscar', [AulaController::class, 'search'])->name('aula.search');
    Route::post('/aula/{course:slug}/preguntar', [AulaController::class, 'ask'])->middleware('throttle:ai')->name('aula.ask');
    Route::get('/aula/{course:slug}/actividades', [AulaController::class, 'activities'])->name('aula.activities');
    Route::post('/aula/{course:slug}/actividades/{activity}/corregir', [AulaController::class, 'checkAnswers'])->name('aula.check');
    Route::get('/aula/{course:slug}/bibliografia', [AulaController::class, 'bibliography'])->name('aula.bibliography');
    Route::get('/aula/{course:slug}/glosario', [AulaController::class, 'glossary'])->name('aula.glossary');
    Route::get('/aula/{course:slug}/complementario', [AulaController::class, 'complementary'])->name('aula.complementary');
    Route::get('/aula/{course:slug}/descargar/{document}', [AulaController::class, 'download'])->name('aula.download');
    Route::post('/aula/{course:slug}/progreso', [AulaController::class, 'progress'])->name('aula.progress');
    Route::get('/aula/{course:slug}/{module:slug}', [AulaController::class, 'module'])->name('aula.module');
    Route::get('/aula/{course:slug}/{module:slug}/{lesson:slug}', [AulaController::class, 'lesson'])->name('aula.lesson');
    Route::get('/aula/{course:slug}', [AulaController::class, 'home'])->name('aula.home');
});

require __DIR__.'/auth.php';

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

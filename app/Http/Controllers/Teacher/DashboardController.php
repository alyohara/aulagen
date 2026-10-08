<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Models\Course;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user();

        $query = Course::query()->withCount(['modules', 'documents', 'lessons']);

        if (! $user->isAdmin()) {
            $query->where(function ($builder) use ($user) {
                $builder->where('owner_id', $user->id)
                    ->orWhereHas('collaborators', fn ($q) => $q->whereKey($user->id));
            });
        }

        $courses = $query->latest()->get()->map(fn (Course $course) => [
            'id' => $course->id,
            'name' => $course->name,
            'slug' => $course->slug,
            'institution' => $course->institution,
            'status' => $course->status->value,
            'status_label' => $course->status->label(),
            'modules_count' => $course->modules_count,
            'documents_count' => $course->documents_count,
            'lessons_count' => $course->lessons_count,
            'published_lessons' => $course->lessons()->whereIn('status', ['approved', 'published'])->count(),
            'pending_lessons' => $course->lessons()->whereIn('status', ['draft', 'generated', 'review'])->count(),
            'updated_at' => $course->updated_at?->diffForHumans(),
            'url' => route('aula.home', $course),
        ]);

        return Inertia::render('Dashboard', [
            'courses' => $courses,
            'stats' => [
                'courses' => $courses->count(),
                'published' => $courses->where('status', 'published')->count(),
                'documents' => (int) $courses->sum('documents_count'),
                'lessons' => (int) $courses->sum('lessons_count'),
            ],
        ]);
    }
}

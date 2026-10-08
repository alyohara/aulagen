<?php

namespace App\Policies;

use App\Models\Course;
use App\Models\User;

class CoursePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isTeacher();
    }

    public function view(User $user, Course $course): bool
    {
        return $user->teaches($course);
    }

    public function update(User $user, Course $course): bool
    {
        return $user->teaches($course);
    }

    public function publish(User $user, Course $course): bool
    {
        return $user->isAdmin() || $course->owner_id === $user->id;
    }

    public function delete(User $user, Course $course): bool
    {
        return $user->isAdmin() || $course->owner_id === $user->id;
    }

    public function manageSettings(User $user, Course $course): bool
    {
        return $user->isAdmin() || $course->owner_id === $user->id;
    }
}

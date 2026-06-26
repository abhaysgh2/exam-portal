<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Exam;
use App\Models\ExamGroup;
use App\Models\Registration;
use App\Models\User;
use Illuminate\Http\Request;

class ExamGroupController extends Controller
{
    public function index(Request $request)
    {
        $query = ExamGroup::with('exams:id,title,status,created_by', 'students:id,full_name,email,role');

        if ($request->user()->role === 'examiner') {
            $query->where('created_by', $request->user()->id);
        }

        return response()->json($query->latest()->paginate($request->integer('limit', 20)));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
        ]);

        $group = ExamGroup::create([
            ...$data,
            'created_by' => $request->user()->id,
        ]);

        return response()->json($group->load('exams', 'students'), 201);
    }

    public function update(Request $request, ExamGroup $group)
    {
        $this->authorizeGroup($request, $group);

        $data = $request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
        ]);

        $group->update($data);

        return response()->json($group->fresh('exams', 'students'));
    }

    public function destroy(Request $request, ExamGroup $group)
    {
        $this->authorizeGroup($request, $group);
        $group->delete();

        return response()->noContent();
    }

    public function attachExam(Request $request, ExamGroup $group)
    {
        $this->authorizeGroup($request, $group);

        $data = $request->validate([
            'exam_id' => ['required', 'uuid', 'exists:exams,id'],
        ]);

        $exam = Exam::findOrFail($data['exam_id']);
        abort_unless($request->user()->role === 'admin' || $exam->created_by === $request->user()->id, 403, 'You can only add tests you created.');

        $group->exams()->syncWithoutDetaching([$exam->id]);
        $this->registerGroupStudentsForExam($group, $exam);

        return response()->json($group->fresh('exams', 'students'));
    }

    public function detachExam(Request $request, ExamGroup $group, Exam $exam)
    {
        $this->authorizeGroup($request, $group);
        abort_unless($request->user()->role === 'admin' || $exam->created_by === $request->user()->id, 403, 'You can only remove tests you created.');

        $group->exams()->detach($exam->id);

        return response()->noContent();
    }

    public function attachStudent(Request $request, ExamGroup $group)
    {
        $this->authorizeGroup($request, $group);

        $data = $request->validate([
            'user_id' => ['required', 'uuid', 'exists:users,id'],
        ]);

        $student = User::findOrFail($data['user_id']);
        abort_unless($student->role === 'student', 422, 'Only student users can be added to a test group.');

        $group->students()->syncWithoutDetaching([$student->id]);
        $group->exams()->get()->each(fn (Exam $exam) => $this->registerStudentForExam($student, $exam));

        return response()->json($group->fresh('exams', 'students'));
    }

    public function detachStudent(Request $request, ExamGroup $group, User $user)
    {
        $this->authorizeGroup($request, $group);
        $group->students()->detach($user->id);

        return response()->noContent();
    }

    private function authorizeGroup(Request $request, ExamGroup $group): void
    {
        abort_unless($request->user()->role === 'admin' || $group->created_by === $request->user()->id, 403);
    }

    private function registerGroupStudentsForExam(ExamGroup $group, Exam $exam): void
    {
        $group->students()->where('role', 'student')->get()
            ->each(fn (User $student) => $this->registerStudentForExam($student, $exam));
    }

    private function registerStudentForExam(User $student, Exam $exam): void
    {
        Registration::firstOrCreate(
            ['exam_id' => $exam->id, 'user_id' => $student->id],
            ['status' => 'registered', 'registered_at' => now()],
        );
    }
}

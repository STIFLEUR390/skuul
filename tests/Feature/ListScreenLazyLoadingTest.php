<?php

namespace Tests\Feature;

use App\Livewire\AttendanceRegister;
use App\Livewire\ListFeeInvoicesTable;
use App\Livewire\ListStudentsTable;
use App\Models\AcademicCycleSection;
use App\Models\AcademicLevel;
use App\Models\AcademicYear;
use App\Models\FeeInvoice;
use App\Models\StudentRecord;
use App\Traits\FeatureTestTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * A list screen eager loads everything it shows.
 *
 * Lazy loading is prevented application-wide outside production, so rendering
 * these screens is the check: a missing with() throws instead of quietly
 * firing one query per row.
 */
class ListScreenLazyLoadingTest extends TestCase
{
    use FeatureTestTrait;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->assertTrue(Model::preventsLazyLoading(), 'Lazy loading is not prevented, so these screens prove nothing.');
    }

    public function test_the_student_list_renders_without_a_lazy_load(): void
    {
        $this->authorized_user(['read student']);
        $this->enrolledStudent();

        Livewire::test(ListStudentsTable::class)->assertOk();
    }

    public function test_the_fee_invoice_list_renders_without_a_lazy_load(): void
    {
        $school = $this->workingSchool();
        $this->authorized_user(['read fee invoice']);
        $student = $this->enrolledStudent();

        FeeInvoice::factory()->for($student->user)->create([
            'school_id' => $school->id,
            'student_record_id' => $student->id,
        ]);

        Livewire::test(ListFeeInvoicesTable::class, ['status' => 'all'])->assertOk();
    }

    public function test_the_attendance_register_renders_without_a_lazy_load(): void
    {
        $this->authorized_user(['read attendance', 'take attendance']);
        $student = $this->enrolledStudent();

        Livewire::test(AttendanceRegister::class, [
            'academicCycleSectionId' => (string) $student->academic_cycle_section_id,
            'attendedOn' => now()->toDateString(),
        ])->assertOk();
    }

    /**
     * Create an enrolled learner in the working school.
     */
    private function enrolledStudent(): StudentRecord
    {
        $school = $this->workingSchool();
        $academicYear = current_academic_year() ?? AcademicYear::factory()->create(['school_id' => $school->id]);
        $academicLevel = AcademicLevel::factory()->create(['school_id' => $school->id]);
        $section = AcademicCycleSection::factory()->create([
            'school_id' => $school->id,
            'academic_year_id' => $academicYear->id,
            'academic_level_id' => $academicLevel->id,
        ]);

        $student = StudentRecord::factory()->create([
            'school_id' => $school->id,
            'academic_cycle_section_id' => $section->id,
        ]);

        $this->memberOf($school, $student->user);

        return $student;
    }
}

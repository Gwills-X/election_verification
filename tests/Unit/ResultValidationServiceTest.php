<?php
namespace Tests\Unit;
use App\Services\ResultValidationService as S;
use PHPUnit\Framework\TestCase;
class ResultValidationServiceTest extends TestCase
{
   private function svc(): S { return new S(['apc', 'pdp'], ['state', 'polling_unit_code', 'lga', 'ward'], true, true, true, true); }
private function row(array $o = []): array
{
    return array_merge(['state' => 'S', 'polling_unit_code' => 'T1', 'polling_unit_name' => 'PU 1', 'lga' => 'A', 'ward' => 'W',
        'registered_voters' => 600, 'accredited_voters' => 500,
        'apc' => 200, 'pdp' => 200, 'rejected_votes' => 10, 'total_votes' => 410], $o);
}
// ...all test methods unchanged, plus the accredited-skip test:
    private function rules(array $r): array { return array_column($r['violations'], 'rule'); }

    public function test_valid_record(): void { $this->assertSame(S::VALID, $this->svc()->validate($this->row())['status']); }
    public function test_negative_vote(): void
    {
        $r = $this->svc()->validate($this->row(['apc' => -20, 'total_votes' => 190]));
        $this->assertSame(S::INVALID, $r['status']); $this->assertContains('NEGATIVE_VALUE', $this->rules($r));
    }
    public function test_total_exceeds_accredited(): void
    {
        $r = $this->svc()->validate($this->row(['apc' => 300, 'pdp' => 340, 'total_votes' => 650]));
        $this->assertSame(S::INVALID, $r['status']); $this->assertContains('ACCREDITED_VOTER_CHECK', $this->rules($r));
    }
    public function test_candidate_total_mismatch(): void
    {
        $r = $this->svc()->validate($this->row(['total_votes' => 450]));
        $this->assertSame(S::INVALID, $r['status']); $this->assertContains('CANDIDATE_TOTAL_CHECK', $this->rules($r));
    }
    public function test_duplicate_is_flagged_not_invalid(): void
    {
        $this->assertSame(S::FLAGGED, $this->svc()->validate($this->row(), true)['status']);
    }
    public function test_missing_required_field(): void
    {
        $r = $this->svc()->validate($this->row(['lga' => '']));
        $this->assertSame(S::INVALID, $r['status']); $this->assertContains('REQUIRED_FIELD', $this->rules($r));
    }
    public function test_invalid_number(): void
    {
        $r = $this->svc()->validate($this->row(['accredited_voters' => 'four hundred']));
        $this->assertContains('DATA_TYPE', $this->rules($r));
    }
   public function test_accredited_rule_skipped_when_column_absent(): void
{
    $svc = new S([], ['state', 'polling_unit_code', 'lga', 'ward'], false, false, false, true);
    $r = $svc->validate(['state' => 'S', 'polling_unit_code' => 'T', 'polling_unit_name' => 'PU', 'lga' => 'A', 'ward' => 'W', 'total_votes' => 9999]);
    $this->assertContains('ACCREDITED_VOTER_CHECK', $this->rules($r));
    $this->assertSame(S::VALID, $r['status']); // skipped rule must NOT invalidate the record
}
    public function test_deterministic(): void { $this->assertSame($this->svc()->validate($this->row()), $this->svc()->validate($this->row())); }
}

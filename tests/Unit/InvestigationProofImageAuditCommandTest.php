<?php

namespace Tests\Unit;

use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class InvestigationProofImageAuditCommandTest extends TestCase
{
    #[Test]
    public function proof_image_audit_is_dry_run_by_default_and_guards_cleanup(): void
    {
        $command = file_get_contents(app_path('Console/Commands/AuditInvestigationProofImages.php'));

        $this->assertStringContainsString("reports:audit-proof-images", $command);
        $this->assertStringContainsString('{--delete', $command);
        $this->assertStringContainsString("DELETE-ORPHANED-PROOF-IMAGES", $command);
        $this->assertStringContainsString("if (\$delete && \$summary['storage_error'] > 0)", $command);
        $this->assertStringContainsString("\$configuration['throw'] = true", $command);
        $this->assertStringContainsString('This was a dry run; nothing was deleted.', $command);
        $this->assertStringNotContainsString('MediaPath::delete(', $command);
    }
}

<?php

namespace Tests\Unit;

use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class SavedBoundaryEditingUiSemanticsTest extends TestCase
{
    #[Test]
    public function chief_can_edit_and_persist_saved_boundary_vertices(): void
    {
        $script = file_get_contents(resource_path('js/map.js'));

        $this->assertStringContainsString('shapeGeometryEditBtn_', $script);
        $this->assertStringContainsString('beginShapeGeometryEdit', $script);
        $this->assertStringContainsString('layer.editing.enable()', $script);
        $this->assertStringContainsString('data-shape-geometry-save', $script);
        $this->assertStringContainsString('saveShapeGeometryEdit', $script);
        $this->assertStringContainsString("method: 'PUT'", $script);
        $this->assertStringContainsString('geojson: editedGeojsonForShape(editState)', $script);
        $this->assertStringContainsString('cancelShapeGeometryEdit', $script);
    }
}

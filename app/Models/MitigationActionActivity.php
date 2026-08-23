<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MitigationActionActivity extends Model
{
    protected $primaryKey = 'mitigation_action_activity_id';

    protected $fillable = ['mitigation_action_id', 'actor_user_id', 'event_type', 'old_status', 'new_status', 'notes', 'metadata'];

    protected function casts(): array
    {
        return ['metadata' => 'array'];
    }

    public function action()
    {
        return $this->belongsTo(MitigationAction::class, 'mitigation_action_id', 'mitigation_action_id');
    }

    public function actor()
    {
        return $this->belongsTo(User::class, 'actor_user_id', 'user_id');
    }
}

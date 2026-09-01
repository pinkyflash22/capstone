<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\Incident;

class Route extends Model
{
    use HasFactory;
    
    protected $primaryKey = 'route_id';
    
    protected $fillable = [
        'coordinates',
        'total_distance',
        'estimated_time',
        'status',
        'route_source',
        'destination_title',
        'destination_lat',
        'destination_lng',
        'origin_lat',
        'origin_lng',
        'current_position_index',
        'current_lat',
        'current_lng',
        'progress_percentage',
        'started_by',
        'started_at',
        'last_updated_at',
        'incident_id'
    ];
    
    protected $casts = [
        'coordinates' => 'json',
        'total_distance' => 'float',
        'estimated_time' => 'float',
        'origin_lat' => 'float',
        'origin_lng' => 'float',
        'current_position_index' => 'integer',
        'current_lat' => 'float',
        'current_lng' => 'float',
        'progress_percentage' => 'float',
        'started_at' => 'datetime',
        'last_updated_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];
    
    // Helper method to get formatted coordinates
    public function getFormattedCoordinates()
    {
        $coordinates = is_string($this->coordinates) 
            ? json_decode($this->coordinates, true) 
            : $this->coordinates;

        if (!is_array($coordinates)) {
            return [];
        }
            
        // Flatten triple nested arrays if needed
        if (is_array($coordinates) && isset($coordinates[0][0]) && is_array($coordinates[0][0])) {
            $coordinates = $coordinates[0];
        }
        
        return $coordinates;
    }
    
    // Helper method to get points for Leaflet (lat, lng format)
    public function getLeafletPoints()
    {
        $coordinates = $this->getFormattedCoordinates();
        $points = [];
        
        foreach ($coordinates as $coord) {
            if (is_array($coord) && count($coord) >= 2) {
                // Convert [lng, lat] to [lat, lng] for Leaflet
                $points[] = [$coord[1], $coord[0]];
            }
        }
        
        return $points;
    }
    
    // Helper method to get remaining points from current position
    public function getRemainingPoints()
    {
        $points = $this->getLeafletPoints();
        $currentIndex = $this->current_position_index;
        
        return array_slice($points, $currentIndex);
    }
    
    // Helper method to check if route is active
    public function isActive()
    {
        return in_array($this->status, ['active', 'moving']);
    }
    
    // Helper method to check if route is completed
    public function isCompleted()
    {
        return $this->progress_percentage >= 99.9;
    }
    
    // Scope for active routes
    public function scopeActive($query)
    {
        return $query->whereIn('status', ['active', 'moving']);
    }
    
    // Scope for routes by user
    public function scopeStartedBy($query, $userName)
    {
        return $query->where('started_by', $userName);
    }

    public function incident(){
        return $this->belongsTo(Incident::class,'incident_id','incident_id');
    }
}

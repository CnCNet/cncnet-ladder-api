<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class QmMapPick extends Model
{
    protected $fillable = [
        'user_id',
        'ladder_id',
        'qm_map_id',
        'side_index',
    ];

    protected $casts = [
        'side_index' => 'integer',
    ];

    /**
     * Get the user that owns this map pick.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the ladder for this map pick.
     */
    public function ladder()
    {
        return $this->belongsTo(Ladder::class);
    }

    /**
     * Get the QM map for this map pick.
     */
    public function qmMap()
    {
        return $this->belongsTo(QmMap::class);
    }

    /**
     * Check if this pick is a veto (map rejection).
     */
    public function isVeto(): bool
    {
        return $this->side_index === -1;
    }

    /**
     * Check if this pick is random faction.
     */
    public function isRandom(): bool
    {
        return $this->side_index === -3;
    }

    /**
     * Validate side_index is within allowed range for ladder.
     *
     * @param int $sideIndex The side index to validate
     * @param Ladder $ladder The ladder to validate against
     * @return bool True if valid, false otherwise
     */
    public static function validateSideIndex(int $sideIndex, Ladder $ladder): bool
    {
        // -3 = random, -1 = veto
        if (in_array($sideIndex, [-3, -1])) {
            return true;
        }

        // Check if side_index exists for this ladder
        $maxSide = $ladder->sides()->max('local_id');
        return $sideIndex >= 0 && $sideIndex <= $maxSide;
    }
}

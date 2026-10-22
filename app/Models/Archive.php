<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Archive extends Model
{
    protected $fillable = [
        'archivable_type',
        'archivable_id',
        'archived_by',
        'archived_at',
        'reason',
        'snapshot',
    ];

    protected function casts(): array
    {
        return [
            'archived_at' => 'datetime',
            'snapshot' => 'array',
        ];
    }

    public function archivable(): MorphTo
    {
        return $this->morphTo();
    }

    public function archivedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'archived_by');
    }

    /**
     * Record an archive entry WITHOUT copying the full record into the
     * snapshot column. The record is fetched live via the archivable
     * relation when displayed, which keeps storage usage minimal.
     *
     * Pass $snapshot only when the record is about to be permanently
     * deleted (so the data survives after the source row is gone).
     */
    public static function log(Model $archivable, ?string $reason = null, ?int $archivedBy = null, ?array $snapshot = null): self
    {
        return static::create([
            'archivable_type' => $archivable->getMorphClass(),
            'archivable_id' => $archivable->getKey(),
            'archived_by' => $archivedBy,
            'archived_at' => now(),
            'reason' => $reason,
            'snapshot' => $snapshot,
        ]);
    }

    /**
     * Resolve the archive snapshot. Prefers the stored snapshot (legacy rows
     * and true-deletion capture); otherwise fetches the live record, loading
     * the relations the archive views rely on.
     */
    public function resolvedSnapshot(): array
    {
        if (! empty($this->snapshot)) {
            return $this->snapshot;
        }

        $model = $this->archivable;

        if (! $model) {
            return [];
        }

        $model->loadMissing(static::snapshotRelationsFor($model));

        return $model->toArray();
    }

    /**
     * @return list<string>
     */
    protected static function snapshotRelationsFor(Model $model): array
    {
        return match (true) {
            $model instanceof Citation => ['violationType', 'enforcer'],
            $model instanceof Appeal => ['citation', 'submitter', 'reviewer'],
            $model instanceof ClampingRecord => ['officer', 'citation'],
            $model instanceof ImpoundingRecord => ['officer', 'citation'],
            $model instanceof CitizenReport => ['processedBy', 'assignedTo', 'citation'],
            default => [],
        };
    }
}

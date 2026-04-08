<?php

namespace App\Models\Compliance;

use App\Models\EnhancedDiligenceRecord;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EddDocumentRequest extends Model
{
    use HasFactory;

    protected $fillable = [
        'edd_record_id',
        'document_type',
        'status',
        'file_path',
        'rejection_reason',
        'uploaded_at',
        'verified_at',
        'verified_by',
    ];

    protected $casts = [
        'uploaded_at' => 'datetime',
        'verified_at' => 'datetime',
    ];

    public function eddRecord(): BelongsTo
    {
        return $this->belongsTo(EnhancedDiligenceRecord::class, 'edd_record_id');
    }

    public function verifier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    public function markReceived(string $filePath): void
    {
        $this->update([
            'status' => 'Received',
            'file_path' => $filePath,
            'uploaded_at' => now(),
        ]);
    }

    public function verify(int $verifiedBy): void
    {
        $this->update([
            'status' => 'Verified',
            'verified_at' => now(),
            'verified_by' => $verifiedBy,
        ]);
    }

    public function reject(string $reason, int $verifiedBy): void
    {
        $this->update([
            'status' => 'Rejected',
            'rejection_reason' => $reason,
            'verified_at' => now(),
            'verified_by' => $verifiedBy,
        ]);
    }
}

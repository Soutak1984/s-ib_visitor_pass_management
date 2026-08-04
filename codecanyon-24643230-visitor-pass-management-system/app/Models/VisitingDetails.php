<?php

namespace App\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Shipu\Watchable\Traits\HasAuditColumn;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;


class VisitingDetails extends Model implements  HasMedia
{
    use InteractsWithMedia;
    use HasAuditColumn;

    protected $table = 'visiting_details';
    protected $guarded = ['id'];
    protected $auditColumn = true;

    protected $fakeColumns = [];

    public function creator()
    {
        return $this->morphTo();
    }

    public function editor()
    {
        return $this->morphTo();
    }


    public function user()
    {
        return $this->belongsTo(User::class);
    }
    public function visitor()
    {
        return $this->belongsTo(Visitor::class,'visitor_id');
    }


    public function employee()
    {
        return $this->belongsTo(Employee::class, 'employee_id');
    }

    public function attendance()
    {
        return $this->hasMany(Attendance::class, 'employee_id');
    }

    public function getMyStatusAttribute()
    {
        return trans('statuses.' . $this->status);
    }

    public function getImagesAttribute()
    {
        return $this->resolveVisitorImageUrl() ?: asset('assets/img/default/user.png');
    }

    /**
     * Reliable visitor photo URL for list, ID card and detail pages.
     * Supports both public_uploads (public/uploads) and legacy storage disk.
     */
    public function resolveVisitorImageUrl(): ?string
    {
        $media = $this->getFirstMedia('visitor');
        if (!$media) {
            return null;
        }

        // Prefer path under public/ so shared hosting (no storage symlink) works
        $relative = ltrim($media->id . '/' . $media->file_name, '/');

        if ($media->disk === 'public_uploads') {
            $publicFile = public_path('uploads/' . $relative);
            if (is_file($publicFile)) {
                return asset('uploads/' . $relative);
            }
        }

        // Legacy Spatie public disk: storage/app/public/{id}/{file}
        $storageFile = storage_path('app/public/' . $relative);
        if (is_file($storageFile)) {
            // If symlink/public/storage exists, use asset; else serve via storage path if linked
            if (is_file(public_path('storage/' . $relative))) {
                return asset('storage/' . $relative);
            }
            // Fallback: copy once into public/uploads so image can display
            $destDir = public_path('uploads/' . $media->id);
            if (!is_dir($destDir)) {
                @mkdir($destDir, 0755, true);
            }
            $dest = $destDir . '/' . $media->file_name;
            if (!is_file($dest)) {
                @copy($storageFile, $dest);
            }
            if (is_file($dest)) {
                return asset('uploads/' . $relative);
            }
        }

        // Last resort: Spatie-generated URL (needs correct APP_URL + storage link)
        $url = $media->getUrl();
        if (!empty($url)) {
            return $url;
        }

        return null;
    }

    public function hasVisitorImage(): bool
    {
        return (bool) $this->getFirstMedia('visitor');
    }
}

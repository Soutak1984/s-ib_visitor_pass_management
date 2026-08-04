<?php

namespace App\Models;

use Spatie\MediaLibrary\HasMedia;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;
use Shipu\Watchable\Traits\HasAuditColumn;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\HasMedia\HasMediaTrait;


class Visitor extends Model implements  HasMedia
{
    use Notifiable;
    use InteractsWithMedia;
    use HasAuditColumn;


    protected $table = 'visitors';
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

    public function invitation()
    {
        return $this->hasOne(PreRegister::class);
    }
    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function preregister()
    {
        return $this->hasOne(PreRegister::class);
    }

    public function getNameAttribute()
    {
        return $this->first_name . ' ' . $this->last_name;
    }

    public function getMyStatusAttribute()
    {
        return trans('statuses.' . $this->status);
    }
    public function getMyGenderAttribute()
    {
        return trans('genders.' . $this->gender);
    }
    public function getImagesAttribute()
    {
        // Visitor photos are stored on VisitingDetails, not Visitor model
        $media = $this->getFirstMedia('visitor');
        if ($media) {
            $relative = $media->id . '/' . $media->file_name;
            if ($media->disk === 'public_uploads' && is_file(public_path('uploads/' . $relative))) {
                return asset('uploads/' . $relative);
            }
            if (is_file(public_path('storage/' . $relative))) {
                return asset('storage/' . $relative);
            }
            $url = $media->getUrl();
            if (!empty($url)) {
                return $url;
            }
        }
        return asset('assets/img/default/user.png');
    }

    public function getQrcodeAttribute()
    {
        if (!empty($this->getFirstMediaUrl('qrcode'))) {
            return asset($this->getFirstMediaUrl('qrcode'));
        }
        return asset('assets/img/default/user.png');
    }

    public function routeNotificationForTwilio()
    {
        return $this->phone;
    }
}

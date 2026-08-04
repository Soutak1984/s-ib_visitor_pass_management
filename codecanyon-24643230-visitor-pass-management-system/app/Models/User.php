<?php
namespace App\Models;

use App\Models\Employee;
use Spatie\MediaLibrary\HasMedia;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Traits\HasRoles;
use Tymon\JWTAuth\Contracts\JWTSubject;
use Illuminate\Notifications\Notifiable;
use Shipu\Watchable\Traits\HasModelEvents;
use Spatie\MediaLibrary\InteractsWithMedia;
use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable implements JWTSubject, HasMedia
{
    use Notifiable, InteractsWithMedia, HasModelEvents, HasRoles;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    
    protected $fillable = [
        'first_name', 'last_name', 'email', 'username', 'password', 'phone', 'address', 'roles', 'device_token','web_token', 'status', 'country_code', 'country_code_name'
    ];
 
    protected $guard_name = 'web';

   

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password', 'remember_token',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'options' => 'array',
    ];

    protected $appends = ['myrole'];

    /**
     * Get the identifier that will be stored in the subject claim of the JWT.
     *
     * @return mixed
     */
    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    /**
     * Return a key value array, containing any custom claims to be added to the JWT.
     *
     * @return array
     */
    public function getJWTCustomClaims()
    {
        return [];
    }

    public function getNameAttribute()
    {
        return $this->first_name . ' ' . $this->last_name;
    }


    public function getImagesAttribute()
    {
        $media = $this->getFirstMedia('user');
        if ($media) {
            $relative = $media->id . '/' . $media->file_name;
            if ($media->disk === 'public_uploads' && is_file(public_path('uploads/' . $relative))) {
                return asset('uploads/' . $relative);
            }
            if (is_file(public_path('storage/' . $relative))) {
                return asset('storage/' . $relative);
            }
            // Recover legacy storage files into public/uploads
            $storageFile = storage_path('app/public/' . $relative);
            if (is_file($storageFile)) {
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
            $url = $media->getUrl();
            if (!empty($url)) {
                // Avoid double asset() wrapping when URL is already absolute
                if (\Illuminate\Support\Str::startsWith($url, ['http://', 'https://', '//'])) {
                    return $url;
                }
                return asset($url);
            }
        }
        return asset('assets/img/default/user.png');
    }



    public function routeNotificationForTwilio()
    {
        return $this->phone;
    }

    /**
     * Route notifications for the FCM channel.
     *
     * @param  \Illuminate\Notifications\Notification  $notification
     * @return string
     */
    public function routeNotificationForFcm($notification)
    {
        return $this->device_token;
    }

    public function getMyroleAttribute()
    {
        return $this->roles->pluck('id', 'id')->first();
    }

    public function getrole()
    {
        return $this->hasOne(Role::class, 'id', 'myrole');
    }

    public function employee()
    {
        return $this->hasOne(Employee::class);
    }

    public function getMyStatusAttribute()
    {
        return trans('statuses.' . $this->status);
    }
}

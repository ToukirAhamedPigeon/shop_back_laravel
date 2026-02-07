 <?php

// namespace Modules\Shared\Application\Resources\Users;

// use Illuminate\Http\Resources\Json\JsonResource;
// use Illuminate\Support\Carbon;

// class UserResource extends JsonResource
// {
//     public function toArray($request): array
//     {
//         return [
//             'id' => $this->id,

//             'name' => $this->name,
//             'username' => $this->username,
//             'email' => $this->email,
//             'mobileNo' => $this->mobile_no,

//             'profileImage' => $this->profile_image,
//             'bio' => $this->bio,
//             'dateOfBirth' => $this->date_of_birth
//                 ? Carbon::parse($this->date_of_birth)->toISOString()
//                 : null,
//             'gender' => $this->gender,
//             'address' => $this->address,

//             'qrCode' => $this->qr_code,

//             'timezone' => $this->timezone,
//             'language' => $this->language,

//             'isActive' => (bool)$this->is_active,

//             'createdAt' => Carbon::parse($this->created_at)->toISOString(),
//             'updatedAt' => Carbon::parse($this->updated_at)->toISOString(),

//             'roles' => $this->roles->pluck('name')->values(),
//             'permissions' => $this->getAllPermissionsAttribute(),
//         ];
//     }
// }

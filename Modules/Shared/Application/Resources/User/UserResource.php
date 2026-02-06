<?php

namespace App\Application\Responses;

class UserResponse
{
    public string $id;
    public string $name;
    public string $username;
    public string $email;
    public ?string $mobileNo;

    public ?string $profileImage;
    public ?string $bio;
    public ?string $dateOfBirth;
    public ?string $gender;
    public ?string $address;

    public ?string $qrCode;
    public ?string $timezone;
    public ?string $language;

    public bool $isActive;
    public string $createdAt;
    public string $updatedAt;

    public array $roles;
    public array $permissions;

    public function __construct(array $data)
    {
        $this->id = $data['id'];
        $this->name = $data['name'];
        $this->username = $data['username'];
        $this->email = $data['email'];
        $this->mobileNo = $data['mobile_no'] ?? null;

        $this->profileImage = $data['profile_image'] ?? null;
        $this->bio = $data['bio'] ?? null;
        $this->dateOfBirth = $data['date_of_birth'] ?? null;
        $this->gender = $data['gender'] ?? null;
        $this->address = $data['address'] ?? null;

        $this->qrCode = $data['qr_code'] ?? null;
        $this->timezone = $data['timezone'] ?? null;
        $this->language = $data['language'] ?? null;

        $this->isActive = (bool) ($data['is_active'] ?? true);
        $this->createdAt = $data['created_at'];
        $this->updatedAt = $data['updated_at'];

        $this->roles = $data['roles'] ?? [];
        $this->permissions = $data['permissions'] ?? [];
    }
}

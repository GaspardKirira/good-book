<?php

namespace Domain\Users;

class UserMapper
{
    public static function mapToUser(array $data): User
    {
        $user = new User(
            $data['fullname'],
            $data['email'],
            $data['photo'],
            $data['password'],
            $data['role'],
            $data['status'],
            $data['verified_email']
        );

        if (isset($data['message_count'])) {
            $user->setMessageCount($data['message_count']);
        }
        $user->setId($data['id']);
        $user->setCoverPhoto($data['cover_photo']);
        $user->setAccessToken($data['access_token']);
        $user->setRefreshToken($data['refresh_token']);
        $user->setBio($data['bio']);
        $user->setPhone($data['phone']);
        $user->setUsername($data['username']);
        $user->setCreateAt($data['created_at']);
        $user->setUpdateAt($data['updated_at']);

        return $user;
    }

    public static function mapToUsers(array $rows): array
    {
        return array_map(fn($data) => $this->mapToUser($data), $rows);
    }
}

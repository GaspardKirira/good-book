<?php

namespace Domain\Users;

interface UserInterface
{

    public function save(User $user): User;
    public function updatePassword(User $user, string $currentPassword, string $newPassword): bool;
    public function forgotPassword(User $user, string $newPassword): bool;
    public function updatePasswordForGoogleUser(User $user, string $newPassword): bool;
    public function findByEmail(string $email): ?User;
    public function findById(int $id): ?User;
    public function update(User $user): void;
    public function updateAccessToken(User $user): void;
    public function delete(int $id): void;
    public function findAll(): iterable;
    public function findByResetToken(string $resetToken): ?User;
    public function updateResetToken(User $user, string $resetToken): bool;
}

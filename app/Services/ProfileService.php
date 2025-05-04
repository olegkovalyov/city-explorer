<?php

namespace App\Services;

use App\Contracts\Services\UserProfileServiceInterface;
use App\Data\DeleteProfileData;
use App\Data\UpdateProfileData;
use App\Enums\ErrorCode;
use App\Support\Result;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Throwable;

class ProfileService implements UserProfileServiceInterface
{

    public function updateProfile(UpdateProfileData $data): Result
    {
        $user = $data->user;

        DB::beginTransaction();

        try {
            $originalEmail = $user->getOriginal('email');

            $user->forceFill([
                'name' => $data->name,
                'email' => $data->email,
            ]);

            if (
                $user->isDirty('email')
                && $originalEmail !== $data->email
            ) {
                $user->email_verified_at = null;
            }

            $user->save();

            DB::commit();

            return Result::success(true);
        } catch (QueryException $e) {
            DB::rollBack();
            Log::error('Database error updating profile in ProfileService.', [
                'userId' => $user->id,
                'exception' => $e
            ]);
            return Result::failureFromException($e, ErrorCode::DATABASE_ERROR, 'Database error during profile update.');
        } catch (Throwable $e) {
            DB::rollBack();
            Log::error('Unexpected error updating profile in ProfileService.', [
                'userId' => $user->id,
                'exception' => $e
            ]);
            return Result::failureFromException($e, ErrorCode::PROFILE_UPDATE_FAILED, 'Failed to update profile.');
        }
    }

    public function deleteProfile(DeleteProfileData $data): Result
    {
        $user = $data->user;

        if (!Hash::check($data->password, $user->password)) {
            Log::warning('Password check failed in ProfileService despite FormRequest validation.', ['userId' => $user->id]);
            return Result::failure(ErrorCode::INVALID_CURRENT_PASSWORD, 'Invalid current password provided.');
        }

        DB::beginTransaction();

        try {
            $user->delete();
            DB::commit();
            return Result::success(true);
        } catch (QueryException $e) {
            DB::rollBack();
            Log::error('Database error deleting profile in ProfileService.', [
                'userId' => $user->id,
                'exception' => $e
            ]);
            return Result::failureFromException($e, ErrorCode::DATABASE_ERROR, 'Database error during profile deletion.');
        } catch (Throwable $e) {
            DB::rollBack();
            Log::error('Unexpected error deleting profile in ProfileService.', [
                'userId' => $user->id,
                'exception' => $e
            ]);
            return Result::failureFromException($e, ErrorCode::PROFILE_DELETE_FAILED, 'Failed to delete profile.');
        }
    }
}

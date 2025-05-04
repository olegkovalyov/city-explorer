<?php

namespace App\Http\Controllers;

use App\Data\DeleteProfileData;
use App\Data\UpdateProfileData;
use App\Http\Requests\DeleteProfileRequest;
use App\Http\Requests\ProfileUpdateRequest;
use App\Services\ProfileService;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Inertia\Inertia;
use Inertia\Response;

class ProfileController extends Controller
{
    protected ProfileService $profileService;

    public function __construct(ProfileService $profileService)
    {
        $this->profileService = $profileService;
    }

    public function edit(Request $request): Response
    {
        return Inertia::render('Profile/Edit', [
            'mustVerifyEmail' => $request->user() instanceof MustVerifyEmail,
            'status' => session('status'),
        ]);
    }

    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        $updateData = new UpdateProfileData(
            user: $request->user(),
            name: $request->validated('name'),
            email: $request->validated('email')
        );

        $result = $this->profileService->updateProfile($updateData);

        if ($result->isSuccess()) {
            return Redirect::route('profile.edit')->with('status', 'profile-updated');
        } else {
            return Redirect::route('profile.edit')->with('error', 'profile-update-failed');
        }
    }

    public function destroy(DeleteProfileRequest $request): RedirectResponse
    {
        $deleteData = new DeleteProfileData(
            user: $request->user(),
            password: $request->validated('password')
        );

        $result = $this->profileService->deleteProfile($deleteData);

        if ($result->isSuccess()) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();
            return Redirect::to('/');
        } else {
            return back()->withErrors(['password' => 'Failed to delete profile. Please try again.']);
        }
    }
}

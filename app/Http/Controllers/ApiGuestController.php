<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Faculty;
use App\Models\Students;
use Illuminate\Http\Request;

final class ApiGuestController extends Controller
{
    public function checkEmail(Request $request)
    {
        $request->validate(['email' => 'required|email']);

        $email = $request->email;
        $userType = $request->userType;
        // Check if the email exists in User records first
        if (User::query()->where('email', $email)->exists()) {
            return response()->json([
                'error' => 'Email already exists in User records.',
            ]);
        }

        // Check if the email exists in Student records
        if ($userType === 'student') {
            if (Students::query()->where('email', $email)->exists()) {
                return response()->json([
                    'success' => 'Email is valid.',
                ]);
            }

            return response()->json([
                'error' => 'Email not found in Student records.',
            ]);

        }

        // Check if the email exists in Faculty records
        if ($userType === 'instructor') {
            if (Faculty::query()->where('email', $email)->exists()) {
                return response()->json([
                    'success' => 'Email is valid.',
                ]);
            }

            return response()->json([
                'error' => 'Email not found in Faculty records.',
            ]);

        }

        return response()->json([
            'error' => 'Email not found in records.',
        ]);
    }

    public function checkId(Request $request)
    {
        $request->validate(['id' => 'required', 'email' => 'required']);

        $id = $request->id;
        $email = $request->email;
        $userType = $request->userType;

        if ($userType === 'student') {
            $student = Students::query()->where('id', $id)->first();
            if (! $student) {
                return response()->json([
                    'error' => 'ID not found in Student records.',
                ]);
            }

            if ($student->email !== $email) {
                return response()->json([
                    'error' => 'The provided email does not match the student ID. Please use the email associated with your student account.',
                ]);
            }

            return response()->json([
                'success' => 'ID and email match.',
                'fullName' => $student->getFullNameAttribute(),
            ]);
        }

        if ($userType === 'instructor') {
            // First try to find by faculty_code (new system)
            $faculty = Faculty::query()->where('faculty_code', $id)->first();

            // If not found by faculty_code, try by UUID (legacy system)
            if (!$faculty) {
                $faculty = Faculty::query()->where('id', $id)->first();
            }

            if ($faculty) {
                // Verify email matches if provided
                if ($faculty->email !== $email) {
                    return response()->json([
                        'error' => 'The provided email does not match the faculty ID. Please use the email associated with your faculty account.',
                    ]);
                }

                return response()->json([
                    'success' => 'ID and email match.',
                    'fullName' => $faculty->getFullNameAttribute(),
                ]);
            }

            return response()->json([
                'error' => 'Faculty ID not found in records.',
            ]);

        }

        return response()->json([
            'error' => 'ID not found in records.',
        ]);
    }
}

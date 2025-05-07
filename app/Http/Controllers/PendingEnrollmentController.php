<?php

namespace App\Http\Controllers;

use Inertia\Inertia;
use App\Models\Courses;
use Illuminate\Http\Request;
use App\Models\GeneralSettings;
use App\Models\PendingEnrollment;
use App\Services\EnrollmentService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
// Removed duplicate Request import

class PendingEnrollmentController extends Controller
{
    /**
     * Display a listing of the resource.
     * Potentially for admin view later.
     */
    public function index()
    {
        // Logic for admin view of pending enrollments
    }

     /**
     * Show the form for creating a new resource.
     * Renders the OnlineEnrollment Vue component with necessary props.
     */
    public function create(Request $request)
    {
        // Fetch allowed course IDs for online enrollment from GeneralSettings
        $settings = GeneralSettings::query()->first();
        $allowedCourseIds = $settings?->enrollment_courses ?? [];
        // Query only the allowed courses
        $courses = Courses::query()
            ->whereIn('id', $allowedCourseIds)
            ->get()
            ->map(fn($course) => [
                'value' => $course->id,
                'label' => $course->title,
                'code' => $course->code,
                'department' => $course->department,
            ]);
        return Inertia::render('OnlineEnrollment', [
            'googleEmail' => $request->session()->get('enrollment_google_email'),
            'googleName' => $request->session()->get('enrollment_google_name'),   // Pass name
            'googleAvatar' => $request->session()->get('enrollment_google_avatar'), // Pass avatar
            // Pass flash messages if they exist
            'flash' => [
                'success' => $request->session()->get('success'),
                'error' => $request->session()->get('error'),
                'warning' => $request->session()->get('warning'),
            ],
            'courses' => $courses,
        ]);
    }

     /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        // Basic validation (can be expanded significantly)
        // Note: Since data is stored as JSON, detailed validation here might be less critical
        // than validation on the frontend or during the approval process.
        $validatedData = $request->validate([
            // Add minimal validation if needed, e.g., ensure required top-level keys exist
            'first_name' => 'required|string|max:50',
            'last_name' => 'required|string|max:50',
            'email' => 'required|email|max:255',
            'course_id' => 'required|integer', // Example validation
            // Add other critical fields as needed
            'enrollment_google_email' => 'nullable|email|max:255', // Validate if present
            'subjects' => 'required|array|min:1',
            'subjects.*.id' => 'required|integer',
        ]);

        // Check for duplicate based on the *form's* email field first,
        // as the Google sign-in is optional.
        $formEmail = $request->input('email');
        $existingPendingByFormEmail = PendingEnrollment::whereJsonContains('data->email', $formEmail)
                                                ->where('status', 'pending')
                                                ->first();

        if ($existingPendingByFormEmail) {
             Log::warning("Attempt to submit new enrollment with existing pending form email: {$formEmail}");
             // Return error response for the frontend onError handler
             return response()->json(['message' => 'You already have a pending enrollment application associated with this email address. Please wait for it to be processed.'], 409); // 409 Conflict
        }

        // If Google email was provided via sign-in, double-check using that too
        // (though the callback should have caught this already if they signed in)
        $googleEmail = $request->input('enrollment_google_email');
        if ($googleEmail && $googleEmail !== $formEmail) {
             $existingPendingByGoogleEmail = PendingEnrollment::whereJsonContains('data->enrollment_google_email', $googleEmail)
                                                    ->where('status', 'pending')
                                                    ->first();
             if ($existingPendingByGoogleEmail) {
                 Log::warning("Attempt to submit new enrollment with existing pending Google email: {$googleEmail}");
                 return response()->json(['message' => 'You already have a pending enrollment application associated with the signed-in Google account. Please wait for it to be processed.'], 409); // 409 Conflict
             }
        }


        try {
            // Create the pending enrollment record
            PendingEnrollment::create([
                'data' => $request->all(), // Store the entire request data as JSON
                'status' => 'pending', // Default status
            ]);

            Log::info('Pending enrollment created successfully for: ' . $formEmail . ($googleEmail ? " (Google: {$googleEmail})" : ""));

            // Clear the session email after successful submission
            $request->session()->forget('enrollment_google_email');

            // Redirect back or to a success page (or return Inertia response if needed)
            // Using Redirect::back() might not trigger the onSuccess handler correctly in Vue if it doesn't return a 2xx/3xx status Inertia expects.
            // Returning a specific Inertia response or a simple success JSON might be better.
            // For simplicity with the current Vue setup expecting onSuccess:
            // We can return a redirect that Inertia handles, which should trigger onSuccess.
            // A redirect to the same form page might be suitable, showing the 'Submitted' state.
            // Or redirect to a dedicated success page. Let's redirect back for now.

            // return Redirect::back()->with('success', 'Application submitted successfully!');
            // Let's return a success response that the Inertia handler expects
             return Redirect::back()->with('success', 'Application submitted successfully!'); // 201 Created status


        } catch (\Exception $e) {
            Log::error('Error creating pending enrollment: ' . $e->getMessage());
            // Return an error response that the onError handler in Vue can catch
            return Redirect::back()->with('error', 'Failed to submit application due to a server error. Please try again later.');
        }
    }

    /**
     * Display the specified resource.
     * Potentially for admin view later.
     */
    public function show(PendingEnrollment $pendingEnrollment)
    {
        // Logic for admin view of a specific pending enrollment
    }

    /**
     * Show the form for editing the specified resource.
     * Potentially for admin view later.
     */
    public function edit(PendingEnrollment $pendingEnrollment)
    {
        // Logic for admin editing form
    }

    /**
     * Update the specified resource in storage.
     * Potentially for admin approval/rejection.
     */
    public function update(Request $request, PendingEnrollment $pendingEnrollment)
    {
        // Logic for admin processing (approve/reject)
        // This would involve changing status, adding remarks, processed_at, approved_by
        // And potentially triggering the creation of the actual Student records.
    }

    /**
     * Remove the specified resource from storage.
     * Potentially for admin deletion.
     */
    public function destroy(PendingEnrollment $pendingEnrollment)
    {
        // Logic for admin deleting a pending enrollment
    }

    /**
     * Confirm and process a pending enrollment.
     * This is called after the student confirms their subjects and payment.
     */
    public function confirm(Request $request, EnrollmentService $enrollmentService)
    {
        $request->validate([
            // 'pending_enrollment_id' => 'required|integer|exists:pending_enrollments,id',
            'downpayment' => 'required|numeric|min:0',
            'payment_method' => 'required|string',
            'subjects' => 'required|array|min:1',
            'totalLectureFee' => 'required|numeric',
            'totalLabFee' => 'required|numeric',
            'totalMiscFee' => 'required|numeric',
            'overallTuition' => 'required|numeric',
            'semester' => 'required|integer',
            'academic_year' => 'required|integer',
            'school_year' => 'required|string',
        ]);

        try {
            $user = Auth::user();
            $pending = $user->approved_pending_enrollment;
            $paymentData = [
                'downpayment' => $request->downpayment,
                'payment_method' => $request->payment_method,
                'subjects' => $request->subjects,
                'totalLectureFee' => $request->totalLectureFee,
                'totalLabFee' => $request->totalLabFee,
                'totalMiscFee' => $request->totalMiscFee,
                'overallTuition' => $request->overallTuition,
                'semester' => $request->semester,
                'academic_year' => $request->academic_year,
                'school_year' => $request->school_year,
            ];
            [$student, $enrollment] = $enrollmentService->process($pending, $paymentData);

            // Optionally, mark the pending enrollment as processed/approved
            $pending->status = 'processed';
            $pending->processed_at = now();
            $pending->save();

            // Return Inertia response with enrollment status/info
            return Inertia::render('DashboardGuest', [
                'enrollmentStatus' => 'processing',
                'enrollment' => $enrollment,
                'student' => $student,
            ]);
        } catch (\Exception $e) {
            \Log::error('Enrollment confirmation failed: ' . $e->getMessage());
            return back()->withErrors(['enrollment' => 'Failed to process enrollment. Please try again later.']);
        }
    }
}

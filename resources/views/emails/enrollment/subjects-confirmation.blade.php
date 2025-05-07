<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Subject Enrollment Confirmation</title>
    <style>
        body {
            font-family: Arial, Helvetica, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
        }
        .header img {
            max-width: 150px;
        }
        .header h1 {
            color: #3b82f6;
            margin-bottom: 5px;
        }
        .info-section {
            background-color: #f9fafb;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 20px;
        }
        .info-row {
            display: flex;
            margin-bottom: 10px;
        }
        .info-label {
            font-weight: bold;
            width: 150px;
        }
        .subjects-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        .subjects-table th {
            background-color: #e5e7eb;
            text-align: left;
            padding: 10px;
        }
        .subjects-table td {
            border-bottom: 1px solid #e5e7eb;
            padding: 10px;
        }
        .note {
            background-color: #eff6ff;
            border-left: 4px solid #3b82f6;
            padding: 15px;
            margin-top: 30px;
        }
        .footer {
            text-align: center;
            margin-top: 40px;
            font-size: 12px;
            color: #6b7280;
        }
    </style>
</head>
<body>
    <div class="header">
        <!-- Replace with your school logo -->
        <h1>Subject Enrollment Confirmation</h1>
        <p>Thank you for enrolling in subjects for {{ $semester }} of School Year {{ $schoolYear }}</p>
    </div>
    
    <div class="info-section">
        <div class="info-row">
            <div class="info-label">Name:</div>
            <div>{{ $student->full_name }}</div>
        </div>
        <div class="info-row">
            <div class="info-label">Student ID:</div>
            <div>{{ $student->id }}</div>
        </div>
        <div class="info-row">
            <div class="info-label">Course:</div>
            <div>{{ $student->course->title }} ({{ $student->course->code }})</div>
        </div>
        <div class="info-row">
            <div class="info-label">Academic Year:</div>
            <div>{{ $academicYear }}</div>
        </div>
        <div class="info-row">
            <div class="info-label">Semester:</div>
            <div>{{ $semester }}</div>
        </div>
        <div class="info-row">
            <div class="info-label">School Year:</div>
            <div>{{ $schoolYear }}</div>
        </div>
        <div class="info-row">
            <div class="info-label">Status:</div>
            <div><strong style="color: #f59e0b;">{{ ucfirst($enrollment->status) }}</strong></div>
        </div>
    </div>
    
    <h2>Enrolled Subjects</h2>
    <table class="subjects-table">
        <thead>
            <tr>
                <th>Code</th>
                <th>Subject</th>
                <th>Units</th>
            </tr>
        </thead>
        <tbody>
            @foreach($subjects as $subject)
            <tr>
                <td>{{ $subject['code'] }}</td>
                <td>{{ $subject['title'] }}</td>
                <td>{{ $subject['units'] }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
    
    <div class="note">
        <strong>Important:</strong> Please visit the Accounting Office to complete your enrollment by paying the required fees.
        Your enrollment is currently in {{ strtolower($enrollment->status) }} status and will be finalized after payment processing.
    </div>
    
    <div class="footer">
        <p>This is an automated email. Please do not reply to this message.</p>
        <p>&copy; {{ date('Y') }} DCCP Hub. All rights reserved.</p>
    </div>
</body>
</html> 
<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Class Students - {{ $class->subject_code }} (Sec {{ $class->section }})</title>
  <style>
    body { font-family: system-ui, -apple-system, Segoe UI, Roboto, Helvetica, Arial, sans-serif; margin: 24px; color: #111827; }
    h1 { font-size: 20px; margin: 0 0 8px; }
    h2 { font-size: 14px; margin: 0 0 16px; color: #6B7280; }
    table { width: 100%; border-collapse: collapse; margin-top: 16px; }
    th, td { border: 1px solid #E5E7EB; padding: 8px 10px; font-size: 12px; }
    th { background: #F9FAFB; text-align: left; }
    .meta { font-size: 12px; color: #6B7280; margin-top: 8px; }
    .footer { margin-top: 24px; font-size: 12px; color: #6B7280; }
    @media print { .no-print { display: none; } }
    .btn { display: inline-block; padding: 6px 10px; background: #1F2937; color: #fff; border-radius: 4px; text-decoration: none; font-size: 12px; }
  </style>
</head>
<body>
  <div class="no-print" style="margin-bottom:12px;">
    <a href="#" class="btn" onclick="window.print(); return false;">Print / Save as PDF</a>
  </div>

  <h1>Class Students - {{ $class->subject_code }} (Sec {{ $class->section }})</h1>
  <h2>{{ $class->subject_title }} | {{ $class->school_year }} - {{ ucfirst($class->semester) }} semester</h2>

  <div class="meta">
    Generated at {{ $generatedAt->format('M j, Y g:i A') }} by {{ $faculty->first_name }} {{ $faculty->last_name }}
  </div>

  <table>
    <thead>
      <tr>
        @if($rows->isNotEmpty())
          @foreach(array_keys($rows->first()) as $heading)
            <th>{{ $heading }}</th>
          @endforeach
        @endif
      </tr>
    </thead>
    <tbody>
      @forelse($rows as $row)
        <tr>
          @foreach($row as $value)
            <td>{{ $value }}</td>
          @endforeach
        </tr>
      @empty
        <tr>
          <td colspan="5" style="text-align:center; color:#6B7280;">No students found.</td>
        </tr>
      @endforelse
    </tbody>
  </table>

  <div class="footer">
    Note: Use your browser's Print dialog to save this page as a PDF.
  </div>
</body>
</html>


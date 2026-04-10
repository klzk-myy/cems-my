@extends('layouts.app')

@section('title', '500 - Server Error | CEMS-MY')

@section('content')
<div class="min-h-screen flex items-center justify-center">
    <div class="card text-center" style="max-width: 500px;">
        <div class="text-6xl mb-4">⚠️</div>
        <div class="text-6xl font-bold text-red-600 leading-none mb-2">500</div>
        <h1 class="text-2xl font-semibold text-gray-900 mb-3">Server Error</h1>
        <p class="text-gray-500 mb-8 leading-relaxed">
            Something went wrong on our end.<br>
            Our team has been notified and is working to fix the issue.
        </p>
        <a href="/" class="btn btn--primary">Back to Dashboard</a>
        <div class="mt-12 text-gray-400 text-sm">
            <p>CEMS-MY v1.0 - Bank Negara Malaysia Compliant MSB Management System</p>
        </div>
    </div>
</div>
@endsection
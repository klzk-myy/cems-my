@extends('layouts.app')

@section('title', '404 - Page Not Found | CEMS-MY')

@section('content')
<div class="min-h-screen flex items-center justify-center">
    <div class="card text-center" style="max-width: 500px;">
        <div class="text-6xl mb-4">🔍</div>
        <div class="text-6xl font-bold text-gray-500 leading-none mb-2">404</div>
        <h1 class="text-2xl font-semibold text-gray-900 mb-3">Page Not Found</h1>
        <p class="text-gray-500 mb-8 leading-relaxed">
            The page you're looking for doesn't exist.<br>
            It may have been moved or deleted.
        </p>
        <a href="/" class="btn btn--primary">Back to Dashboard</a>
        <div class="mt-12 text-gray-400 text-sm">
            <p>CEMS-MY v1.0 - Bank Negara Malaysia Compliant MSB Management System</p>
        </div>
    </div>
</div>
@endsection
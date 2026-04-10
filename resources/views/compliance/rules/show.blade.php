@extends('layouts.app')

@section('title', 'AML Rule Details - CEMS-MY')

@section('content')
<a href="{{ route('compliance.rules.index') }}" class="inline-block px-4 py-2 bg-gray-200 text-gray-700 no-underline rounded font-semibold text-sm hover:bg-gray-300 transition-colors mb-4">← Back to Rules</a>

<div class="mb-6">
    <h2 class="text-xl font-semibold text-gray-800 mb-1">AML Rule: {{ $rule->rule_code }}</h2>
    <p class="text-gray-500 text-sm">{{ $rule->rule_name }}</p>
</div>

<div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
    <div class="bg-white rounded-lg shadow-sm p-6 text-center">
        <div class="text-4xl font-bold text-gray-800">{{ $hitCount }}</div>
        <div class="text-sm text-gray-500 mt-1">Triggers (30 days)</div>
    </div>
    <div class="bg-white rounded-lg shadow-sm p-6 text-center">
        <div class="text-4xl font-bold text-gray-800">{{ $rule->risk_score }}</div>
        <div class="text-sm text-gray-500 mt-1">Risk Score</div>
    </div>
</div>

<div class="bg-white rounded-lg shadow-sm p-6 mb-6">
    <h3 class="text-lg font-semibold text-gray-800 mb-4 pb-2 border-b-2 border-gray-200">Rule Details</h3>

    <div class="flex flex-col">
        <div class="flex justify-between py-3 border-b border-gray-100">
            <div class="font-semibold text-gray-500 w-48 flex-shrink-0">Rule Code</div>
            <div class="text-gray-800"><strong>{{ $rule->rule_code }}</strong></div>
        </div>

        <div class="flex justify-between py-3 border-b border-gray-100">
            <div class="font-semibold text-gray-500 w-48 flex-shrink-0">Rule Name</div>
            <div class="text-gray-800">{{ $rule->rule_name }}</div>
        </div>

        <div class="flex justify-between py-3 border-b border-gray-100">
            <div class="font-semibold text-gray-500 w-48 flex-shrink-0">Description</div>
            <div class="text-gray-800">{{ $rule->description ?? 'No description provided' }}</div>
        </div>

        <div class="flex justify-between py-3 border-b border-gray-100">
            <div class="font-semibold text-gray-500 w-48 flex-shrink-0">Rule Type</div>
            <div class="text-gray-800">
                <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold
                    @php
                        $ruleType = is_object($rule->rule_type) ? $rule->rule_type->value : $rule->rule_type;
                        match($ruleType) {
                            'velocity' => 'bg-blue-100 text-blue-800',
                            'structuring' => 'bg-orange-100 text-orange-800',
                            'amount_threshold' => 'bg-purple-100 text-purple-800',
                            'frequency' => 'bg-teal-100 text-teal-800',
                            'geographic' => 'bg-red-100 text-red-800',
                            default => 'bg-gray-100 text-gray-800'
                        }
                    @endphp
                ">
                    {{ is_object($rule->rule_type) ? $rule->rule_type->label() : (AmlRuleType::tryFrom($rule->rule_type)?->label() ?? 'Unknown') }}
                </span>
            </div>
        </div>

        <div class="flex justify-between py-3 border-b border-gray-100">
            <div class="font-semibold text-gray-500 w-48 flex-shrink-0">Action</div>
            <div class="text-gray-800">
                @php
                    $actionBadgeClass = $rule->action === 'flag' ? 'bg-amber-100 text-amber-800' :
                        ($rule->action === 'hold' ? 'bg-red-100 text-red-800' :
                        ($rule->action === 'block' ? 'bg-gray-800 text-white' : 'bg-gray-100 text-gray-800'));
                @endphp
                <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold {{ $actionBadgeClass }}">
                    {{ ucfirst($rule->action) }}
                </span>
            </div>
        </div>

        <div class="flex justify-between py-3 border-b border-gray-100">
            <div class="font-semibold text-gray-500 w-48 flex-shrink-0">Risk Score</div>
            <div class="text-gray-800">{{ $rule->risk_score }}</div>
        </div>

        <div class="flex justify-between py-3 border-b border-gray-100">
            <div class="font-semibold text-gray-500 w-48 flex-shrink-0">Status</div>
            <div class="text-gray-800">
                <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold {{ $rule->is_active ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                    {{ $rule->is_active ? 'Active' : 'Inactive' }}
                </span>
            </div>
        </div>

        <div class="flex justify-between py-3 border-b border-gray-100">
            <div class="font-semibold text-gray-500 w-48 flex-shrink-0">Created By</div>
            <div class="text-gray-800">{{ $rule->creator->full_name ?? 'System' }}</div>
        </div>

        <div class="flex justify-between py-3 border-b border-gray-100">
            <div class="font-semibold text-gray-500 w-48 flex-shrink-0">Created At</div>
            <div class="text-gray-800">{{ $rule->created_at->format('Y-m-d H:i:s') }}</div>
        </div>

        <div class="flex justify-between py-3">
            <div class="font-semibold text-gray-500 w-48 flex-shrink-0">Updated At</div>
            <div class="text-gray-800">{{ $rule->updated_at->format('Y-m-d H:i:s') }}</div>
        </div>
    </div>
</div>

<div class="bg-white rounded-lg shadow-sm p-6 mb-6">
    <h3 class="text-lg font-semibold text-gray-800 mb-4 pb-2 border-b-2 border-gray-200">Conditions</h3>
    <pre class="bg-gray-800 text-gray-200 p-4 rounded font-mono text-sm whitespace-pre-wrap overflow-x-auto">{{ json_encode($rule->conditions, JSON_PRETTY_PRINT) }}</pre>
</div>

<div class="bg-white rounded-lg shadow-sm p-6 mb-6">
    <h3 class="text-lg font-semibold text-gray-800 mb-4 pb-2 border-b-2 border-gray-200">Rule Actions</h3>
    <div class="flex gap-4">
        <a href="{{ route('compliance.rules.edit', $rule) }}" class="px-4 py-2 bg-blue-600 text-white no-underline rounded font-semibold text-sm hover:bg-blue-700 transition-colors">Edit Rule</a>
        <form method="POST" action="{{ route('compliance.rules.toggle', $rule) }}">
            @csrf
            @method('PATCH')
            <button type="submit" class="px-4 py-2 font-semibold rounded text-sm transition-colors {{ $rule->is_active ? 'bg-green-600 text-white hover:bg-green-700' : 'bg-red-600 text-white hover:bg-red-700' }}">
                {{ $rule->is_active ? 'Deactivate' : 'Activate' }}
            </button>
        </form>
        <form method="POST" action="{{ route('compliance.rules.destroy', $rule) }}" onsubmit="return confirm('Are you sure you want to delete this rule?')">
            @csrf
            @method('DELETE')
            <button type="submit" class="px-4 py-2 bg-gray-200 text-gray-700 rounded font-semibold text-sm hover:bg-gray-300 transition-colors">Delete Rule</button>
        </form>
    </div>
</div>

<div class="bg-white rounded-lg shadow-sm p-6">
    <h3 class="text-lg font-semibold text-gray-800 mb-4 pb-2 border-b-2 border-gray-200">Recent Triggers (Last 50)</h3>
    @if($hitHistory->count() > 0)
    <div class="mt-4">
        @foreach($hitHistory as $hit)
        <div class="flex justify-between items-start py-3 border-b border-gray-100 text-sm">
            <div>
                <span class="font-semibold text-blue-600">
                    @if(isset($hit->entity_id) && $hit->entity_type === 'Transaction')
                        Transaction #{{ $hit->entity_id }}
                    @else
                        {{ $hit->entity_type ?? 'Unknown' }} #{{ $hit->entity_id ?? 'N/A' }}
                    @endif
                </span>
                <br>
                <small class="text-gray-500">{{ $hit->description ?? 'Rule triggered' }}</small>
            </div>
            <div class="text-gray-500 flex-shrink-0 ml-4">
                {{ $hit->created_at->format('Y-m-d H:i') }}
            </div>
        </div>
        @endforeach
    </div>
    @else
    <div class="text-center py-8 text-gray-500">
        <p>No triggers recorded for this rule in the last 30 days.</p>
    </div>
    @endif
</div>
@endsection

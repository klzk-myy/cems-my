@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <h4>Trial Balance - {{ $trialBalance['as_of_date'] }}</h4>
                </div>
                <div class="card-body">
                    @if($trialBalance['is_balanced'])
                        <div class="alert alert-success">Trial balance is balanced</div>
                    @else
                        <div class="alert alert-danger">Trial balance is NOT balanced</div>
                    @endif
                    
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Code</th>
                                <th>Account Name</th>
                                <th>Type</th>
                                <th class="text-end">Debit</th>
                                <th class="text-end">Credit</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($trialBalance['accounts'] as $account)
                            <tr>
                                <td>{{ $account['account_code'] }}</td>
                                <td>{{ $account['account_name'] }}</td>
                                <td>{{ $account['account_type'] }}</td>
                                <td class="text-end">{{ number_format($account['debit'], 2) }}</td>
                                <td class="text-end">{{ number_format($account['credit'], 2) }}</td>
                                <td>
                                    <a href="{{ route('accounting.ledger.account', $account['account_code']) }}" class="btn btn-sm btn-info">Ledger</a>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                        <tfoot>
                            <tr class="table-dark">
                                <th colspan="3">TOTAL</th>
                                <th class="text-end">{{ number_format($trialBalance['total_debits'], 2) }}</th>
                                <th class="text-end">{{ number_format($trialBalance['total_credits'], 2) }}</th>
                                <th></th>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

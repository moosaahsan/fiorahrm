@extends('admin.layouts.app')

@push('styles')
<link href="{{ URL::asset('assets/css/payroll-premium.css') }}" rel="stylesheet" type="text/css" />
@endpush

@section('breadcrumb')
<div class="col-sm-12 px-0">
    <div class="payroll-header mb-4">
        <div class="d-flex align-items-center justify-content-between">
            <div class="text-white">
                <h2 class="fw-bold mb-1" style="color: #fff !important;">Payroll Engine</h2>
                <p class="text-white-50 mb-0">Initiate automated salary and deduction calculations for the current cycle.</p>
            </div>
            <div class="no-print">
                <a href="{{ route('admin.payroll.index') }}" class="btn btn-outline-light rounded-pill px-4">
                    <i class="fas fa-times me-2"></i> Cancel Operation
                </a>
            </div>
        </div>
    </div>
</div>
@endsection

@section('content')
<div class="payroll-premium-suite">
    <div class="container-fluid px-0">
        <div class="row justify-content-center">
            <div class="col-lg-6">
                <div class="payroll-card border-0 overflow-hidden shadow-lg mt-4">
                    <div class="payroll-card-header bg-white border-0 text-center pt-5 pb-4">
                        <div class="rounded-circle bg-soft-indigo d-inline-flex align-items-center justify-content-center mb-3" style="width:70px;height:70px;background:#f5f3ff;color:#4f46e5">
                            <i class="fas fa-calculator fa-2x"></i>
                        </div>
                        <h3 class="fw-bold text-slate-900 mb-2">Monthly Cycle Initiation</h3>
                        <p class="text-muted small px-5">The system will automatically audit shifts, attendance, and leaves to generate an accurate financial draft.</p>
                    </div>

                    <div class="card-body px-5 pb-5 pt-0">
                        @if(session('error'))
                            <div class="alert alert-danger bg-rose text-white border-0 rounded-3 mb-4 d-flex align-items-center shadow-sm">
                                <i class="fas fa-exclamation-triangle me-3 fs-5"></i>
                                <div>{{ session('error') }}</div>
                            </div>
                        @endif
                        @if(session('success'))
                            <div class="alert alert-success bg-emerald text-white border-0 rounded-3 mb-4 d-flex align-items-center shadow-sm">
                                <i class="fas fa-check-circle me-3 fs-5"></i>
                                <div>{{ session('success') }}</div>
                            </div>
                        @endif
                        @if($errors->any())
                            <div class="alert alert-danger bg-rose text-white border-0 rounded-3 mb-4 shadow-sm">
                                <ul class="mb-0 py-2">
                                    @foreach($errors->all() as $error)
                                        <li>{{ $error }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif
                        
                        <form action="{{ route('admin.payroll.store') }}" method="POST">
                            @csrf
                            
                            <div class="mb-4">
                                <label class="form-label small text-uppercase fw-bold text-slate-600 mb-2"><i class="fas fa-calendar-alt me-2 text-indigo"></i>Reference Period</label>
                                <div class="row g-3">
                                    <div class="col-md-7">
                                        <select name="month" class="form-control pr-select shadow-none w-100 bg-light border-0" required>
                                            @foreach(range(1, 12) as $m)
                                                <option value="{{ $m }}" {{ now()->month == $m ? 'selected' : '' }}>
                                                    {{ date('F', mktime(0, 0, 0, $m, 1)) }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-md-5">
                                        <select name="year" class="form-control pr-select shadow-none w-100 bg-light border-0" required>
                                            @foreach(range(now()->year, 2024) as $y)
                                                <option value="{{ $y }}" {{ now()->year == $y ? 'selected' : '' }}>{{ $y }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                            </div>

                            <div class="mb-4">
                                <label class="form-label small text-uppercase fw-bold text-slate-600 mb-2"><i class="fas fa-building me-2 text-indigo"></i>Target Context</label>
                                <select name="branch_id" class="form-control pr-select shadow-none w-100 bg-light border-0" required>
                                    <option value="" disabled selected>-- Select Target Office / Branch --</option>
                                    @foreach($branches as $branch)
                                        <option value="{{ $branch->id }}">{{ $branch->name }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="p-4 mb-4 rounded-3" style="background:#f8fafc; border:1px solid #e2e8f0; border-left: 4px solid #4f46e5;">
                                <div class="d-flex align-items-start">
                                    <i class="fas fa-info-circle me-3 fs-5" style="color: #4f46e5; margin-top: 2px;"></i>
                                    <div>
                                        <div class="fw-bold text-slate-800 mb-1" style="font-size: 0.9rem;">System Audit Protocol</div>
                                        <div class="text-slate-600 small" style="line-height: 1.5;">Once started, the engine will batch-calculate policy-driven deductions for all active personnel. This operation is non-destructive and creates a <strong>Reviewable Draft</strong>.</div>
                                    </div>
                                </div>
                            </div>

                            <div class="d-grid pt-2">
                                <button type="submit" class="btn btn-indigo btn-lg rounded-pill py-3 fw-bold shadow-indigo d-flex justify-content-center align-items-center">
                                    Start Batch Calculation <i class="fas fa-arrow-right ms-3"></i>
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@extends('admin.layouts.app')

@section('title', 'Performance Evaluation Settings')

@section('breadcrumb')
<div class="sm:col-span-6 text-left">
    <h4 class="tw-page-title">Performance Evaluation Settings</h4>
    <ol class="breadcrumb flex flex-wrap gap-1 bg-transparent p-0 m-0 text-sm font-semibold">
        <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}" class="text-brand-600 hover:text-brand-700">Home</a></li>
        <li class="breadcrumb-item active text-slate-500">Performance Settings</li>
    </ol>
</div>
@endsection

@section('content')
<div class="container-fluid">
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show mb-4 flex items-center justify-between rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 font-medium text-emerald-800" role="alert">
            <span><i class="fas fa-check-circle mr-2"></i> {{ session('success') }}</span>
            <button type="button" class="close text-emerald-700 opacity-75 hover:opacity-100" data-dismiss="alert" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>
    @endif

    @if($errors->any())
        <div class="alert alert-danger alert-dismissible fade show mb-4 rounded-2xl border border-rose-200 bg-rose-50 px-4 py-3 font-medium text-rose-800" role="alert">
            <div class="flex items-start justify-between gap-3">
                <div>
                    <i class="fas fa-exclamation-triangle mr-2"></i>
                    @foreach($errors->all() as $error)
                        <div>{{ $error }}</div>
                    @endforeach
                </div>
                <button type="button" class="close text-rose-700 opacity-75 hover:opacity-100" data-dismiss="alert" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
        </div>
    @endif

    <form action="{{ route('admin.performance.settings.update') }}" method="POST">
        @csrf

        <div class="grid grid-cols-1 gap-6 lg:grid-cols-12">
            <div class="lg:col-span-8">
                <div class="tw-card p-6 sm:p-8">
                    <h5 class="mb-2 flex items-center font-display text-lg font-bold text-brand-600">
                        <i class="fas fa-sliders-h mr-3 text-brand-500"></i> Performance Weights Configuration
                    </h5>
                    <p class="mb-6 text-sm font-medium text-slate-500">
                        Configure the maximum score values (weights) allocated to each evaluation metric. The total sum of all weights must equal exactly <strong class="text-slate-700">100.00%</strong>.
                    </p>

                    <h6 class="mb-3 text-xs font-extrabold uppercase tracking-widest text-slate-500">
                        Automatic Metrics (50% Default Suggested)
                    </h6>
                    <div class="mb-6 grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div>
                            <label class="mb-1.5 block text-sm font-bold text-slate-800">Attendance Discipline Weight (%)</label>
                            <input type="number" step="0.01" class="weight-input w-full rounded-xl border-2 border-slate-200 bg-slate-50 px-4 py-3 text-sm font-semibold text-slate-800 transition focus:border-brand-500 focus:bg-white focus:outline-none focus:ring-4 focus:ring-brand-500/10" name="attendance_weight" value="{{ old('attendance_weight', $settings['attendance_weight']->value ?? 15.00) }}" required>
                            <small class="mt-1 block text-slate-500">Max score for perfect monthly attendance.</small>
                        </div>
                        <div>
                            <label class="mb-1.5 block text-sm font-bold text-slate-800">Leaves Compliance Weight (%)</label>
                            <input type="number" step="0.01" class="weight-input w-full rounded-xl border-2 border-slate-200 bg-slate-50 px-4 py-3 text-sm font-semibold text-slate-800 transition focus:border-brand-500 focus:bg-white focus:outline-none focus:ring-4 focus:ring-brand-500/10" name="leave_weight" value="{{ old('leave_weight', $settings['leave_weight']->value ?? 15.00) }}" required>
                            <small class="mt-1 block text-slate-500">Max score for taking minimal/no leaves.</small>
                        </div>
                        <div>
                            <label class="mb-1.5 block text-sm font-bold text-slate-800">Break Limits Compliance Weight (%)</label>
                            <input type="number" step="0.01" class="weight-input w-full rounded-xl border-2 border-slate-200 bg-slate-50 px-4 py-3 text-sm font-semibold text-slate-800 transition focus:border-brand-500 focus:bg-white focus:outline-none focus:ring-4 focus:ring-brand-500/10" name="break_weight" value="{{ old('break_weight', $settings['break_weight']->value ?? 10.00) }}" required>
                            <small class="mt-1 block text-slate-500">Max score for staying within break minutes.</small>
                        </div>
                        <div>
                            <label class="mb-1.5 block text-sm font-bold text-slate-800">Late Arrivals Compliance Weight (%)</label>
                            <input type="number" step="0.01" class="weight-input w-full rounded-xl border-2 border-slate-200 bg-slate-50 px-4 py-3 text-sm font-semibold text-slate-800 transition focus:border-brand-500 focus:bg-white focus:outline-none focus:ring-4 focus:ring-brand-500/10" name="late_weight" value="{{ old('late_weight', $settings['late_weight']->value ?? 10.00) }}" required>
                            <small class="mt-1 block text-slate-500">Max score for minimal late arrivals.</small>
                        </div>
                    </div>

                    <hr class="my-6 border-slate-100">

                    <h6 class="mb-3 text-xs font-extrabold uppercase tracking-widest text-slate-500">
                        Manual Ratings (50% Default Suggested)
                    </h6>
                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                        <div>
                            <label class="mb-1.5 block text-sm font-bold text-slate-800">Dress Code & Grooming (%)</label>
                            <input type="number" step="0.01" class="weight-input w-full rounded-xl border-2 border-slate-200 bg-slate-50 px-4 py-3 text-sm font-semibold text-slate-800 transition focus:border-brand-500 focus:bg-white focus:outline-none focus:ring-4 focus:ring-brand-500/10" name="dress_code_weight" value="{{ old('dress_code_weight', $settings['dress_code_weight']->value ?? 10.00) }}" required>
                            <small class="mt-1 block text-slate-500">Evaluated daily/weekly dress code compliance.</small>
                        </div>
                        <div>
                            <label class="mb-1.5 block text-sm font-bold text-slate-800">Work Performance (%)</label>
                            <input type="number" step="0.01" class="weight-input w-full rounded-xl border-2 border-slate-200 bg-slate-50 px-4 py-3 text-sm font-semibold text-slate-800 transition focus:border-brand-500 focus:bg-white focus:outline-none focus:ring-4 focus:ring-brand-500/10" name="work_performance_weight" value="{{ old('work_performance_weight', $settings['work_performance_weight']->value ?? 20.00) }}" required>
                            <small class="mt-1 block text-slate-500">Monthly work quality and productivity.</small>
                        </div>
                        <div>
                            <label class="mb-1.5 block text-sm font-bold text-slate-800">Behavior & Teamwork (%)</label>
                            <input type="number" step="0.01" class="weight-input w-full rounded-xl border-2 border-slate-200 bg-slate-50 px-4 py-3 text-sm font-semibold text-slate-800 transition focus:border-brand-500 focus:bg-white focus:outline-none focus:ring-4 focus:ring-brand-500/10" name="behavior_weight" value="{{ old('behavior_weight', $settings['behavior_weight']->value ?? 20.00) }}" required>
                            <small class="mt-1 block text-slate-500">Professional behaviour and teamwork support.</small>
                        </div>
                    </div>
                </div>
            </div>

            <div class="lg:col-span-4">
                <div class="tw-card p-6 text-center sm:p-8">
                    <h5 class="mb-4 flex items-center justify-center font-display text-lg font-bold text-slate-800">
                        <i class="fas fa-balance-scale mr-2 text-brand-500"></i> Summary Matrix
                    </h5>

                    <div class="my-6">
                        <span class="mb-1 block text-xs font-bold uppercase tracking-wide text-slate-500">Total Weight Allocation</span>
                        <h2 class="mb-2 text-3xl font-extrabold text-brand-600" id="total-weight-display">0.00%</h2>
                        <span class="tw-badge inline-flex rounded-lg px-3 py-1.5" id="total-weight-status">Calculating...</span>
                    </div>

                    <hr class="my-6 border-slate-100">

                    <button type="submit" class="tw-btn-primary w-full py-3" id="submit-btn" disabled>
                        <i class="fas fa-save"></i> Save Configurations
                    </button>

                    <a href="{{ route('admin.performance.evaluations.index') }}" class="tw-btn-secondary mt-3 w-full py-2.5">
                        Back to Evaluations
                    </a>
                </div>
            </div>
        </div>
    </form>
</div>
@endsection

@push('scripts')
<script>
    $(document).ready(function() {
        function calculateTotalWeight() {
            let total = 0.00;
            $('.weight-input').each(function() {
                let val = parseFloat($(this).val());
                if (!isNaN(val)) {
                    total += val;
                }
            });

            total = Math.round(total * 100) / 100;
            $('#total-weight-display').text(total.toFixed(2) + '%');

            const diff = Math.abs(total - 100.00);
            const $status = $('#total-weight-status');
            if (diff < 0.01) {
                $status
                    .text('Valid weights (Sum is 100.00%)')
                    .removeClass('bg-rose-600 text-white bg-amber-500 text-slate-900')
                    .addClass('tw-badge-success');
                $('#submit-btn').prop('disabled', false);
            } else {
                $status
                    .text('Error: Must sum structure to 100.00%')
                    .removeClass('tw-badge-success')
                    .addClass('bg-rose-600 text-white');
                $('#submit-btn').prop('disabled', true);
            }
        }

        $(document).on('keyup change', '.weight-input', calculateTotalWeight);
        calculateTotalWeight();
    });
</script>
@endpush

@extends('admin.layouts.app')

@section('breadcrumb')
    <div class="col-sm-6 text-left">
        <h4 class="directory-header">Assign Leaves for {{ $employee->name }}</h4>
        <ol class="breadcrumb saas-breadcrumb">
            <li class="breadcrumb-item"><a href="{{ route('admin.employees.index') }}">Employees</a></li>
            <li class="breadcrumb-item active">Assign Leaves</li>
        </ol>
    </div>
@endsection

@section('content')
    <div class="container-fluid">
        <div class="tw-directory-card max-w-4xl">
            <h5 class="mb-6 font-display text-lg font-extrabold text-slate-900">Assign Leave Balances for 2025</h5>
            <form id="assign-leaves-form" action="{{ route('admin.employees.assignLeaveBalance', $employee->id) }}" method="POST">
                @csrf
                <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
                    @foreach ($leaveTypes as $type)
                        <div>
                            <label for="leave-{{ $type->slug }}" class="tw-filter-label">{{ $type->name }} (Days)</label>
                            <input type="number" name="leaves[{{ $type->slug }}]" id="leave-{{ $type->slug }}"
                                   class="tw-form-input"
                                   min="0" step="0.5" value="{{ $leaveBalances[$type->slug] ?? 0 }}"
                                   placeholder="Enter days for {{ $type->name }}">
                        </div>
                    @endforeach
                </div>
                <div class="mt-6 flex gap-3">
                    <button type="submit" class="tw-btn-primary" id="save-leaves">Save Leave Balances</button>
                    <a href="{{ route('admin.employees.index') }}" class="tw-btn-secondary">Cancel</a>
                </div>
            </form>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        $(document).ready(function () {
            let isProcessing = false;
            $('#assign-leaves-form').on('submit', function (e) {
                e.preventDefault();
                if (isProcessing) return;
                isProcessing = true;

                const $button = $('#save-leaves');
                $button.prop('disabled', true).addClass('opacity-50');
                const $spinner = $('<span class="spinner-border spinner-border-sm ms-1"></span>');
                $button.after($spinner);

                $.ajax({
                    url: $(this).attr('action'),
                    method: 'POST',
                    data: $(this).serialize(),
                    success: function () {
                        toastr.success('Leave balances assigned successfully!');
                        window.location.href = '{{ route("admin.employees.index") }}';
                    },
                    error: function (xhr) {
                        toastr.error(xhr.responseJSON?.message || 'Failed to assign leave balances.');
                    },
                    complete: function () {
                        $spinner.remove();
                        $button.prop('disabled', false).removeClass('opacity-50');
                        isProcessing = false;
                    }
                });
            });
        });
    </script>
@endpush

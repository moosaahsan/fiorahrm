@extends('admin.layouts.app')

@section('title', 'Edit Job Vacancy')

@section('breadcrumb')
    <div class="col-sm-6 text-left">
        <h4 class="page-title interviews-header">Refine Opportunity</h4>
        <ol class="breadcrumb saas-breadcrumb">
            <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Home</a></li>
            <li class="breadcrumb-item"><a href="{{ route('admin.job-postings.index') }}">Job Postings</a></li>
            <li class="breadcrumb-item active">Edit</li>
        </ol>
    </div>
@endsection

@section('content')
    <div class="container-fluid">
        <div class="row justify-content-center">
            <div class="col-lg-10">
                <div class="form-card">
                    <form id="edit-job-form">
                        @csrf
                        @method('PUT')
                        <div class="row">
                            <div class="col-md-8">
                                <div class="form-group mb-4">
                                    <label class="form-label">Job Title</label>
                                    <input type="text" name="title" class="form-control-saas"
                                        value="{{ $job->title }}" required>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group mb-4">
                                    <label class="form-label">Category</label>
                                    <select name="category" class="form-control-saas" required>
                                        <option value="BPO" {{ $job->category == 'BPO' ? 'selected' : '' }}>BPO</option>
                                        <option value="Billing" {{ $job->category == 'Billing' ? 'selected' : '' }}>Billing
                                        </option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group mb-4">
                                    <label class="form-label">Type</label>
                                    <select name="type" class="form-control-saas" required>
                                        <option value="Full Time" {{ $job->type == 'Full Time' ? 'selected' : '' }}>Full Time
                                        </option>
                                        <option value="Part Time" {{ $job->type == 'Part Time' ? 'selected' : '' }}>Part Time
                                        </option>
                                        <option value="Internship" {{ $job->type == 'Internship' ? 'selected' : '' }}>
                                            Internship</option>
                                        <option value="Contract" {{ $job->type == 'Contract' ? 'selected' : '' }}>Contract
                                        </option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group mb-4">
                                    <label class="form-label">Shift</label>
                                    <select name="shift" class="form-control-saas" required>
                                        <option value="Night" {{ $job->shift == 'Night' ? 'selected' : '' }}>Night Shift
                                        </option>
                                        <option value="Day" {{ $job->shift == 'Day' ? 'selected' : '' }}>Day Shift</option>
                                        <option value="Rotating" {{ $job->shift == 'Rotating' ? 'selected' : '' }}>Rotating
                                        </option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group mb-4">
                                    <label class="form-label">Status</label>
                                    <select name="status" class="form-control-saas" required>
                                        <option value="Open" {{ $job->status == 'Open' ? 'selected' : '' }}>Open (Live)
                                        </option>
                                        <option value="Closed" {{ $job->status == 'Closed' ? 'selected' : '' }}>Closed (Draft)
                                        </option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <div class="form-group mb-4">
                            <label class="form-label">Shift Timings</label>
                            <input type="text" name="timings" class="form-control-saas"
                                value="{{ $job->timings }}">
                        </div>

                        <div class="form-group mb-4">
                            <label class="form-label">Description</label>
                            <textarea name="description" class="form-control-saas"
                                rows="4">{{ $job->description }}</textarea>
                        </div>

                        <div class="form-group mb-4">
                            <label class="form-label">Requirements</label>
                            <textarea name="requirements" class="form-control-saas"
                                rows="4">{{ $job->requirements }}</textarea>
                        </div>

                        <div class="form-group mb-4">
                            <label class="form-label">Benefits</label>
                            <textarea name="benefits" class="form-control-saas"
                                rows="3">{{ $job->benefits }}</textarea>
                        </div>

                        <div class="text-right mt-5">
                            <button type="submit" class="btn btn-save shadow-primary">
                                <i class="fas fa-save mr-2"></i> Update Vacancy
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script src="https://cdn.ckeditor.com/ckeditor5/35.0.1/classic/ckeditor.js"></script>
    <script>
        $(document).ready(function () {
            let editors = {};

            ['description', 'requirements', 'benefits'].forEach(id => {
                ClassicEditor
                    .create(document.querySelector(`textarea[name="${id}"]`), {
                        toolbar: ['heading', '|', 'bold', 'italic', 'link', 'bulletedList', 'numberedList', 'blockQuote', 'undo', 'redo']
                    })
                    .then(editor => {
                        editors[id] = editor;
                    })
                    .catch(error => {
                        console.error(error);
                    });
            });

            $('#edit-job-form').on('submit', function (e) {
                e.preventDefault();

                // Sync CKEditor data to textareas
                Object.keys(editors).forEach(key => {
                    document.querySelector(`textarea[name="${key}"]`).value = editors[key].getData();
                });

                let btn = $(this).find('button[type="submit"]');
                btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin mr-2"></i> Updating...');

                $.ajax({
                    url: "{{ route('admin.job-postings.update', $job->id) }}",
                    method: 'POST',
                    data: $(this).serialize(),
                    success: function (response) {
                        toastr.success(response.message);
                        setTimeout(() => window.location.href = "{{ route('admin.job-postings.index') }}", 1000);
                    },
                    error: function (xhr) {
                        btn.prop('disabled', false).html('<i class="fas fa-save mr-2"></i> Update Vacancy');
                        toastr.error('Error updating vacancy. Please check inputs.');
                    }
                });
            });
        });
    </script>
@endpush
@extends('admin.layouts.app')

@section('breadcrumb')
<div class="col-sm-6">
    <h4 class="page-title text-left">Profile Update</h4>
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="#">Admin</a></li>
        <li class="breadcrumb-item active">Profile Update</li>
    </ol>
</div>
@endsection

@section('content')

<div class="profile-edit-page">
<div class="profile-container">
    <form id="profileForm" enctype="multipart/form-data">
        @csrf
        
        <div class="profile-card">
            <!-- Banner -->
            <div class="profile-banner">
                <div class="banner-shapes">
                    <div class="banner-shape" style="width: 200px; height: 200px; top: -50px; left: -50px;"></div>
                    <div class="banner-shape" style="width: 150px; height: 150px; bottom: 20px; right: 20px;"></div>
                </div>
                <button type="button" class="theme-toggle-fixed" onclick="toggleDarkMode()" title="Toggle Dark Mode">
                    <i class="mdi mdi-theme-light-dark"></i>
                </button>
            </div>

            <!-- Header Info -->
            <div class="profile-header-content">
                <div class="avatar-wrapper">
                    @php
                        $profilePic = $user->profile_pic
                            ? Storage::disk('public')->url($user->profile_pic)
                            : "https://ui-avatars.com/api/?name=" . urlencode($user->name) . "&background=6366f1&color=fff&size=200";
                    @endphp
                    <img id="croppedPreview" src="{{ $profilePic }}" class="avatar-image" alt="Profile">
                    <label for="profileImageInput" class="avatar-upload-btn mb-0">
                        <i class="mdi mdi-camera"></i>
                    </label>
                    <input type="file" name="profile_pic" class="d-none" id="profileImageInput" accept="image/*">
                </div>
                <h2 class="user-name">{{ $user->name }}</h2>
                <p class="user-email">{{ $user->email }}</p>
            </div>

            <div class="form-section pt-0">
                <div class="row">
                    <!-- Personal Details -->
                    <div class="col-md-6 mb-4">
                        <h5 class="section-title">
                            <i class="mdi mdi-account-outline text-primary"></i> Personal Information
                        </h5>
                        
                        <div class="form-group">
                            <label>Full Name</label>
                            <input type="text" name="name" value="{{ old('name', $user->name) }}" class="tw-form-input" required>
                        </div>
                        
                        <div class="form-group">
                            <label>Email Address</label>
                            <input type="email" name="email" value="{{ old('email', $user->email) }}" class="tw-form-input" readonly style="opacity: 0.7; cursor: not-allowed;">
                        </div>

                        <div class="form-group">
                            <label>Contact Number</label>
                            <input type="text" name="contact_no" value="{{ old('contact_no', $employee?->contact_no) }}" class="tw-form-input" placeholder="Enter contact number">
                        </div>
                    </div>

                    <!-- Security -->
                    <div class="col-md-6 mb-4">
                        <h5 class="section-title">
                            <i class="mdi mdi-shield-lock-outline text-primary"></i> Security
                        </h5>
                        
                        <div class="form-group">
                            <label>New Password</label>
                            <input type="password" name="password" class="tw-form-input" autocomplete="new-password" placeholder="Leave blank to keep current">
                        </div>

                        <div class="form-group">
                            <label>Confirm Password</label>
                            <input type="password" name="password_confirmation" class="tw-form-input" autocomplete="new-password" placeholder="Confirm new password">
                        </div>
                    </div>
                </div>

                <div class="text-center mt-4 mb-3">
                    <button type="submit" class="btn-save">
                        <i class="mdi mdi-content-save-outline mr-1"></i> Save Changes
                    </button>
                </div>
            </div>
        </div>
        
        <!-- Hidden Crop Element -->
        <div style="display: none;">
            <img id="hiddenCropImage" alt="Hidden Crop">
        </div>
        
    </form>
</div>
</div>

<script>
    let cropper;
    const input = document.getElementById('profileImageInput');
    const preview = document.getElementById('croppedPreview');
    const hiddenImage = document.getElementById('hiddenCropImage');

    // Dark Mode LocalStorage Persistence (Optional enhancement)
    if (localStorage.getItem('admin_theme') === 'dark') {
        document.body.classList.add('dark-mode');
    }

    function toggleDarkMode() {
        document.body.classList.toggle('dark-mode');
        localStorage.setItem('admin_theme', document.body.classList.contains('dark-mode') ? 'dark' : 'light');
    }

    input.addEventListener('change', function (e) {
        const file = e.target.files[0];
        if (!file) return;

        const reader = new FileReader();
        reader.onload = function (event) {    
            hiddenImage.src = event.target.result;
            
            if (cropper) cropper.destroy();

            cropper = new Cropper(hiddenImage, {
                aspectRatio: 1,
                viewMode: 1,
                autoCropArea: 1,
                ready() {
                    updatePreview();
                },
                cropend() {
                    updatePreview();
                }
            });
        };
        reader.readAsDataURL(file);
    });

    function updatePreview() {
        if (!cropper) return;
        const canvas = cropper.getCroppedCanvas({ width: 200, height: 200 });
        preview.src = canvas.toDataURL('image/jpeg');
    }

    document.getElementById('profileForm').addEventListener('submit', function (e) {
        e.preventDefault();
        const formData = new FormData(this);
        const submitBtn = this.querySelector('button[type="submit"]');
        const originalBtnHtml = submitBtn.innerHTML;
        
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<i class="mdi mdi-loading mdi-spin"></i> Saving...';

        if (cropper) {
            cropper.getCroppedCanvas({ width: 300, height: 300 }).toBlob(blob => {
                formData.set('profile_pic', blob, 'profile.jpg');
                sendAjax(formData, submitBtn, originalBtnHtml);
            }, 'image/jpeg');
        } else {
            sendAjax(formData, submitBtn, originalBtnHtml);
        }
    });

    function sendAjax(formData, btn, originalHtml) {
        fetch("{{ route('admin.profile.update') }}", {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                toastr.success(data.message || 'Profile updated successfully!');
                setTimeout(() => location.reload(), 1500);
            } else {
                toastr.error(data.message || 'Update failed.');
                btn.disabled = false;
                btn.innerHTML = originalHtml;
            }
        })
        .catch(err => {
            console.error(err);
            toastr.error('An unexpected error occurred.');
            btn.disabled = false;
            btn.innerHTML = originalHtml;
        });
    }
</script>
@endsection

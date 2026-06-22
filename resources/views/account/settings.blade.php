@extends('layouts.storefront')

@section('title', 'Account Settings — GoBuy')

@section('content')
<section class="py-5">
    <div class="container-small">

        {{-- Breadcrumb --}}
        <nav class="mb-3" aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item">
                    <a href="{{ route('account.dashboard') }}">Account</a>
                </li>
                <li class="breadcrumb-item active" aria-current="page">Settings</li>
            </ol>
        </nav>

        <div class="mb-4">
            <h2 class="mb-1">Account Settings</h2>
            <p class="text-body-tertiary mb-0">Manage your personal details, security, and preferences</p>
        </div>

        @if (session('status'))
            <div class="alert alert-success alert-dismissible fade show mb-4" role="alert">
                <i class="fas fa-check-circle me-2"></i>{{ session('status') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        @if ($errors->any())
            <div class="alert alert-danger alert-dismissible fade show mb-4" role="alert">
                <i class="fas fa-exclamation-triangle me-2"></i>
                <ul class="mb-0 ps-3">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        {{-- Vertical wizard (horizontal on mobile via CSS) --}}
        <x-wizard.engine id="customerSettingsWizard" type="vertical">
            <x-slot:nav>
                <x-wizard.step-nav
                    id="pane-profile"
                    icon="fas fa-user"
                    title="Profile"
                    subtitle="Name & email"
                    step="1"
                    :active="true" />
                <x-wizard.step-nav
                    id="pane-security"
                    icon="fas fa-lock"
                    title="Security"
                    subtitle="Change password"
                    step="2" />
                <x-wizard.step-nav
                    id="pane-preferences"
                    icon="fas fa-sliders-h"
                    title="Preferences"
                    subtitle="Theme & display"
                    step="3" />
                <x-wizard.step-nav
                    id="pane-done"
                    icon="fas fa-check"
                    title="Done"
                    subtitle="All set!"
                    step="4" />
            </x-slot:nav>

            {{-- Step 1: Profile --}}
            <x-wizard.step-content id="pane-profile" step="1" :active="true" formId="form-customer-profile">
                @csrf
                <div class="gb-form-section">
                    <div class="gb-form-section-header">
                        <h5>Personal Information</h5>
                    </div>
                    <div class="gb-form-section-body">
                        <div class="row g-3">
                            <div class="col-12">
                                <label class="form-label" for="customer_name">Full Name</label>
                                <input class="form-control"
                                       type="text"
                                       name="name"
                                       id="customer_name"
                                       value="{{ old('name', $user->name) }}"
                                       placeholder="Your full name"
                                       required>
                                <div class="invalid-feedback">Please enter your full name.</div>
                            </div>
                            <div class="col-12">
                                <label class="form-label" for="customer_email">Email Address</label>
                                <input class="form-control"
                                       type="email"
                                       name="email"
                                       id="customer_email"
                                       value="{{ old('email', $user->email) }}"
                                       placeholder="you@example.com"
                                       required>
                                <div class="invalid-feedback">Please enter a valid email address.</div>
                            </div>
                        </div>
                    </div>
                    <div class="gb-form-section-footer">
                        <button type="submit"
                                formaction="{{ route('account.settings.profile') }}"
                                formmethod="POST"
                                class="btn btn-primary btn-sm px-4">
                            <i class="fas fa-save me-1"></i> Save Profile
                        </button>
                    </div>
                </div>
            </x-wizard.step-content>

            {{-- Step 2: Security --}}
            <x-wizard.step-content id="pane-security" step="2" formId="form-customer-security">
                @csrf
                <div class="gb-form-section">
                    <div class="gb-form-section-header">
                        <h5>Change Password</h5>
                    </div>
                    <div class="gb-form-section-body">
                        <div class="row g-3">
                            <div class="col-12">
                                <label class="form-label" for="customer_current_password">Current Password</label>
                                <input class="form-control"
                                       type="password"
                                       name="current_password"
                                       id="customer_current_password"
                                       placeholder="Enter current password"
                                       required>
                                <div class="invalid-feedback">Please enter your current password.</div>
                            </div>
                            <div class="col-sm-6">
                                <label class="form-label" for="customer_password">New Password</label>
                                <input class="form-control"
                                       type="password"
                                       name="password"
                                       id="customer_password"
                                       data-wizard-password="true"
                                       placeholder="Min. 8 characters"
                                       required>
                                <div class="invalid-feedback">Please enter a new password.</div>
                            </div>
                            <div class="col-sm-6">
                                <label class="form-label" for="customer_password_confirmation">Confirm Password</label>
                                <input class="form-control"
                                       type="password"
                                       name="password_confirmation"
                                       id="customer_password_confirmation"
                                       data-wizard-confirm-password="true"
                                       placeholder="Repeat new password"
                                       required>
                                <div class="invalid-feedback">Passwords do not match.</div>
                            </div>
                        </div>
                    </div>
                    <div class="gb-form-section-footer">
                        <button type="submit"
                                formaction="{{ route('account.settings.security') }}"
                                formmethod="POST"
                                class="btn btn-primary btn-sm px-4">
                            <i class="fas fa-lock me-1"></i> Update Password
                        </button>
                    </div>
                </div>
            </x-wizard.step-content>

            {{-- Step 3: Preferences (Theme) --}}
            <x-wizard.step-content id="pane-preferences" step="3">
                <div class="gb-form-section">
                    <div class="gb-form-section-header">
                        <h5>Display Preferences</h5>
                    </div>
                    <div class="gb-form-section-body">
                        <p class="text-body-tertiary fs-9 mb-4">Choose how GoBuy looks for you. Your preference is saved in your browser.</p>

                        <div class="d-flex flex-column gap-2" id="themeOptions" role="radiogroup" aria-label="Theme preference">
                            {{-- Light --}}
                            <label class="gb-theme-option" for="theme_light" id="label_theme_light">
                                <input type="radio" name="theme_pref" id="theme_light" value="light" class="d-none">
                                <div class="gb-theme-card d-flex align-items-center gap-3">
                                    <span class="gb-theme-preview gb-theme-preview-light">
                                        <span class="gb-theme-preview-header"></span>
                                        <span class="gb-theme-preview-body"></span>
                                    </span>
                                    <div>
                                        <p class="mb-0 fw-semibold text-body-emphasis">Light</p>
                                        <span class="text-body-tertiary fs-10">Clean white background</span>
                                    </div>
                                    <span class="gb-theme-check ms-auto fas fa-check-circle text-primary fs-7"></span>
                                </div>
                            </label>

                            {{-- Dark --}}
                            <label class="gb-theme-option" for="theme_dark" id="label_theme_dark">
                                <input type="radio" name="theme_pref" id="theme_dark" value="dark" class="d-none">
                                <div class="gb-theme-card d-flex align-items-center gap-3">
                                    <span class="gb-theme-preview gb-theme-preview-dark">
                                        <span class="gb-theme-preview-header"></span>
                                        <span class="gb-theme-preview-body"></span>
                                    </span>
                                    <div>
                                        <p class="mb-0 fw-semibold text-body-emphasis">Dark</p>
                                        <span class="text-body-tertiary fs-10">Easy on the eyes at night</span>
                                    </div>
                                    <span class="gb-theme-check ms-auto fas fa-check-circle text-primary fs-7"></span>
                                </div>
                            </label>

                            {{-- System --}}
                            <label class="gb-theme-option" for="theme_system" id="label_theme_system">
                                <input type="radio" name="theme_pref" id="theme_system" value="auto" class="d-none">
                                <div class="gb-theme-card d-flex align-items-center gap-3">
                                    <span class="gb-theme-preview gb-theme-preview-system">
                                        <span class="gb-theme-preview-half gb-theme-preview-half-light"></span>
                                        <span class="gb-theme-preview-half gb-theme-preview-half-dark"></span>
                                    </span>
                                    <div>
                                        <p class="mb-0 fw-semibold text-body-emphasis">System</p>
                                        <span class="text-body-tertiary fs-10">Follows your OS setting</span>
                                    </div>
                                    <span class="gb-theme-check ms-auto fas fa-check-circle text-primary fs-7"></span>
                                </div>
                            </label>
                        </div>
                    </div>
                    <div class="gb-form-section-footer">
                        <button type="button" id="saveThemeBtn" class="btn btn-primary btn-sm px-4">
                            <i class="fas fa-palette me-1"></i> Apply Theme
                        </button>
                    </div>
                </div>
            </x-wizard.step-content>

            {{-- Step 4: Done --}}
            <x-wizard.step-content id="pane-done" step="4">
                <div class="text-center py-5">
                    <div class="mb-4">
                        <span class="d-inline-flex align-items-center justify-content-center rounded-circle bg-success-subtle"
                              style="width:4.5rem;height:4.5rem;">
                            <i class="fas fa-check text-success" style="font-size:1.75rem;"></i>
                        </span>
                    </div>
                    <h4 class="mb-2">All done!</h4>
                    <p class="text-body-tertiary mb-4">Your account is fully configured and up to date.</p>
                    <a href="{{ route('account.dashboard') }}" class="btn btn-primary px-4">
                        <i class="fas fa-home me-1"></i> Back to Dashboard
                    </a>
                </div>
            </x-wizard.step-content>

            <x-slot:footer>
                <x-wizard.footer />
            </x-slot:footer>
        </x-wizard.engine>
    </div>
</section>

@push('scripts')
<script>
(function () {
    'use strict';

    const STORAGE_KEY = 'phoenixTheme';

    // Map Phoenix theme values to radio values
    const phoenixToRadio = { light: 'light', dark: 'dark', auto: 'auto' };

    function getCurrentTheme() {
        try {
            return localStorage.getItem(STORAGE_KEY) || 'auto';
        } catch (_) {
            return 'auto';
        }
    }

    function applyThemeSelection(value) {
        const radios = document.querySelectorAll('[name="theme_pref"]');
        const labels = document.querySelectorAll('.gb-theme-option');

        radios.forEach(r => { r.checked = r.value === value; });
        labels.forEach(l => {
            const inp = l.querySelector('input[type="radio"]');
            const check = l.querySelector('.gb-theme-check');
            if (inp && inp.checked) {
                l.querySelector('.gb-theme-card').classList.add('active');
                if (check) { check.style.opacity = '1'; }
            } else {
                l.querySelector('.gb-theme-card').classList.remove('active');
                if (check) { check.style.opacity = '0'; }
            }
        });
    }

    function applyTheme(value) {
        // 1. Update config and local storage
        if (window.config && window.config.set) {
            window.config.set({ phoenixTheme: value });
        } else {
            try {
                localStorage.setItem(STORAGE_KEY, value);
            } catch (_) {}
        }
        
        // 2. Immediately update the DOM so it takes effect without reload
        document.documentElement.setAttribute('data-bs-theme', value === 'auto'
            ? (window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light')
            : value);
        
        // 3. Sync the main navbar toggle switch silently (preventing infinite loop)
        const checkbox = document.querySelector('[data-theme-control="phoenixTheme"]');
        if (checkbox) {
            checkbox.checked = (value === 'dark');
        }
    }

    document.addEventListener('DOMContentLoaded', () => {
        const savedTheme = getCurrentTheme();
        applyThemeSelection(savedTheme);

        // Update selection on click
        document.querySelectorAll('[name="theme_pref"]').forEach(radio => {
            radio.addEventListener('change', () => applyThemeSelection(radio.value));
        });

        document.querySelectorAll('.gb-theme-option').forEach(label => {
            label.addEventListener('click', () => {
                const inp = label.querySelector('input');
                if (inp) { applyThemeSelection(inp.value); }
            });
        });

        // Apply on button click
        const saveBtn = document.getElementById('saveThemeBtn');
        if (saveBtn) {
            saveBtn.addEventListener('click', () => {
                const selected = document.querySelector('[name="theme_pref"]:checked');
                if (selected) {
                    applyTheme(selected.value);
                    
                    const icon = saveBtn.querySelector('i');
                    if (icon) {
                        icon.className = 'fas fa-check text-success me-1';
                        setTimeout(() => { icon.className = 'fas fa-palette me-1'; }, 2000);
                    }
                }
            });
        }

        // Sync preferences UI when external navbar toggle is clicked
        const navbarToggle = document.querySelector('[data-theme-control="phoenixTheme"]');
        if (navbarToggle) {
            navbarToggle.addEventListener('change', (e) => {
                const newTheme = e.target.checked ? 'dark' : 'light';
                const matchingRadio = document.querySelector(`[name="theme_pref"][value="${newTheme}"]`);
                if (matchingRadio) {
                    matchingRadio.checked = true;
                    applyThemeSelection(newTheme);
                }
            });
        }
    });
})();
</script>
@endpush
@endsection

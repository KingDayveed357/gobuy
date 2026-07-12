@extends('layouts.account')

@section('title', 'Account Settings — GoBuy')

@php
    $pageTitle = 'Settings';
@endphp

@section('account_content')
    <div class="mb-4">
        <h3 class="mb-1 text-body-emphasis">Account Settings</h3>
        <p class="text-body-tertiary mb-0">Manage your personal details, security, and preferences</p>
    </div>

    <!-- <div class="card border-0 shadow-sm overflow-hidden"> -->
        <!-- <div class="card-body p-0"> -->
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
                        <div class="gb-form-section-header px-4 pt-4 pb-0">
                            <h5 class="mb-2">Personal Information</h5>
                        </div>
                        <div class="gb-form-section-body p-4">
                            <div class="row g-3">
                                <div class="col-12">
                                    <label class="form-label fw-bold" for="customer_name">Full Name</label>
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
                                    <label class="form-label fw-bold" for="customer_email">Email Address</label>
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
                        <div class="gb-form-section-footer px-4 pb-4 border-top border-translucent pt-3">
                            <button type="submit"
                                    formaction="{{ route('account.settings.profile') }}"
                                    formmethod="POST"
                                    class="btn btn-primary px-4">
                                <i class="fas fa-save me-1"></i> Save Profile
                            </button>
                        </div>
                    </div>
                </x-wizard.step-content>

                {{-- Step 2: Security --}}
                <x-wizard.step-content id="pane-security" step="2" formId="form-customer-security">
                    @csrf
                    <div class="gb-form-section">
                        <div class="gb-form-section-header px-4 pt-4 pb-0">
                            <h5 class="mb-0">Change Password</h5>
                        </div>
                        <div class="gb-form-section-body p-4">
                            <div class="row g-3">
                                <div class="col-12">
                                    <label class="form-label fw-bold" for="customer_current_password">Current Password</label>
                                    <input class="form-control"
                                           type="password"
                                           name="current_password"
                                           id="customer_current_password"
                                           placeholder="Enter current password"
                                           required>
                                    <div class="invalid-feedback">Please enter your current password.</div>
                                </div>
                                <div class="col-sm-6">
                                    <label class="form-label fw-bold" for="customer_password">New Password</label>
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
                                    <label class="form-label fw-bold" for="customer_password_confirmation">Confirm Password</label>
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
                        <div class="gb-form-section-footer px-4 pb-4 border-top border-translucent pt-3">
                            <button type="submit"
                                    formaction="{{ route('account.settings.security') }}"
                                    formmethod="POST"
                                    class="btn btn-primary px-4">
                                <i class="fas fa-lock me-1"></i> Update Password
                            </button>
                        </div>
                    </div>
                </x-wizard.step-content>

                {{-- Step 3: Preferences (Theme) --}}
                <x-wizard.step-content id="pane-preferences" step="3">
                    <div class="gb-form-section">
                        <div class="gb-form-section-header px-4 pt-4 pb-0">
                            <h5 class="mb-0">Display Preferences</h5>
                        </div>
                        <div class="gb-form-section-body p-4">
                            <p class="text-body-tertiary fs-9 mb-4">Choose how GoBuy looks for you. Your preference is saved in your browser.</p>

                            <div class="d-flex flex-column gap-3" id="themeOptions" role="radiogroup" aria-label="Theme preference">
                                {{-- Light --}}
                                <label class="gb-theme-option w-100" for="theme_light" id="label_theme_light">
                                    <input type="radio" name="theme_pref" id="theme_light" value="light" class="d-none">
                                    <div class="gb-theme-card d-flex align-items-center gap-3 p-3 border rounded-3 transition-base hover-border-primary cursor-pointer">
                                        <span class="gb-theme-preview gb-theme-preview-light">
                                            <span class="gb-theme-preview-header"></span>
                                            <span class="gb-theme-preview-body"></span>
                                        </span>
                                        <div>
                                            <p class="mb-0 fw-bold text-body-emphasis">Light</p>
                                            <span class="text-body-tertiary fs-10">Clean white background</span>
                                        </div>
                                        <span class="gb-theme-check ms-auto fas fa-check-circle text-primary fs-7" style="opacity: 0; transition: opacity 0.2s;"></span>
                                    </div>
                                </label>

                                {{-- Dark --}}
                                <label class="gb-theme-option w-100" for="theme_dark" id="label_theme_dark">
                                    <input type="radio" name="theme_pref" id="theme_dark" value="dark" class="d-none">
                                    <div class="gb-theme-card d-flex align-items-center gap-3 p-3 border rounded-3 transition-base hover-border-primary cursor-pointer">
                                        <span class="gb-theme-preview gb-theme-preview-dark">
                                            <span class="gb-theme-preview-header"></span>
                                            <span class="gb-theme-preview-body"></span>
                                        </span>
                                        <div>
                                            <p class="mb-0 fw-bold text-body-emphasis">Dark</p>
                                            <span class="text-body-tertiary fs-10">Easy on the eyes at night</span>
                                        </div>
                                        <span class="gb-theme-check ms-auto fas fa-check-circle text-primary fs-7" style="opacity: 0; transition: opacity 0.2s;"></span>
                                    </div>
                                </label>

                                {{-- System --}}
                                <label class="gb-theme-option w-100" for="theme_system" id="label_theme_system">
                                    <input type="radio" name="theme_pref" id="theme_system" value="auto" class="d-none">
                                    <div class="gb-theme-card d-flex align-items-center gap-3 p-3 border rounded-3 transition-base hover-border-primary cursor-pointer">
                                        <span class="gb-theme-preview gb-theme-preview-system">
                                            <span class="gb-theme-preview-half gb-theme-preview-half-light"></span>
                                            <span class="gb-theme-preview-half gb-theme-preview-half-dark"></span>
                                        </span>
                                        <div>
                                            <p class="mb-0 fw-bold text-body-emphasis">System</p>
                                            <span class="text-body-tertiary fs-10">Follows your OS setting</span>
                                        </div>
                                        <span class="gb-theme-check ms-auto fas fa-check-circle text-primary fs-7" style="opacity: 0; transition: opacity 0.2s;"></span>
                                    </div>
                                </label>
                            </div>
                        </div>
                        <div class="gb-form-section-footer px-4 pb-4 border-top border-translucent pt-3">
                            <button type="button" id="saveThemeBtn" class="btn btn-primary px-4">
                                <i class="fas fa-palette me-1"></i> Apply Theme
                            </button>
                        </div>
                    </div>
                </x-wizard.step-content>

                {{-- Step 4: Done --}}
                <x-wizard.step-content id="pane-done" step="4">
                    <div class="text-center py-6 px-4">
                        <div class="mb-4">
                            <span class="d-inline-flex align-items-center justify-content-center rounded-circle bg-success-subtle shadow-sm"
                                  style="width:5rem;height:5rem;">
                                <i class="fas fa-check text-success" style="font-size:2rem;"></i>
                            </span>
                        </div>
                        <h4 class="mb-2 text-body-emphasis">All done!</h4>
                        <p class="text-body-tertiary mb-4">Your account is fully configured and up to date.</p>
                        <a href="{{ route('account.dashboard') }}" class="btn btn-primary px-5">
                            <i class="fas fa-home me-2"></i> Back to Dashboard
                        </a>
                    </div>
                </x-wizard.step-content>

                <x-slot:footer>
                    <div class="px-4 pb-4">
                        <x-wizard.footer />
                    </div>
                </x-slot:footer>
            </x-wizard.engine>
        <!-- </div> -->
    <!-- </div> -->
@endsection

@push('scripts')
<style>
    /* Add specific styles to make active card border bold */
    .gb-theme-card.active {
        border-color: var(--phoenix-primary) !important;
        background-color: var(--phoenix-primary-subtle) !important;
    }
</style>
<script>
(function () {
    'use strict';

    const STORAGE_KEY = 'phoenixTheme';

    function getCurrentTheme() {
        try { return localStorage.getItem(STORAGE_KEY) || 'auto'; } catch (_) { return 'auto'; }
    }

    function applyThemeSelection(value) {
        const radios = document.querySelectorAll('[name="theme_pref"]');
        const labels = document.querySelectorAll('.gb-theme-option');

        radios.forEach(r => { r.checked = r.value === value; });
        labels.forEach(l => {
            const inp = l.querySelector('input[type="radio"]');
            const check = l.querySelector('.gb-theme-check');
            const card = l.querySelector('.gb-theme-card');
            if (inp && inp.checked) {
                if (card) card.classList.add('active');
                if (check) check.style.opacity = '1';
            } else {
                if (card) card.classList.remove('active');
                if (check) check.style.opacity = '0';
            }
        });
    }

    function applyTheme(value) {
        if (window.config && window.config.set) { window.config.set({ phoenixTheme: value }); }
        else { try { localStorage.setItem(STORAGE_KEY, value); } catch (_) {} }
        
        document.documentElement.setAttribute('data-bs-theme', value === 'auto'
            ? (window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light')
            : value);
        
        const checkbox = document.querySelector('[data-theme-control="phoenixTheme"]');
        if (checkbox) checkbox.checked = (value === 'dark');
    }

    document.addEventListener('DOMContentLoaded', () => {
        const savedTheme = getCurrentTheme();
        applyThemeSelection(savedTheme);

        document.querySelectorAll('[name="theme_pref"]').forEach(radio => {
            radio.addEventListener('change', () => applyThemeSelection(radio.value));
        });

        document.querySelectorAll('.gb-theme-option').forEach(label => {
            label.addEventListener('click', () => {
                const inp = label.querySelector('input');
                if (inp) {
                    inp.checked = true;
                    applyThemeSelection(inp.value);
                }
            });
        });

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
    });
})();
</script>
@endpush

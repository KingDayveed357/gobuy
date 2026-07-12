@extends('admin.layouts.app')

@section('title', 'Settings — GoBuy Admin')

@section('content')
<div class="mb-4">
    <h2 class="mb-1">Settings</h2>
    <p class="text-body-tertiary mb-0">Manage your profile and security preferences</p>
</div>



<x-wizard.engine id="adminSettingsWizard">
    <x-slot:nav>
        <x-wizard.step-nav
            id="pane-admin-profile"
            icon="fas fa-user"
            title="Profile Info"
            subtitle="Name & email"
            step="1"
            :active="true" />
        <x-wizard.step-nav
            id="pane-admin-security"
            icon="fas fa-lock"
            title="Security"
            subtitle="Change password"
            step="2" />
        <x-wizard.step-nav
            id="pane-admin-notifications"
            icon="fas fa-bell"
            title="Notifications"
            subtitle="Alert preferences"
            step="3" />
    </x-slot:nav>

    {{-- Step 1: Profile --}}
    <x-wizard.step-content id="pane-admin-profile" step="1" :active="true" formId="form-admin-profile">
        @csrf
        <div class="gb-form-section">
            <div class="gb-form-section-header">
                <h5>Personal Information</h5>
            </div>
            <div class="gb-form-section-body">
                <div class="row g-3">
                    <div class="col-12">
                        <label class="form-label" for="admin_name">Full Name</label>
                        <input class="form-control"
                               type="text"
                               name="name"
                               id="admin_name"
                               value="{{ old('name', $admin->name) }}"
                               placeholder="Your full name"
                               required>
                        <div class="invalid-feedback">Please enter your full name.</div>
                    </div>
                    <div class="col-12">
                        <label class="form-label" for="admin_email">Email Address</label>
                        <input class="form-control"
                               type="email"
                               name="email"
                               id="admin_email"
                               value="{{ old('email', $admin->email) }}"
                               placeholder="you@example.com"
                               required>
                        <div class="invalid-feedback">Please enter a valid email address.</div>
                    </div>
                </div>
            </div>
            <div class="gb-form-section-footer">
                <button type="submit"
                        formaction="{{ route('admin.settings.profile') }}"
                        formmethod="POST"
                        class="btn btn-primary btn-sm px-4">
                    <i class="fas fa-save me-1"></i> Save Profile
                </button>
            </div>
        </div>
    </x-wizard.step-content>

    {{-- Step 2: Security. No wizard `formId` here — this pane holds TWO independent
         forms (password + 2FA). Nesting them in one <form> is invalid HTML and was
         why the 2FA toggle silently submitted the password form. --}}
    <x-wizard.step-content id="pane-admin-security" step="2">
        <form id="form-admin-security"
              action="{{ route('admin.settings.security') }}"
              method="POST"
              novalidate
              class="wizard-step-form"
              data-wizard-step-form="2">
            @csrf
            <div class="gb-form-section">
                <div class="gb-form-section-header">
                    <h5>Change Password</h5>
                </div>
                <div class="gb-form-section-body">
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label" for="admin_current_password">Current Password</label>
                            <input class="form-control"
                                   type="password"
                                   name="current_password"
                                   id="admin_current_password"
                                   placeholder="Enter current password"
                                   required>
                            <div class="invalid-feedback">Please enter your current password.</div>
                        </div>
                        <div class="col-sm-6">
                            <label class="form-label" for="admin_password">New Password</label>
                            <input class="form-control"
                                   type="password"
                                   name="password"
                                   id="admin_password"
                                   data-wizard-password="true"
                                   placeholder="Min. 8 characters"
                                   required>
                            <div class="invalid-feedback">Please enter a new password.</div>
                        </div>
                        <div class="col-sm-6">
                            <label class="form-label" for="admin_password_confirmation">Confirm Password</label>
                            <input class="form-control"
                                   type="password"
                                   name="password_confirmation"
                                   id="admin_password_confirmation"
                                   data-wizard-confirm-password="true"
                                   placeholder="Repeat new password"
                                   required>
                            <div class="invalid-feedback">Passwords must match.</div>
                        </div>
                    </div>
                </div>
                <div class="gb-form-section-footer">
                    <button type="submit" class="btn btn-primary btn-sm px-4">
                        <i class="fas fa-lock me-1"></i> Update Password
                    </button>
                </div>
            </div>
        </form>

        @php($me = auth('admin')->user())
        <div class="gb-form-section mt-4">
            <div class="gb-form-section-body border-top border-translucent d-flex flex-wrap justify-content-between align-items-center gap-3">
                <div>
                    <h6 class="mb-1">Two-factor authentication
                        <span class="badge badge-phoenix badge-phoenix-{{ $me->two_factor_enabled ? 'success' : 'secondary' }} ms-1">{{ $me->two_factor_enabled ? 'On' : 'Off' }}</span>
                    </h6>
                    <p class="fs-9 text-body-tertiary mb-0">Get an emailed code each time you sign in — an extra layer of protection.</p>
                </div>
                <form action="{{ route('admin.settings.two-factor') }}" method="POST" class="d-inline">
                    @csrf
                    <button type="submit" class="btn btn-sm {{ $me->two_factor_enabled ? 'btn-phoenix-danger' : 'btn-phoenix-success' }}">
                        {{ $me->two_factor_enabled ? 'Turn off' : 'Turn on' }}
                    </button>
                </form>
            </div>
        </div>
    </x-wizard.step-content>

    {{-- Step 3: Notifications --}}
    <x-wizard.step-content id="pane-admin-notifications" step="3">
        <div class="gb-form-section">
            <div class="gb-form-section-header">
                <h5>Notification Preferences</h5>
            </div>
            <div class="gb-form-section-body">
                <div class="d-flex flex-column gap-3">
                    <div class="d-flex align-items-center justify-content-between py-2 border-bottom border-translucent">
                        <div>
                            <p class="mb-0 fw-semibold text-body-emphasis fs-9">New order alerts</p>
                            <span class="text-body-tertiary fs-10">Get notified when a new order is placed</span>
                        </div>
                        <div class="form-check form-switch ms-3 mb-0">
                            <input class="form-check-input" type="checkbox" role="switch" id="notif_orders" checked>
                        </div>
                    </div>
                    <div class="d-flex align-items-center justify-content-between py-2 border-bottom border-translucent">
                        <div>
                            <p class="mb-0 fw-semibold text-body-emphasis fs-9">Low stock warnings</p>
                            <span class="text-body-tertiary fs-10">Alert when product stock falls below threshold</span>
                        </div>
                        <div class="form-check form-switch ms-3 mb-0">
                            <input class="form-check-input" type="checkbox" role="switch" id="notif_stock" checked>
                        </div>
                    </div>
                    <div class="d-flex align-items-center justify-content-between py-2">
                        <div>
                            <p class="mb-0 fw-semibold text-body-emphasis fs-9">Customer messages</p>
                            <span class="text-body-tertiary fs-10">Receive email when customers send inquiries</span>
                        </div>
                        <div class="form-check form-switch ms-3 mb-0">
                            <input class="form-check-input" type="checkbox" role="switch" id="notif_messages">
                        </div>
                    </div>
                </div>
            </div>
            <div class="gb-form-section-footer">
                <button type="button" class="btn btn-primary btn-sm px-4">
                    <i class="fas fa-save me-1"></i> Save Preferences
                </button>
            </div>
        </div>
    </x-wizard.step-content>

    <x-slot:footer>
        <x-wizard.footer />
    </x-slot:footer>
</x-wizard.engine>
@endsection

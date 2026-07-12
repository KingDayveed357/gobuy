@props(['divider' => true, 'return' => null])

@php
    $providers = collect(config('social.providers', []))->filter(fn ($p) => $p['enabled'] ?? false);
    // A same-origin relative path to return to after auth (e.g. back to checkout).
    $returnQuery = $return ? '?return='.urlencode('/'.ltrim($return, '/')) : '';
@endphp

@if ($providers->isNotEmpty())
    <div class="gb-social-auth">
        @if ($divider)
            <div class="gb-social-divider"><span>or continue with</span></div>
        @endif

        <div class="d-grid gap-2">
            @foreach ($providers as $key => $provider)
                <a href="{{ route('social.redirect', $key).$returnQuery }}"
                   class="btn btn-phoenix-secondary gb-social-btn d-flex align-items-center justify-content-center gap-2"
                   data-social-provider="{{ $key }}"
                   aria-label="Continue with {{ $provider['label'] }}">
                    <span class="gb-social-icon" aria-hidden="true">
                        @switch($key)
                            @case('google')
                                <svg width="18" height="18" viewBox="0 0 18 18" xmlns="http://www.w3.org/2000/svg"><path fill="#4285F4" d="M17.64 9.2c0-.64-.06-1.25-.16-1.84H9v3.48h4.84a4.14 4.14 0 0 1-1.8 2.72v2.26h2.92c1.71-1.57 2.68-3.89 2.68-6.62Z"/><path fill="#34A853" d="M9 18c2.43 0 4.47-.8 5.96-2.18l-2.92-2.26c-.8.54-1.83.86-3.04.86-2.34 0-4.32-1.58-5.03-3.7H.96v2.33A9 9 0 0 0 9 18Z"/><path fill="#FBBC05" d="M3.97 10.72a5.4 5.4 0 0 1 0-3.44V4.95H.96a9 9 0 0 0 0 8.1l3.01-2.33Z"/><path fill="#EA4335" d="M9 3.58c1.32 0 2.5.45 3.44 1.35l2.58-2.58C13.46.89 11.42 0 9 0A9 9 0 0 0 .96 4.95l3.01 2.33C4.68 5.16 6.66 3.58 9 3.58Z"/></svg>
                                @break
                            @case('facebook')
                                <svg width="18" height="18" viewBox="0 0 18 18" xmlns="http://www.w3.org/2000/svg"><path fill="#1877F2" d="M18 9a9 9 0 1 0-10.4 8.89v-6.29H5.3V9h2.3V7c0-2.27 1.35-3.52 3.42-3.52.99 0 2.02.18 2.02.18v2.22h-1.14c-1.12 0-1.47.7-1.47 1.41V9h2.5l-.4 2.6h-2.1v6.29A9 9 0 0 0 18 9Z"/></svg>
                                @break
                            @default
                                <span class="fas fa-right-to-bracket"></span>
                        @endswitch
                    </span>
                    <span class="fw-semibold">Continue with {{ $provider['label'] }}</span>
                </a>
            @endforeach
        </div>
    </div>

    @once
        @push('styles')
            <style>
                .gb-social-auth { margin-top: 1rem; }
                .gb-social-divider { display: flex; align-items: center; text-align: center; margin: 1.25rem 0; color: var(--phoenix-secondary-color, #6c757d); }
                .gb-social-divider::before, .gb-social-divider::after { content: ""; flex: 1; height: 1px; background: var(--phoenix-border-color-translucent, rgba(0,0,0,.1)); }
                .gb-social-divider span { padding: 0 .75rem; font-size: .8rem; }
                .gb-social-btn { padding: .6rem 1rem; border-radius: .6rem; transition: transform .08s ease, box-shadow .15s ease; }
                .gb-social-btn:hover { transform: translateY(-1px); box-shadow: 0 4px 12px -4px rgba(0,0,0,.18); }
                .gb-social-btn:active { transform: translateY(0); }
                .gb-social-btn.is-loading { pointer-events: none; opacity: .75; }
                .gb-social-icon { display: inline-flex; }
            </style>
        @endpush
        @push('scripts')
            <script>
                // Friendly loading state on click — the OAuth redirect can take a moment.
                document.addEventListener('click', function (e) {
                    var btn = e.target.closest('[data-social-provider]');
                    if (btn) { btn.classList.add('is-loading'); }
                });
            </script>
        @endpush
    @endonce
@endif

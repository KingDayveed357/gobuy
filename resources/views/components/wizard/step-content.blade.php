@props(['id', 'step', 'active' => false, 'formId' => null])

<div class="gb-step-pane {{ $active ? 'is-active' : '' }}"
     id="{{ $id }}"
     role="tabpanel"
     data-gb-step="{{ $step }}"
     aria-labelledby="step-{{ $step }}">
    @if($formId)
        <form id="{{ $formId }}"
              novalidate
              class="wizard-step-form"
              data-wizard-step-form="{{ $step }}">
            {{ $slot }}
        </form>
    @else
        {{ $slot }}
    @endif
</div>

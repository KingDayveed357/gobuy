<?php

namespace App\Livewire\Admin\Register;

use App\Modules\Operations\Register\Exceptions\RegisterException;
use App\Modules\Operations\Register\Models\CashSession;
use App\Modules\Operations\Register\Services\RegisterService;
use App\Support\Money;
use Livewire\Attributes\Computed;
use Livewire\Component;

/**
 * Open and close the business day. While open it shows what each tender should
 * total from the day's walk-in sales; at close the user counts the drawer, the
 * POS and transfers, and sees the variance — the notebook arithmetic, done.
 */
class Register extends Component
{
    // Open form.
    public string $openingFloat = '0';

    // Close form.
    public string $countedCash = '';

    public string $countedPos = '';

    public string $countedTransfer = '';

    public string $note = '';

    #[Computed]
    public function session(): ?CashSession
    {
        return CashSession::current();
    }

    /**
     * Live per-tender {expected, counted, variance} for the open session, so the
     * drawer count reconciles as it is typed.
     *
     * @return array<string, array{label: string, expected: Money, counted: Money, variance: Money}>
     */
    #[Computed]
    public function preview(): array
    {
        $session = $this->session();
        if (! $session) {
            return [];
        }

        $expected = $session->expected();
        $counted = [
            'cash' => Money::fromNaira($this->normalize($this->countedCash ?: '0')),
            'pos' => Money::fromNaira($this->normalize($this->countedPos ?: '0')),
            'transfer' => Money::fromNaira($this->normalize($this->countedTransfer ?: '0')),
        ];

        $labels = ['cash' => 'Cash', 'pos' => 'POS terminal', 'transfer' => 'Bank transfer'];
        $out = [];
        foreach ($labels as $key => $label) {
            $out[$key] = [
                'label' => $label,
                'expected' => $expected[$key],
                'counted' => $counted[$key],
                'variance' => $counted[$key]->minus($expected[$key]),
            ];
        }

        return $out;
    }

    public function open(RegisterService $register): void
    {
        try {
            $register->open(auth('admin')->user(), Money::fromNaira($this->normalize($this->openingFloat)));
        } catch (RegisterException $e) {
            $this->dispatch('toast', type: 'error', message: $e->getMessage());

            return;
        }

        unset($this->session);
        $this->reset(['openingFloat']);
        $this->openingFloat = '0';
        $this->dispatch('toast', type: 'success', message: 'Register opened. Have a good day of sales!');
    }

    public function close(RegisterService $register): void
    {
        $session = $this->session();
        if (! $session) {
            return;
        }

        $register->close(
            $session,
            Money::fromNaira($this->normalize($this->countedCash)),
            Money::fromNaira($this->normalize($this->countedPos)),
            Money::fromNaira($this->normalize($this->countedTransfer)),
            $this->note ?: null,
            auth('admin')->user(),
        );

        unset($this->session);
        $this->reset(['countedCash', 'countedPos', 'countedTransfer', 'note']);
        $this->dispatch('toast', type: 'success', message: 'Register closed and reconciled.');
    }

    private function normalize(string $value): float
    {
        return max(0, (float) str_replace([',', ' '], '', $value));
    }

    public function render()
    {
        return view('livewire.admin.register.register', [
            'history' => CashSession::query()->whereNotNull('closed_at')
                ->with(['openedBy:id,name', 'closedBy:id,name'])
                ->latest('closed_at')->limit(10)->get(),
        ]);
    }
}

<?php

namespace App\Actions\Pos;

use App\Enums\JournalType;
use App\Enums\PosSessionStatus;
use App\Models\Journal;
use App\Models\PosConfig;
use App\Models\PosPaymentMethod;
use App\Models\PosSession;
use App\Models\Team;
use App\Models\Warehouse;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SavePosConfig
{
    /**
     * Create or update a POS config of the team and sync the payment methods it accepts.
     *
     * The warehouse, the sales journal and the payment methods must belong to the team. Only active payment methods can
     * be chosen, except the ones the config already accepts, so saving an old config does not fail because a method was
     * deactivated later.
     *
     * @param  array<string, mixed>  $attributes
     *
     * @throws ValidationException
     */
    public function handle(Team $team, array $attributes, ?PosConfig $config = null): PosConfig
    {
        return DB::transaction(function () use ($team, $attributes, $config) {
            $paymentMethodIds = array_values(array_unique(array_map('intval', $attributes['payment_method_ids'] ?? [])));
            unset($attributes['payment_method_ids']);

            $this->ensureWarehouse($team, (int) $attributes['warehouse_id']);
            $this->ensureSalesJournal($team, (int) $attributes['sales_journal_id']);
            $this->ensureCodeIsFree($team, (string) $attributes['code'], $config);
            $this->ensurePaymentMethods($team, $paymentMethodIds, $config);

            if ($config !== null) {
                $this->ensureNoOpenSessionConflict($config, $attributes);
            }

            $config ??= new PosConfig(['team_id' => $team->id]);
            $config->fill($attributes)->save();
            $config->paymentMethods()->sync($paymentMethodIds);

            return $config;
        });
    }

    /**
     * Ensure the warehouse belongs to the team.
     *
     * @throws ValidationException
     */
    protected function ensureWarehouse(Team $team, int $warehouseId): void
    {
        if (! Warehouse::query()->where('team_id', $team->id)->whereKey($warehouseId)->exists()) {
            throw ValidationException::withMessages(['warehouse_id' => __('The warehouse must belong to the team.')]);
        }
    }

    /**
     * Ensure the journal is a sales journal of the team.
     *
     * @throws ValidationException
     */
    protected function ensureSalesJournal(Team $team, int $journalId): void
    {
        $valid = Journal::query()->where('team_id', $team->id)->whereKey($journalId)->where('type', JournalType::Sales->value)->exists();

        if (! $valid) {
            throw ValidationException::withMessages(['sales_journal_id' => __('The sales journal must be a sales journal of the team.')]);
        }
    }

    /**
     * Ensure no other config of the team uses the code.
     *
     * @throws ValidationException
     */
    protected function ensureCodeIsFree(Team $team, string $code, ?PosConfig $config): void
    {
        $taken = PosConfig::query()
            ->where('team_id', $team->id)
            ->where('code', $code)
            ->when($config !== null, fn ($query) => $query->whereKeyNot($config->id))
            ->exists();

        if ($taken) {
            throw ValidationException::withMessages(['code' => __('The code is already used by another POS.')]);
        }
    }

    /**
     * Ensure every chosen payment method is an active method of the team, or one the config already accepts.
     *
     * @param  list<int>  $paymentMethodIds
     *
     * @throws ValidationException
     */
    protected function ensurePaymentMethods(Team $team, array $paymentMethodIds, ?PosConfig $config): void
    {
        $accepted = $config?->paymentMethods()->pluck('pos_payment_methods.id')->all() ?? [];

        $allowed = PosPaymentMethod::query()
            ->where('team_id', $team->id)
            ->whereIn('id', $paymentMethodIds)
            ->where(fn ($query) => $query->where('is_active', true)->orWhereIn('id', $accepted))
            ->count();

        if ($allowed !== count($paymentMethodIds)) {
            throw ValidationException::withMessages(['payment_method_ids' => __('The payment methods must be active methods of the team.')]);
        }
    }

    /**
     * Refuse the changes that would break an open session of the config.
     *
     * Deactivating the store, moving it to another warehouse or sales journal, and switching cash control would make the
     * open session end under different rules than it started with.
     *
     * @param  array<string, mixed>  $attributes
     *
     * @throws ValidationException
     */
    protected function ensureNoOpenSessionConflict(PosConfig $config, array $attributes): void
    {
        $hasOpenSession = PosSession::query()
            ->where('pos_config_id', $config->id)
            ->where('status', PosSessionStatus::Open->value)
            ->exists();

        if (! $hasOpenSession) {
            return;
        }

        $messages = [];

        if (array_key_exists('is_active', $attributes) && ! $attributes['is_active'] && $config->is_active) {
            $messages['is_active'] = __('A POS store with an open session cannot be deactivated.');
        }

        foreach (['warehouse_id', 'sales_journal_id'] as $field) {
            if (array_key_exists($field, $attributes) && (int) $attributes[$field] !== $config->{$field}) {
                $messages[$field] = __('This setting cannot be changed while the POS store has an open session.');
            }
        }

        if (array_key_exists('cash_control', $attributes) && (bool) $attributes['cash_control'] !== $config->cash_control) {
            $messages['cash_control'] = __('This setting cannot be changed while the POS store has an open session.');
        }

        if ($messages !== []) {
            throw ValidationException::withMessages($messages);
        }
    }
}

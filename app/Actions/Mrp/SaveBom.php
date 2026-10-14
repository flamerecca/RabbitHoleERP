<?php

namespace App\Actions\Mrp;

use App\Models\Bom;
use App\Models\Product;
use App\Models\Team;
use App\Models\Unit;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SaveBom
{
    /**
     * Create or update a bill of materials with its items.
     *
     * The team row is locked while validating and saving, so concurrent saves of one team cannot both pass the
     * active bom and cycle checks.
     *
     * The data comes from a form, so it is validated here instead of trusting its shape: `product_id`, `quantity`,
     * optional `is_active` and `items`, a list of `component_product_id`, `quantity` and `unit_id`.
     *
     * @param  array<string, mixed>  $data
     *
     * @throws ValidationException
     */
    public function handle(Team $team, array $data, ?Bom $bom = null): Bom
    {
        abort_unless($bom === null || $bom->team_id === $team->id, 404);

        $isActive = (bool) ($data['is_active'] ?? true);

        return DB::transaction(function () use ($team, $data, $isActive, $bom) {
            Team::query()->whereKey($team->id)->lockForUpdate()->first();

            $this->validate($team, $data, $isActive, $bom);

            $bom ??= new Bom(['team_id' => $team->id]);
            $bom->fill([
                'product_id' => $data['product_id'],
                'quantity' => $data['quantity'],
                'is_active' => $isActive,
            ])->save();

            $bom->items()->delete();
            $bom->items()->createMany(array_map(fn (array $item) => [
                'component_product_id' => $item['component_product_id'],
                'quantity' => $item['quantity'],
                'unit_id' => $item['unit_id'],
            ], $data['items']));

            return $bom->load('items');
        });
    }

    /**
     * @param  array<string, mixed>  $data
     *
     * @throws ValidationException
     */
    protected function validate(Team $team, array $data, bool $isActive, ?Bom $bom): void
    {
        $items = $data['items'] ?? [];

        if ((float) $data['quantity'] <= 0) {
            $this->fail('quantity', __('The output quantity must be greater than zero.'));
        }

        if ($items === []) {
            $this->fail('items', __('A bill of materials needs at least one component.'));
        }

        $finished = Product::query()->where('team_id', $team->id)->whereKey($data['product_id'])->first();

        if ($finished === null) {
            $this->fail('product_id', __('The product does not belong to this team.'));
        }

        $componentIds = array_column($items, 'component_product_id');

        if (count($componentIds) !== count(array_unique($componentIds))) {
            $this->fail('items', __('A component can appear only once.'));
        }

        if (in_array($finished->id, $componentIds, true)) {
            $this->fail('items', __('A product cannot be a component of itself.'));
        }

        $components = Product::query()->with('unit')->where('team_id', $team->id)->whereKey($componentIds)->get()->keyBy('id');

        foreach ($items as $item) {
            $component = $components->get($item['component_product_id']);

            if ($component === null) {
                $this->fail('items', __('The component does not belong to this team.'));
            }

            if ((float) $item['quantity'] <= 0) {
                $this->fail('items', __('The component quantity must be greater than zero.'));
            }

            $unit = Unit::query()->where('team_id', $team->id)->whereKey($item['unit_id'])->first();

            if ($unit === null || $unit->category_id !== $component->unit->category_id) {
                $this->fail('items', __('The unit must be in the unit category of the component.'));
            }
        }

        if (! $isActive) {
            return;
        }

        $hasOtherActive = Bom::query()->where('team_id', $team->id)->where('product_id', $finished->id)->where('is_active', true)
            ->when($bom !== null, fn ($query) => $query->whereKeyNot($bom->id))->exists();

        if ($hasOtherActive) {
            $this->fail('product_id', __('The product already has an active bill of materials.'));
        }

        if ($this->reaches($team, $componentIds, $finished->id, $bom)) {
            $this->fail('items', __('The components would form a cycle.'));
        }
    }

    /**
     * Determine if walking the active boms down from the given products reaches the target product.
     *
     * @param  list<int>  $startIds
     */
    protected function reaches(Team $team, array $startIds, int $targetId, ?Bom $ignoring): bool
    {
        $children = Bom::query()->with('items')->where('team_id', $team->id)->where('is_active', true)
            ->when($ignoring !== null, fn ($query) => $query->whereKeyNot($ignoring->id))->get()
            ->mapWithKeys(fn (Bom $bom) => [$bom->product_id => $bom->items->pluck('component_product_id')->all()]);

        $visited = [];
        $stack = $startIds;

        while ($stack !== []) {
            $productId = array_pop($stack);

            if ($productId === $targetId) {
                return true;
            }

            if (isset($visited[$productId])) {
                continue;
            }

            $visited[$productId] = true;
            array_push($stack, ...($children->get($productId) ?? []));
        }

        return false;
    }

    /**
     * @throws ValidationException
     */
    protected function fail(string $key, string $message): never
    {
        throw ValidationException::withMessages([$key => $message]);
    }
}

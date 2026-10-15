<?php

namespace App\Actions\Sales;

use App\Models\QuotationTemplate;
use App\Models\Team;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

class SaveQuotationTemplate
{
    /**
     * Create or update a quotation template, replacing its lines when lines are given.
     *
     * @param  array<string, mixed>  $attributes
     */
    public function handle(Team $team, array $attributes, ?QuotationTemplate $template = null): QuotationTemplate
    {
        return DB::transaction(function () use ($team, $attributes, $template) {
            $template ??= new QuotationTemplate(['team_id' => $team->id]);
            $template->fill(Arr::only($attributes, ['name', 'is_active']))->save();

            if (array_key_exists('items', $attributes)) {
                $template->items()->get()->each->delete();
                $template->items()->createMany(array_map(
                    fn (array $line) => Arr::only($line, ['product_id', 'quantity']),
                    array_values($attributes['items']),
                ));
            }

            return $template->load('items');
        });
    }
}

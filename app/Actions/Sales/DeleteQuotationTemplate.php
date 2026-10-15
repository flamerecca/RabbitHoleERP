<?php

namespace App\Actions\Sales;

use App\Models\QuotationTemplate;
use Illuminate\Support\Facades\DB;

class DeleteQuotationTemplate
{
    /**
     * Delete the template together with its lines one by one so each removal reaches the activity log, leaving the sales orders created from it untouched.
     */
    public function handle(QuotationTemplate $template): void
    {
        DB::transaction(function () use ($template) {
            $template->items()->get()->each->delete();
            $template->delete();
        });
    }
}

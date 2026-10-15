<?php

namespace App\Actions\Sales;

use App\Models\QuotationTemplate;

class DeleteQuotationTemplate
{
    /**
     * Delete the template together with its lines, leaving the sales orders created from it untouched.
     */
    public function handle(QuotationTemplate $template): void
    {
        $template->delete();
    }
}

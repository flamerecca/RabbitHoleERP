<?php

namespace App\Data;

use Illuminate\Database\Eloquent\Model;

/**
 * @template-covariant TDocument of Model
 */
readonly class DocumentResult
{
    /**
     * @param  TDocument  $document
     * @param  list<string>  $warnings  The messages of the warning document rules that matched.
     */
    public function __construct(
        public Model $document,
        public array $warnings = [],
    ) {
        //
    }
}
